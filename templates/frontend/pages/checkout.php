<script>
    if (typeof window.wpStoreSettings === 'undefined') {
        window.wpStoreSettings = {
            restUrl: window.location.origin + '/wp-json/wp-store/v1/',
            nonce: '<?php echo esc_js($nonce); ?>'
        };
    }
</script>
<script>
    window.wpStoreCheckout = function() {
        return {
            loading: false,
            submitting: false,
            cart: [],
            total: 0,
            name: '',
            email: '',
            phone: '',
            address: '',
            profile: {
                first_name: '',
                last_name: '',
                email: '',
                phone: ''
            },
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
            originSubdistrict: '<?php echo esc_js($origin_subdistrict); ?>',
            shippingCouriers: <?php echo json_encode($active_couriers); ?>,
            shippingCourier: '',
            shippingService: '',
            shippingCost: 0,
            shippingOptions: [],
            selectedShippingKey: '',
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
            async calculateAllShipping() {
                if (!this.selectedSubdistrict || !Array.isArray(this.shippingCouriers) || this.shippingCouriers.length === 0) {
                    return;
                }
                this.shippingOptions = [];
                this.selectedShippingKey = '';
                this.shippingCourier = '';
                this.shippingService = '';
                this.shippingCost = 0;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/calculate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpStoreSettings.nonce
                        },
                        body: JSON.stringify({
                            destination_subdistrict: this.selectedSubdistrict,
                            courier: this.shippingCouriers.join(':')
                        })
                    });
                    const data = await res.json();
                    if (res.ok && data && data.success && Array.isArray(data.services)) {
                        this.shippingOptions = data.services.map(s => ({
                            courier: s.courier || '',
                            service: s.service || '',
                            description: s.description || '',
                            cost: s.cost || 0,
                            etd: s.etd || ''
                        }));
                    }
                } catch (e) {}
                const first = this.shippingOptions.find(s => s.cost > 0) || this.shippingOptions[0] || null;
                if (first) {
                    this.selectedShippingKey = first.courier + ':' + (first.service || '');
                    this.shippingCourier = first.courier || '';
                    this.shippingService = first.service || '';
                    this.shippingCost = first.cost || 0;
                }
            },
            onSelectService() {
                const parts = String(this.selectedShippingKey || '').split(':');
                const c = parts[0] || '';
                const svc = parts[1] || '';
                const opt = this.shippingOptions.find(s => String(s.courier) === String(c) && String(s.service) === String(svc));
                if (opt) {
                    this.shippingCourier = opt.courier || '';
                    this.shippingService = opt.service || '';
                    this.shippingCost = opt.cost || 0;
                }
            },
            totalWithShipping() {
                const t = typeof this.total === 'number' ? this.total : parseFloat(this.total || 0);
                const s = typeof this.shippingCost === 'number' ? this.shippingCost : parseFloat(this.shippingCost || 0);
                return t + (isNaN(s) ? 0 : s);
            },
            async fetchProfile() {
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'customer/profile', {
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    if (!res.ok) return;
                    this.profile = await res.json();
                } catch (e) {}
            },
            async fetchAddresses() {
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'customer/addresses', {
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    if (!res.ok) return;
                    this.addresses = await res.json();
                } catch (e) {}
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
                this.postalCode = addr.postal_code || this.postalCode;
                await this.loadSubdistricts();
                this.selectedSubdistrict = addr.subdistrict_id ? String(addr.subdistrict_id) : '';
                await this.calculateAllShipping();
            },
            async loadProvinces() {
                this.isLoadingProvinces = true;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/provinces', {
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    const data = await res.json();
                    this.provinces = data.data || [];
                } catch (e) {
                    this.provinces = [];
                } finally {
                    this.isLoadingProvinces = false;
                }
            },
            async loadCities() {
                if (!this.selectedProvince) {
                    this.cities = [];
                    return;
                }
                this.isLoadingCities = true;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/cities?province=' + encodeURIComponent(this.selectedProvince), {
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    const data = await res.json();
                    this.cities = data.data || [];
                } catch (e) {
                    this.cities = [];
                } finally {
                    this.isLoadingCities = false;
                }
            },
            async loadSubdistricts() {
                if (!this.selectedCity) {
                    this.subdistricts = [];
                    return;
                }
                this.isLoadingSubdistricts = true;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/subdistricts?city=' + encodeURIComponent(this.selectedCity), {
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    const data = await res.json();
                    this.subdistricts = data.data || [];
                } catch (e) {
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
                            shipping_courier: this.shippingCourier || '',
                            shipping_service: this.shippingService || '',
                            shipping_cost: this.shippingCost || 0,
                            items: this.cart.map(i => ({
                                id: i.id,
                                qty: i.qty
                            }))
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
                            headers: {
                                'X-WP-Nonce': wpStoreSettings.nonce
                            }
                        });
                    } catch (_) {}
                    this.cart = [];
                    this.total = 0;
                    document.dispatchEvent(new CustomEvent('wp-store:cart-updated', {
                        detail: {
                            items: [],
                            total: 0
                        }
                    }));
                } catch (e) {
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
        };
    };
</script>
<div class="">
    <div x-data="wpStoreCheckout()" x-init="init()">
        <div class="wps-grid wps-grid-cols-2">
            <div>
                <div class="wps-card">
                    <div class="wps-p-4">
                        <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Informasi Pemesan</div>
                        <div class="">
                            <div class="wps-callout-title">Gunakan Data Tersimpan</div>
                            <div class="wps-flex wps-items-center wps-gap-2 wps-mb-4">
                                <button type="button" class="wps-btn wps-btn-primary" @click="importFromProfile()">Impor Profil</button>
                                <a href="<?php echo esc_url(site_url('/profil-saya/?tab=profile')); ?>" class="wps-btn wps-btn-secondary">Kelola</a>
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
                            <select class="wps-select" x-model="selectedSubdistrict" :disabled="!selectedCity" @change="calculateAllShipping()">
                                <option value="">-- Pilih Kecamatan --</option>
                                <template x-for="s in subdistricts" :key="s.subdistrict_id">
                                    <option :value="s.subdistrict_id" x-text="s.subdistrict_name"></option>
                                </template>
                            </select>
                            <div class="wps-text-xs wps-text-gray-500" x-show="isLoadingSubdistricts">Memuat kecamatan...</div>
                        </div>
                        <div class="wps-form-group">
                            <label class="wps-label">Alamat Lengkap</label>
                            <textarea class="wps-textarea" rows="3" x-model="address"></textarea>
                        </div>
                        <div class="wps-form-group">
                            <label class="wps-label">Kode Pos</label>
                            <input class="wps-input" type="text" x-model="postalCode" placeholder="">
                        </div>
                        <div class="wps-form-group">
                            <label class="wps-label">Catatan</label>
                            <textarea class="wps-textarea" rows="3" x-model="notes" placeholder="Catatan tambahan untuk pesanan"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="wps-card wps-mb-4">
                    <div class="wps-p-4">
                        <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Metode Pengiriman</div>
                        <div class="wps-mb-2">
                            <div class="wps-mt-2" x-show="selectedSubdistrict && shippingOptions.length > 0">
                                <template x-for="opt in shippingOptions" :key="opt.courier + ':' + opt.service">
                                    <button type="button" class="wps-nav-item" :class="{ 'active': selectedShippingKey === (opt.courier + ':' + opt.service) }" @click="selectedShippingKey = opt.courier + ':' + opt.service; onSelectService()">
                                        <div class="wps-flex wps-justify-between wps-items-center wps-w-full">
                                            <span x-text="opt.courier.toUpperCase() + ' ' + opt.service"></span>
                                            <span x-text="formatPrice(opt.cost)"></span>
                                        </div>
                                        <div class="wps-text-xxs wps-text-gray-500">
                                            <span x-text="(opt.description || '') + (opt.etd ? ' • ' + opt.etd : '')"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <div class="wps-text-xs wps-text-gray-500 wps-mt-2" x-show="!originSubdistrict">Asal pengiriman belum diatur di pengaturan.</div>
                            <div class="wps-text-xs wps-text-gray-500 wps-mt-1" x-show="Array.isArray(shippingCouriers) && shippingCouriers.length === 0">Tidak ada kurir aktif di pengaturan.</div>
                            <div class="wps-text-xxs wps-text-gray-500 wps-mt-1" x-show="!selectedSubdistrict">Lengkapi alamat pengiriman untuk menampilkan opsi pengiriman.</div>
                            <div class="wps-text-xs wps-text-gray-500 wps-mt-1" x-show="selectedSubdistrict && shippingOptions.length === 0">Tidak ada layanan tersedia, ubah kurir atau kecamatan.</div>
                        </div>
                    </div>
                </div>
                <div class="wps-card">
                    <div class="wps-p-4">
                        <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Ringkasan Order</div>
                        <template x-if="cart.length === 0">
                            <div class="wps-text-sm wps-text-gray-500">Keranjang kosong.</div>
                        </template>
                        <template x-for="item in cart" :key="item.id + ':' + (item.options ? JSON.stringify(item.options) : '')">
                            <div class="wps-flex wps-items-center wps-gap-2 wps-divider">
                                <img :src="item.image ? item.image : '<?php echo esc_url(WP_STORE_URL . 'assets/frontend/img/noimg.webp'); ?>'" alt="" class="wps-img-40">
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
                                </div>
                            </div>
                        </template>
                        <div class="wps-flex wps-justify-between wps-items-center">
                            <span class="wps-text-sm wps-text-gray-500">Total Produk</span>
                            <span class="wps-text-sm wps-text-gray-900" x-text="formatPrice(total)"></span>
                        </div>
                        <template x-if="shippingCost">
                            <div class="wps-flex wps-justify-between wps-items-center wps-mt-2">
                                <span class="wps-text-sm wps-text-gray-500">
                                    Ongkir (
                                    <template x-if="shippingCourier">
                                        <span x-text="shippingCourier.toUpperCase() + ' '"></span>
                                    </template>
                                    <span x-text="shippingService"></span>
                                    )
                                </span>
                                <span class="wps-text-sm wps-text-gray-900" x-text="formatPrice(shippingCost)"></span>
                            </div>
                        </template>
                        <div class="wps-flex wps-justify-between wps-items-center wps-mt-2">
                            <span class="wps-text-sm wps-text-gray-900 wps-font-medium">Grand Total</span>
                            <span class="wps-text-sm wps-text-gray-900 wps-font-medium" x-text="formatPrice(totalWithShipping())"></span>
                        </div>
                        <div class="wps-mt-4">
                            <button type="button" class="wps-btn wps-btn-primary" :disabled="submitting || cart.length === 0" @click="submit()">
                                <span x-show="submitting">Memproses...</span>
                                <span x-show="!submitting">Buat Pesanan</span>
                            </button>
                            <div class="wps-text-sm wps-text-gray-900 wps-mt-2" x-text="message"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>