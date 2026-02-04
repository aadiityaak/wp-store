<?php if (!empty($items)) : ?>
  <?php
  $per_row = isset($per_row) ? (int) $per_row : 4;
  if ($per_row <= 0) $per_row = 4;
  $w = isset($img_width) ? (int) $img_width : 200;
  if ($w <= 0) $w = 200;
  $h = isset($img_height) ? (int) $img_height : 200;
  if ($h <= 0) $h = 200;
  $crop = isset($crop) ? (bool) $crop : true;
  $size = [$w, $h];
  $item_basis = 'calc(100% / ' . $per_row . ')';
  $style_img = 'width:' . (int) $w . 'px; height:' . (int) $h . 'px; object-fit:' . ($crop ? 'cover' : 'contain') . ';';
  ?>
<div x-data="{ init(){ if (window.Flickity) { var opts = { cellAlign: 'left', contain: true, pageDots: false, prevNextButtons: true, wrapAround: true }; new Flickity($refs.track, opts);} } }" x-init="init()">
      <div class="wps-text-sm wps-text-gray-900 wps-mb-3"><?php echo esc_html($label); ?></div>
    <?php endif; ?>
    <div class="wps-flex wps-items-center wps-gap-2">
      <?php if (!$use_flickity): ?>
    <div x-ref="track" class="wps-flex wps-gap-2">
          <?php
          $id = (int) ($item['id'] ?? 0);
          $link = (string) ($item['link'] ?? '');
          $src = $id ? get_the_post_thumbnail_url($id, $size) : '';
          if (!$src) {
          if (!$src) {
            $src = WP_STORE_URL . 'assets/frontend/img/noimg.webp';
          }
          $alt = $id ? get_the_title($id) : 'Produk';
          $hover_src = '';
          if ($id > 0) {
            $gal = get_post_meta($id, '_store_gallery_ids', true);
            if (is_array($gal) && !empty($gal)) {
              $first = array_values($gal)[0];
              if (is_numeric($first)) {
                $url = wp_get_attachment_image_url((int) $first, $size);
                if (is_string($url)) $hover_src = $url;
              } elseif (is_string($first)) {
                $hover_src = $first;
              }
            }
          }
          ?>
          <a href="<?php echo esc_url($link); ?>" style="flex:0 0 <?php echo esc_attr($item_basis); ?>;">
            <div class="wps-card-hover">
              <div class="wps-image-wrap<?php echo $hover_src ? ' wps-has-hover' : ''; ?>" style="width:<?php echo (int) $w; ?>px; height:<?php echo (int) $h; ?>px;">
                <img class="wps-rounded img-main" src="<?php echo esc_url($src); ?>" alt="<?php echo esc_attr($alt); ?>" style="<?php echo esc_attr($style_img); ?>">
                <?php if ($hover_src) : ?>
                  <img class="wps-rounded img-hover" src="<?php echo esc_url($hover_src); ?>" alt="<?php echo esc_attr($alt); ?>">
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php else : ?>
  <div class="wps-text-sm wps-text-gray-500">Tidak ada produk.</div>
<?php endif; ?>
