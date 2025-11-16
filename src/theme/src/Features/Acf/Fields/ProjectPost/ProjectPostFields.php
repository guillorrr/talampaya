<?php
// Post Type Key: project_post

declare(strict_types=1);

namespace App\Features\Acf\Fields\ProjectPost;

use Illuminate\Support\Str;
use App\Inc\Helpers\AcfHelper;
use App\Features\Acf\Traits\FeaturedImageSyncTrait;

class ProjectPostFields
{
	use FeaturedImageSyncTrait;

	private string $post_type = "project_post";

	public function __construct()
	{
		// Register featured image sync
		$this->registerFeaturedImageSync($this->post_type, "image");

		// Register ACF fields
		add_action("acf/init", [$this, "registerFields"], 10);
	}

	public function registerFields(): void
	{
		$block_title = __("Project Fields", "talampaya");

		// Configuración para el campo de imagen
		$image_args = [
			"return_format" => "array",
			"preview_size" => "medium",
			"library" => "all",
		];

		$additional_args = [
			"display_format" => "Y-m-d",
			"return_format" => "Y-m-d",
			"first_day" => 1,
		];

		$fields = [
			["image", "image", 100, __("Image", "talampaya"), 0, $image_args],
			["start_date", "date_picker", 50, null, 0, $additional_args],
			["end_date", "date_picker", 50, null, 0, $additional_args],
		];

		$groups = [[$block_title, AcfHelper::talampaya_create_acf_group_fields($fields), 1]];

		foreach ($groups as $group) {
			$field_group = [
				"key" => Str::snake($group[0]),
				"title" => __($group[0], "talampaya"),
				"fields" => $group[1],
				"location" => [
					[
						[
							"param" => "post_type",
							"operator" => "==",
							"value" => $this->post_type,
						],
					],
				],
				"show_in_rest" => true,
				"menu_order" => $group[2],
			];

			acf_add_local_field_group(
				AcfHelper::talampaya_replace_keys_from_acf_register_fields(
					$field_group,
					$this->post_type,
					"post_type"
				)
			);
		}
	}
}

new ProjectPostFields();
