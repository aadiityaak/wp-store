<?php $image_src = (!empty($item['image']) ? $item['image'] : (WP_STORE_URL . 'assets/frontend/img/noimg.webp')); ?>
<div class="wps-card wps-card-hover wps-transition">
  <div class="wps-p-2">
    <a class="wps-text-sm wps-text-gray-900 wps-mb-4 wps-text-bold wps-d-block wps-rel" href="<?php echo esc_url($item['link']); ?>">
      <div class="wps-image-wrap">
        <?php
        $hover_src = '';
        $gal = get_post_meta((int) $item['id'], '_store_gallery_ids', true);
        if (is_array($gal) && !empty($gal)) {
          $first = array_values($gal)[0];
          if (is_numeric($first)) {
            $url = wp_get_attachment_image_url((int) $first, 'medium');
            if (is_string($url)) $hover_src = $url;
          } elseif (is_string($first)) {
            $hover_src = $first;
          }
        }
        ?>
        <img class="wps-w-full wps-rounded wps-mb-4 wps-img-160 img-main" src="<?php echo esc_url($image_src); ?>" alt="<?php echo esc_attr($item['title']); ?>">
        <?php if ($hover_src) : ?>
          <img class="wps-w-full wps-rounded wps-mb-4 wps-img-160 img-hover" src="<?php echo esc_url($hover_src); ?>" alt="<?php echo esc_attr($item['title']); ?>">
        <?php endif; ?>
      </div>
      <?php
      $type = get_post_meta((int) $item['id'], '_store_product_type', true);
      $is_digital = ($type === 'digital') || (bool) get_post_meta((int) $item['id'], '_store_is_digital', true);
      if ($is_digital) {
      ?>
        <span class="wps-digital-badge wps-text-xs wps-text-white">
          <?php echo wps_icon(["name" => "cloud-download", "size" => 12, "stroke_color" => "#ffffff"]); ?>
          <span class="txt wps-text-white wps-text-xs">Digital</span>
        </span>
        <?php
      }
      $lbl = get_post_meta((int) $item['id'], '_store_label', true);
      if (is_string($lbl) && $lbl !== '') {
        $txt = $lbl === 'label-best' ? 'Best Seller' : ($lbl === 'label-limited' ? 'Limited' : ($lbl === 'label-new' ? 'New' : ''));
        if ($txt !== '') {
        ?>
          <span class="wps-label-badge <?php echo esc_attr($lbl); ?> wps-text-xs">
            <?php echo wps_icon(["name" => "heart", "size" => 10, "stroke_color" => "#ffffff"]); ?>
            <span class="txt wps-text-white wps-text-xs"><?php echo esc_html($txt); ?></span>
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