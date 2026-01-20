<?php

namespace WpStore\Frontend;

class Shortcode
{
    public function register()
    {
        add_shortcode('wp_store_shop', [$this, 'render_shop']);
        add_shortcode('wp_store_single', [$this, 'render_single']);
        add_shortcode('wp_store_related', [$this, 'render_related']);
        add_shortcode('wp_store_add_to_cart', [$this, 'render_add_to_cart']);
        add_shortcode('wp_store_cart', [$this, 'render_cart_widget']);
        add_shortcode('wp_store_checkout', [$this, 'render_checkout']);
        add_shortcode('store_checkout', [$this, 'render_checkout']);
        add_shortcode('wp_store_thanks', [$this, 'render_thanks']);
        add_shortcode('store_thanks', [$this, 'render_thanks']);
        add_shortcode('wp_store_tracking', [$this, 'render_tracking']);
        add_shortcode('store_tracking', [$this, 'render_tracking']);
        add_shortcode('wp_store_wishlist', [$this, 'render_wishlist']);
        add_shortcode('wp_store_add_to_wishlist', [$this, 'render_add_to_wishlist']);
        add_shortcode('wp_store_link_profile', [$this, 'render_link_profile']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts()
    {
        wp_register_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            null,
            true
        );
        wp_add_inline_script('alpinejs', 'window.deferLoadingAlpineJs = true;', 'before');

        wp_register_script(
            'wp-store-frontend',
            WP_STORE_URL . 'assets/frontend/js/store.js',
            ['alpinejs'],
            WP_STORE_VERSION,
            true
        );

        wp_register_style(
            'wp-store-frontend-css',
            WP_STORE_URL . 'assets/frontend/css/style.css',
            [],
            WP_STORE_VERSION
        );

        wp_enqueue_style('wp-store-frontend-css');

        wp_localize_script(
            'wp-store-frontend',
            'wpStoreSettings',
            [
                'restUrl' => esc_url_raw(rest_url('wp-store/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
                'thanksUrl' => (function () {
                    $settings = get_option('wp_store_settings', []);
                    $pid = isset($settings['page_thanks']) ? absint($settings['page_thanks']) : 0;
                    if ($pid) {
                        $url = get_permalink($pid);
                        if ($url) return esc_url_raw($url);
                    }
                    return esc_url_raw(site_url('/thanks/'));
                })(),
                'trackingUrl' => (function () {
                    $settings = get_option('wp_store_settings', []);
                    $pid = isset($settings['page_tracking']) ? absint($settings['page_tracking']) : 0;
                    if ($pid) {
                        $url = get_permalink($pid);
                        if ($url) return esc_url_raw($url);
                    }
                    return esc_url_raw(site_url('/tracking-order/'));
                })(),
            ]
        );
    }

    public function render_shop($atts = [])
    {
        $atts = shortcode_atts([
            'per_page' => 12,
        ], $atts);

        $per_page = (int) $atts['per_page'];
        if ($per_page <= 0 || $per_page > 50) {
            $per_page = 12;
        }

        $paged = isset($_GET['shop_page']) ? (int) $_GET['shop_page'] : 1;
        if ($paged <= 0) {
            $paged = 1;
        }

        $args = [
            'post_type' => 'store_product',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => 'publish',
        ];

        $query = new \WP_Query($args);
        $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
        $items = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $price = get_post_meta($id, '_store_price', true);
                $stock = get_post_meta($id, '_store_stock', true);
                $image = get_the_post_thumbnail_url($id, 'medium');
                $items[] = [
                    'id' => $id,
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'image' => $image ? $image : null,
                    'price' => $price !== '' ? (float) $price : null,
                    'stock' => $stock !== '' ? (int) $stock : null,
                ];
            }
            wp_reset_postdata();
        }
        return Template::render('pages/shop', [
            'items' => $items,
            'currency' => $currency,
            'page' => (int) $paged,
            'pages' => (int) $query->max_num_pages,
            'total' => (int) $query->found_posts
        ]);
    }

    public function render_checkout($atts = [])
    {
        wp_enqueue_script('alpinejs');
        wp_enqueue_script('wp-store-frontend');
        $settings = get_option('wp_store_settings', []);
        $currency = ($settings['currency_symbol'] ?? 'Rp');
        $origin_subdistrict = isset($settings['shipping_origin_subdistrict']) ? (string) $settings['shipping_origin_subdistrict'] : '';
        $active_couriers = $settings['shipping_couriers'] ?? ['jne', 'sicepat', 'ide'];
        $nonce = wp_create_nonce('wp_rest');
        return Template::render('pages/checkout', [
            'currency' => $currency,
            'origin_subdistrict' => $origin_subdistrict,
            'active_couriers' => $active_couriers,
            'nonce' => $nonce
        ]);
    }

    public function render_thanks($atts = [])
    {
        $settings = get_option('wp_store_settings', []);
        $currency = ($settings['currency_symbol'] ?? 'Rp');
        $order_id = isset($_GET['order']) ? absint($_GET['order']) : 0;
        return Template::render('pages/thanks', [
            'currency' => $currency,
            'order_id' => $order_id,
        ]);
    }

    public function render_tracking($atts = [])
    {
        $settings = get_option('wp_store_settings', []);
        $currency = ($settings['currency_symbol'] ?? 'Rp');
        $order_id = isset($_GET['order']) ? absint($_GET['order']) : 0;
        return Template::render('pages/tracking', [
            'currency' => $currency,
            'order_id' => $order_id,
        ]);
    }

    public function render_related($atts = [])
    {
        $atts = shortcode_atts([
            'id' => 0,
            'per_page' => 4,
        ], $atts);
        $id = (int) $atts['id'];
        if ($id <= 0) {
            $loop_id = get_the_ID();
            if ($loop_id && is_numeric($loop_id)) {
                $id = (int) $loop_id;
            }
        }
        if ($id <= 0 || get_post_type($id) !== 'store_product') {
            return '';
        }
        $per_page = (int) $atts['per_page'];
        if ($per_page <= 0 || $per_page > 12) {
            $per_page = 4;
        }

        $terms = wp_get_post_terms($id, 'store_product_cat', ['fields' => 'ids']);
        if (!is_array($terms) || empty($terms)) {
            return '';
        }
        $args = [
            'post_type' => 'store_product',
            'posts_per_page' => $per_page,
            'post_status' => 'publish',
            'post__not_in' => [$id],
            'tax_query' => [
                [
                    'taxonomy' => 'store_product_cat',
                    'field' => 'term_id',
                    'terms' => $terms,
                ],
            ],
        ];
        $query = new \WP_Query($args);
        $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
        $items = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $rid = get_the_ID();
                $price = get_post_meta($rid, '_store_price', true);
                $image = get_the_post_thumbnail_url($rid, 'medium');
                $items[] = [
                    'id' => $rid,
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'image' => $image ? $image : null,
                    'price' => $price !== '' ? (float) $price : null,
                    'stock' => null
                ];
            }
            wp_reset_postdata();
        }
        return Template::render('pages/related', [
            'items' => $items,
            'currency' => $currency
        ]);
    }

    public function render_single($atts = [])
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);
        $id = (int) $atts['id'];
        if ($id <= 0) {
            $loop_id = get_the_ID();
            if ($loop_id && is_numeric($loop_id)) {
                $id = (int) $loop_id;
            }
        }
        if ($id <= 0 || get_post_type($id) !== 'store_product') {
            return '';
        }
        $price = get_post_meta($id, '_store_price', true);
        $stock = get_post_meta($id, '_store_stock', true);
        $image = get_the_post_thumbnail_url($id, 'large');
        $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
        $content = get_post_field('post_content', $id);
        $content = apply_filters('the_content', $content);
        return Template::render('pages/single', [
            'id' => $id,
            'title' => get_the_title($id),
            'image' => $image ? $image : null,
            'price' => $price !== '' ? (float) $price : null,
            'stock' => $stock !== '' ? (int) $stock : null,
            'currency' => $currency,
            'content' => $content
        ]);
    }


    public function render_add_to_cart($atts = [])
    {
        wp_enqueue_script('alpinejs');
        $atts = shortcode_atts([
            'id' => 0,
            'label' => '+',
            'size' => ''
        ], $atts);
        $size = sanitize_key($atts['size']);
        $btn_class = 'wps-btn wps-btn-primary' . ($size === 'sm' ? ' wps-btn-sm' : '');
        $id = (int) $atts['id'];
        if ($id <= 0) {
            $loop_id = get_the_ID();
            if ($loop_id && is_numeric($loop_id)) {
                $id = (int) $loop_id;
            }
        }
        if ($id > 0 && get_post_type($id) !== 'store_product') {
            return '';
        }
        if ($id <= 0) {
            return '';
        }
        $basic_name = get_post_meta($id, '_store_option_name', true);
        $basic_values = get_post_meta($id, '_store_options', true);
        $adv_name = get_post_meta($id, '_store_option2_name', true);
        $adv_values = get_post_meta($id, '_store_advanced_options', true);
        $nonce = wp_create_nonce('wp_rest');
        return Template::render('components/add-to-cart', [
            'btn_class' => $btn_class,
            'id' => $id,
            'label' => $atts['label'],
            'basic_name' => $basic_name ?: '',
            'basic_values' => (is_array($basic_values) ? array_values($basic_values) : []),
            'adv_name' => $adv_name ?: '',
            'adv_values' => (is_array($adv_values) ? array_values($adv_values) : []),
            'nonce' => $nonce
        ]);
    }

    public function render_cart_widget($atts = [])
    {
        wp_enqueue_script('alpinejs');
        $settings = get_option('wp_store_settings', []);
        $checkout_page_id = isset($settings['page_checkout']) ? absint($settings['page_checkout']) : 0;
        $checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : '';
        $currency = ($settings['currency_symbol'] ?? 'Rp');
        $nonce = wp_create_nonce('wp_rest');
        return Template::render('components/cart-widget', [
            'checkout_url' => $checkout_url,
            'currency' => $currency,
            'nonce' => $nonce
        ]);
    }

    public function render_wishlist($atts = [])
    {
        wp_enqueue_script('alpinejs');
        $settings = get_option('wp_store_settings', []);
        $currency = ($settings['currency_symbol'] ?? 'Rp');
        $nonce = wp_create_nonce('wp_rest');
        return Template::render('components/wishlist-widget', [
            'currency' => $currency,
            'nonce' => $nonce
        ]);
    }

    public function render_add_to_wishlist($atts = [])
    {
        wp_enqueue_script('alpinejs');
        $atts = shortcode_atts([
            'id' => 0,
            'size' => '',
            'label_add' => 'Wishlist',
            'label_remove' => 'Hapus',
            'icon_only' => '0',
        ], $atts);
        $size = sanitize_key($atts['size']);
        $btn_class = 'wps-btn wps-btn-secondary' . ($size === 'sm' ? ' wps-btn-sm' : '');
        $icon_only = (string) $atts['icon_only'] === '1';
        $id = (int) $atts['id'];
        if ($id <= 0) {
            $loop_id = get_the_ID();
            if ($loop_id && is_numeric($loop_id)) {
                $id = (int) $loop_id;
            }
        }
        if ($id > 0 && get_post_type($id) !== 'store_product') {
            return '';
        }
        if ($id <= 0) {
            return '';
        }
        $nonce = wp_create_nonce('wp_rest');
        return Template::render('components/add-to-wishlist', [
            'btn_class' => $btn_class,
            'id' => $id,
            'label_add' => $atts['label_add'],
            'label_remove' => $atts['label_remove'],
            'icon_only' => $icon_only,
            'nonce' => $nonce
        ]);
    }

    public function render_link_profile($atts = [])
    {
        $settings = get_option('wp_store_settings', []);
        $pid = isset($settings['page_profile']) ? absint($settings['page_profile']) : 0;
        $profile_url = $pid ? get_permalink($pid) : site_url('/profil-saya/');
        $href = is_user_logged_in() ? $profile_url : wp_login_url($profile_url);
        $avatar_url = '';
        if (is_user_logged_in()) {
            $uid = get_current_user_id();
            $aid = (int) get_user_meta($uid, '_store_avatar_id', true);
            $avatar_url = $aid ? wp_get_attachment_image_url($aid, 'thumbnail') : '';
            if (!$avatar_url && function_exists('get_avatar_url')) {
                $avatar_url = get_avatar_url($uid);
            }
        }
        if (!$avatar_url) {
            $avatar_url = WP_STORE_URL . 'assets/frontend/img/noimg.webp';
        }
        $html = '<a href="' . esc_url($href) . '" class="wps-link-profile" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">'
            . '<img src="' . esc_url($avatar_url) . '" alt="Profil" style="width:32px;height:32px;border-radius:9999px;object-fit:cover;border:1px solid #e5e7eb;" />'
            . '</a>';
        return $html;
    }
}
