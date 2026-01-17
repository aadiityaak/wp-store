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
        inWishlist: false,
        toastShow: false,
        toastType: 'success',
        toastMessage: '',
        showToast(msg, type) {
            this.toastMessage = msg || '';
            this.toastType = type === 'error' ? 'error' : 'success';
            this.toastShow = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toastShow = false; }, 2000);
        },
        async refreshState() {
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'wishlist', { credentials: 'include' });
                const data = await res.json();
                const items = Array.isArray(data.items) ? data.items : [];
                this.inWishlist = !!items.find((i) => i.id === <?php echo (int) $id; ?>);
            } catch (e) { this.inWishlist = false; }
        },
        async add() {
            this.loading = true;
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'wishlist', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpStoreSettings.nonce
                    },
                    body: JSON.stringify({ id: <?php echo (int) $id; ?> })
                });
                const data = await res.json();
                if (!res.ok) { this.showToast(data.message || 'Gagal menambah', 'error'); return; }
                document.dispatchEvent(new CustomEvent('wp-store:wishlist-updated', { detail: data }));
                this.inWishlist = true;
                this.showToast('Ditambahkan ke wishlist', 'success');
            } catch (e) {
                this.showToast('Kesalahan jaringan', 'error');
            } finally { this.loading = false; }
        },
        async remove() {
            this.loading = true;
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'wishlist', {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpStoreSettings.nonce
                    },
                    body: JSON.stringify({ id: <?php echo (int) $id; ?> })
                });
                const data = await res.json();
                if (!res.ok) { this.showToast(data.message || 'Gagal menghapus', 'error'); return; }
                document.dispatchEvent(new CustomEvent('wp-store:wishlist-updated', { detail: data }));
                this.inWishlist = false;
                this.showToast('Dihapus dari wishlist', 'success');
            } catch (e) {
                this.showToast('Kesalahan jaringan', 'error');
            } finally { this.loading = false; }
        },
        init() { this.refreshState(); document.addEventListener('wp-store:wishlist-updated', () => this.refreshState()); }
    }" x-init="init()">
    <template x-if="!inWishlist">
        <button type="button" @click="add()" :disabled="loading" class="<?php echo esc_attr($btn_class); ?>">
            <?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'heart', 'size' => 18, 'class' => 'wps-mr-2']); ?>
            <?php echo esc_html($label_add); ?>
        </button>
    </template>
    <template x-if="inWishlist">
        <button type="button" @click="remove()" :disabled="loading" class="<?php echo esc_attr($btn_class); ?>">
            <?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'close', 'size' => 18, 'class' => 'wps-mr-2']); ?>
            <?php echo esc_html($label_remove); ?>
        </button>
    </template>
    <div x-show="toastShow" x-transition x-cloak
        :style="'position:fixed;bottom:30px;right:30px;padding:12px 16px;background:#fff;box-shadow:0 3px 10px rgba(0,0,0,.1);border-left:4px solid ' + (toastType === 'success' ? '#46b450' : '#d63638') + ';border-radius:4px;z-index:9999;'">
        <span x-text="toastMessage" class="wps-text-sm wps-text-gray-900"></span>
    </div>
</div>
