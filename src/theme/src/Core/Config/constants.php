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
define("PLUGINS_INTEGRATION_PATH", THEME_URI . "/src/Core/Plugins/Integration");
define("ACF_BLOCKS_PATH", THEME_DIR . "/blocks");
define("ACF_FIELDS_PATH", THEME_DIR . "/src/Features/Acf/Fields");
define("ACF_MODIFIERS_PATH", THEME_DIR . "/src/Features/Acf/Blocks/Modifiers");
define("HOOKS_PATH", THEME_DIR . "/src/Hooks");
define("ADMIN_PAGES_PATH", THEME_DIR . "/src/Features/Admin/Pages");
define("CONTENT_GENERATORS_PATH", THEME_DIR . "/src/Features/ContentGenerator/Generators");
define("TWIG_EXTENDERS_PATH", THEME_DIR . "/src/Core/TwigExtender/Custom");
define("CONTEXT_EXTENDERS_PATH", THEME_DIR . "/src/Core/ContextExtender/Custom");
define("API_ENDPOINTS_PATH", THEME_DIR . "/src/Core/Endpoints/Custom");

// Configuración del sitio
define("GOOGLE_ANALYTICS_ID", "UA-XXXXXXXX");
define("FACEBOOK_PIXEL_ID", "XXXXXXXXXXXXXXX");

// Entorno de desarrollo
define("IS_DEVELOPMENT", WP_DEBUG);
