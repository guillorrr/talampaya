<?php

// Load Composer dependencies.
require_once __DIR__ . "/../../vendor/autoload.php";

require_once __DIR__ . "/core/TalampayaBase.php";
require_once __DIR__ . "/core/TalampayaSetup.php";
require_once __DIR__ . "/core/TalampayaFactory.php";
require_once __DIR__ . "/core/TalampayaTimber.php";

require_once __DIR__ . "/inc/utils/helpers.php";
require_once __DIR__ . "/inc/plugins.php";
require_once __DIR__ . "/inc/cli.php";
require_once __DIR__ . "/inc/imports/import.php";

Timber\Timber::init();
Timber::$dirname = ["templates", "views"];

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

$directories = [
	__DIR__ . "/inc/filters",
	//    __DIR__ . "/inc/models",
	//    __DIR__ . "/inc/services",
	//    __DIR__ . "/inc/defaults",
	__DIR__ . "/inc/controllers",
	//    __DIR__ . "/inc/endpoints",
	//    __DIR__ . "/inc/helpers",
	//    __DIR__ . "/inc/cron",
];

foreach ($directories as $dir) {
	$files = talampaya_directory_iterator($dir);
	if (!empty($files)) {
		foreach ($files as $file) {
			require_once $file;
		}
	}
}
