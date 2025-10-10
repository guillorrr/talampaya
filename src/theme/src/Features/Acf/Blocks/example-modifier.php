<?php
/**
 * Ejemplo de cómo registrar modificadores de contexto para BlockRenderer
 *
 * Este archivo muestra cómo extender la funcionalidad de los bloques ACF
 * usando el sistema de modificadores de contexto de BlockRenderer
 */

namespace App\Features\Acf\Blocks;

// Modificador que agrega información de la fecha de publicación del post
BlockRenderer::registerContextModifier("post_date", function (
	$context,
	$attributes,
	$content,
	$is_preview,
	$post_id
) {
	if ($post_id) {
		$context["post_published_date"] = get_the_date("", $post_id);
		$context["post_modified_date"] = get_the_modified_date("", $post_id);
	}
	return $context;
});

// Modificador que añade clases CSS dinámicas al bloque
BlockRenderer::registerContextModifier("dynamic_classes", function ($context, $attributes) {
	// Añadir clases CSS basadas en atributos del bloque
	$classes = [];

	if (isset($attributes["className"])) {
		$classes[] = $attributes["className"];
	}

	if (isset($context["fields"]["background_color"])) {
		$classes[] = "has-background-" . $context["fields"]["background_color"];
	}

	$context["dynamic_classes"] = implode(" ", $classes);

	return $context;
});
