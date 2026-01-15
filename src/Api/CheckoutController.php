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
        $shipping_courier = isset($data['shipping_courier']) ? sanitize_text_field($data['shipping_courier']) : '';
        $shipping_service = isset($data['shipping_service']) ? sanitize_text_field($data['shipping_service']) : '';
        $shipping_cost = isset($data['shipping_cost']) ? floatval($data['shipping_cost']) : 0;
        $order_total = $total + max(0, $shipping_cost);
        update_post_meta($order_id, '_store_order_total', $order_total);
        update_post_meta($order_id, '_store_order_items', $lines);

        $address = isset($data['address']) ? sanitize_textarea_field($data['address']) : '';
        $province_id = isset($data['province_id']) ? sanitize_text_field($data['province_id']) : '';
        $province_name = isset($data['province_name']) ? sanitize_text_field($data['province_name']) : '';
        $city_id = isset($data['city_id']) ? sanitize_text_field($data['city_id']) : '';
        $city_name = isset($data['city_name']) ? sanitize_text_field($data['city_name']) : '';
        $subdistrict_id = isset($data['subdistrict_id']) ? sanitize_text_field($data['subdistrict_id']) : '';
        $subdistrict_name = isset($data['subdistrict_name']) ? sanitize_text_field($data['subdistrict_name']) : '';
        $postal_code = isset($data['postal_code']) ? sanitize_text_field($data['postal_code']) : '';
        $notes = isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '';

        update_post_meta($order_id, '_store_order_address', $address);
        update_post_meta($order_id, '_store_order_province_id', $province_id);
        update_post_meta($order_id, '_store_order_province_name', $province_name);
        update_post_meta($order_id, '_store_order_city_id', $city_id);
        update_post_meta($order_id, '_store_order_city_name', $city_name);
        update_post_meta($order_id, '_store_order_subdistrict_id', $subdistrict_id);
        update_post_meta($order_id, '_store_order_subdistrict_name', $subdistrict_name);
        update_post_meta($order_id, '_store_order_postal_code', $postal_code);
        update_post_meta($order_id, '_store_order_notes', $notes);
        update_post_meta($order_id, '_store_order_shipping_courier', $shipping_courier);
        update_post_meta($order_id, '_store_order_shipping_service', $shipping_service);
        update_post_meta($order_id, '_store_order_shipping_cost', $shipping_cost);

        return new WP_REST_Response([
            'id' => $order_id,
            'total' => $order_total,
            'message' => 'Pesanan berhasil dibuat',
        ], 201);
    }
}
