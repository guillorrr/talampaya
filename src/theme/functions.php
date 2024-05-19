<?php
// Load Composer dependencies.
require_once __DIR__ . "/../../vendor/autoload.php";

require_once __DIR__ . "/core/TalampayaBase.php";
require_once __DIR__ . "/core/TalampayaSetup.php";
require_once __DIR__ . "/core/TalampayaFactory.php";
require_once __DIR__ . "/core/TalampayaTimber.php";

require_once __DIR__ . "/inc/utils/helpers.php";
require_once __DIR__ . "/inc/plugins.php";

Timber\Timber::init();
Timber::$dirname = ["templates", "views"];

$paths = require_once __DIR__ . "/inc/utils/paths.php";
$theme = wp_get_theme();
$theme_version = $theme->get("Version");
$theme_text_domain = $theme->get("Text Domain");

if (class_exists("TalampayaBase")) {
	new TalampayaBase();
}
if (class_exists("TalampayaSetup")) {
	new TalampayaSetup();
}
$factory = talampaya_directory_iterator_group_by_folder(__DIR__ . "/inc/register");
$factory["nav_menus"] = require_once __DIR__ . "/inc/register/menus.php";
if (class_exists("TalampayaFactory")) {
	new TalampayaFactory($factory);
}
if (class_exists("TalampayaTimber")) {
	new TalampayaTimber();
}

if (class_exists("ACF")) {
	require_once __DIR__ . "/inc/acf.php";
}

$filters = talampaya_directory_iterator(__DIR__ . "/inc/filters");
foreach ($filters as $f) {
	require_once $f;
}
