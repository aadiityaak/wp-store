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
        <div class="wp-store-wrapper">
            <div class="wp-store-products">
                <?php if ($query->have_posts()) : ?>
                    <div class="wp-store-grid">
                        <?php
                        while ($query->have_posts()) :
                            $query->the_post();
                            $id = get_the_ID();
                            $price = get_post_meta($id, '_store_price', true);
                            $stock = get_post_meta($id, '_store_stock', true);
                            $image = get_the_post_thumbnail_url($id, 'medium');
                        ?>
                            <div class="wp-store-card">
                                <?php if ($image) : ?>
                                    <div class="wp-store-image">
                                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                    </div>
                                <?php endif; ?>
                                <h3><?php the_title(); ?></h3>
                                <p><?php echo esc_html(wp_trim_words(get_the_content(), 20)); ?></p>
                                <div class="wp-store-product-details">
                                    <span>
                                        <?php
                                        if ($price !== '') {
                                            echo esc_html(number_format_i18n((float) $price, 0));
                                        }
                                        ?>
                                    </span>
                                    <?php if ($stock !== '') : ?>
                                        <span> | Stok: <?php echo esc_html((int) $stock); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="wp-store-card-footer">
                                    <a href="<?php the_permalink(); ?>">Lihat Detail</a>
                                </div>
                                <div>
                                    <?php echo do_shortcode('[wp_store_add_to_cart]'); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <div>Belum ada produk.</div>
                <?php endif; ?>
            </div>
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
                async add() {
                    this.loading = true;
                    try {
                        let currentQty = 0;
                        try {
                            const resCart = await fetch(wpStoreSettings.restUrl + 'cart', { 
                                credentials: 'include',
                                headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                            });
                            const dataCart = await resCart.json();
                            const item = (dataCart.items || []).find((i) => i.id === <?php echo (int) $id; ?>);
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
                            body: JSON.stringify({ id: <?php echo esc_attr($id); ?>, qty: nextQty })
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.message = data.message || 'Gagal menambah';
                            return;
                        }
                        document.dispatchEvent(new CustomEvent('wp-store:cart-updated', { detail: data }));
                        this.message = 'Ditambahkan';
                        setTimeout(() => { this.message = ''; }, 1500);
                    } catch (e) {
                        this.message = 'Kesalahan jaringan';
                    } finally {
                        this.loading = false;
                    }
                }
            }">
            <button type="button" @click="add()" :disabled="loading"><?php echo esc_html($atts['label']); ?></button>
            <span x-text="message"></span>
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
                async updateItem(id, qty) {
                    this.loading = true;
                    try {
                        const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': wpStoreSettings.nonce
                            },
                            body: JSON.stringify({ id, qty })
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
                increment(item) { this.updateItem(item.id, item.qty + 1); },
                decrement(item) { const q = item.qty > 1 ? item.qty - 1 : 0; this.updateItem(item.id, q); },
                remove(item) { this.updateItem(item.id, 0); },
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
                    <template x-for="item in cart" :key="item.id">
                        <div class="wps-flex wps-items-center wps-gap-2 wps-divider">
                            <img :src="item.image" alt="" class="wps-img-40" x-show="item.image">
                            <div style="flex: 1;">
                                <div x-text="item.title" class="wps-text-sm wps-text-gray-900"></div>
                                <div class="wps-flex wps-items-center wps-gap-2">
                                    <button type="button" @click="decrement(item)" class="wps-btn wps-btn-secondary">-</button>
                                    <span x-text="item.qty" class="wps-badge"></span>
                                    <button type="button" @click="increment(item)" class="wps-btn wps-btn-secondary">+</button>
                                    <button type="button" @click="remove(item)" class="wps-btn wps-btn-danger" style="margin-left: auto;">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="wps-offcanvas-footer">
                    <span class="wps-text-sm wps-text-gray-500">Total</span>
                    <span x-text="total" class="wps-text-sm wps-text-gray-900"></span>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
