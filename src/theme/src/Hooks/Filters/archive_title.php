<?php

/******************************************************************************/
/* Archive Title **************************************************************/
/******************************************************************************/

if (!function_exists("talampaya_archive_title")):
	function talampaya_archive_title($title)
	{
		if (is_category()) {
			$title = single_cat_title("", false);
		} elseif (is_tag()) {
			$title = single_tag_title("", false);
		} elseif (is_author()) {
			$title = get_the_author();
		}

		return $title;
	}
	add_filter("get_the_archive_title", "talampaya_archive_title");
endif;
