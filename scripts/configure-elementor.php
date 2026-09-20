<?php
/**
 * Aligne Elementor sur la charte Campus Connect.
 *
 *   docker compose run --rm wpcli eval-file /scripts/configure-elementor.php
 *
 * Idempotent : relancer le script après une modification de
 * assets/brand/brand-tokens.css remet Elementor en phase avec les jetons.
 *
 * Les valeurs ci-dessous DOIVENT rester identiques à celles de
 * wp-content/themes/campus-connect/assets/brand/brand-tokens.css. Le CSS reste
 * la source de vérité ; ce fichier n'est que sa transcription pour Elementor,
 * qui stocke ses couleurs globales en base et non dans une feuille de style.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! did_action( 'elementor/loaded' ) ) {
	WP_CLI::error( 'Elementor n\'est pas chargé. Vérifier que le plugin est actif.' );
}

/*
 * Couleurs globales.
 *
 * Correspondance demandée entre les 4 emplacements Elementor et les jetons :
 *   Primaire   → --cc-bleu       CTA et liens
 *   Secondaire → --cc-vert       accents, CTA secondaire
 *   Texte      → --cc-encre      texte courant
 *   Accent     → --cc-bleu-nuit  titres, header, footer
 */
$couleurs = array(
	array(
		'_id'   => 'primary',
		'title' => 'Primaire — bleu',
		'color' => '#1565C0',
	),
	array(
		'_id'   => 'secondary',
		'title' => 'Secondaire — vert',
		'color' => '#17A673',
	),
	array(
		'_id'   => 'text',
		'title' => 'Texte — encre',
		'color' => '#1B2733',
	),
	array(
		'_id'   => 'accent',
		'title' => 'Accent — bleu nuit',
		'color' => '#0B3C7D',
	),
);

/*
 * Typographies globales : Poppins pour les titres, Inter pour le texte.
 *
 * Les deux familles sont auto-hébergées par le thème enfant
 * (assets/fonts/fonts.css). Elementor se contente d'écrire le font-family ;
 * il ne doit rien télécharger, d'où la désactivation des Google Fonts plus bas.
 */
$typographies = array(
	array(
		'_id'                      => 'primary',
		'title'                    => 'Titres — Poppins',
		'typography_typography'    => 'custom',
		'typography_font_family'   => 'Poppins',
		'typography_font_weight'   => '600',
	),
	array(
		'_id'                      => 'secondary',
		'title'                    => 'Intertitres — Poppins',
		'typography_typography'    => 'custom',
		'typography_font_family'   => 'Poppins',
		'typography_font_weight'   => '500',
	),
	array(
		'_id'                      => 'text',
		'title'                    => 'Texte — Inter',
		'typography_typography'    => 'custom',
		'typography_font_family'   => 'Inter',
		'typography_font_weight'   => '400',
	),
	array(
		'_id'                      => 'accent',
		'title'                    => 'Accent — Inter',
		'typography_typography'    => 'custom',
		'typography_font_family'   => 'Inter',
		'typography_font_weight'   => '500',
	),
);

$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();

if ( ! $kit || ! $kit->get_id() ) {
	WP_CLI::error( 'Aucun kit Elementor actif. Ouvrir une fois l\'éditeur Elementor pour qu\'il en crée un.' );
}

// update_settings() fusionne avec les réglages existants et vide le cache du kit.
$kit->update_settings(
	array(
		'system_colors'      => $couleurs,
		'system_typography'  => $typographies,
		'default_generic_fonts' => 'Segoe UI, Arial, sans-serif',
	)
);

WP_CLI::log( sprintf( 'Kit #%d : couleurs et typographies globales écrites.', $kit->get_id() ) );

/*
 * RGPD : aucun appel à fonts.googleapis.com depuis le front ni depuis l'éditeur.
 * La clé est `elementor_google_font` au singulier (voir includes/fonts.php,
 * is_google_fonts_enabled) — `elementor_google_fonts` n'existe pas.
 */
update_option( 'elementor_google_font', '0' );

/*
 * Ceinture et bretelles : si un widget ou une extension force malgré tout le
 * chargement d'une Google Font, elle sera servie depuis notre serveur.
 * (Valeur par défaut d'Elementor : '0'.)
 */
update_option( 'elementor_local_google_fonts', '1' );

WP_CLI::log( 'Google Fonts désactivées (elementor_google_font = 0, local_google_fonts = 1).' );

// Régénère les fichiers CSS d'Elementor, sinon les anciennes valeurs persistent.
\Elementor\Plugin::$instance->files_manager->clear_cache();

WP_CLI::success( 'Elementor aligné sur la charte Campus Connect.' );
