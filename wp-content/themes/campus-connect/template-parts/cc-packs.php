<?php
/**
 * Gabarit : [cc_packs]
 *
 * Aucun tarif, et aucune zone réservée pour en accueillir un : la règle du
 * projet garde les prix en interne, et le passage au tarif se fait par
 * « Demander mon offre personnalisée ».
 *
 * @var array $cc {
 *     @type WP_Post[] $packs
 *     @type string    $titre     Titre de section, facultatif.
 *     @type string    $url_offre Cible du bouton principal.
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cc-packs">

	<?php if ( ! empty( $cc['titre'] ) ) : ?>
		<h2 class="cc-titre-section"><?php echo esc_html( $cc['titre'] ); ?></h2>
	<?php endif; ?>

	<div class="cc-grille cc-grille--3">
		<?php
		foreach ( $cc['packs'] as $pack ) :
			$sous_titre  = function_exists( 'get_field' ) ? (string) get_field( 'sous_titre', $pack->ID ) : '';
			$prestations = cc_pack_prestations( $pack->ID );
			?>
			<article <?php post_class( 'cc-carte cc-carte--pack', $pack->ID ); ?>>

				<h3 class="cc-carte__titre"><?php echo esc_html( get_the_title( $pack ) ); ?></h3>

				<?php if ( $sous_titre !== '' ) : ?>
					<p class="cc-carte__soustitre"><?php echo esc_html( $sous_titre ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $prestations ) ) : ?>
					<ul class="cc-packs__prestations">
						<?php foreach ( $prestations as $prestation ) : ?>
							<li class="cc-packs__prestation"><?php echo esc_html( $prestation ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<div class="cc-carte__pied cc-packs__pied">
					<a class="cc-btn cc-btn--primaire"
					   href="<?php echo esc_url( $cc['url_offre'] ); ?>">
						Demander mon offre personnalisée
						<span class="cc-sr-only"> — pack <?php echo esc_html( get_the_title( $pack ) ); ?></span>
					</a>
					<a class="cc-btn cc-btn--secondaire"
					   href="<?php echo esc_url( get_permalink( $pack ) ); ?>">
						Voir le contenu du pack
						<span class="cc-sr-only"> <?php echo esc_html( get_the_title( $pack ) ); ?></span>
					</a>
				</div>

			</article>
		<?php endforeach; ?>
	</div>

</div>
