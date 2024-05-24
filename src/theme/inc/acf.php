<?php

/**
 * Register ACF blocks.
 */
function register_acf_blocks(): void
{
	foreach ($blocks = new DirectoryIterator(get_template_directory() . "/blocks") as $item) {
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
foreach ($directories = new DirectoryIterator(get_template_directory() . "/blocks") as $directory) {
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
