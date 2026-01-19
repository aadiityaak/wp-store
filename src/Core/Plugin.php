<?php

namespace WpStore\Core;

class Plugin
{
    public function run()
    {
        $this->load_core();
        $this->load_admin();
        $this->load_api();
        $this->load_frontend();
    }

    private function load_core()
    {
        $post_types = new PostTypes();
        $post_types->register();
    }

    private function load_admin()
    {
        if (!is_admin()) {
            return;
        }

        $admin_menu = new \WpStore\Admin\AdminMenu();
        $admin_menu->register();

        $meta_boxes = new \WpStore\Admin\ProductMetaBoxes();
        $meta_boxes->register();

        $columns = new \WpStore\Admin\ProductColumns();
        $columns->register();

        $settings = new \WpStore\Admin\Settings();
        $settings->register();

        $order_meta = new \WpStore\Admin\OrderMetaBoxes();
        $order_meta->register();

        $order_columns = new \WpStore\Admin\OrderColumns();
        $order_columns->register();

        $order_actions = new \WpStore\Admin\OrderActions();
        $order_actions->register();
    }

    private function load_api()
    {
        $products = new \WpStore\Api\ProductController();
        add_action('rest_api_init', [$products, 'register_routes']);

        $cart = new \WpStore\Api\CartController();
        add_action('rest_api_init', [$cart, 'register_routes']);

        $wishlist = new \WpStore\Api\WishlistController();
        add_action('rest_api_init', [$wishlist, 'register_routes']);

        $checkout = new \WpStore\Api\CheckoutController();
        add_action('rest_api_init', [$checkout, 'register_routes']);

        $customer = new \WpStore\Api\CustomerController();
        add_action('rest_api_init', [$customer, 'register_routes']);

        $settings = new \WpStore\Api\SettingsController();
        add_action('rest_api_init', [$settings, 'register_routes']);
        
        $raja = new \WpStore\Api\RajaOngkirController();
        add_action('rest_api_init', [$raja, 'register_routes']);
        
        $tools = new \WpStore\Api\ToolsController();
        add_action('rest_api_init', [$tools, 'register_routes']);
    }

    private function load_frontend()
    {
        $shortcode = new \WpStore\Frontend\Shortcode();
        $shortcode->register();

        $profile = new \WpStore\Frontend\CustomerProfile();
        $profile->register();

        $page_templates = new \WpStore\Frontend\PageTemplates();
        $page_templates->register();
    }
}
