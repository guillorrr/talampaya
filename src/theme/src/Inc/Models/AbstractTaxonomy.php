<?php

declare(strict_types=1);

namespace App\Inc\Models;

use Timber\Term;
use Timber\Timber;

abstract class AbstractTaxonomy extends Term
{
	/**
	 * Obtiene una instancia del término por ID o slug
	 *
	 * @param int|string $term Term ID o slug
	 * @param string $taxonomy Taxonomy slug (opcional)
	 * @return Term
	 */
	public static function get(int|string $term, string $taxonomy = ""): Term
	{
		return Timber::get_term($term);
	}

	/**
	 * Obtiene múltiples términos
	 *
	 * @param array $args Argumentos para get_terms()
	 * @return array<static>
	 */
	public static function getAll(array $args = []): array
	{
		$terms = Timber::get_terms($args);
		return is_array($terms) ? $terms : [];
	}
}
