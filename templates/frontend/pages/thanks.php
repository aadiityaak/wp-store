<?php
$order_id = isset($order_id) ? (int) $order_id : 0;
$currency = isset($currency) ? (string) $currency : 'Rp';
$order_exists = ($order_id > 0 && get_post_type($order_id) === 'store_order');
$total = $order_exists ? (float) get_post_meta($order_id, '_store_order_total', true) : 0;
$items = $order_exists ? get_post_meta($order_id, '_store_order_items', true) : [];
$items = is_array($items) ? $items : [];
$shipping_courier = $order_exists ? get_post_meta($order_id, '_store_order_shipping_courier', true) : '';
$shipping_service = $order_exists ? get_post_meta($order_id, '_store_order_shipping_service', true) : '';
$shipping_cost = $order_exists ? (float) get_post_meta($order_id, '_store_order_shipping_cost', true) : 0;
$address = $order_exists ? get_post_meta($order_id, '_store_order_address', true) : '';
$province_name = $order_exists ? get_post_meta($order_id, '_store_order_province_name', true) : '';
$city_name = $order_exists ? get_post_meta($order_id, '_store_order_city_name', true) : '';
$subdistrict_name = $order_exists ? get_post_meta($order_id, '_store_order_subdistrict_name', true) : '';
$postal_code = $order_exists ? get_post_meta($order_id, '_store_order_postal_code', true) : '';
$settings = get_option('wp_store_settings', []);
$shop_id = isset($settings['page_shop']) ? absint($settings['page_shop']) : 0;
$shop_url = $shop_id ? get_permalink($shop_id) : site_url('/shop/');
?>
<div class="wps-container">
    <div class="wps-card wps-p-6">
        <div class="wps-text-center">
            <div class="wps-text-2xl wps-font-semibold wps-text-gray-900">Terima Kasih</div>
            <div class="wps-text-sm wps-text-gray-600 wps-mt-1">Pesanan Anda sudah kami terima.</div>
            <?php if ($order_exists) : ?>
                <div class="wps-mt-2 wps-text-sm wps-text-gray-700">Nomor Pesanan: <span class="wps-font-medium">#<?php echo esc_html($order_id); ?></span></div>
            <?php endif; ?>
        </div>
        <?php if ($order_exists) : ?>
            <div class="wps-divider wps-mt-6 wps-mb-4"></div>
            <div class="wps-grid wps-grid-cols-2" style="gap: 1rem;">
                <div>
                    <div class="wps-text-lg wps-font-medium wps-text-gray-900">Ringkasan Pesanan</div>
                    <div class="wps-mt-2">
                        <?php if (empty($items)) : ?>
                            <div class="wps-text-sm wps-text-gray-500">Tidak ada item.</div>
                        <?php else : ?>
                            <?php foreach ($items as $it) :
                                $title = isset($it['title']) ? (string) $it['title'] : '';
                                $qty = isset($it['qty']) ? (int) $it['qty'] : 0;
                                $price = isset($it['price']) ? (float) $it['price'] : 0;
                                $subtotal = isset($it['subtotal']) ? (float) $it['subtotal'] : ($price * $qty);
                            ?>
                                <div class="wps-flex wps-justify-between wps-items-center wps-mb-2">
                                    <div class="wps-text-sm wps-text-gray-900"><?php echo esc_html($title); ?></div>
                                    <div class="wps-text-xs wps-text-gray-700"><?php echo esc_html(number_format($price, 0, ',', '.')); ?> × <?php echo esc_html($qty); ?> = <span class="wps-text-gray-900"><?php echo esc_html(number_format($subtotal, 0, ',', '.')); ?></span></div>
                                </div>
                            <?php endforeach; ?>
                            <div class="wps-flex wps-justify-between wps-items-center wps-mt-4">
                                <div class="wps-text-sm wps-text-gray-500">Ongkir (<?php echo esc_html(strtoupper($shipping_courier) . ' ' . $shipping_service); ?>)</div>
                                <div class="wps-text-sm wps-text-gray-900"><?php echo esc_html(number_format($shipping_cost, 0, ',', '.')); ?></div>
                            </div>
                            <div class="wps-flex wps-justify-between wps-items-center wps-mt-2">
                                <div class="wps-text-sm wps-text-gray-900 wps-font-medium">Grand Total</div>
                                <div class="wps-text-sm wps-text-gray-900 wps-font-medium"><?php echo esc_html(number_format($total, 0, ',', '.')); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="wps-text-lg wps-font-medium wps-text-gray-900">Alamat Pengiriman</div>
                    <div class="wps-mt-2 wps-text-sm wps-text-gray-700">
                        <div><?php echo esc_html($address); ?></div>
                        <div><?php echo esc_html($subdistrict_name); ?>, <?php echo esc_html($city_name); ?>, <?php echo esc_html($province_name); ?> <?php echo esc_html($postal_code); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="wps-text-center wps-mt-6">
            <a class="wps-btn wps-btn-primary" href="<?php echo esc_url($shop_url); ?>">Kembali Belanja</a>
        </div>
    </div>
</div>

