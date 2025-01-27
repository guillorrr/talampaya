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

$filters = talampaya_directory_iterator(__DIR__ . "/inc/filters");
if (!empty($filters)) {
	foreach ($filters as $f) {
		require_once $f;
	}
}
