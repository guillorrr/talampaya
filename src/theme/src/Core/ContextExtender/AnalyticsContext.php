<?php

namespace App\Core\ContextExtender;

/**
 * Clase que agrega los IDs de analytics al contexto global de Timber
 */
class AnalyticsContext implements ContextExtenderInterface
{
	/**
	 * Extiende el contexto de Timber
	 *
	 * @param array $context El contexto actual de Timber
	 * @return array El contexto modificado
	 */
	public function extendContext(array $context): array
	{
		if (defined("FACEBOOK_PIXEL_ID")) {
			$context["FACEBOOK_PIXEL_ID"] = FACEBOOK_PIXEL_ID;
		}

		if (defined("GOOGLE_ANALYTICS_ID")) {
			$context["GOOGLE_ANALYTICS_ID"] = GOOGLE_ANALYTICS_ID;
		}

		return $context;
	}
}
