<?php

namespace App\Utils;

use DirectoryIterator;

class FileUtils
{
	/**
	 * Directory Iterator Universal
	 *
	 * A flexible method to iterate through directories and files with various options for filtering
	 * and processing.
	 *
	 * @param string $path The directory path to iterate over.
	 * @param array $options Configuration options:
	 *   - extension: (string) File extension to filter by. Default: 'php'.
	 *   - prefix_exclude: (string) Filename prefix to exclude. Default: '_'.
	 *   - exclude_files: (array) Array of filenames to exclude. Default: [].
	 *   - process_subdirs: (bool) Whether to process subdirectories. Default: false.
	 *   - group_by_folder: (bool) Whether to group results by folder. Default: false.
	 *   - include_files: (bool) Whether to include/require files and merge their returned data. Default: false.
	 *    - filter_callback: (callable|null) Optional callback function for additional filtering.
	 *        Function signature: function(SplFileInfo $file, string $path, string $directory_name = null): bool
	 * @return array Results based on the specified options.
	 */
	public static function talampaya_directory_iterator_universal(
		string $path,
		array $options = []
	): array {
		// Default options
		$defaults = [
			"extension" => "php",
			"prefix_exclude" => "_",
			"exclude_files" => [],
			"process_subdirs" => false,
			"group_by_folder" => false,
			"include_files" => false,
			"filter_callback" => null,
		];

		// Merge provided options with defaults
		$options = array_merge($defaults, $options);

		$data = [];
		$iterator = new DirectoryIterator($path);

		foreach ($iterator as $item) {
			// Skip dot directories
			if ($item->isDot()) {
				continue;
			}

			// Process directories if configured
			if ($item->isDir() && $options["process_subdirs"]) {
				$explode_directories = explode("/", $item->getPathname());
				$last_directory = end($explode_directories);

				// Skip directories with excluded prefix
				if (str_starts_with($last_directory, $options["prefix_exclude"])) {
					continue;
				}

				$subdir_files = [];
				foreach (new DirectoryIterator($item->getPathname()) as $file) {
					if ($file->isFile() && $file->getExtension() === $options["extension"]) {
						$filenameWithoutExtension = pathinfo(
							$file->getFilename(),
							PATHINFO_FILENAME
						);

						if (
							!in_array($filenameWithoutExtension, $options["exclude_files"]) &&
							!str_starts_with(
								$filenameWithoutExtension,
								$options["prefix_exclude"]
							) &&
							(!$options["filter_callback"] ||
								call_user_func(
									$options["filter_callback"],
									$file,
									$item->getPathname(),
									$last_directory
								))
						) {
							if ($options["include_files"]) {
								require_once $file->getPathname();
							}

							$subdir_files[] = $file->getPathname();
						}
					}
				}

				if ($options["group_by_folder"]) {
					$data[$last_directory] = $subdir_files;
				} else {
					// Añadir los archivos del subdirectorio al array principal
					$data = array_merge($data, $subdir_files);
				}
			}
			// Process files in main directory
			elseif ($item->isFile() && $item->getExtension() === $options["extension"]) {
				$filenameWithoutExtension = pathinfo($item->getFilename(), PATHINFO_FILENAME);
				if (
					!in_array($filenameWithoutExtension, $options["exclude_files"]) &&
					!str_starts_with($filenameWithoutExtension, $options["prefix_exclude"]) &&
					(!$options["filter_callback"] ||
						call_user_func($options["filter_callback"], $item, $path))
				) {
					if ($options["include_files"]) {
						require_once $item->getPathname();
					} else {
						$data[] = $item->getPathname();
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Directory Iterator Group by Folder
	 *
	 * Iterates over subdirectories in a specified path, and for each subdirectory,
	 * it includes PHP files and merges their returned arrays into a grouped array.
	 *
	 * @param string $path The directory path to iterate over. Defaults to "/inc/register".
	 * @param string $extension The file extension to filter by. Defaults to "php".
	 * @return array An associative array where keys are subdirectory names and values are arrays of merged data from included files.
	 */
	public static function talampaya_directory_iterator_group_by_folder(
		string $path = "/inc/register",
		string $extension = "php"
	): array {
		return self::talampaya_directory_iterator_universal($path, [
			"extension" => $extension,
			"process_subdirs" => true,
			"group_by_folder" => true,
			"include_files" => true,
		]);
	}

	/**
	 * Directory Iterator
	 *
	 * Iterates over files in a specified directory and filters them based on their extension,
	 * prefix, and a list of excluded file names.
	 *
	 * @param string $path The directory path to iterate over. Defaults to "/inc/filters".
	 * @param string $extension The file extension to filter by. Defaults to "php".
	 * @param string $prefix_exclude The filename prefix to exclude. Defaults to "_".
	 * @param array $exclude_files An array of filenames (without extensions) to exclude. Defaults to an empty array.
	 * @return array An array of file paths that match the filtering criteria.
	 */
	public static function talampaya_directory_iterator(
		string $path = "/inc/filters",
		string $extension = "php",
		string $prefix_exclude = "_",
		array $exclude_files = []
	): array {
		return self::talampaya_directory_iterator_universal($path, [
			"extension" => $extension,
			"prefix_exclude" => $prefix_exclude,
			"exclude_files" => $exclude_files,
		]);
	}
}
