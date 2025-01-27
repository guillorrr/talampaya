<?php
$imports = talampaya_directory_iterator(__DIR__, "php", "_", ["import"]);
if (!empty($imports)) {
	foreach ($imports as $i) {
		require_once $i;
	}
}

function talampaya_create_category($category_name)
{
	$category_id = get_cat_ID($category_name);
	if ($category_id == 0) {
		$new_category = wp_insert_term($category_name, "category");
		if (!is_wp_error($new_category)) {
			$category_id = $new_category["term_id"];
		}
	}
	return $category_id;
}

function talampaya_create_post($data, $category_id = null): WP_Error|int
{
	$post_data = [
		"post_title" => $data["title"],
		"post_content" => $data["content"],
		"post_status" => $data["status"],
		"post_type" => $data["post_type"],
		"post_name" => $data["post_slug"],
		"post_date" => $data["post_date"],
		"post_modified" => $data["post_modified"],
	];

	if ($category_id) {
		$post_data["post_category"] = [$category_id];
	}

	return wp_insert_post($post_data);
}

function get_image_id_by_filename($filename)
{
	global $wpdb;

	$basename = basename($filename);

	echo "Basename: " . $basename . "\n";
	echo PHP_EOL;

	$sanitized_basename =
		sanitize_file_name(pathinfo($basename, PATHINFO_FILENAME)) .
		"." .
		pathinfo($basename, PATHINFO_EXTENSION);

	echo "Sanitized basename: " . $sanitized_basename . "\n";
	echo PHP_EOL;

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

function set_post_thumbnail_if_exists($post_id, $image_url, $title): void
{
	if (!empty($image_url)) {
		echo "Image URL: " . $image_url . "\n";
		echo PHP_EOL;

		$filename = basename($image_url);

		echo "Filename: " . $filename . "\n";
		echo PHP_EOL;

		$image_id = get_image_id_by_filename($filename);

		if ($image_id) {
			set_post_thumbnail($post_id, $image_id);
		} else {
			$image_id = media_sideload_image($image_url, $post_id, $title, "id");

			echo "Image ID: " . $image_id . "\n";
			echo PHP_EOL;

			if (!is_wp_error($image_id)) {
				set_post_thumbnail($post_id, $image_id);
			}
		}
	}
}

function set_image_on_custom_field($post_id, $image_url, $custom_field, $title = null): void
{
	if (!empty($image_url)) {
		$filename = basename($image_url);
		$image_id = get_image_id_by_filename($filename);

		if ($image_id) {
			update_field($custom_field, $image_id, $post_id);
		} else {
			$image_id = media_sideload_image($image_url, $post_id, $title, "id");

			if (!is_wp_error($image_id)) {
				update_field($custom_field, $image_id, $post_id);
			}
		}
	}
}
