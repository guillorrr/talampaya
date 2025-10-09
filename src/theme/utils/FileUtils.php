<?php

namespace Talampaya\Utils;

use DirectoryIterator;

class FileUtils
{
	// -----------------------------------------------------------------------------
	// Directory Iterator Group By Folder
	// -----------------------------------------------------------------------------
	public static function talampaya_directory_iterator_group_by_folder(
		$path = "/inc/register",
		$extension = "php"
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

	// -----------------------------------------------------------------------------
	// Directory Iterator
	// -----------------------------------------------------------------------------

	public static function talampaya_directory_iterator(
		$path = "/inc/filters",
		$extension = "php",
		$prefix_exclude = "_",
		$exclude_files = []
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
