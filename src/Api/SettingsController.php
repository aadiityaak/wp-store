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

        register_rest_route('wp-store/v1', '/rajaongkir/cities', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_rajaongkir_cities'],
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

        if (isset($params['rajaongkir_api_key'])) $settings['rajaongkir_api_key'] = sanitize_text_field($params['rajaongkir_api_key']);
        if (isset($params['rajaongkir_account_type'])) $settings['rajaongkir_account_type'] = sanitize_text_field($params['rajaongkir_account_type']);
        if (isset($params['shipping_origin_city'])) $settings['shipping_origin_city'] = sanitize_text_field($params['shipping_origin_city']);
        if (isset($params['shipping_couriers']) && is_array($params['shipping_couriers'])) {
            $settings['shipping_couriers'] = array_map('sanitize_text_field', $params['shipping_couriers']);
        }

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
                'content' => '[wp_store_profile]'
            ],
            'page_cart' => [
                'title' => 'Keranjang',
                'content' => '[wp_store_cart]'
            ],
            'page_checkout' => [
                'title' => 'Checkout',
                'content' => '[wp_store_checkout]'
            ],
        ];

        $created_pages = [];

        foreach ($pages_to_create as $key => $page_data) {
            // Check if page already exists in settings
            if (!empty($settings[$key])) {
                $existing_page = get_post($settings[$key]);
                if ($existing_page && $existing_page->post_status !== 'trash') {
                    continue;
                }
            }

            // Create page
            $page_id = wp_insert_post([
                'post_title'   => $page_data['title'],
                'post_content' => $page_data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);

            if ($page_id && !is_wp_error($page_id)) {
                $settings[$key] = $page_id;
                $created_pages[] = $page_data['title'];
            }
        }

        update_option('wp_store_settings', $settings);

        return new WP_REST_Response([
            'success' => true,
            'message' => count($created_pages) > 0 
                ? 'Halaman berhasil dibuat: ' . implode(', ', $created_pages) 
                : 'Semua halaman sudah ada.',
            'settings' => $settings
        ], 200);
    }

    public function get_rajaongkir_cities(WP_REST_Request $request)
    {
        $settings = get_option('wp_store_settings', []);
        $api_key = $settings['rajaongkir_api_key'] ?? '';
        $account_type = $settings['rajaongkir_account_type'] ?? 'starter';

        if (empty($api_key)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'API Key Raja Ongkir belum diatur.'
            ], 400);
        }

        $base_url = 'https://api.rajaongkir.com/starter';
        if ($account_type === 'basic') {
            $base_url = 'https://api.rajaongkir.com/basic';
        } elseif ($account_type === 'pro') {
            $base_url = 'https://pro.rajaongkir.com/api';
        }

        $response = wp_remote_get($base_url . '/city', [
            'headers' => [
                'key' => $api_key
            ]
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $response->get_error_message()
            ], 500);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // RajaOngkir structure: { meta: {...}, data: [...] } or { rajaongkir: { results: [...] } }
        // Official docs say: { rajaongkir: { query: [], status: {}, results: [] } }
        
        if (isset($data['rajaongkir']['results'])) {
            return new WP_REST_Response([
                'success' => true,
                'data' => $data['rajaongkir']['results']
            ], 200);
        }

        return new WP_REST_Response([
            'success' => false,
            'message' => 'Gagal mengambil data kota dari Raja Ongkir.',
            'raw' => $data
        ], 500);
    }
}
