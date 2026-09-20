<?php
/**
 * Plugin Name: CC – Configuration centrale
 * Description: Source unique des coordonnées Campus Connect (WhatsApp, contacts).
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │  NUMÉROS DE DÉVELOPPEMENT — À REMPLACER AVANT LA MISE EN PRODUCTION      │
 * │                                                                          │
 * │  CC_WHATSAPP_FR et CC_WHATSAPP_CM ci-dessous sont des numéros de test.   │
 * │  Ils doivent être remplacés par les numéros professionnels de Campus     │
 * │  Connect avant toute ouverture au public : premier point bloquant de     │
 * │  docs/checklist-mise-en-ligne.md.                                        │
 * │                                                                          │
 * │  Tant qu'ils sont actifs hors environnement local, une alerte s'affiche  │
 * │  dans le back-office (voir cc_whatsapp_dev_notice plus bas).             │
 * └──────────────────────────────────────────────────────────────────────────┘
 *
 * Aucun numéro ne doit être écrit en dur ailleurs : ni dans le thème, ni dans
 * une page Elementor. Utiliser cc_whatsapp_link() ou le shortcode [cc_whatsapp].
 */

defined( 'ABSPATH' ) || exit;

/*
 * Format wa.me : indicatif pays + numéro, sans « + », sans espace, sans zéro
 * initial. Un numéro mal formaté n'échoue pas : wa.me ouvre une page d'erreur.
 */
define( 'CC_WHATSAPP_FR', '33745619902' );
define( 'CC_WHATSAPP_CM', '237673514650' );

/** Numéros de dev, comparés tels quels pour déclencher l'alerte back-office. */
const CC_WHATSAPP_DEV_NUMBERS = [ '33745619902', '237673514650' ];

/**
 * Numéro WhatsApp d'un pays.
 *
 * @param string $pays 'fr' (France) ou 'cm' (Cameroun). Toute autre valeur
 *                     retombe sur la France, qui est le canal par défaut.
 * @return string Numéro au format international sans « + ».
 */
function cc_whatsapp_number( $pays = 'fr' ) {
    $pays = strtolower( trim( (string) $pays ) );

    $numeros = [
        'fr' => CC_WHATSAPP_FR,
        'cm' => CC_WHATSAPP_CM,
    ];

    return isset( $numeros[ $pays ] ) ? $numeros[ $pays ] : CC_WHATSAPP_FR;
}

/**
 * Lien wa.me vers le WhatsApp Campus Connect, message pré-rempli optionnel.
 *
 * @param string $pays    'fr' ou 'cm'.
 * @param string $message Message pré-rempli. Vide = aucun paramètre text.
 * @return string URL absolue, déjà encodée. À passer dans esc_url() à l'affichage.
 */
function cc_whatsapp_link( $pays = 'fr', $message = '' ) {
    $url = 'https://wa.me/' . cc_whatsapp_number( $pays );

    $message = trim( (string) $message );
    if ( $message !== '' ) {
        // rawurlencode : wa.me attend %20 pour les espaces, pas « + ».
        $url .= '?text=' . rawurlencode( $message );
    }

    return $url;
}

/**
 * Shortcode [cc_whatsapp] — bouton ou lien WhatsApp, utilisable dans Elementor.
 *
 * Exemples :
 *   [cc_whatsapp]
 *   [cc_whatsapp pays="cm" texte="Écrire au bureau de Douala"]
 *   [cc_whatsapp message="Bonjour, je souhaite étudier en France."]
 *   [cc_whatsapp url_seule="1"]   → l'URL nue, pour l'attribut lien d'un bouton Elementor
 */
function cc_whatsapp_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'pays'      => 'fr',
            'message'   => '',
            'texte'     => 'Discuter sur WhatsApp',
            'classe'    => '',
            'url_seule' => '',
        ],
        $atts,
        'cc_whatsapp'
    );

    $url = cc_whatsapp_link( $atts['pays'], $atts['message'] );

    // Pour alimenter le champ « lien » d'un widget Elementor sans balise autour.
    if ( ! empty( $atts['url_seule'] ) && $atts['url_seule'] !== '0' ) {
        return esc_url( $url );
    }

    $classes = trim( 'cc-whatsapp-link ' . $atts['classe'] );

    return sprintf(
        // noopener : obligatoire avec target="_blank". nofollow : lien sortant non éditorial.
        '<a class="%1$s" href="%2$s" target="_blank" rel="noopener nofollow">%3$s</a>',
        esc_attr( $classes ),
        esc_url( $url ),
        esc_html( $atts['texte'] )
    );
}
add_shortcode( 'cc_whatsapp', 'cc_whatsapp_shortcode' );

/**
 * Alerte back-office tant que les numéros de dev sont en place.
 *
 * Silencieuse en environnement 'local' : c'est le cas normal pendant le
 * développement. Elle apparaît dès que le site tourne en staging ou en
 * production, c'est-à-dire exactement quand l'oubli devient un problème.
 */
function cc_whatsapp_dev_notice() {
    if ( wp_get_environment_type() === 'local' ) {
        return;
    }

    $actifs = array_intersect(
        [ CC_WHATSAPP_FR, CC_WHATSAPP_CM ],
        CC_WHATSAPP_DEV_NUMBERS
    );

    if ( empty( $actifs ) ) {
        return;
    }

    printf(
        '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s<br><code>%3$s</code></p></div>',
        esc_html__( 'Campus Connect :', 'campus-connect' ),
        esc_html__(
            'les numéros WhatsApp de développement sont encore actifs sur ce site. Les messages des visiteurs n\'arrivent pas à Campus Connect. Remplacer CC_WHATSAPP_FR et CC_WHATSAPP_CM par les numéros professionnels.',
            'campus-connect'
        ),
        esc_html( 'wp-content/mu-plugins/cc-config.php — ' . implode( ', ', $actifs ) )
    );
}
add_action( 'admin_notices', 'cc_whatsapp_dev_notice' );
