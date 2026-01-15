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

        // Metabox Detail Produk (Normal) with Tabs
        $general = new_cmb2_box([
            'id'            => 'wp_store_product_detail',
            'title'         => 'General',
            'object_types'  => ['store_product'],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
            'vertical_tabs' => true, // Ensure this is supported by your CMB2 setup or use custom CSS/JS
        ]);

        $inventory = new_cmb2_box([
            'id'            => 'wp_store_product_inventory',
            'title'         => 'Inventory',
            'object_types'  => ['store_product'],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
            'vertical_tabs' => true, // Ensure this is supported by your CMB2 setup or use custom CSS/JS
        ]);

        $attributes = new_cmb2_box([
            'id'            => 'wp_store_product_attributes',
            'title'         => 'Attributes',
            'object_types'  => ['store_product'],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
            'vertical_tabs' => true, // Ensure this is supported by your CMB2 setup or use custom CSS/JS
        ]);

        $gallery = new_cmb2_box([
            'id'            => 'wp_store_product_gallery',
            'title'         => 'Gallery',
            'object_types'  => ['store_product'],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
            'vertical_tabs' => true, // Ensure this is supported by your CMB2 setup or use custom CSS/JS
        ]);

        // --- General Tab ---
        $general->add_field([
            'name'    => 'Tipe Produk',
            'id'      => '_store_product_type',
            'type'    => 'select',
            'options' => [
                'physical' => 'Produk Fisik (Basic)',
                'digital'  => 'Produk Digital',
            ],
            'default' => 'physical',
        ]);

        $general->add_field([
            'name'       => 'Harga Regular (Rp)',
            'id'         => '_store_price',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '0.01',
            ],
        ]);

        $general->add_field([
            'name'       => 'Harga Promo (Rp)',
            'id'         => '_store_sale_price',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '0.01',
            ],
        ]);

        $general->add_field([
            'name'       => 'Diskon Sampai',
            'id'         => '_store_flashsale_until',
            'type'       => 'text',
            'attributes' => [
                'type' => 'datetime-local',
            ],
        ]);

        $general->add_field([
            'name' => 'File Produk (Digital)',
            'id'   => '_store_digital_file',
            'type' => 'file',
            'text' => [
                'add_upload_files_text' => 'Upload File',
            ],
        ]);

        // --- Inventory Tab ---
        $inventory->add_field([
            'name' => 'Kode Produk (SKU)',
            'id'   => '_store_sku',
            'type' => 'text',
        ]);

        $inventory->add_field([
            'name'       => 'Stok',
            'id'         => '_store_stock',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '1',
            ],
        ]);

        $inventory->add_field([
            'name'       => 'Minimal Order',
            'id'         => '_store_min_order',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '1',
                'step' => '1',
            ],
        ]);

        $inventory->add_field([
            'name'       => 'Berat Produk (Kg)',
            'id'         => '_store_weight_kg',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '0.01',
            ],
        ]);



        // --- Attributes Tab ---
        $attributes->add_field([
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

        $attributes->add_field([
            'name'        => 'Nama Opsi (Basic)',
            'id'          => '_store_option_name',
            'type'        => 'text',
            'placeholder' => 'Contoh: Pilih Warna',
        ]);

        $attributes->add_field([
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

        $attributes->add_field([
            'name'        => 'Nama Opsi (Advance)',
            'id'          => '_store_option2_name',
            'type'        => 'text',
            'placeholder' => 'Contoh: Pilih Ukuran',
        ]);

        $group_field_id = $attributes->add_field([
            'name'        => 'Opsi Advance',
            'id'          => '_store_advanced_options',
            'type'        => 'group',
            'options'     => [
                'group_title'   => 'Opsi {#}',
                'add_button'    => 'Tambah Opsi',
                'remove_button' => 'Hapus Opsi',
                'sortable'      => true,
            ],
        ]);

        $attributes->add_group_field($group_field_id, [
            'name' => 'Label',
            'id'   => 'label',
            'type' => 'text',
        ]);

        $attributes->add_group_field($group_field_id, [
            'name' => 'Harga',
            'id'   => 'price',
            'type' => 'text',
            'attributes' => [
                'type' => 'number',
            ],
        ]);

        // --- Gallery Tab ---
        $gallery->add_field([
            'name' => 'Gambar Produk',
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
    }
}
