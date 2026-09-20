<?php
/**
 * Plugin Name: CC – Champs ACF
 * Description: Charge les groupes de champs depuis mu-plugins/acf-json/ et fournit les accesseurs.
 *
 * Les groupes de champs vivent en JSON versionné, jamais dans la base : une
 * modification passe par Git et se déploie avec le code. Ils sont rangés à côté
 * des CPT qu'ils décrivent, dans mu-plugins, et non dans le thème comme le veut
 * la convention ACF — pour la même raison que les CPT : ils doivent survivre à
 * un changement de thème.
 *
 * ACF installé ici est la version GRATUITE (6.8.x). Ni le champ « répéteur » ni
 * acf_add_options_page() n'existent :
 *   - la liste des prestations d'un pack est une zone de texte, une prestation
 *     par ligne, lue par cc_pack_prestations() ;
 *   - les réglages globaux passent par l'API Settings, voir cc-reglages.php.
 * Les deux sont remplaçables par leurs équivalents ACF Pro sans toucher aux
 * gabarits, à condition de garder ces mêmes accesseurs.
 */

defined( 'ABSPATH' ) || exit;

/** Répertoire des groupes de champs versionnés. */
function cc_acf_json_dir() {
	return __DIR__ . '/acf-json';
}

/** Charge les groupes depuis mu-plugins/acf-json/. */
add_filter( 'acf/settings/load_json', function ( $paths ) {
	$paths[] = cc_acf_json_dir();
	return $paths;
} );

/**
 * Écrit les modifications faites dans l'interface au même endroit, pour
 * qu'elles apparaissent dans `git status` au lieu de rester en base.
 */
add_filter( 'acf/settings/save_json', function ( $path ) {
	$dir = cc_acf_json_dir();
	return is_dir( $dir ) && is_writable( $dir ) ? $dir : $path;
} );

/**
 * Prestations d'un pack, une par ligne.
 *
 * Point de passage unique vers ce champ : si le projet passe un jour en ACF
 * Pro, seul le corps de cette fonction change, pas les gabarits qui l'appellent.
 *
 * @param int|null $post_id
 * @return string[] Lignes non vides, dans l'ordre de saisie.
 */
function cc_pack_prestations( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$brut = function_exists( 'get_field' )
		? (string) get_field( 'prestations', $post_id )
		: (string) get_post_meta( $post_id, 'prestations', true );

	if ( trim( $brut ) === '' ) {
		return array();
	}

	// Les sauts de ligne arrivent en \r\n depuis un poste Windows.
	$lignes = preg_split( '/\r\n|\r|\n/', $brut );
	$lignes = array_map( 'trim', $lignes );

	return array_values( array_filter( $lignes, function ( $ligne ) {
		return $ligne !== '';
	} ) );
}

/**
 * Statut d'une destination : 'active' ou 'bientot'.
 *
 * @param int|null $post_id
 * @return string
 */
function cc_destination_statut( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$statut = function_exists( 'get_field' )
		? get_field( 'statut', $post_id )
		: get_post_meta( $post_id, 'statut', true );

	// Par défaut « bientôt » : annoncer une destination comme ouverte alors
	// qu'elle ne l'est pas engagerait Campus Connect auprès d'un visiteur.
	return in_array( $statut, array( 'active', 'bientot' ), true ) ? $statut : 'bientot';
}

/**
 * Un témoignage est-il diffusable ?
 *
 * Sans accord écrit, la citation et le prénom ne doivent pas être publiés
 * (RGPD, et annotation des maquettes sur les témoignages).
 *
 * @param int|null $post_id
 * @return bool
 */
function cc_temoignage_diffusable( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$accord = function_exists( 'get_field' )
		? get_field( 'accord_ecrit', $post_id )
		: get_post_meta( $post_id, 'accord_ecrit', true );

	return (bool) $accord;
}

/**
 * Un partenaire est-il affichable ?
 *
 * Afficher un logo sur une page « Nos partenaires » laisse entendre qu'une
 * convention existe. Seuls les partenariats formalisés sont listés.
 *
 * @param int|null $post_id
 * @return bool
 */
function cc_partenaire_affichable( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$formalise = function_exists( 'get_field' )
		? get_field( 'partenariat_formalise', $post_id )
		: get_post_meta( $post_id, 'partenariat_formalise', true );

	return (bool) $formalise;
}

/**
 * Signale une installation d'ACF incompatible plutôt que de laisser les
 * groupes de champs disparaître sans explication.
 */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! class_exists( 'ACF' ) ) {
		printf(
			'<div class="notice notice-error"><p><strong>Campus Connect :</strong> %s</p></div>',
			esc_html__( 'Advanced Custom Fields est inactif. Les champs des packs, témoignages, partenaires et destinations ne sont plus éditables.', 'campus-connect' )
		);
		return;
	}

	if ( ! is_dir( cc_acf_json_dir() ) ) {
		printf(
			'<div class="notice notice-warning"><p><strong>Campus Connect :</strong> %s</p></div>',
			esc_html( sprintf(
				/* translators: %s: chemin du dossier */
				__( 'le dossier des groupes de champs est introuvable (%s).', 'campus-connect' ),
				'mu-plugins/acf-json'
			) )
		);
	}
} );
