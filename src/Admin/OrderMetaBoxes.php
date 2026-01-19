<?php

namespace WpStore\Admin;

class OrderMetaBoxes
{
    public function register()
    {
        add_action('cmb2_admin_init', [$this, 'register_metaboxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
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
}
