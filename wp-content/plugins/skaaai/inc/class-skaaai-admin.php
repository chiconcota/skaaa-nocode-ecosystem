<?php
/**
 * Lớp Quản Trị Admin UI (Skaaai_Admin)
 *
 * Chịu trách nhiệm hiển thị trang cài đặt Skaaa Bridge,
 * xử lý AJAX bắt tay, lưu cấu hình và giao diện Deploy Code.
 *
 * @package Skaaai
 * @version 1.0.3
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Admin {

    private static ?Admin $instance = null;

    public static function get_instance(): Admin {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ], 25 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // AJAX handlers
        add_action( 'wp_ajax_skaaai_save_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_skaaai_regenerate_token', [ $this, 'ajax_regenerate_token' ] );
        add_action( 'wp_ajax_skaaai_test_handshake', [ $this, 'ajax_test_handshake' ] );
        add_action( 'wp_ajax_skaaai_deploy_code_ajax', [ $this, 'ajax_deploy_code' ] );
        add_action( 'wp_ajax_skaaai_push_existing_file', [ $this, 'ajax_push_existing_file' ] );
        add_action( 'wp_ajax_skaaai_load_file_content', [ $this, 'ajax_load_file_content' ] );
        add_action( 'wp_ajax_skaaai_delete_custom_file', [ $this, 'ajax_delete_file' ] );
        add_action( 'wp_ajax_skaaai_init_harness', [ $this, 'ajax_init_harness' ] );
        add_action( 'wp_ajax_skaaai_get_harness_status', [ $this, 'ajax_get_harness_status' ] );
    }

    /**
     * Đăng ký trang quản trị
     */
    public function register_menu(): void {
        global $menu;
        $parent_slug = 'skaaa-system-dashboard';

        $parent_slug   = 'skaaa-system-dashboard';
        $parent_exists = is_array( $menu ) && in_array( $parent_slug, array_column( $menu, 2 ), true );

        if ( $parent_exists ) {
            add_submenu_page( $parent_slug, __( 'Skaaa Bridge & Sync', 'skaaai' ), __( 'Bridge & Sync', 'skaaai' ), 'manage_options', 'skaaai-settings', [ $this, 'render_admin_page' ] );
        } else {
            add_menu_page( __( 'Skaaa Bridge', 'skaaai' ), __( 'Skaaa Bridge', 'skaaai' ), 'manage_options', 'skaaai-settings', [ $this, 'render_admin_page' ], 'dashicons-rest-api', 30 );
        }
    }

    /**
     * Nạp tài nguyên CSS & JS
     */
    public function enqueue_assets( string $hook ): void {
        if ( ! str_contains( $hook, 'skaaai-settings' ) ) {
            return;
        }

        wp_enqueue_style(
            'skaaai-admin-css',
            SKAAAI_URL . 'assets/css/skaaai-admin.css',
            [],
            SKAAAI_VERSION
        );

        wp_enqueue_script(
            'skaaai-admin-js',
            SKAAAI_URL . 'assets/js/skaaai-admin.js',
            [ 'jquery', 'wp-util' ],
            SKAAAI_VERSION,
            true
        );

        wp_localize_script( 'skaaai-admin-js', 'skaaaiAdmin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'skaaai_admin_nonce' ),
            'i18n'     => [
                'copied'          => __( 'Copied to clipboard!', 'skaaai' ),
                'testing'         => __( 'Testing connection...', 'skaaai' ),
                'deploying'       => __( 'Deploying code to remote server...', 'skaaai' ),
                'confirm_delete'  => __( 'Are you sure you want to delete this file? It will be deleted locally and removed from Live Webhost.', 'skaaai' ),
                'saving'          => __( 'Saving settings...', 'skaaai' ),
                'saved'           => __( 'Settings saved successfully!', 'skaaai' ),
                'pushing'         => __( 'Pushing file to remote server...', 'skaaai' ),
                'push_success'    => __( 'File pushed successfully to remote webhost!', 'skaaai' ),
                'loading_file'    => __( 'Loading file content...', 'skaaai' ),
                'init_harness'    => __( 'Initializing Agent Cockpit & Memory...', 'skaaai' ),
                'harness_success' => __( 'Agent Cockpit & Memory initialized successfully!', 'skaaai' ),
                'ready_for_ai'    => __( 'Ready for AI', 'skaaai' ),
                'not_initialized' => __( 'Not Initialized', 'skaaai' ),
            ],
        ] );
    }

    /**
     * AJAX Lưu cấu hình
     */
    public function ajax_save_settings(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $role              = sanitize_key( $_POST['role'] ?? 'receiver' );
        $remote_url        = esc_url_raw( $_POST['remote_url'] ?? '' );
        $remote_token      = sanitize_text_field( $_POST['remote_token'] ?? '' );
        $allow_code_deploy = ! empty( $_POST['allow_code_deploy'] );

        Core::set_setting( 'skaaai_role', $role );
        Core::set_setting( 'skaaai_remote_url', $remote_url );
        Core::set_setting( 'skaaai_remote_token', $remote_token );
        Core::set_setting( 'skaaai_allow_code_deploy', $allow_code_deploy );

        wp_send_json_success( [ 'message' => __( 'Settings updated successfully.', 'skaaai' ) ] );
    }

    /**
     * AJAX Tạo lại Pairing Token
     */
    public function ajax_regenerate_token(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $new_token = Pairing::regenerate_token();
        $new_uri   = Pairing::get_pairing_uri();

        wp_send_json_success( [
            'token'       => $new_token,
            'pairing_uri' => $new_uri,
            'message'     => __( 'Secret token regenerated successfully.', 'skaaai' ),
        ] );
    }

    /**
     * AJAX Kiểm tra Handshake
     */
    public function ajax_test_handshake(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $remote_url = esc_url_raw( $_POST['remote_url'] ?? '' );
        $token      = sanitize_text_field( $_POST['token'] ?? '' );

        if ( empty( $remote_url ) || empty( $token ) ) {
            wp_send_json_error( [ 'message' => __( 'Remote URL and Token are required.', 'skaaai' ) ] );
        }

        $result = Pairing::test_remote_handshake( $remote_url, $token );
        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX Deploy Code từ Local sang Remote (Đồng thời lưu bản sao tại máy Local)
     */
    public function ajax_deploy_code(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $filename   = sanitize_text_field( $_POST['filename'] ?? '' );
        $code       = wp_unslash( $_POST['code'] ?? '' );
        $overwrite  = ! empty( $_POST['overwrite'] );
        $remote_url = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $token      = sanitize_text_field( Core::get_setting( 'skaaai_remote_token', '' ) );

        if ( empty( $remote_url ) || empty( $token ) ) {
            wp_send_json_error( [ 'message' => __( 'Remote website is not configured. Please pair your site first.', 'skaaai' ) ] );
        }

        // Kiểm tra cú pháp sơ bộ tại local trước khi thực hiện
        $lint_check = File_Deployer::validate_php_syntax( $code );
        if ( ! $lint_check['valid'] ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'Local syntax check failed: %s', 'skaaai' ), $lint_check['error'] ) ] );
        }

        // BƯỚC 1: Lưu bản sao trực tiếp vào thư mục skaaa-custom-nodes/ của máy Dev Local
        $local_save = File_Deployer::save_local_file( $filename, $code, [], $overwrite );
        if ( ! $local_save['success'] ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'Local save failed: %s', 'skaaai' ), $local_save['message'] ) ] );
        }

        // BƯỚC 2: Gửi qua REST API của máy nhận Live Webhost
        $endpoint = rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/deploy-file';
        $response = wp_remote_post( $endpoint, [
            'timeout' => 20,
            'headers' => [
                'X-Skaaai-Token' => $token,
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
            ],
            'body'      => wp_json_encode( [
                'filename'  => $filename,
                'code'      => $code,
                'overwrite' => $overwrite,
            ] ),
            'sslverify' => false,
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [
                'message'     => sprintf( __( 'Saved locally, but remote network error: %s', 'skaaai' ), $response->get_error_message() ),
                'local_saved' => true,
                'file_meta'   => [
                    'filename' => $local_save['filename'] ?? $filename,
                    'size'     => size_format( strlen( $code ) ),
                    'modified' => date_i18n( 'Y-m-d H:i', time() ),
                ],
            ] );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! empty( $data['success'] ) ) {
            $data['local_saved'] = true;
            $data['file_meta']   = [
                'filename' => $local_save['filename'] ?? $filename,
                'size'     => size_format( strlen( $code ) ),
                'modified' => date_i18n( 'Y-m-d H:i', time() ),
                'has_bak'  => $local_save['backup_made'] ?? false,
            ];
            wp_send_json_success( $data );
        } else {
            $err = $data['message'] ?? __( 'Unknown deployment error occurred.', 'skaaai' );
            wp_send_json_error( [
                'message'     => sprintf( __( 'Saved locally, but remote deployment failed: %s', 'skaaai' ), $err ),
                'local_saved' => true,
                'file_meta'   => [
                    'filename' => $local_save['filename'] ?? $filename,
                    'size'     => size_format( strlen( $code ) ),
                    'modified' => date_i18n( 'Y-m-d H:i', time() ),
                ],
            ] );
        }
    }

    /**
     * AJAX Đẩy file đã có sẵn trên máy Local lên Live Webhost
     */
    public function ajax_push_existing_file(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $filename   = sanitize_text_field( $_POST['filename'] ?? '' );
        $remote_url = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $token      = sanitize_text_field( Core::get_setting( 'skaaai_remote_token', '' ) );

        if ( empty( $remote_url ) || empty( $token ) ) {
            wp_send_json_error( [ 'message' => __( 'Remote website is not configured. Please pair your site first.', 'skaaai' ) ] );
        }

        $code = File_Deployer::get_file_content( $filename );
        if ( is_null( $code ) ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'Local file %s not found.', 'skaaai' ), $filename ) ] );
        }

        $endpoint = rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/deploy-file';
        $response = wp_remote_post( $endpoint, [
            'timeout'   => 20,
            'headers'   => [
                'X-Skaaai-Token' => $token,
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
            ],
            'body'      => wp_json_encode( [
                'filename'  => $filename,
                'code'      => $code,
                'overwrite' => true,
            ] ),
            'sslverify' => false,
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => $response->get_error_message() ] );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! empty( $data['success'] ) ) {
            wp_send_json_success( $data );
        } else {
            $err = $data['message'] ?? __( 'Unknown deployment error occurred.', 'skaaai' );
            wp_send_json_error( [ 'message' => $err ] );
        }
    }

    /**
     * AJAX Đọc nội dung file custom node để đưa vào Editor
     */
    public function ajax_load_file_content(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $filename = sanitize_text_field( $_POST['filename'] ?? '' );
        $code     = File_Deployer::get_file_content( $filename );

        if ( is_null( $code ) ) {
            wp_send_json_error( [ 'message' => __( 'File not found.', 'skaaai' ) ] );
        }

        wp_send_json_success( [
            'filename' => $filename,
            'code'     => $code,
        ] );
    }

    /**
     * AJAX Xóa file custom node (Bảo vệ Receiver, tự động đồng bộ xóa trên Live từ Sender)
     */
    public function ajax_delete_file(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $role = Core::get_setting( 'skaaai_role', 'receiver' );
        if ( $role === 'receiver' ) {
            wp_send_json_error( [
                'message' => __( 'Direct deletion is disabled on Live Webhost. Custom nodes are managed by your Local Dev site.', 'skaaai' ),
            ] );
        }

        $filename = sanitize_text_field( $_POST['filename'] ?? '' );
        $result   = File_Deployer::delete_file( $filename );

        if ( ! $result['success'] ) {
            wp_send_json_error( $result );
        }

        // Tự động gọi REST API xóa file trên Live Webhost nếu đang kết nối
        $remote_url     = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $token          = sanitize_text_field( Core::get_setting( 'skaaai_remote_token', '' ) );
        $remote_deleted = false;

        if ( ! empty( $remote_url ) && ! empty( $token ) ) {
            $endpoint = rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/delete-file';
            $response = wp_remote_post( $endpoint, [
                'timeout'   => 15,
                'headers'   => [
                    'X-Skaaai-Token' => $token,
                    'Content-Type'   => 'application/json',
                    'Accept'         => 'application/json',
                ],
                'body'      => wp_json_encode( [
                    'filename' => $filename,
                ] ),
                'sslverify' => false,
            ] );

            if ( ! is_wp_error( $response ) ) {
                $body = wp_remote_retrieve_body( $response );
                $data = json_decode( $body, true );
                $remote_deleted = ! empty( $data['success'] );
            }
        }

        $result['remote_deleted'] = $remote_deleted;
        $result['message'] = $remote_deleted
            ? sprintf( __( 'File %s deleted from Local and removed from Live Webhost.', 'skaaai' ), $filename )
            : sprintf( __( 'File %s deleted from Local.', 'skaaai' ), $filename );

        wp_send_json_success( $result );
    }

    /**
     * AJAX Khởi tạo hoặc Tái đồng bộ Buồng lái Agent Harness & Memory (Sender Only)
     */
    public function ajax_init_harness(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $overwrite = ! empty( $_POST['overwrite'] );
        $result    = Harness_Initializer::initialize( $overwrite );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX Lấy trạng thái Buồng lái Agent Harness
     */
    public function ajax_get_harness_status(): void {
        check_ajax_referer( 'skaaai_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $status = Harness_Initializer::check_status();
        wp_send_json_success( $status );
    }

    /**
     * Render giao diện HTML trang quản trị
     */
    public function render_admin_page(): void {
        $role              = Core::get_setting( 'skaaai_role', 'receiver' );
        $remote_url        = Core::get_setting( 'skaaai_remote_url', '' );
        $remote_token      = Core::get_setting( 'skaaai_remote_token', '' );
        $allow_code_deploy = (bool) Core::get_setting( 'skaaai_allow_code_deploy', false );
        $pairing_uri       = Pairing::get_pairing_uri();
        $secret_token      = Pairing::get_or_create_token();
        $custom_files      = File_Deployer::get_deployed_files();
        $view_file = SKAAAI_DIR . 'views/admin-settings-page.php';
        if ( file_exists( $view_file ) ) {
            include $view_file;
        }
    }
}
