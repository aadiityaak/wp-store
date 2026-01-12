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

        $meta_boxes = new \WpStore\Admin\ProductMetaBoxes();
        $meta_boxes->register();
    }

    private function load_api()
    {
        $products = new \WpStore\Api\ProductController();
        add_action('rest_api_init', [$products, 'register_routes']);

        $cart = new \WpStore\Api\CartController();
        add_action('rest_api_init', [$cart, 'register_routes']);

        $checkout = new \WpStore\Api\CheckoutController();
        add_action('rest_api_init', [$checkout, 'register_routes']);
    }

    private function load_frontend()
    {
        $shortcode = new \WpStore\Frontend\Shortcode();
        $shortcode->register();
    }
}
