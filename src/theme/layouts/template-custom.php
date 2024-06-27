<?php
/*
Template Name: Template Custom
*/

$context = Timber::context();

$timber_post = Timber::get_post();
$context["post"] = $timber_post;
Timber::render(["pages/page-" . $timber_post->post_name . ".twig", "pages/page.twig"], $context);
