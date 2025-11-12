<?php

//==============================================================================
// Archives Count Filter
//==============================================================================

if (!function_exists("talampaya_archive_count_filter")):
	function talampaya_archive_count_filter($links)
	{
		$links = str_replace("</a>&nbsp;(", '</a><span class="count">', $links);
		$links = str_replace(")", "</span>", $links);
		return $links;
	}
	add_filter("get_archives_link", "talampaya_archive_count_filter");
endif;
