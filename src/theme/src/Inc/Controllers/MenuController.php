<?php

namespace App\Inc\Controllers;

/**
 * Controlador para menús de WordPress
 * Genera estructuras de datos compatibles con las vistas Twig
 */
class MenuController
{
	/**
	 * Obtiene un menú por su ubicación y lo estructura para Twig
	 *
	 * @param string $location Ubicación del menú (ej: 'main', 'footer')
	 * @return array|null Estructura del menú o null si no existe
	 */
	public static function getMenuByLocation(string $location): ?array
	{
		$locations = get_nav_menu_locations();

		if (!isset($locations[$location])) {
			return null;
		}

		$menu_id = $locations[$location];
		$menu_items = wp_get_nav_menu_items($menu_id);

		if (!$menu_items) {
			return null;
		}

		return self::buildMenuTree($menu_items);
	}

	/**
	 * Construye un árbol jerárquico de items de menú
	 *
	 * @param array $items Items planos del menú de WordPress
	 * @param int $parent_id ID del padre (0 para raíz)
	 * @return array Estructura jerárquica
	 */
	protected static function buildMenuTree(array $items, int $parent_id = 0): array
	{
		$tree = [];

		foreach ($items as $item) {
			if ((int) $item->menu_item_parent === $parent_id) {
				$node = self::formatMenuItem($item);

				// Buscar hijos recursivamente
				$children = self::buildMenuTree($items, (int) $item->ID);

				if (!empty($children)) {
					$node["children"] = $children;
				}

				$tree[] = $node;
			}
		}

		return $tree;
	}

	/**
	 * Formatea un item de menú para la vista
	 *
	 * @param object $item Item de WordPress
	 * @return array Item formateado
	 */
	protected static function formatMenuItem(object $item): array
	{
		return [
			"id" => $item->ID,
			"label" => $item->title,
			"url" => $item->url,
			"description" => $item->description ?: "",
			"classes" => implode(" ", $item->classes),
			"target" => $item->target ?: "_self",
			"object_id" => $item->object_id,
			"object_type" => $item->object,
			"type" => $item->type,
		];
	}

	/**
	 * Obtiene un menú formateado (alias de getMenuByLocation)
	 *
	 * @param string $location Ubicación del menú
	 * @return array Menú formateado
	 */
	public static function getFormattedMenu(string $location): array
	{
		return self::getMenuByLocation($location) ?? [];
	}

	/**
	 * Convierte menú jerárquico a estructura compatible con Pattern Lab
	 * Esta función transforma el menú de WordPress al formato que espera menu.twig
	 *
	 * @param string $location Ubicación del menú
	 * @return array Estructura compatible con Pattern Lab
	 */
	public static function getPatternLabMenu(string $location = "main"): array
	{
		$menu = self::getMenuByLocation($location);

		if (!$menu) {
			return [];
		}

		return array_map(function ($item) {
			return self::convertToPatternLabFormat($item);
		}, $menu);
	}

	/**
	 * Convierte un item de menú al formato de Pattern Lab
	 *
	 * @param array $item Item del menú
	 * @return array Item en formato Pattern Lab
	 */
	protected static function convertToPatternLabFormat(array $item): array
	{
		$formatted = [
			"id" => $item["id"],
			"label" => $item["label"],
			"url" => $item["url"] !== "#" ? $item["url"] : null,
		];

		// Si tiene hijos, convertirlos recursivamente
		if (isset($item["children"]) && !empty($item["children"])) {
			$formatted["children"] = array_map(function ($child) {
				return self::convertChildToCategory($child);
			}, $item["children"]);
		}

		return $formatted;
	}

	/**
	 * Convierte un hijo a formato de categoría/grupo
	 *
	 * @param array $child Item hijo (nivel 2)
	 * @return array Categoría formateada
	 */
	protected static function convertChildToCategory(array $child): array
	{
		$category = [
			"id" => $child["id"],
			"label" => $child["label"],
			"description" => $child["description"],
			"url" => $child["url"] !== "#" ? $child["url"] : null,
		];

		// Si tiene hijos (nivel 3), mantenerlos como "children"
		if (isset($child["children"]) && !empty($child["children"])) {
			$category["children"] = array_map(function ($item) {
				$level3 = [
					"id" => $item["id"],
					"label" => $item["label"],
					"url" => $item["url"] !== "#" ? $item["url"] : null,
					"description" => $item["description"] ?? "",
					"image" => self::getItemImage($item),
				];

				// Si nivel 3 tiene hijos (nivel 4), convertirlos a "items"
				if (isset($item["children"]) && !empty($item["children"])) {
					$level3["items"] = array_map(function ($subitem) {
						return [
							"id" => $subitem["id"],
							"label" => $subitem["label"],
							"url" => $subitem["url"],
							"description" => $subitem["description"] ?? "",
						];
					}, $item["children"]);
				}

				return $level3;
			}, $child["children"]);
		}

		return $category;
	}

	/**
	 * Obtiene la imagen asociada a un item (si existe)
	 * Puede extenderse para usar campos ACF
	 *
	 * @param array $item Item del menú
	 * @return array|null Imagen o null
	 */
	protected static function getItemImage(array $item): ?array
	{
		// Por ahora retorna null, pero puede extenderse con ACF
		// $image_id = get_field('menu_item_image', $item['id']);
		// if ($image_id) {
		//     return [
		//         'src' => wp_get_attachment_image_url($image_id, 'large'),
		//         'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
		//     ];
		// }

		return null;
	}
}
