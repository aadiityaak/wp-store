<?php

namespace WpStore\Admin;

class ProductMetaBoxes
{
    public function register()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_store_product', [$this, 'save_product_meta']);
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
    }

    public function render_pricing_box($post)
    {
        wp_nonce_field('wp_store_save_product_meta', 'wp_store_product_meta_nonce');

        $price = get_post_meta($post->ID, '_store_price', true);
        $stock = get_post_meta($post->ID, '_store_stock', true);
        ?>
        <p>
            <label for="wp_store_price" style="display:block;margin-bottom:4px;">Harga</label>
            <input
                type="number"
                step="0.01"
                min="0"
                id="wp_store_price"
                name="wp_store_price"
                value="<?php echo esc_attr($price); ?>"
                style="width:100%;"
            />
        </p>
        <p>
            <label for="wp_store_stock" style="display:block;margin-bottom:4px;">Stok</label>
            <input
                type="number"
                step="1"
                min="0"
                id="wp_store_stock"
                name="wp_store_stock"
                value="<?php echo esc_attr($stock); ?>"
                style="width:100%;"
            />
        </p>
        <?php
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

        $price = isset($_POST['wp_store_price']) ? sanitize_text_field(wp_unslash($_POST['wp_store_price'])) : '';
        $stock = isset($_POST['wp_store_stock']) ? sanitize_text_field(wp_unslash($_POST['wp_store_stock'])) : '';

        if ($price !== '' && is_numeric($price)) {
            update_post_meta($post_id, '_store_price', (string) ((float) $price));
        } else {
            delete_post_meta($post_id, '_store_price');
        }

        if ($stock !== '' && is_numeric($stock)) {
            update_post_meta($post_id, '_store_stock', (string) ((int) $stock));
        } else {
            delete_post_meta($post_id, '_store_stock');
        }
    }
}

