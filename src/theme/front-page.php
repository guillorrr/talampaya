<?php

use Timber\Timber;
use App\Inc\Controllers\DefaultController;

$templates = [get_template_directory() . "/views/pages/front-page.twig"];

$context = Timber::context();

$controller = new DefaultController();

Timber::render($templates, $controller->get_front_page_context($context));
