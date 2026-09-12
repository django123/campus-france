<?php
/**
 * Plugin Name: CC – Mailpit (dev only)
 * Description: Envoie tous les mails WordPress vers Mailpit en environnement local.
 */
if ( wp_get_environment_type() === 'local' ) {
    add_action( 'phpmailer_init', function ( $phpmailer ) {
        $phpmailer->isSMTP();
        $phpmailer->Host     = 'mailpit';
        $phpmailer->Port     = 1025;
        $phpmailer->SMTPAuth = false;
    } );
}
