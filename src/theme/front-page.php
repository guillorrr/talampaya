<?php

use Timber\Timber;
use Talampaya\App\Controllers\DefaultController;

$templates = ["pages/front-page.twig"];

$context = Timber::context();

$controller = new DefaultController();

Timber::render($templates, $controller->get_front_page_context($context));
