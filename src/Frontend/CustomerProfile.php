<?php

namespace WpStore\Frontend;

class CustomerProfile
{
    public function register()
    {
        add_shortcode('store_customer_profile', [$this, 'render_profile']);
    }

    public function render_profile($atts = [])
    {
        if (!is_user_logged_in()) {
            return '<div class="wp-store-notice">Silakan login untuk mengakses halaman profil.</div>';
        }

        wp_enqueue_script('alpinejs');
        // Ensure our localize script is available if we use the same handle, 
        // but Shortcode.php registers 'wp-store-frontend'. 
        // We might need to make sure the localized vars are available here too.
        // For safety, I'll enqueue 'wp-store-frontend' which has the vars, 
        // even if I write the specific JS inline for this component.
        wp_enqueue_script('wp-store-frontend');

        ob_start();
        ?>
        <div class="wp-store-profile-wrapper" x-data="storeCustomerProfile()" x-init="init()">
            <div class="wp-store-tabs">
                <button 
                    @click="tab = 'profile'" 
                    :class="{ 'active': tab === 'profile' }"
                    class="wp-store-btn-tab">
                    Profil Saya
                </button>
                <button 
                    @click="tab = 'addresses'" 
                    :class="{ 'active': tab === 'addresses' }"
                    class="wp-store-btn-tab">
                    Buku Alamat
                </button>
            </div>

            <!-- Notification -->
            <div x-show="message" x-transition class="wp-store-alert" x-text="message"></div>

            <!-- Profile Tab -->
            <div x-show="tab === 'profile'" class="wp-store-tab-content">
                <h3>Edit Profil</h3>
                <form @submit.prevent="saveProfile">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" x-model="profile.email" disabled class="form-control-disabled">
                    </div>
                    <div class="form-group">
                        <label>Nama Depan</label>
                        <input type="text" x-model="profile.first_name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Nama Belakang</label>
                        <input type="text" x-model="profile.last_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" x-model="profile.phone" class="form-control">
                    </div>
                    <button type="submit" class="btn-primary" :disabled="loading">
                        <span x-show="loading">Menyimpan...</span>
                        <span x-show="!loading">Simpan Perubahan</span>
                    </button>
                </form>
            </div>

            <!-- Addresses Tab -->
            <div x-show="tab === 'addresses'" class="wp-store-tab-content">
                <div x-show="!isEditingAddress">
                    <div class="address-header">
                        <h3>Daftar Alamat</h3>
                        <button @click="resetAddressForm(); isEditingAddress = true" class="btn-secondary">+ Tambah Alamat</button>
                    </div>
                    
                    <div class="address-list">
                        <template x-if="addresses.length === 0">
                            <p>Belum ada alamat tersimpan.</p>
                        </template>
                        <template x-for="addr in addresses" :key="addr.id">
                            <div class="address-card">
                                <h4 x-text="addr.label"></h4>
                                <p x-text="addr.address"></p>
                                <p><span x-text="addr.city"></span>, <span x-text="addr.postal_code"></span></p>
                                <div class="address-actions">
                                    <button @click="editAddress(addr)" class="btn-small">Edit</button>
                                    <button @click="deleteAddress(addr.id)" class="btn-small btn-danger">Hapus</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Address Form -->
                <div x-show="isEditingAddress">
                    <h3 x-text="addressForm.id ? 'Edit Alamat' : 'Tambah Alamat Baru'"></h3>
                    <form @submit.prevent="saveAddress">
                        <div class="form-group">
                            <label>Label (Contoh: Rumah, Kantor)</label>
                            <input type="text" x-model="addressForm.label" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Alamat Lengkap</label>
                            <textarea x-model="addressForm.address" required class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Kota</label>
                            <input type="text" x-model="addressForm.city" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Kode Pos</label>
                            <input type="text" x-model="addressForm.postal_code" required class="form-control">
                        </div>
                        <div class="form-actions">
                            <button type="button" @click="isEditingAddress = false" class="btn-secondary">Batal</button>
                            <button type="submit" class="btn-primary" :disabled="loading">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
            .wp-store-profile-wrapper { max-width: 800px; margin: 20px auto; font-family: sans-serif; }
            .wp-store-tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 20px; }
            .wp-store-btn-tab { padding: 10px 20px; border: none; background: none; cursor: pointer; font-size: 16px; font-weight: bold; color: #666; }
            .wp-store-btn-tab.active { border-bottom: 2px solid #007cba; color: #007cba; }
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
            .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .form-control-disabled { width: 100%; padding: 8px; border: 1px solid #eee; background: #f9f9f9; color: #666; }
            .btn-primary { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            .btn-secondary { background: #f0f0f1; color: #333; padding: 10px 20px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; }
            .btn-small { padding: 5px 10px; font-size: 12px; cursor: pointer; }
            .btn-danger { background: #dc3232; color: white; border: none; }
            .address-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .address-card { border: 1px solid #eee; padding: 15px; margin-bottom: 15px; border-radius: 4px; }
            .address-actions { margin-top: 10px; display: flex; gap: 10px; }
            .wp-store-alert { padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px; }
        </style>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('storeCustomerProfile', () => ({
                    tab: 'profile',
                    loading: false,
                    message: '',
                    profile: {
                        first_name: '',
                        last_name: '',
                        email: '',
                        phone: ''
                    },
                    addresses: [],
                    isEditingAddress: false,
                    addressForm: {
                        id: null,
                        label: '',
                        address: '',
                        city: '',
                        postal_code: ''
                    },
                    
                    init() {
                        this.fetchProfile();
                        this.fetchAddresses();
                    },

                    async fetchProfile() {
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'customer/profile', {
                                headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                            });
                            const data = await res.json();
                            this.profile = data;
                        } catch (err) {
                            console.error(err);
                        }
                    },

                    async saveProfile() {
                        this.loading = true;
                        this.message = '';
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'customer/profile', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': wpStoreSettings.nonce
                                },
                                body: JSON.stringify(this.profile)
                            });
                            const data = await res.json();
                            this.message = data.message;
                            setTimeout(() => this.message = '', 3000);
                        } catch (err) {
                            console.error(err);
                        } finally {
                            this.loading = false;
                        }
                    },

                    async fetchAddresses() {
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'customer/addresses', {
                                headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                            });
                            this.addresses = await res.json();
                        } catch (err) {
                            console.error(err);
                        }
                    },

                    resetAddressForm() {
                        this.addressForm = { id: null, label: '', address: '', city: '', postal_code: '' };
                    },

                    editAddress(addr) {
                        this.addressForm = { ...addr };
                        this.isEditingAddress = true;
                    },

                    async saveAddress() {
                        this.loading = true;
                        try {
                            const isUpdate = !!this.addressForm.id;
                            const url = isUpdate 
                                ? wpStoreSettings.restUrl + 'customer/addresses/' + this.addressForm.id 
                                : wpStoreSettings.restUrl + 'customer/addresses';
                            
                            const method = isUpdate ? 'PUT' : 'POST';

                            const res = await fetch(url, {
                                method: method,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': wpStoreSettings.nonce
                                },
                                body: JSON.stringify(this.addressForm)
                            });

                            if (res.ok) {
                                await this.fetchAddresses();
                                this.isEditingAddress = false;
                                this.message = isUpdate ? 'Alamat diperbarui' : 'Alamat ditambahkan';
                                setTimeout(() => this.message = '', 3000);
                            }
                        } catch (err) {
                            console.error(err);
                        } finally {
                            this.loading = false;
                        }
                    },

                    async deleteAddress(id) {
                        if (!confirm('Apakah Anda yakin ingin menghapus alamat ini?')) return;
                        
                        try {
                            const res = await fetch(wpStoreSettings.restUrl + 'customer/addresses/' + id, {
                                method: 'DELETE',
                                headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                            });
                            
                            if (res.ok) {
                                await this.fetchAddresses();
                            }
                        } catch (err) {
                            console.error(err);
                        }
                    }
                }));
            });
        </script>
        <?php
        return ob_get_clean();
    }
}
