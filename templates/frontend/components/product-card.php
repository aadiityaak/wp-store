<?php $image_src = (!empty($item['image']) ? $item['image'] : (WP_STORE_URL . 'assets/frontend/img/noimg.webp')); ?>
<style>
  .wps-digital-badge .txt {
    opacity: 0;
    max-width: 0;
    margin-left: 0;
    transition: max-width .15s ease, opacity .15s ease
  }

  .wps-digital-badge:hover .txt {
    opacity: 1;
    max-width: 60px;
    margin-left: 4px
  }
</style>
<div class="wps-card wps-card-hover wps-transition">
  <div class="wps-p-2">
    <a class="wps-text-sm wps-text-gray-900 wps-mb-4 wps-text-bold" href="<?php echo esc_url($item['link']); ?>" style="position:relative;display:block;">
      <img class="wps-w-full wps-rounded wps-mb-4 wps-img-160" src="<?php echo esc_url($image_src); ?>" alt="<?php echo esc_attr($item['title']); ?>">
      <?php
      $type = get_post_meta((int) $item['id'], '_store_product_type', true);
      $is_digital = ($type === 'digital') || (bool) get_post_meta((int) $item['id'], '_store_is_digital', true);
      if ($is_digital) {
      ?>
        <span class="wps-digital-badge wps-text-xs wps-text-white" style="position:absolute;top:8px;left:8px;display:flex;align-items:center;background:#111827cc;color:#fff;border-radius:9999px;padding:2px 6px;backdrop-filter:saturate(180%) blur(4px);">
          <?php echo wps_icon(["name" => "cloud-download", "size" => 12, "stroke_color" => "#ffffff"]); ?>
          <span class="txt" style="color:#fff;font-size:10px;white-space:nowrap;overflow:hidden;">Digital</span>
        </span>
        <?php
      }
      $lbl = get_post_meta((int) $item['id'], '_store_label', true);
      if (is_string($lbl) && $lbl !== '') {
        $txt = $lbl === 'label-best' ? 'Best Seller' : ($lbl === 'label-limited' ? 'Limited' : ($lbl === 'label-new' ? 'New' : ''));
        $bg  = $lbl === 'label-best' ? '#f59e0b' : ($lbl === 'label-limited' ? '#ef4444' : ($lbl === 'label-new' ? '#10b981' : '#374151'));
        if ($txt !== '') {
        ?>
          <span class="wps-text-xs" style="position:absolute;top:8px;right:8px;display:inline-flex;align-items:center;background:' . esc_attr($bg) . ';color:#fff;border-radius:9999px;padding:2px 6px;">
            <?php echo wps_icon(["name" => "heart", "size" => 10, "stroke_color" => "#ffffff"]); ?>
            <span style="color:#fff;font-size:10px;margin-left:4px;"><?php echo esc_html($txt); ?></span>
          </span>
      <?php
        }
      }
      ?>
      <?php echo esc_html($item['title']); ?>
    </a>
    <div class="wps-text-xxs wps-text-gray-900 wps-mb-4">
      <?php if (isset($item['price']) && $item['price'] !== null) : ?>
        <?php
        $price_val = (float) ($item['price']);
        $formatted_price = ($currency ?? 'Rp') === 'Rp'
          ? number_format($price_val, 0, ',', '.')
          : number_format_i18n($price_val, 0);
        echo esc_html(($currency ?? 'Rp') . ' ' . $formatted_price);
        ?>
      <?php endif; ?>
    </div>
    <div class="wps-flex wps-items-center wps-justify-between">
      <div class="wps-flex wps-gap-2">
        <?php echo do_shortcode('[wp_store_add_to_cart id="' . esc_attr($item['id']) . '" size="sm"]'); ?>
        <?php echo do_shortcode('[wp_store_add_to_wishlist id="' . esc_attr($item['id']) . '" size="sm" icon_only="1" label_add="" label_remove=""]'); ?>
      </div>
      <a class="wps-btn wps-btn-secondary wps-btn-sm" href="<?php echo esc_url($item['link']); ?>"><?php echo esc_html($view_label ?? 'Detail'); ?></a>
    </div>
  </div>
</div>