<?php

namespace App\Core\Pages;

/**
 * Clase para crear páginas personalizadas basadas en ACF.
 *
 * Extiende la clase AbstractPage para proporcionar funcionalidad específica
 * para páginas que utilizan Advanced Custom Fields (ACF).
 */
class AcfPage extends AbstractPage
{
	/**
	 * Campos ACF asociados a esta página.
	 *
	 * @var array
	 */
	protected array $fields = [];

	/**
	 * Clave del grupo de campos.
	 *
	 * @var string
	 */
	protected string $fieldGroupKey;

	/**
	 * Título del grupo de campos.
	 *
	 * @var string
	 */
	protected string $fieldGroupTitle;

	/**
	 * Constructor.
	 *
	 * @param string      $pageTitle      Título de la página.
	 * @param string      $menuTitle      Título del menú.
	 * @param string      $menuSlug       Slug del menú.
	 * @param string      $fieldGroupKey  Clave del grupo de campos ACF.
	 * @param string      $fieldGroupTitle Título del grupo de campos ACF.
	 * @param string|null $parentSlug     Slug del menú padre (opcional).
	 */
	public function __construct(
		string $pageTitle,
		string $menuTitle,
		string $menuSlug,
		string $fieldGroupKey,
		string $fieldGroupTitle,
		?string $parentSlug = null
	) {
		parent::__construct($pageTitle, $menuTitle, $menuSlug, $parentSlug);

		$this->fieldGroupKey = $fieldGroupKey;
		$this->fieldGroupTitle = $fieldGroupTitle;
	}

	/**
	 * Inicializa la página y registra los campos ACF.
	 */
	public function init(): void
	{
		parent::init();

		// Registrar los campos ACF cuando se inicialice ACF
		add_action("acf/init", [$this, "registerFields"]);
	}

	/**
	 * Agrega un campo ACF a la página.
	 *
	 * @param array $field Configuración del campo ACF.
	 * @return self
	 */
	public function addField(array $field): self
	{
		$this->fields[] = $field;
		return $this;
	}

	/**
	 * Agrega múltiples campos ACF a la página.
	 *
	 * @param array $fields Array de configuraciones de campos ACF.
	 * @return self
	 */
	public function addFields(array $fields): self
	{
		foreach ($fields as $field) {
			$this->addField($field);
		}
		return $this;
	}

	/**
	 * Registra los campos ACF para esta página.
	 */
	public function registerFields(): void
	{
		if (empty($this->fields) || !function_exists("acf_add_local_field_group")) {
			return;
		}

		acf_add_local_field_group([
			"key" => $this->fieldGroupKey,
			"title" => $this->fieldGroupTitle,
			"fields" => $this->fields,
			"location" => [
				[
					[
						"param" => "options_page",
						"operator" => "==",
						"value" => $this->menuSlug,
					],
				],
			],
			"show_in_rest" => true,
			"menu_order" => 0,
		]);
	}

	/**
	 * Renderiza el contenido de la página ACF.
	 *
	 * Las páginas ACF son gestionadas automáticamente por WordPress/ACF,
	 * por lo que este método puede quedar vacío.
	 */
	public function render(): void
	{
		// ACF maneja la renderización automáticamente
	}
}
