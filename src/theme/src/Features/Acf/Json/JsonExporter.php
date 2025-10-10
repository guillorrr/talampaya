<?php

namespace App\Features\Acf\Json;

/**
 * Clase para exportar campos ACF a archivos JSON
 */
class JsonExporter
{
	/**
	 * Guarda los campos ACF en archivos JSON
	 *
	 * @return array Resultados de la operación
	 */
	public static function saveFieldsToJson(): array
	{
		if (!function_exists("acf_get_field_groups") || !function_exists("acf_get_fields")) {
			return [
				"success" => [],
				"errors" => ["ACF plugin is not active"],
			];
		}

		$results = ["success" => [], "errors" => []];

		$json_path = get_stylesheet_directory() . "/acf-json";

		if (!file_exists($json_path)) {
			if (!mkdir($json_path, 0755, true)) {
				$results["errors"][] = "Failed to create directory: {$json_path}";
				return $results;
			}
		}

		$field_groups = acf_get_field_groups();

		if (empty($field_groups)) {
			$results["errors"][] = "No field groups found.";
			return $results;
		}

		foreach ($field_groups as $field_group) {
			$fields = acf_get_fields($field_group["key"]);

			if (!$fields) {
				$results["errors"][] = "No fields found for field group: {$field_group["title"]}.";
				continue;
			}

			$field_group_data = $field_group;
			$field_group_data["fields"] = $fields;

			$file_name = $json_path . "/" . $field_group["key"] . ".json";

			if (
				file_put_contents(
					$file_name,
					json_encode($field_group_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
				) === false
			) {
				$results["errors"][] = "Failed to save JSON file: {$file_name}";
			} else {
				$results["success"][] = "Saved JSON for field group: {$field_group["title"]}.";
			}
		}

		return $results;
	}
}
