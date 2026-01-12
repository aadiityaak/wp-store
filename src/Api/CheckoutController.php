<?php

namespace WpStore\Api;

use WP_REST_Request;
use WP_REST_Response;

class CheckoutController
{
    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/checkout', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_order'],
                'permission_callback' => [$this, 'require_rest_nonce'],
            ],
        ]);
    }

    public function require_rest_nonce(WP_REST_Request $request)
    {
        $nonce = $request->get_header('x_wp_nonce');
        if (!$nonce) {
            $nonce = $request->get_header('x-wp-nonce');
        }

        return is_string($nonce) && wp_verify_nonce($nonce, 'wp_rest');
    }

    public function create_order(WP_REST_Request $request)
    {
        $data = $request->get_json_params();

        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        $email = isset($data['email']) ? sanitize_email($data['email']) : '';
        $phone = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

        if ($name === '' || empty($items)) {
            return new WP_REST_Response(['message' => 'Data tidak lengkap'], 400);
        }

        $lines = [];
        $total = 0;

        foreach ($items as $item) {
            $product_id = isset($item['id']) ? (int) $item['id'] : 0;
            $qty = isset($item['qty']) ? (int) $item['qty'] : 1;

            if ($product_id <= 0 || $qty <= 0 || get_post_type($product_id) !== 'store_product') {
                continue;
            }

            $price = (float) get_post_meta($product_id, '_store_price', true);
            $subtotal = $price * $qty;
            $total += $subtotal;

            $lines[] = [
                'product_id' => $product_id,
                'title' => get_the_title($product_id),
                'qty' => $qty,
                'price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        if (empty($lines)) {
            return new WP_REST_Response(['message' => 'Keranjang kosong'], 400);
        }

        $order_post = [
            'post_type' => 'store_order',
            'post_status' => 'publish',
            'post_title' => $name . ' - ' . current_time('mysql'),
        ];

        $order_id = wp_insert_post($order_post);

        if (is_wp_error($order_id)) {
            return new WP_REST_Response(['message' => 'Gagal membuat pesanan'], 500);
        }

        update_post_meta($order_id, '_store_order_email', $email);
        update_post_meta($order_id, '_store_order_phone', $phone);
        update_post_meta($order_id, '_store_order_total', $total);
        update_post_meta($order_id, '_store_order_items', $lines);

        return new WP_REST_Response([
            'id' => $order_id,
            'total' => $total,
            'message' => 'Pesanan berhasil dibuat',
        ], 201);
    }
}
