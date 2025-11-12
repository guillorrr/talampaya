<?php

namespace App\Features\ContentGenerator\Generators;

use App\Features\ContentGenerator\AbstractContentGenerator;
use App\Features\ContentGenerator\ContentGeneratorFactory;
use App\Features\ContentGenerator\ContentGeneratorManager;
use App\Features\ContentGenerator\ContentTypeGenerator;
use App\Inc\Helpers\ContentTypeHelper;

/**
 * Ejemplo de generador de proyectos con bloques personalizados
 */
class ProjectPostGenerator extends AbstractContentGenerator
{
	/**
	 * El tipo de post para proyectos
	 * @var string
	 */
	protected string $post_type = "project_post";

	/**
	 * Flag para incluir proyecto manual
	 * @var bool
	 */
	protected bool $includeManualProject;

	/**
	 * Generador interno para manejar la creación de contenido
	 * @var ContentTypeGenerator|null
	 */
	protected ?ContentTypeGenerator $internalGenerator = null;

	/**
	 * Constructor
	 *
	 * @param bool $includeManualProject Si debe incluir también el proyecto manual
	 */
	public function __construct(bool $includeManualProject = true)
	{
		parent::__construct("projects_content_generated");
		$this->includeManualProject = $includeManualProject;
	}

	/**
	 * Implementación del método abstracto para generar el contenido
	 *
	 * @return bool Verdadero si la generación fue exitosa
	 */
	protected function generateContent(): bool
	{
		// Crear el generador de contenido de bloques con la factory usando los datos de proyectos
		//        $projects_data = $this->getProjectsData();
		//        $this->internalGenerator = ContentGeneratorFactory::createBlocksContentGenerator(
		//            $this->getOptionKey() . '_main',
		//            $this->post_type,
		//            $projects_data
		//        );

		$projects_data = $this->generateManualProject();
		$this->internalGenerator = ContentGeneratorFactory::createContentGenerator(
			$this->getOptionKey() . "_manual",
			$this->post_type,
			$projects_data,
			[
				"raw" => function ($content) {
					return $content;
				},
			] // Procesador que no modifica el contenido
		);

		try {
			return $this->internalGenerator->generateContent();
		} catch (\Exception $e) {
			error_log("ProjectPostGenerator: Error al generar contenido: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Obtiene los datos de los proyectos
	 *
	 * @return array Datos de proyectos
	 */
	protected function getProjectsData(): array
	{
		return [
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
								"field_block_example_title" => "Características del Proyecto",
								"field_block_example_subtitle" => "Innovación y Tecnología",
								"field_block_example_intro" =>
									"Descubre lo que hace especial a este proyecto y por qué destaca entre los demás.",
								"field_block_example_background_color" => "#f5f5f5",
								"field_block_example_image" => [
									"id" => 123, // ID de la imagen (debe existir en la biblioteca de medios)
									"url" => "https://via.placeholder.com/800x600",
									"alt" => "Imagen del proyecto",
								],
								"field_block_example_list" => [
									["example_text" => "Diseño moderno y responsive"],
									["example_text" => "Optimización de rendimiento"],
									["example_text" => "Accesibilidad web integrada"],
									["example_text" => "Integración con APIs externas"],
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
	}

	/**
	 * Genera un proyecto individual usando el ContentTypeHelper para mayor flexibilidad
	 * Esta es una forma alternativa de generar contenido usando el helper
	 *
	 * @return array|bool Si la generación fue exitosa
	 */
	protected function generateManualProject(): array|bool
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
				"field_block_example_title" => "Sección Destacada",
				"field_block_example_subtitle" => "Creada con Helper",
				"field_block_example_intro" =>
					"Esta sección utiliza el bloque personalizado Example.",
				"field_block_example_background_color" => "#f0f8ff",
				"field_block_example_list" => [
					["example_text" => "Elemento 1 de la lista"],
					["example_text" => "Elemento 2 de la lista"],
					["example_text" => "Elemento 3 de la lista"],
				],
			],
		]);
		$blocks[] = $example_block;

		// Combinar todos los bloques
		$content = ContentTypeHelper::combineBlocks($blocks);

		// Preparar datos para un único post
		return [
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
	}
}
