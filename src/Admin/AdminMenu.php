<?php

namespace WpStore\Admin;

class AdminMenu
{
    public function register()
    {
        add_action('admin_menu', [$this, 'add_main_menu'], 5);
    }

    public function add_main_menu()
    {
        // Add top-level menu
        add_menu_page(
            'WP Store',
            'WP Store',
            'manage_options',
            'wp-store',
            [$this, 'render_dashboard'],
            'dashicons-store',
            30
        );

        // Add Dashboard submenu (so it appears first and is named "Dashboard")
        add_submenu_page(
            'wp-store',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'wp-store',
            [$this, 'render_dashboard']
        );

        // Add quick link to Orders under Products menu
        add_submenu_page(
            'edit.php?post_type=store_product',
            'Pesanan',
            'Pesanan',
            'edit_posts',
            'edit.php?post_type=store_order',
            null
        );
    }

    public function render_dashboard()
    {
        $settings = get_option('wp_store_settings', []);
        $currency = isset($settings['currency_symbol']) ? (string) $settings['currency_symbol'] : 'Rp';
        $product_count = (int) (wp_count_posts('store_product')->publish ?? 0);
        $order_count = (int) (wp_count_posts('store_order')->publish ?? 0);
        global $wpdb;
        $posts = $wpdb->prefix . 'posts';
        $meta = $wpdb->prefix . 'postmeta';
        $status_rows = $wpdb->get_results("
            SELECT pm.meta_value AS status, COUNT(*) AS cnt
            FROM {$meta} pm
            INNER JOIN {$posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'store_order'
              AND p.post_status = 'publish'
              AND pm.meta_key = '_store_order_status'
            GROUP BY pm.meta_value
        ");
        $status_counts = [
            'pending' => 0,
            'awaiting_payment' => 0,
            'paid' => 0,
            'processing' => 0,
            'shipped' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        if (is_array($status_rows)) {
            foreach ($status_rows as $r) {
                $key = is_string($r->status) ? $r->status : '';
                if ($key !== '' && isset($status_counts[$key])) {
                    $status_counts[$key] = (int) $r->cnt;
                }
            }
        }
        $revenue_total = (float) $wpdb->get_var("
            SELECT SUM(CAST(tot.meta_value AS DECIMAL(20,2)))
            FROM {$posts} p
            INNER JOIN {$meta} st ON st.post_id = p.ID AND st.meta_key = '_store_order_status'
            INNER JOIN {$meta} tot ON tot.post_id = p.ID AND tot.meta_key = '_store_order_total'
            WHERE p.post_type = 'store_order'
              AND p.post_status = 'publish'
              AND st.meta_value IN ('paid','completed')
        ");
        $revenue_30d = (float) $wpdb->get_var("
            SELECT SUM(CAST(tot.meta_value AS DECIMAL(20,2)))
            FROM {$posts} p
            INNER JOIN {$meta} st ON st.post_id = p.ID AND st.meta_key = '_store_order_status'
            INNER JOIN {$meta} tot ON tot.post_id = p.ID AND tot.meta_key = '_store_order_total'
            WHERE p.post_type = 'store_order'
              AND p.post_status = 'publish'
              AND st.meta_value IN ('paid','completed')
              AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $days_rows = $wpdb->get_results("
            SELECT DATE(p.post_date) AS day, COUNT(*) AS cnt
            FROM {$posts} p
            WHERE p.post_type = 'store_order'
              AND p.post_status = 'publish'
              AND p.post_date >= DATE_SUB(NOW(), INTERVAL 14 DAY)
            GROUP BY DATE(p.post_date)
            ORDER BY day ASC
        ");
        $days = [];
        $counts = [];
        $map = [];
        if (is_array($days_rows)) {
            foreach ($days_rows as $dr) {
                $map[$dr->day] = (int) $dr->cnt;
            }
        }
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $days[] = $d;
            $counts[] = isset($map[$d]) ? (int) $map[$d] : 0;
        }
?>
        <div class="wrap wp-store-dashboard">
            <h1 class="wp-store-dashboard-title">Dashboard Toko</h1>
            <div class="wp-store-dashboard-grid">
                <div class="wp-store-card">
                    <div class="wp-store-card-title">Total Produk</div>
                    <div class="wp-store-card-value"><?php echo esc_html($product_count); ?></div>
                    <div class="wp-store-card-desc">Jumlah produk aktif</div>
                </div>
                <div class="wp-store-card">
                    <div class="wp-store-card-title">Total Pesanan</div>
                    <div class="wp-store-card-value"><?php echo esc_html($order_count); ?></div>
                    <div class="wp-store-card-desc">Semua pesanan masuk</div>
                </div>
                <div class="wp-store-card">
                    <div class="wp-store-card-title">Pendapatan Total</div>
                    <div class="wp-store-card-value"><?php echo esc_html(($currency ?: 'Rp') . ' ' . number_format($revenue_total, 0, ',', '.')); ?></div>
                    <div class="wp-store-card-desc">Status dibayar/selesai</div>
                </div>
                <div class="wp-store-card">
                    <div class="wp-store-card-title">Pendapatan 30 Hari</div>
                    <div class="wp-store-card-value"><?php echo esc_html(($currency ?: 'Rp') . ' ' . number_format($revenue_30d, 0, ',', '.')); ?></div>
                    <div class="wp-store-card-desc">Terakhir 30 hari</div>
                </div>
            </div>
            <div class="wp-store-dashboard-sections">
                <div class="wp-store-box">
                    <div class="wp-store-box-header">Ringkasan Status Pesanan</div>
                    <div class="wp-store-status-grid">
                        <div class="wp-store-status-item"><span>Menunggu Pembayaran</span><span class="wp-store-badge wp-store-badge-yellow"><?php echo esc_html($status_counts['awaiting_payment']); ?></span></div>
                        <div class="wp-store-status-item"><span>Sudah Dibayar</span><span class="wp-store-badge wp-store-badge-green"><?php echo esc_html($status_counts['paid']); ?></span></div>
                        <div class="wp-store-status-item"><span>Sedang Diproses</span><span class="wp-store-badge wp-store-badge-blue"><?php echo esc_html($status_counts['processing']); ?></span></div>
                        <div class="wp-store-status-item"><span>Dikirim</span><span class="wp-store-badge wp-store-badge-indigo"><?php echo esc_html($status_counts['shipped']); ?></span></div>
                        <div class="wp-store-status-item"><span>Selesai</span><span class="wp-store-badge wp-store-badge-teal"><?php echo esc_html($status_counts['completed']); ?></span></div>
                        <div class="wp-store-status-item"><span>Dibatalkan</span><span class="wp-store-badge wp-store-badge-red"><?php echo esc_html($status_counts['cancelled']); ?></span></div>
                    </div>
                </div>
                <div class="wp-store-box">
                    <div class="wp-store-box-header">Pesanan 14 Hari Terakhir</div>
                    <canvas id="wpStoreOrdersChart" width="800" height="280"></canvas>
                </div>
            </div>
        </div>
        <style>
            .wp-store-dashboard {
                max-width: 1100px;
            }

            .wp-store-dashboard-title {
                font-size: 24px;
                font-weight: 600;
                margin: 20px 0;
            }

            .wp-store-dashboard-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 16px;
            }

            @media (max-width: 960px) {
                .wp-store-dashboard-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 640px) {
                .wp-store-dashboard-grid {
                    grid-template-columns: 1fr;
                }
            }

            .wp-store-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 6px;
                padding: 16px;
            }

            .wp-store-card-title {
                font-size: 13px;
                color: #646970;
            }

            .wp-store-card-value {
                font-size: 24px;
                font-weight: 700;
                color: #1d2327;
                margin-top: 6px;
            }

            .wp-store-card-desc {
                font-size: 12px;
                color: #7a7f86;
                margin-top: 4px;
            }

            .wp-store-dashboard-sections {
                margin-top: 24px;
                display: grid;
                grid-template-columns: 1.5fr 1fr;
                gap: 16px;
            }

            @media (max-width: 960px) {
                .wp-store-dashboard-sections {
                    grid-template-columns: 1fr;
                }
            }

            .wp-store-box {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 6px;
            }

            .wp-store-box-header {
                padding: 12px 16px;
                border-bottom: 1px solid #e5e7eb;
                font-weight: 600;
                color: #1d2327;
            }

            .wp-store-status-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                padding: 16px;
            }

            @media (max-width: 640px) {
                .wp-store-status-grid {
                    grid-template-columns: 1fr;
                }
            }

            .wp-store-status-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #f6f7f7;
                border: 1px solid #dcdcde;
                border-radius: 6px;
                padding: 10px 12px;
            }

            .wp-store-badge {
                display: inline-block;
                padding: 2px 10px;
                border-radius: 9999px;
                font-size: 12px;
                font-weight: 600;
                line-height: 1.4;
                border: 1px solid transparent;
            }

            .wp-store-badge-yellow {
                background: #fef3c7;
                color: #92400e;
                border-color: #fde68a;
            }

            .wp-store-badge-green {
                background: #dcfce7;
                color: #065f46;
                border-color: #86efac;
            }

            .wp-store-badge-blue {
                background: #dbeafe;
                color: #1e40af;
                border-color: #93c5fd;
            }

            .wp-store-badge-indigo {
                background: #e0e7ff;
                color: #3730a3;
                border-color: #a5b4fc;
            }

            .wp-store-badge-teal {
                background: #ccfbf1;
                color: #115e59;
                border-color: #99f6e4;
            }

            .wp-store-badge-red {
                background: #fee2e2;
                color: #991b1b;
                border-color: #fca5a5;
            }
        </style>
        <script>
            (function() {
                var labels = <?php echo wp_json_encode(array_map(function ($d) {
                                    return date('d/m', strtotime($d));
                                }, $days)); ?>;
                var values = <?php echo wp_json_encode($counts); ?>;
                var canvas = document.getElementById('wpStoreOrdersChart');
                if (!canvas) return;
                var ctx = canvas.getContext('2d');
                var W = canvas.width,
                    H = canvas.height;
                var padL = 40,
                    padR = 10,
                    padT = 20,
                    padB = 30;
                var maxVal = 0;
                for (var i = 0; i < values.length; i++) {
                    if (values[i] > maxVal) {
                        maxVal = values[i];
                    }
                }
                maxVal = Math.max(maxVal, 5);
                ctx.clearRect(0, 0, W, H);
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, W, H);
                ctx.strokeStyle = '#e5e7eb';
                ctx.beginPath();
                ctx.moveTo(padL, H - padB);
                ctx.lineTo(W - padR, H - padB);
                ctx.stroke();
                var plotW = W - padL - padR;
                var plotH = H - padT - padB;
                var barW = Math.max(8, Math.floor(plotW / labels.length * 0.6));
                var gap = Math.floor((plotW - barW * labels.length) / (labels.length - 1 || 1));
                var x = padL;
                for (var i = 0; i < labels.length; i++) {
                    var v = values[i];
                    var h = Math.round((v / maxVal) * plotH);
                    var y = H - padB - h;
                    ctx.fillStyle = '#2271b1';
                    ctx.fillRect(x, y, barW, h);
                    ctx.fillStyle = '#374151';
                    ctx.font = '10px system-ui';
                    ctx.textAlign = 'center';
                    ctx.fillText(labels[i], x + barW / 2, H - padB + 14);
                    x += barW + gap;
                }
            })();
        </script>
<?php
    }
}
