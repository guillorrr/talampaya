<?php

//==============================================================================
// Enable Repeater Field Support on ACF Custom Tables
//==============================================================================

if (!function_exists("talampaya_acf_custom_tables_support_repeater_field")) {
	function talampaya_acf_custom_tables_support_repeater_field()
	{
		add_filter("acfcdt/settings/enable_repeater_field_support", "__return_true");
	}
}
