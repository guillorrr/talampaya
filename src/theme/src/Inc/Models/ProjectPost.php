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
		$post_args = [
			"ID" => $this->ID,
			"post_title" => sanitize_text_field($data["title"]),
			"post_content" => wp_kses_post($data["content"] ?? ""),
			"post_status" => $data["status"],
		];

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

		$updated_post = get_post($this->ID);
		$this->post_title = $updated_post->post_title;
		$this->post_content = $updated_post->post_content;
		$this->post_status = $updated_post->post_status;
		$this->post_date = $updated_post->post_date;
		$this->post_modified = $updated_post->post_modified;
		$this->post_slug = get_post_field("post_name", $this->ID);

		// Actualizar campos personalizados
		parent::updateCustomFields($data);

		return true;
	}
}
