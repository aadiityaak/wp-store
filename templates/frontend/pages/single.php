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
            </div>
            <div class="wps-mb-4">
                <?php
                $sku = get_post_meta($id, '_store_sku', true);
                $min_order = get_post_meta($id, '_store_min_order', true);
                $weight_kg = get_post_meta($id, '_store_weight_kg', true);
                $ptype = get_post_meta($id, '_store_product_type', true);
                $sale_price = get_post_meta($id, '_store_sale_price', true);
                $terms = get_the_terms($id, 'store_product_cat');
                $cats = [];
                if (is_array($terms)) {
                    foreach ($terms as $t) {
                        $cats[] = $t->name;
                    }
                }
                ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <?php if (is_string($sku) && $sku !== '') : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Kode Produk</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html($sku); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($weight_kg !== '' && $weight_kg !== null) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Berat</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html(number_format_i18n((float) $weight_kg, 2)); ?> kg</td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($min_order !== '' && $min_order !== null) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Minimal Order</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html((int) $min_order); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($stock !== null) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Stok</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html((int) $stock); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (is_string($ptype) && $ptype !== '') : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Tipe</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900">
                                    <?php echo esc_html($ptype === 'digital' ? 'Produk Digital' : 'Produk Fisik'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($sale_price !== '' && $sale_price !== null) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Harga Promo</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900">
                                    <?php echo esc_html(($currency ?? 'Rp') . ' ' . number_format_i18n((float) $sale_price, 0)); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($cats)) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Kategori</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html(implode(', ', $cats)); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="wps-flex wps-gap-2 wps-items-center wps-mb-2">
                <div><?php echo do_shortcode('[wp_store_add_to_cart id="' . esc_attr($id) . '"]'); ?></div>
                <div><?php echo do_shortcode('[wp_store_add_to_wishlist id="' . esc_attr($id) . '"]'); ?></div>
            </div>
            <div class="wps-mb-4">
                <?php
                $share_link = get_permalink($id);
                $share_title = $title;
                $enc_url = rawurlencode($share_link);
                $enc_title = rawurlencode($share_title);
                $wa_url = 'https://api.whatsapp.com/send?text=' . rawurlencode($share_title . ' ' . $share_link);
                $x_url = 'https://twitter.com/intent/tweet?text=' . $enc_title . '&url=' . $enc_url;
                $fb_url = 'https://www.facebook.com/sharer/sharer.php?u=' . $enc_url;
                $mail_url = 'mailto:?subject=' . rawurlencode($share_title) . '&body=' . rawurlencode($share_link);
                ?>
                <div class="wps-flex wps-gap-2 wps-items-center">
                    <a href="<?php echo esc_url($wa_url); ?>" target="_blank" rel="noopener" class="wps-btn wps-btn-secondary wps-btn-sm"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'whatsapp', 'size' => 18]); ?></a>
                    <a href="<?php echo esc_url($x_url); ?>" target="_blank" rel="noopener" class="wps-btn wps-btn-secondary wps-btn-sm"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'x', 'size' => 18]); ?></a>
                    <a href="<?php echo esc_url($fb_url); ?>" target="_blank" rel="noopener" class="wps-btn wps-btn-secondary wps-btn-sm"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'facebook', 'size' => 18]); ?></a>
                    <a href="<?php echo esc_url($mail_url); ?>" class="wps-btn wps-btn-secondary wps-btn-sm"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'email', 'size' => 18]); ?></a>
                </div>
            </div>
            <div class="wps-text-sm wps-text-gray-500">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
</div>