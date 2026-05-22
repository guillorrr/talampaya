<?php

declare(strict_types=1);

namespace App\Features\Seo;

/**
 * Multisite hreflang generator (no WPML/Polylang required).
 *
 * Resolves equivalents across subsites using a stable per-post meta key
 * (`_protected_page_key` by default), so hreflang alternates keep working
 * even when editors localize the URL slugs per subsite.
 *
 * To wire it up:
 * - Set the meta key on each "anchor" page that should be cross-linked
 *   (e.g. `_protected_page_key = "about"`) on every subsite.
 * - For dynamic CPTs/taxonomies, hook into `talampaya_hreflang_key` or
 *   `talampaya_hreflang_alternates` to provide the mapping yourself.
 *
 * Intentionally conservative: only outputs alternates it can resolve, so a
 * missing meta key just means no `<link rel="alternate">` is emitted.
 */
class MultisiteHreflangLinks
{
	/**
	 * Per-post meta key used to identify the same logical page across subsites.
	 * Stable even if editors change the per-locale slug.
	 *
	 * Override per-request via the `talampaya_hreflang_key` filter.
	 */
	private const PROTECTED_PAGE_KEY_META = "_protected_page_key";

	public function __construct()
	{
		if (!is_multisite() || is_admin()) {
			return;
		}

		add_action("wp_head", [$this, "render"], 5);
	}

	public function render(): void
	{
		if (is_feed() || is_robots() || is_trackback()) {
			return;
		}

		$alternates = $this->buildAlternates();
		if (empty($alternates)) {
			return;
		}

		/**
		 * Filter final hreflang alternates.
		 *
		 * @param array<string,string> $alternates Map hreflang => url
		 */
		$alternates = apply_filters("talampaya_hreflang_alternates", $alternates);
		if (empty($alternates) || !is_array($alternates)) {
			return;
		}

		foreach ($alternates as $hreflang => $url) {
			if (!$hreflang || !$url) {
				continue;
			}

			printf(
				"<link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n",
				esc_attr($hreflang),
				esc_url($url)
			);
		}
	}

	/**
	 * @return array<string,string> Map hreflang => url
	 */
	private function buildAlternates(): array
	{
		$is_front = is_front_page() || is_home();
		$is_singular = is_singular();

		// Start conservative: expand later if needed (tax archives, CPT archives, etc.).
		if (!$is_front && !$is_singular) {
			return [];
		}

		$current_blog_id = get_current_blog_id();
		$current_post = get_queried_object();
		$current_post_type = $current_post instanceof \WP_Post ? $current_post->post_type : null;
		$page_key = $this->getCurrentProtectedPageKey($current_post);

		$sites = get_sites([
			"archived" => 0,
			"deleted" => 0,
		]);

		$alternates = [];

		foreach ($sites as $site) {
			$blog_id = (int) $site->blog_id;

			if ($blog_id === $current_blog_id) {
				$url = $this->getCurrentUrl();
				$hreflang = $this->localeToHreflang($this->getCurrentSiteLocale());
			} else {
				$result = $this->resolveAlternateOnBlog($blog_id, $page_key, $current_post_type);
				$url = $result["url"] ?? null;
				$hreflang = $result["hreflang"] ?? null;
			}

			if (!$url || !$hreflang) {
				continue;
			}

			$alternates[$hreflang] = $url;
		}

		return $alternates;
	}

	/**
	 * Returns the site locale for the current blog (not the user locale).
	 * In WordPress, site language is stored in the WPLANG option.
	 */
	private function getCurrentSiteLocale(): string
	{
		$wplang = get_option("WPLANG");
		return is_string($wplang) && $wplang !== "" ? $wplang : "en_US";
	}

	private function getCurrentUrl(): ?string
	{
		if (is_front_page() || is_home()) {
			return home_url("/");
		}

		$post = get_queried_object();
		if ($post instanceof \WP_Post) {
			$permalink = get_permalink($post);
			return $permalink ?: null;
		}

		return null;
	}

	private function getCurrentProtectedPageKey($queried): ?string
	{
		$key = null;

		if ($queried instanceof \WP_Post) {
			$key = get_post_meta($queried->ID, self::PROTECTED_PAGE_KEY_META, true) ?: null;
		}

		/**
		 * Filter the hreflang mapping key for the current request.
		 *
		 * @param string|null $key
		 */
		$key = apply_filters("talampaya_hreflang_key", $key);

		return is_string($key) && $key !== "" ? $key : null;
	}

	/**
	 * @return array{url:?string,hreflang:?string}
	 */
	private function resolveAlternateOnBlog(
		int $blog_id,
		?string $page_key,
		?string $current_post_type
	): array {
		$current_post = get_queried_object();
		if (!$current_post instanceof \WP_Post) {
			return ["url" => null, "hreflang" => null];
		}

		switch_to_blog($blog_id);
		try {
			// IMPORTANT: use the target blog's site language (WPLANG), not the current request locale.
			$hreflang = $this->localeToHreflang($this->getCurrentSiteLocale());

			if (is_front_page() || is_home()) {
				return ["url" => home_url("/"), "hreflang" => $hreflang];
			}

			if ($page_key && $current_post_type) {
				$alt_post = $this->findPostByProtectedPageKey($page_key, $current_post_type);
				if ($alt_post instanceof \WP_Post) {
					$permalink = get_permalink($alt_post);
					return ["url" => $permalink ?: null, "hreflang" => $hreflang];
				}
			}

			return ["url" => null, "hreflang" => $hreflang];
		} finally {
			restore_current_blog();
		}
	}

	private function findPostByProtectedPageKey(string $key, string $post_type): ?\WP_Post
	{
		$posts = get_posts([
			"post_type" => $post_type,
			"post_status" => "publish",
			"numberposts" => 1,
			"meta_key" => self::PROTECTED_PAGE_KEY_META,
			"meta_value" => $key,
			"suppress_filters" => true,
		]);

		$post = $posts[0] ?? null;
		return $post instanceof \WP_Post ? $post : null;
	}

	private function localeToHreflang(string $locale): string
	{
		return strtolower(str_replace("_", "-", $locale));
	}
}
