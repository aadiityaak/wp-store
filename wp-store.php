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

if (file_exists(WP_STORE_PATH . 'vendor/cmb2/cmb2/init.php')) {
    require_once WP_STORE_PATH . 'vendor/cmb2/cmb2/init.php';
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
    $should_migrate = get_option('wp_store_db_version') !== '1.0.1';
    global $wpdb;
    $table_name = $wpdb->prefix . 'store_carts';
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if ($should_migrate || !$exists) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            guest_key VARCHAR(64) NULL DEFAULT NULL,
            cart LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_user (user_id),
            UNIQUE KEY uniq_guest (guest_key)
        ) {$charset_collate};";
        dbDelta($sql);
        update_option('wp_store_db_version', '1.0.1');
    }

    $plugin = new \WpStore\Core\Plugin();
    $plugin->run();
}

add_action('plugins_loaded', 'wp_store_init');

register_activation_hook(__FILE__, function () {
    $post_types = new \WpStore\Core\PostTypes();
    $post_types->register_product_type();
    $post_types->register_order_type();

    // Trigger update logic
    delete_option('wp_store_db_version'); // Force update
    wp_store_init();

    flush_rewrite_rules();
});
