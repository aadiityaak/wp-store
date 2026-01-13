<?php

namespace WpStore\Frontend;

class Shortcode
{
    public function register()
    {
        add_shortcode('wp_store_shop', [$this, 'render_shop']);
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
        wp_enqueue_script('alpinejs');
        wp_enqueue_script('wp-store-frontend');

        $atts = shortcode_atts([
            'per_page' => 12,
        ], $atts);

        $per_page = (int) $atts['per_page'];
        if ($per_page <= 0 || $per_page > 50) {
            $per_page = 12;
        }

        ob_start();
?>
        <script>
            if (typeof window.wpStoreSettings === 'undefined') {
                window.wpStoreSettings = <?php echo json_encode([
                                                'restUrl' => esc_url_raw(rest_url('wp-store/v1/')),
                                                'nonce' => wp_create_nonce('wp_rest'),
                                            ]); ?>;
            }
        </script>
        <script>
            window.wpStore = window.wpStore || function(perPage) {
                var upgraded = false;
                var self = {
                    loading: false,
                    products: [],
                    cart: [],
                    perPage: perPage || 12,
                    page: 1,
                    customer: {
                        name: '',
                        email: '',
                        phone: ''
                    },
                    submitting: false,
                    message: '',
                    get total() {
                        try {
                            return (Array.isArray(this.cart) ? this.cart : []).reduce(function(sum, item) {
                                var price = typeof item.price === 'number' ? item.price : parseFloat(item.price || 0);
                                var qty = typeof item.qty === 'number' ? item.qty : parseInt(item.qty || 0, 10);
                                return sum + (price * qty);
                            }, 0);
                        } catch (e) {
                            return 0;
                        }
                    },
                    formatPrice: function(v) {
                        return String(v || '');
                    },
                    fetchProducts: async function() {
                        this.loading = true;
                        try {
                            var base = window.wpStoreSettings && window.wpStoreSettings.restUrl ? window.wpStoreSettings.restUrl : '';
                            if (!base) {
                                return;
                            }
                            var url = new URL(base + 'products');
                            url.searchParams.set('per_page', this.perPage);
                            url.searchParams.set('page', this.page);
                            var response = await fetch(url.toString());
                            if (!response.ok) {
                                return;
                            }
                            var data = await response.json();
                            this.products = Array.isArray(data.items) ? data.items : [];
                        } catch (e) {} finally {
                            this.loading = false;
                        }
                    },
                    init: function() {
                        if (!upgraded && window.wpStoreReady && typeof window.wpStore === 'function') {
                            var real = window.wpStore(self.perPage);
                            upgraded = true;
                            for (var k in real) {
                                try {
                                    self[k] = real[k];
                                } catch (e) {}
                            }
                            if (typeof self.init === 'function') {
                                self.init();
                                return;
                            }
                        }
                        if (!upgraded && typeof self.fetchProducts === 'function') {
                            self.fetchProducts();
                        }
                    }
                };
                document.addEventListener('wp-store:ready', function() {
                    if (!upgraded) {
                        self.init();
                    }
                });
                return self;
            };
        </script>
        <div x-data="wpStore(<?php echo esc_attr($per_page); ?>)" x-init="init()" class="wp-store-wrapper">
            <div class="wp-store-products">
                <template x-if="loading">
                    <div>Memuat produk...</div>
                </template>
                <template x-if="!loading && products.length === 0">
                    <div>Belum ada produk.</div>
                </template>
                <div class="wp-store-grid">
                    <template x-for="product in products" :key="product.id">
                        <div class="wp-store-card">
                            <div class="wp-store-image" x-show="product.image">
                                <img :src="product.image" :alt="product.title">
                            </div>
                            <h3 x-text="product.title"></h3>
                            <p x-text="product.excerpt"></p>
                            <div class="wp-store-product-details">
                                <span x-text="formatPrice(product.price)"></span>
                                <span x-show="product.stock !== null"> | Stok: <span x-text="product.stock"></span></span>
                            </div>
                            <div class="wp-store-card-footer">
                                <button type="button" @click="typeof addToCart==='function' && addToCart(product)">Tambah</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="wp-store-cart" x-show="cart.length > 0">
                <h3>Keranjang</h3>
                <template x-for="item in cart" :key="item.id">
                    <div class="wp-store-cart-item">
                        <span x-text="item.title"></span>
                        <div class="wp-store-cart-qty">
                            <button type="button" @click="typeof decrement==='function' && decrement(item)">-</button>
                            <span x-text="item.qty"></span>
                            <button type="button" @click="typeof increment==='function' && increment(item)">+</button>
                        </div>
                        <span x-text="formatPrice(item.qty * item.price)"></span>
                        <button type="button" @click="typeof remove==='function' && remove(item)">x</button>
                    </div>
                </template>
                <div class="wp-store-cart-total">
                    <span>Total</span>
                    <span x-text="formatPrice(total)"></span>
                </div>
                <form @submit.prevent="typeof checkout==='function' && checkout()">
                    <input type="text" x-model="customer.name" placeholder="Nama">
                    <input type="email" x-model="customer.email" placeholder="Email">
                    <input type="text" x-model="customer.phone" placeholder="No. HP">
                    <button type="submit" x-bind:disabled="submitting">
                        <span x-show="!submitting">Kirim Pesanan</span>
                        <span x-show="submitting">Mengirim...</span>
                    </button>
                </form>
                <p x-text="message"></p>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
