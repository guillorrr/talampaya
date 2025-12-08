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
	 * Descarga una imagen desde una URL y la sube a la biblioteca de medios
	 * Si la imagen ya existe (por URL o nombre de archivo), retorna el ID existente
	 *
	 * Este es el método base para descargar imágenes desde URLs externas.
	 * Otros métodos como set_post_thumbnail_if_exists() usan este internamente.
	 *
	 * @param string $image_url URL de la imagen a descargar
	 * @param int $post_id ID del post al que se asociará (0 para no asociar)
	 * @param string|null $title Título opcional para la imagen
	 * @return int|null ID de la imagen o null si falla
	 *
	 * @example
	 * $image_id = AttachmentsHelper::download_image_from_url('https://example.com/image.jpg', 123, 'My Image');
	 */
	public static function download_image_from_url(
		string $image_url,
		int $post_id = 0,
		?string $title = null
	): ?int {
		if (empty($image_url)) {
			return null;
		}

		// Cargar archivos necesarios
		if (!function_exists("media_sideload_image")) {
			require_once ABSPATH . "wp-admin/includes/media.php";
			require_once ABSPATH . "wp-admin/includes/file.php";
			require_once ABSPATH . "wp-admin/includes/image.php";
		}

		// Verificar si la imagen ya existe por URL
		global $wpdb;
		$existing_by_url = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment' LIMIT 1",
				$image_url
			)
		);

		if ($existing_by_url) {
			return (int) $existing_by_url;
		}

		// Verificar si existe por nombre de archivo
		$filename = basename(parse_url($image_url, PHP_URL_PATH));
		$existing_by_filename = self::get_image_id_by_filename($filename);

		if ($existing_by_filename) {
			return $existing_by_filename;
		}

		// Descargar imagen desde URL
		$image_id = media_sideload_image($image_url, $post_id, $title ?: "", "id");

		if (is_wp_error($image_id)) {
			error_log(
				"AttachmentsHelper::download_image_from_url - Error al descargar {$image_url}: " .
					$image_id->get_error_message()
			);
			return null;
		}

		return $image_id;
	}

	/**
	 * Descarga una imagen y la establece como featured image del post
	 * Wrapper conveniente de download_image_from_url() + set_post_thumbnail()
	 *
	 * @param int $post_id The post ID to set the thumbnail for.
	 * @param string $image_url The URL of the image to use as the thumbnail.
	 * @param string $title The title to use for the image if sideloaded.
	 * @param bool $debug Whether to output debug information.
	 * @return int|null ID de la imagen si se estableció correctamente, null si falló
	 */
	public static function set_post_thumbnail_if_exists(
		int $post_id,
		string $image_url,
		string $title,
		bool $debug = false
	): ?int {
		if (empty($image_url)) {
			return null;
		}

		if ($debug) {
			echo "Image URL: " . $image_url . "\n";
			echo "Filename: " . basename($image_url) . "\n";
			echo PHP_EOL;
		}

		// Usar el método centralizado para descargar la imagen
		$image_id = self::download_image_from_url($image_url, $post_id, $title);

		if ($debug && $image_id) {
			echo "Image ID: " . $image_id . "\n";
			echo PHP_EOL;
		}

		// Establecer como thumbnail si se descargó correctamente
		if ($image_id) {
			set_post_thumbnail($post_id, $image_id);
			return $image_id;
		}

		return null;
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
	 * Descarga múltiples imágenes y las asigna a un campo ACF gallery
	 * Usa download_image_from_url() internamente para cada imagen
	 *
	 * @param int $post_id ID del post
	 * @param array $image_urls Array con URLs de imágenes
	 * @param string $custom_field Nombre del campo personalizado (ACF)
	 * @return bool Éxito de la operación
	 *
	 * @example
	 * set_gallery_images(123, ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'], 'my_gallery_field');
	 */
	public static function set_gallery_images($post_id, array $image_urls, $custom_field): bool
	{
		if (empty($image_urls)) {
			return false;
		}

		$gallery_ids = [];

		foreach ($image_urls as $image_url) {
			if (empty($image_url)) {
				continue;
			}

			// Usar el método base centralizado
			$image_id = self::download_image_from_url($image_url, $post_id);

			if ($image_id) {
				$gallery_ids[] = $image_id;
			}
		}

		if (!empty($gallery_ids)) {
			return update_field($custom_field, $gallery_ids, $post_id);
		}

		return false;
	}

	/**
	 * Procesa una imagen de WordPress y genera un array con tamaños responsive
	 *
	 * @param int|array|null $image ID de imagen, array de ACF, o null
	 * @param string|array|null $sizes String (mismo tamaño para todos), array con tamaños específicos, o null (valores por defecto)
	 * @return array|null Array con src, smallSrc, mediumSrc, largeSrc, xlargeSrc, alt. Retorna null si no hay imagen válida
	 *
	 * @example
	 * // Valores por defecto
	 * $image_data = AttachmentsHelper::processWordPressImage($image_id);
	 *
	 * // Tamaño único para todos
	 * $image_data = AttachmentsHelper::processWordPressImage($image_id, 'large');
	 *
	 * // Tamaños personalizados
	 * $image_data = AttachmentsHelper::processWordPressImage($image_id, [
	 *   'small' => 'medium_large',
	 *   'medium' => 'medium_large',
	 *   'large' => 'large',
	 *   'xlarge' => 'large',
	 *   'src' => 'full'
	 * ]);
	 */
	public static function processWordPressImage($image, $sizes = null): ?array
	{
		// Extraer ID de imagen
		$image_id = null;
		$alt = "";

		if (is_numeric($image)) {
			$image_id = (int) $image;
		} elseif (is_array($image)) {
			$image_id = $image["ID"] ?? ($image["id"] ?? null);
			$alt = $image["alt"] ?? "";
		}

		if (!$image_id) {
			return null;
		}

		// Obtener alt si no está en el array
		if (empty($alt)) {
			$alt = get_post_meta($image_id, "_wp_attachment_image_alt", true) ?: "";
		}

		// Determinar los tamaños a usar
		$size_config = [];

		if (is_string($sizes)) {
			// Si es un string, usar el mismo tamaño para todos
			$size_config = [
				"small" => $sizes,
				"medium" => $sizes,
				"large" => $sizes,
				"xlarge" => $sizes,
				"src" => $sizes,
			];
		} elseif (is_array($sizes)) {
			// Si es un array, usar los tamaños especificados con valores por defecto
			$size_config = [
				"small" => $sizes["small"] ?? "medium_large",
				"medium" => $sizes["medium"] ?? "medium_large",
				"large" => $sizes["large"] ?? "large",
				"xlarge" => $sizes["xlarge"] ?? "full",
				"src" => $sizes["src"] ?? "full",
			];
		} else {
			// Valores por defecto (comportamiento original)
			$size_config = [
				"small" => "medium_large",
				"medium" => "medium_large",
				"large" => "large",
				"xlarge" => "full",
				"src" => "full",
			];
		}

		// Obtener URL del tamaño full (siempre existe) como fallback final
		$full_url = wp_get_attachment_image_url($image_id, "full");
		if (!$full_url) {
			return null;
		}

		// Obtener URL del tamaño por defecto (src) para el atributo src
		$default_url = wp_get_attachment_image_url($image_id, $size_config["src"]) ?: $full_url;

		// Obtener URLs de todos los tamaños, usando full como fallback si el tamaño no existe
		$small_url = wp_get_attachment_image_url($image_id, $size_config["small"]) ?: $full_url;
		$medium_url = wp_get_attachment_image_url($image_id, $size_config["medium"]) ?: $full_url;
		$large_url = wp_get_attachment_image_url($image_id, $size_config["large"]) ?: $full_url;
		$xlarge_url = wp_get_attachment_image_url($image_id, $size_config["xlarge"]) ?: $full_url;

		// Construir array compatible con image.twig
		return [
			"src" => $default_url,
			"alt" => $alt,
			"smallSrc" => $small_url,
			"mediumSrc" => $medium_url,
			"largeSrc" => $large_url,
			"xlargeSrc" => $xlarge_url,
		];
	}
}
