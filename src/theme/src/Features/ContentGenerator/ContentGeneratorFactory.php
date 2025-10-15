<?php

namespace App\Features\ContentGenerator;

use App\Features\ContentGenerator\Processors\BlocksContentProcessor;
use App\Features\ContentGenerator\Processors\HtmlContentProcessor;

/**
 * Fábrica para crear instancias de generadores de contenido
 */
class ContentGeneratorFactory
{
	/**
	 * Crea un generador de contenido genérico
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param string $post_type Tipo de post
	 * @param array $content_data Datos del contenido
	 * @param array $content_processors Procesadores de contenido opcionales
	 * @return ContentTypeGenerator
	 */
	public static function createContentGenerator(
		string $option_key,
		string $post_type,
		array $content_data,
		array $content_processors = []
	): ContentTypeGenerator {
		return new ContentTypeGenerator(
			$option_key,
			$post_type,
			$content_data,
			$content_processors
		);
	}

	/**
	 * Crea un generador para contenido HTML
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param string $post_type Tipo de post
	 * @param array $content_data Datos del contenido
	 * @param string $base_path Ruta base para archivos HTML
	 * @return ContentTypeGenerator
	 */
	public static function createHtmlContentGenerator(
		string $option_key,
		string $post_type,
		array $content_data,
		string $base_path = "/src/Features/DefaultContent/html-content/"
	): ContentTypeGenerator {
		// Preparar datos de contenido con rutas HTML
		$prepared_data = [];
		foreach ($content_data as $slug => $data) {
			if (is_array($data)) {
				// Si ya es un array con configuración, añadir ruta HTML
				$file_path = $base_path . ($data["file"] ?? $slug . ".html");
				$prepared_data[$slug] = array_merge($data, [
					"content" => $file_path,
					"content_type" => "html",
				]);
			} else {
				// Si es simplemente un slug, configurar con datos básicos
				$file_path = $base_path . $slug . ".html";
				$prepared_data[$slug] = [
					"title" => ucfirst(str_replace("-", " ", $slug)),
					"content" => $file_path,
					"content_type" => "html",
				];
			}
		}

		// Crear generador con procesador HTML
		return self::createContentGenerator($option_key, $post_type, $prepared_data, [
			"html" => [HtmlContentProcessor::class, "process"],
		]);
	}

	/**
	 * Crea un generador de contenido para bloques
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param string $post_type Tipo de post
	 * @param array $content_data Datos de contenido con bloques
	 * @return ContentTypeGenerator
	 */
	public static function createBlocksContentGenerator(
		string $option_key,
		string $post_type,
		array $content_data
	): ContentTypeGenerator {
		// Preparar datos para formato de bloques
		$prepared_data = [];
		foreach ($content_data as $slug => $data) {
			if (isset($data["blocks"])) {
				$prepared_data[$slug] = array_merge($data, [
					"content" => $data["blocks"],
					"content_type" => "blocks",
				]);
			} else {
				$prepared_data[$slug] = $data;
			}
		}

		// Crear generador con procesador de bloques
		return self::createContentGenerator($option_key, $post_type, $prepared_data, [
			"blocks" => [BlocksContentProcessor::class, "process"],
		]);
	}
}
