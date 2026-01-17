<?php if (!empty($items)) : ?>
<div class="wps-p-4">
    <div class="wps-grid wps-grid-cols-2 wps-md-grid-cols-4">
        <?php foreach ($items as $item) : ?>
            <div class="wps-card wps-card-hover wps-transition">
                <div class="wps-p-2">
                    <?php if (!empty($item['image'])) : ?>
                        <img class="wps-w-full wps-rounded wps-mb-4 wps-img-160" src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                    <?php endif; ?>
                    <a class="wps-text-sm wps-text-gray-900 wps-mb-4" href="<?php echo esc_url($item['link']); ?>"><?php echo esc_html($item['title']); ?></a>
                    <div class="wps-text-sm wps-text-gray-900 wps-mb-4">
                        <?php if ($item['price'] !== null) : ?>
                            <?php echo esc_html(($currency ?? 'Rp') . ' ' . number_format_i18n((float) $item['price'], 0)); ?>
                        <?php endif; ?>
                        <?php if ($item['stock'] !== null) : ?>
                            <span class="wps-text-gray-500"> • Stok: <?php echo esc_html((int) $item['stock']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="wps-flex wps-items-center wps-justify-between">
                        <div><?php echo do_shortcode('[wp_store_add_to_cart id="' . esc_attr($item['id']) . '" size="sm"]'); ?></div>
                        <a class="wps-btn wps-btn-secondary wps-btn-sm" href="<?php echo esc_url($item['link']); ?>">Lihat Detail</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else : ?>
<div class="wps-p-4">
    <div class="wps-text-sm wps-text-gray-500">Belum ada produk.</div>
</div>
<?php endif; ?>
