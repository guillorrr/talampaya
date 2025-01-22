<?php

if (defined("WP_CLI") && WP_CLI) {
	class Talampaya_CLI_Command
	{
		/**
		 * Save ACF fields to JSON files.
		 *
		 * ## EXAMPLES
		 *
		 *     wp talampaya acf-save-fields
		 *
		 * @when after_wp_load
		 */
		public function acf_save_fields()
		{
			$results = save_acf_fields_to_json();
			if (!empty($results["errors"])) {
				foreach ($results["errors"] as $error) {
					WP_CLI::error($error);
				}
			}
			foreach ($results["success"] as $message) {
				WP_CLI::success($message);
			}
		}

		/**
		 * Create ACF tables JSON files for each group.
		 *
		 * ## EXAMPLES
		 *
		 *     wp talampaya acf-create-tables
		 *
		 * @when after_wp_load
		 */
		public function acf_create_tables()
		{
			$results = create_acf_table_json_for_each_group();

			if (!empty($results["errors"])) {
				foreach ($results["errors"] as $error) {
					WP_CLI::warning($error);
				}
			}

			if (!empty($results["success"])) {
				foreach ($results["success"] as $message) {
					WP_CLI::success($message);
				}
			}

			$total_groups = count($results["success"]) + count($results["errors"]);
			WP_CLI::log("Processed {$total_groups} field groups.");
			WP_CLI::log(count($results["success"]) . " groups processed successfully.");
			WP_CLI::log(count($results["errors"]) . " groups had errors.");
		}
	}

	WP_CLI::add_command("talampaya", "Talampaya_CLI_Command", [
		"shortdesc" => "Custom commands for Talampaya theme development",
	]);
}
