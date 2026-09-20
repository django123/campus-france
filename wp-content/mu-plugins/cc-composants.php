<?php
/**
 * Plugin Name: CC – Composants
 * Description: Shortcodes des composants réutilisables. Gabarits surchargeables dans le thème.
 *
 * Principe
 * --------
 * Tout élément répété sur plusieurs pages est codé une fois ici et exposé en
 * shortcode. Elementor sert à la mise en page, pas à dupliquer des composants :
 * un bloc recopié dans cinq pages, c'est cinq endroits à corriger le jour où il
 * change.
 *
 * Séparation des rôles
 * --------------------
 *   mu-plugins (ce fichier) : données, règles métier, mise en file du CSS.
 *   thème (template-parts/) : balisage. Surchargeable — locate_template() va
 *   chercher d'abord dans le thème enfant, puis dans le parent.
 *
 * Chargement du CSS
 * -----------------
 * Le socle (boutons, sections, cartes) est dans style.css, donc toujours là.
 * Chaque composant met en file SA feuille au moment où il est rendu. Comme le
 * rendu a lieu après <head>, WordPress imprime ces feuilles dans le pied de
 * page : c'est le prix à payer pour ne pas charger huit fichiers sur des pages
 * qui n'en utilisent aucun.
 */

defined( 'ABSPATH' ) || exit;

/** Version des feuilles de composants, pour le cache navigateur. */
function cc_composants_version( $fichier ) {
	$chemin = get_stylesheet_directory() . '/assets/css/composants/' . $fichier;

	if ( wp_get_environment_type() === 'local' && file_exists( $chemin ) ) {
		return (string) filemtime( $chemin );
	}

	return wp_get_theme()->get( 'Version' );
}

/**
 * Met en file la feuille d'un composant, une seule fois par page.
 *
 * @param string $nom Nom du fichier sans extension, dans assets/css/composants/.
 */
function cc_charger_style( $nom ) {
	$handle = 'cc-composant-' . $nom;

	if ( wp_style_is( $handle, 'enqueued' ) ) {
		return;
	}

	wp_enqueue_style(
		$handle,
		get_stylesheet_directory_uri() . '/assets/css/composants/' . $nom . '.css',
		array( 'campus-connect-child' ),
		cc_composants_version( $nom . '.css' )
	);
}

/**
 * Rend un gabarit de composant et renvoie son HTML.
 *
 * @param string $nom     Nom du gabarit dans template-parts/, sans extension.
 * @param array  $donnees Variables mises à disposition du gabarit.
 * @return string
 */
function cc_gabarit( $nom, array $donnees = array() ) {
	$fichier = locate_template( 'template-parts/' . $nom . '.php' );

	if ( ! $fichier ) {
		// Silencieux côté visiteur : un gabarit manquant ne doit pas afficher
		// un message technique sur le site public.
		if ( current_user_can( 'edit_posts' ) ) {
			return sprintf(
				'<div class="cc-vide">%s</div>',
				esc_html( sprintf( 'Gabarit introuvable : template-parts/%s.php', $nom ) )
			);
		}
		return '';
	}

	// Rendues accessibles au gabarit inclus ci-dessous.
	$cc = $donnees;

	ob_start();
	include $fichier;

	return (string) ob_get_clean();
}

/**
 * Message affiché à la place d'un composant vide.
 *
 * Visible uniquement des personnes qui peuvent y remédier : un visiteur ne
 * doit jamais lire « aucun témoignage publiable ».
 *
 * @param string $raison
 * @return string
 */
function cc_composant_vide( $raison ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return '';
	}

	return sprintf( '<div class="cc-vide">%s</div>', esc_html( $raison ) );
}

/**
 * URL de la page de conversion, cible de tous les CTA principaux.
 *
 * @return string
 */
function cc_url_formulaire() {
	$page = get_page_by_path( 'je-veux-etudier-en-france' );

	return $page ? (string) get_permalink( $page ) : home_url( '/' );
}

/* ================================================================== *
 * [cc_atouts]
 * ================================================================== */

function cc_sc_atouts( $atts ) {
	$atts = shortcode_atts(
		array(
			'titre' => '',
		),
		$atts,
		'cc_atouts'
	);

	$atouts = function_exists( 'cc_atouts' ) ? cc_atouts() : array();

	if ( empty( $atouts ) ) {
		return cc_composant_vide( 'Aucun point fort renseigné. Réglages → Campus Connect → Points forts.' );
	}

	cc_charger_style( 'atouts' );

	return cc_gabarit( 'cc-atouts', array(
		'atouts' => $atouts,
		'titre'  => $atts['titre'],
	) );
}
add_shortcode( 'cc_atouts', 'cc_sc_atouts' );

/* ================================================================== *
 * [cc_parcours]
 * ================================================================== */

/**
 * Les huit étapes du parcours.
 *
 * Elles viennent du cahier des charges et ne bougent pas d'une page à
 * l'autre : un tableau filtrable suffit, inutile d'en faire un CPT que
 * personne n'éditera. Le filtre `cc_parcours_etapes` permet de les ajuster
 * sans modifier ce fichier.
 *
 * Formulation : les étapes 3, 4 et 5 dépendent de décisions administratives
 * extérieures. Elles décrivent donc un accompagnement à la démarche, jamais
 * un résultat acquis — la règle du projet interdit toute promesse de résultat.
 *
 * @return array<int, array{titre:string, texte:string}>
 */
function cc_parcours_etapes() {
	$etapes = array(
		array(
			'titre' => 'Évaluation gratuite',
			'texte' => 'Un premier échange pour comprendre votre projet, votre parcours et vos contraintes.',
		),
		array(
			'titre' => 'Recherche d\'établissement',
			'texte' => 'Sélection d\'établissements publics et privés cohérents avec votre profil.',
		),
		array(
			'titre' => 'Admission',
			'texte' => 'Préparation et dépôt des candidatures, puis suivi des réponses reçues.',
		),
		array(
			'titre' => 'Dossier Campus France',
			'texte' => 'Constitution du dossier et préparation de l\'entretien.',
		),
		array(
			'titre' => 'Visa',
			'texte' => 'Préparation du dossier de demande et du rendez-vous consulaire.',
		),
		array(
			'titre' => 'Logement',
			'texte' => 'Recherche avec nos bailleurs partenaires, dossier de garant, assurance AVI.',
		),
		array(
			'titre' => 'Départ',
			'texte' => 'Organisation du voyage et des dernières formalités avant l\'arrivée.',
		),
		array(
			'titre' => 'Installation et suivi',
			'texte' => 'Démarches des premières semaines et accompagnement une fois sur place.',
		),
	);

	return (array) apply_filters( 'cc_parcours_etapes', $etapes );
}

function cc_sc_parcours( $atts ) {
	$atts = shortcode_atts(
		array(
			'titre'   => '',
			'compact' => '',
		),
		$atts,
		'cc_parcours'
	);

	$etapes = cc_parcours_etapes();

	if ( empty( $etapes ) ) {
		return cc_composant_vide( 'Aucune étape de parcours définie.' );
	}

	cc_charger_style( 'parcours' );

	return cc_gabarit( 'cc-parcours', array(
		'etapes'  => $etapes,
		'titre'   => $atts['titre'],
		'compact' => ! empty( $atts['compact'] ) && $atts['compact'] !== '0',
	) );
}
add_shortcode( 'cc_parcours', 'cc_sc_parcours' );

/* ================================================================== *
 * [cc_packs situation="pre|post"]
 * ================================================================== */

function cc_sc_packs( $atts ) {
	$atts = shortcode_atts(
		array(
			'situation' => '',
			'titre'     => '',
		),
		$atts,
		'cc_packs'
	);

	$args = array(
		'post_type'      => 'pack',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_key'       => 'ordre_affichage',
		'orderby'        => array(
			'meta_value_num' => 'ASC',
			'title'          => 'ASC',
		),
	);

	// « pre » et « post » sont les raccourcis attendus dans les pages ;
	// les slugs réels des termes sont plus longs.
	$correspondance = array(
		'pre'            => 'pre-admission',
		'pre-admission'  => 'pre-admission',
		'post'           => 'post-admission',
		'post-admission' => 'post-admission',
	);

	$situation = strtolower( trim( $atts['situation'] ) );

	if ( $situation !== '' ) {
		if ( ! isset( $correspondance[ $situation ] ) ) {
			return cc_composant_vide( sprintf( 'Situation inconnue : « %s ». Valeurs acceptées : pre, post.', $situation ) );
		}

		$args['tax_query'] = array(
			array(
				'taxonomy' => 'situation',
				'field'    => 'slug',
				'terms'    => $correspondance[ $situation ],
			),
		);
	}

	$packs = get_posts( $args );

	if ( empty( $packs ) ) {
		return cc_composant_vide( 'Aucun pack publié pour cette situation.' );
	}

	cc_charger_style( 'packs' );

	return cc_gabarit( 'cc-packs', array(
		'packs'     => $packs,
		'titre'     => $atts['titre'],
		'url_offre' => cc_url_formulaire(),
	) );
}
add_shortcode( 'cc_packs', 'cc_sc_packs' );

/* ================================================================== *
 * [cc_temoignages limite="3"]
 * ================================================================== */

function cc_sc_temoignages( $atts ) {
	$atts = shortcode_atts(
		array(
			'limite' => '3',
			'titre'  => '',
		),
		$atts,
		'cc_temoignages'
	);

	$limite = max( 1, (int) $atts['limite'] );

	/*
	 * Le filtre sur l'accord écrit est une RÈGLE, pas une option : aucun
	 * attribut de shortcode ne permet de l'outrepasser. Publier le prénom et
	 * la citation d'une personne sans son accord écrit est un problème de
	 * droit, pas un choix de mise en page.
	 *
	 * Le tri se fait après filtrage plutôt que par meta_query pour rester
	 * lisible et parce que le volume de témoignages restera faible.
	 */
	$tous = get_posts( array(
		'post_type'      => 'temoignage',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	$publiables = array_values( array_filter( $tous, 'cc_temoignage_diffusable' ) );

	if ( empty( $publiables ) ) {
		return cc_composant_vide(
			sprintf(
				'Aucun témoignage diffusable : %d témoignage(s) publié(s), aucun avec accord écrit. Cocher « Accord écrit obtenu » sur les témoignages concernés.',
				count( $tous )
			)
		);
	}

	cc_charger_style( 'temoignages' );

	return cc_gabarit( 'cc-temoignages', array(
		'temoignages' => array_slice( $publiables, 0, $limite ),
		'titre'       => $atts['titre'],
	) );
}
add_shortcode( 'cc_temoignages', 'cc_sc_temoignages' );

/* ================================================================== *
 * [cc_partenaires type="..."]
 * ================================================================== */

function cc_sc_partenaires( $atts ) {
	$atts = shortcode_atts(
		array(
			'type'  => '',
			'titre' => '',
		),
		$atts,
		'cc_partenaires'
	);

	$args = array(
		'post_type'      => 'partenaire',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$type = sanitize_title( $atts['type'] );

	if ( $type !== '' ) {
		if ( ! term_exists( $type, 'type_partenaire' ) ) {
			return cc_composant_vide( sprintf( 'Type de partenaire inconnu : « %s ».', $type ) );
		}

		$args['tax_query'] = array(
			array(
				'taxonomy' => 'type_partenaire',
				'field'    => 'slug',
				'terms'    => $type,
			),
		);
	}

	$tous = get_posts( $args );

	/*
	 * Même logique que pour les témoignages : un logo sur une page
	 * « Nos partenaires » laisse entendre qu'une convention existe. Seuls les
	 * partenariats formalisés sont affichés, sans échappatoire par attribut.
	 */
	$affichables = array_values( array_filter( $tous, 'cc_partenaire_affichable' ) );

	if ( empty( $affichables ) ) {
		return cc_composant_vide(
			sprintf(
				'Aucun partenaire affichable : %d partenaire(s) trouvé(s), aucun avec partenariat formalisé.',
				count( $tous )
			)
		);
	}

	cc_charger_style( 'partenaires' );

	return cc_gabarit( 'cc-partenaires', array(
		'partenaires' => $affichables,
		'titre'       => $atts['titre'],
	) );
}
add_shortcode( 'cc_partenaires', 'cc_sc_partenaires' );

/* ================================================================== *
 * [cc_destinations]
 * ================================================================== */

function cc_sc_destinations( $atts ) {
	$atts = shortcode_atts(
		array(
			'titre' => '',
		),
		$atts,
		'cc_destinations'
	);

	$destinations = get_posts( array(
		'post_type'      => 'destination',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	) );

	if ( empty( $destinations ) ) {
		return cc_composant_vide( 'Aucune destination publiée.' );
	}

	/*
	 * Les destinations actives d'abord : une offre disponible ne doit pas
	 * passer après deux offres qui ne le sont pas.
	 */
	usort( $destinations, function ( $a, $b ) {
		$pa = cc_destination_statut( $a->ID ) === 'active' ? 0 : 1;
		$pb = cc_destination_statut( $b->ID ) === 'active' ? 0 : 1;

		return ( $pa === $pb ) ? strcmp( $a->post_title, $b->post_title ) : ( $pa <=> $pb );
	} );

	cc_charger_style( 'destinations' );

	return cc_gabarit( 'cc-destinations', array(
		'destinations' => $destinations,
		'titre'        => $atts['titre'],
	) );
}
add_shortcode( 'cc_destinations', 'cc_sc_destinations' );

/* ================================================================== *
 * [cc_faq]
 * ================================================================== */

function cc_sc_faq( $atts ) {
	$atts = shortcode_atts(
		array(
			'limite' => '0',
			'titre'  => '',
		),
		$atts,
		'cc_faq'
	);

	$limite = (int) $atts['limite'];

	$questions = get_posts( array(
		'post_type'      => 'faq',
		'post_status'    => 'publish',
		'posts_per_page' => $limite > 0 ? $limite : -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	) );

	if ( empty( $questions ) ) {
		return cc_composant_vide( 'Aucune question publiée. Questions fréquentes → Ajouter.' );
	}

	cc_charger_style( 'faq' );

	return cc_gabarit( 'cc-faq', array(
		'questions' => $questions,
		'titre'     => $atts['titre'],
	) );
}
add_shortcode( 'cc_faq', 'cc_sc_faq' );

/* ================================================================== *
 * [cc_cta_final]
 * ================================================================== */

function cc_sc_cta_final( $atts ) {
	$atts = shortcode_atts(
		array(
			'titre' => 'Prêt à commencer ?',
			'texte' => '',
			'pays'  => 'fr',
		),
		$atts,
		'cc_cta_final'
	);

	cc_charger_style( 'cta-final' );

	return cc_gabarit( 'cc-cta-final', array(
		'titre'        => $atts['titre'],
		'texte'        => $atts['texte'],
		'url_formulaire' => cc_url_formulaire(),
		'url_whatsapp' => function_exists( 'cc_whatsapp_link' ) ? cc_whatsapp_link( $atts['pays'] ) : '',
	) );
}
add_shortcode( 'cc_cta_final', 'cc_sc_cta_final' );

/* ================================================================== *
 * [cc_exemple composant="..."] — réservé au styleguide
 *
 * Deux composants ne rendent rien tant qu'aucune donnée n'a passé leur
 * contrôle : les témoignages exigent un accord écrit, les partenaires une
 * convention signée. Sur du contenu fictif, aucun ne remplit ces conditions —
 * et il est hors de question de cocher ces cases sur des personnes et des
 * structures inventées juste pour remplir une page.
 *
 * Ce shortcode rend donc un exemple de MISE EN FORME, à partir de données
 * écrites en dur et annoncées comme telles. Il permet de voir si le CSS casse,
 * sans affaiblir la règle ni fabriquer de faux registre. Il n'a aucune raison
 * d'être posé ailleurs que sur le styleguide.
 * ================================================================== */

function cc_sc_exemple( $atts ) {
	$atts = shortcode_atts( array( 'composant' => '' ), $atts, 'cc_exemple' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		return '';
	}

	$avertissement = '<p class="cc-vide" style="text-align:left">'
		. 'Exemple de mise en forme, données écrites en dur. Le composant réel '
		. 'n\'affiche rien ci-dessus tant qu\'aucune donnée ne remplit sa condition.'
		. '</p>';

	switch ( $atts['composant'] ) {
		case 'temoignages':
			cc_charger_style( 'temoignages' );

			$html = '<div class="cc-grille cc-grille--3">';
			foreach ( array( 'Prénom A', 'Prénom B', 'Prénom C' ) as $prenom ) {
				$html .= '<figure class="cc-carte cc-carte--temoignage">'
					. '<blockquote class="cc-temoignage__citation">Citation d\'exemple, deux à trois lignes sur le déroulé de l\'accompagnement.</blockquote>'
					. '<figcaption class="cc-temoignage__auteur"><span>'
					. '<span class="cc-temoignage__prenom">' . esc_html( $prenom ) . '</span>'
					. '<span class="cc-temoignage__origine">Établissement — Ville</span>'
					. '</span></figcaption></figure>';
			}
			$html .= '</div>';

			return $avertissement . $html;

		case 'partenaires':
			cc_charger_style( 'partenaires' );

			$html = '<ul class="cc-partenaires">';
			foreach ( array( 'Logo A', 'Logo B', 'Logo C', 'Logo D' ) as $nom ) {
				$html .= '<li class="cc-partenaires__item">'
					. '<span class="cc-partenaires__nom">' . esc_html( $nom ) . '</span>'
					. '</li>';
			}
			$html .= '</ul>';

			return $avertissement . $html;
	}

	return cc_composant_vide( 'Exemple inconnu : ' . $atts['composant'] );
}
add_shortcode( 'cc_exemple', 'cc_sc_exemple' );

/* ================================================================== *
 * Bouton WhatsApp flottant
 *
 * Remplace l'extension Click to Chat. Rendu directement dans le pied de page
 * plutôt qu'en shortcode : il est présent sur toutes les pages, personne ne
 * doit avoir à penser à le poser.
 * ================================================================== */

/**
 * Le bouton flottant doit-il être affiché sur cette page ?
 *
 * @return bool
 */
function cc_afficher_whatsapp_flottant() {
	if ( ! function_exists( 'cc_whatsapp_link' ) ) {
		return false;
	}

	// Inutile dans l'éditeur Elementor, où il masquerait les commandes.
	if ( isset( $_GET['elementor-preview'] ) ) {
		return false;
	}

	/**
	 * Permet de masquer le bouton sur certains gabarits — par exemple une
	 * page destinée aux professionnels, dont l'audience n'est pas la même.
	 */
	return (bool) apply_filters( 'cc_afficher_whatsapp_flottant', true );
}

/*
 * La feuille du bouton est mise en file dans l'en-tête, et non au moment du
 * rendu comme celles des autres composants : le bouton est présent sur toutes
 * les pages, et un élément en position fixe qui apparaît sans style le temps
 * que le pied de page se charge se voit immédiatement.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( cc_afficher_whatsapp_flottant() ) {
		cc_charger_style( 'whatsapp-flottant' );
	}
}, 30 );

/*
 * Priorité 5 : wp_print_footer_scripts est accroché à wp_footer en 20. Un
 * rendu plus tardif sortirait le balisage après l'impression des styles.
 */
add_action( 'wp_footer', function () {
	if ( ! cc_afficher_whatsapp_flottant() ) {
		return;
	}

	echo cc_gabarit( 'cc-whatsapp-flottant', array(
		'url' => cc_whatsapp_link( 'fr' ),
	) );
}, 5 );
