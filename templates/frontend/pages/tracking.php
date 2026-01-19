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
?>
<div class="wps-container">
    <div class="wps-card wps-p-6">
        <div class="wps-text-center">
            <div class="wps-text-2xl wps-font-semibold wps-text-gray-900">Tracking Pesanan</div>
            <?php if ($order_exists) : ?>
                <div class="wps-mt-1 wps-text-sm wps-text-gray-700">Nomor Pesanan: <span class="wps-font-medium">#<?php echo esc_html($order_id); ?></span></div>
            <?php else : ?>
                <div class="wps-text-sm wps-text-gray-600 wps-mt-1">Masukkan parameter <span class="wps-font-medium">order</span> di URL untuk melihat status.</div>
            <?php endif; ?>
        </div>
        <?php if ($order_exists) : ?>
            <div class="wps-divider wps-mt-6 wps-mb-4"></div>
            <div class="wps-grid" style="display:grid; gap: 1rem; grid-template-columns: 1.2fr 0.8fr;">
                <div>
                    <div class="wps-text-lg wps-font-medium wps-text-gray-900">Ringkasan Pesanan</div>
                    <div class="wps-mt-2">
                        <?php if (empty($items)) : ?>
                            <div class="wps-text-sm wps-text-gray-500">Tidak ada item.</div>
                        <?php else : ?>
                            <table class="wps-text-sm wps-table wps-table-striped">
                                <thead class="wps-table-head">
                                    <tr>
                                        <th class="wps-text-left wps-th">Produk</th>
                                        <th class="wps-text-right wps-th">Harga</th>
                                        <th class="wps-text-right wps-th">Qty</th>
                                        <th class="wps-text-right wps-th">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $it) :
                                        $title = isset($it['title']) ? (string) $it['title'] : '';
                                        $qty = isset($it['qty']) ? (int) $it['qty'] : 0;
                                        $price = isset($it['price']) ? (float) $it['price'] : 0;
                                        $subtotal = isset($it['subtotal']) ? (float) $it['subtotal'] : ($price * $qty);
                                    ?>
                                        <tr>
                                            <td class="wps-text-gray-900 wps-td"><?php echo esc_html($title); ?></td>
                                            <td class="wps-text-gray-700 wps-text-right wps-td"><?php echo esc_html(($currency ?: 'Rp') . ' ' . number_format($price, 0, ',', '.')); ?></td>
                                            <td class="wps-text-gray-700 wps-text-right wps-td"><?php echo esc_html($qty); ?></td>
                                            <td class="wps-text-gray-900 wps-text-right wps-td"><?php echo esc_html(($currency ?: 'Rp') . ' ' . number_format($subtotal, 0, ',', '.')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="wps-mt-4 wps-p-4 wps-summary-box">
                                <div class="wps-flex wps-justify-between wps-items-center">
                                    <div class="wps-text-sm wps-text-gray-500">Total Produk</div>
                                    <div class="wps-text-sm wps-text-gray-900"><?php echo esc_html(($currency ?: 'Rp') . ' ' . number_format($total - $shipping_cost, 0, ',', '.')); ?></div>
                                </div>
                                <div class="wps-flex wps-justify-between wps-items-center wps-mt-2">
                                    <?php
                                    $courier_labels = [
                                        'jne' => 'JNE',
                                        'sicepat' => 'SiCepat',
                                        'ide' => 'IDExpress',
                                        'sap' => 'SAP Express',
                                        'ninja' => 'Ninja',
                                        'jnt' => 'J&T Express',
                                        'tiki' => 'TIKI',
                                        'wahana' => 'Wahana Express',
                                        'pos' => 'POS Indonesia',
                                        'sentral' => 'Sentral Cargo',
                                        'lion' => 'Lion Parcel',
                                        'rex' => 'Royal Express Asia',
                                    ];
                                    $courier_label = isset($courier_labels[$shipping_courier]) ? $courier_labels[$shipping_courier] : strtoupper((string)$shipping_courier);
                                    ?>
                                    <div class="wps-text-sm wps-text-gray-500">Ongkir (<?php echo esc_html($courier_label . ' ' . $shipping_service); ?>)</div>
                                    <div class="wps-text-sm wps-text-gray-900"><?php echo esc_html(($currency ?: 'Rp') . ' ' . number_format($shipping_cost, 0, ',', '.')); ?></div>
                                </div>
                                <div class="wps-flex wps-justify-between wps-items-center wps-mt-2" style="border-top:1px dashed #e5e7eb; padding-top:12px;">
                                    <div class="wps-text-sm wps-text-gray-900 wps-font-medium">Grand Total</div>
                                    <div class="wps-text-sm wps-text-gray-900 wps-font-medium"><?php echo esc_html(($currency ?: 'Rp') . ' ' . number_format($total, 0, ',', '.')); ?></div>
                                </div>
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
                    <div class="wps-text-lg wps-font-medium wps-text-gray-900 wps-mt-6">Status</div>
                    <?php
                    $status = get_post_meta($order_id, '_store_order_status', true);
                    $status = is_string($status) && $status !== '' ? $status : 'pending';
                    $status_labels = [
                        'pending' => 'Pending',
                        'awaiting_payment' => 'Menunggu Pembayaran',
                        'paid' => 'Sudah Dibayar',
                        'processing' => 'Sedang Diproses',
                        'shipped' => 'Dikirim',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ];
                    $status_label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
                    $tracking_number = get_post_meta($order_id, '_store_order_tracking_number', true);
                    ?>
                    <div class="wps-mt-2 wps-text-sm wps-text-gray-700 wps-bg-primary-100 wps-text-primary-800 wps-p-2 wps-rounded-md wps-font-medium"><?php echo esc_html($status_label); ?></div>
                    <?php if (!empty($tracking_number)) : ?>
                        <div class="wps-mt-2 wps-text-sm wps-text-gray-700">No. Resi: <span class="wps-font-medium"><?php echo esc_html($tracking_number); ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>