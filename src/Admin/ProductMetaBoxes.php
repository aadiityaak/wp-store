<?php

namespace WpStore\Admin;

class ProductMetaBoxes
{
    public function register()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_store_product', [$this, 'save_product_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    private function get_pricing_fields()
    {
        return [
            [
                'name' => 'Harga',
                'id' => 'wp_store_price',
                'meta_key' => '_store_price',
                'type' => 'number',
                'min' => 0,
                'step' => '0.01',
            ],
            [
                'name' => 'Stok',
                'id' => 'wp_store_stock',
                'meta_key' => '_store_stock',
                'type' => 'number',
                'min' => 0,
                'step' => '1',
            ],
        ];
    }

    private function get_detail_fields()
    {
        return [
            [
                'name' => 'Label Produk',
                'id' => 'wp_store_label',
                'meta_key' => '_store_label',
                'type' => 'select',
                'options' => [
                    '' => '-',
                    'label-best' => 'Best Seller',
                    'label-limited' => 'Limited',
                    'label-new' => 'New',
                ],
                'allowed' => ['label-best', 'label-limited', 'label-new'],
            ],
            [
                'name' => 'Kode Produk (SKU)',
                'id' => 'wp_store_sku',
                'meta_key' => '_store_sku',
                'type' => 'text',
            ],
            [
                'name' => 'Harga Promo (Rp)',
                'id' => 'wp_store_sale_price',
                'meta_key' => '_store_sale_price',
                'type' => 'number',
                'min' => 0,
                'step' => '0.01',
            ],
            [
                'name' => 'Minimal Order',
                'id' => 'wp_store_min_order',
                'meta_key' => '_store_min_order',
                'type' => 'number',
                'min' => 1,
                'step' => '1',
            ],
            [
                'name' => 'Diskon Sampai',
                'id' => 'wp_store_flashsale_until',
                'meta_key' => '_store_flashsale_until',
                'type' => 'datetime-local',
            ],
            [
                'name' => 'Berat Produk (Kg)',
                'id' => 'wp_store_weight_kg',
                'meta_key' => '_store_weight_kg',
                'type' => 'number',
                'min' => 0,
                'step' => '0.01',
            ],
            [
                'name' => 'Gallery',
                'id' => 'wp_store_gallery_ids',
                'meta_key' => '_store_gallery_ids',
                'type' => 'gallery',
            ],
            [
                'name' => 'Nama Opsi (Basic)',
                'id' => 'wp_store_option_name',
                'meta_key' => '_store_option_name',
                'type' => 'text',
                'placeholder' => 'Contoh: Pilih Warna',
            ],
            [
                'name' => 'Opsi Basic (1 baris = 1 opsi)',
                'id' => 'wp_store_options',
                'meta_key' => '_store_options',
                'type' => 'textarea_lines',
                'rows' => 5,
            ],
            [
                'name' => 'Nama Opsi (Advance)',
                'id' => 'wp_store_option2_name',
                'meta_key' => '_store_option2_name',
                'type' => 'text',
                'placeholder' => 'Contoh: Pilih Ukuran',
            ],
            [
                'name' => 'Opsi Advance (Format: Nama=Harga)',
                'id' => 'wp_store_price_options',
                'meta_key' => '_store_price_options',
                'type' => 'textarea_lines',
                'rows' => 5,
            ],
        ];
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'wp_store_product_pricing',
            'Harga & Stok',
            [$this, 'render_pricing_box'],
            'store_product',
            'side',
            'default'
        );

        add_meta_box(
            'wp_store_product_detail',
            'Detail Produk',
            [$this, 'render_detail_box'],
            'store_product',
            'normal',
            'high'
        );
    }

    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !isset($screen->post_type) || $screen->post_type !== 'store_product') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'wp-store-product-gallery',
            WP_STORE_URL . 'assets/js/admin/product-gallery.js',
            ['jquery'],
            WP_STORE_VERSION,
            true
        );
    }

    public function render_pricing_box($post)
    {
        wp_nonce_field('wp_store_save_product_meta', 'wp_store_product_meta_nonce');
        $this->render_fields($post->ID, $this->get_pricing_fields(), [
            'columns' => 1,
            'grid' => false,
        ]);
    }

    public function render_detail_box($post)
    {
        wp_nonce_field('wp_store_save_product_meta', 'wp_store_product_meta_nonce');
        $this->render_fields($post->ID, $this->get_detail_fields(), [
            'columns' => 2,
            'grid' => true,
        ]);
    }

    public function save_product_meta($post_id)
    {
        if (!isset($_POST['wp_store_product_meta_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['wp_store_product_meta_nonce'], 'wp_store_save_product_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array_merge($this->get_pricing_fields(), $this->get_detail_fields());
        foreach ($fields as $field) {
            $this->save_field_value($post_id, $field);
        }
    }

    private function render_fields($post_id, $fields, $layout = [])
    {
        $columns = isset($layout['columns']) ? (int) $layout['columns'] : 1;
        if ($columns <= 0) {
            $columns = 1;
        }

        $grid = isset($layout['grid']) ? (bool) $layout['grid'] : false;

        if ($grid) {
            echo '<div style="display:grid;grid-template-columns:repeat(' . esc_attr($columns) . ',minmax(0,1fr));gap:12px;">';
        }

        foreach ($fields as $field) {
            $type = isset($field['type']) ? (string) $field['type'] : 'text';

            if ($type === 'gallery') {
                if ($grid) {
                    echo '</div>';
                }
                echo '<hr />';
                $this->render_gallery_field($post_id, $field);
                echo '<hr />';
                if ($grid) {
                    echo '<div style="display:grid;grid-template-columns:repeat(' . esc_attr($columns) . ',minmax(0,1fr));gap:12px;">';
                }
                continue;
            }

            $full_width = $type === 'textarea_lines';

            if ($grid) {
                $style = $full_width ? ' style="margin:0;grid-column:1 / -1;"' : ' style="margin:0;"';
                echo '<p' . $style . '>';
            } else {
                echo '<p>';
            }

            $this->render_field($post_id, $field);
            echo '</p>';
        }

        if ($grid) {
            echo '</div>';
        }
    }

    private function render_field($post_id, $field)
    {
        $id = isset($field['id']) ? (string) $field['id'] : '';
        $type = isset($field['type']) ? (string) $field['type'] : 'text';
        $name = isset($field['name']) ? (string) $field['name'] : '';
        $meta_key = isset($field['meta_key']) ? (string) $field['meta_key'] : '';

        $value = $meta_key !== '' ? get_post_meta($post_id, $meta_key, true) : '';

        echo '<label for="' . esc_attr($id) . '" style="display:block;margin-bottom:4px;">' . esc_html($name) . '</label>';

        if ($type === 'select') {
            $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : [];
            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" style="width:100%;">';
            foreach ($options as $opt_value => $opt_label) {
                $selected = ((string) $value === (string) $opt_value) ? ' selected' : '';
                echo '<option value="' . esc_attr($opt_value) . '"' . $selected . '>' . esc_html($opt_label) . '</option>';
            }
            echo '</select>';
            return;
        }

        if ($type === 'textarea_lines') {
            $rows = isset($field['rows']) ? (int) $field['rows'] : 5;
            if ($rows <= 0) {
                $rows = 5;
            }
            $text = is_array($value) ? implode("\n", array_map('strval', $value)) : '';
            echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" rows="' . esc_attr($rows) . '" style="width:100%;">' . esc_textarea($text) . '</textarea>';
            return;
        }

        $min = isset($field['min']) ? $field['min'] : null;
        $step = isset($field['step']) ? $field['step'] : null;
        $placeholder = isset($field['placeholder']) ? (string) $field['placeholder'] : '';

        $attrs = '';
        if ($min !== null && $min !== '') {
            $attrs .= ' min="' . esc_attr($min) . '"';
        }
        if ($step !== null && $step !== '') {
            $attrs .= ' step="' . esc_attr($step) . '"';
        }
        if ($placeholder !== '') {
            $attrs .= ' placeholder="' . esc_attr($placeholder) . '"';
        }

        $input_type = $type;
        if ($type !== 'text' && $type !== 'number' && $type !== 'datetime-local') {
            $input_type = 'text';
        }

        echo '<input type="' . esc_attr($input_type) . '" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="' . esc_attr(is_string($value) ? $value : '') . '" style="width:100%;"' . $attrs . ' />';
    }

    private function render_gallery_field($post_id, $field)
    {
        $id = isset($field['id']) ? (string) $field['id'] : 'wp_store_gallery_ids';
        $meta_key = isset($field['meta_key']) ? (string) $field['meta_key'] : '_store_gallery_ids';

        $gallery_ids = get_post_meta($post_id, $meta_key, true);
        $gallery_ids = is_string($gallery_ids) ? $gallery_ids : '';

        echo '<div>';
        echo '<div style="display:flex;align-items:center;gap:8px;justify-content:space-between;">';
        echo '<strong>Gallery</strong>';
        echo '<button type="button" class="button" id="wp_store_add_gallery">Tambah Gambar</button>';
        echo '</div>';
        echo '<input type="hidden" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="' . esc_attr($gallery_ids) . '" />';
        echo '<div id="wp_store_gallery_container" style="margin-top:10px;">';

        $ids = $gallery_ids !== '' ? array_filter(array_map('intval', explode(',', $gallery_ids))) : [];
        foreach ($ids as $attachment_id) {
            $thumb = wp_get_attachment_image_url($attachment_id, 'thumbnail');
            if (!$thumb) {
                continue;
            }
            echo '<div class="wp-store-gallery-item" data-id="' . esc_attr($attachment_id) . '" style="display:inline-block;margin:5px;position:relative;">';
            echo '<img src="' . esc_url($thumb) . '" style="width:100px;height:100px;object-fit:cover;border:1px solid #ccc;" />';
            echo '<button type="button" class="wp-store-remove-image" style="position:absolute;top:0;right:0;background:red;color:white;border:none;cursor:pointer;padding:2px 6px;line-height:1;">×</button>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    private function save_field_value($post_id, $field)
    {
        $id = isset($field['id']) ? (string) $field['id'] : '';
        $meta_key = isset($field['meta_key']) ? (string) $field['meta_key'] : '';
        $type = isset($field['type']) ? (string) $field['type'] : 'text';

        if ($id === '' || $meta_key === '') {
            return;
        }

        $raw = isset($_POST[$id]) ? wp_unslash($_POST[$id]) : null;

        if ($type === 'textarea_lines') {
            $arr = $this->parse_lines_to_array(is_string($raw) ? $raw : '');
            if (!empty($arr)) {
                update_post_meta($post_id, $meta_key, $arr);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
            return;
        }

        if ($type === 'gallery') {
            $csv = sanitize_text_field(is_string($raw) ? $raw : '');
            $ids = $this->sanitize_csv_ids($csv);
            if (!empty($ids)) {
                update_post_meta($post_id, $meta_key, implode(',', $ids));
            } else {
                delete_post_meta($post_id, $meta_key);
            }
            return;
        }

        if ($type === 'select') {
            $val = sanitize_text_field(is_string($raw) ? $raw : '');
            $allowed = isset($field['allowed']) && is_array($field['allowed']) ? $field['allowed'] : [];
            if ($val !== '' && !empty($allowed) && in_array($val, $allowed, true)) {
                update_post_meta($post_id, $meta_key, $val);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
            return;
        }

        if ($type === 'number') {
            $val = sanitize_text_field(is_string($raw) ? $raw : '');
            if ($val !== '' && is_numeric($val)) {
                $min = isset($field['min']) ? $field['min'] : null;
                $num = (float) $val;
                if ($min !== null && $min !== '' && is_numeric($min) && $num < (float) $min) {
                    $num = (float) $min;
                }
                update_post_meta($post_id, $meta_key, (string) $num);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
            return;
        }

        $val = sanitize_text_field(is_string($raw) ? $raw : '');
        if ($val !== '') {
            update_post_meta($post_id, $meta_key, $val);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }

    private function sanitize_csv_ids($csv)
    {
        $ids = [];
        if (!is_string($csv) || $csv === '') {
            return [];
        }

        foreach (explode(',', $csv) as $id) {
            $id = (int) trim((string) $id);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));
        return $ids;
    }

    private function parse_lines_to_array($text)
    {
        if (!is_string($text) || $text === '') {
            return [];
        }

        $lines = preg_split("/\r\n|\n|\r/", $text);
        if (!is_array($lines)) {
            return [];
        }

        $out = [];
        foreach ($lines as $line) {
            $line = sanitize_text_field($line);
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $out[] = $line;
        }

        return $out;
    }
}
