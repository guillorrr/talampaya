<?php
/**
 * The Template for displaying all single posts
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

use Timber\Timber;
use Talampaya\src\app\Controllers\DefaultController;

$context = Timber::context();
$post = $context["post"];
$templates = ["pages/single-" . $post->post_type . ".twig", "pages/single.twig"];

if (post_password_required($post->ID)) {
	$templates = "pages/single-password.twig";
}

$controller = new DefaultController();

Timber::render("@pages/single.twig", $controller->get_single_context($context));
