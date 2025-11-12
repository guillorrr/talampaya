<?php

namespace App\Register\PostType;

abstract class AbstractPostType
{
	protected string $slug;
	protected array $args = [];

	public function __construct()
	{
		$this->register();
	}

	abstract protected function configure(): array;

	public function register(): void
	{
		add_action("init", function () {
			$this->args = $this->configure();
			register_post_type($this->slug, $this->args);
		});
	}

	protected function getLabels(
		$singular = "Post",
		$plural = "Posts",
		?string $gender = null
	): array {
		$p_lower = strtolower($plural);
		$s_lower = strtolower($singular);

		$new = $gender === "f" ? "Nueva" : "Nuevo";
		$all = $gender === "f" ? "Todas las" : "Todos los";

		return [
			"name" => __($plural, "talampaya"),
			"singular_name" => __($singular, "talampaya"),
			"add_new_item" => __("$new $singular", "talampaya"),
			"edit_item" => __("Editar $singular", "talampaya"),
			"view_item" => __("Ver $singular", "talampaya"),
			"view_items" => __("Ver $plural", "talampaya"),
			"search_items" => __("Buscar $plural", "talampaya"),
			"not_found" => __("No $p_lower found", "talampaya"),
			"not_found_in_trash" => __("No $p_lower found in Trash", "talampaya"),
			"parent_item_colon" => __("Parent $singular", "talampaya"),
			"all_items" => __("$all $plural", "talampaya"),
			"archives" => __("$singular Archives", "talampaya"),
			"attributes" => __("$singular Attributes", "talampaya"),
			"insert_into_item" => __("Insert into $s_lower", "talampaya"),
			"uploaded_to_this_item" => __("Uploaded to this $s_lower", "talampaya"),
		];
	}
}
