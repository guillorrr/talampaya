<?php

namespace App\Features\Import;

use App\Inc\Models\ProjectPost;
use App\Inc\Services\ProjectImportService;

/**
 * Clase para gestionar la importación de proyectos desde CSV
 */
class ProjectImport
{
	/**
	 * Importa proyectos desde un archivo CSV
	 *
	 * @param string|null $file_path Ruta del archivo CSV, si es null se usa el archivo de muestra
	 * @param int $start_line Línea desde la que empezar la importación
	 * @param int $line_count Número máximo de líneas a importar
	 * @return void
	 */
	public function importFromCsv(
		string $file_path = null,
		int $start_line = 1,
		int $line_count = 100
	): void {
		if (is_null($file_path)) {
			$file_path = get_template_directory() . "/mockups/projects.csv";
		}

		$service = new ProjectImportService();
		$result = $service->processCsv(
			$file_path,
			function ($row) use ($service) {
				$data = $service->processData($row);
				return $service->createOrUpdate($data, ProjectPost::getInstance()) !== null;
			},
			null, // No validar encabezados para máxima compatibilidad
			$start_line,
			$line_count
		);

		echo $result["message"] . "\n";
	}
}
