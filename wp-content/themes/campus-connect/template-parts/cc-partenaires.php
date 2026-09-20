<?php
/**
 * Gabarit : [cc_partenaires]
 *
 * Seuls les partenariats formalisés arrivent ici, le shortcode a déjà filtré.
 * La grille s'adapte au nombre reçu : pas de case vide.
 *
 * @var array $cc {
 *     @type WP_Post[] $partenaires
 *     @type string    $titre
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cc-partenaires-bloc">

	<?php if ( ! empty( $cc['titre'] ) ) : ?>
		<h2 class="cc-titre-section"><?php echo esc_html( $cc['titre'] ); ?></h2>
	<?php endif; ?>

	<ul class="cc-partenaires">
		<?php
		foreach ( $cc['partenaires'] as $partenaire ) :
			$logo = function_exists( 'get_field' ) ? get_field( 'logo', $partenaire->ID ) : null;
			$site = function_exists( 'get_field' ) ? (string) get_field( 'site_web', $partenaire->ID ) : '';
			$nom  = get_the_title( $partenaire );

			// Le logo porte le nom en alt : c'est l'information utile.
			if ( is_array( $logo ) && ! empty( $logo['url'] ) ) {
				$visuel = sprintf(
					'<img class="cc-partenaires__logo" src="%s" alt="%s" loading="lazy" decoding="async">',
					esc_url( $logo['url'] ),
					esc_attr( $nom )
				);
			} else {
				// Repli : le nom en toutes lettres, jamais une case vide.
				$visuel = sprintf( '<span class="cc-partenaires__nom">%s</span>', esc_html( $nom ) );
			}
			?>
			<li class="cc-partenaires__item">
				<?php if ( $site !== '' ) : ?>
					<a class="cc-partenaires__lien"
					   href="<?php echo esc_url( $site ); ?>"
					   target="_blank"
					   rel="noopener nofollow">
						<?php echo $visuel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="cc-sr-only">(nouvelle fenêtre)</span>
					</a>
				<?php else : ?>
					<?php echo $visuel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

</div>
