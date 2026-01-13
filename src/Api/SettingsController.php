<?php

namespace WpStore\Api;

use WP_REST_Request;
use WP_REST_Response;

class SettingsController
{
    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/settings', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_settings'],
                'permission_callback' => [$this, 'check_admin_auth'],
            ],
        ]);

        register_rest_route('wp-store/v1', '/settings/generate-pages', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'generate_pages'],
                'permission_callback' => [$this, 'check_admin_auth'],
            ],
        ]);
    }

    public function check_admin_auth()
    {
        return current_user_can('manage_options');
    }

    public function save_settings(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $settings = get_option('wp_store_settings', []);
        
        // Use active_tab to determine what to update, or just update everything provided
        // The frontend will send the relevant data.
        // To keep it simple and robust, we can merge existing settings with new ones.
        
        if (isset($params['store_name'])) $settings['store_name'] = sanitize_text_field($params['store_name']);
        if (isset($params['store_address'])) $settings['store_address'] = sanitize_textarea_field($params['store_address']);
        if (isset($params['store_email'])) $settings['store_email'] = sanitize_email($params['store_email']);
        if (isset($params['store_phone'])) $settings['store_phone'] = sanitize_text_field($params['store_phone']);

        // Handle multiple bank accounts
        if (isset($params['store_bank_accounts']) && is_array($params['store_bank_accounts'])) {
            $bank_accounts = [];
            foreach ($params['store_bank_accounts'] as $account) {
                if (isset($account['bank_name']) && isset($account['bank_account']) && isset($account['bank_holder'])) {
                    $bank_accounts[] = [
                        'bank_name'    => sanitize_text_field($account['bank_name']),
                        'bank_account' => sanitize_text_field($account['bank_account']),
                        'bank_holder'  => sanitize_text_field($account['bank_holder']),
                    ];
                }
            }
            $settings['store_bank_accounts'] = $bank_accounts;
        }

        // Legacy support (optional, but good to keep clean if we are fully migrating)
        if (isset($params['bank_name'])) $settings['bank_name'] = sanitize_text_field($params['bank_name']);
        if (isset($params['bank_account'])) $settings['bank_account'] = sanitize_text_field($params['bank_account']);
        if (isset($params['bank_holder'])) $settings['bank_holder'] = sanitize_text_field($params['bank_holder']);

        if (isset($params['page_shop'])) $settings['page_shop'] = absint($params['page_shop']);
        if (isset($params['page_profile'])) $settings['page_profile'] = absint($params['page_profile']);
        if (isset($params['page_cart'])) $settings['page_cart'] = absint($params['page_cart']);
        if (isset($params['page_checkout'])) $settings['page_checkout'] = absint($params['page_checkout']);

        if (isset($params['currency_symbol'])) $settings['currency_symbol'] = sanitize_text_field($params['currency_symbol']);

        update_option('wp_store_settings', $settings);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Pengaturan berhasil disimpan',
            'settings' => $settings
        ], 200);
    }

    public function generate_pages()
    {
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
                'content' => '[store_cart]'
            ],
            'page_checkout' => [
                'title' => 'Checkout',
                'content' => '[store_checkout]'
            ]
        ];

        $created_pages = [];
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
                    $created_pages[] = [
                        'key' => $key,
                        'id' => $page_id,
                        'title' => $page_data['title']
                    ];
                    $updated = true;
                }
            }
        }

        if ($updated) {
            update_option('wp_store_settings', $settings);
        }

        if (empty($created_pages)) {
             return new WP_REST_Response([
                'success' => true,
                'message' => 'Semua halaman sudah ada.',
                'pages' => $created_pages,
                'settings' => $settings
            ], 200);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Halaman berhasil dibuat.',
            'pages' => $created_pages,
            'settings' => $settings
        ], 200);
    }
}
