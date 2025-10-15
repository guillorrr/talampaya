<?php

namespace App\Features\ContentGenerator\Processors;

use App\Features\ContentGenerator\ContentProcessorInterface;

/**
 * Procesador para contenido de bloques Gutenberg
 */
class BlocksContentProcessor implements ContentProcessorInterface
{
	/**
	 * Procesa definiciones de bloques para convertirlas en contenido de WordPress
	 *
	 * @param mixed $content Array de definiciones de bloques
	 * @return string Contenido procesado de bloques
	 */
	public function process(mixed $content): string
	{
		if (!is_array($content)) {
			return "";
		}

		// Usar el helper de ACF si está disponible
		if (function_exists("\App\Inc\Helpers\AcfHelper::talampaya_make_content_for_blocks_acf")) {
			return \App\Inc\Helpers\AcfHelper::talampaya_make_content_for_blocks_acf($content);
		}

		// Implementación básica para bloques
		$processed_content = "";
		foreach ($content as $block) {
			$block_name = $block["name"] ?? "";
			if (empty($block_name)) {
				continue;
			}

			$attrs =
				isset($block["attributes"]) && is_array($block["attributes"])
					? " " . json_encode($block["attributes"])
					: "";

			$inner_content = $block["innerContent"] ?? "";

			if ($inner_content) {
				$processed_content .= "<!-- wp:" . $block_name . $attrs . " -->\n";
				$processed_content .= $inner_content . "\n";
				$processed_content .= "<!-- /wp:" . $block_name . " -->\n";
			} else {
				$processed_content .= "<!-- wp:" . $block_name . $attrs . " /-->\n";
			}
		}

		return $processed_content;
	}
}
