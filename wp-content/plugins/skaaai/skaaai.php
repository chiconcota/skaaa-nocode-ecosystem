<?php
/**
 * Plugin Name: Skaaai
 * Plugin URI: https://skaaa.net
 * Description: The 1-Click Sync & Deployment Bridge between Localhost Dev and Live Webhost for the Skaaa Ecosystem.
 * Version: 1.1.0
 * Author: Ly Tat Thanh + Antigravity AI
 * Author URI: https://lytatthanh.com
 * Text Domain: skaaai
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.2
 */

defined( 'ABSPATH' ) || exit;

// Khai báo hằng số hệ thống
define( 'SKAAAI_VERSION', '1.1.0' );
define( 'SKAAAI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SKAAAI_URL', plugin_dir_url( __FILE__ ) );
define( 'SKAAAI_FILE', __FILE__ );

// Thư mục lưu trữ Custom Nodes bền vững (Persistent Storage), nằm ngoài plugin để không bị xóa khi update
if ( ! defined( 'SKAAAI_CUSTOM_NODES_DIR' ) ) {
    define( 'SKAAAI_CUSTOM_NODES_DIR', ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content' ) . '/skaaa-custom-nodes/' );
}

// Tải text domain cho đa ngôn ngữ
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'skaaai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Nạp file lõi điều phối
require_once SKAAAI_DIR . 'inc/class-skaaai-core.php';

// Khởi chạy Skaaai Core khi các plugin khác đã nạp xong (priority 20 để sau Logic Engine & Data Pro)
add_action( 'plugins_loaded', function() {
    \Skaaai\Core::instance();
}, 20 );
