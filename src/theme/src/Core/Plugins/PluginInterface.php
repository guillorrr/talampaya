<?php

namespace App\Core\Plugins;

/**
 * Interfaz para plugins integrados del tema
 */
interface PluginInterface
{
	/**
	 * Inicializa el plugin
	 *
	 * @return void
	 */
	public function initialize(): void;

	/**
	 * Verifica si el plugin debe activarse
	 *
	 * @return bool True si el plugin debe activarse
	 */
	public function shouldLoad(): bool;

	/**
	 * Obtiene el nombre del plugin
	 *
	 * @return string Nombre del plugin
	 */
	public function getName(): string;

	/**
	 * Obtiene la lista de plugins requeridos por este plugin
	 * para usar con TGM Plugin Activation
	 *
	 * @return array Lista de plugins requeridos
	 */
	public function getRequiredPlugins(): array;
}
