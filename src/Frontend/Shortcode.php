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

        wp_register_script(
            'wp-store-frontend',
            WP_STORE_URL . 'assets/js/frontend/store.js',
            ['alpinejs'],
            WP_STORE_VERSION,
            true
        );

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
                                <button type="button" @click="addToCart(product)">Tambah</button>
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
                            <button type="button" @click="decrement(item)">-</button>
                            <span x-text="item.qty"></span>
                            <button type="button" @click="increment(item)">+</button>
                        </div>
                        <span x-text="formatPrice(item.qty * item.price)"></span>
                        <button type="button" @click="remove(item)">x</button>
                    </div>
                </template>
                <div class="wp-store-cart-total">
                    <span>Total</span>
                    <span x-text="formatPrice(total)"></span>
                </div>
                <form @submit.prevent="checkout">
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
