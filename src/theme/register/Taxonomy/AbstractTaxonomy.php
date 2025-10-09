<?php

namespace Talampaya\Register\Taxonomy;

abstract class AbstractTaxonomy
{
	protected string $slug;
	protected array $object_types = [];
	protected array $args = [];

	public function __construct()
	{
		$this->register();
	}

	abstract protected function configure(): array;

	public function register(): void
	{
		add_action(
			"init",
			function () {
				$this->args = $this->configure();
				register_taxonomy($this->slug, $this->object_types, $this->args);
			},
			0
		);
	}

	protected function getLabels(string $singular, string $plural): array
	{
		return [
			"name" => $plural,
			"singular_name" => $singular,
			"menu_name" => $plural,
			"all_items" => sprintf(__("Todos los %s", "talampaya"), $plural),
			"parent_item" => sprintf(__("Padre %s", "talampaya"), $singular),
			"parent_item_colon" => sprintf(__("Padre %s:", "talampaya"), $singular),
			"new_item_name" => sprintf(__("Nuevo nombre de %s", "talampaya"), $singular),
			"add_new_item" => sprintf(__("Añadir nuevo %s", "talampaya"), $singular),
			"edit_item" => sprintf(__("Editar %s", "talampaya"), $singular),
			"update_item" => sprintf(__("Actualizar %s", "talampaya"), $singular),
			"view_item" => sprintf(__("Ver %s", "talampaya"), $singular),
			"separate_items_with_commas" => sprintf(
				__("Separar %s con comas", "talampaya"),
				$plural
			),
			"add_or_remove_items" => sprintf(__("Añadir o eliminar %s", "talampaya"), $plural),
			"choose_from_most_used" => sprintf(
				__("Elegir entre los %s más usados", "talampaya"),
				$plural
			),
			"popular_items" => sprintf(__("%s populares", "talampaya"), $plural),
			"search_items" => sprintf(__("Buscar %s", "talampaya"), $plural),
			"not_found" => sprintf(__("No se encontraron %s", "talampaya"), $plural),
			"no_terms" => sprintf(__("No hay %s", "talampaya"), $plural),
			"items_list" => sprintf(__("Lista de %s", "talampaya"), $plural),
			"items_list_navigation" => sprintf(
				__("Navegación de lista de %s", "talampaya"),
				$plural
			),
		];
	}
}
