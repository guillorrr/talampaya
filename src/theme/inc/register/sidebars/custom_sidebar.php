<?php

$args = [
	"name" => __("Custom Sidebar", "talampaya"),
	"description" => __("Description", "talampaya"),
	"id" => "sidebar-id",
	"class" => "class",
	"before_widget" => '<li id="%1$s" class="widget %2$s">',
	"after_widget" => "</li>",
	"before_title" => '<h2 class="widgettitle">',
	"after_title" => "</h2>",
];

return [$args];
