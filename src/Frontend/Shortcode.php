<?php

namespace WpStore\Frontend;

class Shortcode
{
    public function register()
    {
        add_shortcode('wp_store_shop', [$this, 'render_shop']);
        add_shortcode('wp_store_single', [$this, 'render_single']);
        add_shortcode('wp_store_related', [$this, 'render_related']);
        add_shortcode('wp_store_thumbnail', [$this, 'render_thumbnail']);
        add_shortcode('wp_store_price', [$this, 'render_price']);
        add_shortcode('wp_store_add_to_cart', [$this, 'render_add_to_cart']);
        add_shortcode('wp_store_detail', [$this, 'render_detail']);
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
        add_shortcode('wp_store_products_carousel', [$this, 'render_products_carousel']);
        add_filter('the_content', [$this, 'filter_single_content']);
        add_filter('template_include', [$this, 'override_archive_template']);
        add_action('pre_get_posts', [$this, 'adjust_archive_query']);
        add_action('template_redirect', [$this, 'redirect_page_conflict']);
    }

    private function resolve_product_id($given_id = 0)
    {
        $id = (int) $given_id;
        if ($id <= 0) {
            $loop_id = get_the_ID();
            if ($loop_id && is_numeric($loop_id)) {
                $id = (int) $loop_id;
            }
        }
        if ($id > 0 && get_post_type($id) !== 'store_product') {
            $meta_pid = (int) get_post_meta($id, 'product_id', true);
            if ($meta_pid > 0) {
                $id = $meta_pid;
            }
        }
        if ($id > 0 && get_post_type($id) !== 'store_product') {
            return 0;
        }
        return $id > 0 ? $id : 0;
    }

    private function get_currency()
    {
        $settings = get_option('wp_store_settings', []);
        return ($settings['currency_symbol'] ?? 'Rp');
    }



    public function filter_single_content($content)
    {
        if (is_singular('store_product') && in_the_loop() && is_main_query()) {
            $id = get_the_ID();
            if (!$id || get_post_type($id) !== 'store_product') {
                return $content;
            }
            $price = get_post_meta($id, '_store_price', true);
            $stock = get_post_meta($id, '_store_stock', true);
            $image = get_the_post_thumbnail_url($id, 'large');
            $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
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
        return $content;
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
        $id = $this->resolve_product_id((int) $atts['id']);
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

    public function render_products_carousel($atts = [])
    {
        wp_enqueue_script('alpinejs');
        $atts = shortcode_atts([
            'label' => '',
            'per_page' => 10,
            'per_row' => 1,
            'img_width' => 200,
            'img_height' => 300,
            'crop' => 'true',
            'autoplay' => 0,
            'pause_on_hover' => 'true',
            'wrap_around' => 'true',
            'page_dots' => 'false',
            'prev_next_buttons' => 'true',
            'lazy_load' => 0,
            'cell_align' => 'center',
            'draggable' => 'true',
            'contain' => 'true'
        ], $atts);
        wp_enqueue_style('wp-store-flickity');
        $per_page = (int) $atts['per_page'];
        if ($per_page <= 0 || $per_page > 20) {
            $per_page = 10;
        }
        $args = [
            'post_type' => 'store_product',
            'posts_per_page' => $per_page,
            'post_status' => 'publish',
        ];
        $query = new \WP_Query($args);
        $currency = $this->get_currency();
        $items = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $price = get_post_meta($id, '_store_price', true);
                $image = get_the_post_thumbnail_url($id, 'medium');
                $items[] = [
                    'id' => $id,
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'image' => $image ? $image : null,
                    'price' => $price !== '' ? (float) $price : null,
                    'stock' => null
                ];
            }
            wp_reset_postdata();
        }
        $html = Template::render('components/products-carousel', [
            'items' => $items,
            'per_row' => (int) $atts['per_row'],
            'currency' => $currency,
            'label' => (string) $atts['label'],
            'img_width' => max(1, (int) $atts['img_width']),
            'img_height' => max(1, (int) $atts['img_height']),
            'crop' => in_array(strtolower((string) $atts['crop']), ['1', 'true', 'yes'], true),
            'opts' => [
                'autoplay' => max(0, (int) $atts['autoplay']),
                'pause_on_hover' => in_array(strtolower((string) $atts['pause_on_hover']), ['1', 'true', 'yes'], true),
                'wrap_around' => in_array(strtolower((string) $atts['wrap_around']), ['1', 'true', 'yes'], true),
                'page_dots' => in_array(strtolower((string) $atts['page_dots']), ['1', 'true', 'yes'], true),
                'prev_next_buttons' => in_array(strtolower((string) $atts['prev_next_buttons']), ['1', 'true', 'yes'], true),
                'lazy_load' => max(0, (int) $atts['lazy_load']),
                'cell_align' => sanitize_key($atts['cell_align']),
                'draggable' => in_array(strtolower((string) $atts['draggable']), ['1', 'true', 'yes'], true),
                'contain' => in_array(strtolower((string) $atts['contain']), ['1', 'true', 'yes'], true),
            ]
        ]);
        wp_enqueue_script('wp-store-frontend');
        return $html;
    }

    public function render_thumbnail($atts = [])
    {
        $atts = shortcode_atts([
            'id' => 0,
            'width' => 300,
            'height' => 300,
            'crop' => 'true',
            'upscale' => 'true',
            'alt' => '',
            'hover' => 'change',
            'label' => 'true'
        ], $atts);
        $id = $this->resolve_product_id((int) $atts['id']);
        if ($id <= 0 || get_post_type($id) !== 'store_product') {
            return '';
        }
        $w = max(1, (int) $atts['width']);
        $h = max(1, (int) $atts['height']);
        $size = [$w, $h];
        $src = get_the_post_thumbnail_url($id, $size);
        if (!$src) {
            $src = WP_STORE_URL . 'assets/frontend/img/noimg.webp';
        }
        $alt = is_string($atts['alt']) && $atts['alt'] !== '' ? $atts['alt'] : get_the_title($id);
        $crop = in_array(strtolower((string) $atts['crop']), ['1', 'true', 'yes'], true);
        $style = 'width:100%; height:100%; object-fit:' . ($crop ? 'cover' : 'contain') . ';';
        $wrap_style = 'width:100%; max-width:' . (int) $w . 'px; aspect-ratio:' . (int) $w . ' / ' . (int) $h . '; overflow:hidden;';
        $hoverMode = sanitize_key($atts['hover']);
        $showLabel = in_array(strtolower((string) $atts['label']), ['1', 'true', 'yes'], true);
        $badgeHtml = '';
        $digitalHtml = '';
        if ($showLabel) {
            $ptype = get_post_meta((int) $id, '_store_product_type', true);
            $is_digital = ($ptype === 'digital') || (bool) get_post_meta((int) $id, '_store_is_digital', true);
            if ($is_digital) {
                $digitalHtml = '<span class="wps-digital-badge wps-text-xs wps-text-white">'
                    . \wps_icon(['name' => 'cloud-download', 'size' => 12, 'stroke_color' => '#ffffff'])
                    . '<span class="txt wps-text-white wps-text-xs">Digital</span>'
                    . '</span>';
            }
            $badgeHtml = \wps_label_badge_html((int) $id);
        }
        if ($hoverMode === 'change') {
            $hover_src = '';
            $gal = get_post_meta((int) $id, '_store_gallery_ids', true);
            if (is_array($gal) && !empty($gal)) {
                $first = array_values($gal)[0];
                if (is_numeric($first)) {
                    $url = wp_get_attachment_image_url((int) $first, $size);
                    if (is_string($url)) $hover_src = $url;
                } elseif (is_string($first)) {
                    $hover_src = $first;
                }
            }
            $wrap_class = 'wps-card-hover';
            $image_wrap_class = 'wps-image-wrap' . ($hover_src ? ' wps-has-hover' : '');
            $html = '<div class="' . esc_attr($wrap_class) . '"><div class="' . esc_attr($image_wrap_class) . '" style="' . esc_attr($wrap_style) . '">';
            $html .= '<img class="wps-rounded img-main" src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" style="' . esc_attr($style) . '">';
            if ($hover_src) {
                $html .= '<img class="wps-rounded img-hover" src="' . esc_url($hover_src) . '" alt="' . esc_attr($alt) . '">';
            }
            if ($digitalHtml) {
                $html .= $digitalHtml;
            }
            if ($badgeHtml) {
                $html .= $badgeHtml;
            }
            $html .= \wps_discount_badge_html((int) $id);
            $html .= '</div></div>';
            return $html;
        }
        return '<div class="wps-image-wrap" style="' . esc_attr($wrap_style) . '"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" style="' . esc_attr($style) . '" class="wps-rounded">' . $digitalHtml . $badgeHtml . \wps_discount_badge_html((int) $id) . '</div>';
    }

    public function render_price($atts)
    {
        $atts = shortcode_atts([
            'id' => 0,
            'countdown' => false
        ], $atts);

        $id = $this->resolve_product_id((int) $atts['id']);
        if ($id <= 0 || get_post_type($id) !== 'store_product') {
            return '';
        }
        $currency = $this->get_currency();
        $price = get_post_meta($id, '_store_price', true);
        $sale = get_post_meta($id, '_store_sale_price', true);
        $price = $price !== '' ? (float) $price : null;
        $sale = $sale !== '' ? (float) $sale : null;
        $countdownAttr = $atts['countdown'];
        $wantCountdown = false;
        if (is_bool($countdownAttr)) {
            $wantCountdown = $countdownAttr;
        } else {
            $wantCountdown = in_array(strtolower((string) $countdownAttr), ['1', 'true', 'yes'], true);
        }
        $untilRaw = (string) get_post_meta($id, '_store_flashsale_until', true);
        $untilTs = $untilRaw ? strtotime($untilRaw) : 0;
        $nowTs = current_time('timestamp');
        $saleActive = $sale !== null && $sale > 0 && (($price !== null && $sale < $price) || $price === null) && ($untilTs === 0 || $untilTs > $nowTs);
        $html = '<div class="wps-price">';
        if ($saleActive) {
            $html .= '<div class="wps-flex wps-items-baseline wps-gap-2">';
            $html .= '<span class="wps-text-lg wps-text-gray-900 wps-font-medium">' . esc_html(($currency ?: 'Rp') . ' ' . number_format($sale, 0, ',', '.')) . '</span>';
            if ($price !== null && $price > 0) {
                $html .= '<span class="wps-text-sm wps-text-gray-500" style="text-decoration: line-through;">' . esc_html(($currency ?: 'Rp') . ' ' . number_format($price, 0, ',', '.')) . '</span>';
            }
            $html .= '</div>';
        } else {
            if ($price !== null) {
                $html .= '<div class="wps-text-lg wps-text-gray-900 wps-font-medium">' . esc_html(($currency ?: 'Rp') . ' ' . number_format($price, 0, ',', '.')) . '</div>';
            } else {
                $html .= '<div class="wps-text-sm wps-text-gray-500">Harga belum diatur.</div>';
            }
        }
        if ($wantCountdown && $untilTs > $nowTs) {
            wp_enqueue_script('alpinejs');
            $endJs = esc_js($untilRaw);
            $html .= '<div class="wps-text-xs wps-text-gray-700 wps-mt-1" x-data="{ end: new Date(\'' . $endJs . '\'), d:0,h:0,m:0,s:0, tick(){ const diff = Math.max(0, this.end - new Date()); this.d = Math.floor(diff/86400000); this.h = Math.floor((diff%86400000)/3600000); this.m = Math.floor((diff%3600000)/60000); this.s = Math.floor((diff%60000)/1000); }, init(){ this.tick(); setInterval(()=>this.tick(), 1000); } }" x-init="init">';
            $html .= '<span>Berakhir dalam </span><span x-text="d"></span><span> hari </span><span x-text="h"></span><span> jam </span><span x-text="m"></span><span> menit </span><span x-text="s"></span><span> detik</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function render_single($atts = [])
    {
        $atts = shortcode_atts([
            'id' => get_the_ID(),
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
            'text' => '',
            'size' => '',
            'class' => 'wps-btn wps-btn-primary'
        ], $atts);
        $size = sanitize_key($atts['size']);
        $base_class = 'wps-btn wps-btn-primary';
        $extra_class = is_string($atts['class']) ? trim($atts['class']) : '';
        $btn_class = trim($base_class . ($size === 'sm' ? ' wps-btn-sm' : '') . ($extra_class ? ' ' . $extra_class : ''));
        $id = $this->resolve_product_id((int) $atts['id']);
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
        $label = (is_string($atts['text']) && $atts['text'] !== '') ? $atts['text'] : $atts['label'];
        return Template::render('components/add-to-cart', [
            'btn_class' => $btn_class,
            'id' => $id,
            'label' => $label,
            'basic_name' => $basic_name ?: '',
            'basic_values' => (is_array($basic_values) ? array_values($basic_values) : []),
            'adv_name' => $adv_name ?: '',
            'adv_values' => (is_array($adv_values) ? array_values($adv_values) : []),
            'nonce' => $nonce
        ]);
    }

    public function render_detail($atts = [])
    {
        $atts = shortcode_atts([
            'id' => 0,
            'text' => 'Detail',
            'size' => '',
            'class' => 'wps-btn wps-btn-secondary wps-w-full'
        ], $atts);
        $size = sanitize_key($atts['size']);
        $base_class = 'wps-btn wps-btn-secondary';
        $extra_class = is_string($atts['class']) ? trim($atts['class']) : '';
        $btn_class = trim($base_class . ($size === 'sm' ? ' wps-btn-sm' : '') . ($extra_class ? ' ' . $extra_class : ''));
        $id = $this->resolve_product_id((int) $atts['id']);
        if ($id <= 0 || get_post_type($id) !== 'store_product') {
            return '';
        }
        $link = get_permalink($id);
        $text = (string) $atts['text'];
        return '<a href="' . esc_url($link) . '" class="' . esc_attr($btn_class) . '">' . \wps_icon(['name' => 'eye', 'size' => 16, 'class' => 'wps-mr-2']) . esc_html($text !== '' ? $text : 'Detail') . '</a>';
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
            $avatar_url = WP_STORE_URL . 'assets/frontend/img/user.png';
        }
        $html = '<a href="' . esc_url($profile_url) . '" class="wps-link-profile" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">'
            . '<img src="' . esc_url($avatar_url) . '" alt="Profil" style="width:32px;height:32px;border-radius:9999px;object-fit:cover;border:1px solid #e5e7eb;" />'
            . '</a>';
        return $html;
    }

    public function override_archive_template($template)
    {
        if (is_post_type_archive('store_product') || (get_query_var('post_type') === 'store_product' && !is_singular())) {
            $tpl = WP_STORE_PATH . 'templates/frontend/archive-store_product.php';
            if (file_exists($tpl)) {
                return $tpl;
            }
        }
        return $template;
    }

    public function adjust_archive_query($query)
    {
        if (is_admin()) return;
        if (!$query->is_main_query()) return;
        if ($query->is_post_type_archive('store_product') || ($query->get('post_type') === 'store_product' && !$query->is_singular())) {
            $query->set('post_status', 'publish');
            $query->set('ignore_sticky_posts', true);
        }
    }

    public function redirect_page_conflict()
    {
        if (is_admin()) return;
        if (is_page()) {
            $page = get_queried_object();
            if ($page && isset($page->post_name) && $page->post_name === 'produk') {
                $n = (int) get_query_var('paged');
                if ($n <= 0) {
                    $n = (int) get_query_var('page');
                }
                $base = get_post_type_archive_link('store_product');
                $produk_page = function_exists('get_page_by_path') ? get_page_by_path('produk') : null;
                if ($produk_page && is_a($produk_page, '\WP_Post') && $base) {
                    if (rtrim($base, '/') === rtrim(home_url('/produk/'), '/')) {
                        $base = home_url('/produk-list/');
                    }
                }
                $target = $base;
                if ($base && $n > 1) {
                    $target = trailingslashit($base) . 'page/' . $n . '/';
                }
                $current = home_url(isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/');
                if ($target && rtrim($target, '/') !== rtrim($current, '/')) {
                    wp_redirect($target, 301);
                    exit;
                }
            }
        }
        if (is_404()) {
            $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            if (strpos($uri, '/produk/') === 0) {
                $n = 0;
                if (preg_match('#/page/(\d+)/#', $uri, $m)) {
                    $n = (int) ($m[1] ?? 0);
                }
                $base = get_post_type_archive_link('store_product');
                $produk_page = function_exists('get_page_by_path') ? get_page_by_path('produk') : null;
                if ($produk_page && is_a($produk_page, '\WP_Post') && $base) {
                    if (rtrim($base, '/') === rtrim(home_url('/produk/'), '/')) {
                        $base = home_url('/produk-list/');
                    }
                }
                if ($base) {
                    $target = $n > 1 ? trailingslashit($base) . 'page/' . $n . '/' : $base;
                    $current = home_url($uri);
                    if (rtrim($target, '/') !== rtrim($current, '/')) {
                        wp_redirect($target, 301);
                        exit;
                    }
                }
            }
        }
    }
}
