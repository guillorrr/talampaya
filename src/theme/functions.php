<?php

namespace App;

use Timber\Timber;

// Load Composer dependencies.
require_once __DIR__ . "/vendor/autoload.php";

// Inicializar el framework
require_once __DIR__ . "/src/Core/bootstrap.php";

// Inicializar Timber
Timber::init();

// Inicializar la aplicación
new TalampayaStarter();
