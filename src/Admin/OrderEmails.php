<?php

namespace WpStore\Admin;

class OrderEmails
{
    public function register()
    {
        add_action('updated_post_meta', [$this, 'maybe_send_status_email'], 10, 4);
    }

    public function maybe_send_status_email($meta_id, $post_id, $meta_key, $meta_value)
    {
        if ($meta_key !== '_store_order_status') {
            return;
        }
        if (get_post_type($post_id) !== 'store_order') {
            return;
        }
        $email = get_post_meta($post_id, '_store_order_email', true);
        if (!is_string($email) || $email === '' || !is_email($email)) {
            return;
        }
        $settings = get_option('wp_store_settings', []);
        $store_name = isset($settings['store_name']) && is_string($settings['store_name']) && $settings['store_name'] !== '' ? $settings['store_name'] : get_bloginfo('name');
        $order_number = get_post_meta($post_id, '_store_order_number', true);
        $order_number = $order_number ? $order_number : $post_id;
        $labels = [
            'pending' => 'Pending',
            'awaiting_payment' => 'Menunggu Pembayaran',
            'paid' => 'Sudah Dibayar',
            'processing' => 'Sedang Diproses',
            'shipped' => 'Dikirim',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
        $status_label = isset($labels[$meta_value]) ? $labels[$meta_value] : $meta_value;
        $tracking_id = isset($settings['page_tracking']) ? (int) $settings['page_tracking'] : 0;
        $tracking_url = $tracking_id ? get_permalink($tracking_id) : site_url('/tracking-order/');
        $subject = '[' . $store_name . '] Status Pesanan #' . $order_number . ': ' . $status_label;
        $body = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#111;">'
            . '<p>Halo,</p>'
            . '<p>Status pesanan #' . esc_html($order_number) . ' Anda kini: <strong>' . esc_html($status_label) . '</strong>.</p>'
            . '<p>Anda dapat melihat detail dan riwayat pengiriman melalui tautan berikut:</p>'
            . '<p><a href="' . esc_url(add_query_arg(['order' => $order_number], $tracking_url)) . '" target="_blank" rel="noopener">Lihat Status Pesanan</a></p>'
            . '<p>Salam,<br>' . esc_html($store_name) . '</p>'
            . '</div>';
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($email, $subject, $body, $headers);
    }
}
