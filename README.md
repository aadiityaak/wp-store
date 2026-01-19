# WP Store

WP Store adalah plugin WordPress untuk membuat toko sederhana dengan fitur Produk, Keranjang, Wishlist, Checkout, dan integrasi ongkir RajaOngkir (via Komerce API).

## Untuk Pengguna

- Instalasi
  - Unggah folder plugin ke `wp-content/plugins/wp-store` atau install melalui ZIP.
  - Aktifkan plugin di halaman Plugins.
  - Menu “WP Store” akan muncul di admin untuk mengelola Produk dan Pesanan.

- Pengaturan Toko
  - Buka “WP Store → Pengaturan”.
  - Isi informasi toko, metode pembayaran, dan halaman sistem (Cart, Checkout, Thanks, Tracking).
  - Pengiriman:
    - Masukkan API Key RajaOngkir (via Komerce).
    - Pilih asal pengiriman (Provinsi → Kota → Kecamatan).
    - Pilih kurir yang diaktifkan.
  - Cache data wilayah dikendalikan otomatis, tersedia tombol “Clear Cache” di tab Tools.

- Produk
  - Tambah produk baru di “Produk”.
  - Lengkapi harga, stok, berat (Kg). Berat akan dikonversi otomatis ke gram untuk kalkulasi ongkir.

- Keranjang & Checkout
  - Pengguna dapat menambah ke keranjang dari halaman produk/katalog.
  - Di Checkout, pilih tujuan pengiriman dan kurir; biaya dihitung otomatis dari berat total keranjang.
  - Setelah pesanan dibuat, status pesanan dapat dilihat di admin.

- Wishlist
  - Tombol “Tambah ke Wishlist” tersedia di kartu produk/halaman produk.
  - Jika belum login, akan muncul modal login secara instan tanpa transisi.
  - Wishlist untuk guest disimpan dengan cookie, untuk user tersimpan di database.

- Tracking & Bukti Pembayaran
  - Halaman Tracking dapat diatur di Pengaturan Halaman.
  - Bukti pembayaran (transfer) dapat diunggah via halaman publik yang disediakan, akan muncul di detail pesanan admin.

## Untuk Developer

- REST API Utama
  - Base: `/wp-json/wp-store/v1/`
  - Produk: `/products` (lihat controller terkait)
  - Keranjang: `/cart` (GET/POST; operasi write membutuhkan nonce `X-WP-Nonce`)
  - Wishlist: `/wishlist` (GET/POST/DELETE; write membutuhkan nonce)
  - Checkout: `/checkout` (POST; membutuhkan nonce)
  - RajaOngkir:
    - `/rajaongkir/provinces` GET
    - `/rajaongkir/cities?province={id}` GET
    - `/rajaongkir/subdistricts?city={id}` GET
    - `/rajaongkir/calculate` POST
  - Captcha: `/captcha/new` GET
  - Tools: `/tools/seed-products` POST (admin), `/tools/clear-cache` POST (admin), `/tools/cache-stats` GET (admin)

- Security
  - Semua endpoint write publik menggunakan nonce REST: header `X-WP-Nonce` dengan `wp_create_nonce('wp_rest')`.
  - Admin-only (Tools) dibatasi dengan `current_user_can('manage_options')`.

- Database & Identitas
  - Tabel `store_carts`: menyimpan cart, snapshot shipping_data, dan total_price.
  - Tabel `store_wishlists`: menyimpan wishlist per user/guest.
  - Guest diidentifikasi via cookie `wp_store_cart_key`.

- Komerce API (Wrapper RajaOngkir)
  - Base: `https://rajaongkir.komerce.id/api/v1`
  - Endpoint yang digunakan: destination/province, destination/city/{province}, destination/district/{city}, calculate/domestic-cost.
  - Header: `key: <API_KEY>`, body `application/x-www-form-urlencoded`.

- Berat & Cache
  - Berat total dihitung dari meta `_store_weight_kg` per produk dan dikonversi ke gram.
  - Cache wilayah dan ongkir menggunakan `transient` dengan masa simpan ± 24 jam.

- Hook & Filter
  - Checkout:
    - `wp_store_before_create_order($data, WP_REST_Request $request)`
    - `wp_store_after_create_order($order_id, $data, $lines, $order_total)`
  - Shipping:
    - `wp_store_before_calculate_shipping($params, WP_REST_Request $request)`
    - `wp_store_shipping_weight($grams, $params)`
    - `wp_store_shipping_cache_key($key, $params)`
    - `wp_store_shipping_services($services, $params)`
    - `wp_store_shipping_payload($payload, $params)`
    - `wp_store_after_calculate_shipping($payload, $params)`
    - `wp_store_shipping_calculated($payload, $params)`
  - Bukti Pembayaran:
    - `wp_store_upload_allowed_statuses($allowed_statuses, $order_id)`
    - `wp_store_upload_proof_allowed_mimes($mimes, $order_id)`
    - `wp_store_before_upload_proof($file, $order_id)`
    - `wp_store_payment_proof_uploaded($order_id, $attachment_id, $url)`
    - `wp_store_after_upload_proof($order_id, $attachment_id, $url)`
  - Tools:
    - `wp_store_tools_products_seeded($created_ids)`
    - `wp_store_after_seed_products($created_ids)`
  - Captcha:
    - `wp_store_captcha_code_length($length)`
    - `wp_store_captcha_code($code)`
    - `wp_store_captcha_svg($svg, $code)`
    - `wp_store_captcha_created($id, $code)`

- Contoh Penggunaan Filter
```php
// Tambah catatan khusus saat checkout
add_filter('wp_store_before_create_order', function ($data, $request) {
    $data['notes'] = trim(($data['notes'] ?? '') . "\nSumber: Landing Promo A");
    return $data;
}, 10, 2);

// Tambah layanan kurir kustom setelah kalkulasi
add_filter('wp_store_after_calculate_shipping', function ($payload, $params) {
    $payload['services'][] = [
        'courier' => 'CUSTOM',
        'service' => 'SAME_DAY',
        'description' => 'Kurir internal same-day',
        'cost' => 25000,
        'etd' => '0-1'
    ];
    return $payload;
}, 10, 2);

// Validasi file bukti transfer sebelum upload
add_filter('wp_store_before_upload_proof', function ($file, $order_id) {
    if (empty($file['type']) && preg_match('/\.(jpe?g|png|webp|pdf)$/i', $file['name'] ?? '')) {
        $file['type'] = 'image/jpeg';
    }
    return $file;
}, 10, 2);
```

