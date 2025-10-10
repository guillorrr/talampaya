<?php

/**
 * Register ACF blocks.
 */
function register_acf_blocks(): void
{
	foreach ($blocks = new DirectoryIterator(ACF_BLOCKS_PATH) as $item) {
		if ($item->isDir() && !$item->isDot()) {
			$explode_directories = explode("/", $item->getPathname());
			$last_directory = end($explode_directories);
			$block_json = $item->getPathname() . "/" . $last_directory . "-block.json";
			if (file_exists($block_json)) {
				register_block_type($block_json);
			}
		}
	}
}
add_action("init", "register_acf_blocks");

/**
 * Register ACF blocks fields.
 */
foreach ($directories = new DirectoryIterator(ACF_BLOCKS_PATH) as $directory) {
	if ($directory->isDir() && !$directory->isDot()) {
		$explode_directories = explode("/", $directory->getPathname());
		$last_directory = end($explode_directories);
		if (!str_starts_with($last_directory, "_")) {
			foreach ($files = new DirectoryIterator($directory->getPathname()) as $file) {
				if ($file->isFile() && $file->getExtension() === "php") {
					require_once $file->getPathname();
				}
			}
		}
	}
}

/**
 * Register ACF Custom fields.
 */
foreach ($files = new DirectoryIterator(ACF_PATH) as $file) {
	if ($file->isFile() && $file->getExtension() === "php") {
		$filenameWithoutExtension = pathinfo($file->getFilename(), PATHINFO_FILENAME);
		if (!str_starts_with($filenameWithoutExtension, "_")) {
			require_once $file->getPathname();
		}
	}
}

/**
 * Render callback to prepare and display a registered block using Timber.
 */
function my_acf_block_render_callback(
	array $attributes,
	string $content = "",
	bool $is_preview = false,
	int $post_id = 0,
	WP_Block $wp_block = null
): void {
	// Create the slug of the block using the name property in the block.json.
	$slug = str_replace("acf/", "", $attributes["name"]);

	$context = Timber::context();

	// Store block attributes.
	$context["attributes"] = $attributes;

	// Store field values. These are the fields from your ACF field group for the block.
	$context["fields"] = get_fields();

	// Store whether the block is being rendered in the editor or on the frontend.
	$context["is_preview"] = $is_preview;

	// Render the block.
	Timber::render("blocks/" . $slug . "/" . $slug . "-block.twig", $context);
}

/**
 * Save ACF fields to JSON.
 */
function save_acf_fields_to_json()
{
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

/**
 * Create ACF table JSON for each field group.
 */
function create_acf_table_json_for_each_group()
{
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
