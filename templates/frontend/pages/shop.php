<?php if (!empty($items)) : ?>
<div class="wps-p-4">
    <div class="wps-grid wps-grid-cols-2 wps-md-grid-cols-4">
        <?php foreach ($items as $item) : ?>
            <?php echo \WpStore\Frontend\Template::render('components/product-card', ['item' => $item, 'currency' => $currency, 'view_label' => 'Lihat Detail']); ?>
        <?php endforeach; ?>
    </div>
</div>
<?php else : ?>
<div class="wps-p-4">
    <div class="wps-text-sm wps-text-gray-500">Belum ada produk.</div>
</div>
<?php endif; ?>
