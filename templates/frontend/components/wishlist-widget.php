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
        async fetchWishlist() {
            this.loading = true;
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'wishlist', { credentials: 'include' });
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
            this.fetchWishlist();
            document.addEventListener('wp-store:wishlist-updated', (e) => {
                const data = e.detail || {};
                this.items = data.items || [];
            });
        }
    }" x-init="init()">
    <div class="wps-card">
        <div class="wps-p-4">
            <div class="wps-flex wps-justify-between wps-items-center">
                <div class="wps-text-lg wps-font-medium wps-text-gray-900">Wishlist</div>
                <button class="wps-btn wps-btn-secondary wps-btn-sm" @click="clear()" x-show="items.length">Bersihkan</button>
            </div>
            <template x-if="!items.length">
                <div class="wps-text-sm wps-text-gray-600 wps-mt-4">Belum ada item di wishlist.</div>
            </template>
            <div class="wps-divide-y wps-mt-4">
                <template x-for="item in items" :key="item.id">
                    <div class="wps-flex wps-items-center wps-py-2 wps-gap-3">
                        <img :src="item.image || '<?php echo esc_url(WP_STORE_URL . 'assets/frontend/img/noimg.webp'); ?>'" alt="" class="wps-img-40 wps-rounded">
                        <div class="wps-flex-1">
                            <a :href="item.link" class="wps-text-sm wps-text-gray-900" x-text="item.title"></a>
                            <div class="wps-text-xs wps-text-gray-600" x-text="formatPrice(item.price)"></div>
                        </div>
                        <div class="wps-flex wps-gap-2">
                            <button class="wps-btn wps-btn-primary wps-btn-sm" @click="addToCart(item)"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'cart', 'size' => 16, 'class' => 'wps-mr-2']); ?>Keranjang</button>
                            <button class="wps-btn wps-btn-secondary wps-btn-sm" @click="remove(item)"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'close', 'size' => 16, 'class' => 'wps-mr-2']); ?>Hapus</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
