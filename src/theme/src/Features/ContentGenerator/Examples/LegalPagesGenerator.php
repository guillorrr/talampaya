<?php

namespace App\Features\ContentGenerator\Examples;

use App\Features\ContentGenerator\ContentGeneratorFactory;
use App\Features\ContentGenerator\ContentGeneratorManager;

/**
 * Ejemplo de generador de páginas legales desde archivos HTML
 */
class LegalPagesGenerator
{
	/**
	 * Inicializa y registra el generador de páginas legales
	 *
	 * @return void
	 */
	public static function register(): void
	{
		// Ruta base para los archivos HTML legales
		$html_base_path = "/src/Features/DefaultContent/html-content/";

		// Definir las páginas legales que queremos crear
		$legal_pages = [
			"aviso-legal" => [
				"title" => "Aviso Legal",
				"update" => true, // Actualizar si ya existe
				"meta" => [
					"_legal_type" => "terms",
					"_show_in_footer" => true,
				],
			],
			"politica-de-privacidad" => [
				"title" => "Política de Privacidad",
				"update" => true,
				"meta" => [
					"_legal_type" => "privacy",
					"_show_in_footer" => true,
				],
			],
			"cookies" => [
				"title" => "Política de Cookies",
				"file" => "politica-de-cookies.html", // Archivo personalizado
				"update" => true,
				"meta" => [
					"_legal_type" => "cookies",
					"_show_in_footer" => true,
				],
			],
		];

		// Crear el generador de contenido HTML con la factory
		$legalGenerator = ContentGeneratorFactory::createHtmlContentGenerator(
			"legal_pages_generated", // Clave única para seguimiento
			"page", // Tipo de post (páginas)
			$legal_pages, // Datos de las páginas
			$html_base_path // Ruta base para los archivos HTML
		);

		// Opcionalmente, configurar opciones adicionales del generador
		// $legalGenerator->setForceUpdate(true); // Forzar actualización siempre

		// Instanciar o recuperar el manager
		$manager = new ContentGeneratorManager();

		// Registrar el generador con prioridad 10
		$manager->register($legalGenerator, 10);
	}

	/**
	 * Ejecuta la generación de páginas legales
	 *
	 * @param bool $force Forzar regeneración aunque ya existan
	 * @return void
	 */
	public static function generate(bool $force = false): void
	{
		// Registrar el generador
		self::register();

		// Instanciar el manager
		$manager = new ContentGeneratorManager();

		// Generar el contenido
		if ($force) {
			// Forzar regeneración de todo el contenido
			$manager->forceRegenerateAll();
		} else {
			// Solo generar contenido que no existe
			$manager->generateAllContent();
		}
	}
}

// Ejemplo de uso:
// LegalPagesGenerator::register(); // Solo registra el generador para activación del tema
// LegalPagesGenerator::generate(); // Registra y ejecuta la generación
