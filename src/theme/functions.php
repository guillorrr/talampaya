<?php

use Talampaya\Core\TalampayaBase;
use Talampaya\Core\TalampayaSetup;
use Talampaya\Core\TalampayaFactory;
use Talampaya\Core\TalampayaTimber;
use Talampaya\Utils\FileUtils;

// Load Composer dependencies.
require_once __DIR__ . "/../../vendor/autoload.php";

require_once __DIR__ . "/plugins/plugins.php";

Timber\Timber::init();
Timber::$dirname = ["templates", "views"];

if (class_exists("TalampayaBase")) {
	new TalampayaBase();
}
if (class_exists("TalampayaSetup")) {
	new TalampayaSetup();
}
$factory = FileUtils::talampaya_directory_iterator_group_by_folder(__DIR__ . "/register");
$factory["nav_menus"] = require_once __DIR__ . "/register/menus.php";
if (class_exists("TalampayaFactory")) {
	new TalampayaFactory($factory);
}
if (class_exists("TalampayaTimber")) {
	new TalampayaTimber();
}

if (class_exists("ACF")) {
	require_once __DIR__ . "/features/Acf/acf.php";
}

$directories = [
	__DIR__ . "/hooks/filters",
	//    __DIR__ . "/app/models",
	//    __DIR__ . "/app/services",
	//    __DIR__ . "/app/defaults",
	__DIR__ . "/app/controllers",
	//    __DIR__ . "/app/endpoints",
	__DIR__ . "/app/helpers",
	__DIR__ . "utils",
	//    __DIR__ . "/app/cron",
];

foreach ($directories as $dir) {
	$files = FileUtils::talampaya_directory_iterator($dir);
	if (!empty($files)) {
		foreach ($files as $file) {
			require_once $file;
		}
	}
}
