<?php

/**
 * The template for the 404 page
 */

use Timber\Timber;

$context = Timber::context();
Timber::render("pages/404.twig", $context);
