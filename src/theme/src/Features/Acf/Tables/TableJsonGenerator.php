<?php

namespace App\Features\Acf\Tables;

/**
 * Generador de archivos JSON para tablas personalizadas de ACF
 */
class TableJsonGenerator
{
	/**
	 * Crea archivos JSON de tablas para cada grupo de campos ACF
	 *
	 * @return array Resultados de la operación
	 */
	public static function createTablesJson(): array
	{
		if (!function_exists("acf_get_field_groups") || !function_exists("acf_get_fields")) {
			return [
				"success" => [],
				"errors" => ["ACF plugin is not active"],
			];
		}

		// Verificar si el plugin ACF Custom Tables está activo
		if (
			!class_exists("ACF_Custom_Database_Tables") &&
			!class_exists("ACF_Custom_Database_Tables\Main")
		) {
			return [
				"success" => [],
				"errors" => ["ACF Custom Database Tables plugin is not active"],
			];
		}

		$results = ["success" => [], "errors" => []];

		$json_path = get_stylesheet_directory() . "/acf-json/database-tables";

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
			$post_type = null;

			foreach ($field_group["location"] as $location) {
				foreach ($location as $rule) {
					if ($rule["param"] === "post_type") {
						$post_type = $rule["value"];
						break 2;
					}
				}
			}

			if (!$post_type) {
				continue;
			}

			$acfcdt_table_name = isset($field_group["acfcdt_table_name"])
				? $field_group["acfcdt_table_name"]
				: null;
			$acfcdt_table_definition_file_name = isset(
				$field_group["acfcdt_table_definition_file_name"]
			)
				? $field_group["acfcdt_table_definition_file_name"]
				: null;

			if (empty($acfcdt_table_name) || empty($acfcdt_table_definition_file_name)) {
				$results[
					"errors"
				][] = "Missing table name or definition for field group: {$field_group["title"]}.";
				continue;
			}

			$table_structure = [
				"name" => $acfcdt_table_name,
				"relationship" => [
					"type" => "post",
					"post_type" => $post_type,
				],
				"primary_key" => ["id"],
				"keys" => [
					[
						"name" => "post_id",
						"columns" => ["post_id"],
						"unique" => true,
					],
				],
				"columns" => [
					[
						"name" => "id",
						"format" => "%d",
						"type" => "bigint(20)",
						"null" => false,
						"auto_increment" => true,
						"unsigned" => true,
					],
					[
						"name" => "post_id",
						"format" => "%d",
						"type" => "bigint(20)",
					],
				],
				"hash" => md5($acfcdt_table_name . time()),
				"modified" => time(),
			];

			$fields = acf_get_fields($field_group["key"]);

			foreach ($fields as $field) {
				$type = "longtext";
				$format = "%s";

				switch ($field["type"]) {
					case "text":
					case "email":
					case "url":
						$type = "varchar(255)";
						$format = "%s";
						break;
					case "textarea":
					case "wysiwyg":
					case "number":
					case "range":
						$type = "longtext";
						break;
					case "date_picker":
					case "date_time_picker":
						$type = "datetime";
						$format = "%s";
						break;
					case "true_false":
						$type = "tinyint(1)";
						$format = "%d";
						break;
				}

				$column = [
					"name" => $field["name"],
					"type" => $type,
					"map" => [
						"type" => "acf_field_name",
						"identifier" => $field["name"],
						"key" => $field["key"],
					],
				];

				if ($type !== "longtext") {
					$column["format"] = $format;
				}

				$table_structure["columns"][] = $column;
			}

			$json_data = json_encode(
				$table_structure,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);
			$file_name = $json_path . "/" . $acfcdt_table_definition_file_name . ".json";

			if (file_put_contents($file_name, $json_data) === false) {
				$results["errors"][] = "Failed to save JSON file: {$file_name}";
			} else {
				$results["success"][] = "Saved JSON for field group: {$field_group["title"]}.";
			}
		}

		return $results;
	}
}
