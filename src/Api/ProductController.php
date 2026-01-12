<?php

namespace WpStore\Api;

use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

class ProductController
{
    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/products', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_products'],
                'permission_callback' => '__return_true',
            ],
        ]);

        register_rest_route('wp-store/v1', '/products/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_product'],
                'permission_callback' => '__return_true',
                'args' => [
                    'id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        },
                    ],
                ],
            ],
        ]);
    }

    public function get_products(WP_REST_Request $request)
    {
        $per_page = (int) $request->get_param('per_page');
        if ($per_page <= 0 || $per_page > 50) {
            $per_page = 12;
        }

        $paged = (int) $request->get_param('page');
        if ($paged <= 0) {
            $paged = 1;
        }

        $args = [
            'post_type' => 'store_product',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => 'publish',
        ];

        $search = $request->get_param('search');
        if (is_string($search) && $search !== '') {
            $args['s'] = $search;
        }

        $category = $request->get_param('category');
        if (is_string($category) && $category !== '') {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'store_product_cat',
                    'field' => 'slug',
                    'terms' => $category,
                ],
            ];
        }

        $query = new WP_Query($args);

        $items = [];
        foreach ($query->posts as $post) {
            $items[] = $this->format_product($post->ID);
        }

        $response = [
            'items' => $items,
            'total' => (int) $query->found_posts,
            'pages' => (int) $query->max_num_pages,
            'page' => $paged,
        ];

        return new WP_REST_Response($response, 200);
    }

    public function get_product(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        if ($id <= 0 || get_post_type($id) !== 'store_product') {
            return new WP_REST_Response(['message' => 'Produk tidak ditemukan'], 404);
        }

        return new WP_REST_Response($this->format_product($id), 200);
    }

    private function format_product($id)
    {
        $price = get_post_meta($id, '_store_price', true);
        $stock = get_post_meta($id, '_store_stock', true);
        $image = get_the_post_thumbnail_url($id, 'medium');

        return [
            'id' => $id,
            'title' => get_the_title($id),
            'slug' => get_post_field('post_name', $id),
            'excerpt' => wp_trim_words(get_post_field('post_content', $id), 20),
            'price' => $price !== '' ? (float) $price : null,
            'stock' => $stock !== '' ? (int) $stock : null,
            'image' => $image ? $image : null,
            'link' => get_permalink($id),
        ];
    }
}

