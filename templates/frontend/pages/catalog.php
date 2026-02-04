<?php
$currency = isset($currency) ? (string) $currency : (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
?>
<style>
@media print {
  .no-print { display: none !important; }
  .catalog-grid { grid-template-columns: 1fr 1fr 1fr; }
}
</style>
<script>
function wpsDownloadCatalogPdf() {
  window.print();
}
</script>
<div class="wps-container wps-mx-auto wps-my-8">
  <div class="wps-flex wps-items-center wps-justify-between wps-mb-4">
    <div class="wps-text-lg wps-font-medium wps-text-gray-900">Katalog Produk</div>
    <a href="<?php echo esc_url(site_url('/wp-json/wp-store/v1/catalog/pdf')); ?>" class="wps-btn wps-btn-secondary">
      <?php echo wps_icon(['name' => 'filetype-pdf', 'size' => 16, 'class' => 'wps-mr-2']); ?>Download PDF
    </a>
  </div>
  <?php if (!empty($items)) : ?>
    <div class="catalog-grid wps-grid wps-gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
      <?php foreach ($items as $item) : ?>
        <div class="wps-box-gray wps-rounded wps-p-3">
          <a class="wps-block wps-mb-2" href="<?php echo esc_url($item['link']); ?>">
            <?php
            $src = is_string($item['image']) && $item['image'] !== '' ? $item['image'] : (WP_STORE_URL . 'assets/frontend/img/noimg.webp');
            $alt = is_string($item['title']) ? $item['title'] : 'Produk';
            ?>
            <img class="wps-rounded" src="<?php echo esc_url($src); ?>" alt="<?php echo esc_attr($alt); ?>" style="width:100%; aspect-ratio: 1 / 1; object-fit: cover;">
          </a>
          <div class="wps-text-sm wps-text-gray-900 wps-font-medium"><?php echo esc_html($item['title']); ?></div>
          <?php if (isset($item['price']) && $item['price'] !== null) : ?>
            <?php
            $price_val = (float) ($item['price']);
            $formatted_price = ($currency ?? 'Rp') === 'Rp'
              ? number_format($price_val, 0, ',', '.')
              : number_format_i18n($price_val, 0);
            ?>
            <div class="wps-text-sm wps-text-primary-700 wps-font-semibold"><?php echo esc_html(($currency ?? 'Rp') . ' ' . $formatted_price); ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <div class="wps-text-sm wps-text-gray-500">Belum ada produk.</div>
  <?php endif; ?>
</div>
