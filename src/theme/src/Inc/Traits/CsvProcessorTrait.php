<?php

namespace App\Inc\Traits;

trait CsvProcessorTrait
{
	/**
	 * Procesa un archivo CSV y aplica un procesador para cada fila.
	 *
	 * @param string $file_path Ruta al archivo CSV
	 * @param callable $row_processor Función para procesar cada fila
	 * @param array|null $expected_headers Cabeceras esperadas en el CSV
	 * @param int $start_line Línea desde la que empezar a procesar
	 * @param int $line_count Número máximo de líneas a procesar
	 * @return array Resultado del procesamiento
	 */
	public function processCsv(
		$file_path,
		callable $row_processor,
		?array $expected_headers = null,
		int $start_line = 1,
		int $line_count = PHP_INT_MAX
	): array {
		$class_name = get_class($this);

		$default_response = [
			"success" => 0,
			"errors" => 0,
			"message" => "Error al procesar el CSV",
			"updated_items" => [],
		];

		if (!file_exists($file_path)) {
			$error_message = "El archivo CSV no existe: " . $file_path;
			error_log("$class_name::processCsv: {$error_message}");
			return array_merge($default_response, ["message" => $error_message]);
		}

		$success_count = 0;
		$error_count = 0;
		$updated_items = [];

		if (($handle = fopen($file_path, "r")) !== false) {
			$headers = fgetcsv($handle, 20000, ",");
			if ($headers === false) {
				fclose($handle);
				$error_message = "No se pudieron leer los encabezados del CSV";
				error_log("$class_name::processCsv: {$error_message}");
				return array_merge($default_response, ["message" => $error_message]);
			}

			error_log("$class_name::processCsv: Encabezados=" . print_r($headers, true));

			if ($expected_headers && $headers !== $expected_headers) {
				fclose($handle);
				$error_message =
					"El CSV no tiene los encabezados esperados: " . implode(",", $expected_headers);
				error_log("$class_name::processCsv: {$error_message}");
				return array_merge($default_response, ["message" => $error_message]);
			}

			$current_line = 1;
			$processed_lines = 0;
			$processed_ids = [];

			while (($data = fgetcsv($handle, 20000, ",")) !== false) {
				if ($current_line < $start_line) {
					$current_line++;
					continue;
				}

				if ($processed_lines >= $line_count) {
					break;
				}

				$data = array_pad($data, count($headers), "");
				$row = array_combine($headers, $data);
				if ($row === false) {
					error_log(
						"$class_name::processCsv: No se pudo combinar la fila en línea=" .
							$current_line
					);
					$error_count++;
					$current_line++;
					continue;
				}

				// Limpiar valores "NULL" en la fila
				$row = $this->cleanNullValues($row);

				error_log("$class_name::processCsv: Procesando fila=" . print_r($row, true));

				$result = call_user_func($row_processor, $row);
				if ($result) {
					$success_count++;
					// Procesar el resultado para guardar información sobre el elemento actualizado
					$this->processResult($result, $row, $processed_ids, $updated_items);
				} else {
					$error_count++;
				}

				$current_line++;
				$processed_lines++;
			}

			fclose($handle);

			$message = sprintf(
				__("Procesadas %d líneas: %d correctas, %d errores.", "talampaya"),
				$processed_lines,
				$success_count,
				$error_count
			);
			error_log("$class_name::processCsv: " . $message);

			return [
				"success" => $success_count,
				"errors" => $error_count,
				"message" => $message,
				"updated_items" => $updated_items,
			];
		} else {
			$error_message = "No se pudo abrir el archivo CSV: " . $file_path;
			error_log("$class_name::processCsv: {$error_message}");
			return array_merge($default_response, ["message" => $error_message]);
		}
	}

	/**
	 * Procesa el resultado del procesador de filas y guarda información sobre el elemento actualizado
	 *
	 * @param mixed $result Resultado del procesamiento de la fila
	 * @param array $row Datos de la fila procesada
	 * @param array $processed_ids Array para llevar registro de IDs procesados
	 * @param array $updated_items Array para guardar elementos actualizados
	 */
	protected function processResult(
		$result,
		array $row,
		array &$processed_ids,
		array &$updated_items
	): void {
		if ($result instanceof WP_Post || method_exists($result, "ID")) {
			$item_id = $this->getItemId($row, $result);
			if (!isset($processed_ids[$item_id])) {
				$processed_ids[$item_id] = true;
				$updated_items[] = [
					"post_id" => $result->ID,
					"title" => method_exists($result, "title")
						? $result->title()
						: $result->post_title,
					"permalink" => get_permalink($result->ID),
				];
			}
		}
	}

	protected function getItemId(array $row, $result): string
	{
		return $row["id"] ?? ($row["nid"] ?? $result->ID);
	}

	/**
	 * Limpia los valores "NULL" de un array, convirtiéndolos a cadenas vacías
	 *
	 * @param array $data Array con datos que pueden contener el texto "NULL"
	 * @return array Array con datos limpios
	 */
	protected function cleanNullValues(array $data): array
	{
		return array_map(function ($value) {
			return $value === "NULL" ? "" : $value;
		}, $data);
	}
}
