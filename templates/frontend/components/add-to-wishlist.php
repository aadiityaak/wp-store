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
        iconOnly: <?php echo isset($icon_only) && $icon_only ? 'true' : 'false'; ?>,
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
                const res = await fetch(wpStoreSettings.restUrl + 'wishlist', { 
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                });
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
                    credentials: 'same-origin',
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
                    credentials: 'same-origin',
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
    <button type="button"
        :disabled="loading"
        @click="inWishlist ? remove() : add()"
        class="<?php echo esc_attr($btn_class); ?>"
        :style="loading ? 'opacity:.7; pointer-events:none;' : ''">
        <template x-if="loading">
            <span>
                <?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'spinner', 'size' => 18, 'class' => 'wps-mr-0']); ?>
            </span>
        </template>
        <template x-if="!loading && inWishlist">
            <span>
                <?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'heart', 'size' => 18, 'stroke_color' => '#f472b6', 'class' => 'wps-mr-0']); ?>
            </span>
        </template>
        <template x-if="!loading && !inWishlist">
            <span>
                <?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'heart', 'size' => 18, 'class' => 'wps-mr-0']); ?>
            </span>
        </template>
        <span x-show="!iconOnly"><?php echo esc_html($label_add); ?></span>
    </button>
    <div x-show="toastShow" x-transition x-cloak
        :style="'position:fixed;bottom:30px;right:30px;padding:12px 16px;background:#fff;box-shadow:0 3px 10px rgba(0,0,0,.1);border-left:4px solid ' + (toastType === 'success' ? '#46b450' : '#d63638') + ';border-radius:4px;z-index:9999;'">
        <span x-text="toastMessage" class="wps-text-sm wps-text-gray-900"></span>
    </div>
</div>