<?php

namespace WpStore\Frontend;

class Shortcode
{
    public function register()
    {
        add_shortcode('wp_store_shop', [$this, 'render_shop']);
        add_shortcode('wp_store_single', [$this, 'render_single']);
        add_shortcode('wp_store_related', [$this, 'render_related']);
        add_shortcode('wp_store_add_to_cart', [$this, 'render_add_to_cart']);
        add_shortcode('wp_store_cart', [$this, 'render_cart_widget']);
        add_shortcode('wp_store_checkout', [$this, 'render_checkout']);
        add_shortcode('store_checkout', [$this, 'render_checkout']);
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
                <div class="wps-grid wps-grid-cols-2 wps-md-grid-cols-4">
                    <?php
                    while ($query->have_posts()) :
                        $query->the_post();
                        $id = get_the_ID();
                        $price = get_post_meta($id, '_store_price', true);
                        $stock = get_post_meta($id, '_store_stock', true);
                        $image = get_the_post_thumbnail_url($id, 'medium');
                        $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
                    ?>
                        <div class="wps-card wps-card-hover wps-transition">
                            <div class="wps-p-2">
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
                                    <div><?php echo do_shortcode('[wp_store_add_to_cart size="sm"]'); ?></div>
                                    <a class="wps-btn wps-btn-secondary wps-btn-sm" href="<?php the_permalink(); ?>">Lihat Detail</a>
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

    public function render_checkout($atts = [])
    {
        wp_enqueue_script('alpinejs');
        $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
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
        <div class="wps-p-4">
            <div x-data="{
                    loading: false,
                    submitting: false,
                    cart: [],
                    total: 0,
                    name: '',
                    email: '',
                    phone: '',
                    address: '',
                    profile: { first_name: '', last_name: '', email: '', phone: '' },
                    addresses: [],
                    selectedAddressId: '',
                    provinces: [],
                    cities: [],
                    subdistricts: [],
                    selectedProvince: '',
                    selectedCity: '',
                    selectedSubdistrict: '',
                    postalCode: '',
                    notes: '',
                    isLoadingProvinces: false,
                    isLoadingCities: false,
                    isLoadingSubdistricts: false,
                    message: '',
                    currency: '<?php echo esc_js($currency); ?>',
                    formatPrice(value) {
                        const v = typeof value === 'number' ? value : parseFloat(value || 0);
                        if (this.currency === 'USD') {
                            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(v);
                        }
                        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
                    },
                    async fetchProfile() {
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'customer/profile', {
                                headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                            });
                            if (!res.ok) return;
                            this.profile = await res.json();
                        } catch(e) {}
                    },
                    async fetchAddresses() {
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'customer/addresses', {
                                headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                            });
                            if (!res.ok) return;
                            this.addresses = await res.json();
                        } catch(e) {}
                    },
                    useProfile() {
                        if (!this.profile) return;
                        const fullName = [this.profile.first_name || '', this.profile.last_name || ''].filter(Boolean).join(' ').trim();
                        this.name = fullName || this.name;
                        this.email = this.profile.email || this.email;
                        this.phone = this.profile.phone || this.phone;
                    },
                    async useAddressById() {
                        const addr = this.addresses.find(a => String(a.id) === String(this.selectedAddressId));
                        if (!addr) return;
                        await this.applyAddress(addr);
                    },
                    async applyAddress(addr) {
                        this.address = addr.address || '';
                        this.selectedProvince = addr.province_id ? String(addr.province_id) : '';
                        await this.loadCities();
                        this.selectedCity = addr.city_id ? String(addr.city_id) : '';
                        // If postal code provided, set
                        this.postalCode = addr.postal_code || this.postalCode;
                        await this.loadSubdistricts();
                        this.selectedSubdistrict = addr.subdistrict_id ? String(addr.subdistrict_id) : '';
                    },
                    async loadProvinces() {
                        this.isLoadingProvinces = true;
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/provinces', { headers: { 'X-WP-Nonce': wpStoreSettings.nonce } });
                            const data = await res.json();
                            this.provinces = data.data || [];
                        } catch(e) {
                            this.provinces = [];
                        } finally {
                            this.isLoadingProvinces = false;
                        }
                    },
                    async loadCities() {
                        if (!this.selectedProvince) { this.cities = []; return; }
                        this.isLoadingCities = true;
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/cities?province=' + encodeURIComponent(this.selectedProvince), { headers: { 'X-WP-Nonce': wpStoreSettings.nonce } });
                            const data = await res.json();
                            this.cities = data.data || [];
                        } catch(e) {
                            this.cities = [];
                        } finally {
                            this.isLoadingCities = false;
                        }
                    },
                    async loadSubdistricts() {
                        if (!this.selectedCity) { this.subdistricts = []; return; }
                        this.isLoadingSubdistricts = true;
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/subdistricts?city=' + encodeURIComponent(this.selectedCity), { headers: { 'X-WP-Nonce': wpStoreSettings.nonce } });
                            const data = await res.json();
                            this.subdistricts = data.data || [];
                        } catch(e) {
                            this.subdistricts = [];
                        } finally {
                            this.isLoadingSubdistricts = false;
                        }
                    },
                    async fetchCart() {
                        this.loading = true;
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
                        } finally {
                            this.loading = false;
                        }
                    },
                    async importFromProfile() {
                        await this.fetchProfile();
                        if (!this.profile || !this.profile.email) {
                            window.location.href = '<?php echo esc_js(site_url('/profil-saya/?tab=profile')); ?>';
                            return;
                        }
                        this.useProfile();
                    },
                    async submit() {
                        if (!this.name || this.cart.length === 0) {
                            this.message = 'Isi nama dan pastikan keranjang berisi.';
                            return;
                        }
                        this.submitting = true;
                        this.message = '';
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'checkout', {
                                method: 'POST',
                                credentials: 'include',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': wpStoreSettings.nonce
                                },
                                body: JSON.stringify({
                                    name: this.name,
                                    email: this.email,
                                    phone: this.phone,
                                    address: this.address,
                                    province_id: this.selectedProvince || '',
                                    province_name: (this.provinces.find(p => String(p.province_id) === String(this.selectedProvince)) || {}).province || '',
                                    city_id: this.selectedCity || '',
                                    city_name: (this.cities.find(c => String(c.city_id) === String(this.selectedCity)) || {}).city_name || '',
                                    subdistrict_id: this.selectedSubdistrict || '',
                                    subdistrict_name: (this.subdistricts.find(s => String(s.subdistrict_id) === String(this.selectedSubdistrict)) || {}).subdistrict_name || '',
                                    postal_code: this.postalCode || '',
                                    notes: this.notes || '',
                                    items: this.cart.map(i => ({ id: i.id, qty: i.qty }))
                                })
                            });
                            const data = await res.json();
                            if (!res.ok) {
                                this.message = data.message || 'Gagal membuat pesanan.';
                                return;
                            }
                            this.message = data.message || 'Pesanan berhasil dibuat.';
                            try {
                                await fetch(wpStoreSettings.restUrl + 'cart', {
                                    method: 'DELETE',
                                    credentials: 'include',
                                    headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                                });
                            } catch(_) {}
                            this.cart = [];
                            this.total = 0;
                            document.dispatchEvent(new CustomEvent('wp-store:cart-updated', { detail: { items: [], total: 0 } }));
                        } catch(e) {
                            this.message = 'Terjadi kesalahan jaringan.';
                        } finally {
                            this.submitting = false;
                        }
                    },
                    init() {
                        this.fetchCart();
                        this.fetchProfile();
                        this.fetchAddresses();
                        this.loadProvinces();
                    }
                }" x-init="init()">
                <div class="wps-grid wps-grid-cols-2">
                    <div class="wps-card">
                        <div class="wps-p-4">
                            <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Informasi Pemesan</div>
                            <div class="">
                                <div class="wps-callout-title">Gunakan Data Tersimpan</div>
                                <div class="wps-flex wps-items-center wps-gap-2 wps-mb-4">
                                    <button type="button" class="wps-btn wps-btn-primary" @click="importFromProfile()">Impor Profil</button>
                                    <a href="<?php echo esc_url(site_url('/profil-saya/?tab=profile')); ?>" class="wps-btn wps-btn-secondary">Kelola Profil</a>
                                </div>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Nama</label>
                                <input class="wps-input" type="text" x-model="name" placeholder="Nama lengkap">
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Email</label>
                                <input class="wps-input" type="email" x-model="email" placeholder="email@contoh.com">
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Telepon/WA</label>
                                <input class="wps-input" type="text" x-model="phone" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="wps-form-group" x-show="addresses && addresses.length">
                                <label class="wps-label wps-text-bold">Alamat Tersimpan</label>
                                <select class="wps-select" x-model="selectedAddressId" @change="useAddressById()">
                                    <option value="">-- Pilih alamat --</option>
                                    <template x-for="addr in addresses" :key="addr.id">
                                        <option :value="addr.id" x-text="(addr.label ? addr.label + ' - ' : '') + (addr.city_name || '')"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Provinsi</label>
                                <select class="wps-select" x-model="selectedProvince" @change="selectedCity=''; selectedSubdistrict=''; postalCode=''; loadCities()">
                                    <option value="">-- Pilih Provinsi --</option>
                                    <template x-for="prov in provinces" :key="prov.province_id">
                                        <option :value="prov.province_id" x-text="prov.province"></option>
                                    </template>
                                </select>
                                <div class="wps-text-xs wps-text-gray-500" x-show="isLoadingProvinces">Memuat provinsi...</div>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Kota/Kabupaten</label>
                                <select class="wps-select" x-model="selectedCity" @change="selectedSubdistrict=''; postalCode=(cities.find(c => String(c.city_id) === String(selectedCity)) || {}).postal_code || ''; loadSubdistricts()" :disabled="!selectedProvince">
                                    <option value="">-- Pilih Kota/Kabupaten --</option>
                                    <template x-for="c in cities" :key="c.city_id">
                                        <option :value="c.city_id" x-text="c.city_name"></option>
                                    </template>
                                </select>
                                <div class="wps-text-xs wps-text-gray-500" x-show="isLoadingCities">Memuat kota...</div>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Kecamatan</label>
                                <select class="wps-select" x-model="selectedSubdistrict" :disabled="!selectedCity">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <template x-for="s in subdistricts" :key="s.subdistrict_id">
                                        <option :value="s.subdistrict_id" x-text="s.subdistrict_name"></option>
                                    </template>
                                </select>
                                <div class="wps-text-xs wps-text-gray-500" x-show="isLoadingSubdistricts">Memuat kecamatan...</div>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Alamat Lengkap</label>
                                <textarea class="wps-textarea" rows="3" x-model="address" placeholder="Nama jalan, nomor rumah, RT/RW, patokan, dsb."></textarea>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Kode Pos</label>
                                <input class="wps-input" type="text" x-model="postalCode" placeholder="Contoh: 40285">
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Pesan Tambahan</label>
                                <textarea class="wps-textarea" rows="3" x-model="notes" placeholder="Catatan untuk pesanan, alamat lengkap, dll."></textarea>
                            </div>
                            <div class="wps-form-group">
                                <button type="button" class="wps-btn wps-btn-primary" @click="submit()" :disabled="submitting || cart.length === 0">
                                    <span x-show="!submitting">Buat Pesanan</span>
                                    <span x-show="submitting">Mengirim...</span>
                                </button>
                            </div>
                            <template x-if="message">
                                <div class="wps-alert wps-alert-success" x-text="message"></div>
                            </template>
                        </div>
                    </div>
                    <div class="wps-card">
                        <div class="wps-p-4">
                            <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Ringkasan Keranjang</div>
                            <template x-if="loading">
                                <div class="wps-text-sm wps-text-gray-500">Memuat keranjang...</div>
                            </template>
                            <template x-if="!loading && cart.length === 0">
                                <div class="wps-text-sm wps-text-gray-500">Keranjang kosong.</div>
                            </template>
                            <template x-for="item in cart" :key="item.id + ':' + (item.options ? JSON.stringify(item.options) : '')">
                                <div class="wps-flex wps-items-start wps-gap-2 wps-mb-4">
                                    <img :src="item.image" alt="" class="wps-img-40" x-show="item.image">
                                    <div style="flex:1">
                                        <div x-text="item.title" class="wps-text-sm wps-text-gray-900"></div>
                                        <template x-if="item.options && Object.keys(item.options).length">
                                            <div class="wps-text-xs wps-text-gray-500">
                                                <span x-text="Object.entries(item.options).map(([k,v]) => k + ': ' + v).join(' • ')"></span>
                                            </div>
                                        </template>
                                        <div class="wps-text-xs wps-text-gray-500">
                                            <span x-text="formatPrice(item.price)"></span>
                                            <span> × </span>
                                            <span x-text="item.qty"></span>
                                            <span> = </span>
                                            <span class="wps-text-gray-900" x-text="formatPrice(item.subtotal)"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div class="wps-flex wps-justify-between wps-items-center">
                                <span class="wps-text-sm wps-text-gray-500">Total</span>
                                <span class="wps-text-sm wps-text-gray-900" x-text="formatPrice(total)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    public function render_related($atts = [])
    {
        $atts = shortcode_atts([
            'id' => 0,
            'per_page' => 4,
        ], $atts);
        $id = (int) $atts['id'];
        if ($id <= 0) {
            $loop_id = get_the_ID();
            if ($loop_id && is_numeric($loop_id)) {
                $id = (int) $loop_id;
            }
        }
        if ($id <= 0 || get_post_type($id) !== 'store_product') {
            return '';
        }
        $per_page = (int) $atts['per_page'];
        if ($per_page <= 0 || $per_page > 12) {
            $per_page = 4;
        }

        $terms = wp_get_post_terms($id, 'store_product_cat', ['fields' => 'ids']);
        if (!is_array($terms) || empty($terms)) {
            return '';
        }

        $args = [
            'post_type' => 'store_product',
            'posts_per_page' => $per_page,
            'post_status' => 'publish',
            'post__not_in' => [$id],
            'tax_query' => [
                [
                    'taxonomy' => 'store_product_cat',
                    'field' => 'term_id',
                    'terms' => $terms,
                ],
            ],
        ];
        $query = new \WP_Query($args);

        $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');

        ob_start();
    ?>
        <div class="wps-p-4">
            <div class="wps-text-sm wps-text-gray-900 wps-mb-4">Produk Terkait</div>
            <?php if ($query->have_posts()) : ?>
                <div class="wps-grid wps-grid-cols-2 wps-md-grid-cols-4">
                    <?php
                    while ($query->have_posts()) :
                        $query->the_post();
                        $rid = get_the_ID();
                        $price = get_post_meta($rid, '_store_price', true);
                        $image = get_the_post_thumbnail_url($rid, 'medium');
                    ?>
                        <div class="wps-card wps-card-hover wps-transition">
                            <div class="wps-p-4">
                                <?php if ($image) : ?>
                                    <img class="wps-w-full wps-rounded wps-mb-4 wps-img-160" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                <?php endif; ?>
                                <a class="wps-text-sm wps-text-gray-900 wps-mb-2" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                <div class="wps-text-sm wps-text-gray-900 wps-mb-3">
                                    <?php
                                    if ($price !== '') {
                                        echo esc_html($currency . ' ' . number_format_i18n((float) $price, 0));
                                    }
                                    ?>
                                </div>
                                <div class="wps-flex wps-items-center wps-justify-between">
                                    <div><?php echo do_shortcode('[wp_store_add_to_cart id="' . esc_attr($rid) . '" size="sm"]'); ?></div>
                                    <a class="wps-btn wps-btn-secondary wps-btn-sm" href="<?php the_permalink(); ?>">Lihat</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="wps-text-sm wps-text-gray-500">Tidak ada produk terkait.</div>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    public function render_single($atts = [])
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);
        $id = (int) $atts['id'];
        if ($id <= 0) {
            $loop_id = get_the_ID();
            if ($loop_id && is_numeric($loop_id)) {
                $id = (int) $loop_id;
            }
        }
        if ($id <= 0 || get_post_type($id) !== 'store_product') {
            return '';
        }
        $price = get_post_meta($id, '_store_price', true);
        $stock = get_post_meta($id, '_store_stock', true);
        $image = get_the_post_thumbnail_url($id, 'large');
        $currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');

        $content = get_post_field('post_content', $id);
        $content = apply_filters('the_content', $content);

        ob_start();
    ?>
        <div class="wps-p-4">
            <div class="wps-text-lg wps-font-medium wps-text-gray-900 wps-mb-4"><?php echo esc_html(get_the_title($id)); ?></div>
            <div class="wps-flex wps-gap-4 wps-items-start">
                <div style="flex: 1;">
                    <?php if ($image) : ?>
                        <img class="wps-w-full wps-rounded wps-img-320" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title($id)); ?>">
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
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
                    <div class="wps-mb-4">
                        <?php echo do_shortcode('[wp_store_add_to_cart id="' . esc_attr($id) . '"]'); ?>
                    </div>
                    <div class="wps-text-sm wps-text-gray-500">
                        <?php echo $content; ?>
                    </div>
                </div>
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
            'label' => 'Tambah',
            'size' => ''
        ], $atts);
        $size = sanitize_key($atts['size']);
        $btn_class = 'wps-btn wps-btn-primary' . ($size === 'sm' ? ' wps-btn-sm' : '');
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
            <button type="button" @click="add()" :disabled="loading" class="<?php echo esc_attr($btn_class); ?>">
                <?php echo \WpStore\Frontend\Component::icon('cart', 20, 'wps-icon-20 wps-mr-2', 2); ?>
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
        $settings = get_option('wp_store_settings', []);
        $checkout_page_id = isset($settings['page_checkout']) ? absint($settings['page_checkout']) : 0;
        $checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : '';
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
                    <div class="wps-total-box">
                        <div class="wps-total-label">Total</div>
                        <div class="wps-total-amount" x-text="formatPrice(total)"></div>
                    </div>
                    <?php if (!empty($checkout_url)) : ?>
                        <a href="<?php echo esc_url($checkout_url); ?>" class="wps-btn wps-btn-primary wps-checkout-btn" x-show="cart.length > 0">Checkout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
