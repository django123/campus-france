<?php
/**
 * Gabarit : [cc_faq]
 *
 * <details>/<summary> plutôt que des boutons et aria-expanded : le pliage, la
 * navigation au clavier et l'annonce de l'état sont gérés nativement par le
 * navigateur, sans une ligne de JavaScript. Rien ne peut casser et laisser les
 * réponses inaccessibles.
 *
 * @var array $cc {
 *     @type WP_Post[] $questions
 *     @type string    $titre
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cc-faq-bloc">

	<?php if ( ! empty( $cc['titre'] ) ) : ?>
		<h2 class="cc-titre-section cc-titre-section--centre"><?php echo esc_html( $cc['titre'] ); ?></h2>
	<?php endif; ?>

	<div class="cc-faq">
		<?php foreach ( $cc['questions'] as $question ) : ?>
			<details <?php post_class( 'cc-faq__item', $question->ID ); ?>>
				<summary class="cc-faq__question">
					<span><?php echo esc_html( get_the_title( $question ) ); ?></span>
					<span class="cc-faq__chevron" aria-hidden="true"></span>
				</summary>
				<div class="cc-faq__reponse">
					<?php
					// Contenu d'éditeur : wpautop + filtres habituels, puis
					// nettoyage par wp_kses_post avant affichage.
					echo wp_kses_post( apply_filters( 'the_content', $question->post_content ) );
					?>
				</div>
			</details>
		<?php endforeach; ?>
	</div>

</div>
