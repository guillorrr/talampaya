<?php

namespace App\ThirdParty;

/**
 * Gestor centralizado para todas las integraciones de terceros
 *
 * Esta clase gestiona la carga e inicialización de todas las integraciones
 * con software de terceros, como plugins, bibliotecas, APIs, etc.
 */
class ThirdPartyManager
{
	/**
	 * Inicializa todas las integraciones de terceros
	 */
	public static function initialize(): void
	{
		// Inicializar TGM Plugin Activation
		self::initializeTGM();

		// Aquí se pueden inicializar otras integraciones en el futuro
		// self::initializeOtraIntegracion();
	}

	/**
	 * Inicializa TGM Plugin Activation para la gestión de plugins
	 */
	private static function initializeTGM(): void
	{
		$tgmFile = get_template_directory() . "/src/ThirdParty/TGM/plugins-config.php";
		if (file_exists($tgmFile)) {
			require_once $tgmFile;
		}
	}
}
