<?php

/**
 * Script para ejecutar ejemplos de ContentGenerator desde la línea de comandos
 *
 * Uso:
 * php run-example.php legal
 * php run-example.php project
 * php run-example.php all
 */

// Cargar WordPress
require_once dirname(__FILE__, 7) . "/wp-load.php";

// Cargar nuestros generadores de ejemplo
require_once dirname(__FILE__) . "/LegalPagesGenerator.php";
require_once dirname(__FILE__) . "/ProjectPostGenerator.php";
require_once dirname(__FILE__) . "/ContentGeneratorExample.php";

use App\Features\ContentGenerator\Examples\LegalPagesGenerator;
use App\Features\ContentGenerator\Examples\ProjectPostGenerator;
use App\Features\ContentGenerator\Examples\ContentGeneratorExample;

// Comprobar que tenemos un argumento
if ($argc < 2) {
	echo "Uso: php run-example.php [legal|project|all] [--force]\n";
	exit(1);
}

// Obtener el tipo de generador a ejecutar
$type = $argv[1];

// Comprobar si debemos forzar la regeneración
$force = in_array("--force", $argv);

// Ejecutar el generador adecuado
switch ($type) {
	case "legal":
		echo "Generando páginas legales...\n";
		LegalPagesGenerator::generate($force);
		break;

	case "project":
		echo "Generando proyectos...\n";
		ProjectPostGenerator::generate($force);
		break;

	case "manual-project":
		echo "Generando proyecto manual...\n";
		ProjectPostGenerator::generateManualProject();
		break;

	case "all":
		echo "Generando todo el contenido...\n";
		ContentGeneratorExample::generateAll($force);
		break;

	default:
		echo "Tipo de generador no válido. Opciones disponibles: legal, project, manual-project, all\n";
		exit(1);
}

echo "¡Generación de contenido completada!\n";

// Para ver el resultado
echo "\nPuedes verificar el contenido generado en el panel de administración de WordPress:\n";
echo "- Páginas legales: " . admin_url("edit.php?post_type=page") . "\n";
echo "- Proyectos: " . admin_url("edit.php?post_type=project_post") . "\n";
