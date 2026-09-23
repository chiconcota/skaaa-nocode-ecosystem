<?php
/**
 * Lớp Quản Đốc Lõi Skaaai (Skaaai_Core)
 *
 * Chịu trách nhiệm khởi tạo các dịch vụ, quản lý cài đặt trên bảng phẳng MySQL,
 * và tự động nạp các Custom Pluggable Nodes vào Logic Engine.
 *
 * @package Skaaai
 * @version 1.0.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Core {

    /**
     * Singleton instance
     */
    private static ?Core $instance = null;

    /**
     * Lấy thể hiện duy nhất của class
     */
    public static function instance(): Core {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Khởi tạo các module
     */
    private function __construct() {
        $this->ensure_flat_table();
        $this->includes();
        $this->init_hooks();
        $this->load_custom_nodes();
    }

    /**
     * Nạp các tệp cần thiết
     */
    private function includes(): void {
        require_once SKAAAI_DIR . 'inc/class-skaaai-pairing.php';
        require_once SKAAAI_DIR . 'inc/class-skaaai-file-deployer.php';
        require_once SKAAAI_DIR . 'inc/class-skaaai-sync-post.php';
        require_once SKAAAI_DIR . 'inc/class-skaaai-rest-api.php';
        require_once SKAAAI_DIR . 'inc/class-skaaai-admin.php';
    }

    /**
     * Đảm bảo bảng phẳng wp_skaaa_data_sys_settings tồn tại
     */
    public function ensure_flat_table(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skaaa_data_sys_settings';

        $wpdb->suppress_errors( true );
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $wpdb->suppress_errors( false );

        if ( $exists !== $table_name ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE `{$table_name}` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(191) NOT NULL,
                `setting_value` longtext DEFAULT NULL,
                `group_name` varchar(100) DEFAULT 'general',
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`)
            ) $charset_collate;";

            dbDelta( $sql );
        }
    }

    /**
     * Khởi tạo các hook WordPress
     */
    private function init_hooks(): void {
        // Đăng ký REST API
        add_action( 'rest_api_init', [ Rest_Api::class, 'register_routes' ] );

        // Đăng ký Admin Menu & Enqueue Assets
        if ( is_admin() ) {
            Admin::get_instance();
        }

        // Đăng ký các Custom Nodes vào Skaaa Logic Engine nếu có hook
        add_filter( 'skaaa_logic_registered_nodes', [ $this, 'filter_registered_nodes' ] );
    }

    /**
     * Tự động quét và nạp các file PHP trong thư mục custom-nodes/
     */
    public function load_custom_nodes(): void {
        $nodes_dir = File_Deployer::get_target_dir();
        if ( ! is_dir( $nodes_dir ) ) {
            return;
        }

        $files = glob( $nodes_dir . '*.php' );
        if ( empty( $files ) ) {
            return;
        }

        foreach ( $files as $file ) {
            if ( basename( $file ) === 'index.php' ) {
                continue;
            }
            // An toàn nạp file PHP
            require_once $file;
        }
    }

    /**
     * Hook đăng ký các custom nodes vào Logic Engine
     *
     * @param array $nodes Danh sách các nodes hiện tại
     * @return array
     */
    public function filter_registered_nodes( array $nodes ): array {
        // Cho phép các custom node định nghĩa class đăng ký tự động
        $custom_nodes_meta = self::get_setting( 'skaaai_custom_nodes_meta', [] );
        if ( is_array( $custom_nodes_meta ) ) {
            foreach ( $custom_nodes_meta as $type => $config ) {
                if ( ! empty( $config['type'] ) && ! empty( $config['class'] ) && class_exists( $config['class'] ) ) {
                    $nodes[ $config['type'] ] = $config;
                }
            }
        }

        return $nodes;
    }

    /**
     * Helper đọc cấu hình phẳng từ wp_skaaa_data_sys_settings
     *
     * @param string $key Khóa cấu hình
     * @param mixed  $default Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    public static function get_setting( string $key, mixed $default = null ): mixed {
        if ( function_exists( 'skaaa_get_system_setting' ) ) {
            return skaaa_get_system_setting( $key, $default );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'skaaa_data_sys_settings';

        $wpdb->suppress_errors( true );
        $value = $wpdb->get_var( $wpdb->prepare( "SELECT setting_value FROM `{$table_name}` WHERE setting_key = %s", $key ) );
        $wpdb->suppress_errors( false );

        if ( is_null( $value ) ) {
            return $default;
        }

        $decoded = json_decode( $value, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return $decoded;
        }
        return $value;
    }

    /**
     * Helper lưu cấu hình phẳng vào wp_skaaa_data_sys_settings
     *
     * @param string $key Khóa cấu hình
     * @param mixed  $value Giá trị cần lưu (hỗ trợ array tự động encode JSON)
     * @param string $group Phân nhóm cấu hình (mặc định: skaaai)
     * @return bool
     */
    public static function set_setting( string $key, mixed $value, string $group = 'skaaai' ): bool {
        if ( function_exists( 'skaaa_set_system_setting' ) ) {
            return skaaa_set_system_setting( $key, $value, $group );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'skaaa_data_sys_settings';

        $serialized_value = ( is_array( $value ) || is_object( $value ) ) ? wp_json_encode( $value ) : $value;

        $wpdb->suppress_errors( true );
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table_name}` WHERE setting_key = %s", $key ) );
        $wpdb->suppress_errors( false );

        if ( $exists ) {
            $result = $wpdb->update(
                $table_name,
                [ 'setting_value' => $serialized_value, 'group_name' => $group ],
                [ 'setting_key' => $key ]
            );
        } else {
            $result = $wpdb->insert(
                $table_name,
                [
                    'setting_key'   => $key,
                    'setting_value' => $serialized_value,
                    'group_name'    => $group,
                ]
            );
        }

        return $result !== false;
    }
}
