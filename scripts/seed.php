<?php
/**
 * Contenu de démonstration Campus Connect.
 *
 *   Créer   : docker compose run --rm wpcli eval-file /scripts/seed.php
 *   Retirer : docker compose run --rm wpcli eval-file /scripts/seed.php supprimer
 *
 * Tout ce que ce script crée est FICTIF et porte la méta `_cc_seed`, qui
 * déclenche la classe .cc-placeholder sur le site (voir mu-plugins/cc-demo.php).
 * La suppression s'appuie sur cette même méta : elle ne peut pas emporter un
 * contenu rédigé à la main.
 *
 * Provenance des textes
 * ---------------------
 * Les noms des six packs et la liste de leurs prestations viennent des
 * maquettes (docs/wireframes/accompagnements.html), donc du cahier des charges.
 * Les maquettes précisent toutefois que le détail des prestations est du
 * remplissage : il donne le volume attendu, pas le périmètre contractuel réel.
 * Tout le reste — témoignages, partenaires, accroches — est inventé.
 *
 * Deux valeurs sont volontairement à « non » :
 *   - l'accord écrit des témoignages,
 *   - le caractère formalisé des partenariats.
 * Ces personnes et ces structures n'existent pas : il n'existe donc ni accord
 * ni convention. Les cocher fabriquerait un faux registre, exactement ce que
 * ces deux champs servent à empêcher.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$supprimer = isset( $args[0] ) && in_array( $args[0], array( 'supprimer', 'reverse', 'down' ), true );

/*
 * Le téléversement des logos passe par la médiathèque, donc par le contrôle de
 * capacités de cc-svg-upload.php. En CLI aucun utilisateur n'est connecté :
 * on endosse le premier administrateur.
 */
$admins = get_users( array(
	'role'    => 'administrator',
	'number'  => 1,
	'orderby' => 'ID',
) );

if ( empty( $admins ) ) {
	WP_CLI::error( 'Aucun administrateur : impossible de téléverser les logos de démonstration.' );
}
wp_set_current_user( $admins[0]->ID );

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* ================================================================== *
 * Suppression
 * ================================================================== */

if ( $supprimer ) {
	$demo = get_posts( array(
		'post_type'      => array( 'pack', 'temoignage', 'partenaire', 'formation', 'destination', 'attachment' ),
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => CC_META_DEMO,
				'compare' => 'EXISTS',
			),
		),
	) );

	foreach ( $demo as $id ) {
		wp_delete_post( $id, true );
	}

	WP_CLI::log( sprintf( '%d contenus de démonstration supprimés.', count( $demo ) ) );

	/*
	 * Les domaines de formation sont posés par ce script, contrairement aux
	 * situations et aux types de partenaire qui relèvent de l'architecture
	 * (scripts/structure.php). Ils repartent donc avec le reste — seuls ceux
	 * portant la marque, jamais un domaine ajouté à la main.
	 */
	$termes_demo = get_terms( array(
		'taxonomy'   => 'domaine',
		'hide_empty' => false,
		'fields'     => 'ids',
		'meta_query' => array(
			array(
				'key'     => CC_META_DEMO,
				'compare' => 'EXISTS',
			),
		),
	) );

	if ( ! is_wp_error( $termes_demo ) ) {
		foreach ( $termes_demo as $terme_id ) {
			wp_delete_term( $terme_id, 'domaine' );
		}
		WP_CLI::log( sprintf( '%d domaines de démonstration supprimés.', count( $termes_demo ) ) );
	}

	// Réglages : on ne remet à vide que ce que le seed avait posé et que
	// personne n'a modifié depuis. Une valeur éditée à la main est conservée.
	$poses    = get_option( 'cc_seed_reglages', array() );
	$reglages = get_option( CC_REGLAGES_OPTION, array() );
	$rendus   = 0;

	if ( is_array( $poses ) && is_array( $reglages ) ) {
		foreach ( $poses as $cle => $valeur_posee ) {
			if ( isset( $reglages[ $cle ] ) && $reglages[ $cle ] === $valeur_posee ) {
				$reglages[ $cle ] = '';
				$rendus++;
			}
		}
		update_option( CC_REGLAGES_OPTION, $reglages );
	}

	delete_option( 'cc_seed_reglages' );

	WP_CLI::log( sprintf( '%d réglages de démonstration remis à vide.', $rendus ) );
	WP_CLI::success( 'Contenu de démonstration retiré.' );
	return;
}

/* ================================================================== *
 * Outils
 * ================================================================== */

/**
 * Enveloppe un texte dans un bloc signalé comme fictif.
 *
 * @param string $texte
 * @return string
 */
function cc_seed_contenu( $texte ) {
	return sprintf(
		'<div class="cc-placeholder"><p>%s</p></div>',
		esc_html( $texte )
	);
}

/**
 * Crée ou met à jour un contenu de démonstration, repéré par son slug.
 *
 * @param string $type
 * @param string $slug
 * @param string $titre
 * @param string $contenu
 * @return int ID du contenu.
 */
function cc_seed_post( $type, $slug, $titre, $contenu = '' ) {
	$existant = get_posts( array(
		'post_type'      => $type,
		'name'           => $slug,
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	$donnees = array(
		'post_type'    => $type,
		'post_title'   => $titre,
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_content' => $contenu,
	);

	if ( $existant ) {
		$donnees['ID'] = $existant[0];
	}

	$id = wp_insert_post( $donnees, true );

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( sprintf( '%s « %s » : %s', $type, $titre, $id->get_error_message() ) );
	}

	update_post_meta( $id, CC_META_DEMO, '1' );

	if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_default_language' ) ) {
		pll_set_post_language( $id, pll_default_language() );
	}

	return (int) $id;
}

/**
 * Téléverse un SVG généré et le renvoie comme pièce jointe.
 *
 * Passe par media_handle_sideload, donc par l'assainissement SVG de
 * cc-svg-upload.php : ces fichiers subissent le même traitement que ceux
 * déposés à la main dans la médiathèque.
 *
 * @param string $svg     Markup complet.
 * @param string $nom     Nom de fichier, sans extension.
 * @param string $titre   Titre dans la médiathèque.
 * @param int    $parent  Contenu auquel rattacher la pièce jointe.
 * @return int|null ID de la pièce jointe.
 */
function cc_seed_svg( $svg, $nom, $titre, $parent = 0 ) {
	/*
	 * Réutiliser la pièce jointe déjà posée par une exécution précédente.
	 * Sans ce garde-fou, chaque relance empile un logo-fictif-alp-1.svg,
	 * -2.svg, -3.svg dans la médiathèque.
	 *
	 * La recherche passe par une méta et non par le nom du fichier :
	 * media_handle_sideload construit le post_name à partir du TITRE, pas du
	 * nom de fichier, donc chercher « logo-fictif-alp » ne trouve rien.
	 */
	$deja = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'   => '_cc_seed_ref',
				'value' => $nom,
			),
		),
	) );

	if ( $deja ) {
		return (int) $deja[0];
	}

	$temporaire = wp_tempnam( $nom . '.svg' );

	if ( ! $temporaire || file_put_contents( $temporaire, $svg ) === false ) {
		WP_CLI::warning( sprintf( 'Écriture impossible pour %s.', $nom ) );
		return null;
	}

	$fichier = array(
		'name'     => $nom . '.svg',
		'type'     => 'image/svg+xml',
		'tmp_name' => $temporaire,
		'error'    => 0,
		'size'     => filesize( $temporaire ),
	);

	$id = media_handle_sideload( $fichier, $parent, $titre );

	if ( is_wp_error( $id ) ) {
		@unlink( $temporaire );
		WP_CLI::warning( sprintf( 'Téléversement de %s : %s', $nom, $id->get_error_message() ) );
		return null;
	}

	update_post_meta( $id, CC_META_DEMO, '1' );
	update_post_meta( $id, '_cc_seed_ref', $nom );

	return (int) $id;
}

/**
 * Logo géométrique de démonstration.
 *
 * Aucune marque réelle : une forme simple dans les couleurs de la charte, avec
 * les initiales. Le but est de tenir la place d'un logo dans les grilles, pas
 * de ressembler à quoi que ce soit d'existant.
 *
 * @param string $initiales
 * @param string $forme     carre|cercle|triangle|losange|hexagone|arche
 * @param string $fond
 * @param string $trait
 * @return string
 */
function cc_seed_logo_svg( $initiales, $forme, $fond, $trait ) {
	switch ( $forme ) {
		case 'cercle':
			$dessin = sprintf( '<circle cx="80" cy="56" r="34" fill="%s"/>', $trait );
			break;
		case 'triangle':
			$dessin = sprintf( '<polygon points="80,22 114,84 46,84" fill="%s"/>', $trait );
			break;
		case 'losange':
			$dessin = sprintf( '<polygon points="80,20 116,56 80,92 44,56" fill="%s"/>', $trait );
			break;
		case 'hexagone':
			$dessin = sprintf( '<polygon points="80,20 112,38 112,74 80,92 48,74 48,38" fill="%s"/>', $trait );
			break;
		case 'arche':
			$dessin = sprintf(
				'<path d="M52 86 C 52 40, 108 40, 108 86" fill="none" stroke="%s" stroke-width="12" stroke-linecap="round"/>',
				$trait
			);
			break;
		case 'carre':
		default:
			$dessin = sprintf( '<rect x="48" y="24" width="64" height="64" rx="12" fill="%s"/>', $trait );
			break;
	}

	return sprintf(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 140" width="160" height="140" role="img" aria-label="Logo fictif %1$s">'
		. '<title>Logo de démonstration — %1$s</title>'
		. '<rect width="160" height="140" fill="%2$s"/>'
		. '%3$s'
		. '<text x="80" y="122" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" font-weight="700" fill="%4$s">%1$s</text>'
		. '</svg>',
		esc_html( $initiales ),
		$fond,
		$dessin,
		$trait
	);
}

/**
 * Aplat de couleur tenant lieu de portrait.
 *
 * Les maquettes demandent explicitement qu'aucune photo de personne réelle ne
 * serve de témoignage : une initiale sur un aplat, rien de plus.
 *
 * @param string $initiale
 * @param string $fond
 * @return string
 */
function cc_seed_portrait_svg( $initiale, $fond ) {
	return sprintf(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200" role="img" aria-label="Portrait fictif">'
		. '<title>Aplat de couleur — aucune personne réelle</title>'
		. '<rect width="200" height="200" fill="%2$s"/>'
		. '<text x="100" y="128" text-anchor="middle" font-family="Arial, sans-serif" font-size="88" font-weight="700" fill="#FFFFFF">%1$s</text>'
		. '</svg>',
		esc_html( $initiale ),
		$fond
	);
}

/**
 * Crée un terme de démonstration s'il manque, et le marque comme fictif.
 *
 * Un terme n'a pas de contenu HTML : il ne peut pas porter .cc-placeholder.
 * La marque en méta de terme sert donc uniquement à la suppression.
 *
 * @param string $taxonomie
 * @param string $slug
 * @param string $nom
 * @return int term_id, ou 0 en cas d'échec.
 */
function cc_seed_terme_demo( $taxonomie, $slug, $nom ) {
	$terme = get_term_by( 'slug', $slug, $taxonomie );

	if ( $terme ) {
		$id = (int) $terme->term_id;
	} else {
		$cree = wp_insert_term( $nom, $taxonomie, array( 'slug' => $slug ) );

		if ( is_wp_error( $cree ) ) {
			WP_CLI::warning( sprintf( '%s / %s : %s', $taxonomie, $slug, $cree->get_error_message() ) );
			return 0;
		}

		$id = (int) $cree['term_id'];

		// Sans langue, Polylang crée un doublon suffixé « -fr » à la première
		// affectation. Voir le commentaire détaillé dans scripts/structure.php.
		if ( function_exists( 'pll_set_term_language' ) ) {
			pll_set_term_language( $id, pll_default_language() );
		}
	}

	update_term_meta( $id, CC_META_DEMO, '1' );

	return $id;
}

/** Rattache un contenu à un terme, en créant le terme au besoin. */
function cc_seed_terme( $post_id, $taxonomie, $slug ) {
	$terme = get_term_by( 'slug', $slug, $taxonomie );

	if ( ! $terme ) {
		WP_CLI::warning( sprintf( 'Terme %s / %s absent.', $taxonomie, $slug ) );
		return;
	}

	wp_set_object_terms( $post_id, (int) $terme->term_id, $taxonomie, false );
}

/* ================================================================== *
 * Termes structurels — au cas où structure.php n'aurait pas tourné
 * ================================================================== */

foreach ( cc_termes_structurels() as $taxonomie => $termes ) {
	foreach ( $termes as $slug => $nom ) {
		if ( term_exists( $slug, $taxonomie ) ) {
			continue;
		}

		$cree = wp_insert_term( $nom, $taxonomie, array( 'slug' => $slug ) );

		// Sans langue, Polylang crée un doublon suffixé « -fr » à la première
		// affectation. Voir le commentaire détaillé dans scripts/structure.php.
		if ( ! is_wp_error( $cree ) && function_exists( 'pll_set_term_language' ) ) {
			pll_set_term_language( (int) $cree['term_id'], pll_default_language() );
		}
	}
}

/* ================================================================== *
 * 1. Packs
 * ================================================================== */

$packs = array(
	array(
		'slug'        => 'essentiel',
		'titre'       => 'Essentiel',
		'situation'   => 'pre-admission',
		'ordre'       => 10,
		'prestations' => array(
			'Bilan d\'orientation individuel',
			'Sélection d\'établissements adaptés au profil',
			'Relecture du CV et de la lettre de motivation',
			'Dépôt des candidatures',
			'Suivi des réponses reçues',
		),
	),
	array(
		'slug'        => 'confort',
		'titre'       => 'Confort',
		'situation'   => 'pre-admission',
		'ordre'       => 20,
		'prestations' => array(
			'Tout le contenu du pack Essentiel',
			'Élargissement de la liste d\'établissements',
			'Préparation aux entretiens d\'admission',
			'Constitution du dossier académique',
			'Point de suivi régulier',
			'Assistance par WhatsApp aux heures ouvrées',
		),
	),
	array(
		'slug'        => 'premium',
		'titre'       => 'Premium',
		'situation'   => 'pre-admission',
		'ordre'       => 30,
		'prestations' => array(
			'Tout le contenu du pack Confort',
			'Conseiller dédié pendant toute la procédure',
			'Candidatures en établissements publics et privés',
			'Simulations d\'entretien',
			'Préparation du dossier Campus France',
			'Coordination avec les écoles partenaires',
			'Disponibilité étendue',
		),
	),
	array(
		'slug'        => 'procedure',
		'titre'       => 'Procédure',
		'situation'   => 'post-admission',
		'ordre'       => 10,
		'prestations' => array(
			'Ouverture et suivi du dossier Campus France',
			'Préparation à l\'entretien Campus France',
			'Constitution du dossier de demande de visa',
			'Vérification des pièces justificatives',
			'Préparation du rendez-vous consulaire',
		),
	),
	array(
		'slug'        => 'installation',
		'titre'       => 'Installation',
		'situation'   => 'post-admission',
		'ordre'       => 20,
		'prestations' => array(
			'Recherche de logement avec nos bailleurs partenaires',
			'Souscription de l\'assurance AVI',
			'Constitution du dossier de garant',
			'Ouverture de compte bancaire',
			'Organisation du voyage et de l\'arrivée',
			'Démarches d\'installation les premières semaines',
		),
	),
	array(
		'slug'        => 'premium-vip',
		'titre'       => 'Premium VIP',
		'situation'   => 'post-admission',
		'ordre'       => 30,
		'prestations' => array(
			'Tout le contenu des packs Procédure et Installation',
			'Conseiller dédié joignable en priorité',
			'Accueil à l\'arrivée en France',
			'Accompagnement aux démarches administratives sur place',
			'Mise en relation avec le réseau d\'anciens',
			'Suivi prolongé après l\'installation',
		),
	),
);

foreach ( $packs as $pack ) {
	$id = cc_seed_post(
		'pack',
		$pack['slug'],
		$pack['titre'],
		cc_seed_contenu(
			'Description du pack à rédiger. Le détail des prestations ci-contre est provisoire : '
			. 'il donne le volume attendu, pas le périmètre contractuel, qui doit être fourni et validé.'
		)
	);

	update_field( 'sous_titre', 'Ligne de positionnement à rédiger — texte provisoire.', $id );
	update_field( 'prestations', implode( "\n", $pack['prestations'] ), $id );
	update_field( 'ordre_affichage', $pack['ordre'], $id );

	cc_seed_terme( $id, 'situation', $pack['situation'] );

	WP_CLI::log( sprintf( 'Pack : %s (#%d)', $pack['titre'], $id ) );
}

/* ================================================================== *
 * 2. Témoignages
 *
 * Prénoms et parcours inventés. Les citations évitent toute promesse de
 * résultat : elles portent sur le déroulé de l'accompagnement, jamais sur
 * l'obtention d'une admission ou d'un visa.
 * ================================================================== */

$temoignages = array(
	array(
		'slug'          => 'temoignage-aminata',
		'prenom'        => 'Aminata',
		'etablissement' => 'Université fictive de Lille',
		'ville'         => 'Lille',
		'citation'      => 'J\'ai su à chaque étape ce qu\'on attendait de moi, et ce qui dépendait de ma seule préparation. Le dossier a été relu avec moi ligne à ligne.',
		'couleur'       => '#1565C0',
	),
	array(
		'slug'          => 'temoignage-serge',
		'prenom'        => 'Serge',
		'etablissement' => 'École fictive de Nantes',
		'ville'         => 'Nantes',
		'citation'      => 'Les échanges par WhatsApp m\'ont évité de perdre des semaines sur des pièces manquantes. On me disait quoi préparer, et quand.',
		'couleur'       => '#17A673',
	),
	array(
		'slug'          => 'temoignage-laure',
		'prenom'        => 'Laure',
		'etablissement' => 'Institut fictif de Lyon',
		'ville'         => 'Lyon',
		'citation'      => 'Ce que j\'ai apprécié, c\'est qu\'on ne m\'a rien promis. On m\'a expliqué la procédure, ses délais et ses incertitudes, puis on m\'a aidée à m\'y préparer.',
		'couleur'       => '#0B3C7D',
	),
);

foreach ( $temoignages as $t ) {
	$id = cc_seed_post(
		'temoignage',
		$t['slug'],
		$t['prenom'] . ' — témoignage fictif',
		cc_seed_contenu( 'Témoignage inventé pour la maquette. Aucune personne réelle, aucun accord de diffusion.' )
	);

	update_field( 'prenom', $t['prenom'], $id );
	update_field( 'etablissement', $t['etablissement'], $id );
	update_field( 'ville', $t['ville'], $id );
	update_field( 'citation', $t['citation'], $id );
	// Faux : personne n'a donné d'accord, puisque personne n'existe.
	update_field( 'accord_ecrit', 0, $id );

	$portrait = cc_seed_svg(
		cc_seed_portrait_svg( mb_substr( $t['prenom'], 0, 1 ), $t['couleur'] ),
		'portrait-fictif-' . sanitize_title( $t['prenom'] ),
		'Aplat de couleur — ' . $t['prenom'] . ' (fictif)',
		$id
	);

	if ( $portrait ) {
		set_post_thumbnail( $id, $portrait );
	}

	WP_CLI::log( sprintf( 'Témoignage : %s (#%d)', $t['prenom'], $id ) );
}

/* ================================================================== *
 * 3. Partenaires
 *
 * Noms ouvertement fictifs : un nom plausible finirait par être pris pour un
 * partenaire réel, sur une page qui laisse entendre qu'une convention existe.
 * ================================================================== */

$partenaires = array(
	array(
		'slug'      => 'partenaire-alpha',
		'titre'     => 'École Alpha (fictif)',
		'initiales' => 'ALP',
		'forme'     => 'carre',
		'type'      => 'etablissement-france',
		'fond'      => '#E8F1FB',
		'trait'     => '#1565C0',
	),
	array(
		'slug'      => 'partenaire-beta',
		'titre'     => 'Université Bêta (fictif)',
		'initiales' => 'BET',
		'forme'     => 'cercle',
		'type'      => 'etablissement-france',
		'fond'      => '#E8F1FB',
		'trait'     => '#0B3C7D',
	),
	array(
		'slug'      => 'partenaire-gamma',
		'titre'     => 'Lycée Gamma (fictif)',
		'initiales' => 'GAM',
		'forme'     => 'triangle',
		'type'      => 'etablissement-etranger',
		'fond'      => '#E6F6EF',
		'trait'     => '#17A673',
	),
	array(
		'slug'      => 'partenaire-delta',
		'titre'     => 'Assurance Delta (fictif)',
		'initiales' => 'DEL',
		'forme'     => 'hexagone',
		'type'      => 'assurance-avi',
		'fond'      => '#E6F6EF',
		'trait'     => '#0E7A54',
	),
	array(
		'slug'      => 'partenaire-epsilon',
		'titre'     => 'Résidence Epsilon (fictif)',
		'initiales' => 'EPS',
		'forme'     => 'losange',
		'type'      => 'logement',
		'fond'      => '#E8F1FB',
		'trait'     => '#5A6B7B',
	),
	array(
		'slug'      => 'partenaire-zeta',
		'titre'     => 'Bailleur Zêta (fictif)',
		'initiales' => 'ZET',
		'forme'     => 'arche',
		'type'      => 'logement',
		'fond'      => '#F5F9FE',
		'trait'     => '#1565C0',
	),
);

foreach ( $partenaires as $p ) {
	$id = cc_seed_post(
		'partenaire',
		$p['slug'],
		$p['titre'],
		cc_seed_contenu( 'Partenaire inventé pour la maquette. Aucune convention, aucune autorisation d\'utilisation de marque.' )
	);

	$logo = cc_seed_svg(
		cc_seed_logo_svg( $p['initiales'], $p['forme'], $p['fond'], $p['trait'] ),
		'logo-fictif-' . sanitize_title( $p['initiales'] ),
		'Logo fictif — ' . $p['titre'],
		$id
	);

	if ( $logo ) {
		update_field( 'logo', $logo, $id );
		set_post_thumbnail( $id, $logo );
	}

	update_field( 'site_web', '', $id );
	// Faux : aucune convention n'existe avec une structure qui n'existe pas.
	update_field( 'partenariat_formalise', 0, $id );

	cc_seed_terme( $id, 'type_partenaire', $p['type'] );

	WP_CLI::log( sprintf( 'Partenaire : %s (#%d)', $p['titre'], $id ) );
}

/* ================================================================== *
 * 4. Destinations
 * ================================================================== */

$destinations = array(
	array(
		'slug'     => 'france',
		'titre'    => 'France',
		'statut'   => 'active',
		'accroche' => 'Notre destination principale : accompagnement de l\'orientation jusqu\'à l\'installation.',
	),
	array(
		'slug'     => 'canada',
		'titre'    => 'Canada',
		'statut'   => 'bientot',
		'accroche' => 'Destination en préparation. Nous n\'accompagnons pas encore de dossier vers le Canada.',
	),
	array(
		'slug'     => 'allemagne',
		'titre'    => 'Allemagne',
		'statut'   => 'bientot',
		'accroche' => 'Destination en préparation. Nous n\'accompagnons pas encore de dossier vers l\'Allemagne.',
	),
);

foreach ( $destinations as $d ) {
	$id = cc_seed_post(
		'destination',
		$d['slug'],
		$d['titre'],
		cc_seed_contenu( 'Présentation de la destination à rédiger.' )
	);

	update_field( 'statut', $d['statut'], $id );
	update_field( 'texte_accroche', $d['accroche'], $id );

	WP_CLI::log( sprintf( 'Destination : %s (%s) (#%d)', $d['titre'], $d['statut'], $id ) );
}

/* ================================================================== *
 * 5. Formations et domaines
 *
 * Les intitulés sont des types de cursus courants, pas des formations
 * inventées de toutes pièces : on ne fabrique pas de faux diplôme. Ce qui
 * reste à valider, c'est le PÉRIMÈTRE — quelles filières et quels niveaux
 * Campus Connect couvre réellement. D'où le marquage .cc-placeholder.
 *
 * Les domaines sont posés ici et non dans structure.php : contrairement aux
 * situations (deux onglets de la page packs) et aux types de partenaire
 * (trois bandes de la page partenaires), qui viennent des maquettes, cette
 * liste-là n'est adossée à rien. Elle est provisoire et repart avec le seed.
 * ================================================================== */

$domaines = array(
	'commerce-gestion'     => 'Commerce et gestion',
	'ingenierie-sciences'  => 'Ingénierie et sciences',
	'informatique'         => 'Informatique et numérique',
	'sante-social'         => 'Santé et social',
	'arts-design'          => 'Arts et design',
	'droit-sciences-po'    => 'Droit et sciences politiques',
);

foreach ( $domaines as $slug => $nom ) {
	cc_seed_terme_demo( 'domaine', $slug, $nom );
}

WP_CLI::log( sprintf( '%d domaines posés.', count( $domaines ) ) );

$formations = array(
	array(
		'slug'    => 'bts-but-gestion',
		'titre'   => 'BTS et BUT en gestion',
		'domaine' => 'commerce-gestion',
		'niveau'  => 'Bac +2 / Bac +3',
	),
	array(
		'slug'    => 'licence-economie-gestion',
		'titre'   => 'Licence en économie et gestion',
		'domaine' => 'commerce-gestion',
		'niveau'  => 'Bac +3',
	),
	array(
		'slug'    => 'master-management',
		'titre'   => 'Master en management',
		'domaine' => 'commerce-gestion',
		'niveau'  => 'Bac +5',
	),
	array(
		'slug'    => 'licence-informatique',
		'titre'   => 'Licence en informatique',
		'domaine' => 'informatique',
		'niveau'  => 'Bac +3',
	),
	array(
		'slug'    => 'master-cybersecurite',
		'titre'   => 'Master en cybersécurité',
		'domaine' => 'informatique',
		'niveau'  => 'Bac +5',
	),
	array(
		'slug'    => 'cycle-ingenieur',
		'titre'   => 'Cycle ingénieur',
		'domaine' => 'ingenierie-sciences',
		'niveau'  => 'Bac +5',
	),
	array(
		'slug'    => 'licence-biologie',
		'titre'   => 'Licence en sciences de la vie',
		'domaine' => 'sante-social',
		'niveau'  => 'Bac +3',
	),
	array(
		'slug'    => 'bachelor-design',
		'titre'   => 'Bachelor en design graphique',
		'domaine' => 'arts-design',
		'niveau'  => 'Bac +3',
	),
	array(
		'slug'    => 'licence-droit',
		'titre'   => 'Licence en droit',
		'domaine' => 'droit-sciences-po',
		'niveau'  => 'Bac +3',
	),
);

foreach ( $formations as $f ) {
	$id = cc_seed_post(
		'formation',
		$f['slug'],
		$f['titre'],
		cc_seed_contenu(
			'Niveau visé : ' . $f['niveau'] . '. Descriptif à rédiger — débouchés, '
			. 'prérequis, établissements concernés. À confirmer : cette filière fait-elle '
			. 'partie du périmètre réellement accompagné ?'
		)
	);

	// L'extrait alimente les vignettes de la page « Nos formations ».
	wp_update_post( array(
		'ID'           => $id,
		'post_excerpt' => $f['niveau'] . ' — descriptif provisoire.',
	) );

	cc_seed_terme( $id, 'domaine', $f['domaine'] );

	WP_CLI::log( sprintf( 'Formation : %s (%s)', $f['titre'], $f['domaine'] ) );
}

/* ================================================================== *
 * 6. Questions fréquentes
 *
 * Les réponses restent au conditionnel sur tout ce qui dépend d'une décision
 * administrative : une FAQ est lue comme un engagement, et la règle du projet
 * interdit toute promesse de résultat.
 * ================================================================== */

$questions = array(
	array(
		'slug'     => 'faq-evaluation-gratuite',
		'question' => 'La première évaluation est-elle vraiment gratuite ?',
		'reponse'  => 'Réponse à valider. Préciser ce que couvre l\'évaluation, sa durée, et s\'il existe une condition — c\'est la promesse d\'entrée, elle doit être tenable telle quelle.',
		'ordre'    => 10,
	),
	array(
		'slug'     => 'faq-garantie-visa',
		'question' => 'Garantissez-vous l\'obtention du visa ?',
		'reponse'  => 'Non. L\'admission comme le visa relèvent de décisions d\'établissements et d\'autorités consulaires, sur lesquelles aucun accompagnateur n\'a de pouvoir. Campus Connect prépare le dossier, explique la procédure et vous entraîne aux entretiens. Toute structure qui vous garantit un résultat vous trompe.',
		'ordre'    => 20,
	),
	array(
		'slug'     => 'faq-tarifs',
		'question' => 'Quels sont vos tarifs ?',
		'reponse'  => 'Réponse à rédiger. Les montants ne sont pas affichés sur le site : expliquer que l\'offre est établie après l\'évaluation, en fonction du pack retenu et de la situation.',
		'ordre'    => 30,
	),
	array(
		'slug'     => 'faq-delais',
		'question' => 'Quand faut-il commencer les démarches ?',
		'reponse'  => 'Réponse à rédiger, avec les échéances réelles de la campagne Campus France. Penser à dater l\'information : ce calendrier change chaque année.',
		'ordre'    => 40,
	),
	array(
		'slug'     => 'faq-hors-france',
		'question' => 'Accompagnez-vous vers le Canada ou l\'Allemagne ?',
		'reponse'  => 'Pas encore. Ces destinations sont en préparation. Aujourd\'hui, l\'accompagnement porte uniquement sur la France.',
		'ordre'    => 50,
	),
);

foreach ( $questions as $q ) {
	$id = cc_seed_post( 'faq', $q['slug'], $q['question'], cc_seed_contenu( $q['reponse'] ) );

	wp_update_post( array(
		'ID'         => $id,
		'menu_order' => $q['ordre'],
	) );

	WP_CLI::log( sprintf( 'Question : %s', $q['question'] ) );
}

/* ================================================================== *
 * 7. Réglages globaux
 *
 * Préfixés [DÉMO] : un réglage n'a pas de contenu HTML, il ne peut donc pas
 * porter la classe .cc-placeholder. Le préfixe joue le même rôle.
 * ================================================================== */

$reglages_demo = array(
	// 2019 donne l'ancienneté annoncée dans les maquettes (« +7 ans »).
	'annee_debut_activite' => '2019',

	/*
	 * Les quatre titres viennent des maquettes, donc du cahier des charges, et
	 * sont posés dans l'ordre du brief. Les textes d'appui, eux, restent à
	 * écrire : leur formulation le dit explicitement, un préfixe « [DÉMO] »
	 * serait redondant.
	 */
	'atout_1_titre'        => 'Établissements publics et privés',
	'atout_1_texte'        => 'Phrase d\'appui à rédiger : l\'éventail réellement couvert, sans citer de nom d\'établissement à ce stade.',
	'atout_2_titre'        => 'Quel que soit votre parcours',
	'atout_2_texte'        => 'Phrase d\'appui à rédiger : bac, licence en cours, réorientation, reprise d\'études.',
	'atout_3_titre'        => 'Réseau d\'écoles partenaires',
	'atout_3_texte'        => 'Phrase d\'appui à rédiger : la nature du réseau, et le renvoi vers la page Partenaires.',
	'atout_4_titre'        => 'Partenaires assurance AVI et logement',
	'atout_4_texte'        => 'Phrase d\'appui à rédiger : les démarches couvertes une fois l\'admission obtenue.',

	'france_adresse'       => '[DÉMO] Adresse à renseigner, France',
	'france_telephone'     => '[DÉMO] +33 0 00 00 00 00',
	'france_email'         => '',
	'cameroun_adresse'     => '[DÉMO] Adresse à renseigner, Cameroun',
	'cameroun_telephone'   => '[DÉMO] +237 0 00 00 00 00',
	'cameroun_email'       => '',
);

$reglages = get_option( CC_REGLAGES_OPTION, array() );
$reglages = is_array( $reglages ) ? $reglages : array();
$poses    = array();

foreach ( $reglages_demo as $cle => $valeur ) {
	$actuelle = isset( $reglages[ $cle ] ) ? $reglages[ $cle ] : '';

	// Ne jamais écraser une valeur saisie à la main.
	if ( $actuelle === '' ) {
		$reglages[ $cle ] = $valeur;
		$actuelle         = $valeur;
	}

	/*
	 * On retient TOUTE valeur qui est celle de la démonstration, et pas
	 * seulement celles que cette exécution vient de poser. Sinon, à la
	 * deuxième exécution, les valeurs déjà en place ne seraient plus
	 * enregistrées comme fictives, et la suppression laisserait les « [DÉMO] »
	 * en base — une réversibilité incomplète, donc trompeuse.
	 */
	if ( $actuelle === $valeur ) {
		$poses[ $cle ] = $valeur;
	}
}

update_option( CC_REGLAGES_OPTION, $reglages );
update_option( 'cc_seed_reglages', $poses );

WP_CLI::log( sprintf( '%d réglages de démonstration posés.', count( $poses ) ) );

WP_CLI::success(
	'Contenu de démonstration en place. Tout est fictif et signalé .cc-placeholder. '
	. 'Retrait : eval-file /scripts/seed.php supprimer'
);
