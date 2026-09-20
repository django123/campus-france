<?php
/**
 * Plugin Name: CC – Contenu de démonstration
 * Description: Repère et signale le contenu créé par scripts/seed.php.
 *
 * Le contenu de démonstration porte la méta `_cc_seed`. Ce fichier s'appuie
 * dessus pour que ce contenu soit impossible à confondre avec du réel :
 *
 *   - classe .cc-placeholder ajoutée automatiquement sur le site, quel que
 *     soit le gabarit qui l'affiche — aucun besoin d'y penser à l'intégration ;
 *   - colonne « Démo » dans les listes du back-office ;
 *   - compteur en tête d'administration tant qu'il en reste.
 *
 * Ce fichier et le contenu qu'il signale disparaissent ensemble :
 *   docker compose run --rm wpcli eval-file /scripts/seed.php supprimer
 */

defined( 'ABSPATH' ) || exit;

/** Méta qui marque un contenu créé par le script de démonstration. */
const CC_META_DEMO = '_cc_seed';

/** Types de contenu susceptibles de porter du contenu de démonstration. */
function cc_demo_types() {
	return array( 'pack', 'temoignage', 'partenaire', 'formation', 'destination' );
}

/**
 * Ce contenu vient-il du script de démonstration ?
 *
 * @param int|WP_Post|null $post
 * @return bool
 */
function cc_est_contenu_demo( $post = null ) {
	$post = get_post( $post );

	return $post ? (bool) get_post_meta( $post->ID, CC_META_DEMO, true ) : false;
}

/**
 * Ajoute .cc-placeholder aux contenus de démonstration.
 *
 * Passe par post_class plutôt que par le contenu : la classe suit le contenu
 * dans n'importe quelle boucle, y compris les widgets Elementor, sans qu'aucun
 * gabarit n'ait à s'en soucier.
 */
add_filter( 'post_class', function ( $classes, $class, $post_id ) {
	if ( cc_est_contenu_demo( $post_id ) ) {
		$classes[] = 'cc-placeholder';
	}
	return $classes;
}, 10, 3 );

/** Colonne « Démo » dans les listes du back-office. */
add_action( 'admin_init', function () {
	foreach ( cc_demo_types() as $type ) {
		add_filter( "manage_{$type}_posts_columns", function ( $colonnes ) {
			$colonnes['cc_demo'] = 'Démo';
			return $colonnes;
		} );

		add_action( "manage_{$type}_posts_custom_column", function ( $colonne, $post_id ) {
			if ( $colonne === 'cc_demo' && cc_est_contenu_demo( $post_id ) ) {
				echo '<span title="Contenu de démonstration, à retirer avant la mise en ligne" style="color:#8A5300;font-weight:600">fictif</span>';
			}
		}, 10, 2 );
	}
} );

/** Nombre de contenus de démonstration encore présents. */
function cc_demo_compter() {
	$requete = new WP_Query(
		array(
			'post_type'              => cc_demo_types(),
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => CC_META_DEMO,
					'compare' => 'EXISTS',
				),
			),
		)
	);

	return (int) $requete->found_posts;
}

/**
 * Rappel en tête d'administration.
 *
 * Volontairement discret (notice-info) : en développement c'est l'état normal.
 * Le point bloquant, lui, est dans docs/checklist-mise-en-ligne.md.
 */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$nombre = cc_demo_compter();
	if ( $nombre === 0 ) {
		return;
	}

	printf(
		'<div class="notice notice-info"><p><strong>Campus Connect :</strong> %s</p></div>',
		esc_html( sprintf(
			'%d contenus de démonstration sont publiés (packs, témoignages, partenaires, destinations). Ils sont fictifs et doivent être retirés avant la mise en ligne : docker compose run --rm wpcli eval-file /scripts/seed.php supprimer',
			$nombre
		) )
	);
} );
