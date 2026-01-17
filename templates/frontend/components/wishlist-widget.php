<script>
    if (typeof window.wpStoreSettings === 'undefined') {
        window.wpStoreSettings = {
            restUrl: window.location.origin + '/wp-json/wp-store/v1/',
            nonce: '<?php echo esc_js($nonce); ?>'
        };
    }
</script>
<div x-data="{
        loading: false,
        items: [],
        message: '',
        initial: JSON.parse('<?php echo esc_js(wp_json_encode(isset($initial_items) ? $initial_items : [])); ?>'),
        async fetchWishlist() {
            this.loading = true;
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'wishlist', { credentials: 'same-origin' });
                const data = await res.json();
                if (!res.ok) { this.message = data.message || 'Gagal mengambil wishlist'; this.items = []; return; }
                this.items = data.items || [];
            } catch(e) { this.items = []; } finally { this.loading = false; }
        },
        formatPrice(val) {
            try {
                const p = Number(val || 0);
                return '<?php echo esc_js($currency); ?> ' + p.toLocaleString('id-ID');
            } catch(e) { return '<?php echo esc_js($currency); ?> ' + (val || 0); }
        },
        async remove(item) {
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'wishlist', {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': wpStoreSettings.nonce },
                    body: JSON.stringify({ id: item.id })
                });
                const data = await res.json();
                if (!res.ok) { return; }
                this.items = data.items || [];
                document.dispatchEvent(new CustomEvent('wp-store:wishlist-updated', { detail: data }));
            } catch(e) {}
        },
        async addToCart(item) {
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': wpStoreSettings.nonce },
                    body: JSON.stringify({ id: item.id, qty: 1, options: (item.options || {}) })
                });
                const data = await res.json();
                if (!res.ok) { return; }
                document.dispatchEvent(new CustomEvent('wp-store:cart-updated', { detail: data }));
            } catch(e) {}
        },
        async clear() {
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'wishlist', {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': wpStoreSettings.nonce },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (!res.ok) { return; }
                this.items = data.items || [];
                document.dispatchEvent(new CustomEvent('wp-store:wishlist-updated', { detail: data }));
            } catch(e) {}
        },
        init() {
            if (Array.isArray(this.initial) && this.initial.length) {
                this.items = this.initial;
            } else {
                this.fetchWishlist();
            }
            document.addEventListener('wp-store:wishlist-updated', (e) => {
                const data = e.detail || {};
                this.items = data.items || [];
            });
        }
    }" x-init="init()">
    <div class="">
        <div class="wps-flex wps-justify-between wps-items-center">
            <div class="wps-text-lg wps-font-medium wps-text-gray-900">Wishlist</div>
            <button class="wps-btn wps-btn-secondary wps-btn-sm" @click="clear()" x-show="items.length">Bersihkan</button>
        </div>
        <template x-if="!items.length">
            <div class="wps-text-sm wps-text-gray-600 wps-mt-4">Belum ada item di wishlist.</div>
        </template>
        <div class="wps-mt-4">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding: 8px; color:#6b7280; font-size:12px;">Produk</th>
                        <th style="text-align:left; padding: 8px; color:#6b7280; font-size:12px;">Nama</th>
                        <th style="text-align:right; padding: 8px; color:#6b7280; font-size:12px;">Harga</th>
                        <th style="text-align:right; padding: 8px; color:#6b7280; font-size:12px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr style="border-top: 1px solid #e5e7eb;">
                            <td style="padding: 8px;">
                                <img :src="item.image || '<?php echo esc_url(WP_STORE_URL . 'assets/frontend/img/noimg.webp'); ?>'" alt="" class="wps-img-40 wps-rounded">
                            </td>
                            <td style="padding: 8px;">
                                <a :href="item.link" class="wps-text-sm wps-text-gray-900" x-text="item.title"></a>
                            </td>
                            <td style="padding: 8px; text-align: right;">
                                <span class="wps-text-sm wps-text-gray-700" x-text="formatPrice(item.price)"></span>
                            </td>
                            <td style="padding: 8px; text-align: right;">
                                <div class="wps-flex wps-gap-2" style="justify-content: flex-end;">
                                    <button class="wps-btn wps-btn-primary wps-btn-sm" @click="addToCart(item)"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'cart', 'size' => 16, 'class' => 'wps-mr-2']); ?>Keranjang</button>
                                    <button class="wps-btn wps-btn-secondary wps-btn-sm" @click="remove(item)"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'close', 'size' => 16, 'class' => 'wps-mr-2']); ?>Hapus</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>