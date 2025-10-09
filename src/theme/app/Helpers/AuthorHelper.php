<?php

namespace Talampaya\App\Helpers;

class AuthorHelper
{
	public static function talampaya_get_or_create_author($email): int|WP_Error
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
}
