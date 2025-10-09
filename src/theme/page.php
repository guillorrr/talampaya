<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 */

use Timber\Timber;
use Talampaya\App\Controllers\DefaultController;

$context = Timber::context();

$controller = new DefaultController();

Timber::render("@pages/page.twig", $controller->get_page_context($context));
