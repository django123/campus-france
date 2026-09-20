<?php
/**
 * Gabarit : [cc_parcours]
 *
 * Liste ordonnée : l'ordre des étapes porte du sens, il doit être restitué
 * aux lecteurs d'écran par le balisage et pas seulement par les pastilles.
 *
 * @var array $cc {
 *     @type array  $etapes  Liste de ['titre' => string, 'texte' => string].
 *     @type string $titre   Titre de section, facultatif.
 *     @type bool   $compact Masque les descriptions.
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cc-parcours-bloc">

	<?php if ( ! empty( $cc['titre'] ) ) : ?>
		<h2 class="cc-titre-section cc-titre-section--centre"><?php echo esc_html( $cc['titre'] ); ?></h2>
	<?php endif; ?>

	<ol class="cc-parcours<?php echo $cc['compact'] ? ' cc-parcours--compact' : ''; ?>">
		<?php foreach ( $cc['etapes'] as $index => $etape ) : ?>
			<li class="cc-parcours__etape">
				<span class="cc-parcours__numero" aria-hidden="true"><?php echo esc_html( $index + 1 ); ?></span>
				<h3 class="cc-parcours__titre"><?php echo esc_html( $etape['titre'] ); ?></h3>
				<?php if ( ! $cc['compact'] && ! empty( $etape['texte'] ) ) : ?>
					<p class="cc-parcours__texte"><?php echo esc_html( $etape['texte'] ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>

</div>
