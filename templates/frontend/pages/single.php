<div class="wps-p-4">
    <div class="wps-text-lg wps-font-medium wps-text-gray-900 wps-mb-4"><?php echo esc_html($title); ?></div>
    <div class="wps-flex wps-gap-4 wps-items-start">
        <div style="flex: 1;">
            <?php $image_src = (!empty($image) ? $image : (WP_STORE_URL . 'assets/frontend/img/noimg.webp')); ?>
            <img class="wps-w-full wps-rounded wps-img-320" src="<?php echo esc_url($image_src); ?>" alt="<?php echo esc_attr($title); ?>">
        </div>
        <div style="flex: 1;">
            <div class="wps-text-sm wps-text-gray-900 wps-mb-4">
                <?php if ($price !== null) : ?>
                    <?php echo esc_html(($currency ?? 'Rp') . ' ' . number_format_i18n((float) $price, 0)); ?>
                <?php endif; ?>
                <?php if ($stock !== null) : ?>
                    <span class="wps-text-gray-500"> • Stok: <?php echo esc_html((int) $stock); ?></span>
                <?php endif; ?>
            </div>
            <div class="wps-mb-4">
                <?php echo do_shortcode('[wp_store_add_to_cart id="' . esc_attr($id) . '"]'); ?>
            </div>
            <div class="wps-mb-4">
                <?php echo do_shortcode('[wp_store_add_to_wishlist id="' . esc_attr($id) . '"]'); ?>
            </div>
            <div class="wps-text-sm wps-text-gray-500">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
</div>