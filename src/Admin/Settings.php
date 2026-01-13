<?php

namespace WpStore\Admin;

class Settings
{
    public function register()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'save_settings']);
        add_action('admin_init', [$this, 'generate_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_menu_page()
    {
        add_submenu_page(
            'wp-store',
            'Pengaturan Toko',
            'Pengaturan',
            'manage_options',
            'wp-store-settings',
            [$this, 'render_page']
        );
    }

    public function enqueue_scripts($hook)
    {
        // Only load on our settings page
        // The hook for submenu page is usually 'store_product_page_wp-store-settings'
        // or we can check get_current_screen()
        $screen = get_current_screen();

        if ($screen && strpos($screen->id, 'wp-store-settings') !== false) {
            // Register Alpine.js if not already registered (it might be by other plugins or parts)
            if (!wp_script_is('alpinejs', 'registered')) {
                wp_register_script(
                    'alpinejs',
                    'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
                    [],
                    null,
                    true
                );
            }
            wp_enqueue_script('alpinejs');
        }
    }

    public function save_settings()
    {
        if (!isset($_POST['wp_store_settings_submit'])) {
            return;
        }

        if (!isset($_POST['wp_store_settings_nonce']) || !wp_verify_nonce($_POST['wp_store_settings_nonce'], 'wp_store_settings_action')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = isset($_POST['active_tab']) ? sanitize_text_field($_POST['active_tab']) : 'general';
        $settings = get_option('wp_store_settings', []);

        // General
        if ($active_tab === 'general') {
            $settings['store_name'] = sanitize_text_field($_POST['store_name']);
            $settings['store_address'] = sanitize_textarea_field($_POST['store_address']);
            $settings['store_email'] = sanitize_email($_POST['store_email']);
            $settings['store_phone'] = sanitize_text_field($_POST['store_phone']);
        }

        // Payment
        if ($active_tab === 'payment') {
            $settings['bank_name'] = sanitize_text_field($_POST['bank_name']);
            $settings['bank_account'] = sanitize_text_field($_POST['bank_account']);
            $settings['bank_holder'] = sanitize_text_field($_POST['bank_holder']);
        }

        // Pages
        if ($active_tab === 'pages') {
            $settings['page_shop'] = absint($_POST['page_shop']);
            $settings['page_profile'] = absint($_POST['page_profile']);
            $settings['page_cart'] = absint($_POST['page_cart']);
            $settings['page_checkout'] = absint($_POST['page_checkout']);
        }

        // System
        if ($active_tab === 'system') {
            $settings['currency_symbol'] = sanitize_text_field($_POST['currency_symbol']);
        }

        update_option('wp_store_settings', $settings);

        // Redirect back with success flag
        wp_safe_redirect(add_query_arg(['settings-updated' => 'true', 'tab' => $active_tab], admin_url('admin.php?page=wp-store-settings')));
        exit;
    }

    public function generate_pages()
    {
        if (!isset($_POST['wp_store_generate_pages'])) {
            return;
        }

        if (!isset($_POST['wp_store_settings_nonce']) || !wp_verify_nonce($_POST['wp_store_settings_nonce'], 'wp_store_settings_action')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('wp_store_settings', []);
        $pages_to_create = [
            'page_shop' => [
                'title' => 'Toko',
                'content' => '[wp_store_shop]'
            ],
            'page_profile' => [
                'title' => 'Profil Saya',
                'content' => '[store_customer_profile]'
            ],
            'page_cart' => [
                'title' => 'Keranjang Belanja',
                'content' => '[store_cart]' // Placeholder, assumes this shortcode exists or will be created
            ],
            'page_checkout' => [
                'title' => 'Checkout',
                'content' => '[store_checkout]' // Placeholder, assumes this shortcode exists or will be created
            ]
        ];

        $updated = false;

        foreach ($pages_to_create as $key => $page_data) {
            // Only create if not already set or if the set page doesn't exist
            if (empty($settings[$key]) || !get_post($settings[$key])) {
                $page_id = wp_insert_post([
                    'post_title'   => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ]);

                if (!is_wp_error($page_id)) {
                    $settings[$key] = $page_id;
                    $updated = true;
                }
            }
        }

        if ($updated) {
            update_option('wp_store_settings', $settings);
            // Redirect with a success flag
            wp_safe_redirect(add_query_arg(['settings-updated' => 'true', 'tab' => 'pages'], admin_url('admin.php?page=wp-store-settings')));
            exit;
        }
    }

    public function render_page()
    {
        require WP_STORE_PATH . 'templates/admin/settings.php';
    }
}
