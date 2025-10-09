<?php

namespace App;

use Timber\Timber;
use App\Core\TalampayaBase;
use App\Core\TalampayaSetup;
use App\Utils\FileUtils;
use App\Register\RegisterManager;

// Load Composer dependencies.
require_once __DIR__ . "/../../vendor/autoload.php";

Timber::init();

Timber::$dirname = [get_template_directory() . "/views"];

if (class_exists("App\\Core\\TalampayaBase")) {
	new TalampayaBase();
}
if (class_exists("App\\Core\\TalampayaSetup")) {
	new TalampayaSetup();
}

require_once __DIR__ . "/src/Plugins/plugins.php";

if (class_exists("App\\TalampayaStarter")) {
	new TalampayaStarter();

	error_log("Timber Initialized with Paths: " . print_r(Timber::$dirname, true));

	add_action(
		"init",
		function () {
			error_log("Timber Namespaces Check: " . print_r(Timber::$dirname, true));
		},
		999
	);
}

RegisterManager::registerAll();

if (class_exists("ACF")) {
	require_once __DIR__ . "/src/Features/Acf/acf.php";
}

$directories = [__DIR__ . "/hooks/Filters", __DIR__ . "/src/Core/Config"];

foreach ($directories as $dir) {
	$files = FileUtils::talampaya_directory_iterator($dir);
	if (!empty($files)) {
		foreach ($files as $file) {
			require_once $file;
		}
	}
}
