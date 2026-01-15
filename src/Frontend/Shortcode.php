<?php

namespace WpStore\Frontend;

class Shortcode
{
    public function register()
    {
        add_shortcode('wp_store_shop', [$this, 'render_shop']);
        add_shortcode('wp_store_add_to_cart', [$this, 'render_add_to_cart']);
        add_shortcode('wp_store_cart', [$this, 'render_cart_widget']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts()
    {
        wp_register_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            null,
            true
        );
        wp_add_inline_script('alpinejs', 'window.deferLoadingAlpineJs = true;', 'before');

        wp_register_script(
            'wp-store-frontend',
            WP_STORE_URL . 'assets/frontend/js/store.js',
            ['alpinejs'],
            WP_STORE_VERSION,
            true
        );

        wp_register_style(
            'wp-store-frontend-css',
            WP_STORE_URL . 'assets/frontend/css/style.css',
            [],
            WP_STORE_VERSION
        );

        wp_enqueue_style('wp-store-frontend-css');

        wp_localize_script(
            'wp-store-frontend',
            'wpStoreSettings',
            [
                'restUrl' => esc_url_raw(rest_url('wp-store/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
            ]
        );
    }

    public function render_shop($atts = [])
    {
        $atts = shortcode_atts([
            'per_page' => 12,
        ], $atts);

        $per_page = (int) $atts['per_page'];
        if ($per_page <= 0 || $per_page > 50) {
            $per_page = 12;
        }

        $args = [
            'post_type' => 'store_product',
            'posts_per_page' => $per_page,
            'post_status' => 'publish',
        ];

        $query = new \WP_Query($args);

        ob_start();
?>
        <div class="wps-p-4">
            <?php if ($query->have_posts()) : ?>
                <div class="wps-grid wps-grid-cols-2 wps-grid-cols-4">
                    <?php
                    while ($query->have_posts()) :
                        $query->the_post();
                        $id = get_the_ID();
                        $price = get_post_meta($id, '_store_price', true);
                        $stock = get_post_meta($id, '_store_stock', true);
                        $image = get_the_post_thumbnail_url($id, 'medium');
                        $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
                    ?>
                        <div class="wps-card">
                            <div class="wps-p-4">
                                <?php if ($image) : ?>
                                    <img class="wps-w-full wps-rounded wps-mb-4 wps-img-160" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                <?php endif; ?>
                                <a class="wps-text-sm wps-text-gray-900 wps-mb-4" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                <div class="wps-text-sm wps-text-gray-900 wps-mb-4">
                                    <?php
                                    if ($price !== '') {
                                        echo esc_html($currency . ' ' . number_format_i18n((float) $price, 0));
                                    }
                                    ?>
                                    <?php if ($stock !== '') : ?>
                                        <span class="wps-text-gray-500"> • Stok: <?php echo esc_html((int) $stock); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="wps-flex wps-items-center wps-justify-between">
                                    <div><?php echo do_shortcode('[wp_store_add_to_cart]'); ?></div>
                                    <a class="wps-btn wps-btn-secondary" href="<?php the_permalink(); ?>">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="wps-text-sm wps-text-gray-500">Belum ada produk.</div>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }


    public function render_add_to_cart($atts = [])
    {
        wp_enqueue_script('alpinejs');
        $atts = shortcode_atts([
            'id' => 0,
            'label' => 'Tambah'
        ], $atts);
        $id = (int) $atts['id'];
        if ($id <= 0) {
            $loop_id = get_the_ID();
            if ($loop_id && is_numeric($loop_id)) {
                $id = (int) $loop_id;
            }
        }
        if ($id > 0 && get_post_type($id) !== 'store_product') {
            return '';
        }
        if ($id <= 0) {
            return '';
        }
        $basic_name = get_post_meta($id, '_store_option_name', true);
        $basic_values = get_post_meta($id, '_store_options', true);
        $adv_name = get_post_meta($id, '_store_option2_name', true);
        $adv_values = get_post_meta($id, '_store_advanced_options', true);
        ob_start();
    ?>
        <script>
            if (typeof window.wpStoreSettings === 'undefined') {
                window.wpStoreSettings = {
                    restUrl: window.location.origin + '/wp-json/wp-store/v1/',
                    nonce: '<?php echo wp_create_nonce('wp_rest'); ?>'
                };
            }
        </script>
        <div x-data="{
                loading: false,
                message: '',
                showModal: false,
                basicName: '<?php echo esc_js($basic_name ?: ''); ?>',
                basicOptions: JSON.parse('<?php echo esc_js(wp_json_encode(is_array($basic_values) ? array_values($basic_values) : [])); ?>'),
                advName: '<?php echo esc_js($adv_name ?: ''); ?>',
                advOptions: JSON.parse('<?php echo esc_js(wp_json_encode(is_array($adv_values) ? array_values($adv_values) : [])); ?>'),
                selectedBasic: '',
                selectedAdv: '',
                hasOptions() {
                    return ((this.basicName && this.basicOptions.length > 0) || (this.advName && this.advOptions.length > 0));
                },
                canSubmit() {
                    const needBasic = !!(this.basicName && this.basicOptions.length);
                    const needAdv = !!(this.advName && this.advOptions.length);
                    return (!needBasic || !!this.selectedBasic) && (!needAdv || !!this.selectedAdv);
                },
                getOptionsPayload() {
                    const opts = {};
                    const bName = (this.basicName || '').trim();
                    const bVal = (this.selectedBasic || '').trim();
                    const aName = (this.advName || '').trim();
                    const aVal = (this.selectedAdv || '').trim();
                    if (bName && bVal) { opts[bName] = bVal; }
                    if (aName && aVal) { opts[aName] = aVal; }
                    return opts;
                },
                async add() {
                    if (this.hasOptions()) {
                        this.showModal = true;
                        return;
                    }
                    await this.confirmAdd();
                },
                async confirmAdd() {
                    this.loading = true;
                    try {
                        let currentQty = 0;
                        try {
                            const resCart = await fetch(wpStoreSettings.restUrl + 'cart', { 
                                credentials: 'include',
                                headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                            });
                            const dataCart = await resCart.json();
                            const opts = this.getOptionsPayload();
                            const item = (dataCart.items || []).find((i) => {
                                if (i.id !== <?php echo (int) $id; ?>) return false;
                                const a = i.options || {};
                                const b = opts || {};
                                return JSON.stringify(a) === JSON.stringify(b);
                            });
                            currentQty = item ? (item.qty || 0) : 0;
                        } catch (e) {}
                        const nextQty = currentQty + 1;
                        const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': wpStoreSettings.nonce
                            },
                            body: JSON.stringify({ id: <?php echo esc_attr($id); ?>, qty: nextQty, options: this.getOptionsPayload() })
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.message = data.message || 'Gagal menambah';
                            return;
                        }
                        document.dispatchEvent(new CustomEvent('wp-store:cart-updated', { detail: data }));
                        this.message = 'Ditambahkan';
                        this.showModal = false;
                        setTimeout(() => { this.message = ''; }, 1500);
                    } catch (e) {
                        this.message = 'Kesalahan jaringan';
                    } finally {
                        this.loading = false;
                    }
                }
            }">
            <button type="button" @click="add()" :disabled="loading" class="wps-btn wps-btn-primary">
                <?php echo \WpStore\Frontend\Component::icon('cart', 24, 'wps-icon-24 wps-mr-2', 2); ?>
                <?php echo esc_html($atts['label']); ?>
            </button>
            <span x-text="message"></span>
            <div x-show="showModal" x-cloak class="wps-modal-backdrop" @click.self="showModal = false"></div>
            <div x-show="showModal" x-cloak class="wps-modal">
                <div class="wps-p-4">
                    <div class="wps-mb-4 wps-text-lg wps-font-medium wps-text-gray-900">Pilih Opsi</div>
                    <div class="wps-mb-4" x-show="basicName && basicOptions.length" x-cloak>
                        <label class="wps-label" x-text="basicName"></label>
                        <select class="wps-select" x-model="selectedBasic">
                            <option value="">-- Pilih --</option>
                            <template x-for="opt in basicOptions" :key="opt">
                                <option :value="opt" x-text="opt"></option>
                            </template>
                        </select>
                    </div>
                    <div class="wps-mb-4" x-show="advName && advOptions.length" x-cloak>
                        <label class="wps-label" x-text="advName"></label>
                        <select class="wps-select" x-model="selectedAdv">
                            <option value="">-- Pilih --</option>
                            <template x-for="opt in advOptions" :key="opt.label">
                                <option :value="opt.label" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="wps-flex wps-justify-between wps-items-center">
                        <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm" @click="showModal = false">Batal</button>
                        <button type="button" class="wps-btn wps-btn-primary wps-btn-sm" @click="confirmAdd()" :disabled="!canSubmit()">Tambah</button>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    public function render_cart_widget($atts = [])
    {
        wp_enqueue_script('alpinejs');
        ob_start();
    ?>
        <script>
            if (typeof window.wpStoreSettings === 'undefined') {
                window.wpStoreSettings = {
                    restUrl: window.location.origin + '/wp-json/wp-store/v1/',
                    nonce: '<?php echo wp_create_nonce('wp_rest'); ?>'
                };
            }
        </script>
        <div x-data="{
                open: false,
                loading: false,
                cart: [],
                total: 0,
                currency: '<?php echo esc_js((get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp')); ?>',
                formatPrice(value) {
                    const v = typeof value === 'number' ? value : parseFloat(value || 0);
                    if (this.currency === 'USD') {
                        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(v);
                    }
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
                },
                async fetchCart() {
                    try {
                        const res = await fetch(wpStoreSettings.restUrl + 'cart', { 
                            credentials: 'include',
                            headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                        });
                        const data = await res.json();
                        this.cart = data.items || [];
                        this.total = data.total || 0;
                    } catch(e) {
                        this.cart = [];
                        this.total = 0;
                    }
                },
                async updateItem(item, qty) {
                    this.loading = true;
                    try {
                        const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': wpStoreSettings.nonce
                            },
                            body: JSON.stringify({ id: item.id, qty, options: (item.options || {}) })
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            return;
                        }
                        this.cart = data.items || [];
                        this.total = data.total || 0;
                        document.dispatchEvent(new CustomEvent('wp-store:cart-updated', { detail: data }));
                    } catch(e) {
                    } finally {
                        this.loading = false;
                    }
                },
                increment(item) { this.updateItem(item, item.qty + 1); },
                decrement(item) { const q = item.qty > 1 ? item.qty - 1 : 0; this.updateItem(item, q); },
                remove(item) { this.updateItem(item, 0); },
                init() {
                    this.fetchCart();
                    document.addEventListener('wp-store:cart-updated', (e) => {
                        const data = e.detail || {};
                        this.cart = data.items || [];
                        this.total = data.total || 0;
                    });
                }
            }" x-init="init()" class="wps-rel">
            <button type="button" @click="open = true" class="wps-btn-icon wps-cart-button wps-rel">
                <span>🛒</span>
                <span x-text="cart.reduce((sum, item) => sum + (item.qty || 0), 0)" class="wps-absolute wps-top--6 wps-right--10 wps-bg-blue-500 wps-text-white wps-text-xs rounded-full wps-px-2.5 wps-py-0.5"></span>
            </button>
            <div class="wps-offcanvas-backdrop" x-show="open" @click="open = false" x-transition.opacity></div>
            <div class="wps-offcanvas" x-show="open" x-transition>
                <div class="wps-offcanvas-header">
                    <strong class="wps-text-gray-900">Keranjang</strong>
                    <button type="button" @click="open = false" class="wps-btn-icon">✕</button>
                </div>
                <div class="wps-offcanvas-body">
                    <template x-if="cart.length === 0">
                        <div class="wps-text-sm wps-text-gray-500">Keranjang kosong.</div>
                    </template>
                    <template x-for="item in cart" :key="item.id + ':' + (item.options ? JSON.stringify(item.options) : '')">
                        <div class="wps-flex wps-items-center wps-gap-2 wps-divider">
                            <img :src="item.image" alt="" class="wps-img-40" x-show="item.image">
                            <div style="flex: 1;">
                                <div x-text="item.title" class="wps-text-sm wps-text-gray-900"></div>
                                <template x-if="item.options && Object.keys(item.options).length">
                                    <div class="wps-text-xs wps-text-gray-500">
                                        <span x-text="Object.entries(item.options).map(([k,v]) => k + ': ' + v).join(' • ')"></span>
                                    </div>
                                </template>
                                <div class="wps-text-xs wps-text-gray-500 wps-mb-1">
                                    <span x-text="formatPrice(item.price)"></span>
                                    <span> × </span>
                                    <span x-text="item.qty"></span>
                                    <span> = </span>
                                    <span class="wps-text-gray-900" x-text="formatPrice(item.subtotal)"></span>
                                </div>
                                <div class="wps-flex wps-items-center wps-gap-1">
                                    <button type="button" @click="decrement(item)" class="wps-btn wps-btn-secondary wps-btn-sm">-</button>
                                    <span x-text="item.qty" class="wps-badge wps-badge-sm"></span>
                                    <button type="button" @click="increment(item)" class="wps-btn wps-btn-secondary wps-btn-sm">+</button>
                                    <button type="button" @click="remove(item)" class="wps-btn wps-btn-danger wps-btn-sm wps-ml-auto">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="wps-offcanvas-footer">
                    <span class="wps-text-sm wps-text-gray-500">Total</span>
                    <span x-text="formatPrice(total)" class="wps-text-sm wps-text-gray-900"></span>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
