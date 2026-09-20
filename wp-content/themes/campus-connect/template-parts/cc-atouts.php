<?php
/**
 * Gabarit : [cc_atouts]
 *
 * @var array $cc {
 *     @type array  $atouts Liste de ['titre' => string, 'texte' => string].
 *     @type string $titre  Titre de section, facultatif.
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cc-atouts">

	<?php if ( ! empty( $cc['titre'] ) ) : ?>
		<h2 class="cc-titre-section"><?php echo esc_html( $cc['titre'] ); ?></h2>
	<?php endif; ?>

	<div class="cc-grille cc-grille--4">
		<?php foreach ( $cc['atouts'] as $atout ) : ?>
			<div class="cc-carte cc-carte--atout cc-atouts__item">
				<span class="cc-atouts__marque" aria-hidden="true"></span>
				<h3 class="cc-atouts__titre"><?php echo esc_html( $atout['titre'] ); ?></h3>
				<?php if ( $atout['texte'] !== '' ) : ?>
					<p class="cc-atouts__texte"><?php echo esc_html( $atout['texte'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

</div>
