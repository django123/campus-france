<?php
/**
 * Thème enfant Campus Connect (parent : Hello Elementor).
 * Les CPT/taxonomies vivent dans mu-plugins pour survivre à un changement de thème.
 */

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'campus-connect-child',
        get_stylesheet_uri(),
        [ 'hello-elementor' ],
        wp_get_theme()->get( 'Version' )
    );
}, 20 );
