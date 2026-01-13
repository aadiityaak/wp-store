<?php

/**
 * Plugin Name: WP Store
 * Description: Plugin ecommerce sederhana berbasis REST API dan Alpine.js
 * Version:     0.1.0
 * Author:      Aditya Kristyanto
 * Text Domain: wp-store
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_STORE_VERSION', '0.1.0');
define('WP_STORE_PATH', plugin_dir_path(__FILE__));
define('WP_STORE_URL', plugin_dir_url(__FILE__));

if (file_exists(WP_STORE_PATH . 'vendor/autoload.php')) {
    require_once WP_STORE_PATH . 'vendor/autoload.php';
}

spl_autoload_register(function ($class) {
    $prefix = 'WpStore\\';
    $base_dir = WP_STORE_PATH . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

function wp_store_init()
{
    $plugin = new \WpStore\Core\Plugin();
    $plugin->run();
}

add_action('plugins_loaded', 'wp_store_init');

register_activation_hook(__FILE__, function () {
    $post_types = new \WpStore\Core\PostTypes();
    $post_types->register_product_type();
    $post_types->register_order_type();
    flush_rewrite_rules();
});
