document.addEventListener('alpine:init', () => {
    Alpine.data('wpStore', (perPage) => ({
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
        async init() {
            await this.fetchCart();
            await this.fetchProducts();
        },
        async fetchCart() {
            try {
                const response = await fetch(wpStoreSettings.restUrl + 'cart', {
                    credentials: 'same-origin'
                });
                if (!response.ok) {
                    throw new Error('Gagal mengambil keranjang');
                }
                const data = await response.json();
                this.cart = data.items || [];
            } catch (e) {
                this.cart = [];
            }
        },
        async fetchProducts() {
            this.loading = true;
            try {
                const url = new URL(wpStoreSettings.restUrl + 'products');
                url.searchParams.set('per_page', this.perPage);
                url.searchParams.set('page', this.page);
                const response = await fetch(url.toString());
                if (!response.ok) {
                    throw new Error('Gagal mengambil produk');
                }
                const data = await response.json();
                this.products = data.items || [];
            } catch (e) {
            } finally {
                this.loading = false;
            }
        },
        async addToCart(product) {
            const existing = this.cart.find((item) => item.id === product.id);
            const nextQty = existing ? (existing.qty + 1) : 1;
            await this.updateCartItem(product.id, nextQty);
        },
        async increment(item) {
            await this.updateCartItem(item.id, item.qty + 1);
        },
        async decrement(item) {
            const nextQty = item.qty > 1 ? (item.qty - 1) : 0;
            await this.updateCartItem(item.id, nextQty);
        },
        async remove(item) {
            await this.updateCartItem(item.id, 0);
        },
        get total() {
            return this.cart.reduce((sum, item) => sum + item.price * item.qty, 0);
        },
        async updateCartItem(id, qty) {
            try {
                const response = await fetch(wpStoreSettings.restUrl + 'cart', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpStoreSettings.nonce
                    },
                    body: JSON.stringify({ id, qty })
                });
                const data = await response.json();
                if (!response.ok) {
                    this.message = data.message || 'Gagal update keranjang.';
                    return;
                }
                this.cart = data.items || [];
            } catch (e) {
                this.message = 'Terjadi kesalahan jaringan.';
            }
        },
        formatPrice(value) {
            if (typeof value !== 'number') {
                value = parseFloat(value || 0);
            }
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        },
        async checkout() {
            if (!this.customer.name || this.cart.length === 0) {
                this.message = 'Isi nama dan keranjang terlebih dahulu.';
                return;
            }
            this.submitting = true;
            this.message = '';
            try {
                const response = await fetch(wpStoreSettings.restUrl + 'checkout', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpStoreSettings.nonce
                    },
                    body: JSON.stringify({
                        name: this.customer.name,
                        email: this.customer.email,
                        phone: this.customer.phone,
                        items: this.cart.map((item) => ({
                            id: item.id,
                            qty: item.qty
                        }))
                    })
                });
                const data = await response.json();
                if (!response.ok) {
                    this.message = data.message || 'Gagal mengirim pesanan.';
                    return;
                }
                this.message = data.message || 'Pesanan berhasil dibuat.';
                this.cart = [];
            } catch (e) {
                this.message = 'Terjadi kesalahan jaringan.';
            } finally {
                this.submitting = false;
            }
        }
    }));
});
