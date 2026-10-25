<?php
/**
 * Plugin Name: Skaaa Logic Engine
 * Description: The "Skaaa-cement" Logic Brain of the Skaaa Builder Ecosystem. Handles workflows, data routing, and node-based automation features.
 * Version: 1.3.2
 * Author: Skaaa Ecosystem
 * Text Domain: skaaa-logic-engine
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit; // Bảo mật chuẩn WordPress

// Khai báo Const
define( 'SKAAA_LOGIC_ENGINE_VERSION', '1.3.2' );
define( 'SKAAA_LOGIC_ENGINE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SKAAA_LOGIC_ENGINE_URL', plugin_dir_url( __FILE__ ) ); // Phục vụ gọi JS UI nếu cần

// Load Text Domain
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'skaaa-logic-engine', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Tuân thủ Load Order Bubble: Giống như Data Pro, 
// không đẻ Class loạn xạ khi mạng lưới Ecosystem (Skaaa Builder Core / Data Pro) chưa nạp xong.
add_action( 'plugins_loaded', 'skaaa_logic_engine_init', 15 ); // Trễ một nhịp (15) để chắc chắn Data Pro (10) đã sẵn sàng

function skaaa_logic_engine_init() {
    // Gọi tệp Quản Đốc Core
    require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/class-skaaa-logic-core.php';
    
    // Khởi tạo
    Skaaa_Logic_Core::instance();
}

