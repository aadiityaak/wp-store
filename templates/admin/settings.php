<?php
$settings = get_option('wp_store_settings', []);
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>
<div class="wrap wp-store-wrapper" x-data="storeSettingsManager()">
    <div class="wp-store-header">
        <div>
            <h1 class="wp-store-title">Pengaturan Toko</h1>
            <p class="wp-store-helper">Kelola informasi toko, pembayaran, dan pengaturan sistem.</p>
        </div>
    </div>

    <div class="wp-store-card wp-store-card-settings">
        <!-- Tabs Navigation -->
        <div class="wp-store-tabs">
            <div @click="switchTab('general')" class="wp-store-tab" :class="{ 'active': activeTab === 'general' }">
                Umum
            </div>
            <div @click="switchTab('payment')" class="wp-store-tab" :class="{ 'active': activeTab === 'payment' }">
                Pembayaran
            </div>
            <div @click="switchTab('shipping')" class="wp-store-tab" :class="{ 'active': activeTab === 'shipping' }">
                Pengiriman
            </div>
            <div @click="switchTab('pages')" class="wp-store-tab" :class="{ 'active': activeTab === 'pages' }">
                Halaman
            </div>
            <div @click="switchTab('system')" class="wp-store-tab" :class="{ 'active': activeTab === 'system' }">
                Sistem
            </div>
            <div @click="switchTab('tools')" class="wp-store-tab" :class="{ 'active': activeTab === 'tools' }">
                Tool
            </div>
        </div>

        <form @submit.prevent="saveSettings" x-ref="form">
            <?php wp_nonce_field('wp_rest', '_wpnonce'); ?>
            <input type="hidden" name="active_tab" :value="activeTab">

            <!-- Tab: Umum -->
            <div x-show="activeTab === 'general'" class="wp-store-tab-content" x-cloak>
                <div class="wp-store-form-grid">
                    <div>
                        <label class="wp-store-label" for="store_name">Nama Toko</label>
                        <input name="store_name" type="text" id="store_name" value="<?php echo esc_attr($settings['store_name'] ?? get_bloginfo('name')); ?>" class="wp-store-input" placeholder="Contoh: Toko Serba Ada">
                    </div>

                    <div>
                        <label class="wp-store-label" for="store_address">Alamat Toko</label>
                        <textarea name="store_address" id="store_address" class="wp-store-textarea" rows="3"><?php echo esc_textarea($settings['store_address'] ?? ''); ?></textarea>
                        <p class="wp-store-helper">Alamat lengkap toko untuk invoice/nota.</p>
                    </div>

                    <div class="wp-store-grid-2">
                        <div>
                            <label class="wp-store-label" for="store_email">Email Toko</label>
                            <input name="store_email" type="email" id="store_email" value="<?php echo esc_attr($settings['store_email'] ?? get_bloginfo('admin_email')); ?>" class="wp-store-input">
                        </div>
                        <div>
                            <label class="wp-store-label" for="store_phone">Telepon/WA</label>
                            <input name="store_phone" type="text" id="store_phone" value="<?php echo esc_attr($settings['store_phone'] ?? ''); ?>" class="wp-store-input">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Pembayaran -->
            <div x-show="activeTab === 'payment'" class="wp-store-tab-content" x-cloak>
                <div class="wp-store-form-grid">
                    <h3 class="wp-store-subtitle">Transfer Bank</h3>
                    <p class="wp-store-helper">Kelola daftar rekening bank untuk pembayaran manual.</p>

                    <template x-for="(account, index) in bankAccounts" :key="index">
                        <div class="wp-store-box-gray wp-store-mt-4" style="position: relative; padding-top: 30px;">
                            <!-- Remove Button -->
                            <button type="button" @click="removeBankAccount(index)" class="button-link-delete" style="position: absolute; top: 10px; right: 10px; text-decoration: none;" title="Hapus Rekening" x-show="bankAccounts.length > 0">
                                <span class="dashicons dashicons-trash"></span> Hapus
                            </button>

                            <div class="wp-store-grid-2">
                                <div>
                                    <label class="wp-store-label">Nama Bank</label>
                                    <select x-model="account.bank_name" class="wp-store-input">
                                        <template x-for="bank in indonesianBanks" :key="bank">
                                            <option :value="bank" x-text="bank" :selected="account.bank_name === bank"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="wp-store-label">Nomor Rekening</label>
                                    <input type="text" x-model="account.bank_account" class="wp-store-input" placeholder="Contoh: 1234567890">
                                </div>
                            </div>
                            <div class="wp-store-mt-4">
                                <label class="wp-store-label">Atas Nama</label>
                                <input type="text" x-model="account.bank_holder" class="wp-store-input" placeholder="Contoh: Nama Pemilik">
                            </div>
                        </div>
                    </template>

                    <div class="wp-store-mt-4">
                        <button type="button" @click="addBankAccount" class="wp-store-btn wp-store-btn-secondary">
                            <span class="dashicons dashicons-plus-alt2"></span> Tambah Rekening
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab: Pengiriman -->
            <div x-show="activeTab === 'shipping'" class="wp-store-tab-content" x-cloak>
                <div class="wp-store-form-grid">
                    <h3 class="wp-store-subtitle">Pengaturan Pengiriman</h3>
                    <p class="wp-store-helper">Konfigurasi API Raja Ongkir dan metode pengiriman.</p>

                    <div class="wp-store-box-gray wp-store-mt-4">
                        <h4 class="wp-store-subtitle-small">API Raja Ongkir</h4>
                        <div class="wp-store-mt-2">
                            <label class="wp-store-label" for="rajaongkir_api_key">API Key</label>
                            <input name="rajaongkir_api_key" type="text" id="rajaongkir_api_key" value="<?php echo esc_attr($settings['rajaongkir_api_key'] ?? ''); ?>" class="wp-store-input" placeholder="Masukkan API Key Starter/Basic/Pro Anda">
                            <p class="wp-store-helper">Dapatkan API Key di <a href="https://rajaongkir.com/" target="_blank">RajaOngkir.com</a>.</p>
                        </div>

                        <div class="wp-store-mt-4">
                            <label class="wp-store-label" for="rajaongkir_account_type">Tipe Akun</label>
                            <select name="rajaongkir_account_type" id="rajaongkir_account_type" class="wp-store-input" style="width: 200px;">
                                <option value="starter" <?php selected($settings['rajaongkir_account_type'] ?? 'starter', 'starter'); ?>>Starter (Free)</option>
                                <option value="basic" <?php selected($settings['rajaongkir_account_type'] ?? 'starter', 'basic'); ?>>Basic</option>
                                <option value="pro" <?php selected($settings['rajaongkir_account_type'] ?? 'starter', 'pro'); ?>>Pro</option>
                            </select>
                        </div>
                    </div>

                    <div class="wp-store-box-gray wp-store-mt-4">
                        <h4 class="wp-store-subtitle-small">Asal Pengiriman</h4>
                        <p class="wp-store-helper">Lokasi toko Anda untuk perhitungan ongkos kirim.</p>

                        <div class="wp-store-mt-2">
                            <!-- Province -->
                            <div class="wp-store-mb-2">
                                <label class="wp-store-label" for="shipping_origin_province">Provinsi</label>
                                <select name="shipping_origin_province" id="shipping_origin_province" x-model="settings.shipping_origin_province" @change="onProvinceChange()" class="wp-store-input" style="width: 100%; max-width: 400px;">
                                    <option value="">-- Pilih Provinsi --</option>
                                    <template x-for="prov in provinces" :key="prov.province_id">
                                        <option :value="prov.province_id" x-text="prov.province" :selected="prov.province_id == settings.shipping_origin_province"></option>
                                    </template>
                                </select>
                                <div x-show="isLoadingProvinces" class="wp-store-helper">Memuat provinsi...</div>
                            </div>

                            <!-- City -->
                            <div class="wp-store-mb-2">
                                <label class="wp-store-label" for="shipping_origin_city">Kota/Kabupaten</label>
                                <select name="shipping_origin_city" id="shipping_origin_city" x-model="settings.shipping_origin_city" @change="onCityChange()" class="wp-store-input" style="width: 100%; max-width: 400px;" :disabled="!settings.shipping_origin_province">
                                    <option value="">-- Pilih Kota/Kabupaten --</option>
                                    <template x-for="city in cities" :key="city.city_id">
                                        <option :value="city.city_id" x-text="`${city.type} ${city.city_name}`" :selected="city.city_id == settings.shipping_origin_city"></option>
                                    </template>
                                </select>
                                <div x-show="isLoadingCities" class="wp-store-helper">Memuat kota...</div>
                            </div>

                            <!-- Subdistrict -->
                            <div class="wp-store-mb-2">
                                <label class="wp-store-label" for="shipping_origin_subdistrict">Kecamatan</label>
                                <select name="shipping_origin_subdistrict" id="shipping_origin_subdistrict" x-model="settings.shipping_origin_subdistrict" class="wp-store-input" style="width: 100%; max-width: 400px;" :disabled="!settings.shipping_origin_city">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <template x-for="sub in subdistricts" :key="sub.subdistrict_id">
                                        <option :value="sub.subdistrict_id" x-text="sub.subdistrict_name" :selected="sub.subdistrict_id == settings.shipping_origin_subdistrict"></option>
                                    </template>
                                </select>
                                <div x-show="isLoadingSubdistricts" class="wp-store-helper">Memuat kecamatan...</div>
                            </div>

                            <p class="wp-store-helper">Lokasi toko Anda untuk perhitungan ongkos kirim.</p>
                        </div>
                    </div>

                    <div class="wp-store-box-gray wp-store-mt-4">
                        <h4 class="wp-store-subtitle-small">Kurir Aktif</h4>
                        <p class="wp-store-helper">Pilih kurir yang ingin Anda gunakan.</p>

                        <div class="wp-store-grid-3 wp-store-mt-2">
                            <?php
                            $couriers = [
                                'jne' => 'JNE',
                                'sicepat' => 'SiCepat',
                                'ide' => 'IDExpress',
                                'sap' => 'SAP Express',
                                'ninja' => 'Ninja',
                                'jnt' => 'J&T Express',
                                'tiki' => 'TIKI',
                                'wahana' => 'Wahana Express',
                                'pos' => 'POS Indonesia',
                                'sentral' => 'Sentral Cargo',
                                'lion' => 'Lion Parcel',
                                'rex' => 'Royal Express Asia'
                            ];
                            $active_couriers = $settings['shipping_couriers'] ?? ['jne', 'sicepat', 'ide'];
                            foreach ($couriers as $code => $label) :
                            ?>
                                <label class="wp-store-checkbox-label">
                                    <input type="checkbox" name="shipping_couriers[]" value="<?php echo $code; ?>" <?php echo in_array($code, $active_couriers) ? 'checked' : ''; ?>>
                                    <?php echo $label; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Halaman -->
            <div x-show="activeTab === 'pages'" class="wp-store-tab-content" x-cloak>
                <div class="wp-store-form-grid">
                    <p class="wp-store-helper">Tentukan halaman untuk fitur-fitur toko.</p>

                    <div>
                        <label class="wp-store-label" for="page_shop">Halaman Toko (Shop)</label>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'page_shop',
                            'selected' => $settings['page_shop'] ?? 0,
                            'show_option_none' => '-- Pilih Halaman --',
                            'class' => 'wp-store-input'
                        ]);
                        ?>
                    </div>

                    <div>
                        <label class="wp-store-label" for="page_profile">Halaman Profil</label>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'page_profile',
                            'selected' => $settings['page_profile'] ?? 0,
                            'show_option_none' => '-- Pilih Halaman --',
                            'class' => 'wp-store-input'
                        ]);
                        ?>
                    </div>

                    <div>
                        <label class="wp-store-label" for="page_cart">Halaman Keranjang (Cart)</label>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'page_cart',
                            'selected' => $settings['page_cart'] ?? 0,
                            'show_option_none' => '-- Pilih Halaman --',
                            'class' => 'wp-store-input'
                        ]);
                        ?>
                    </div>

                    <div>
                        <label class="wp-store-label" for="page_checkout">Halaman Checkout</label>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'page_checkout',
                            'selected' => $settings['page_checkout'] ?? 0,
                            'show_option_none' => '-- Pilih Halaman --',
                            'class' => 'wp-store-input'
                        ]);
                        ?>
                    </div>

                    <div>
                        <label class="wp-store-label" for="page_thanks">Halaman Terima Kasih (Thanks)</label>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'page_thanks',
                            'selected' => $settings['page_thanks'] ?? 0,
                            'show_option_none' => '-- Pilih Halaman --',
                            'class' => 'wp-store-input'
                        ]);
                        ?>
                    </div>

                    <div>
                        <label class="wp-store-label" for="page_tracking">Halaman Tracking Order</label>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'page_tracking',
                            'selected' => $settings['page_tracking'] ?? 0,
                            'show_option_none' => '-- Pilih Halaman --',
                            'class' => 'wp-store-input'
                        ]);
                        ?>
                    </div>

                    <div class="wp-store-box-gray">
                        <h3 class="wp-store-subtitle">Generate Halaman Otomatis</h3>
                        <p class="wp-store-helper">Belum punya halaman? Klik tombol di bawah ini untuk membuat halaman Toko, Profil, Keranjang, Checkout, Terima Kasih, dan Tracking Order secara otomatis dengan shortcode yang sesuai.</p>

                        <div class="wp-store-mt-4">
                            <button type="button" @click="openGeneratePagesModal" class="wp-store-btn wp-store-btn-secondary" :disabled="isGenerating">
                                <span class="dashicons dashicons-plus-alt" x-show="!isGenerating"></span>
                                <span class="dashicons dashicons-update" x-show="isGenerating" style="animation: spin 2s linear infinite;"></span>
                                <span x-text="isGenerating ? 'Sedang Membuat...' : 'Buat Halaman Otomatis'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Sistem -->
            <div x-show="activeTab === 'system'" class="wp-store-tab-content" x-cloak>
                <div class="wp-store-form-grid">
                    <div>
                        <label class="wp-store-label" for="currency_symbol">Simbol Mata Uang</label>
                        <select name="currency_symbol" id="currency_symbol" class="wp-store-input" style="width: 150px;">
                            <option value="Rp" <?php selected($settings['currency_symbol'] ?? 'Rp', 'Rp'); ?>>Rp (Rupiah)</option>
                            <option value="USD" <?php selected($settings['currency_symbol'] ?? 'Rp', 'USD'); ?>>USD (Dollar)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tab: Tool -->
            <div x-show="activeTab === 'tools'" class="wp-store-tab-content" x-cloak>
                <div class="wp-store-form-grid">
                    <div class="wp-store-box-gray">
                        <h3 class="wp-store-subtitle">Tool</h3>
                        <div class="wp-store-mt-4" style="display:flex; gap:10px; flex-wrap:wrap;">
                            <button type="button" @click="openSeederModal" class="wp-store-btn wp-store-btn-secondary" :disabled="isSeeding">
                                <span class="dashicons dashicons-admin-tools" x-show="!isSeeding"></span>
                                <span class="dashicons dashicons-update" x-show="isSeeding" style="animation: spin 2s linear infinite;"></span>
                                <span x-text="isSeeding ? 'Menjalankan Seeder...' : 'Seeder'"></span>
                            </button>
                            <a href="<?php echo admin_url('edit.php?post_type=store_product'); ?>" class="wp-store-btn wp-store-btn-secondary">
                                <span class="dashicons dashicons-cart"></span>
                                Produk
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wp-store-form-actions">
                <button type="submit" class="wp-store-btn wp-store-btn-primary" :disabled="isSaving">
                    <span class="dashicons dashicons-saved" x-show="!isSaving"></span>
                    <span class="dashicons dashicons-update" x-show="isSaving" style="animation: spin 2s linear infinite;"></span>
                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Pengaturan'"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- Notification Toast -->
    <div x-show="notification.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="wp-store-toast"
        :class="notification.type"
        x-cloak>
        <span class="dashicons" :class="notification.type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning'" class="wp-store-icon-20"></span>
        <span x-text="notification.message"></span>
    </div>
    <div x-show="isSeederModalOpen" x-cloak class="wp-store-modal-overlay">
        <div class="wp-store-modal" @keydown.escape.window="closeSeederModal">
            <div class="wp-store-modal-header">Konfirmasi Seeder</div>
            <div class="wp-store-modal-body">
                Jalankan seeder untuk membuat produk contoh?
            </div>
            <div class="wp-store-modal-actions">
                <button type="button" class="wp-store-btn wp-store-btn-secondary" @click="closeSeederModal" :disabled="isSeeding">Batal</button>
                <button type="button" class="wp-store-btn wp-store-btn-primary" @click="runSeeder" :disabled="isSeeding">
                    <span class="dashicons dashicons-update" x-show="isSeeding" style="animation: spin 2s linear infinite;"></span>
                    <span x-text="isSeeding ? 'Memproses...' : 'Jalankan'"></span>
                </button>
            </div>
        </div>
    </div>
    <div x-show="isGeneratePagesModalOpen" x-cloak class="wp-store-modal-overlay">
        <div class="wp-store-modal" @keydown.escape.window="closeGeneratePagesModal">
            <div class="wp-store-modal-header">Konfirmasi Pembuatan Halaman</div>
            <div class="wp-store-modal-body">
                Buat halaman Toko, Profil, Keranjang, Checkout, Terima Kasih, dan Tracking Order secara otomatis?
            </div>
            <div class="wp-store-modal-actions">
                <button type="button" class="wp-store-btn wp-store-btn-secondary" @click="closeGeneratePagesModal" :disabled="isGenerating">Batal</button>
                <button type="button" class="wp-store-btn wp-store-btn-primary" @click="generatePages" :disabled="isGenerating">
                    <span class="dashicons dashicons-update" x-show="isGenerating" style="animation: spin 2s linear infinite;"></span>
                    <span x-text="isGenerating ? 'Memproses...' : 'Buat'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styling similar to wp-desa */
    .wp-store-wrapper {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        max-width: 1000px;
        margin: 20px auto;
    }

    .wp-store-header {
        margin-bottom: 20px;
    }

    .wp-store-title {
        font-size: 24px;
        font-weight: 600;
        margin: 0 0 5px 0;
        color: #1d2327;
    }

    .wp-store-helper {
        color: #646970;
        font-size: 14px;
        margin: 0;
    }

    .wp-store-card {
        background: #fff;
        border: 1px solid #c3c4c7;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
        border-radius: 4px;
        overflow: hidden;
    }

    .wp-store-tabs {
        display: flex;
        border-bottom: 1px solid #c3c4c7;
        background: #f6f7f7;
    }

    .wp-store-tab {
        padding: 15px 20px;
        cursor: pointer;
        font-weight: 600;
        color: #50575e;
        border-right: 1px solid #c3c4c7;
        transition: all 0.2s;
        text-decoration: none;
    }

    .wp-store-tab:hover {
        background: #f0f0f1;
        color: #1d2327;
    }

    .wp-store-tab.active {
        background: #fff;
        color: #2271b1;
        border-bottom: 1px solid transparent;
        margin-bottom: -1px;
    }

    .wp-store-tab:focus {
        box-shadow: none;
        outline: none;
    }

    .wp-store-tab-content {
        padding: 30px;
    }

    .wp-store-form-grid {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .wp-store-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .wp-store-label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        color: #1d2327;
    }

    .wp-store-input,
    .wp-store-textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #8c8f94;
        border-radius: 4px;
        font-size: 14px;
    }

    .wp-store-input:focus,
    .wp-store-textarea:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }

    .wp-store-box-gray {
        background: #f6f7f7;
        border: 1px solid #dcdcde;
        padding: 20px;
        border-radius: 4px;
    }

    .wp-store-subtitle {
        margin: 0 0 5px 0;
        font-size: 16px;
    }

    .wp-store-mt-4 {
        margin-top: 16px;
    }

    .wp-store-form-actions {
        padding: 20px 30px;
        background: #f6f7f7;
        border-top: 1px solid #c3c4c7;
        display: flex;
        justify-content: flex-end;
    }

    .wp-store-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid transparent;
        text-decoration: none;
        font-size: 13px;
    }

    .wp-store-btn-primary {
        background: #2271b1;
        color: #fff;
        border-color: #2271b1;
    }

    .wp-store-btn-primary:hover {
        background: #135e96;
        border-color: #135e96;
    }

    .wp-store-btn-secondary {
        background: #f6f7f7;
        color: #2271b1;
        border-color: #2271b1;
    }

    .wp-store-btn-secondary:hover {
        background: #f0f0f1;
        border-color: #135e96;
        color: #135e96;
    }

    .wp-store-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    /* Toast Notification */
    .wp-store-toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 12px 20px;
        background: #fff;
        border-left: 4px solid #46b450;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 9999;
    }

    .wp-store-toast.success {
        border-color: #46b450;
    }

    .wp-store-toast.error {
        border-color: #d63638;
    }

    .wp-store-icon-20 {
        font-size: 20px;
        width: 20px;
        height: 20px;
    }

    .dashicons-yes-alt {
        color: #46b450;
    }

    .dashicons-warning {
        color: #d63638;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    [x-cloak] {
        display: none !important;
    }

    .wp-store-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 20px;
    }

    .wp-store-modal {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 6px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        width: 100%;
        max-width: 420px;
        overflow: hidden;
    }

    .wp-store-modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #1d2327;
    }

    .wp-store-modal-body {
        padding: 16px 20px;
        color: #3c434a;
        font-size: 14px;
    }

    .wp-store-modal-actions {
        padding: 14px 20px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: #f9fafb;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('storeSettingsManager', () => ({
            activeTab: '<?php echo esc_js($active_tab); ?>',
            isSaving: false,
            isGenerating: false,
            isSeeding: false,
            isSeederModalOpen: false,
            isGeneratePagesModalOpen: false,
            notification: {
                show: false,
                message: '',
                type: 'success'
            },
            bankAccounts: [],
            indonesianBanks: [
                'Bank Mandiri', 'BRI', 'BCA', 'BNI', 'BTN', 'BSI', 'CIMB Niaga',
                'OCBC NISP', 'Bank Permata', 'Bank Danamon', 'Panin Bank',
                'Maybank Indonesia', 'Bank Mega', 'Bank Sinarmas', 'Bank BTN Syariah',
                'Bank Mega Syariah', 'Bank Commonwealth', 'Bank UOB Indonesia',
                'Bank DBS Indonesia', 'Bank Woori Saudara', 'Bank Hana Indonesia',
                'Bank Resona Perdania', 'Bank J Trust Indonesia', 'Bank Ina Perdana',
                'Bank Artha Graha', 'Bank Index Selindo', 'Bank Ganesha',
                'Bank Maspion', 'Bank Bumi Arta', 'Bank Victoria', 'Lainnya'
            ],

            provinces: [],
            cities: [],
            subdistricts: [],
            isLoadingProvinces: false,
            isLoadingCities: false,
            isLoadingSubdistricts: false,
            settings: {
                shipping_origin_province: '<?php echo esc_js($settings['shipping_origin_province'] ?? ''); ?>',
                shipping_origin_city: '<?php echo esc_js($settings['shipping_origin_city'] ?? ''); ?>',
                shipping_origin_subdistrict: '<?php echo esc_js($settings['shipping_origin_subdistrict'] ?? ''); ?>',
                rajaongkir_account_type: '<?php echo esc_js($settings['rajaongkir_account_type'] ?? 'starter'); ?>'
            },

            init() {
                // Initialize history state if needed
                this.updateUrl(this.activeTab);

                // Load provinces and cascading data
                this.loadProvinces().then(() => {
                    if (this.settings.shipping_origin_province) {
                        this.loadCities(this.settings.shipping_origin_province).then(() => {
                            if (this.settings.shipping_origin_city) {
                                this.loadSubdistricts(this.settings.shipping_origin_city);
                            }
                        });
                    }
                });

                // Initialize bank accounts
                const savedAccounts = <?php echo json_encode($settings['store_bank_accounts'] ?? []); ?>;
                if (Array.isArray(savedAccounts) && savedAccounts.length > 0) {
                    this.bankAccounts = savedAccounts;
                } else {
                    // Check legacy
                    const legacyName = '<?php echo esc_js($settings['bank_name'] ?? ''); ?>';
                    const legacyAccount = '<?php echo esc_js($settings['bank_account'] ?? ''); ?>';
                    const legacyHolder = '<?php echo esc_js($settings['bank_holder'] ?? ''); ?>';

                    if (legacyName || legacyAccount || legacyHolder) {
                        this.bankAccounts.push({
                            bank_name: legacyName,
                            bank_account: legacyAccount,
                            bank_holder: legacyHolder
                        });
                    } else {
                        this.addBankAccount();
                    }
                }
            },

            addBankAccount() {
                this.bankAccounts.push({
                    bank_name: 'BCA',
                    bank_account: '',
                    bank_holder: ''
                });
            },

            removeBankAccount(index) {
                this.bankAccounts.splice(index, 1);
            },

            async loadProvinces() {
                this.isLoadingProvinces = true;
                try {
                    const response = await fetch('/wp-json/wp-store/v1/rajaongkir/provinces', {
                        method: 'GET',
                        headers: {
                            'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'
                        }
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        this.provinces = result.data;
                    }
                } catch (error) {
                    console.error('Error loading provinces:', error);
                } finally {
                    this.isLoadingProvinces = false;
                }
            },

            async loadCities(provinceId) {
                if (!provinceId) return;
                this.isLoadingCities = true;
                this.cities = [];
                try {
                    const response = await fetch(`/wp-json/wp-store/v1/rajaongkir/cities?province=${provinceId}`, {
                        method: 'GET',
                        headers: {
                            'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'
                        }
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        this.cities = result.data;
                    } else {
                        console.error('Failed to load cities:', result.message);
                    }
                } catch (error) {
                    console.error('Error loading cities:', error);
                } finally {
                    this.isLoadingCities = false;
                }
            },

            async loadSubdistricts(cityId) {
                if (!cityId) return;
                // Don't try to load subdistricts for starter account if we know it will fail
                // But let's try anyway as user might have upgraded key but not setting
                this.isLoadingSubdistricts = true;
                this.subdistricts = [];
                try {
                    const response = await fetch(`/wp-json/wp-store/v1/rajaongkir/subdistricts?city=${cityId}`, {
                        method: 'GET',
                        headers: {
                            'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'
                        }
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        this.subdistricts = result.data;
                    }
                } catch (error) {
                    console.error('Error loading subdistricts:', error);
                } finally {
                    this.isLoadingSubdistricts = false;
                }
            },

            onProvinceChange() {
                this.settings.shipping_origin_city = '';
                this.settings.shipping_origin_subdistrict = '';
                this.cities = [];
                this.subdistricts = [];
                if (this.settings.shipping_origin_province) {
                    this.loadCities(this.settings.shipping_origin_province);
                }
            },

            onCityChange() {
                this.settings.shipping_origin_subdistrict = '';
                this.subdistricts = [];
                if (this.settings.shipping_origin_city) {
                    this.loadSubdistricts(this.settings.shipping_origin_city);
                }
            },

            switchTab(tab) {
                this.activeTab = tab;
                this.updateUrl(tab);
            },

            updateUrl(tab) {
                const newUrl = window.location.pathname + '?page=wp-store-settings&tab=' + tab;
                window.history.pushState({
                    tab: tab
                }, '', newUrl);
            },

            async saveSettings() {
                this.isSaving = true;
                const formData = new FormData(this.$refs.form);
                const data = {};
                formData.forEach((value, key) => {
                    // Handle array inputs like shipping_couriers[]
                    if (key.endsWith('[]')) {
                        const realKey = key.slice(0, -2);
                        if (!data[realKey]) {
                            data[realKey] = [];
                        }
                        data[realKey].push(value);
                    } else {
                        data[key] = value;
                    }
                });

                // Add bank accounts manually to data
                data.store_bank_accounts = this.bankAccounts;

                try {
                    const response = await fetch('/wp-json/wp-store/v1/settings', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': formData.get('_wpnonce')
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (response.ok) {
                        this.showNotification('Pengaturan berhasil disimpan!', 'success');
                    } else {
                        this.showNotification(result.message || 'Gagal menyimpan pengaturan.', 'error');
                    }
                } catch (error) {
                    this.showNotification('Terjadi kesalahan jaringan.', 'error');
                    console.error(error);
                } finally {
                    this.isSaving = false;
                }
            },

            async generatePages() {
                this.isGenerating = true;
                const nonce = document.getElementById('_wpnonce').value;

                try {
                    const response = await fetch('/wp-json/wp-store/v1/settings/generate-pages', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': nonce
                        }
                    });

                    const result = await response.json();

                    if (response.ok) {
                        this.showNotification(result.message, 'success');
                        // Optionally reload to show new page selections, but for now just notify
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        this.showNotification(result.message || 'Gagal membuat halaman.', 'error');
                    }
                } catch (error) {
                    this.showNotification('Terjadi kesalahan jaringan.', 'error');
                    console.error(error);
                } finally {
                    this.isGenerating = false;
                    this.closeGeneratePagesModal();
                }
            },

            openSeederModal() {
                this.isSeederModalOpen = true;
            },
            closeSeederModal() {
                this.isSeederModalOpen = false;
            },
            openGeneratePagesModal() {
                this.isGeneratePagesModalOpen = true;
            },
            closeGeneratePagesModal() {
                this.isGeneratePagesModalOpen = false;
            },
            async runSeeder() {
                this.isSeeding = true;
                const nonce = document.getElementById('_wpnonce').value;
                try {
                    const response = await fetch('/wp-json/wp-store/v1/tools/seed-products', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': nonce
                        },
                        body: JSON.stringify({
                            count: 12
                        })
                    });
                    const result = await response.json();
                    if (response.ok) {
                        this.showNotification(result.message || 'Seeder berhasil dijalankan.', 'success');
                    } else {
                        this.showNotification(result.message || 'Gagal menjalankan seeder.', 'error');
                    }
                } catch (error) {
                    this.showNotification('Terjadi kesalahan jaringan.', 'error');
                    console.error(error);
                } finally {
                    this.isSeeding = false;
                    this.closeSeederModal();
                }
            },

            showNotification(message, type = 'success') {
                this.notification.message = message;
                this.notification.type = type;
                this.notification.show = true;
                setTimeout(() => {
                    this.notification.show = false;
                }, 3000);
            }
        }));
    });
</script>