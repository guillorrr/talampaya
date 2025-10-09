<?php

use Talampaya\Core\TalampayaBase;
use Talampaya\Core\TalampayaSetup;
use Talampaya\Core\TalampayaTimber;
use Talampaya\Utils\FileUtils;
use Talampaya\Register\RegisterManager;

// Load Composer dependencies.
require_once __DIR__ . "/../../vendor/autoload.php";

require_once __DIR__ . "/plugins/plugins.php";

Timber\Timber::init();

$theme_dir = get_template_directory();
Timber::$dirname = ["{$theme_dir}/templates", "{$theme_dir}/views"];

if (class_exists("Talampaya\\Core\\TalampayaBase")) {
	new TalampayaBase();
}
if (class_exists("Talampaya\\Core\\TalampayaSetup")) {
	new TalampayaSetup();
}

RegisterManager::registerAll();

if (class_exists("Talampaya\\Core\\TalampayaTimber")) {
	new TalampayaTimber();

	error_log("Timber Initialized with Paths: " . print_r(Timber::$dirname, true));

	add_action(
		"init",
		function () {
			error_log("Timber Namespaces Check: " . print_r(Timber::$dirname, true));
		},
		999
	);
}

if (class_exists("ACF")) {
	require_once __DIR__ . "/features/Acf/acf.php";
}

$directories = [
	__DIR__ . "/hooks/Filters",
	__DIR__ . "/core/Config",
	//    __DIR__ . "/app/models",
	//    __DIR__ . "/app/services",
	//    __DIR__ . "/app/defaults",
	//	__DIR__ . "/app/controllers",
	//    __DIR__ . "/app/endpoints",
	//	__DIR__ . "/app/helpers",
	//	__DIR__ . "/utils",
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
