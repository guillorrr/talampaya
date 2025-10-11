<?php

use App\Core\Pages\AcfPage;
use App\Core\Pages\PagesManager;

/**
 * Registra las páginas predeterminadas del tema en el gestor de páginas.
 *
 * @param PagesManager $pagesManager Instancia del gestor de páginas.
 * @return void
 */
function register_talampaya_import_pages(PagesManager $pagesManager): void
{
	if (!function_exists("acf_add_options_page")) {
		return;
	}

	$headerPage = new AcfPage(
		"Importar Contenido",
		__("Importar Contenido", "talampaya"),
		"acf-options-import",
		"group_options_page_import",
		__("Importar Contenido", "talampaya"),
		"theme-general-settings"
	);

	$headerPage->addField([
		"key" => "field_options_page_import_csv_project_post",
		"name" => "options_page_import_csv_project_post",
		"label" => __("Upload Project CSV", "talampaya"),
		"type" => "file",
		"required" => 0,
		"return_format" => "array",
		"allowed_file_types" => "csv",
		"instructions" => wp_kses_post(
			__(
				'Sube un archivo CSV con el formato correcto para FAQs. Descarga un <a href="' .
					get_template_directory_uri() .
					'/inc/imports/examples/project.csv" target="_blank">ejemplo de CSV</a> para referencia.',
				"talampaya"
			)
		),
	]);

	$pagesManager->addPage($headerPage);
}
add_action("talampaya_register_admin_pages", "register_talampaya_import_pages");

function process_csv_project_on_save($post_id): void
{
	if ($post_id !== "options") {
		error_log("process_csv_project_on_save: No es la Options Page, post_id=" . $post_id);
		return;
	}

	$service = new \App\Inc\Services\ProjectImportService();
	$transient_key = "csv_import_messages_" . get_current_user_id();
	$transient_messages = get_transient($transient_key) ?: [];

	if ($project_file_data = get_field("options_page_import_csv_project_post", "option")) {
		$project_file_path = get_attached_file($project_file_data["ID"]);

		// Verificar si el archivo existe y si no ha sido procesado antes
		$processing_key = "csv_processed_" . md5($project_file_path);
		$already_processed = get_transient($processing_key);

		if ($project_file_path && file_exists($project_file_path) && !$already_processed) {
			// Marcar el archivo como en procesamiento para evitar repetición
			set_transient($processing_key, time(), HOUR_IN_SECONDS);

			$project_result = $service->processCsv(
				$project_file_path,
				function ($row) use ($service) {
					$data = $service->processData($row);
					return $service->createOrUpdate(
						$data,
						\App\Inc\Models\ProjectPost::getInstance()
					) !== null;
				},
				null
			);

			error_log("process_csv_on_save: FAQs result: " . print_r($project_result, true));

			$project_message = sprintf(
				__("Procesadas %d líneas: %d correctas, %d errores.", "talampaya"),
				$project_result["success"] + $project_result["errors"],
				$project_result["success"],
				$project_result["errors"]
			);

			if (!empty($project_result["updated_project"])) {
				$project_message .=
					"<br><strong>" .
					__("FAQs actualizadas o creadas:", "talampaya") .
					'</strong><ul class="updated_project_list">';
				foreach ($project_result["updated_project"] as $faq) {
					$edit_link = get_edit_post_link($faq["post_id"], "display");
					$permalink = $faq["permalink"];
					$project_message .= sprintf(
						'<li>%s (<a href="%s" target="_blank">%s</a> | <a href="%s" target="_blank">%s</a>)</li>',
						esc_html($faq["title"]),
						esc_url($edit_link),
						__("Editar", "talampaya"),
						esc_url($permalink),
						__("Ver", "talampaya")
					);
				}
				$project_message .= "</ul>";
			}

			error_log("process_csv_project_on_save: FAQs message prepared: " . $project_message);

			add_settings_error(
				"csv_import",
				"project_import_result",
				$project_message,
				$project_result["errors"] > 0 ? "warning" : "updated"
			);
			$transient_messages[] = [
				"code" => "project_import_result",
				"message" => $project_message,
				"type" => $project_result["errors"] > 0 ? "warning" : "updated",
			];

			// Eliminar el archivo después de procesarlo
			if (file_exists($project_file_path)) {
				wp_delete_attachment($project_file_data["ID"], true);
				error_log(
					"process_csv_project_on_save: Archivo eliminado después de procesar: " .
						$project_file_path
				);
			}

			// Limpiar el campo ACF
			update_field("options_page_import_csv_project_post", "", "option");
			error_log("process_csv_project_on_save: Campo ACF limpiado");
		} elseif ($project_file_path && file_exists($project_file_path) && $already_processed) {
			error_log(
				"process_csv_project_on_save: Archivo ya procesado anteriormente, se omite: " .
					$project_file_path
			);
			$already_processed_message = __(
				"El archivo CSV de FAQs ya fue procesado anteriormente. Por favor, sube un nuevo archivo para procesar contenido diferente.",
				"talampaya"
			);
			add_settings_error(
				"csv_import",
				"project_already_processed",
				$already_processed_message,
				"warning"
			);
			$transient_messages[] = [
				"code" => "project_already_processed",
				"message" => $already_processed_message,
				"type" => "warning",
			];
		} else {
			$error_message = __(
				"El archivo CSV de FAQs no se encontró en el servidor.",
				"talampaya"
			);
			add_settings_error("csv_import", "project_file_not_found", $error_message, "error");
			$transient_messages[] = [
				"code" => "project_file_not_found",
				"message" => $error_message,
				"type" => "error",
			];
		}
	}

	if (!empty($transient_messages)) {
		set_transient($transient_key, $transient_messages, 30);
		error_log("process_csv_project_on_save: Transient set for key: " . $transient_key);
	}
}
add_action("acf/save_post", "process_csv_project_on_save", 20);
