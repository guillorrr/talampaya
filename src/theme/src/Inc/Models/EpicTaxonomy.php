<?php

declare(strict_types=1);

namespace App\Inc\Models;

use Timber\Term;

class EpicTaxonomy extends AbstractTaxonomy
{
	/**
	 * Obtiene un Epic por ID o slug
	 *
	 * @param int|string $term
	 * @param string $taxonomy
	 * @return Term
	 */
	public static function get(int|string $term, string $taxonomy = ""): Term
	{
		return parent::get($term, "epic_taxonomy");
	}

	/**
	 * Obtiene todos los Epics
	 *
	 * @param array $args Argumentos adicionales para get_terms()
	 * @return array<static>
	 */
	public static function getAll(array $args = []): array
	{
		$args["taxonomy"] = "epic_taxonomy";
		return parent::getAll($args);
	}
}
