<?php

namespace WpStore\Admin;

class OrderMetaBoxes
{
    public function register()
    {
        add_action('cmb2_admin_init', [$this, 'register_metaboxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('add_meta_boxes', [$this, 'add_proofs_box']);
    }

    public function enqueue_styles()
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'store_order') {
            wp_enqueue_style(
                'wp-store-admin-cmb2',
                WP_STORE_URL . 'assets/admin/css/xmb2.css',
                [],
                WP_STORE_VERSION
            );

            wp_enqueue_script(
                'wp-store-admin-js',
                WP_STORE_URL . 'assets/admin/js/store-admin.js',
                ['jquery'],
                WP_STORE_VERSION,
                true
            );
        }
    }

    public function register_metaboxes()
    {
        $status_box = new_cmb2_box([
            'id'            => 'wp_store_order_status_box',
            'title'         => 'Status Pesanan',
            'object_types'  => ['store_order'],
            'context'       => 'side',
            'priority'      => 'high',
            'show_names'    => true,
        ]);

        $status_box->add_field([
            'name'    => 'Status',
            'id'      => '_store_order_status',
            'type'    => 'select',
            'options' => [
                'pending'           => 'Pending',
                'awaiting_payment'  => 'Menunggu Pembayaran',
                'paid'              => 'Sudah Dibayar',
                'processing'        => 'Diproses',
                'shipped'           => 'Dikirim',
                'completed'         => 'Selesai',
                'cancelled'         => 'Dibatalkan',
            ],
            'default' => 'pending',
        ]);

        $status_box->add_field([
            'name' => 'No. Resi',
            'id'   => '_store_order_tracking_number',
            'type' => 'text',
        ]);

        $details = new_cmb2_box([
            'id'            => 'wp_store_order_details',
            'title'         => 'Detail Pengiriman & Pembayaran',
            'object_types'  => ['store_order'],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ]);

        $details->add_field([
            'name' => 'Kurir',
            'id'   => '_store_order_shipping_courier',
            'type' => 'text',
        ]);

        // Layanan: gunakan select sesuai setting Shipping -> Couriers
        $settings = get_option('wp_store_settings', []);
        $active_couriers = isset($settings['shipping_couriers']) && is_array($settings['shipping_couriers']) ? $settings['shipping_couriers'] : [];
        $courier_labels = [
            'jne'     => 'JNE',
            'sicepat' => 'SiCepat',
            'ide'     => 'IDExpress',
            'sap'     => 'SAP Express',
            'ninja'   => 'Ninja',
            'jnt'     => 'J&T Express',
            'tiki'    => 'TIKI',
            'wahana'  => 'Wahana Express',
            'pos'     => 'POS Indonesia',
            'sentral' => 'Sentral Cargo',
            'lion'    => 'Lion Parcel',
            'rex'     => 'Royal Express Asia',
        ];
        $service_options = [];
        if (!empty($active_couriers)) {
            foreach ($active_couriers as $code) {
                if (isset($courier_labels[$code])) {
                    $service_options[$code] = $courier_labels[$code];
                }
            }
        } else {
            // fallback: tampilkan semua opsi jika belum ada setting
            $service_options = $courier_labels;
        }
        $details->add_field([
            'name'    => 'Layanan',
            'id'      => '_store_order_shipping_courier',
            'type'    => 'select',
            'options' => $service_options,
        ]);

        $details->add_field([
            'name'       => 'Biaya Ongkir',
            'id'         => '_store_order_shipping_cost',
            'type'       => 'text',
            'attributes' => [
                'pattern'     => '^[0-9,.]+$',
                'inputmode'   => 'decimal',
                'placeholder' => 'Contoh: 25000',
            ],
        ]);

        $details->add_field([
            'name'       => 'Grand Total',
            'id'         => '_store_order_total',
            'type'       => 'text',
            'attributes' => [
                'readonly' => 'readonly',
            ],
        ]);

        $details->add_field([
            'name' => 'Catatan Admin',
            'id'   => '_store_order_admin_note',
            'type' => 'textarea_small',
        ]);
    }

    public function add_proofs_box()
    {
        add_meta_box(
            'wp_store_order_proofs',
            'Bukti Transfer',
            [$this, 'render_proofs_box'],
            'store_order',
            'normal',
            'default'
        );
    }

    public function render_proofs_box($post)
    {
        $order_id = isset($post->ID) ? (int) $post->ID : 0;
        if ($order_id <= 0) {
            echo '<p>Tidak ada data.</p>';
            return;
        }
        $proofs = get_post_meta($order_id, '_store_order_payment_proofs', true);
        $proofs = is_array($proofs) ? $proofs : [];
        if (empty($proofs)) {
            echo '<p class="description">Belum ada bukti transfer.</p>';
            return;
        }
        echo '<div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:12px;">';
        foreach ($proofs as $pid) {
            $url = wp_get_attachment_url($pid);
            $mime = get_post_mime_type($pid);
            echo '<div class="wps-card" style="border:1px solid #e5e7eb; border-radius:6px; padding:8px;">';
            if ($mime && strpos($mime, 'image/') === 0) {
                $thumb = wp_get_attachment_image_url($pid, 'medium');
                $thumb = $thumb ?: $url;
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">';
                echo '<img src="' . esc_url($thumb) . '" alt="Bukti Transfer" style="width:100%; height:140px; object-fit:cover;">';
                echo '</a>';
            } else {
                $title = get_the_title($pid);
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener" class="button button-small">Lihat Dokumen</a>';
                echo '<div style="margin-top:6px; font-size:12px; color:#374151;">' . esc_html($title) . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}
