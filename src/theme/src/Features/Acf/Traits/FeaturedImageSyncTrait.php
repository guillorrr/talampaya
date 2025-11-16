<?php

declare(strict_types=1);

namespace App\Features\Acf\Traits;

use App\Inc\Helpers\AcfHelper;

/**
 * Trait to sync ACF image fields with WordPress featured images
 *
 * Usage in ACF field registration files:
 *
 * class MyPostTypeFields {
 *     use FeaturedImageSyncTrait;
 *
 *     public function __construct() {
 *         $this->registerFeaturedImageSync('my_post_type', 'image');
 *         add_action('acf/init', [$this, 'registerFields']);
 *     }
 * }
 */
trait FeaturedImageSyncTrait
{
	/**
	 * Register featured image sync for a post type
	 *
	 * @param string $postType The post type slug
	 * @param string $fieldName The raw field name (without post_type prefix)
	 */
	protected function registerFeaturedImageSync(string $postType, string $fieldName): void
	{
		add_action("save_post", function ($post_id) use ($postType, $fieldName) {
			$full_field_name = "post_type_" . $postType . "_" . $fieldName;

			AcfHelper::talampaya_save_custom_thumbnail_as_featured_image(
				$post_id,
				$postType,
				$full_field_name
			);
		});
	}
}
