<?php
/**
 * Plugin Name: CC – Téléversement SVG assaini (administrateurs)
 * Description: Autorise le SVG au seul rôle administrateur, après assainissement du fichier.
 *
 * Un SVG est un document XML exécutable : il peut porter du script, des
 * gestionnaires onload, des liens javascript: ou une référence externe qui
 * transforme un logo en vecteur XSS ou en fuite d'IP visiteur. WordPress le
 * refuse par défaut pour cette raison.
 *
 * Ici : le type MIME n'est ouvert qu'aux comptes disposant de `manage_options`
 * (donc l'administrateur), et chaque fichier est réécrit à partir d'une liste
 * blanche d'éléments et d'attributs avant d'atteindre uploads/. Un SVG qui ne
 * survit pas à l'assainissement est rejeté, jamais stocké tel quel.
 */

defined( 'ABSPATH' ) || exit;

/** Seul un administrateur peut téléverser du SVG. */
function cc_svg_user_can_upload() {
	return current_user_can( 'manage_options' ) && current_user_can( 'upload_files' );
}

/** Ouvre le type MIME image/svg+xml, uniquement pour ces utilisateurs. */
add_filter( 'upload_mimes', function ( $mimes ) {
	if ( cc_svg_user_can_upload() ) {
		$mimes['svg'] = 'image/svg+xml';
	} else {
		unset( $mimes['svg'], $mimes['svgz'] );
	}
	return $mimes;
} );

/**
 * WordPress vérifie le contenu réel du fichier et, pour un SVG, ne retrouve pas
 * le couple extension/type attendu : sans ce filtre l'envoi échoue avec
 * « ce fichier n'est pas autorisé pour des raisons de sécurité ».
 */
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
	if ( ! cc_svg_user_can_upload() ) {
		return $data;
	}
	if ( strtolower( substr( $filename, -4 ) ) === '.svg' ) {
		$data['ext']  = 'svg';
		$data['type'] = 'image/svg+xml';
	}
	return $data;
}, 10, 4 );

/**
 * Assainit le fichier au téléversement, avant qu'il n'atteigne uploads/.
 *
 * @param array $file Entrée de $_FILES en cours de traitement.
 * @return array Le fichier, éventuellement porteur d'une clé `error` qui annule l'envoi.
 */
function cc_svg_prefilter( $file ) {
	$nom  = isset( $file['name'] ) ? strtolower( $file['name'] ) : '';
	$type = isset( $file['type'] ) ? $file['type'] : '';

	/*
	 * Le type déclaré ne suffit pas : un sideload (wp media import, ACF, un
	 * importateur d'extension) construit souvent le tableau sans clé `type`.
	 * Se fier au seul $file['type'] laisserait ces fichiers passer sans
	 * assainissement. On regarde donc aussi l'extension.
	 */
	$est_svg = ( $type === 'image/svg+xml' )
		|| ( substr( $nom, -4 ) === '.svg' )
		|| ( substr( $nom, -5 ) === '.svgz' );

	if ( ! $est_svg ) {
		return $file;
	}

	if ( ! cc_svg_user_can_upload() ) {
		$file['error'] = __( 'Seuls les administrateurs peuvent téléverser des fichiers SVG.', 'campus-connect' );
		return $file;
	}

	// Le .svgz est un SVG gzippé : on ne sait pas l'assainir en l'état.
	if ( strtolower( substr( $file['name'], -5 ) ) === '.svgz' ) {
		$file['error'] = __( 'Les fichiers SVGZ compressés ne sont pas acceptés. Téléverser un .svg non compressé.', 'campus-connect' );
		return $file;
	}

	$contenu = file_get_contents( $file['tmp_name'] );
	if ( $contenu === false || trim( $contenu ) === '' ) {
		$file['error'] = __( 'Fichier SVG illisible ou vide.', 'campus-connect' );
		return $file;
	}

	$propre = cc_svg_sanitize( $contenu );
	if ( $propre === false ) {
		$file['error'] = __( 'Ce SVG n\'a pas pu être assaini (XML invalide ou contenu non autorisé). Fichier refusé.', 'campus-connect' );
		return $file;
	}

	if ( file_put_contents( $file['tmp_name'], $propre ) === false ) {
		$file['error'] = __( 'Impossible d\'écrire le SVG assaini.', 'campus-connect' );
		return $file;
	}

	return $file;
}

/*
 * Les deux chemins d'entrée d'un fichier dans la médiathèque :
 *   - upload   : « Ajouter un média » dans l'administration ;
 *   - sideload : import par un script ou une extension (wp media import, ACF…).
 * Sans le second, un SVG importé en ligne de commande échapperait à l'assainissement.
 */
add_filter( 'wp_handle_upload_prefilter', 'cc_svg_prefilter' );
add_filter( 'wp_handle_sideload_prefilter', 'cc_svg_prefilter' );

/**
 * Réécrit un SVG à partir d'une liste blanche.
 *
 * Tout élément hors liste est supprimé avec son contenu ; tout attribut hors
 * liste est retiré ; les valeurs pointant vers un script, une ressource externe
 * ou une donnée non-image sont écartées.
 *
 * @param string $svg Contenu XML source.
 * @return string|false SVG assaini, ou false si le document est inexploitable.
 */
function cc_svg_sanitize( $svg ) {
	// DOCTYPE / entités externes : vecteur XXE. On refuse d'emblée.
	if ( preg_match( '/<!(DOCTYPE|ENTITY)/i', $svg ) ) {
		return false;
	}

	$elements_autorises = array(
		'svg', 'title', 'desc', 'metadata', 'defs', 'g', 'symbol', 'use',
		'path', 'circle', 'ellipse', 'line', 'polygon', 'polyline', 'rect',
		'text', 'tspan', 'textpath',
		'lineargradient', 'radialgradient', 'stop',
		'clippath', 'mask', 'pattern', 'marker',
	);

	$attributs_autorises = array(
		// Structure
		'id', 'class', 'xmlns', 'xmlns:xlink', 'version', 'viewbox',
		'width', 'height', 'x', 'y', 'preserveaspectratio',
		// Accessibilité
		'role', 'aria-label', 'aria-labelledby', 'aria-describedby', 'aria-hidden',
		// Géométrie
		'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x1', 'y1', 'x2', 'y2',
		'points', 'transform', 'gradienttransform', 'gradientunits',
		'patternunits', 'patterncontentunits', 'offset', 'spreadmethod',
		'clip-path', 'clip-rule', 'mask', 'maskunits',
		'markerwidth', 'markerheight', 'refx', 'refy', 'orient',
		'fx', 'fy', 'fr',
		// Rendu
		'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width',
		'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset',
		'stroke-opacity', 'stroke-miterlimit', 'opacity', 'color',
		'stop-color', 'stop-opacity', 'style', 'display', 'visibility',
		'vector-effect', 'paint-order', 'shape-rendering',
		// Texte
		'font-family', 'font-size', 'font-weight', 'font-style', 'letter-spacing',
		'text-anchor', 'dominant-baseline', 'dx', 'dy',
		// Références internes (valeur filtrée plus bas)
		'href', 'xlink:href',
	);

	$erreurs_precedentes = libxml_use_internal_errors( true );

	$dom                     = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	// LIBXML_NONET : aucune requête réseau pendant l'analyse du document.
	$charge = $dom->loadXML( $svg, LIBXML_NONET );

	libxml_clear_errors();
	libxml_use_internal_errors( $erreurs_precedentes );

	if ( ! $charge || ! $dom->documentElement ) {
		return false;
	}

	if ( strtolower( $dom->documentElement->nodeName ) !== 'svg' ) {
		return false;
	}

	cc_svg_clean_node( $dom->documentElement, $elements_autorises, $attributs_autorises );
	cc_svg_clean_attributes( $dom->documentElement, $attributs_autorises );

	// saveXML() sur l'élément racine, et non sur le document : cela évite la
	// déclaration XML en tête, inutile pour un SVG servi en image ou inline.
	$sortie = $dom->saveXML( $dom->documentElement );

	return $sortie === false ? false : $sortie;
}

/**
 * Nettoie récursivement les enfants d'un nœud.
 *
 * @param DOMElement $node
 * @param string[]   $elements_autorises  Noms d'éléments, en minuscules.
 * @param string[]   $attributs_autorises Noms d'attributs, en minuscules.
 */
function cc_svg_clean_node( $node, $elements_autorises, $attributs_autorises ) {
	// Parcours à l'envers : retirer un enfant décale la NodeList, qui est vivante.
	for ( $i = $node->childNodes->length - 1; $i >= 0; $i-- ) {
		$enfant = $node->childNodes->item( $i );

		// Instruction de traitement (xml-stylesheet) : référence externe.
		if ( $enfant->nodeType === XML_PI_NODE ) {
			$node->removeChild( $enfant );
			continue;
		}

		// Commentaires, texte, CDATA : ne s'exécutent pas.
		if ( $enfant->nodeType !== XML_ELEMENT_NODE ) {
			continue;
		}

		if ( ! in_array( strtolower( $enfant->nodeName ), $elements_autorises, true ) ) {
			$node->removeChild( $enfant );
			continue;
		}

		cc_svg_clean_attributes( $enfant, $attributs_autorises );
		cc_svg_clean_node( $enfant, $elements_autorises, $attributs_autorises );
	}
}

/**
 * Retire les attributs hors liste blanche et les valeurs dangereuses.
 *
 * @param DOMElement $element
 * @param string[]   $attributs_autorises
 */
function cc_svg_clean_attributes( $element, $attributs_autorises ) {
	for ( $i = $element->attributes->length - 1; $i >= 0; $i-- ) {
		$attr = $element->attributes->item( $i );
		$nom  = strtolower( $attr->nodeName );
		$val  = (string) $attr->nodeValue;

		// Gestionnaires d'événements : onload, onclick, onmouseover…
		if ( strpos( $nom, 'on' ) === 0 ) {
			$element->removeAttribute( $attr->nodeName );
			continue;
		}

		if ( ! in_array( $nom, $attributs_autorises, true ) ) {
			$element->removeAttribute( $attr->nodeName );
			continue;
		}

		// href / xlink:href : seules les références internes (#id) sont gardées.
		if ( $nom === 'href' || $nom === 'xlink:href' ) {
			if ( strpos( ltrim( $val ), '#' ) !== 0 ) {
				$element->removeAttribute( $attr->nodeName );
			}
			continue;
		}

		// Valeur compactée : neutralise « java\nscript: » et les espaces insérés.
		$compacte = strtolower( preg_replace( '/[\s\x00-\x1F]+/', '', $val ) );

		if ( preg_match( '/(javascript|vbscript|livescript|data|blob):/', $compacte ) ) {
			$element->removeAttribute( $attr->nodeName );
			continue;
		}

		// url() distant : chargement externe et fuite d'IP visiteur.
		// Les références internes, url(#degrade), restent autorisées.
		if ( strpos( $compacte, 'url(' ) !== false
			&& ! preg_match( '/^url\([\'"]?#[^)]*\)$/', $compacte ) ) {
			$element->removeAttribute( $attr->nodeName );
			continue;
		}

		// @import et expression() dans un attribut style.
		if ( $nom === 'style' && preg_match( '/@import|expression\s*\(|behaviou?r\s*:/i', $val ) ) {
			$element->removeAttribute( $attr->nodeName );
		}
	}
}

/**
 * Dimensions réelles d'un SVG, lues dans le fichier.
 *
 * getimagesize() ne sait pas lire un SVG : sans ça, WordPress annonce un média
 * de 0×0 et écrit des attributs width/height arbitraires, ce qui déforme un
 * logo horizontal dans l'en-tête. On lit donc width/height, et à défaut le
 * viewBox, qui porte toujours le rapport de forme.
 *
 * @param int $attachment_id
 * @return array{0:int,1:int}|false
 */
function cc_svg_dimensions( $attachment_id ) {
	$cache = get_post_meta( $attachment_id, '_cc_svg_dimensions', true );
	if ( is_array( $cache ) && ! empty( $cache[0] ) && ! empty( $cache[1] ) ) {
		return array( (int) $cache[0], (int) $cache[1] );
	}

	$fichier = get_attached_file( $attachment_id );
	if ( ! $fichier || ! file_exists( $fichier ) ) {
		return false;
	}

	// L'en-tête suffit : la balise <svg> ouvrante est en tête de fichier.
	$entete = file_get_contents( $fichier, false, null, 0, 2048 );
	if ( $entete === false || ! preg_match( '/<svg\b[^>]*>/i', $entete, $balise ) ) {
		return false;
	}
	$balise = $balise[0];

	$largeur = $hauteur = 0;

	// width/height explicites, en unités absolues uniquement : un « 100% »
	// ne dit rien du rapport de forme, on passe alors au viewBox.
	if ( preg_match( '/\bwidth\s*=\s*["\']([\d.]+)(px)?["\']/i', $balise, $m ) ) {
		$largeur = (float) $m[1];
	}
	if ( preg_match( '/\bheight\s*=\s*["\']([\d.]+)(px)?["\']/i', $balise, $m ) ) {
		$hauteur = (float) $m[1];
	}

	if ( ( ! $largeur || ! $hauteur )
		&& preg_match( '/\bviewBox\s*=\s*["\']\s*[\d.eE+-]+[,\s]+[\d.eE+-]+[,\s]+([\d.eE+-]+)[,\s]+([\d.eE+-]+)/i', $balise, $m ) ) {
		$largeur = (float) $m[1];
		$hauteur = (float) $m[2];
	}

	if ( $largeur <= 0 || $hauteur <= 0 ) {
		return false;
	}

	$dimensions = array( (int) round( $largeur ), (int) round( $hauteur ) );
	update_post_meta( $attachment_id, '_cc_svg_dimensions', $dimensions );

	return $dimensions;
}

/**
 * Médiathèque et front : donne au SVG ses vraies dimensions, pour que le logo
 * garde son rapport de forme et que la vignette ne soit ni écrasée ni invisible.
 */
add_filter( 'wp_get_attachment_image_src', function ( $image, $attachment_id ) {
	if ( ! is_array( $image ) || get_post_mime_type( $attachment_id ) !== 'image/svg+xml' ) {
		return $image;
	}

	$dimensions = cc_svg_dimensions( $attachment_id );
	if ( ! $dimensions ) {
		return $image;
	}

	$image[1] = $dimensions[0];
	$image[2] = $dimensions[1];

	return $image;
}, 10, 2 );

/**
 * Même correction dans les métadonnées de la pièce jointe : c'est ce que lisent
 * l'écran de détail du média et les extensions tierces.
 */
add_filter( 'wp_update_attachment_metadata', function ( $data, $attachment_id ) {
	if ( get_post_mime_type( $attachment_id ) !== 'image/svg+xml' ) {
		return $data;
	}

	$dimensions = cc_svg_dimensions( $attachment_id );
	if ( ! $dimensions ) {
		return $data;
	}

	$data             = is_array( $data ) ? $data : array();
	$data['width']    = $dimensions[0];
	$data['height']   = $dimensions[1];
	$data['file']     = isset( $data['file'] ) ? $data['file'] : _wp_relative_upload_path( get_attached_file( $attachment_id ) );
	$data['sizes']    = isset( $data['sizes'] ) ? $data['sizes'] : array();

	return $data;
}, 10, 2 );
