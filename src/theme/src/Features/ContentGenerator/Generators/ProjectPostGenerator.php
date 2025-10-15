<?php

namespace App\Features\ContentGenerator\Generators;

use App\Features\ContentGenerator\ContentGeneratorFactory;
use App\Features\ContentGenerator\ContentGeneratorManager;
use App\Inc\Helpers\ContentTypeHelper;

/**
 * Ejemplo de generador de proyectos con bloques personalizados
 */
class ProjectPostGenerator
{
	/**
	 * Inicializa y registra el generador de proyectos
	 *
	 * @return void
	 */
	public static function register(): void
	{
		// Crear contenido de ejemplo usando bloques
		$projects_data = [
			"proyecto-principal" => [
				"title" => "Proyecto Principal",
				"status" => "publish",
				"blocks" => [
					// Bloque de encabezado y párrafo introductorio usando helper
					[
						"name" => "core/heading",
						"attributes" => [
							"content" => "Nuestro Proyecto Destacado",
							"level" => 2,
						],
					],
					[
						"name" => "core/paragraph",
						"attributes" => [
							"content" =>
								"Este es un proyecto destacado que muestra nuestras capacidades y tecnologías utilizadas.",
							"dropCap" => true,
						],
					],

					// Bloque personalizado ACF Example
					[
						"name" => "acf/example",
						"attributes" => [
							"data" => [
								"title" => "Características del Proyecto",
								"subtitle" => "Innovación y Tecnología",
								"intro" =>
									"Descubre lo que hace especial a este proyecto y por qué destaca entre los demás.",
								"background_color" => "#f5f5f5",
								"image" => [
									"id" => 123, // ID de la imagen (debe existir en la biblioteca de medios)
									"url" => "https://via.placeholder.com/800x600",
									"alt" => "Imagen del proyecto",
								],
								"list" => [
									["text" => "Diseño moderno y responsive"],
									["text" => "Optimización de rendimiento"],
									["text" => "Accesibilidad web integrada"],
									["text" => "Integración con APIs externas"],
								],
							],
							"mode" => "preview",
						],
					],

					// Bloque de separador
					[
						"name" => "core/separator",
					],

					// Bloque de galería (como ejemplo adicional)
					[
						"name" => "core/gallery",
						"attributes" => [
							"images" => [
								[
									"url" => "https://via.placeholder.com/800x600/FF5733/FFFFFF",
									"alt" => "Imagen 1 del proyecto",
								],
								[
									"url" => "https://via.placeholder.com/800x600/33FF57/FFFFFF",
									"alt" => "Imagen 2 del proyecto",
								],
								[
									"url" => "https://via.placeholder.com/800x600/3357FF/FFFFFF",
									"alt" => "Imagen 3 del proyecto",
								],
							],
							"columns" => 3,
						],
					],
				],
				"meta" => [
					"project_year" => "2023",
					"project_client" => "Cliente Ejemplo",
					"project_featured" => true,
				],
				"taxonomies" => [
					"project_taxonomy" => ["destacado", "tecnologia"],
				],
			],

			// Otro proyecto de ejemplo más simple
			"proyecto-secundario" => [
				"title" => "Proyecto Secundario",
				"status" => "publish",
				"blocks" => [
					[
						"name" => "core/heading",
						"attributes" => [
							"content" => "Proyecto Secundario",
							"level" => 2,
						],
					],
					[
						"name" => "core/paragraph",
						"attributes" => [
							"content" =>
								"Descripción del proyecto secundario con sus características principales.",
						],
					],
					[
						"name" => "acf/example",
						"attributes" => [
							"data" => [
								"title" => "Detalles del Proyecto",
								"subtitle" => "Características Principales",
								"intro" =>
									"Este proyecto muestra nuestra capacidad para desarrollar soluciones eficientes.",
								"background_color" => "#e0f7fa",
								"image" => [
									"id" => 124,
									"url" => "https://via.placeholder.com/800x600/CCCCCC/000000",
									"alt" => "Imagen del proyecto secundario",
								],
								"list" => [
									["text" => "Interfaz intuitiva"],
									["text" => "Tiempos de carga optimizados"],
									["text" => "Compatibilidad multiplataforma"],
								],
							],
							"mode" => "preview",
						],
					],
				],
				"meta" => [
					"project_year" => "2022",
					"project_client" => "Cliente B",
					"project_featured" => false,
				],
				"taxonomies" => [
					"project_taxonomy" => ["web", "diseño"],
				],
			],
		];

		// Crear el generador de contenido de bloques con la factory
		$projectsGenerator = ContentGeneratorFactory::createBlocksContentGenerator(
			"projects_content_generated", // Clave única para seguimiento
			"project_post", // Tipo de post personalizado
			$projects_data // Datos de los proyectos con bloques
		);

		// Instanciar o recuperar el manager
		$manager = new ContentGeneratorManager();

		// Registrar el generador con prioridad 20
		$manager->register($projectsGenerator, 20);
	}

	/**
	 * Ejecuta la generación de proyectos
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

	/**
	 * Genera un proyecto individual usando el ContentTypeHelper para mayor flexibilidad
	 * Esta es una forma alternativa de generar contenido usando el helper
	 *
	 * @return void
	 */
	public static function generateManualProject(): void
	{
		// Crear contenido usando el helper
		$blocks = [];

		// Añadir encabezado principal
		$blocks[] = ContentTypeHelper::createHeading("Proyecto Creado Manualmente", 1);

		// Añadir párrafo introductorio
		$blocks[] = ContentTypeHelper::createParagraph(
			"Este proyecto ha sido creado manualmente utilizando el ContentTypeHelper para demostrar una forma alternativa de generar contenido estructurado."
		);

		// Añadir lista de características
		$blocks[] = ContentTypeHelper::createList([
			"Característica 1 del proyecto",
			"Característica 2 con detalles importantes",
			"Característica 3 con especificaciones técnicas",
		]);

		// Añadir bloque personalizado de ejemplo
		$example_block = ContentTypeHelper::createCustomBlock("acf/example", [
			"data" => [
				"title" => "Sección Destacada",
				"subtitle" => "Creada con Helper",
				"intro" => "Esta sección utiliza el bloque personalizado Example.",
				"background_color" => "#f0f8ff",
				"list" => [
					["text" => "Elemento 1 de la lista"],
					["text" => "Elemento 2 de la lista"],
					["text" => "Elemento 3 de la lista"],
				],
			],
		]);
		$blocks[] = $example_block;

		// Combinar todos los bloques
		$content = ContentTypeHelper::combineBlocks($blocks);

		// Preparar datos para un único post
		$project_data = [
			"manual-project" => [
				"title" => "Proyecto Creado con Helper",
				"content" => $content,
				"content_type" => "raw", // Ya está en formato de bloques
				"status" => "publish",
				"meta" => [
					"project_year" => "2024",
					"project_client" => "Cliente Manual",
					"project_featured" => true,
				],
				"taxonomies" => [
					"project_taxonomy" => ["manual", "ejemplo"],
				],
			],
		];

		// Crear generador para este único post
		$manualGenerator = ContentGeneratorFactory::createContentGenerator(
			"manual_project_generated",
			"project_post",
			$project_data,
			[
				"raw" => function ($content) {
					return $content;
				},
			] // Procesador que no modifica el contenido
		);

		// Registrar y ejecutar
		$manager = new ContentGeneratorManager();
		$manager->register($manualGenerator, 30);
		$manager->generateAllContent();
	}
}

// Ejemplo de uso:
// ProjectPostGenerator::register(); // Solo registra el generador para activación del tema
// ProjectPostGenerator::generate(); // Registra y ejecuta la generación
// ProjectPostGenerator::generateManualProject(); // Genera un proyecto usando el helper
