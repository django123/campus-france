<?php
/**
 * Gabarit : [cc_cta_final]
 *
 * @var array $cc {
 *     @type string $titre
 *     @type string $texte
 *     @type string $url_formulaire
 *     @type string $url_whatsapp
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cc-cta-final">

	<?php if ( ! empty( $cc['titre'] ) ) : ?>
		<h2 class="cc-titre-section cc-titre-section--centre"><?php echo esc_html( $cc['titre'] ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $cc['texte'] ) ) : ?>
		<p class="cc-cta-final__texte"><?php echo esc_html( $cc['texte'] ); ?></p>
	<?php endif; ?>

	<div class="cc-btn-rangee">
		<a class="cc-btn cc-btn--primaire" href="<?php echo esc_url( $cc['url_formulaire'] ); ?>">
			Je veux étudier en France
		</a>

		<?php if ( ! empty( $cc['url_whatsapp'] ) ) : ?>
			<a class="cc-btn cc-btn--whatsapp"
			   href="<?php echo esc_url( $cc['url_whatsapp'] ); ?>"
			   target="_blank"
			   rel="noopener nofollow">
				Discuter sur WhatsApp
				<span class="cc-sr-only">(nouvelle fenêtre)</span>
			</a>
		<?php endif; ?>
	</div>

</div>
