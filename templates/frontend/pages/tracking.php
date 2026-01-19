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
                <?php
                $settings = get_option('wp_store_settings', []);
                $tracking_id = isset($settings['page_tracking']) ? absint($settings['page_tracking']) : 0;
                $tracking_url = $tracking_id ? get_permalink($tracking_id) : site_url('/tracking-order/');
                ?>
                <div class="wps-mt-4" style="max-width:420px; margin:0 auto;">
                    <form id="wps-find-order" class="wps-flex wps-items-center wps-gap-2">
                        <input type="number" min="1" step="1" id="wps-order-id" class="wps-input" placeholder="Masukkan Nomor Order (contoh: 651)">
                        <button type="submit" class="wps-btn wps-btn-primary">Lacak</button>
                    </form>
                    <div id="wps-find-msg" class="wps-text-xs wps-text-gray-500 wps-mt-1">Format benar: <?php echo esc_html($tracking_url); ?>?order=ID</div>
                </div>
                <script>
                    (function() {
                        var f = document.getElementById('wps-find-order');
                        if (!f) return;
                        var input = document.getElementById('wps-order-id');
                        var msg = document.getElementById('wps-find-msg');
                        var base = <?php echo wp_json_encode($tracking_url); ?>;
                        f.addEventListener('submit', function(e) {
                            e.preventDefault();
                            var val = input && typeof input.value === 'string' ? input.value.trim() : '';
                            var id = parseInt(val, 10);
                            if (!id || id <= 0) {
                                if (msg) {
                                    msg.className = 'wps-text-xs wps-text-red-700 wps-mt-1';
                                    msg.textContent = 'Nomor order tidak valid.';
                                }
                                return;
                            }
                            var url = base + (base.indexOf('?') === -1 ? '?order=' + encodeURIComponent(String(id)) : '&order=' + encodeURIComponent(String(id)));
                            window.location.href = url;
                        });
                    })();
                </script>
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
                    $payment_method = get_post_meta($order_id, '_store_order_payment_method', true);
                    $settings = get_option('wp_store_settings', []);
                    $proofs = get_post_meta($order_id, '_store_order_payment_proofs', true);
                    $proofs = is_array($proofs) ? $proofs : [];
                    $bank_accounts = [];
                    if (isset($settings['store_bank_accounts']) && is_array($settings['store_bank_accounts'])) {
                        $bank_accounts = $settings['store_bank_accounts'];
                    } else {
                        $legacy_bank = [
                            'bank_name' => isset($settings['bank_name']) ? (string) $settings['bank_name'] : '',
                            'bank_account' => isset($settings['bank_account']) ? (string) $settings['bank_account'] : '',
                            'bank_holder' => isset($settings['bank_holder']) ? (string) $settings['bank_holder'] : '',
                        ];
                        if ($legacy_bank['bank_name'] !== '' || $legacy_bank['bank_account'] !== '' || $legacy_bank['bank_holder'] !== '') {
                            $bank_accounts[] = $legacy_bank;
                        }
                    }
                    ?>
                    <div class="wps-mt-2 wps-text-sm wps-text-gray-700 wps-bg-primary-100 wps-text-primary-800 wps-p-2 wps-rounded-md wps-font-medium"><?php echo esc_html($status_label); ?></div>
                    <?php if (!empty($tracking_number)) : ?>
                        <div class="wps-mt-2 wps-text-sm wps-text-gray-700">No. Resi: <span class="wps-font-medium"><?php echo esc_html($tracking_number); ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($proofs)) : ?>
                        <div class="wps-mt-3">
                            <div class="wps-text-sm wps-text-gray-900 wps-font-medium">Bukti Transfer</div>
                            <div class="wps-grid" style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:8px;">
                                <?php foreach ($proofs as $pid) :
                                    $url = wp_get_attachment_url($pid);
                                    $mime = get_post_mime_type($pid);
                                ?>
                                    <div class="wps-card wps-p-2">
                                        <?php if ($mime && strpos($mime, 'image/') === 0) : ?>
                                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                                                <img src="<?php echo esc_url($url); ?>" alt="Bukti Transfer" style="width:100%; height:120px; object-fit:cover;">
                                            </a>
                                        <?php else : ?>
                                            <a class="wps-text-sm wps-text-primary-700" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">Lihat Dokumen</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (in_array($status, ['pending', 'awaiting_payment'], true)) : ?>
                        <div class="wps-text-lg wps-font-medium wps-text-gray-900 wps-mt-6">Informasi Pembayaran</div>
                        <div class="wps-text-sm wps-text-gray-700 wps-mt-1">Gunakan nomor pesanan <span class="wps-font-medium">#<?php echo esc_html($order_id); ?></span> sebagai berita.</div>
                        <div class="wps-mt-3">
                            <div class="wps-flex wps-justify-between wps-items-center">
                                <div class="wps-text-sm wps-text-gray-500">Total yang harus dibayar</div>
                                <div class="wps-text-sm wps-text-gray-900 wps-font-medium"><?php echo esc_html(($currency ?: 'Rp') . ' ' . number_format($total, 0, ',', '.')); ?></div>
                            </div>
                        </div>
                        <?php if ($payment_method === 'qris') : ?>
                            <?php
                            $qris_id = isset($settings['qris_image_id']) ? absint($settings['qris_image_id']) : 0;
                            $qris_src = $qris_id ? wp_get_attachment_image_url($qris_id, 'medium') : '';
                            $qris_label = isset($settings['qris_label']) ? (string) $settings['qris_label'] : 'QRIS';
                            ?>
                            <div class="wps-mt-3 wps-p-4" style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; text-align:center;">
                                <div class="wps-text-sm wps-text-gray-900 wps-font-medium" style="margin-bottom:8px;"><?php echo esc_html($qris_label); ?></div>
                                <div class="wps-mt-2">
                                    <img src="<?php echo esc_url($qris_src ?: WP_STORE_URL . 'assets/frontend/img/noimg.webp'); ?>" alt="QRIS" style="width:180px;height:180px; object-fit:contain;">
                                </div>
                                <div class="wps-text-xs wps-text-gray-500 wps-mt-2">Scan untuk membayar via QRIS.</div>
                            </div>
                        <?php else : ?>
                            <?php if (!empty($bank_accounts)) : ?>
                                <div class="wps-mt-3">
                                    <?php foreach ($bank_accounts as $acc) : ?>
                                        <div class="wps-card wps-p-4 wps-mb-2">
                                            <div class="wps-text-sm wps-text-gray-900 wps-font-medium" style="margin-bottom:6px;"><?php echo esc_html($acc['bank_name'] ?? ''); ?></div>
                                            <div class="wps-text-sm wps-text-gray-700">
                                                <div>No. Rekening: <span class="wps-font-medium"><?php echo esc_html($acc['bank_account'] ?? ''); ?></span></div>
                                                <div>Atas Nama: <span class="wps-font-medium"><?php echo esc_html($acc['bank_holder'] ?? ''); ?></span></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="wps-text-xs wps-text-gray-500 wps-mt-4">Setelah pembayaran, kirim bukti transfer melalui kontak yang tersedia atau tunggu konfirmasi dari kami.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php
                        $ajax_url = admin_url('admin-ajax.php');
                        $nonce_upload = wp_create_nonce('wp_store_upload_payment_proof');
                        ?>
                        <div class="wps-text-lg wps-font-medium wps-text-gray-900 wps-mt-6">Upload Bukti Transfer</div>
                        <form id="wps-upload-proof" action="<?php echo esc_url($ajax_url); ?>" method="post" enctype="multipart/form-data" class="wps-mt-2">
                            <input type="hidden" name="action" value="wp_store_upload_payment_proof">
                            <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_upload); ?>">
                            <?php if (!is_user_logged_in()) : ?>
                                <div class="wps-mb-2">
                                    <?php echo \WpStore\Frontend\Template::render('components/captcha'); ?>
                                </div>
                            <?php endif; ?>
                            <div class="wps-flex wps-items-center wps-gap-2">
                                <input type="file" name="proof" accept="image/*,.pdf" required class="wps-input">
                                <button type="submit" class="wps-btn wps-btn-primary">Upload</button>
                            </div>
                            <div class="wps-text-xs wps-text-gray-500 wps-mt-1">Format yang didukung: JPG, PNG, WEBP, PDF.</div>
                            <div id="wps-upload-msg" class="wps-text-sm wps-mt-2"></div>
                        </form>
                        <div id="wps-upload-preview" class="wps-mt-2" style="display:none;">
                            <div class="wps-text-sm wps-text-gray-900 wps-font-medium">Preview</div>
                            <div class="wps-card wps-p-2" id="wps-upload-preview-box"></div>
                        </div>
                        <?php if (!empty($proofs)) : ?>
                            <div class="wps-mt-3">
                                <div class="wps-text-sm wps-text-gray-900 wps-font-medium">Bukti yang Diupload</div>
                                <div class="wps-grid" style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:8px;">
                                    <?php foreach ($proofs as $pid) :
                                        $url = wp_get_attachment_url($pid);
                                        $mime = get_post_mime_type($pid);
                                    ?>
                                        <div class="wps-card wps-p-2">
                                            <?php if ($mime && strpos($mime, 'image/') === 0) : ?>
                                                <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                                                    <img src="<?php echo esc_url($url); ?>" alt="Bukti Transfer" style="width:100%; height:120px; object-fit:cover;">
                                                </a>
                                            <?php else : ?>
                                                <a class="wps-text-sm wps-text-primary-700" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">Lihat Dokumen</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <script>
                            (function() {
                                var f = document.getElementById('wps-upload-proof');
                                if (!f) return;
                                var msg = document.getElementById('wps-upload-msg');
                                var fileInput = f.querySelector('input[type="file"]');
                                var pvWrap = document.getElementById('wps-upload-preview');
                                var pvBox = document.getElementById('wps-upload-preview-box');
                                if (fileInput) {
                                    fileInput.addEventListener('change', function() {
                                        if (!fileInput.files || !fileInput.files[0]) {
                                            if (pvWrap) pvWrap.style.display = 'none';
                                            if (pvBox) pvBox.innerHTML = '';
                                            return;
                                        }
                                        var f0 = fileInput.files[0];
                                        var mime = f0.type || '';
                                        var url = URL.createObjectURL(f0);
                                        if (pvBox) {
                                            pvBox.innerHTML = '';
                                            if (mime.indexOf('image/') === 0) {
                                                var img = document.createElement('img');
                                                img.src = url;
                                                img.alt = 'Preview';
                                                img.style.width = '100%';
                                                img.style.height = '160px';
                                                img.style.objectFit = 'cover';
                                                pvBox.appendChild(img);
                                            } else {
                                                var link = document.createElement('span');
                                                link.className = 'wps-text-sm wps-text-primary-700';
                                                link.textContent = 'Dokumen ' + (f0.name || '');
                                                pvBox.appendChild(link);
                                            }
                                            if (pvWrap) pvWrap.style.display = '';
                                        }
                                    });
                                }
                                f.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    msg.textContent = '';
                                    var btn = f.querySelector('button[type="submit"]');
                                    if (btn) btn.disabled = true;
                                    var fd = new FormData(f);
                                    var act = f.getAttribute('action') || '';
                                    fetch(act, {
                                            method: 'POST',
                                            body: fd
                                        })
                                        .then(function(r) {
                                            return r.json();
                                        })
                                        .then(function(j) {
                                            if (j && j.success) {
                                                msg.className = 'wps-text-sm wps-text-green-700 wps-mt-2';
                                                msg.textContent = 'Bukti transfer berhasil diunggah.';
                                                if (pvWrap) pvWrap.style.display = 'none';
                                                if (pvBox) pvBox.innerHTML = '';
                                                if (j.data && j.data.url) {
                                                    var grid = f.parentNode.querySelector('.wps-grid');
                                                    if (grid) {
                                                        var mime = '';
                                                        if (fileInput && fileInput.files && fileInput.files[0]) {
                                                            mime = fileInput.files[0].type || '';
                                                        }
                                                        var wrap = document.createElement('div');
                                                        wrap.className = 'wps-card wps-p-2';
                                                        if (mime.indexOf('image/') === 0) {
                                                            var a = document.createElement('a');
                                                            a.href = j.data.url;
                                                            a.target = '_blank';
                                                            a.rel = 'noopener';
                                                            var img = document.createElement('img');
                                                            img.src = j.data.url;
                                                            img.alt = 'Bukti Transfer';
                                                            img.style.width = '100%';
                                                            img.style.height = '120px';
                                                            img.style.objectFit = 'cover';
                                                            a.appendChild(img);
                                                            wrap.appendChild(a);
                                                        } else {
                                                            var link = document.createElement('a');
                                                            link.href = j.data.url;
                                                            link.target = '_blank';
                                                            link.rel = 'noopener';
                                                            link.className = 'wps-text-sm wps-text-primary-700';
                                                            link.textContent = 'Lihat Dokumen';
                                                            wrap.appendChild(link);
                                                        }
                                                        grid.appendChild(wrap);
                                                    }
                                                }
                                                f.reset();
                                            } else {
                                                msg.className = 'wps-text-sm wps-text-red-700 wps-mt-2';
                                                msg.textContent = (j && j.data && j.data.message) ? j.data.message : 'Gagal mengunggah.';
                                            }
                                        })
                                        .catch(function() {
                                            msg.className = 'wps-text-sm wps-text-red-700 wps-mt-2';
                                            msg.textContent = 'Terjadi kesalahan.';
                                        })
                                        .finally(function() {
                                            if (btn) btn.disabled = false;
                                        });
                                });
                            })();
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>