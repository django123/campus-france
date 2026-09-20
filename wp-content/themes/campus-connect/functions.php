<?php
/**
 * Thème enfant Campus Connect (parent : Hello Elementor).
 * Les CPT/taxonomies vivent dans mu-plugins pour survivre à un changement de thème.
 */

/**
 * Version de cache-busting : la version du thème en prod, le mtime du fichier
 * en local, pour ne pas vider le cache du navigateur à chaque retouche CSS.
 */
function cc_asset_version( $relative_path ) {
    if ( wp_get_environment_type() === 'local' ) {
        $file = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );
        if ( file_exists( $file ) ) {
            return (string) filemtime( $file );
        }
    }
    return wp_get_theme()->get( 'Version' );
}

add_action( 'wp_enqueue_scripts', function () {
    // Polices auto-hébergées (RGPD : aucun appel à Google Fonts).
    // Chargées avant la feuille du thème enfant, qui déclare les @font-face utilisées.
    wp_enqueue_style(
        'campus-connect-fonts',
        get_stylesheet_directory_uri() . '/assets/fonts/fonts.css',
        [],
        cc_asset_version( 'assets/fonts/fonts.css' )
    );

    wp_enqueue_style(
        'campus-connect-child',
        get_stylesheet_uri(),
        [ 'hello-elementor', 'campus-connect-fonts' ],
        cc_asset_version( 'style.css' )
    );
}, 20 );

/**
 * Précharge les deux fichiers réellement utilisés au premier rendu (texte Inter 400,
 * titres Poppins 600, sous-ensemble latin). Sans ça, le navigateur ne les découvre
 * qu'après avoir parsé fonts.css et le texte reste en police de repli plus longtemps.
 */
add_action( 'wp_head', function () {
    $preload = [
        'assets/fonts/inter-400-latin.woff2',
        'assets/fonts/poppins-600-latin.woff2',
    ];
    foreach ( $preload as $path ) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
            esc_url( get_stylesheet_directory_uri() . '/' . $path )
        );
    }
}, 1 );

/**
 * Les mêmes jetons de marque et la classe .cc-placeholder dans l'éditeur Elementor
 * et l'éditeur de blocs, pour que le back-office rende comme le site.
 */
add_action( 'admin_init', function () {
    add_editor_style( 'assets/brand/brand-tokens.css' );
    add_editor_style( 'style.css' );
} );
