<?php

namespace App\Features\Admin\Pages;

use App\Core\Pages\PagesManager;
use App\Core\Pages\RegularPage;

/**
 * Clase para gestionar la configuración de geolocalización en el panel de administración
 */
class GeolocationSettings
{
	/**
	 * Instancia de la página
	 *
	 * @var RegularPage
	 */
	protected RegularPage $page;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action("admin_init", [$this, "registerSettings"]);
		add_action("talampaya_register_admin_pages", [$this, "registerPage"]);
	}

	/**
	 * Registra la página en el gestor de páginas
	 *
	 * @param PagesManager $pagesManager Instancia del gestor de páginas
	 */
	public function registerPage(PagesManager $pagesManager): void
	{
		$this->page = new RegularPage(
			"Configuración de Geolocalización",
			"Geolocalización",
			"talampaya-geolocation",
			[$this, "renderSettingsPage"]
		);

		$this->page->setAsOptionsPage(true)->setCapability("manage_options");

		$pagesManager->addPage($this->page);
	}

	/**
	 * Registra las opciones de configuración
	 */
	public function registerSettings(): void
	{
		register_setting("talampaya_geolocation_settings", "talampaya_geolocation_service", [
			"type" => "string",
			"description" => "Servicio de geolocalización a utilizar",
			"default" => "maxmind",
		]);

		register_setting("talampaya_geolocation_settings", "talampaya_maxmind_account_id", [
			"type" => "string",
			"description" => "ID de cuenta de MaxMind",
			"sanitize_callback" => "sanitize_text_field",
		]);

		register_setting("talampaya_geolocation_settings", "talampaya_maxmind_license_key", [
			"type" => "string",
			"description" => "Clave de licencia de MaxMind",
			"sanitize_callback" => "sanitize_text_field",
		]);

		add_settings_section(
			"talampaya_geolocation_main",
			"Configuración de la API de Geolocalización",
			[$this, "renderSectionDescription"],
			"talampaya-geolocation"
		);

		add_settings_field(
			"talampaya_geolocation_service",
			"Servicio",
			[$this, "renderServiceField"],
			"talampaya-geolocation",
			"talampaya_geolocation_main"
		);

		add_settings_field(
			"talampaya_maxmind_credentials",
			"Credenciales de MaxMind",
			[$this, "renderMaxMindCredentialsField"],
			"talampaya-geolocation",
			"talampaya_geolocation_main"
		);
	}

	/**
	 * Renderiza la descripción de la sección
	 */
	public function renderSectionDescription(): void
	{
		echo "<p>Configura el servicio de geolocalización para detectar la ubicación de los visitantes por su dirección IP.</p>";
		echo '<p>Para usar MaxMind GeoIP, necesitas <a href="https://www.maxmind.com/en/geolite2/signup" target="_blank">crear una cuenta gratuita</a> y generar una clave de licencia.</p>';
	}

	/**
	 * Renderiza el campo para seleccionar el servicio
	 */
	public function renderServiceField(): void
	{
		$service = get_option("talampaya_geolocation_service", "maxmind");

		echo '<select name="talampaya_geolocation_service" id="talampaya_geolocation_service">';
		echo '<option value="maxmind" ' .
			selected($service, "maxmind", false) .
			">MaxMind GeoIP API</option>";
		echo "</select>";
	}

	/**
	 * Renderiza los campos para las credenciales de MaxMind
	 */
	public function renderMaxMindCredentialsField(): void
	{
		$accountId = get_option("talampaya_maxmind_account_id", "");
		$licenseKey = get_option("talampaya_maxmind_license_key", "");

		// Campo para el ID de cuenta
		echo '<div style="margin-bottom: 10px;">';
		echo '<label for="talampaya_maxmind_account_id">ID de Cuenta:</label><br>';
		echo '<input type="text" id="talampaya_maxmind_account_id" name="talampaya_maxmind_account_id" value="' .
			esc_attr($accountId) .
			'" class="regular-text">';
		echo "</div>";

		// Campo para la clave de licencia
		echo "<div>";
		echo '<label for="talampaya_maxmind_license_key">Clave de Licencia:</label><br>';
		echo '<input type="password" id="talampaya_maxmind_license_key" name="talampaya_maxmind_license_key" value="' .
			esc_attr($licenseKey) .
			'" class="regular-text">';
		echo "</div>";

		// Mensaje si las credenciales están definidas en wp-config.php
		if (defined("MAXMIND_ACCOUNT_ID") && defined("MAXMIND_LICENSE_KEY")) {
			echo '<p class="description">Las credenciales están definidas en wp-config.php. Los valores ingresados aquí serán ignorados.</p>';
		}
	}

	/**
	 * Renderiza la página de configuración
	 */
	public function renderSettingsPage(): void
	{
		if (!current_user_can("manage_options")) {
			return;
		} ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields("talampaya_geolocation_settings");
                do_settings_sections("talampaya-geolocation");
                submit_button("Guardar Cambios");
                ?>
            </form>

            <hr>

            <h2>Probar Geolocalización</h2>
            <p>Puedes probar tu configuración de geolocalización para verificar que funciona correctamente.</p>

            <div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px;">
                <div id="geolocation-test-result">
                    <p>Haz clic en "Probar" para verificar la configuración de geolocalización.</p>
                </div>
                <button type="button" class="button button-primary" id="test-geolocation">Probar</button>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('#test-geolocation').on('click', function() {
                    var $result = $('#geolocation-test-result');
                    $result.html('<p>Consultando API de geolocalización...</p>');

                    $.ajax({
                        url: '<?php echo esc_url(rest_url("talampaya/v1/geolocation")); ?>',
                        method: 'GET',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js(
                            	wp_create_nonce("wp_rest")
                            ); ?>');
                        },
                        success: function(response) {
                            if (response.success) {
                                var html = '<h4>Resultado exitoso</h4>';
                                html += '<table class="widefat" style="margin-top: 10px;">';
                                html += '<tr><th>IP</th><td>' + response.ip + '</td></tr>';

                                if (response.data.country) {
                                    html += '<tr><th>País</th><td>' + response.data.country.name + ' (' + response.data.country.code + ')</td></tr>';
                                }

                                if (response.data.city && response.data.city !== 'Unknown') {
                                    html += '<tr><th>Ciudad</th><td>' + response.data.city + '</td></tr>';
                                }

                                if (response.data.subdivision) {
                                    html += '<tr><th>Región</th><td>' + response.data.subdivision.name + '</td></tr>';
                                }

                                if (response.data.location) {
                                    html += '<tr><th>Coordenadas</th><td>' + response.data.location.latitude + ', ' + response.data.location.longitude + '</td></tr>';
                                    html += '<tr><th>Zona horaria</th><td>' + response.data.location.timezone + '</td></tr>';
                                }

                                html += '<tr><th>Proveedor</th><td>' + response.data.provider + '</td></tr>';

                                if (response.data.error) {
                                    html += '<tr><th>Error</th><td>' + response.data.error + '</td></tr>';
                                }

                                html += '</table>';
                                $result.html(html);
                            } else {
                                $result.html('<p class="error">Error: ' + response.message + '</p>');
                            }
                        },
                        error: function(jqXHR) {
                            var message = jqXHR.responseJSON && jqXHR.responseJSON.message
                                ? jqXHR.responseJSON.message
                                : 'Error desconocido al contactar el API.';

                            $result.html('<p class="error">Error: ' + message + '</p>');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
	}
}
