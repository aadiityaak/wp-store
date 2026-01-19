<?php

namespace WpStore\Admin;

class OrderColumns
{
    public function register()
    {
        add_filter('manage_store_order_posts_columns', [$this, 'add_columns']);
        add_action('manage_store_order_posts_custom_column', [$this, 'render_columns'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_columns($columns)
    {
        $new = [];
        foreach ($columns as $key => $label) {
            if ($key === 'date') {
                continue;
            }
            // Insert Status right after Title
            $new[$key] = $label;
            if ($key === 'title') {
                $new['order_status'] = 'Status';
                $new['order_payment'] = 'Metode Pembayaran';
                $new['order_tracking'] = 'Tracking';
            }
        }
        return $new;
    }

    public function render_columns($column, $post_id)
    {
        if ($column === 'order_status') {
            $status = get_post_meta($post_id, '_store_order_status', true);
            $labels = [
                'pending' => 'Pending',
                'awaiting_payment' => 'Menunggu Pembayaran',
                'paid' => 'Sudah Dibayar',
                'processing' => 'Sedang Diproses',
                'shipped' => 'Dikirim',
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan',
            ];
            $nonce = wp_create_nonce('wp_store_update_order_status');
            echo '<select class="wps-order-status-select" data-order-id="' . esc_attr($post_id) . '" data-nonce="' . esc_attr($nonce) . '">';
            foreach ($labels as $key => $label) {
                $selected = ($key === $status) ? ' selected' : '';
                echo '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            return;
        }
        if ($column === 'order_tracking') {
            $settings = get_option('wp_store_settings', []);
            $tracking_id = isset($settings['page_tracking']) ? (int) $settings['page_tracking'] : 0;
            $tracking_url = $tracking_id ? get_permalink($tracking_id) : site_url('/tracking-order/');
            if ($tracking_url) {
                $url = add_query_arg(['order' => $post_id], $tracking_url);
                echo '<a class="button button-small" href="' . esc_url($url) . '" target="_blank" rel="noopener">Buka Tracking</a>';
            } else {
                echo '-';
            }
            return;
        }
        if ($column === 'order_payment') {
            $method = get_post_meta($post_id, '_store_order_payment_method', true);
            $label = ($method === 'qris') ? 'QRIS' : 'Transfer Bank';
            echo esc_html($label);
            return;
        }
    }

    public function enqueue_scripts()
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'store_order' && $screen->base === 'edit') {
            wp_enqueue_style(
                'wp-store-order-columns',
                WP_STORE_URL . 'assets/admin/css/order-columns.css',
                [],
                WP_STORE_VERSION
            );
            wp_enqueue_script(
                'wp-store-order-columns',
                WP_STORE_URL . 'assets/admin/js/order-columns.js',
                ['jquery'],
                WP_STORE_VERSION,
                true
            );
            wp_localize_script('wp-store-order-columns', 'wpStoreOrderColumns', [
                'ajaxUrl' => admin_url('admin-ajax.php')
            ]);
        }
    }
}
