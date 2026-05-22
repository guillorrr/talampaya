<?php

declare(strict_types=1);

namespace App\Features\Seo;

/**
 * Agrega los sitemaps de los subsitios al sitemap index del sitio principal
 *
 * En un WordPress Multisite con subdirectorios, Yoast SEO genera un sitemap
 * independiente para cada subsitio. Esta clase modifica el sitemap index
 * del sitio principal para incluir referencias a los sitemaps de todos
 * los subsitios, facilitando el rastreo por parte de los motores de búsqueda.
 */
class MultisiteSitemapIndex
{
	public function __construct()
	{
		// Solo ejecutar si es multisite
		if (!is_multisite()) {
			return;
		}

		// Agregar sitemaps de subsitios al índice principal
		add_filter("wpseo_sitemap_index", [$this, "addSubsiteSitemaps"]);
	}

	/**
	 * Agrega los sitemaps de los subsitios al sitemap index del sitio principal
	 *
	 * @param string $sitemap_index El contenido XML del sitemap index
	 * @return string El sitemap index modificado con las referencias a los subsitios
	 */
	public function addSubsiteSitemaps(string $sitemap_index): string
	{
		// Solo ejecutar en el sitio principal
		if (!is_main_site()) {
			return $sitemap_index;
		}

		$subsites = get_sites([
			//"public" => 1,
			"archived" => 0,
			"deleted" => 0,
		]);

		$external_sitemaps = "";
		$current_time = date("c");

		foreach ($subsites as $subsite) {
			if ((int) $subsite->blog_id === get_main_site_id()) {
				continue;
			}

			$subsite_url = get_site_url((int) $subsite->blog_id);
			$sitemap_url = trailingslashit($subsite_url) . "sitemap_index.xml";

			$external_sitemaps .= sprintf(
				"<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>" . PHP_EOL,
				esc_url($sitemap_url),
				$current_time
			);
		}

		if (str_contains($sitemap_index, "</sitemapindex>")) {
			return str_replace(
				"</sitemapindex>",
				$external_sitemaps . "</sitemapindex>",
				$sitemap_index
			);
		}

		return $sitemap_index . $external_sitemaps;
	}
}
