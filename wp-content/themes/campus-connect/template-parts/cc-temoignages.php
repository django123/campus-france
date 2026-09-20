<?php
/**
 * Gabarit : [cc_temoignages]
 *
 * Le filtrage sur l'accord écrit est fait en amont, dans le shortcode. Ce
 * gabarit ne rend que des témoignages déjà jugés diffusables.
 *
 * @var array $cc {
 *     @type WP_Post[] $temoignages
 *     @type string    $titre
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cc-temoignages">

	<?php if ( ! empty( $cc['titre'] ) ) : ?>
		<h2 class="cc-titre-section"><?php echo esc_html( $cc['titre'] ); ?></h2>
	<?php endif; ?>

	<div class="cc-grille cc-grille--3">
		<?php
		foreach ( $cc['temoignages'] as $temoignage ) :
			$prenom        = function_exists( 'get_field' ) ? (string) get_field( 'prenom', $temoignage->ID ) : '';
			$etablissement = function_exists( 'get_field' ) ? (string) get_field( 'etablissement', $temoignage->ID ) : '';
			$ville         = function_exists( 'get_field' ) ? (string) get_field( 'ville', $temoignage->ID ) : '';
			$citation      = function_exists( 'get_field' ) ? (string) get_field( 'citation', $temoignage->ID ) : '';

			$origine = trim( implode( ' — ', array_filter( array( $etablissement, $ville ) ) ) );
			?>
			<figure <?php post_class( 'cc-carte cc-carte--temoignage', $temoignage->ID ); ?>>

				<blockquote class="cc-temoignage__citation">
					<?php echo esc_html( $citation ); ?>
				</blockquote>

				<figcaption class="cc-temoignage__auteur">
					<?php if ( has_post_thumbnail( $temoignage->ID ) ) : ?>
						<?php
						/*
						 * alt vide : le portrait n'apporte rien au-delà du
						 * prénom déjà lu juste après. Le décrire ferait
						 * répéter l'information au lecteur d'écran.
						 */
						echo get_the_post_thumbnail(
							$temoignage->ID,
							'thumbnail',
							array(
								'class' => 'cc-temoignage__portrait',
								'alt'   => '',
								'aria-hidden' => 'true',
							)
						);
						?>
					<?php endif; ?>

					<span>
						<span class="cc-temoignage__prenom"><?php echo esc_html( $prenom ); ?></span>
						<?php if ( $origine !== '' ) : ?>
							<span class="cc-temoignage__origine"><?php echo esc_html( $origine ); ?></span>
						<?php endif; ?>
					</span>
				</figcaption>

			</figure>
		<?php endforeach; ?>
	</div>

</div>
