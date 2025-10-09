<?php

namespace App\Core\Plugin;

/**
 * Clase base para plugins integrados del tema
 */
abstract class AbstractPlugin implements PluginInterface
{
	/**
	 * Flag para indicar si el plugin está activo
	 */
	protected bool $active = false;

	/**
	 * Nombre del plugin
	 */
	protected string $name = "";

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if ($this->shouldLoad()) {
			$this->active = true;
		}
	}

	/**
	 * Obtiene el nombre del plugin
	 *
	 * @return string Nombre del plugin
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Verifica si el plugin está activo
	 *
	 * @return bool True si el plugin está activo
	 */
	public function isActive(): bool
	{
		return $this->active;
	}

	/**
	 * Verifica si el plugin debe activarse
	 * Por defecto, siempre se activa, pero las clases hijas pueden sobrescribir este método
	 *
	 * @return bool True si el plugin debe activarse
	 */
	public function shouldLoad(): bool
	{
		return true;
	}

	/**
	 * Obtiene la lista de plugins requeridos por este plugin
	 * para usar con TGM Plugin Activation
	 *
	 * @return array Lista de plugins requeridos
	 */
	public function getRequiredPlugins(): array
	{
		return [];
	}
}
