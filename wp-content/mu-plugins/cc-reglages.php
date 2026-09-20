<?php
/**
 * Plugin Name: CC – Réglages globaux
 * Description: Réglages éditoriaux du site (expérience, coordonnées France et Cameroun).
 *
 * Remplace la page d'options ACF, absente de la version gratuite. Tout est
 * stocké dans une seule option, `cc_reglages`, et lu par cc_reglage().
 *
 * Partage des responsabilités avec cc-config.php
 * ----------------------------------------------
 * cc-config.php  : numéros WhatsApp. Ils vivent dans le CODE parce qu'ils sont
 *                  provisoires et doivent être remplacés avant la production —
 *                  c'est le premier point bloquant de la checklist, et une
 *                  alerte back-office le rappelle. Un numéro éditable en base
 *                  passerait inaperçu.
 * cc-reglages.php: adresse, téléphone, e-mail, ancienneté. Données éditoriales
 *                  qui changent au fil de la vie de l'agence, modifiables sans
 *                  intervention technique.
 *
 * Ne pas dupliquer une donnée d'un fichier à l'autre.
 */

defined( 'ABSPATH' ) || exit;

const CC_REGLAGES_OPTION = 'cc_reglages';

/**
 * Définition des réglages. Source unique : la page d'administration, la
 * validation et les valeurs par défaut en sont toutes dérivées.
 *
 * @return array<string, array<string, mixed>>
 */
function cc_reglages_champs() {
	return array(
		'annee_debut_activite' => array(
			'section' => 'agence',
			'label'   => 'Année de début d\'activité',
			'type'    => 'number',
			'defaut'  => '',
			'aide'    => 'Sert à calculer l\'ancienneté affichée sur le site. On stocke l\'année de départ, et non un nombre d\'années : un nombre écrit en dur serait faux au prochain anniversaire.',
		),
		'france_adresse'       => array(
			'section' => 'france',
			'label'   => 'Adresse',
			'type'    => 'textarea',
			'defaut'  => '',
			'aide'    => '',
		),
		'france_telephone'     => array(
			'section' => 'france',
			'label'   => 'Téléphone',
			'type'    => 'text',
			'defaut'  => '',
			'aide'    => 'Format d\'affichage libre. Le numéro WhatsApp, lui, est défini dans cc-config.php.',
		),
		'france_email'         => array(
			'section' => 'france',
			'label'   => 'E-mail',
			'type'    => 'email',
			'defaut'  => '',
			'aide'    => '',
		),
		'cameroun_adresse'     => array(
			'section' => 'cameroun',
			'label'   => 'Adresse',
			'type'    => 'textarea',
			'defaut'  => '',
			'aide'    => '',
		),
		'cameroun_telephone'   => array(
			'section' => 'cameroun',
			'label'   => 'Téléphone',
			'type'    => 'text',
			'defaut'  => '',
			'aide'    => '',
		),
		'cameroun_email'       => array(
			'section' => 'cameroun',
			'label'   => 'E-mail',
			'type'    => 'email',
			'defaut'  => '',
			'aide'    => '',
		),
	);
}

/**
 * Valeur d'un réglage.
 *
 * @param string $cle
 * @param mixed  $defaut Renvoyé si le réglage est vide.
 * @return mixed
 */
function cc_reglage( $cle, $defaut = '' ) {
	$reglages = get_option( CC_REGLAGES_OPTION, array() );

	if ( ! is_array( $reglages ) || ! isset( $reglages[ $cle ] ) || $reglages[ $cle ] === '' ) {
		return $defaut;
	}

	return $reglages[ $cle ];
}

/**
 * Nombre d'années d'expérience, calculé à partir de l'année de début d'activité.
 *
 * Renvoie null si l'année n'est pas renseignée, pour que les gabarits puissent
 * masquer la mention plutôt que d'afficher « 0 an d'expérience ».
 *
 * @return int|null
 */
function cc_annees_experience() {
	$debut = (int) cc_reglage( 'annee_debut_activite', 0 );

	if ( $debut < 1900 ) {
		return null;
	}

	$annees = (int) current_time( 'Y' ) - $debut;

	return $annees > 0 ? $annees : null;
}

/**
 * Coordonnées d'une implantation.
 *
 * @param string $pays 'france' ou 'cameroun'.
 * @return array{adresse:string, telephone:string, email:string}
 */
function cc_coordonnees( $pays = 'france' ) {
	$pays = in_array( $pays, array( 'france', 'cameroun' ), true ) ? $pays : 'france';

	return array(
		'adresse'   => (string) cc_reglage( $pays . '_adresse' ),
		'telephone' => (string) cc_reglage( $pays . '_telephone' ),
		'email'     => (string) cc_reglage( $pays . '_email' ),
	);
}

/** Page Réglages → Campus Connect. */
add_action( 'admin_menu', function () {
	add_options_page(
		'Campus Connect',
		'Campus Connect',
		'manage_options',
		'cc-reglages',
		'cc_reglages_page'
	);
} );

/** Déclare l'option, les sections et les champs. */
add_action( 'admin_init', function () {
	register_setting(
		'cc_reglages_groupe',
		CC_REGLAGES_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'cc_reglages_nettoyer',
			'default'           => array(),
		)
	);

	$sections = array(
		'agence'   => array(
			'titre' => 'L\'agence',
			'aide'  => 'Informations reprises sur l\'accueil et dans le pied de page.',
		),
		'france'   => array(
			'titre' => 'Implantation France',
			'aide'  => '',
		),
		'cameroun' => array(
			'titre' => 'Implantation Cameroun',
			'aide'  => '',
		),
	);

	foreach ( $sections as $id => $section ) {
		add_settings_section(
			'cc_section_' . $id,
			$section['titre'],
			function () use ( $section ) {
				if ( $section['aide'] !== '' ) {
					printf( '<p class="description">%s</p>', esc_html( $section['aide'] ) );
				}
			},
			'cc-reglages'
		);
	}

	foreach ( cc_reglages_champs() as $cle => $champ ) {
		add_settings_field(
			'cc_champ_' . $cle,
			$champ['label'],
			'cc_reglages_rendu_champ',
			'cc-reglages',
			'cc_section_' . $champ['section'],
			array(
				'cle'       => $cle,
				'champ'     => $champ,
				'label_for' => 'cc_champ_' . $cle,
			)
		);
	}
} );

/**
 * Affiche un champ.
 *
 * @param array $args
 */
function cc_reglages_rendu_champ( $args ) {
	$cle    = $args['cle'];
	$champ  = $args['champ'];
	$valeur = cc_reglage( $cle, $champ['defaut'] );
	$nom    = CC_REGLAGES_OPTION . '[' . $cle . ']';
	$id     = 'cc_champ_' . $cle;

	if ( $champ['type'] === 'textarea' ) {
		printf(
			'<textarea id="%1$s" name="%2$s" rows="3" class="large-text">%3$s</textarea>',
			esc_attr( $id ),
			esc_attr( $nom ),
			esc_textarea( $valeur )
		);
	} else {
		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text"%5$s>',
			esc_attr( $champ['type'] ),
			esc_attr( $id ),
			esc_attr( $nom ),
			esc_attr( $valeur ),
			$champ['type'] === 'number' ? ' min="1900" max="2100" step="1"' : ''
		);
	}

	if ( $champ['aide'] !== '' ) {
		printf( '<p class="description">%s</p>', esc_html( $champ['aide'] ) );
	}

	// Aide contextuelle : traduire l'année saisie en années d'expérience.
	if ( $cle === 'annee_debut_activite' ) {
		$annees = cc_annees_experience();
		if ( $annees !== null ) {
			printf(
				'<p class="description"><strong>%s</strong></p>',
				esc_html( sprintf( 'Affiché aujourd\'hui : %d ans d\'expérience.', $annees ) )
			);
		}
	}
}

/**
 * Valide et nettoie avant enregistrement.
 *
 * @param mixed $entree
 * @return array<string, string>
 */
function cc_reglages_nettoyer( $entree ) {
	$propre = array();

	if ( ! is_array( $entree ) ) {
		return $propre;
	}

	foreach ( cc_reglages_champs() as $cle => $champ ) {
		$valeur = isset( $entree[ $cle ] ) ? $entree[ $cle ] : '';

		switch ( $champ['type'] ) {
			case 'number':
				$valeur = trim( (string) $valeur );
				if ( $valeur !== '' ) {
					$annee = (int) $valeur;
					// Une année hors de cette plage est une faute de frappe.
					if ( $annee < 1900 || $annee > (int) current_time( 'Y' ) ) {
						add_settings_error(
							CC_REGLAGES_OPTION,
							'cc_annee_invalide',
							sprintf(
								'Année de début d\'activité ignorée : %s n\'est pas une année plausible.',
								esc_html( $valeur )
							)
						);
						$valeur = '';
					} else {
						$valeur = (string) $annee;
					}
				}
				break;

			case 'email':
				$valeur = sanitize_email( $valeur );
				break;

			case 'textarea':
				$valeur = sanitize_textarea_field( $valeur );
				break;

			default:
				$valeur = sanitize_text_field( $valeur );
		}

		$propre[ $cle ] = $valeur;
	}

	return $propre;
}

/** Rendu de la page. */
function cc_reglages_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p class="description">
			Ces valeurs alimentent l'accueil et le pied de page. Les numéros
			WhatsApp ne sont pas ici : ils sont définis dans
			<code>mu-plugins/cc-config.php</code>.
		</p>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'cc_reglages_groupe' );
			do_settings_sections( 'cc-reglages' );
			submit_button( 'Enregistrer' );
			?>
		</form>
	</div>
	<?php
}
