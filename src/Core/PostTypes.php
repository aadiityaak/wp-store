<?php

namespace WpStore\Core;

class PostTypes
{
    public function register()
    {
        add_action('init', [$this, 'register_product_type']);
        add_action('init', [$this, 'register_order_type']);
    }

    public function register_product_type()
    {
        $labels_cat = [
            'name' => 'Kategori Produk',
            'singular_name' => 'Kategori Produk',
            'search_items' => 'Cari Kategori',
            'all_items' => 'Semua Kategori',
            'parent_item' => 'Induk Kategori',
            'parent_item_colon' => 'Induk Kategori:',
            'edit_item' => 'Edit Kategori',
            'update_item' => 'Update Kategori',
            'add_new_item' => 'Tambah Kategori Baru',
            'new_item_name' => 'Nama Kategori Baru',
            'menu_name' => 'Kategori',
        ];

        register_taxonomy('store_product_cat', ['store_product'], [
            'hierarchical' => true,
            'labels' => $labels_cat,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'kategori-produk'],
            'show_in_rest' => true,
        ]);

        $labels = [
            'name' => 'Produk',
            'singular_name' => 'Produk',
            'menu_name' => 'Produk',
            'name_admin_bar' => 'Produk',
            'add_new' => 'Tambah Baru',
            'add_new_item' => 'Tambah Produk Baru',
            'new_item' => 'Produk Baru',
            'edit_item' => 'Edit Produk',
            'view_item' => 'Lihat Produk',
            'all_items' => 'Semua Produk',
            'search_items' => 'Cari Produk',
            'parent_item_colon' => 'Induk Produk:',
            'not_found' => 'Tidak ditemukan produk.',
            'not_found_in_trash' => 'Tidak ditemukan di tempat sampah.',
            'featured_image' => 'Gambar Produk',
            'set_featured_image' => 'Atur gambar produk',
            'remove_featured_image' => 'Hapus gambar produk',
            'use_featured_image' => 'Gunakan sebagai gambar produk',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'produk'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 7,
            'menu_icon' => 'dashicons-cart',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
        ];

        register_post_type('store_product', $args);
    }

    public function register_order_type()
    {
        $labels = [
            'name' => 'Pesanan',
            'singular_name' => 'Pesanan',
            'menu_name' => 'Pesanan',
            'name_admin_bar' => 'Pesanan',
            'add_new' => 'Tambah Baru',
            'add_new_item' => 'Tambah Pesanan Baru',
            'new_item' => 'Pesanan Baru',
            'edit_item' => 'Edit Pesanan',
            'view_item' => 'Lihat Pesanan',
            'all_items' => 'Semua Pesanan',
            'search_items' => 'Cari Pesanan',
            'parent_item_colon' => 'Induk Pesanan:',
            'not_found' => 'Tidak ditemukan pesanan.',
            'not_found_in_trash' => 'Tidak ditemukan di tempat sampah.',
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 8,
            'menu_icon' => 'dashicons-clipboard',
            'supports' => ['title', 'editor'],
            'show_in_rest' => false,
        ];

        register_post_type('store_order', $args);
    }
}

