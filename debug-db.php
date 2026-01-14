<?php
// Force absolute path to avoid relative path confusion
require_once 'd:/local-site/dev/app/public/wp-load.php';

global $wpdb;
$table = $wpdb->prefix . 'store_carts';

echo "Checking table: $table\n";

$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
echo "Table exists: " . ($exists ? 'YES' : 'NO') . "\n";

if ($exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "Row count: $count\n";

    $rows = $wpdb->get_results("SELECT * FROM $table LIMIT 5");
    print_r($rows);
} else {
    echo "Last error: " . $wpdb->last_error . "\n";

    // Try to create it manually to see error
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
        guest_key VARCHAR(64) NULL DEFAULT NULL,
        cart LONGTEXT NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_user (user_id),
        UNIQUE KEY uniq_guest (guest_key)
    ) {$charset_collate};";

    echo "Attempting to create table...\n";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Check again
    $exists_again = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    echo "Table created: " . ($exists_again ? 'YES' : 'NO') . "\n";
    if (!$exists_again) {
        // Try direct query without dbDelta
        $res = $wpdb->query($sql);
        if ($res === false) {
            echo "Direct query failed: " . $wpdb->last_error . "\n";
        } else {
            echo "Direct query executed.\n";
        }
    }
}
