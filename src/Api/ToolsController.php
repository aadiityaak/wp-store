<?php

namespace WpStore\Api;

use WP_REST_Request;
use WP_REST_Response;

class ToolsController
{
    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/tools/seed-products', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'seed_products'],
                'permission_callback' => [$this, 'check_admin_auth'],
            ],
        ]);
    }

    public function check_admin_auth()
    {
        return current_user_can('manage_options');
    }

    public function seed_products(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $count = isset($params['count']) ? (int) $params['count'] : 12;
        if ($count <= 0) {
            $count = 12;
        }
        if ($count > 50) {
            $count = 50;
        }
        $categories = [
            ['name' => 'Elektronik', 'slug' => 'elektronik'],
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Rumah Tangga', 'slug' => 'rumah-tangga'],
            ['name' => 'Olahraga', 'slug' => 'olahraga'],
            ['name' => 'Makanan & Minuman', 'slug' => 'makanan-minuman'],
        ];
        $term_ids = [];
        foreach ($categories as $cat) {
            $exists = term_exists($cat['slug'], 'store_product_cat');
            if ($exists && isset($exists['term_id'])) {
                $term_ids[] = (int) $exists['term_id'];
            } else {
                $created = wp_insert_term($cat['name'], 'store_product_cat', ['slug' => $cat['slug']]);
                if (!is_wp_error($created) && isset($created['term_id'])) {
                    $term_ids[] = (int) $created['term_id'];
                }
            }
        }
        if (empty($term_ids)) {
            $term = wp_insert_term('Umum', 'store_product_cat', ['slug' => 'umum']);
            if (!is_wp_error($term) && isset($term['term_id'])) {
                $term_ids[] = (int) $term['term_id'];
            }
        }
        $created_ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $title = 'Produk Contoh ' . $i;
            $content = 'Deskripsi produk contoh ' . $i . ' untuk pengujian katalog.';
            $post_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'store_product',
            ]);
            if (is_wp_error($post_id) || !$post_id) {
                continue;
            }
            $price = rand(10000, 250000);
            $stock = rand(1, 100);
            $weight = rand(1, 25) / 10;
            update_post_meta($post_id, '_store_price', $price);
            update_post_meta($post_id, '_store_stock', $stock);
            update_post_meta($post_id, '_store_weight_kg', $weight);
            if (!empty($term_ids)) {
                $rand_term = $term_ids[array_rand($term_ids)];
                wp_set_object_terms($post_id, [$rand_term], 'store_product_cat', false);
            }
            $created_ids[] = (int) $post_id;
        }
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Seeder berhasil membuat ' . count($created_ids) . ' produk.',
            'created' => $created_ids
        ], 200);
    }
}
