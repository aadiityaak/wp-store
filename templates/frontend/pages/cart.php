<div x-data="wpStoreCartPage()" x-init="init()">
    <div class="wps-container">
        <div class="wps-card wps-p-4">
            <div class="wps-card-body">
                <?php echo \WpStore\Frontend\Template::render('components/cart-list-page'); ?>
            </div>
            <div class="wps-card-footer wps-flex wps-justify-between wps-items-center">
                <div class="wps-total-box">
                    <div class="wps-total-label">Total</div>
                    <div class="wps-total-amount" x-text="formatPrice(total)"></div>
                </div>
                <div class="wps-flex wps-gap-2">
                    <a :href="wpStoreSettings.checkoutUrl" class="wps-btn wps-btn-primary" x-show="cart.length > 0"><?php echo wps_icon(['name' => 'credit-card', 'size' => 16, 'class' => 'wps-mr-2']); ?>Lanjut ke Pembayaran</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('wpStoreCartPage', () => ({
            loading: false,
            updatingKey: '',
            cart: [],
            total: 0,
            currency: '<?php echo esc_js(($currency ?? 'Rp')); ?>',
            formatPrice(value) {
                const v = typeof value === 'number' ? value : parseFloat(value || 0);
                if (this.currency === 'USD') {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD',
                        minimumFractionDigits: 0
                    }).format(v);
                }
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(v);
            },
            getItemKey(i) {
                const opts = i && i.options ? i.options : {};
                let s = '';
                try {
                    s = JSON.stringify(opts);
                } catch (e) {
                    s = '';
                }
                return String(i.id) + ':' + s;
            },
            async fetchCart() {
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                        credentials: 'same-origin',
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    const data = await res.json();
                    this.cart = data.items || [];
                    this.total = data.total || 0;
                } catch (e) {
                    this.cart = [];
                    this.total = 0;
                }
            },
            async updateItem(item, qty) {
                this.loading = true;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpStoreSettings.nonce
                        },
                        body: JSON.stringify({
                            id: item.id,
                            qty,
                            options: (item.options || {})
                        })
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        return;
                    }
                    this.cart = data.items || [];
                    this.total = data.total || 0;
                    document.dispatchEvent(new CustomEvent('wp-store:cart-updated', {
                        detail: data
                    }));
                } catch (e) {} finally {
                    this.loading = false;
                    this.updatingKey = '';
                }
            },
            increment(item) {
                this.updateItem(item, item.qty + 1);
            },
            decrement(item) {
                const q = item.qty > 1 ? item.qty - 1 : 0;
                this.updateItem(item, q);
            },
            remove(item) {
                this.updatingKey = this.getItemKey(item);
                this.updateItem(item, 0);
            },
            init() {
                this.fetchCart();
                document.addEventListener('wp-store:cart-updated', (e) => {
                    const data = e.detail || {};
                    this.cart = data.items || [];
                    this.total = data.total || 0;
                });
            }
        }));
    });
</script>