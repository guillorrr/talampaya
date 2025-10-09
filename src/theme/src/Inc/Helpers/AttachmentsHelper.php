<?php

namespace App\Inc\Helpers;

class AttachmentsHelper
{
	public static function get_image_id_by_filename($filename, $debug = false): ?int
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

	public static function set_post_thumbnail_if_exists(
		$post_id,
		$image_url,
		$title,
		$debug = false
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
}
