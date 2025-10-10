<?php

namespace App\Core\Config;

/**
 * Archivo de definición de constantes globales para el tema
 */

// Versión del tema
$theme = wp_get_theme();
define("THEME_VERSION", $theme->get("Version"));
define("THEME_NAME", $theme->get("Name"));
define("THEME_TEXT_DOMAIN", $theme->get("TextDomain"));

// Estado de plugins
define("WOOCOMMERCE_IS_ACTIVE", class_exists("WooCommerce"));
define("ACF_IS_ACTIVE", class_exists("ACF"));

// Rutas importantes
define("THEME_DIR", get_template_directory());
define("THEME_URI", get_template_directory_uri());
define("THEME_ASSETS_URI", THEME_URI . "/assets");
define("THEME_IMG_URI", THEME_URI . "/assets/img");
define("THEME_CSS_URI", THEME_URI . "/css");
define("THEME_JS_URI", THEME_URI . "/js");

// Rutas ACF
define("ACF_PATH", THEME_DIR . "/src/Features/Acf");
define("ACF_BLOCKS_PATH", THEME_DIR . "/blocks");

// Configuración del sitio
define("GOOGLE_ANALYTICS_ID", "UA-XXXXXXXX");
define("FACEBOOK_PIXEL_ID", "XXXXXXXXXXXXXXX");

// Entorno de desarrollo
define("IS_DEVELOPMENT", WP_DEBUG);
