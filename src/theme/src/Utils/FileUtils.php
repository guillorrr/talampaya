<?php

namespace App\Utils;

use DirectoryIterator;

class FileUtils
{
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
		$data = [];
		foreach ($directories = new DirectoryIterator($path) as $directory) {
			if ($directory->isDir() && !$directory->isDot()) {
				$explode_directories = explode("/", $directory->getPathname());
				$last_directory = end($explode_directories);

				$data[$last_directory] = [];
				foreach ($files = new DirectoryIterator($directory->getPathname()) as $file) {
					if ($file->isFile() && $file->getExtension() === $extension) {
						$data[$last_directory] = array_merge(
							$data[$last_directory],
							require_once $file->getPathname()
						);
					}
				}
			}
		}
		return $data;
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
		$data = [];
		foreach ($files = new DirectoryIterator($path) as $file) {
			if ($file->isFile() && $file->getExtension() === $extension) {
				$filenameWithoutExtension = pathinfo($file->getFilename(), PATHINFO_FILENAME);
				if (
					!in_array($filenameWithoutExtension, $exclude_files) &&
					!str_starts_with($filenameWithoutExtension, $prefix_exclude)
				) {
					$data[] = $file->getPathname();
				}
			}
		}
		return $data;
	}
}
