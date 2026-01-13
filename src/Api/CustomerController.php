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
            'province_id' => sanitize_text_field($request->get_param('province_id')),
            'province_name' => sanitize_text_field($request->get_param('province_name')),
            'city_id' => sanitize_text_field($request->get_param('city_id')),
            'city_name' => sanitize_text_field($request->get_param('city_name')),
            'subdistrict_id' => sanitize_text_field($request->get_param('subdistrict_id')),
            'subdistrict_name' => sanitize_text_field($request->get_param('subdistrict_name')),
            'postal_code' => sanitize_text_field($request->get_param('postal_code')),
        ];

        // Fallback for legacy 'city' field usage in display if needed, 
        // though frontend should now use city_name.
        // We'll keep 'city' as an alias to 'city_name' or just rely on 'city_name'.
        // Let's explicitly add 'city' for backward compatibility if any.
        $new_address['city'] = $new_address['city_name'];

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
                
                $addr['province_id'] = sanitize_text_field($request->get_param('province_id'));
                $addr['province_name'] = sanitize_text_field($request->get_param('province_name'));
                $addr['city_id'] = sanitize_text_field($request->get_param('city_id'));
                $addr['city_name'] = sanitize_text_field($request->get_param('city_name'));
                $addr['subdistrict_id'] = sanitize_text_field($request->get_param('subdistrict_id'));
                $addr['subdistrict_name'] = sanitize_text_field($request->get_param('subdistrict_name'));
                
                $addr['postal_code'] = sanitize_text_field($request->get_param('postal_code'));
                
                // Backwards compat
                $addr['city'] = $addr['city_name'];
                
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
