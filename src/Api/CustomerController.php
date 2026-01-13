<?php

namespace WpStore\Api;

use WP_REST_Request;
use WP_REST_Response;

class CustomerController
{
    public function register_routes()
    {
        // Profile Endpoints
        register_rest_route('wp-store/v1', '/customer/profile', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_profile'],
                'permission_callback' => [$this, 'check_auth'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'update_profile'],
                'permission_callback' => [$this, 'check_auth'],
            ],
        ]);

        // Address Endpoints (CRUD)
        register_rest_route('wp-store/v1', '/customer/addresses', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_addresses'],
                'permission_callback' => [$this, 'check_auth'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_address'],
                'permission_callback' => [$this, 'check_auth'],
            ],
        ]);

        register_rest_route('wp-store/v1', '/customer/addresses/(?P<id>[\w-]+)', [
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_address'],
                'permission_callback' => [$this, 'check_auth'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_address'],
                'permission_callback' => [$this, 'check_auth'],
            ],
        ]);
    }

    public function check_auth()
    {
        return is_user_logged_in();
    }

    public function get_profile()
    {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        return new WP_REST_Response([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => get_user_meta($user_id, '_store_phone', true),
        ], 200);
    }

    public function update_profile(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        
        $first_name = sanitize_text_field($request->get_param('first_name'));
        $last_name = sanitize_text_field($request->get_param('last_name'));
        $phone = sanitize_text_field($request->get_param('phone'));

        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        ]);

        update_user_meta($user_id, '_store_phone', $phone);

        return new WP_REST_Response(['message' => 'Profil berhasil diperbarui'], 200);
    }

    // --- Address CRUD Logic ---
    // Storing addresses as an array in user meta '_store_addresses'
    // Structure: [{id: 'uniqid', label: 'Home', address: '...', city: '...'}]

    public function get_addresses()
    {
        $user_id = get_current_user_id();
        $addresses = get_user_meta($user_id, '_store_addresses', true);
        
        if (!is_array($addresses)) {
            $addresses = [];
        }

        return new WP_REST_Response($addresses, 200);
    }

    public function create_address(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $addresses = get_user_meta($user_id, '_store_addresses', true);
        if (!is_array($addresses)) {
            $addresses = [];
        }

        $new_address = [
            'id' => uniqid('addr_'),
            'label' => sanitize_text_field($request->get_param('label')),
            'address' => sanitize_textarea_field($request->get_param('address')),
            'city' => sanitize_text_field($request->get_param('city')),
            'postal_code' => sanitize_text_field($request->get_param('postal_code')),
        ];

        array_push($addresses, $new_address);
        update_user_meta($user_id, '_store_addresses', $addresses);

        return new WP_REST_Response($new_address, 201);
    }

    public function update_address(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $address_id = $request->get_param('id');
        $addresses = get_user_meta($user_id, '_store_addresses', true);
        
        if (!is_array($addresses)) {
            return new WP_REST_Response(['message' => 'Address list not found'], 404);
        }

        $found = false;
        foreach ($addresses as &$addr) {
            if ($addr['id'] === $address_id) {
                $addr['label'] = sanitize_text_field($request->get_param('label'));
                $addr['address'] = sanitize_textarea_field($request->get_param('address'));
                $addr['city'] = sanitize_text_field($request->get_param('city'));
                $addr['postal_code'] = sanitize_text_field($request->get_param('postal_code'));
                $found = true;
                break;
            }
        }

        if (!$found) {
            return new WP_REST_Response(['message' => 'Address not found'], 404);
        }

        update_user_meta($user_id, '_store_addresses', $addresses);
        return new WP_REST_Response(['message' => 'Alamat berhasil diperbarui'], 200);
    }

    public function delete_address(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $address_id = $request->get_param('id');
        $addresses = get_user_meta($user_id, '_store_addresses', true);
        
        if (!is_array($addresses)) {
            return new WP_REST_Response(['message' => 'Address list not found'], 404);
        }

        $new_addresses = array_filter($addresses, function($addr) use ($address_id) {
            return $addr['id'] !== $address_id;
        });

        // Re-index array
        $new_addresses = array_values($new_addresses);

        update_user_meta($user_id, '_store_addresses', $new_addresses);
        return new WP_REST_Response(['message' => 'Alamat berhasil dihapus'], 200);
    }
}
