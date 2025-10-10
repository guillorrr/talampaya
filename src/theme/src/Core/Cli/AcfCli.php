<?php

namespace App\Core\Cli;

use App\Features\Acf\Exporters\JsonExporter;
use App\Features\Acf\Exporters\TableJsonExporter;
use WP_CLI;
use WP_CLI\ExitException;

if (defined("WP_CLI") && WP_CLI) {
	class AcfCli
	{
		/**
		 * Verifica si ACF está activo
		 *
		 * @return bool
		 * @throws ExitException
		 */
		private function isAcfActive(): bool
		{
			if (!class_exists("ACF")) {
				WP_CLI::error("Advanced Custom Fields (ACF) plugin is not active.");
				return false;
			}
			return true;
		}

		/**
		 * Save ACF fields to JSON files.
		 *
		 * ## EXAMPLES
		 *
		 *     wp talampaya-acf save-fields
		 *
		 * @when after_wp_load
		 * @throws ExitException
		 */
		public function save_fields(): void
		{
			if (!$this->isAcfActive()) {
				return;
			}

			$results = JsonExporter::saveFieldsToJson();

			if (!empty($results["errors"])) {
				foreach ($results["errors"] as $error) {
					WP_CLI::error($error, false);
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
		 *     wp talampaya-acf create-tables
		 *
		 * @when after_wp_load
		 * @throws ExitException
		 */
		public function create_tables(): void
		{
			if (!$this->isAcfActive()) {
				return;
			}

			// Verificar si el plugin de tablas personalizadas está activo
			if (
				!class_exists("ACF_Custom_Database_Tables") &&
				!class_exists("ACF_Custom_Database_Tables\Main")
			) {
				WP_CLI::error("ACF Custom Database Tables plugin is not active.", false);
				return;
			}

			$results = TableJsonExporter::createTablesJson();

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

	WP_CLI::add_command("talampaya-acf", AcfCli::class, [
		"shortdesc" => "Custom commands for Talampaya theme development with ACF",
	]);
}
