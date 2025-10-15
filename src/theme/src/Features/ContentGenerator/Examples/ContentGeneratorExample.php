<?php

namespace App\Features\ContentGenerator\Examples;

use App\Features\ContentGenerator\ContentGeneratorManager;

/**
 * Clase principal para ejecutar ejemplos de generación de contenido
 *
 * Este archivo muestra cómo utilizar el sistema ContentGenerator para generar
 * diferentes tipos de contenido en WordPress de forma programática
 */
class ContentGeneratorExample
{
	/**
	 * Registra todos los generadores de ejemplo
	 *
	 * @return void
	 */
	public static function registerAll(): void
	{
		// Registrar generador de páginas legales
		LegalPagesGenerator::register();

		// Registrar generador de proyectos
		ProjectPostGenerator::register();
	}

	/**
	 * Ejecuta todos los generadores de ejemplo
	 *
	 * @param bool $force Forzar regeneración aunque ya existan
	 * @return void
	 */
	public static function generateAll(bool $force = false): void
	{
		// Registrar todos los generadores
		self::registerAll();

		// Instanciar el manager
		$manager = new ContentGeneratorManager();

		// Generar todo el contenido
		if ($force) {
			$manager->forceRegenerateAll();
		} else {
			$manager->generateAllContent();
		}
	}

	/**
	 * Función para ejecutar al activar el tema
	 *
	 * Esta función debe ser conectada al hook 'after_switch_theme'
	 *
	 * @return void
	 */
	public static function activateThemeContent(): void
	{
		// Registrar todos los generadores
		self::registerAll();

		// El ContentGeneratorManager ejecutará automáticamente los generadores
		// cuando se active el tema si están registrados con el hook adecuado
	}
}

/**
 * Ejemplo de implementación:
 */

// Para registrar la generación al activar el tema:
// add_action('after_switch_theme', [ContentGeneratorExample::class, 'activateThemeContent']);

// Para ejecutar en un punto específico (por ejemplo, un endpoint de administrador):
/*
add_action('admin_post_generate_default_content', function() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die('Acceso denegado');
    }

    // Generar contenido
    ContentGeneratorExample::generateAll();

    // Redireccionar
    wp_redirect(admin_url('admin.php?page=content-generator&generated=true'));
    exit;
});
*/

// Para ejecutar desde una página de opciones personalizada:
/*
function mi_funcion_opciones() {
    if (isset($_POST['generate_content']) && check_admin_referer('generate_content_nonce')) {
        $force = isset($_POST['force_regenerate']);
        ContentGeneratorExample::generateAll($force);
        echo '<div class="notice notice-success"><p>Contenido generado correctamente.</p></div>';
    }

    // Interfaz de opciones
    ?>
    <div class="wrap">
        <h1>Generador de Contenido</h1>
        <form method="post">
            <?php wp_nonce_field('generate_content_nonce'); ?>
            <p>
                <input type="checkbox" name="force_regenerate" id="force_regenerate">
                <label for="force_regenerate">Forzar regeneración (sobreescribirá contenido existente)</label>
            </p>
            <p>
                <input type="submit" name="generate_content" class="button button-primary" value="Generar Contenido">
            </p>
        </form>
    </div>
    <?php
}

// Añadir página de opciones
add_action('admin_menu', function() {
    add_management_page(
        'Generador de Contenido',
        'Generador de Contenido',
        'manage_options',
        'content-generator',
        'mi_funcion_opciones'
    );
});
*/
