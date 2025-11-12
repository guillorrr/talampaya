<?php

namespace App\Inc\Helpers;

class AttachmentsHelper
{
	/**
	 * Get the image ID by filename.
	 *
	 * @param string $filename The filename to search for.
	 * @param bool $debug Whether to output debug information.
	 * @return int|null The image ID if found, null otherwise.
	 */
	public static function get_image_id_by_filename(string $filename, bool $debug = false): ?int
	{
		global $wpdb;

		$basename = basename($filename);

		if ($debug) {
			echo "Basename: " . $basename . "\n";
			echo PHP_EOL;
		}

		$sanitized_basename =
			sanitize_file_name(pathinfo($basename, PATHINFO_FILENAME)) .
			"imports" .
			pathinfo($basename, PATHINFO_EXTENSION);

		if ($debug) {
			echo "Sanitized basename: " . $sanitized_basename . "\n";
			echo PHP_EOL;
		}

		$query = "
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'attachment'
        AND guid LIKE %s
        LIMIT 1";

		$prepared_query = $wpdb->prepare($query, "%" . $sanitized_basename . "%");

		$image_id = $wpdb->get_var($prepared_query);

		return $image_id ? intval($image_id) : null;
	}

	/**
	 * Set the post thumbnail if the image exists, otherwise sideload it.
	 *
	 * @param int $post_id The post ID to set the thumbnail for.
	 * @param string $image_url The URL of the image to use as the thumbnail.
	 * @param string $title The title to use for the image if sideloaded.
	 * @param bool $debug Whether to output debug information.
	 * @return void
	 */
	public static function set_post_thumbnail_if_exists(
		int $post_id,
		string $image_url,
		string $title,
		bool $debug = false
	): void {
		if (!empty($image_url)) {
			if ($debug) {
				echo "Image URL: " . $image_url . "\n";
				echo PHP_EOL;
			}

			$filename = basename($image_url);

			if ($debug) {
				echo "Filename: " . $filename . "\n";
				echo PHP_EOL;
			}

			$image_id = self::get_image_id_by_filename($filename);

			if ($image_id) {
				set_post_thumbnail($post_id, $image_id);
			} else {
				$image_id = media_sideload_image($image_url, $post_id, $title, "id");

				if ($debug) {
					echo "Image ID: " . $image_id . "\n";
					echo PHP_EOL;
				}

				if (!is_wp_error($image_id)) {
					set_post_thumbnail($post_id, $image_id);
				}
			}
		}
	}

	/**
	 * Importa una imagen desde una ruta local y la asigna a un campo personalizado
	 *
	 * @param int|string $post_id ID del post o "option" para campos de opciones
	 * @param string $path Ruta de la imagen (puede ser relativa al tema, absoluta, o relativa a uploads)
	 * @param string $custom_field Campo personalizado donde se guardará la imagen
	 * @param string|null $title Título opcional para la imagen
	 * @return bool Éxito de la operación
	 *
	 * @note
	 * La función intenta encontrar la imagen en tres ubicaciones:
	 * 1. Ruta absoluta proporcionada
	 * 2. Ruta relativa al directorio del tema activo
	 * 3. Ruta relativa al directorio de uploads de WordPress (con estructura año/mes si no se proporciona)
	 * Si la imagen no se encuentra en ninguna de estas ubicaciones, la función retorna false.
	 * Si la imagen se encuentra, se sube a la biblioteca de medios y se asigna al campo personalizado especificado.
	 * Si ocurre algún error durante la subida o asignación, también retorna false.
	 * @example
	 * // Asignar imagen a un post específico
	 * set_image_from_local_path(123, 'images/my-image.jpg', 'my_custom_field', 'My Image Title');
	 * // Asignar imagen a un campo de opciones
	 * set_image_from_local_path('option', '2024/06/my-image.jpg', 'my_option_field');
	 * @see https://developer.wordpress.org/reference/functions/media_handle_sideload/
	 * @see https://www.advancedcustomfields.com/resources/image/
	 * @see https://www.advancedcustomfields.com/resources/update_field/
	 * @see https://developer.wordpress.org/reference/functions/wp_upload_bits/
	 * @see https://developer.wordpress.org/reference/functions/wp_upload_dir/
	 */
	public static function set_image_from_local_path(
		$post_id,
		$path,
		$custom_field,
		$title = null
	): bool {
		require_once ABSPATH . "wp-admin/includes/file.php";
		require_once ABSPATH . "wp-admin/includes/media.php";
		require_once ABSPATH . "wp-admin/includes/image.php";

		// Comprobar si es una ruta absoluta que existe
		if (file_exists($path)) {
			$absolute_path = $path;
		} else {
			// Intentar como ruta relativa al tema
			$theme_path = get_stylesheet_directory() . "/" . ltrim($path, "/");

			if (file_exists($theme_path)) {
				$absolute_path = $theme_path;
			} else {
				// Intentar como ruta relativa al directorio uploads
				$upload_dir = wp_upload_dir();

				// Si no comienza con año/mes y no es una ruta absoluta, intentar construir esa estructura
				if (!preg_match("/^\d{4}\/\d{2}\//", $path) && !preg_match("/^\//", $path)) {
					$date_path = date("Y/m/");
					$path = $date_path . ltrim($path, "/");
				}

				$uploads_path = $upload_dir["basedir"] . "/" . ltrim($path, "/");

				if (file_exists($uploads_path)) {
					$absolute_path = $uploads_path;
				} else {
					error_log("Archivo no encontrado en ninguna ubicación: " . $path);
					return false;
				}
			}
		}

		$upload = wp_upload_bits(basename($absolute_path), null, file_get_contents($absolute_path));

		if ($upload["error"]) {
			error_log("Error en wp_upload_bits: " . $upload["error"]);
			return false;
		}

		$file_array = [
			"name" => basename($absolute_path),
			"tmp_name" => $upload["file"],
		];

		$image_id = media_handle_sideload($file_array, is_numeric($post_id) ? $post_id : 0, $title);

		if (is_wp_error($image_id)) {
			error_log("Error al registrar imagen: " . $image_id->get_error_message());
			return false;
		}

		return update_field($custom_field, $image_id, $post_id);
	}

	/**
	 * Configura imágenes de galería buscando primero localmente antes de descargar
	 *
	 * @param int $post_id ID del post
	 * @param array $image_urls Array con URLs de imágenes
	 * @param string $custom_field Nombre del campo personalizado (ACF)
	 * @return bool Éxito de la operación
	 *
	 * @note
	 * La función intenta encontrar cada imagen en la biblioteca de medios local primero.
	 * Si no se encuentra, intenta descargarla desde la URL proporcionada.
	 * Si ninguna imagen especificada, la función retorna false.
	 * Si ocurre algún error durante la descarga o asignación, se registra en el log de errores y la función continúa con la siguiente imagen.
	 * Al final, si al menos una imagen se asigna correctamente, retorna true; de lo contrario, false.
	 * @example
	 * set_gallery_images(123, ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'], 'my_gallery_field');
	 * @see https://developer.wordpress.org/reference/functions/media_sideload_image/
	 * @see https://www.advancedcustomfields.com/resources/update_field/
	 * @see https://developer.wordpress.org/reference/functions/is_wp_error/
	 */
	public static function set_gallery_images($post_id, array $image_urls, $custom_field): bool
	{
		if (empty($image_urls)) {
			return false;
		}

		if (!function_exists("media_sideload_image")) {
			require_once ABSPATH . "wp-admin/includes/media.php";
			require_once ABSPATH . "wp-admin/includes/file.php";
			require_once ABSPATH . "wp-admin/includes/image.php";
		}

		$gallery_ids = [];

		foreach ($image_urls as $image_url) {
			if (empty($image_url)) {
				continue;
			}

			// Primero intentar encontrar la imagen por nombre de archivo
			$filename = basename($image_url);
			$image_id = self::get_image_id_by_filename($filename);

			if ($image_id) {
				// Si la imagen ya existe en la biblioteca de medios, usarla
				$gallery_ids[] = $image_id;
			} else {
				// Si no existe localmente, intentar descargarla
				$image_id = media_sideload_image($image_url, $post_id, "", "id");

				if (!is_wp_error($image_id)) {
					$gallery_ids[] = $image_id;
				} else {
					error_log(
						"Error al descargar imagen para galería: " . $image_id->get_error_message()
					);
				}
			}
		}

		if (!empty($gallery_ids)) {
			return update_field($custom_field, $gallery_ids, $post_id);
		}

		return false;
	}
}
