<?php
/**
 * Plugin Name: CC – Types de contenu et taxonomies
 * Description: CPT et taxonomies Campus Connect. En mu-plugin pour survivre à un changement de thème.
 *
 * Choix de conception
 * -------------------
 * Slugs de réécriture au SINGULIER (/pack/essentiel/, /partenaire/…) : les
 * gabarits de liste sont des pages (« Nos accompagnements », « Partenaires »),
 * dont les slugs sont au pluriel. Un slug de CPT au pluriel entrerait en
 * collision avec la page correspondante, et c'est la page qui disparaîtrait.
 *
 * `has_archive` est à false partout, pour la même raison : ce sont les pages
 * qui listent ces contenus. Les maquettes laissent encore ouverte la question
 * d'une page par pack et d'une page par destination (voir les annotations des
 * wireframes) ; passer une archive à true reste une modification d'une ligne.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Termes structurels : ils font partie de l'architecture, pas du contenu de
 * démonstration. scripts/structure.php et scripts/seed.php les créent à partir
 * de cette même définition, pour qu'il n'existe qu'une seule source.
 *
 * @return array<string, array<string, string>> taxonomie => [ slug => nom ]
 */
function cc_termes_structurels() {
	return array(
		'situation'       => array(
			'pre-admission'  => 'Pré-admission',
			'post-admission' => 'Post-admission',
		),
		'type_partenaire' => array(
			'etablissement-france'   => 'Établissement en France',
			'etablissement-etranger' => 'Établissement à l\'étranger',
			'assurance-avi'          => 'Assurance AVI',
			'logement'               => 'Logement',
		),
	);
}

/**
 * Jeu d'étiquettes complet pour un CPT.
 *
 * WordPress n'hérite que partiellement des libellés : sans ce détail, le
 * back-office mélange « Article » et le nom réel du type dans les messages.
 *
 * @param string $singulier
 * @param string $pluriel
 * @param string $genre 'f' pour un nom féminin, 'm' sinon.
 * @return array<string, string>
 */
function cc_labels_cpt( $singulier, $pluriel, $genre = 'm' ) {
	$un       = ( $genre === 'f' ) ? 'Une' : 'Un';
	$nouveau  = ( $genre === 'f' ) ? 'Nouvelle' : 'Nouveau';
	$le       = ( $genre === 'f' ) ? 'la' : 'le';
	$accorde  = ( $genre === 'f' ) ? 'e' : '';

	return array(
		'name'                  => $pluriel,
		'singular_name'         => $singulier,
		'menu_name'             => $pluriel,
		'all_items'             => 'Tous les ' . $pluriel,
		'add_new'               => 'Ajouter',
		'add_new_item'          => $nouveau . ' ' . $singulier,
		'edit_item'             => 'Modifier ' . $le . ' ' . $singulier,
		'new_item'              => $nouveau . ' ' . $singulier,
		'view_item'             => 'Voir ' . $le . ' ' . $singulier,
		'view_items'            => 'Voir les ' . $pluriel,
		'search_items'          => 'Rechercher ' . $un . ' ' . $singulier,
		'not_found'             => 'Aucun' . $accorde . ' ' . $singulier . ' trouvé' . $accorde,
		'not_found_in_trash'    => 'Aucun' . $accorde . ' ' . $singulier . ' dans la corbeille',
		'featured_image'        => 'Image à la une',
		'set_featured_image'    => 'Définir l\'image à la une',
		'remove_featured_image' => 'Retirer l\'image à la une',
		'use_featured_image'    => 'Utiliser comme image à la une',
		'insert_into_item'      => 'Insérer dans ' . $le . ' ' . $singulier,
		'items_list'            => 'Liste des ' . $pluriel,
		'item_published'        => $singulier . ' publié' . $accorde,
		'item_updated'          => $singulier . ' mis' . $accorde . ' à jour',
	);
}

/**
 * Jeu d'étiquettes complet pour une taxonomie.
 *
 * @param string $singulier
 * @param string $pluriel
 * @param string $genre 'f' pour un nom féminin, 'm' sinon.
 * @return array<string, string>
 */
function cc_labels_taxonomie( $singulier, $pluriel, $genre = 'f' ) {
	$un      = ( $genre === 'f' ) ? 'Une' : 'Un';
	$nouveau = ( $genre === 'f' ) ? 'Nouvelle' : 'Nouveau';
	$le      = ( $genre === 'f' ) ? 'la' : 'le';
	$accorde = ( $genre === 'f' ) ? 'e' : '';

	return array(
		'name'              => $pluriel,
		'singular_name'     => $singulier,
		'menu_name'         => $pluriel,
		'all_items'         => 'Tou' . ( $genre === 'f' ? 'tes les' : 's les' ) . ' ' . $pluriel,
		'edit_item'         => 'Modifier ' . $le . ' ' . $singulier,
		'view_item'         => 'Voir ' . $le . ' ' . $singulier,
		'update_item'       => 'Mettre à jour ' . $le . ' ' . $singulier,
		'add_new_item'      => 'Ajouter ' . $un . ' ' . $singulier,
		'new_item_name'     => 'Nom ' . ( $genre === 'f' ? 'de la nouvelle ' : 'du nouveau ' ) . $singulier,
		'search_items'      => 'Rechercher ' . $un . ' ' . $singulier,
		'not_found'         => 'Aucun' . $accorde . ' ' . $singulier . ' trouvé' . $accorde,
		'back_to_items'     => 'Retour aux ' . $pluriel,
		'parent_item'       => $singulier . ' parent' . $accorde,
		'parent_item_colon' => $singulier . ' parent' . $accorde . ' :',
	);
}

/**
 * Enregistre les types de contenu.
 */
function cc_enregistrer_types() {

	// --- Packs d'accompagnement -------------------------------------------
	register_post_type(
		'pack',
		array(
			'labels'        => cc_labels_cpt( 'pack', 'Packs' ),
			'description'   => 'Packs d\'accompagnement. Aucun tarif : les prix restent internes.',
			'public'        => true,
			'has_archive'   => false,
			'menu_position' => 20,
			'menu_icon'     => 'dashicons-portfolio',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'  => true,
			'rewrite'       => array(
				'slug'       => 'pack',
				'with_front' => false,
			),
		)
	);

	// --- Témoignages -------------------------------------------------------
	register_post_type(
		'temoignage',
		array(
			'labels'        => cc_labels_cpt( 'témoignage', 'Témoignages' ),
			'description'   => 'Témoignages d\'étudiants accompagnés. Diffusion soumise à un accord écrit.',
			'public'        => true,
			'has_archive'   => false,
			'menu_position' => 21,
			'menu_icon'     => 'dashicons-format-quote',
			'supports'      => array( 'title', 'thumbnail', 'revisions' ),
			'show_in_rest'  => true,
			'rewrite'       => array(
				'slug'       => 'temoignage',
				'with_front' => false,
			),
		)
	);

	// --- Partenaires -------------------------------------------------------
	register_post_type(
		'partenaire',
		array(
			'labels'        => cc_labels_cpt( 'partenaire', 'Partenaires' ),
			'description'   => 'Établissements, assureurs et bailleurs. N\'afficher que les partenariats formalisés.',
			'public'        => true,
			'has_archive'   => false,
			'menu_position' => 22,
			'menu_icon'     => 'dashicons-networking',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'show_in_rest'  => true,
			'rewrite'       => array(
				'slug'       => 'partenaire',
				'with_front' => false,
			),
		)
	);

	// --- Formations --------------------------------------------------------
	register_post_type(
		'formation',
		array(
			'labels'        => cc_labels_cpt( 'formation', 'Formations', 'f' ),
			'description'   => 'Filières et niveaux couverts par l\'accompagnement.',
			'public'        => true,
			'has_archive'   => false,
			'menu_position' => 23,
			'menu_icon'     => 'dashicons-welcome-learn-more',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'  => true,
			'rewrite'       => array(
				'slug'       => 'formation',
				'with_front' => false,
			),
		)
	);

	// --- Destinations ------------------------------------------------------
	// Le statut actif / bientôt est un champ ACF, pas une taxonomie : c'est un
	// état unique par destination, pas un classement. Voir acf-json/.
	register_post_type(
		'destination',
		array(
			'labels'        => cc_labels_cpt( 'destination', 'Destinations', 'f' ),
			'description'   => 'Pays couverts. France active ; Canada et Allemagne annoncés « bientôt ».',
			'public'        => true,
			'has_archive'   => false,
			'menu_position' => 24,
			'menu_icon'     => 'dashicons-location-alt',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'  => true,
			'rewrite'       => array(
				'slug'       => 'destination',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'cc_enregistrer_types', 5 );

/**
 * Enregistre les taxonomies.
 */
function cc_enregistrer_taxonomies() {

	// Situation : pré-admission / post-admission. Deux onglets sur la page
	// « Nos accompagnements » — c'est ce qui sépare les six packs en deux blocs.
	register_taxonomy(
		'situation',
		array( 'pack' ),
		array(
			'labels'            => cc_labels_taxonomie( 'situation', 'Situations', 'f' ),
			'description'       => 'Avancement du projet de l\'étudiant : avant ou après l\'admission.',
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'situation',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		'type_partenaire',
		array( 'partenaire' ),
		array(
			'labels'            => cc_labels_taxonomie( 'type de partenaire', 'Types de partenaire', 'm' ),
			'description'       => 'Établissement en France ou à l\'étranger, assurance AVI, logement.',
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'type-de-partenaire',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		'domaine',
		array( 'formation' ),
		array(
			'labels'            => cc_labels_taxonomie( 'domaine', 'Domaines', 'm' ),
			'description'       => 'Domaine d\'études : commerce, ingénierie, santé, arts…',
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'domaine',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'cc_enregistrer_taxonomies', 5 );

/**
 * Réécritures : les CPT et taxonomies étant déclarés dans un mu-plugin, aucune
 * activation d'extension ne déclenche le vidage des règles. Sans ça, les URL
 * des packs renvoient un 404 jusqu'au prochain enregistrement des permaliens.
 *
 * On ne vide qu'une fois par version déclarée, jamais à chaque chargement :
 * flush_rewrite_rules() sur init est coûteux et déconseillé.
 */
function cc_verifier_reecritures() {
	$version_actuelle = '2';

	if ( get_option( 'cc_types_rewrite_version' ) === $version_actuelle ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'cc_types_rewrite_version', $version_actuelle, true );
}
add_action( 'init', 'cc_verifier_reecritures', 99 );
