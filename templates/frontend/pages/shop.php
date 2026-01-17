<?php if (!empty($items)) : ?>
    <div class="">
        <div class="wps-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));<?php echo (count($items) === 1) ? ' max-width: 360px; margin: 0 auto;' : ''; ?>">
            <?php foreach ($items as $item) : ?>
                <?php echo \WpStore\Frontend\Template::render('components/product-card', ['item' => $item, 'currency' => $currency, 'view_label' => 'Lihat Detail']); ?>
            <?php endforeach; ?>
        </div>
        <?php if (isset($pages) && (int) $pages > 1) : ?>
            <div class="wps-flex wps-items-center wps-gap-2 wps-mt-4" style="justify-content: center;">
                <?php $base = get_permalink(); ?>
                <?php if ((int) $page > 1) : ?>
                    <a href="<?php echo esc_url(add_query_arg('shop_page', (int) $page - 1, $base)); ?>" class="wps-btn wps-btn-secondary wps-btn-sm">Sebelumnya</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= (int) $pages; $i++) : ?>
                    <a href="<?php echo esc_url(add_query_arg('shop_page', $i, $base)); ?>" class="wps-btn <?php echo ($i === (int) $page) ? 'wps-btn-primary' : 'wps-btn-secondary'; ?> wps-btn-sm"><?php echo esc_html($i); ?></a>
                <?php endfor; ?>
                <?php if ((int) $page < (int) $pages) : ?>
                    <a href="<?php echo esc_url(add_query_arg('shop_page', (int) $page + 1, $base)); ?>" class="wps-btn wps-btn-secondary wps-btn-sm">Berikutnya</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div class="">
        <div class="wps-text-sm wps-text-gray-500">Belum ada produk.</div>
    </div>
<?php endif; ?>