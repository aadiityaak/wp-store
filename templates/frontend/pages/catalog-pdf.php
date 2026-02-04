<?php
$currency = isset($currency) ? (string) $currency : 'Rp';
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Katalog Produk</title>
  <style>
    @page {
      margin: 24px;
    }

    body {
      font-family: sans-serif;
      font-size: 12px;
      color: #111;
    }

    h1 {
      font-size: 18px;
      margin: 0 0 12px 0;
    }

    .grid {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .card {
      width: 31%;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 8px;
      box-sizing: border-box;
    }

    .title {
      font-size: 12px;
      font-weight: 600;
      margin: 6px 0;
    }

    .price {
      font-size: 12px;
      color: #2563eb;
      font-weight: 700;
    }

    .img {
      width: 100%;
      height: 150px;
      object-fit: cover;
      border-radius: 4px;
    }
  </style>
</head>

<body>
  <h1>Katalog Produk</h1>
  <?php if (!empty($items)) : ?>
    <div class="grid">
      <?php foreach ($items as $item) : ?>
        <div class="card">
          <?php
          $src = is_string($item['image']) && $item['image'] !== '' ? $item['image'] : (WP_STORE_URL . 'assets/frontend/img/noimg.webp');
          $alt = is_string($item['title']) ? $item['title'] : 'Produk';
          ?>
          <img class="img" src="<?php echo esc_url($src); ?>" alt="<?php echo esc_attr($alt); ?>">
          <div class="title"><?php echo esc_html($item['title']); ?></div>
          <?php if (isset($item['price']) && $item['price'] !== null) : ?>
            <?php
            $price_val = (float) ($item['price']);
            $formatted_price = ($currency ?? 'Rp') === 'Rp'
              ? number_format($price_val, 0, ',', '.')
              : number_format_i18n($price_val, 0);
            ?>
            <div class="price"><?php echo esc_html(($currency ?? 'Rp') . ' ' . $formatted_price); ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <div>Tidak ada produk.</div>
  <?php endif; ?>
</body>

</html>