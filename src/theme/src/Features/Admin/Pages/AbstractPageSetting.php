<?php

namespace App\Features\Admin\Pages;

use App\Core\Pages\PagesManager;

/**
 * Clase base abstracta para páginas de administración
 *
 * Proporciona la estructura básica que deben implementar todas las clases
 * que gestionan páginas de administración para evitar problemas de carga.
 */
abstract class AbstractPageSetting
{
	/**
	 * Constructor base
	 *
	 * Configura el hook principal para registro de páginas.
	 * Las clases hijas pueden sobrescribir este método para añadir hooks adicionales.
	 */
	public function __construct()
	{
		// Hook principal para registrar páginas
		add_action("talampaya_register_admin_pages", [$this, "registerPage"]);

		// Hook opcional para inicialización segura
		add_action("admin_init", [$this, "initialize"], 20);
	}

	/**
	 * Método para inicialización segura
	 *
	 * Este método se ejecuta cuando WordPress ya ha cargado completamente.
	 * Las clases hijas pueden sobrescribirlo para añadir funcionalidades.
	 *
	 * @return void
	 */
	public function initialize(): void
	{
		// Implementación vacía por defecto
		// Las clases hijas pueden sobrescribir este método
	}

	/**
	 * Método abstracto que todas las clases hijas deben implementar
	 * para registrar sus páginas en el gestor de páginas
	 *
	 * @param PagesManager $pagesManager Instancia del gestor de páginas
	 * @return void
	 */
	abstract public function registerPage(PagesManager $pagesManager): void;
}
