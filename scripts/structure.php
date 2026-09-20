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

// Bouton de conversion, cible de tous les CTA du site.
cc_entree_page(
	$menu_principal,
	$ids['je-veux-etudier-en-france'],
	'Je veux étudier en France',
	0,
	'cc-menu-cta'
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
 * 7. Permaliens
 * ------------------------------------------------------------------ */

if ( ! get_option( 'permalink_structure' ) ) {
	update_option( 'permalink_structure', '/%postname%/' );
	WP_CLI::log( 'Permaliens passés en /%postname%/.' );
}

flush_rewrite_rules( false );

WP_CLI::success( 'Structure en place.' );
