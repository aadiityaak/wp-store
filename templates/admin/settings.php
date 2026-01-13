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
            <a href="?page=wp-store-settings&tab=general" class="wp-store-tab <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
                Umum
            </a>
            <a href="?page=wp-store-settings&tab=payment" class="wp-store-tab <?php echo $active_tab === 'payment' ? 'active' : ''; ?>">
                Pembayaran
            </a>
            <a href="?page=wp-store-settings&tab=pages" class="wp-store-tab <?php echo $active_tab === 'pages' ? 'active' : ''; ?>">
                Halaman
            </a>
            <a href="?page=wp-store-settings&tab=system" class="wp-store-tab <?php echo $active_tab === 'system' ? 'active' : ''; ?>">
                Sistem
            </a>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('wp_store_settings_action', 'wp_store_settings_nonce'); ?>
            <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

            <!-- Tab: Umum -->
            <?php if ($active_tab === 'general'): ?>
                <div class="wp-store-tab-content">
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
            <?php endif; ?>

            <!-- Tab: Pembayaran -->
            <?php if ($active_tab === 'payment'): ?>
                <div class="wp-store-tab-content">
                    <div class="wp-store-form-grid">
                        <div class="wp-store-box-gray">
                            <h3 class="wp-store-subtitle">Transfer Bank</h3>
                            <p class="wp-store-helper">Informasi rekening untuk pembayaran manual.</p>

                            <div class="wp-store-grid-2 wp-store-mt-4">
                                <div>
                                    <label class="wp-store-label" for="bank_name">Nama Bank</label>
                                    <input name="bank_name" type="text" id="bank_name" value="<?php echo esc_attr($settings['bank_name'] ?? ''); ?>" class="wp-store-input" placeholder="Contoh: BCA">
                                </div>
                                <div>
                                    <label class="wp-store-label" for="bank_account">Nomor Rekening</label>
                                    <input name="bank_account" type="text" id="bank_account" value="<?php echo esc_attr($settings['bank_account'] ?? ''); ?>" class="wp-store-input">
                                </div>
                            </div>
                            <div class="wp-store-mt-4">
                                <label class="wp-store-label" for="bank_holder">Atas Nama</label>
                                <input name="bank_holder" type="text" id="bank_holder" value="<?php echo esc_attr($settings['bank_holder'] ?? ''); ?>" class="wp-store-input">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tab: Halaman -->
            <?php if ($active_tab === 'pages'): ?>
                <div class="wp-store-tab-content">
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

                        <div class="wp-store-box-gray">
                            <h3 class="wp-store-subtitle">Generate Halaman Otomatis</h3>
                            <p class="wp-store-helper">Belum punya halaman? Klik tombol di bawah ini untuk membuat halaman Toko, Profil, Keranjang, dan Checkout secara otomatis dengan shortcode yang sesuai.</p>

                            <div class="wp-store-mt-4">
                                <button type="submit" name="wp_store_generate_pages" class="wp-store-btn wp-store-btn-secondary" onclick="return confirm('Apakah Anda yakin ingin membuat halaman-halaman ini?');">
                                    <span class="dashicons dashicons-plus-alt"></span> Buat Halaman Otomatis
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tab: Sistem -->
            <?php if ($active_tab === 'system'): ?>
                <div class="wp-store-tab-content">
                    <div class="wp-store-form-grid">
                        <div>
                            <label class="wp-store-label" for="currency_symbol">Simbol Mata Uang</label>
                            <input name="currency_symbol" type="text" id="currency_symbol" value="<?php echo esc_attr($settings['currency_symbol'] ?? 'Rp'); ?>" class="wp-store-input" style="width: 100px;">
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="wp-store-form-actions">
                <button type="submit" name="wp_store_settings_submit" id="submit" class="wp-store-btn wp-store-btn-primary">
                    <span class="dashicons dashicons-saved"></span> Simpan Pengaturan
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
        <span class="dashicons dashicons-yes-alt wp-store-icon-20"></span>
        <span x-text="notification.message"></span>
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

    .wp-store-icon-20 {
        font-size: 20px;
        width: 20px;
        height: 20px;
        color: #46b450;
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('storeSettingsManager', () => ({
            notification: {
                show: false,
                message: '',
                type: 'success'
            },
            init() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('settings-updated') === 'true') {
                    this.showNotification('Pengaturan berhasil disimpan!', 'success');
                    // We don't remove the 'tab' parameter here, only 'settings-updated' if we want clean URL
                    // But for consistency, we can keep the tab in the URL
                    // Actually, replaceState can clean up the 'settings-updated' part
                    const currentTab = urlParams.get('tab') || 'general';
                    const newUrl = window.location.pathname + '?page=wp-store-settings&tab=' + currentTab;
                    window.history.replaceState({}, document.title, newUrl);
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