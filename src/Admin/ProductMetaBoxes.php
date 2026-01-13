<?php

namespace WpStore\Admin;

class ProductMetaBoxes
{
    public function register()
    {
        add_action('cmb2_admin_init', [$this, 'register_metaboxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles()
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'store_product') {
            wp_enqueue_style(
                'wp-store-admin-cmb2',
                WP_STORE_URL . 'assets/admin/css/xmb2.css',
                [],
                WP_STORE_VERSION
            );
        }
    }

    public function register_metaboxes()
    {
        // Metabox Harga & Stok (Side)
        $pricing = new_cmb2_box([
            'id'            => 'wp_store_product_pricing',
            'title'         => 'Harga & Stok',
            'object_types'  => ['store_product'],
            'context'       => 'side',
            'priority'      => 'default',
        ]);

        $pricing->add_field([
            'name'       => 'Harga',
            'id'         => '_store_price',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '0.01',
            ],
        ]);

        $pricing->add_field([
            'name'       => 'Stok',
            'id'         => '_store_stock',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '1',
            ],
        ]);

        // Metabox Detail Produk (Normal)
        $detail = new_cmb2_box([
            'id'            => 'wp_store_product_detail',
            'title'         => 'Detail Produk',
            'object_types'  => ['store_product'],
            'context'       => 'normal',
            'priority'      => 'high',
        ]);

        $detail->add_field([
            'name'    => 'Label Produk',
            'id'      => '_store_label',
            'type'    => 'select',
            'options' => [
                ''              => '-',
                'label-best'    => 'Best Seller',
                'label-limited' => 'Limited',
                'label-new'     => 'New',
            ],
        ]);

        $detail->add_field([
            'name' => 'Kode Produk (SKU)',
            'id'   => '_store_sku',
            'type' => 'text',
        ]);

        $detail->add_field([
            'name'       => 'Harga Promo (Rp)',
            'id'         => '_store_sale_price',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '0.01',
            ],
        ]);

        $detail->add_field([
            'name'       => 'Minimal Order',
            'id'         => '_store_min_order',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '1',
                'step' => '1',
            ],
        ]);

        $detail->add_field([
            'name'       => 'Diskon Sampai',
            'id'         => '_store_flashsale_until',
            'type'       => 'text',
            'attributes' => [
                'type' => 'datetime-local',
            ],
        ]);

        $detail->add_field([
            'name'       => 'Berat Produk (Kg)',
            'id'         => '_store_weight_kg',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '0.01',
            ],
        ]);

        $detail->add_field([
            'name' => 'Gallery',
            'id'   => '_store_gallery_ids',
            'type' => 'file_list',
            'text' => [
                'add_upload_files_text' => 'Tambah Gambar',
                'remove_image_text'     => 'Hapus',
                'file_text'             => 'Gambar',
                'file_download_text'    => 'Download',
                'remove_text'           => 'Hapus',
            ],
        ]);

        $detail->add_field([
            'name'        => 'Nama Opsi (Basic)',
            'id'          => '_store_option_name',
            'type'        => 'text',
            'placeholder' => 'Contoh: Pilih Warna',
        ]);

        $detail->add_field([
            'name'        => 'Opsi Basic',
            'id'          => '_store_options',
            'type'        => 'text',
            'repeatable'  => true,
            'text'        => [
                'add_row_text' => 'Tambah Opsi',
            ],
            'attributes'  => [
                'placeholder' => 'Contoh: merah, biru, hijau',
            ],
        ]);

        $detail->add_field([
            'name'        => 'Nama Opsi (Advance)',
            'id'          => '_store_option2_name',
            'type'        => 'text',
            'placeholder' => 'Contoh: Pilih Ukuran',
        ]);

        $detail->add_field([
            'name'        => 'Opsi Advance',
            'id'          => '_store_price_options',
            'type'        => 'text',
            'repeatable'  => true,
            'text'        => [
                'add_row_text' => 'Tambah Opsi',
            ],
            'attributes'  => [
                'placeholder' => 'Contoh: XL=250000',
            ],
        ]);
    }
}
