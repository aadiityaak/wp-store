<?php

namespace WpStore\Admin;

class AdminMenu
{
    public function register()
    {
        add_action('admin_menu', [$this, 'add_main_menu']);
    }

    public function add_main_menu()
    {
        // Add top-level menu
        add_menu_page(
            'WP Store',
            'WP Store',
            'manage_options',
            'wp-store',
            [$this, 'render_dashboard'],
            'dashicons-store',
            30
        );

        // Add Dashboard submenu (so it appears first and is named "Dashboard")
        add_submenu_page(
            'wp-store',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'wp-store',
            [$this, 'render_dashboard']
        );
    }

    public function render_dashboard()
    {
        ?>
        <div class="wrap">
            <h1>WP Store Dashboard</h1>
            <p>Selamat datang di WP Store. Gunakan menu di sebelah kiri untuk mengelola toko Anda.</p>
        </div>
        <?php
    }
}
