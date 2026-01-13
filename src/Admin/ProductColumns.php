<?php

namespace WpStore\Admin;

class ProductColumns
{
    public function register()
    {
        add_filter('manage_store_product_posts_columns', [$this, 'add_columns']);
        add_action('manage_store_product_posts_custom_column', [$this, 'render_columns'], 10, 2);
    }

    public function add_columns($columns)
    {
        $new_columns = [];
        
        // Loop through existing columns to insert ours in specific positions
        foreach ($columns as $key => $title) {
            // Insert Thumbnail before Title
            if ($key === 'title') {
                $new_columns['thumbnail'] = 'Thumbnail';
            }
            
            $new_columns[$key] = $title;
            
            // Insert Price after Title
            if ($key === 'title') {
                $new_columns['price'] = 'Harga';
            }
        }
        
        return $new_columns;
    }

    public function render_columns($column, $post_id)
    {
        switch ($column) {
            case 'thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [50, 50]);
                } else {
                    echo '<span style="color:#ccc">–</span>';
                }
                break;
                
            case 'price':
                $price = get_post_meta($post_id, '_store_price', true);
                if ($price !== '') {
                    echo 'Rp ' . number_format((float)$price, 0, ',', '.');
                } else {
                    echo '-';
                }
                break;
        }
    }
}
