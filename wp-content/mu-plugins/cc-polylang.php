<?php
/**
 * Plugin Name: CC – Polylang
 * Description: Déclare les contenus Campus Connect comme traduisibles. Français en langue unique pour l'instant.
 *
 * Le site est monolingue aujourd'hui, mais l'anglais est prévu. Déclarer les
 * types ici plutôt que de cocher des cases dans l'interface a deux effets :
 * le réglage est versionné, et il est déjà en place le jour où la deuxième
 * langue est créée — sans quoi il faudrait réaffecter à la main chaque contenu
 * existant.
 *
 * Tant qu'une seule langue existe, Polylang n'affiche aucun sélecteur : la
 * déclaration est sans effet visible côté visiteur.
 */

defined( 'ABSPATH' ) || exit;

/** Types de contenu traduisibles. */
add_filter( 'pll_get_post_types', function ( $types, $est_reglages ) {
	$notres = array(
		'pack'        => 'pack',
		'temoignage'  => 'temoignage',
		'partenaire'  => 'partenaire',
		'formation'   => 'formation',
		'destination' => 'destination',
	);

	return array_merge( $types, $notres );
}, 10, 2 );

/** Taxonomies traduisibles. */
add_filter( 'pll_get_taxonomies', function ( $taxonomies, $est_reglages ) {
	$notres = array(
		'situation'       => 'situation',
		'type_partenaire' => 'type_partenaire',
		'domaine'         => 'domaine',
	);

	return array_merge( $taxonomies, $notres );
}, 10, 2 );

/**
 * Affecte la langue par défaut aux contenus créés sans langue.
 *
 * Un contenu sans langue est invisible sur le site dès qu'une deuxième langue
 * existe. Le cas se produit surtout pour les contenus créés par script ou par
 * import, qui ne passent pas par l'écran d'édition.
 *
 * @param int     $post_id
 * @param WP_Post $post
 */
add_action( 'save_post', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! function_exists( 'pll_get_post_language' ) || ! function_exists( 'pll_default_language' ) ) {
		return;
	}

	$types = array( 'page', 'post', 'pack', 'temoignage', 'partenaire', 'formation', 'destination' );
	if ( ! in_array( $post->post_type, $types, true ) ) {
		return;
	}

	if ( pll_get_post_language( $post_id ) ) {
		return;
	}

	$defaut = pll_default_language();
	if ( $defaut ) {
		pll_set_post_language( $post_id, $defaut );
	}
}, 10, 2 );
