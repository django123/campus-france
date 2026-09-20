<?php
/**
 * Gabarit : [cc_destinations]
 *
 * Une destination « bientôt » n'est pas un lien. Ce n'est pas un détail de
 * style : un lien invite à cliquer, et il n'y a rien à montrer derrière tant
 * que la destination n'est pas ouverte.
 *
 * @var array $cc {
 *     @type WP_Post[] $destinations
 *     @type string    $titre
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cc-destinations">

	<?php if ( ! empty( $cc['titre'] ) ) : ?>
		<h2 class="cc-titre-section"><?php echo esc_html( $cc['titre'] ); ?></h2>
	<?php endif; ?>

	<div class="cc-grille cc-grille--3">
		<?php
		foreach ( $cc['destinations'] as $destination ) :
			$statut   = cc_destination_statut( $destination->ID );
			$active   = ( $statut === 'active' );
			$accroche = function_exists( 'get_field' ) ? (string) get_field( 'texte_accroche', $destination->ID ) : '';
			$nom      = get_the_title( $destination );

			$classes = 'cc-carte' . ( $active ? '' : ' cc-carte--destination-bientot' );
			?>
			<article <?php post_class( $classes, $destination->ID ); ?>>
				<div class="cc-destination">

					<span class="cc-destination__etat cc-destination__etat--<?php echo esc_attr( $active ? 'active' : 'bientot' ); ?>">
						<?php echo esc_html( $active ? 'Ouverte' : 'Bientôt' ); ?>
					</span>

					<?php if ( $active ) : ?>
						<a class="cc-destination__lien" href="<?php echo esc_url( get_permalink( $destination ) ); ?>">
							<h3 class="cc-destination__nom"><?php echo esc_html( $nom ); ?></h3>
						</a>
					<?php else : ?>
						<h3 class="cc-destination__nom">
							<?php echo esc_html( $nom ); ?>
							<span class="cc-sr-only">— destination pas encore ouverte</span>
						</h3>
					<?php endif; ?>

					<?php if ( $accroche !== '' ) : ?>
						<p class="cc-destination__accroche"><?php echo esc_html( $accroche ); ?></p>
					<?php endif; ?>

				</div>
			</article>
		<?php endforeach; ?>
	</div>

</div>
