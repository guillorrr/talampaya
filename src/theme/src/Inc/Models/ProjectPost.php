<?php

namespace App\Inc\Models;

/**
 * Modelo para proyectos
 */
class ProjectPost extends AbstractPost
{
	/**
	 * Obtiene el ID personalizado del proyecto
	 *
	 * @return string|null
	 */
	public function custom_id(): ?string
	{
		return $this->meta("post_type_project_post_custom_id");
	}

	/**
	 * Actualiza los datos del proyecto a partir de un array
	 *
	 * @param array $data Datos a actualizar
	 * @return bool
	 */
	public function updateFromData(array $data): bool
	{
		try {
			error_log(
				"ProjectPost::updateFromData: Iniciando actualización para proyecto ID=" . $this->ID
			);

			$post_args = [
				"ID" => $this->ID,
			];

			if (!empty($data["title"])) {
				$post_args["post_title"] = sanitize_text_field($data["title"]);
			}
			if (!empty($data["content"])) {
				$post_args["post_content"] = wp_kses_post($data["content"] ?? "");
			}
			if (!empty($data["status"])) {
				$post_args["post_status"] = $data["status"];
			}

			if (!empty($data["slug"])) {
				$post_args["post_name"] = sanitize_title($data["slug"]);
			} elseif (!empty($data["post_slug"])) {
				$post_args["post_name"] = sanitize_title($data["post_slug"]);
			}

			if (!empty($data["post_date"])) {
				$post_args["post_date"] = $data["post_date"];
			}
			if (!empty($data["post_modified"])) {
				$post_args["post_modified"] = $data["post_modified"];
			}

			if (count($post_args) > 1) {
				error_log(
					"ProjectPost::updateFromData: Actualizando datos básicos para proyecto ID=" .
						$this->ID
				);
				$result = wp_update_post($post_args, true);
				if (is_wp_error($result)) {
					error_log(
						"ProjectPost::updateFromData: Error al actualizar Proyecto ID=" .
							$this->ID .
							", error=" .
							$result->get_error_message()
					);
					return false;
				}

				// Actualizar propiedades del objeto
				$updated_post = get_post($this->ID);
				if ($updated_post) {
					$this->post_title = $updated_post->post_title;
					$this->post_content = $updated_post->post_content;
					$this->post_status = $updated_post->post_status;
					$this->post_date = $updated_post->post_date;
					$this->post_modified = $updated_post->post_modified;
					$this->post_slug = get_post_field("post_name", $this->ID);
				}
			}

			// Asegurar que el campo custom_id esté establecido
			$custom_id = $data["custom_id"] ?? $this->custom_id();
			if (!empty($custom_id)) {
				update_field("field_post_type_project_post_custom_id", $custom_id, $this->ID);
				error_log(
					"ProjectPost::updateFromData: Custom ID confirmado para proyecto ID=" .
						$this->ID .
						", custom_id=" .
						$custom_id
				);
			}

			// Procesar campos específicos del proyecto
			$custom_fields_success = $this->processProjectSpecificFields($data);
			if (!$custom_fields_success) {
				error_log(
					"ProjectPost::updateFromData: Advertencia - Algunos campos específicos no se actualizaron para proyecto ID=" .
						$this->ID
				);
				// Continuamos a pesar de problemas con campos específicos
			}

			// Actualizar campos personalizados genéricos
			$fields_updated = parent::updateCustomFields($data);
			if (!$fields_updated) {
				error_log(
					"ProjectPost::updateFromData: Advertencia - Algunos campos personalizados no se actualizaron para proyecto ID=" .
						$this->ID
				);
				// Continuamos a pesar de problemas con campos personalizados
			}

			// Actualizar datos SEO
			if (method_exists($this, "updateSeoData")) {
				$seo_updated = $this->updateSeoData($data);
				if (!$seo_updated) {
					error_log(
						"ProjectPost::updateFromData: Advertencia - Datos SEO no se actualizaron para proyecto ID=" .
							$this->ID
					);
					// Continuamos a pesar de problemas con SEO
				}
			}

			error_log(
				"ProjectPost::updateFromData: Actualización completada con éxito para proyecto ID=" .
					$this->ID
			);
			return true;
		} catch (\Exception $e) {
			error_log(
				"ProjectPost::updateFromData: Error inesperado al actualizar Proyecto ID=" .
					$this->ID .
					", error=" .
					$e->getMessage()
			);
			return false;
		}
	}

	/**
	 * Procesa campos específicos del proyecto
	 *
	 * @param array $data Datos a actualizar
	 * @return bool True si la actualización fue exitosa
	 */
	protected function processProjectSpecificFields(array $data): bool
	{
		try {
			// Actualizar campos específicos de los proyectos
			$specific_fields = [
				"subtitle" => "field_post_type_project_post_subtitle",
				"category" => "field_post_type_project_post_category",
				"tags" => "field_post_type_project_post_tags",
				"image_main_url" => "field_post_type_project_post_image_main_url",
				"image_main_title" => "field_post_type_project_post_image_main_title",
				"image_main_alt" => "field_post_type_project_post_image_main_alt",
			];

			foreach ($specific_fields as $key => $field_key) {
				if (!empty($data[$key])) {
					$value = $data[$key];

					// Procesamiento especial para tags
					if ($key === "tags" && !empty($value)) {
						if (is_string($value) && strpos($value, ";") !== false) {
							$value = explode(";", $value);
							$value = array_map("trim", $value);
						}
					}

					update_field($field_key, $value, $this->ID);
				}
			}

			return true;
		} catch (\Exception $e) {
			error_log(
				"ProjectPost::processProjectSpecificFields: Error al procesar campos específicos para ID=" .
					$this->ID .
					", error=" .
					$e->getMessage()
			);
			return false;
		}
	}
}
