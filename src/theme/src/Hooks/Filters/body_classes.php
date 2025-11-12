<?php

//==============================================================================
// Adds custom classes to the array of body classes.
//==============================================================================

if (!function_exists("talampaya_body_classes")):
	function talampaya_body_classes($classes)
	{
		// Adds a class of hfeed to non-singular pages.
		if (!is_singular()) {
			$classes[] = "hfeed";
		}

		// Adds a class of no-sidebar when there is no sidebar present.
		if (!is_active_sidebar("sidebar-1")) {
			$classes[] = "no-sidebar";
		}

		return $classes;
	}
	add_filter("body_class", "talampaya_body_classes");
endif;
