<?php
/**
 * Structure du site Campus Connect : langue, termes, pages, menus.
 *
 *   docker compose run --rm wpcli eval-file /scripts/structure.php
 *
 * Idempotent : relancer le script remet la structure en place sans créer de
 * doublon. Il ne touche jamais au contenu rédigé dans les pages — il ne crée
 * que ce qui manque, et ne réécrit que les menus, qu'il reconstruit à neuf.
 *
 * La base n'étant pas versionnée (voir README), ce script est ce qui permet de
 * retrouver la même architecture sur un autre poste.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

/* ------------------------------------------------------------------ *
 * 1. Polylang — français en langue unique
 * ------------------------------------------------------------------ */

if ( ! function_exists( 'PLL' ) || ! PLL() ) {
	WP_CLI::warning( 'Polylang inactif : étape langue ignorée.' );
} else {
	$langues = PLL()->model->languages;

	if ( ! $langues->get( 'fr' ) ) {
		// Le locale suffit : Polylang en déduit le nom, le slug et le drapeau.
		$resultat = $langues->add( array( 'locale' => 'fr_FR' ) );

		if ( is_wp_error( $resultat ) ) {
			WP_CLI::error( 'Création de la langue française : ' . $resultat->get_error_message() );
		}
		WP_CLI::log( 'Langue française créée.' );
	} else {
		WP_CLI::log( 'Langue française déjà présente.' );
	}

	$langues->update_default( 'fr' );
	$langues->clean_cache();

	WP_CLI::log( 'Français défini comme langue par défaut. L\'anglais pourra être ajouté sans rien reprendre.' );
}

/* ------------------------------------------------------------------ *
 * 2. Termes structurels des taxonomies
 * ------------------------------------------------------------------ */

foreach ( cc_termes_structurels() as $taxonomie => $termes ) {
	foreach ( $termes as $slug => $nom ) {
		if ( term_exists( $slug, $taxonomie ) ) {
			continue;
		}

		$resultat = wp_insert_term( $nom, $taxonomie, array( 'slug' => $slug ) );

		if ( is_wp_error( $resultat ) ) {
			WP_CLI::warning( sprintf( '%s / %s : %s', $taxonomie, $slug, $resultat->get_error_message() ) );
			continue;
		}

		/*
		 * Affecter la langue immédiatement est indispensable. Un terme sans
		 * langue dans une taxonomie traduisible amène Polylang à en créer un
		 * doublon au moment où on l'attache à un contenu, avec un slug suffixé
		 * « -fr » — on se retrouve alors avec deux termes par notion, dont un
		 * vide, et des URL de taxonomie en « pre-admission-fr ».
		 */
		if ( function_exists( 'pll_set_term_language' ) ) {
			pll_set_term_language( (int) $resultat['term_id'], 'fr' );
		}

		WP_CLI::log( sprintf( 'Terme créé : %s → %s', $taxonomie, $nom ) );
	}
}

/* ------------------------------------------------------------------ *
 * 3. Pages
 *
 * Volontairement vides : elles seront construites dans Elementor. Ce script
 * fixe l'arborescence, les slugs et les identifiants, pas le contenu.
 * ------------------------------------------------------------------ */

$pages = array(
	'accueil'                   => 'Accueil',
	'etudier-en-france'         => 'Étudier en France',
	'nos-formations'            => 'Nos formations',
	'nos-accompagnements'       => 'Nos accompagnements',
	'qui-sommes-nous'           => 'Qui sommes-nous',
	'faq'                       => 'FAQ',
	'partenaires'               => 'Partenaires',
	'je-veux-etudier-en-france' => 'Je veux étudier en France',
	'blog'                      => 'Blog',
	'contact'                   => 'Contact',
);

$ids = array();

foreach ( $pages as $slug => $titre ) {
	$existante = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $existante ) {
		$ids[ $slug ] = (int) $existante->ID;
		WP_CLI::log( sprintf( 'Page déjà présente : %s (#%d)', $titre, $existante->ID ) );
	} else {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => $titre,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			WP_CLI::error( sprintf( 'Création de « %s » : %s', $titre, $id->get_error_message() ) );
		}

		$ids[ $slug ] = (int) $id;
		WP_CLI::log( sprintf( 'Page créée : %s (#%d)', $titre, $id ) );
	}

	// Polylang exige une langue sur chaque contenu, sinon la page est
	// considérée comme non traduite et disparaît du site.
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $ids[ $slug ], 'fr' );
	}
}

/* ------------------------------------------------------------------ *
 * 4. Réglages de lecture
 * ------------------------------------------------------------------ */

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $ids['accueil'] );
update_option( 'page_for_posts', $ids['blog'] );

WP_CLI::log( 'Accueil en page d\'accueil statique, Blog en page des articles.' );

/* ------------------------------------------------------------------ *
 * 5. Menus
 *
 * Reconstruits intégralement à chaque exécution : c'est la seule façon de
 * garantir qu'une relance remet exactement l'arborescence attendue.
 * ------------------------------------------------------------------ */

/**
 * Vide un menu de ses entrées, ou le crée s'il n'existe pas.
 *
 * @param string $nom
 * @return int term_id du menu.
 */
function cc_menu_vierge( $nom ) {
	$menu = wp_get_nav_menu_object( $nom );

	if ( ! $menu ) {
		$id = wp_create_nav_menu( $nom );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( sprintf( 'Création du menu « %s » : %s', $nom, $id->get_error_message() ) );
		}
		return (int) $id;
	}

	foreach ( wp_get_nav_menu_items( $menu->term_id ) as $entree ) {
		wp_delete_post( $entree->ID, true );
	}

	return (int) $menu->term_id;
}

/**
 * Ajoute une entrée pointant vers une page.
 *
 * @param int    $menu_id
 * @param int    $page_id
 * @param string $libelle Libellé affiché, s'il diffère du titre de la page.
 * @param int    $parent
 * @param string $classes
 * @return int
 */
function cc_entree_page( $menu_id, $page_id, $libelle = '', $parent = 0, $classes = '' ) {
	return (int) wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-object-id' => $page_id,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-title'     => $libelle,
			'menu-item-parent-id' => $parent,
			'menu-item-classes'   => $classes,
			'menu-item-status'    => 'publish',
		)
	);
}

$menu_principal = cc_menu_vierge( 'Menu principal' );

// 1. Étudier en France — la page parente est reprise en première entrée du
//    sous-menu : sur mobile, un parent dépliable n'est plus cliquable.
$entree_etudier = cc_entree_page( $menu_principal, $ids['etudier-en-france'] );
cc_entree_page( $menu_principal, $ids['etudier-en-france'], 'Étudier en France', $entree_etudier );
cc_entree_page( $menu_principal, $ids['nos-formations'], '', $entree_etudier );

// 2. Nos accompagnements
cc_entree_page( $menu_principal, $ids['nos-accompagnements'] );

// 3. À propos — simple regroupement : aucune page ne porte ce titre, l'entrée
//    n'est donc qu'une étiquette dépliante.
$entree_apropos = (int) wp_update_nav_menu_item(
	$menu_principal,
	0,
	array(
		'menu-item-title'   => 'À propos',
		'menu-item-url'     => '#',
		'menu-item-type'    => 'custom',
		'menu-item-classes' => 'cc-menu-sans-lien',
		'menu-item-status'  => 'publish',
	)
);
cc_entree_page( $menu_principal, $ids['qui-sommes-nous'], '', $entree_apropos );
cc_entree_page( $menu_principal, $ids['faq'], '', $entree_apropos );

// 4. Partenaires
cc_entree_page( $menu_principal, $ids['partenaires'] );

/*
 * Bouton de conversion, cible de tous les CTA du site.
 *
 * Les classes du design system sont posées ici plutôt qu'à la main dans
 * l'interface des menus : une relance du script les remettrait sinon à zéro.
 * `cc-menu-cta` sert de crochet pour les ajustements propres au contexte menu.
 */
cc_entree_page(
	$menu_principal,
	$ids['je-veux-etudier-en-france'],
	'Je veux étudier en France',
	0,
	'cc-menu-cta cc-btn cc-btn--primaire'
);

WP_CLI::log( 'Menu principal : 4 entrées et le bouton « Je veux étudier en France ».' );

// Menu du pied de page : Blog et Contact n'apparaissent que là.
$menu_footer = cc_menu_vierge( 'Menu pied de page' );
cc_entree_page( $menu_footer, $ids['blog'] );
cc_entree_page( $menu_footer, $ids['contact'] );

WP_CLI::log( 'Menu pied de page : Blog et Contact.' );

/* ------------------------------------------------------------------ *
 * 6. Emplacements
 * ------------------------------------------------------------------ */

$emplacements           = (array) get_theme_mod( 'nav_menu_locations', array() );
$emplacements['menu-1'] = $menu_principal;
$emplacements['menu-2'] = $menu_footer;
set_theme_mod( 'nav_menu_locations', $emplacements );

WP_CLI::log( 'Menus affectés aux emplacements Header et Footer du thème.' );

/*
 * Polylang tient sa PROPRE table emplacement → langue → menu, et elle prime sur
 * nav_menu_locations dès qu'une langue existe. Sans cette affectation, le thème
 * appelle wp_nav_menu() et reçoit une chaîne vide : le header s'affiche sans
 * aucune navigation, alors que le menu existe et que l'emplacement est bien
 * renseigné côté WordPress. Le symptôme est déroutant, la cause ne l'est pas.
 */
if ( function_exists( 'PLL' ) && PLL() ) {
	$langue_defaut = pll_default_language();
	$theme         = get_stylesheet();

	$options_pll = get_option( 'polylang' );

	if ( is_array( $options_pll ) && $langue_defaut ) {
		$options_pll['nav_menus'][ $theme ]['menu-1'][ $langue_defaut ] = $menu_principal;
		$options_pll['nav_menus'][ $theme ]['menu-2'][ $langue_defaut ] = $menu_footer;

		update_option( 'polylang', $options_pll );

		WP_CLI::log( 'Menus affectés côté Polylang pour la langue « ' . $langue_defaut . ' ».' );
	}
}

/* ------------------------------------------------------------------ *
 * 7. Page de contrôle visuel « Styleguide »
 *
 * Page PRIVÉE : elle n'existe que pour vérifier d'un coup d'œil qu'aucun
 * composant n'est cassé. Son contenu est régénéré à chaque exécution, à la
 * différence des neuf pages du site, auxquelles le script ne touche jamais.
 *
 * Les shortcodes affichés en légende sont écrits [[entre doubles crochets]] :
 * c'est la façon dont WordPress échappe un shortcode pour le montrer au lieu
 * de l'exécuter.
 * ------------------------------------------------------------------ */

/**
 * Un bloc du styleguide : titre, légende, puis le rendu.
 *
 * @param string $titre
 * @param string $code    Shortcode à montrer en légende, sans crochets.
 * @param string $rendu   Contenu à rendre (shortcode réel ou HTML).
 * @param string $note    Remarque facultative.
 * @return string
 */
function cc_bloc_styleguide( $titre, $code, $rendu, $note = '' ) {
	$html  = '<h2 class="cc-titre-section">' . esc_html( $titre ) . '</h2>';
	$html .= '<p style="font-family:monospace;background:#F5F9FE;border:1px solid #DCE4EC;'
		. 'border-radius:6px;padding:.5rem .75rem;display:inline-block;font-size:.875rem">';
	$html .= $code !== '' ? '[[' . esc_html( $code ) . ']]' : '<em>HTML direct, sans shortcode</em>';
	$html .= '</p>';

	if ( $note !== '' ) {
		$html .= '<p style="color:#5A6B7B;font-size:.9375rem">' . esc_html( $note ) . '</p>';
	}

	$html .= $rendu;
	$html .= '<hr style="margin:3rem 0;border:0;border-top:1px solid #DCE4EC">';

	return $html;
}

$sg  = '<p>Page de contrôle interne. Chaque bloc montre un composant et le '
	. 'shortcode qui le produit. Si quelque chose casse, ça se voit ici en premier.</p>';
$sg .= '<hr style="margin:2rem 0;border:0;border-top:1px solid #DCE4EC">';

$sg .= cc_bloc_styleguide(
	'Boutons',
	'',
	'<div class="cc-btn-rangee">'
	. '<a class="cc-btn cc-btn--primaire" href="#">Action principale</a>'
	. '<a class="cc-btn cc-btn--secondaire" href="#">Action secondaire</a>'
	. '<a class="cc-btn cc-btn--whatsapp" href="#">Discuter sur WhatsApp</a>'
	. '</div>',
	'Tabuler jusqu\'aux boutons pour vérifier le contour de focus (2px, décalé de 2px). '
	. 'Le libellé WhatsApp est en encre et non en blanc : blanc sur le vert WhatsApp donne 1,98:1.'
);

$sg .= cc_bloc_styleguide(
	'Titre de section',
	'',
	'<h3 class="cc-titre-section">Titre avec filet vert</h3>'
	. '<h3 class="cc-titre-section cc-titre-section--centre">Variante centrée</h3>'
);

$sg .= cc_bloc_styleguide( 'Points forts', 'cc_atouts', '[cc_atouts]' );

$sg .= cc_bloc_styleguide(
	'Parcours en 8 étapes',
	'cc_parcours',
	'[cc_parcours]',
	'Alternance gauche/droite à partir de 1024px, colonne unique en dessous.'
);

$sg .= cc_bloc_styleguide( 'Parcours, version compacte', 'cc_parcours compact="1"', '[cc_parcours compact="1"]' );

$sg .= cc_bloc_styleguide(
	'Packs pré-admission',
	'cc_packs situation="pre"',
	'[cc_packs situation="pre"]',
	'Aucun tarif, et aucune zone réservée pour en accueillir un.'
);

$sg .= cc_bloc_styleguide( 'Packs post-admission', 'cc_packs situation="post"', '[cc_packs situation="post"]' );

$sg .= cc_bloc_styleguide(
	'Témoignages',
	'cc_temoignages limite="3"',
	'[cc_temoignages limite="3"][cc_exemple composant="temoignages"]',
	'Seuls les témoignages dont l\'accord écrit est coché sont rendus. Sur le contenu '
	. 'de démonstration, aucun ne l\'est : le bloc doit donc annoncer qu\'il n\'a rien à montrer.'
);

$sg .= cc_bloc_styleguide(
	'Partenaires, tous types',
	'cc_partenaires',
	'[cc_partenaires][cc_exemple composant="partenaires"]',
	'Même règle : seuls les partenariats formalisés apparaissent.'
);

$sg .= cc_bloc_styleguide( 'Partenaires, logement seulement', 'cc_partenaires type="logement"', '[cc_partenaires type="logement"]' );

$sg .= cc_bloc_styleguide(
	'Destinations',
	'cc_destinations',
	'[cc_destinations]',
	'La France est cliquable, le Canada et l\'Allemagne ne le sont pas.'
);

$sg .= cc_bloc_styleguide(
	'Questions fréquentes',
	'cc_faq',
	'[cc_faq]',
	'Accordéon natif (details/summary) : pliage, clavier et annonce gérés sans JavaScript.'
);

$sg .= cc_bloc_styleguide( 'Rappel des CTA', 'cc_cta_final', '[cc_cta_final texte="Rappel de la promesse en une phrase."]' );

$sg .= cc_bloc_styleguide(
	'Fonds de section',
	'',
	'<div class="cc-section cc-section--bleue"><div class="cc-container">'
	. '<h3 class="cc-titre-section">Section bleue</h3>'
	. '<p>Texte courant sur fond bleu clair — 13,29:1.</p></div></div>'
	. '<div class="cc-section cc-section--nuit"><div class="cc-container">'
	. '<h3 class="cc-titre-section">Section nuit</h3>'
	. '<p>Texte clair sur bleu nuit — 9,41:1.</p>'
	. '<div class="cc-btn-rangee"><a class="cc-btn cc-btn--secondaire" href="#">Bouton sur fond sombre</a></div>'
	. '</div></div>'
);

$styleguide = get_page_by_path( 'styleguide', OBJECT, 'page' );

$donnees_sg = array(
	'post_type'    => 'page',
	'post_title'   => 'Styleguide',
	'post_name'    => 'styleguide',
	'post_status'  => 'private',
	'post_content' => $sg,
);

if ( $styleguide ) {
	$donnees_sg['ID'] = $styleguide->ID;
}

$id_sg = wp_insert_post( $donnees_sg, true );

if ( is_wp_error( $id_sg ) ) {
	WP_CLI::warning( 'Styleguide : ' . $id_sg->get_error_message() );
} else {
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $id_sg, 'fr' );
	}
	WP_CLI::log( sprintf( 'Styleguide (privée) : %s', get_permalink( $id_sg ) ) );
}

/* ------------------------------------------------------------------ *
 * 8. Permaliens
 * ------------------------------------------------------------------ */

if ( ! get_option( 'permalink_structure' ) ) {
	update_option( 'permalink_structure', '/%postname%/' );
	WP_CLI::log( 'Permaliens passés en /%postname%/.' );
}

flush_rewrite_rules( false );

WP_CLI::success( 'Structure en place.' );
