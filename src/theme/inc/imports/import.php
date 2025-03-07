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
	$author_id = null;
	if (!empty($data["author_email"])) {
		$author_id = talampaya_get_or_create_author($data["author_email"]);
		if (is_wp_error($author_id)) {
			return $author_id;
		}
	}

	$post_data = [
		"post_title" => $data["title"],
		"post_content" => $data["content"],
		"post_status" => $data["status"],
		"post_type" => $data["post_type"],
		"post_name" => $data["post_slug"],
		"post_date" => $data["post_date"],
		"post_date_gmt" => $data["post_date"],
		"post_modified" => $data["post_modified"],
		"post_modified_gmt" => $data["post_modified"],
	];

	if ($category_id) {
		$post_data["post_category"] = [$category_id];
	}

	if ($author_id) {
		$post_data["post_author"] = $author_id;
	}

	add_filter("wp_insert_post_data", "talampaya_create_post_with_post_modified", PHP_INT_MAX, 2);
	$post = wp_insert_post($post_data);
	remove_filter("wp_insert_post_data", "talampaya_create_post_with_post_modified", PHP_INT_MAX);

	return $post;
}

function talampaya_create_post_with_post_modified($data, $array)
{
	$data["post_modified"] = $array["post_modified"] ?? null;
	$data["post_modified_gmt"] =
		$array["post_modified_gmt"] ?? get_gmt_from_date($data["post_modified"]);
	$data["post_modified"] =
		$data["post_modified"] ?? get_date_from_gmt($data["post_modified_gmt"]);

	return $data;
}

function talampaya_get_or_create_author($email): int|WP_Error
{
	if (empty($email)) {
		return new WP_Error(
			"missing_email",
			"El correo electrónico es obligatorio para buscar o crear un autor."
		);
	}

	$user = get_user_by("email", $email);
	if ($user) {
		return $user->ID;
	}

	$username = sanitize_user(current(explode("@", $email)));
	$user_id = wp_create_user($username, wp_generate_password(), $email);

	if (is_wp_error($user_id)) {
		return new WP_Error(
			"user_creation_failed",
			"No se pudo crear el usuario: " . $user_id->get_error_message()
		);
	}

	wp_update_user([
		"ID" => $user_id,
		"role" => "author",
	]);

	return $user_id;
}

function get_image_id_by_filename($filename, $debug = false): ?int
{
	global $wpdb;

	$basename = basename($filename);

	if ($debug) {
		echo "Basename: " . $basename . "\n";
		echo PHP_EOL;
	}

	$sanitized_basename =
		sanitize_file_name(pathinfo($basename, PATHINFO_FILENAME)) .
		"." .
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

function set_post_thumbnail_if_exists($post_id, $image_url, $title, $debug = false): void
{
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

		$image_id = get_image_id_by_filename($filename);

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

function set_image_on_custom_field($post_id, $image_url, $custom_field, $title = null): bool
{
	if (!empty($image_url)) {
		$filename = basename($image_url);
		$image_id = get_image_id_by_filename($filename);

		if ($image_id) {
			return update_field($custom_field, $image_id, $post_id);
		} else {
			$post_id = is_int($post_id) ? $post_id : 0;
			$image_id = media_sideload_image($image_url, $post_id, $title, "id");
			if (!is_wp_error($image_id)) {
				return update_field($custom_field, $image_id, $post_id);
			}
		}
	}
	return false;
}
