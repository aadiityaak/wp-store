<?php

namespace WpStore\Api;

use WP_REST_Request;
use WP_REST_Response;

class CartController
{
    private $cookie_key = 'wp_store_cart_key';

    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/cart', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_cart'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'upsert_item'],
                'permission_callback' => [$this, 'require_rest_nonce'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'clear_cart'],
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

    public function get_cart(WP_REST_Request $request)
    {
        $cart = $this->read_cart();
        return new WP_REST_Response($this->format_cart($cart), 200);
    }

    public function upsert_item(WP_REST_Request $request)
    {
        $data = $request->get_json_params();
        if (!is_array($data)) {
            $data = [];
        }

        $product_id = isset($data['id']) ? (int) $data['id'] : 0;
        $qty = isset($data['qty']) ? (int) $data['qty'] : 1;

        if ($product_id <= 0 || get_post_type($product_id) !== 'store_product') {
            return new WP_REST_Response(['message' => 'Produk tidak valid'], 400);
        }

        if ($qty < 0) {
            $qty = 0;
        }

        $cart = $this->read_cart();
        $cart = $this->apply_upsert($cart, $product_id, $qty);
        $this->write_cart($cart);

        return new WP_REST_Response($this->format_cart($cart), 200);
    }

    public function clear_cart(WP_REST_Request $request)
    {
        $this->write_cart([]);
        return new WP_REST_Response($this->format_cart([]), 200);
    }

    private function apply_upsert($cart, $product_id, $qty)
    {
        $cart = is_array($cart) ? $cart : [];

        $next = [];
        $found = false;

        foreach ($cart as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $row_qty = isset($row['qty']) ? (int) $row['qty'] : 0;

            if ($id <= 0 || $row_qty <= 0) {
                continue;
            }

            if ($id === (int) $product_id) {
                $found = true;
                if ($qty > 0) {
                    $next[] = ['id' => (int) $product_id, 'qty' => (int) $qty];
                }
                continue;
            }

            $next[] = ['id' => $id, 'qty' => $row_qty];
        }

        if (!$found && $qty > 0) {
            $next[] = ['id' => (int) $product_id, 'qty' => (int) $qty];
        }

        return $next;
    }

    private function read_cart()
    {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $cart = get_user_meta($user_id, '_wp_store_cart', true);
            return is_array($cart) ? $cart : [];
        }

        $key = $this->get_or_set_guest_key();
        $cart = get_transient('wp_store_cart_' . $key);
        return is_array($cart) ? $cart : [];
    }

    private function write_cart($cart)
    {
        $cart = is_array($cart) ? $cart : [];

        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, '_wp_store_cart', $cart);
            return;
        }

        $key = $this->get_or_set_guest_key();
        set_transient('wp_store_cart_' . $key, $cart, DAY_IN_SECONDS * 7);
    }

    private function get_or_set_guest_key()
    {
        if (isset($_COOKIE[$this->cookie_key]) && is_string($_COOKIE[$this->cookie_key]) && $_COOKIE[$this->cookie_key] !== '') {
            return sanitize_key($_COOKIE[$this->cookie_key]);
        }

        $key = sanitize_key(wp_generate_uuid4());

        $secure = is_ssl();
        $path = defined('COOKIEPATH') ? COOKIEPATH : '/';
        $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';

        setcookie($this->cookie_key, $key, time() + (DAY_IN_SECONDS * 30), $path, $domain, $secure, true);
        $_COOKIE[$this->cookie_key] = $key;

        return $key;
    }

    private function format_cart($cart)
    {
        $items = [];
        $total = 0;

        foreach ($cart as $row) {
            $product_id = isset($row['id']) ? (int) $row['id'] : 0;
            $qty = isset($row['qty']) ? (int) $row['qty'] : 0;

            if ($product_id <= 0 || $qty <= 0 || get_post_type($product_id) !== 'store_product') {
                continue;
            }

            $price = (float) get_post_meta($product_id, '_store_price', true);
            $subtotal = $price * $qty;
            $total += $subtotal;

            $items[] = [
                'id' => $product_id,
                'title' => get_the_title($product_id),
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $subtotal,
                'image' => get_the_post_thumbnail_url($product_id, 'thumbnail') ?: null,
                'link' => get_permalink($product_id),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}

