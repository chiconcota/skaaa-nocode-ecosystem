<?php
/**
 * Lớp Quản Trị Giao Diện Đồng Bộ Toàn Hệ Sinh Thái (Skaaai_Ecosystem_Sync_UI)
 *
 * Chịu trách nhiệm:
 * 1. Bảng điều khiển "1-Click Full Ecosystem Sync" trong Admin Skaaai.
 * 2. Nút tắt nhanh trên WordPress Admin Bar: 🚀 Skaaa Sync ➔ Push All to Live.
 * 3. Hộp thoại đối soát Pre-flight Review Gate & Tiến độ thời gian thực (Progress Modal).
 * 4. Xử lý AJAX dry-run và execute push sang Live Webhost.
 *
 * @package Skaaai
 * @version 1.4.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Ecosystem_Sync_UI {

    /**
     * Khởi tạo các hook
     */
    public static function init(): void {
        $role = Core::get_setting( 'skaaai_role', 'receiver' );

        // Admin Bar Quick Action hiển thị trên cả Backend và Frontend nếu là Sender và có quyền quản trị
        if ( 'sender' === $role ) {
            add_action( 'admin_bar_menu', [ self::class, 'add_admin_bar_menu' ], 95 );
            add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
            add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
            add_action( 'admin_footer', [ self::class, 'render_modal' ] );
            add_action( 'wp_footer', [ self::class, 'render_modal' ] );
        }

        // AJAX handlers
        add_action( 'wp_ajax_skaaai_ecosystem_diff', [ self::class, 'ajax_ecosystem_diff' ] );
        add_action( 'wp_ajax_skaaai_ecosystem_execute', [ self::class, 'ajax_ecosystem_execute' ] );
        add_action( 'wp_ajax_skaaai_ecosystem_pull_diff', [ self::class, 'ajax_ecosystem_pull_diff' ] );
        add_action( 'wp_ajax_skaaai_ecosystem_pull_execute', [ self::class, 'ajax_ecosystem_pull_execute' ] );
    }

    /**
     * Nạp assets CSS & JS cho Modal và Admin Bar
     */
    public static function enqueue_assets(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_enqueue_style(
            'skaaai-ecosystem-sync-css',
            SKAAAI_URL . 'assets/css/skaaai-ecosystem-sync.css',
            [],
            defined( 'SKAAAI_VERSION' ) ? SKAAAI_VERSION : '1.5.0'
        );

        wp_enqueue_script(
            'skaaai-ecosystem-sync-js',
            SKAAAI_URL . 'assets/js/skaaai-ecosystem-sync.js',
            [ 'jquery', 'wp-util' ],
            defined( 'SKAAAI_VERSION' ) ? SKAAAI_VERSION : '1.5.0',
            true
        );

        $remote_url = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );

        wp_localize_script( 'skaaai-ecosystem-sync-js', 'skaaaiEcosystem', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'skaaai_ecosystem_nonce' ),
            'remote_url' => $remote_url,
            'is_paired'  => ! empty( $remote_url ),
            'i18n'       => [
                'not_paired_error'    => __( 'Remote website is not paired yet. Please configure remote connection in Skaaa Bridge settings.', 'skaaai' ),
                'analyzing_push'      => __( 'Analyzing Local Ecosystem vs Live Webhost...', 'skaaai' ),
                'syncing_push'        => __( 'Synchronizing Ecosystem to Live Webhost...', 'skaaai' ),
                'diff_error_push'     => __( 'Failed to analyze ecosystem changes for push.', 'skaaai' ),
                'sync_error_push'     => __( 'Ecosystem synchronization to Live encountered an error.', 'skaaai' ),
                'sync_success_push'   => __( 'Full Ecosystem pushed to Live successfully!', 'skaaai' ),
                'confirm_title_push'  => __( 'Ready to Synchronize to Live', 'skaaai' ),
                'approve_btn_push'    => __( 'Approve & Execute Push', 'skaaai' ),
                'analyzing_pull'      => __( 'Fetching and analyzing Ecosystem from Live Webhost...', 'skaaai' ),
                'syncing_pull'        => __( 'Pulling and applying Ecosystem from Live Webhost to Localhost...', 'skaaai' ),
                'diff_error_pull'     => __( 'Failed to fetch and analyze ecosystem from Live.', 'skaaai' ),
                'sync_error_pull'     => __( 'Ecosystem pull from Live encountered an error.', 'skaaai' ),
                'sync_success_pull'   => __( 'Full Ecosystem pulled from Live successfully!', 'skaaai' ),
                'confirm_title_pull'  => __( 'Review Changes from Live (Pre-flight Diff Gate)', 'skaaai' ),
                'approve_btn_pull'    => __( 'Approve & Pull to Localhost', 'skaaai' ),
            ],
        ] );
    }

    /**
     * Bổ sung nút tắt nhanh trên WordPress Admin Bar
     *
     * @param \WP_Admin_Bar $admin_bar
     */
    public static function add_admin_bar_menu( \WP_Admin_Bar $admin_bar ): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $remote_url = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $is_paired  = ! empty( $remote_url );

        // Menu cha
        $admin_bar->add_node( [
            'id'    => 'skaaai-ecosystem-sync-bar',
            'title' => '<span class="ab-icon dashicons dashicons-randomize" style="margin-top:2px;"></span><span class="ab-label" style="font-weight:600;letter-spacing:0.3px;">' . esc_html__( 'Skaaa Sync', 'skaaai' ) . '</span>',
            'href'  => '#',
            'meta'  => [
                'class' => 'skaaai-admin-bar-root',
                'title' => esc_attr__( 'Bidirectional Full Ecosystem Synchronization', 'skaaai' ),
            ],
        ] );

        // Menu con: 1-Click Full Push to Live
        $admin_bar->add_node( [
            'parent' => 'skaaai-ecosystem-sync-bar',
            'id'     => 'skaaai-bar-full-sync',
            'title'  => '🚀 ' . esc_html__( 'Push All to Live', 'skaaai' ),
            'href'   => '#',
            'meta'   => [
                'class'   => 'skaaai-trigger-full-sync',
                'onclick' => 'return false;',
            ],
        ] );

        // Menu con: 1-Click Full Pull from Live
        $admin_bar->add_node( [
            'parent' => 'skaaai-ecosystem-sync-bar',
            'id'     => 'skaaai-bar-full-pull',
            'title'  => '📥 ' . esc_html__( 'Pull All from Live', 'skaaai' ),
            'href'   => '#',
            'meta'   => [
                'class'   => 'skaaai-trigger-full-pull',
                'onclick' => 'return false;',
            ],
        ] );

        // Menu con: Quản trị kết nối Bridge
        $admin_bar->add_node( [
            'parent' => 'skaaai-ecosystem-sync-bar',
            'id'     => 'skaaai-bar-settings',
            'title'  => '⚙️ ' . esc_html__( 'Bridge Settings & Pairing', 'skaaai' ),
            'href'   => admin_url( 'admin.php?page=skaaai-settings' ),
        ] );

        if ( $is_paired ) {
            $admin_bar->add_node( [
                'parent' => 'skaaai-ecosystem-sync-bar',
                'id'     => 'skaaai-bar-view-live',
                'title'  => '🌐 ' . esc_html__( 'Open Live Website', 'skaaai' ),
                'href'   => $remote_url,
                'meta'   => [ 'target' => '_blank' ],
            ] );
        }
    }

    /**
     * Render Card chuyên dụng trong tab Bridge & Sync của Admin Cockpit
     */
    public static function render_sender_card(): void {
        $remote_url = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $is_paired  = ! empty( $remote_url );
        ?>
        <div class="skaaai-card skaaai-ecosystem-card">
            <div class="skaaai-ecosystem-card-header">
                <div>
                    <span class="skaaai-badge skaaai-badge-purple"><?php esc_html_e( 'Full Ecosystem Sync Engine', 'skaaai' ); ?></span>
                    <h3 style="margin-top: 8px;"><?php esc_html_e( '🚀 1-Click Full Ecosystem Sync to Live', 'skaaai' ); ?></h3>
                    <p class="description">
                        <?php esc_html_e( 'Replicate your entire local build to the Live webhost in a single click: Design Tokens, Header/Footer Organisms, Theme Templates, Logic Workflows, Custom Flat Tables, All Published Pages, and Homepage Setup.', 'skaaai' ); ?>
                    </p>
                </div>
                <div class="skaaai-ecosystem-card-target">
                    <span class="skaaai-target-label"><?php esc_html_e( 'Target Webhost:', 'skaaai' ); ?></span>
                    <?php if ( $is_paired ) : ?>
                        <strong class="skaaai-target-url"><code><?php echo esc_html( $remote_url ); ?></code></strong>
                    <?php else : ?>
                        <span class="skaaai-target-warning">⚠️ <?php esc_html_e( 'Not paired yet', 'skaaai' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="skaaai-scope-selector">
                <h4><?php esc_html_e( 'Select Ecosystem Components to Synchronize:', 'skaaai' ); ?></h4>
                <div class="skaaai-scope-grid">
                    <label class="skaaai-scope-item">
                        <input type="checkbox" name="ecosystem_scope[]" value="presets" checked>
                        <div>
                            <strong>🎨 <?php esc_html_e( 'Design Tokens & Presets', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'Brand colors, typography & automatic CSS compilation', 'skaaai' ); ?></span>
                        </div>
                    </label>

                    <label class="skaaai-scope-item">
                        <input type="checkbox" name="ecosystem_scope[]" value="organisms" checked>
                        <div>
                            <strong>🧩 <?php esc_html_e( 'Organisms (Header & Footer)', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'HeaderBar, FooterBar, and reusable block templates', 'skaaai' ); ?></span>
                        </div>
                    </label>

                    <label class="skaaai-scope-item">
                        <input type="checkbox" name="ecosystem_scope[]" value="theme_templates" checked>
                        <div>
                            <strong>📐 <?php esc_html_e( 'Theme Templates Rules', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'Active header/footer placement across entire site', 'skaaai' ); ?></span>
                        </div>
                    </label>

                    <label class="skaaai-scope-item">
                        <input type="checkbox" name="ecosystem_scope[]" value="workflows" checked>
                        <div>
                            <strong>⚡ <?php esc_html_e( 'Skaaa Logic Workflows', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'Event DAG graphs, form automations & cache refresh', 'skaaai' ); ?></span>
                        </div>
                    </label>

                    <label class="skaaai-scope-item">
                        <input type="checkbox" name="ecosystem_scope[]" value="custom_tables" checked>
                        <div>
                            <strong>🗄️ <?php esc_html_e( 'Custom Flat Tables & Data', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'Projects, wikis, courses schemas & content (excluding submissions)', 'skaaai' ); ?></span>
                        </div>
                    </label>

                    <label class="skaaai-scope-item">
                        <input type="checkbox" name="ecosystem_scope[]" value="pages" checked>
                        <div>
                            <strong>📄 <?php esc_html_e( 'All Published Pages & Media', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'Full page trees, automatic media upload & URL rewriting', 'skaaai' ); ?></span>
                        </div>
                    </label>

                    <label class="skaaai-scope-item">
                        <input type="checkbox" name="ecosystem_scope[]" value="settings" checked>
                        <div>
                            <strong>⚙️ <?php esc_html_e( 'Site Setup & Homepage Routing', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'Static front page linkage, permalinks & cache flush', 'skaaai' ); ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="skaaai-safeguards-notice">
                <span class="dashicons dashicons-shield"></span>
                <p>
                    <strong><?php esc_html_e( '4-Layer Safeguards Active:', 'skaaai' ); ?></strong>
                    <?php esc_html_e( 'Production domains (siteurl/home), administrator credentials, active plugins, user accounts, and customer form submissions are strictly protected from overwrite.', 'skaaai' ); ?>
                </p>
            </div>

            <div class="skaaai-ecosystem-actions" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <button type="button" class="button button-primary button-hero skaaai-btn-gradient skaaai-trigger-full-sync" <?php disabled( ! $is_paired ); ?>>
                    <span class="dashicons dashicons-cloud-upload"></span> <?php esc_html_e( '🚀 1-Click Full Push to Live', 'skaaai' ); ?>
                </button>
                <button type="button" class="button button-secondary button-hero skaaai-btn-pull-gradient skaaai-trigger-full-pull" <?php disabled( ! $is_paired ); ?>>
                    <span class="dashicons dashicons-cloud-download"></span> <?php esc_html_e( '📥 1-Click Full Pull from Live', 'skaaai' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render Hộp thoại Modal đối soát và tiến độ thời gian thực
     */
    public static function render_modal(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div id="skaaai-ecosystem-modal" class="skaaai-modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="skaaai-modal-title">
            <div class="skaaai-modal-dialog">
                <div class="skaaai-modal-header">
                    <h3 id="skaaai-modal-title">
                        <span class="dashicons dashicons-randomize"></span> <span id="skaaai-modal-title-text"><?php esc_html_e( '1-Click Full Ecosystem Sync', 'skaaai' ); ?></span>
                    </h3>
                    <button type="button" class="skaaai-modal-close" aria-label="<?php esc_attr_e( 'Close', 'skaaai' ); ?>">&times;</button>
                </div>

                <div class="skaaai-modal-body">
                    <!-- TRẠNG THÁI 1: ĐANG PHÂN TÍCH (ANALYZING) -->
                    <div id="skaaai-step-analyzing" class="skaaai-modal-step active">
                        <div class="skaaai-spinner-container">
                            <div class="skaaai-pulse-spinner"></div>
                            <p class="skaaai-step-text" id="skaaai-analyzing-text"><?php esc_html_e( 'Analyzing Ecosystem Differences...', 'skaaai' ); ?></p>
                            <span class="skaaai-step-subtext"><?php esc_html_e( 'Running pre-flight dry-run check without modifying database...', 'skaaai' ); ?></span>
                        </div>
                    </div>

                    <!-- TRẠNG THÁI 2: ĐỐI SOÁT PHÊ DUYỆT (PRE-FLIGHT REVIEW GATE) -->
                    <div id="skaaai-step-review" class="skaaai-modal-step hidden">
                        <div class="skaaai-review-banner">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <div>
                                <strong id="skaaai-review-banner-title"><?php esc_html_e( 'Pre-Flight Analysis Completed', 'skaaai' ); ?></strong>
                                <p id="skaaai-review-banner-sub"><?php esc_html_e( 'Review changes below before committing updates.', 'skaaai' ); ?></p>
                            </div>
                        </div>

                        <div class="skaaai-diff-grid" id="skaaai-diff-summary-container">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <div class="skaaai-safeguard-pill">
                            <span class="dashicons dashicons-lock"></span>
                            <span><?php esc_html_e( 'Protected: siteurl, home, admin_email, active_plugins, wp_users & submissions.', 'skaaai' ); ?></span>
                        </div>
                    </div>

                    <!-- TRẠNG THÁI 3: TIẾN ĐỘ THỜI GIAN THỰC (REAL-TIME PROGRESS) -->
                    <div id="skaaai-step-progress" class="skaaai-modal-step hidden">
                        <div class="skaaai-progress-list">
                            <div class="skaaai-progress-item" data-stage="presets">
                                <span class="skaaai-progress-icon"><span class="dashicons dashicons-ellipsis"></span></span>
                                <span class="skaaai-progress-label"><?php esc_html_e( '1. Syncing Design Tokens & Compiling CSS...', 'skaaai' ); ?></span>
                            </div>
                            <div class="skaaai-progress-item" data-stage="organisms">
                                <span class="skaaai-progress-icon"><span class="dashicons dashicons-ellipsis"></span></span>
                                <span class="skaaai-progress-label"><?php esc_html_e( '2. Syncing Organisms & Theme Templates...', 'skaaai' ); ?></span>
                            </div>
                            <div class="skaaai-progress-item" data-stage="workflows">
                                <span class="skaaai-progress-icon"><span class="dashicons dashicons-ellipsis"></span></span>
                                <span class="skaaai-progress-label"><?php esc_html_e( '3. Deploying Workflows & Database Tables...', 'skaaai' ); ?></span>
                            </div>
                            <div class="skaaai-progress-item" data-stage="pages">
                                <span class="skaaai-progress-icon"><span class="dashicons dashicons-ellipsis"></span></span>
                                <span class="skaaai-progress-label"><?php esc_html_e( '4. Sideloading Media & Syncing Pages...', 'skaaai' ); ?></span>
                            </div>
                            <div class="skaaai-progress-item" data-stage="settings">
                                <span class="skaaai-progress-icon"><span class="dashicons dashicons-ellipsis"></span></span>
                                <span class="skaaai-progress-label"><?php esc_html_e( '5. Applying Homepage Routing & Purging Caches...', 'skaaai' ); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- TRẠNG THÁI 4: HOÀN TẤT (COMPLETED) -->
                    <div id="skaaai-step-completed" class="skaaai-modal-step hidden">
                        <div class="skaaai-completed-box">
                            <div class="skaaai-completed-icon">🎉</div>
                            <h4 id="skaaai-completed-title"><?php esc_html_e( 'Full Ecosystem Synchronized Successfully!', 'skaaai' ); ?></h4>
                            <p class="description" id="skaaai-completed-details">
                                <?php esc_html_e( 'All selected components, pages, media, and configurations have been synchronized.', 'skaaai' ); ?>
                            </p>
                            <div class="skaaai-completed-actions">
                                <a href="#" id="btn-view-live-site" target="_blank" class="button button-primary button-hero">
                                    <span class="dashicons dashicons-external"></span> <?php esc_html_e( 'View Live Website', 'skaaai' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- TRẠNG THÁI LỖI (ERROR) -->
                    <div id="skaaai-step-error" class="skaaai-modal-step hidden">
                        <div class="skaaai-error-box">
                            <span class="dashicons dashicons-warning"></span>
                            <h4><?php esc_html_e( 'Synchronization Error', 'skaaai' ); ?></h4>
                            <p id="skaaai-error-message"></p>
                        </div>
                    </div>
                </div>

                <div class="skaaai-modal-footer">
                    <button type="button" class="button button-secondary" id="btn-modal-cancel">
                        <?php esc_html_e( 'Cancel', 'skaaai' ); ?>
                    </button>
                    <button type="button" class="button button-primary button-hero skaaai-btn-gradient hidden" id="btn-approve-sync">
                        <span class="dashicons dashicons-yes"></span> <span id="btn-approve-sync-text"><?php esc_html_e( 'Approve & Execute Sync', 'skaaai' ); ?></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX Xử lý phân tích đối soát Dry-run cho chiều ĐẨY (Push)
     */
    public static function ajax_ecosystem_diff(): void {
        check_ajax_referer( 'skaaai_ecosystem_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $scopes = isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] )
            ? array_map( 'sanitize_key', $_POST['scopes'] )
            : [ 'presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'settings' ];

        $result = Sync_Ecosystem::push_ecosystem_to_remote( true, $scopes );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX Xử lý Thực thi Đồng bộ ĐẨY (Push) sang Live
     */
    public static function ajax_ecosystem_execute(): void {
        check_ajax_referer( 'skaaai_ecosystem_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $scopes = isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] )
            ? array_map( 'sanitize_key', $_POST['scopes'] )
            : [ 'presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'settings' ];

        $result = Sync_Ecosystem::push_ecosystem_to_remote( false, $scopes );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX Xử lý phân tích đối soát Dry-run cho chiều KÉO (Pull)
     */
    public static function ajax_ecosystem_pull_diff(): void {
        check_ajax_referer( 'skaaai_ecosystem_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $scopes = isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] )
            ? array_map( 'sanitize_key', $_POST['scopes'] )
            : [ 'presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'settings' ];

        $result = Sync_Ecosystem::pull_ecosystem_from_remote( true, $scopes );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX Xử lý Thực thi Đồng bộ KÉO (Pull) từ Live về Localhost
     */
    public static function ajax_ecosystem_pull_execute(): void {
        check_ajax_referer( 'skaaai_ecosystem_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'skaaai' ) ] );
        }

        $scopes = isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] )
            ? array_map( 'sanitize_key', $_POST['scopes'] )
            : [ 'presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'settings' ];

        $result = Sync_Ecosystem::pull_ecosystem_from_remote( false, $scopes );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }
}
