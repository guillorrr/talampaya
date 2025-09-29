<?php

/**
 * The template for the 404 page
 */

use Timber\Timber;

$context = Timber::context();

$controller = new DefaultController();

Timber::render("pages/404.twig", $controller->get_404_context($context));
