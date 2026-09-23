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
    }

    /**
     * Đăng ký trang quản trị
     */
    public function register_menu(): void {
        global $menu;
        $parent_slug = 'skaaa-system-dashboard';

        // Kiểm tra xem menu cha skaaa-system-dashboard có tồn tại không
        $parent_exists = false;
        if ( is_array( $menu ) ) {
            foreach ( $menu as $item ) {
                if ( isset( $item[2] ) && $item[2] === $parent_slug ) {
                    $parent_exists = true;
                    break;
                }
            }
        }

        if ( $parent_exists ) {
            add_submenu_page(
                $parent_slug,
                __( 'Skaaa Bridge & Sync', 'skaaai' ),
                __( 'Bridge & Sync', 'skaaai' ),
                'manage_options',
                'skaaai-settings',
                [ $this, 'render_admin_page' ]
            );
        } else {
            add_menu_page(
                __( 'Skaaa Bridge', 'skaaai' ),
                __( 'Skaaa Bridge', 'skaaai' ),
                'manage_options',
                'skaaai-settings',
                [ $this, 'render_admin_page' ],
                'dashicons-rest-api',
                30
            );
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
        ?>
        <div class="wrap skaaai-admin-wrap">
            <header class="skaaai-header">
                <div class="skaaai-header-left">
                    <span class="skaaai-badge">v<?php echo esc_html( SKAAAI_VERSION ); ?></span>
                    <h1><?php esc_html_e( 'Skaaa Bridge & Deployment', 'skaaai' ); ?></h1>
                    <p class="skaaai-subtitle"><?php esc_html_e( '1-Click Sync & Deployment Bridge between Localhost Dev and Live Webhost.', 'skaaai' ); ?></p>
                </div>
                <div class="skaaai-header-right">
                    <span class="skaaai-status-pill <?php echo $role === 'receiver' ? 'pill-green' : 'pill-blue'; ?>">
                        ● <?php echo $role === 'receiver' ? esc_html__( 'Mode: Receiver (Webhost)', 'skaaai' ) : esc_html__( 'Mode: Sender (Localhost)', 'skaaai' ); ?>
                    </span>
                </div>
            </header>

            <!-- Navigation Tabs -->
            <nav class="skaaai-tabs">
                <button type="button" class="skaaai-tab-btn active" data-tab="pairing">
                    <span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Pairing & Connection', 'skaaai' ); ?>
                </button>
                <button type="button" class="skaaai-tab-btn" data-tab="deployer">
                    <span class="dashicons dashicons-media-code"></span> <?php esc_html_e( 'Code & Node Deployer', 'skaaai' ); ?>
                </button>
            </nav>

            <!-- TAB 1: PAIRING & CONNECTION -->
            <section id="tab-pairing" class="skaaai-tab-pane active">
                <form id="skaaai-pairing-form">
                    <div class="skaaai-card">
                        <h3><?php esc_html_e( 'Site Role Configuration', 'skaaai' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Choose whether this WordPress installation acts as a development sender or production receiver.', 'skaaai' ); ?></p>
                        
                        <div class="skaaai-radio-group">
                            <label class="skaaai-radio-card <?php echo $role === 'receiver' ? 'selected' : ''; ?>">
                                <input type="radio" name="role" value="receiver" <?php checked( $role, 'receiver' ); ?>>
                                <div class="radio-card-content">
                                    <strong><?php esc_html_e( 'Receiver (Live Webhost / Production)', 'skaaai' ); ?></strong>
                                    <span><?php esc_html_e( 'This site receives published pages, media, and code files from your PC.', 'skaaai' ); ?></span>
                                </div>
                            </label>

                            <label class="skaaai-radio-card <?php echo $role === 'sender' ? 'selected' : ''; ?>">
                                <input type="radio" name="role" value="sender" <?php checked( $role, 'sender' ); ?>>
                                <div class="radio-card-content">
                                    <strong><?php esc_html_e( 'Sender (Localhost Dev / PC)', 'skaaai' ); ?></strong>
                                    <span><?php esc_html_e( 'You design layout, write code, and push updates directly to the Live site.', 'skaaai' ); ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- RECEIVER VIEW -->
                    <div id="section-receiver" class="skaaai-role-section <?php echo $role === 'receiver' ? '' : 'hidden'; ?>">
                        <div class="skaaai-card">
                            <h3><?php esc_html_e( 'Receiver Pairing Information', 'skaaai' ); ?></h3>
                            <p class="description"><?php esc_html_e( 'Copy the Pairing Key below and paste it into your Localhost WordPress site.', 'skaaai' ); ?></p>

                            <div class="skaaai-form-group">
                                <label><?php esc_html_e( 'Universal Pairing Key', 'skaaai' ); ?></label>
                                <div class="skaaai-input-group">
                                    <input type="text" id="skaaai-pairing-key-input" class="large-text code" readonly value="<?php echo esc_attr( $pairing_uri ); ?>">
                                    <button type="button" class="button button-primary" id="btn-copy-pairing-key">
                                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy Key', 'skaaai' ); ?>
                                    </button>
                                </div>
                                <span class="help-text"><?php esc_html_e( 'Includes remote URL and encrypted secret token in one portable string.', 'skaaai' ); ?></span>
                            </div>

                            <div class="skaaai-form-group">
                                <label><?php esc_html_e( 'Secret Token', 'skaaai' ); ?></label>
                                <div class="skaaai-input-group">
                                    <input type="password" id="skaaai-secret-token-display" class="regular-text code" readonly value="<?php echo esc_attr( $secret_token ); ?>">
                                    <button type="button" class="button" id="btn-regenerate-token">
                                        <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Regenerate Token', 'skaaai' ); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SENDER VIEW -->
                    <div id="section-sender" class="skaaai-role-section <?php echo $role === 'sender' ? '' : 'hidden'; ?>">
                        <div class="skaaai-card">
                            <h3><?php esc_html_e( 'Connect to Remote Live Webhost', 'skaaai' ); ?></h3>
                            <p class="description"><?php esc_html_e( 'Paste the Pairing Key generated by your online WordPress website.', 'skaaai' ); ?></p>

                            <div class="skaaai-form-group">
                                <label><?php esc_html_e( 'Paste Pairing Key (skaaai_pair://...)', 'skaaai' ); ?></label>
                                <textarea id="skaaai-paste-key-input" class="large-text code" rows="3" placeholder="skaaai_pair://ey..."></textarea>
                                <button type="button" class="button button-secondary" id="btn-parse-key" style="margin-top: 8px;">
                                    <?php esc_html_e( 'Auto-fill Connection Details', 'skaaai' ); ?>
                                </button>
                            </div>

                            <div class="skaaai-grid-2">
                                <div class="skaaai-form-group">
                                    <label><?php esc_html_e( 'Remote Webhost URL', 'skaaai' ); ?></label>
                                    <input type="url" name="remote_url" id="remote_url" class="large-text" value="<?php echo esc_attr( $remote_url ); ?>" placeholder="https://example.com">
                                </div>
                                <div class="skaaai-form-group">
                                    <label><?php esc_html_e( 'Remote Secret Token', 'skaaai' ); ?></label>
                                    <input type="password" name="remote_token" id="remote_token" class="large-text code" value="<?php echo esc_attr( $remote_token ); ?>">
                                </div>
                            </div>

                            <div class="skaaai-actions-bar">
                                <button type="button" class="button button-secondary" id="btn-test-handshake">
                                    <span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Test Connection (Handshake)', 'skaaai' ); ?>
                                </button>
                                <span id="handshake-result-status" class="skaaai-inline-result"></span>
                            </div>
                        </div>
                    </div>

                    <div class="skaaai-submit-row">
                        <button type="submit" class="button button-primary button-hero" id="btn-save-settings">
                            <?php esc_html_e( 'Save Configuration', 'skaaai' ); ?>
                        </button>
                        <span id="save-settings-status" class="skaaai-inline-result"></span>
                    </div>
                </form>
            </section>

            <!-- TAB 2: CODE & NODE DEPLOYER -->
            <section id="tab-deployer" class="skaaai-tab-pane">
                <div class="skaaai-card">
                    <h3><?php esc_html_e( 'Security & Deployment Options', 'skaaai' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Control whether this server accepts incoming custom PHP node files.', 'skaaai' ); ?></p>
                    
                    <label class="skaaai-checkbox-label">
                        <input type="checkbox" name="allow_code_deploy" id="allow_code_deploy" value="1" <?php checked( $allow_code_deploy ); ?>>
                        <strong><?php esc_html_e( 'Allow Remote Code & Pluggable Node Deployment', 'skaaai' ); ?></strong>
                        <span id="allow-code-deploy-status" class="skaaai-inline-result" style="margin-left: 10px;"></span>
                    </label>
                    <p class="description" style="margin-left: 24px;">
                        <?php esc_html_e( 'When enabled, paired senders can deploy PHP classes into the sandbox directory. Strict syntax validation and backup points are enforced automatically.', 'skaaai' ); ?>
                    </p>
                </div>

                <div class="skaaai-grid-2">
                    <!-- DEPLOY TOOL (FOR SENDER) -->
                    <div class="skaaai-card">
                        <h3><?php esc_html_e( '1-Click Code Deployer (Push to Remote)', 'skaaai' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Deploy custom node or helper PHP code directly to the paired remote host.', 'skaaai' ); ?></p>

                        <div class="skaaai-form-group">
                            <label><?php esc_html_e( 'File Name (*.php)', 'skaaai' ); ?></label>
                            <input type="text" id="deploy_filename" class="large-text code" placeholder="class-my-custom-node.php">
                        </div>

                        <div class="skaaai-form-group">
                            <label><?php esc_html_e( 'PHP Source Code', 'skaaai' ); ?></label>
                            <textarea id="deploy_code" class="large-text code" rows="12" placeholder="<?php echo esc_attr( "<?php\ndefined( 'ABSPATH' ) || exit;\n\n// Custom Node Logic..." ); ?>"></textarea>
                        </div>

                        <label class="skaaai-checkbox-label">
                            <input type="checkbox" id="deploy_overwrite" value="1" checked>
                            <?php esc_html_e( 'Overwrite if file exists (creates .bak backup)', 'skaaai' ); ?>
                        </label>

                        <div style="margin-top: 15px;">
                            <button type="button" class="button button-primary" id="btn-deploy-code">
                                <span class="dashicons dashicons-upload"></span> <?php esc_html_e( '🚀 Deploy to Remote Webhost', 'skaaai' ); ?>
                            </button>
                            <div id="deploy-output-status" class="skaaai-console-box hidden"></div>
                        </div>
                    </div>

                    <!-- DEPLOYED FILES LIST (ON RECEIVER & SENDER) -->
                    <div class="skaaai-card">
                        <h3><?php esc_html_e( 'Active Custom Nodes on this Server', 'skaaai' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Files currently loaded inside wp-content/skaaa-custom-nodes/ (Persistent Storage)', 'skaaai' ); ?></p>

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Filename', 'skaaai' ); ?></th>
                                    <th><?php esc_html_e( 'Size', 'skaaai' ); ?></th>
                                    <th><?php esc_html_e( 'Modified', 'skaaai' ); ?></th>
                                    <th><?php esc_html_e( 'Action', 'skaaai' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="custom-nodes-tbody">
                                <?php if ( empty( $custom_files ) ) : ?>
                                    <tr id="no-custom-files-row">
                                        <td colspan="4" class="text-center" style="color: #666; font-style: italic;">
                                            <?php esc_html_e( 'No custom node files deployed yet.', 'skaaai' ); ?>
                                        </td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ( $custom_files as $cf ) : ?>
                                        <tr data-file="<?php echo esc_attr( $cf['filename'] ); ?>">
                                            <td>
                                                <strong><code><?php echo esc_html( $cf['filename'] ); ?></code></strong>
                                                <?php if ( ! empty( $cf['has_bak'] ) ) : ?>
                                                    <span class="skaaai-bak-badge" title="<?php esc_attr_e( 'Backup file exists (.bak)', 'skaaai' ); ?>">📦 .bak</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="file-size"><?php echo esc_html( size_format( $cf['size'] ) ); ?></td>
                                            <td class="file-modified"><?php echo esc_html( date_i18n( 'Y-m-d H:i', $cf['modified'] ) ); ?></td>
                                            <td style="white-space: nowrap;">
                                                <button type="button" class="button button-small btn-load-file" data-file="<?php echo esc_attr( $cf['filename'] ); ?>" title="<?php esc_attr_e( 'Load into code editor', 'skaaai' ); ?>">
                                                    <span class="dashicons dashicons-edit" style="font-size:14px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Edit', 'skaaai' ); ?>
                                                </button>
                                                <?php if ( $role === 'sender' ) : ?>
                                                    <button type="button" class="button button-small btn-push-file" data-file="<?php echo esc_attr( $cf['filename'] ); ?>" title="<?php esc_attr_e( 'Push to paired remote webhost', 'skaaai' ); ?>">
                                                        <span class="dashicons dashicons-upload" style="font-size:14px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Push', 'skaaai' ); ?>
                                                    </button>
                                                    <button type="button" class="button button-small button-link-delete btn-delete-file" data-file="<?php echo esc_attr( $cf['filename'] ); ?>" title="<?php esc_attr_e( 'Delete locally and remove from Live Webhost', 'skaaai' ); ?>">
                                                        <?php esc_html_e( 'Delete', 'skaaai' ); ?>
                                                    </button>
                                                <?php else : ?>
                                                    <span class="skaaai-readonly-badge" title="<?php esc_attr_e( 'Managed by Local Dev. Direct deletion is disabled on Live.', 'skaaai' ); ?>">
                                                        <span class="dashicons dashicons-lock" style="font-size:13px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Live Protected', 'skaaai' ); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <script type="text/html" id="tmpl-skaaai-custom-node-row">
            <tr data-file="{{ data.filename }}">
                <td>
                    <strong><code>{{ data.filename }}</code></strong>
                    <# if ( data.has_bak ) { #>
                        <span class="skaaai-bak-badge" title="<?php esc_attr_e( 'Backup file exists (.bak)', 'skaaai' ); ?>">📦 .bak</span>
                    <# } #>
                </td>
                <td class="file-size">{{ data.size }}</td>
                <td class="file-modified">{{ data.modified }}</td>
                <td style="white-space: nowrap;">
                    <button type="button" class="button button-small btn-load-file" data-file="{{ data.filename }}" title="<?php esc_attr_e( 'Load into code editor', 'skaaai' ); ?>">
                        <span class="dashicons dashicons-edit" style="font-size:14px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Edit', 'skaaai' ); ?>
                    </button>
                    <# if ( data.is_sender ) { #>
                        <button type="button" class="button button-small btn-push-file" data-file="{{ data.filename }}" title="<?php esc_attr_e( 'Push to paired remote webhost', 'skaaai' ); ?>">
                            <span class="dashicons dashicons-upload" style="font-size:14px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Push', 'skaaai' ); ?>
                        </button>
                        <button type="button" class="button button-small button-link-delete btn-delete-file" data-file="{{ data.filename }}">
                            <?php esc_html_e( 'Delete', 'skaaai' ); ?>
                        </button>
                    <# } else { #>
                        <span class="skaaai-readonly-badge">
                            <span class="dashicons dashicons-lock" style="font-size:13px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Live Protected', 'skaaai' ); ?>
                        </span>
                    <# } #>
                </td>
            </tr>
        </script>
        <?php
    }
}
