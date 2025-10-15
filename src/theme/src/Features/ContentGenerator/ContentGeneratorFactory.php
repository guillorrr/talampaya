<?php

namespace App\Features\ContentGenerator;

/**
 * Fábrica para crear instancias de generadores de contenido
 */
class ContentGeneratorFactory
{
	/**
	 * Crea un generador de contenido para páginas
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param array $pages_data Datos de contenido para páginas
	 * @return PageContentGenerator
	 */
	public static function createPageGenerator(
		string $option_key,
		array $pages_data
	): PageContentGenerator {
		return new PageContentGenerator($option_key, $pages_data);
	}

	/**
	 * Crea un generador de contenido para Custom Post Types
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param string $post_type El tipo de post personalizado
	 * @param array $posts_data Datos de los posts a crear
	 * @return CustomPostTypeGenerator
	 */
	public static function createCustomPostTypeGenerator(
		string $option_key,
		string $post_type,
		array $posts_data
	): CustomPostTypeGenerator {
		return new CustomPostTypeGenerator($option_key, $post_type, $posts_data);
	}

	/**
	 * Crea un generador de contenido legal
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param array $legal_slugs Slugs de las páginas legales
	 * @param string $content_base_path Ruta base a los archivos de contenido
	 * @param string $post_type Tipo de post
	 * @return LegalContentGenerator
	 */
	public static function createLegalContentGenerator(
		string $option_key,
		array $legal_slugs,
		string $content_base_path = "/src/Features/Content/legal-content/",
		string $post_type = "page"
	): LegalContentGenerator {
		return new LegalContentGenerator($option_key, $legal_slugs, $content_base_path, $post_type);
	}
}
