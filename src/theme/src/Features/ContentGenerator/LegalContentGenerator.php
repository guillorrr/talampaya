<?php

namespace App\Features\ContentGenerator;

/**
 * Generador de contenido legal desde archivos HTML
 */
class LegalContentGenerator extends AbstractContentGenerator
{
	/**
	 * Los slugs de las páginas legales
	 * @var array
	 */
	protected array $legal_slugs;

	/**
	 * La ruta base donde se encuentran los archivos de contenido legal
	 * @var string
	 */
	protected string $content_base_path;

	/**
	 * El tipo de post (page o legal_post)
	 * @var string
	 */
	protected string $post_type;

	/**
	 * Constructor
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param array $legal_slugs Slugs de las páginas legales
	 * @param string $content_base_path Ruta base a los archivos de contenido
	 * @param string $post_type Tipo de post (por defecto 'page')
	 */
	public function __construct(
		string $option_key,
		array $legal_slugs,
		string $content_base_path = "/src/Features/DefaultContent/legal-content/",
		string $post_type = "page"
	) {
		parent::__construct($option_key);
		$this->legal_slugs = $legal_slugs;
		$this->content_base_path = $content_base_path;
		$this->post_type = $post_type;
	}

	/**
	 * Genera contenido legal desde archivos HTML
	 *
	 * @return bool Verdadero si la generación fue exitosa, falso en caso contrario
	 */
	protected function generateContent(): bool
	{
		if (empty($this->legal_slugs)) {
			return false;
		}

		$update = false;

		foreach ($this->legal_slugs as $slug) {
			$file_path = get_template_directory() . $this->content_base_path . $slug . ".html";

			if (!file_exists($file_path)) {
				continue;
			}

			$html_content = file_get_contents($file_path);
			if (!$html_content) {
				continue;
			}

			$post = get_page_by_path($slug, OBJECT, $this->post_type);

			if ($post && $post->post_content === "") {
				$updated = wp_update_post([
					"ID" => $post->ID,
					"post_content" => $html_content,
				]);

				if ($updated) {
					$update = true;
				}
			}
		}

		return $update;
	}
}
