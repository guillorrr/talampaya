<?php

//==============================================================================
// Disable store ACF Custom Tables in Core Meta
//==============================================================================

if (!function_exists("talampaya_acf_custom_tables_store_in_core_meta")) {
	function talampaya_acf_custom_tables_store_in_core_meta()
	{
		add_filter("acfcdt/settings/store_acf_values_in_core_meta", "__return_false");
		add_filter("acfcdt/settings/store_acf_keys_in_core_meta", "__return_false");
	}
}
