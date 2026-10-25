<?php

defined( 'ABSPATH' ) || exit;

class Skaaa_Logic_Core {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        
        // Khởi tạo Node Registry
        Skaaa_Node_Registry::instance();

        $this->maybe_create_tables();
        $this->init_hooks();
    }

    private function includes() {
        // 1. Interfaces (Khuôn mẫu) & Registry
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/interface-skaaa-node.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/class-skaaa-node-registry.php';
        
        // 2. Các cục Cục Nút (Nodes)
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-trigger.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-set-data.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-condition.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-switch.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-db-action.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-db-query.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-http-request.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-client-response.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-render-template.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/primitives/class-skaaa-logic-iterator.php';
        
        // 3. Đường ray Tàu Hỏa (Pipeline Runner & Async)
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/pipeline/class-workflow-runner.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/pipeline/class-async-worker.php';
        
        // 4. Họng Đón Dữ liệu (API)
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/api/class-form-receiver.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/api/class-blueprint-api.php';
        
        // 5. Đánh chặn Giao diện (Frontend Hydration)
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/pipeline/class-dynamic-content.php';
        
        // 6. SkaaaFX Engine (Parser/Evaluator)
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/skaaafx/class-skaaafx-exceptions.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/skaaafx/class-skaaafx-lexer.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/skaaafx/class-skaaafx-parser.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/skaaafx/class-skaaafx-context-resolver.php';
        require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/skaaafx/class-skaaafx-evaluator.php';
    }

    private function init_hooks() {
        // Khởi động REST API Receiver
        add_action( 'rest_api_init', [ 'Skaaa_Form_Receiver', 'register_routes' ] );
        add_action( 'rest_api_init', [ 'Skaaa_Blueprint_API', 'register_routes' ] );
        
        // Khởi động Frontend Data Hydration Pipeline
        Skaaa_Dynamic_Content::instance();
        
        // Đón nhận Lệnh "Chạy Workflow" và Trả Pipeline Data về
        add_filter( 'skaaa_logic_run_pipeline', [ 'Skaaa_Workflow_Runner', 'execute' ], 10, 2 );

        // Đăng ký WP Admin Menu
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ], 20 );
        
        // Đăng ký Module Card vào System Dashboard
        add_action( 'skaaa_system_dashboard_modules', [ $this, 'render_dashboard_card' ] );

        // Bơm ống Cáp JS cho Lớp Vỏ ở Frontend kết nối với Endpoint
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);

        // Nạp UI Component Universal Dynamic Binding vào Editor Gutenberg
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_scripts']);

        // Đăng ký bảo vệ bảng phẳng hệ thống của Logic Engine
        add_filter( 'skaaa_data_protected_tables', [ $this, 'protect_system_tables' ] );

        // Đăng ký JIT Scan CSS classes cho các Workflows của Logic Engine
        add_filter( 'skaaa_design_classes_to_scan', [ $this, 'scan_workflows_classes' ] );
        // Đăng ký JIT Scan CSS classes từ dữ liệu bảng phẳng trong Database
        add_filter( 'skaaa_design_classes_to_scan', [ $this, 'scan_database_flat_tables_classes' ] );
    }

    public function enqueue_editor_scripts() {
        // Sử dụng asset.php từ wp-scripts để tự động lấy đúng Version và Dependencies (React, Components, v.v.)
        $asset_path = SKAAA_LOGIC_ENGINE_DIR . 'assets/js/dist/index.jsx.asset.php';
        
        if ( file_exists( $asset_path ) ) {
            $asset = require $asset_path;

            // Bổ sung wp-api-fetch vì Schema API dùng nó
            if (!in_array('wp-api-fetch', $asset['dependencies'])) {
                $asset['dependencies'][] = 'wp-api-fetch';
            }

            wp_enqueue_script(
                'skaaa-editor-logic-js',
                SKAAA_LOGIC_ENGINE_URL . 'assets/js/dist/index.jsx.js',
                $asset['dependencies'],
                $asset['version'],
                true
            );

            if ( function_exists( 'wp_set_script_translations' ) ) {
                wp_set_script_translations( 'skaaa-editor-logic-js', 'skaaa-logic-engine', SKAAA_LOGIC_ENGINE_DIR . 'languages' );
            }
        }

        // Truyền đường dẫn API Schema (Fallback cho trường hợp không dùng wp.apiFetch)
        wp_localize_script('skaaa-editor-logic-js', 'skaaaLogicEditorEnv', [
            'schema_api_url' => esc_url_raw( rest_url( 'skaaa-logic/v1/schema' ) )
        ]);
    }

    public function enqueue_frontend_scripts() {
        $version = file_exists( SKAAA_LOGIC_ENGINE_DIR . 'assets/js/skaaa-core.js' ) 
            ? filemtime( SKAAA_LOGIC_ENGINE_DIR . 'assets/js/skaaa-core.js' ) 
            : SKAAA_LOGIC_ENGINE_VERSION;

        wp_enqueue_script(
            'skaaa-core-js',
            SKAAA_LOGIC_ENGINE_URL . 'assets/js/skaaa-core.js',
            [],
            $version,
            true
        );

        // Trao quyền Bản đồ Đường Đi (Route API) & Thông tin cho thư viện $skaaa
        wp_localize_script('skaaa-core-js', 'skaaaAppEnv', [
            'ajaxUrl'  => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
            'restUrl'  => esc_url_raw( rest_url( 'skaaa-logic/v1/submit' ) ),
            'nonce'    => wp_create_nonce('wp_rest') // BẮT BUỘC dùng 'wp_rest' cho WP REST API Header
        ]);
    }

    public function register_admin_menu() {
        // Gắn vào under Skaaa System Framework thay vì tạo Menu Độc Lập
        add_submenu_page(
            'skaaa-system-dashboard',
            'Skaaa Logic Automation', 
            'Skaaa Logic Engine', 
            'manage_options', 
            'skaaa-logic-engine', 
            [ $this, 'render_admin_page' ]
        );

        // Đọc danh sách workflow_id dựa trên Dev Mode
        $dev_mode = get_option( 'skaaa_system_dev_mode', '1' ) === '1';
        $workflow_ids = [];
        if ( $dev_mode ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'skaaa_data_sys_workflows';
            $wpdb->suppress_errors( true );
            $workflow_ids = $wpdb->get_col( "SELECT workflow_id FROM `{$table_name}`" );
            $wpdb->suppress_errors( false );
        } else {
            $workflow_ids = get_option( 'skaaa_logic_workflow_ids', [] );
        }

        if ( is_array( $workflow_ids ) ) {
            foreach ( $workflow_ids as $id ) {
                add_submenu_page(
                    null,
                    $id . ' Workflow', 
                    '— ' . $id, 
                    'manage_options', 
                    'skaaa-logic-engine&view=builder&workflow_id=' . $id, 
                    [ $this, 'render_admin_page' ]
                );
            }
        }
    }

    public function render_dashboard_card() {
        ?>
        <!-- Module: Logic Engine -->
        <div class="module-card rounded-2xl p-6 flex flex-col sm:flex-row gap-6 relative overflow-hidden mt-4 group">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-gradient-to-b from-amber-400 to-orange-600 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="w-16 h-16 bg-gradient-to-br from-amber-50 to-orange-50 text-amber-600 rounded-2xl flex items-center justify-center flex-shrink-0 border border-amber-100 shadow-inner group-hover:scale-105 transition-transform duration-300">
                <span class="material-symbols-outlined text-[32px]">account_tree</span>
            </div>
            <div class="flex-1">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-slate-900 text-lg m-0 p-0">Skaaa Logic Engine</h3>
                        <p class="text-sm text-slate-600 mt-2 leading-relaxed"><?php esc_html_e( 'Cement binds the ecosystem. ', 'skaaa-logic-engine' ); ?></p>
                    </div>
                </div>
                <div class="mt-5 flex gap-4 text-sm">
                    <a href="?page=skaaa-logic-engine" class="inline-flex items-center gap-1 text-indigo-600 font-semibold hover:text-indigo-800 transition-colors bg-indigo-50 px-3 py-1.5 rounded-lg no-underline">
                        <span class="material-symbols-outlined text-[18px]">account_tree</span> <?php esc_html_e( 'Open Workflows', 'skaaa-logic-engine' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_admin_page() {
        // XỬ LÝ ACTIONS QUẢN LÝ TỪ MANAGER UI
        if ( isset($_POST['skaaa_logic_action']) && check_admin_referer('skaaa_logic_nonce') ) {
            $action = sanitize_text_field($_POST['skaaa_logic_action']);
            global $wpdb;
            $table_name = $wpdb->prefix . 'skaaa_data_sys_workflows';
            
            if ($action === 'create') {
                $new_id = sanitize_title($_POST['new_workflow_id']);
                if (!empty($new_id)) {
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table_name}` WHERE workflow_id = %s", $new_id));
                    if (!$exists) {
                        $wpdb->insert(
                            $table_name,
                            [
                                'workflow_id' => $new_id,
                                'name'        => $new_id,
                                'app_id'      => 'skaaa_system',
                                'graph'       => wp_json_encode(['nodes' => [], 'edges' => []]),
                                'node_count'  => 0,
                                'status'      => 'active'
                            ]
                        );
                        self::sync_workflow_ids_cache();
                        // Chuyển thẳng tới Builder
                        echo "<script>window.location.href='?page=skaaa-logic-engine&view=builder&workflow_id={$new_id}';</script>";
                        exit;
                    }
                }
            } elseif ($action === 'delete') {
                $del_id = sanitize_text_field($_POST['workflow_id']);
                $wpdb->delete($table_name, ['workflow_id' => $del_id]);
                self::sync_workflow_ids_cache();
                echo __( '<div class="notice notice-success is-dismissible" style="margin-top:15px; margin-left:0; border-left-color: #ef4444;"><p><strong>Target Carousel shredded!</strong></p></div>', 'skaaa-logic-engine' );
            } elseif ($action === 'rename') {
                $old_id = sanitize_text_field($_POST['old_id']);
                $new_id = sanitize_title($_POST['new_id']);
                if (!empty($new_id)) {
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table_name}` WHERE workflow_id = %s", $new_id));
                    if (!$exists) {
                        $wpdb->update(
                            $table_name,
                            [
                                'workflow_id' => $new_id,
                                'name'        => $new_id
                            ],
                            ['workflow_id' => $old_id]
                        );
                        self::sync_workflow_ids_cache();
                        echo __( '<div class="notice notice-success is-dismissible" style="margin-top:15px; margin-left:0; border-left-color: #3b82f6;"><p><strong>Nameplate has been updated!</strong></p></div>', 'skaaa-logic-engine' );
                    }
                }
            } elseif ($action === 'import_blueprint') {
                $import_id = sanitize_title($_POST['import_workflow_id']);
                $overwrite = isset($_POST['overwrite']) ? true : false;
                
                if (!empty($import_id) && isset($_FILES['blueprint_file']) && $_FILES['blueprint_file']['error'] === UPLOAD_ERR_OK) {
                    $file_content = file_get_contents($_FILES['blueprint_file']['tmp_name']);
                    $graph_data = json_decode($file_content, true);
                    
                    if (is_array($graph_data) && isset($graph_data['nodes']) && isset($graph_data['edges'])) {
                        $node_count = count($graph_data['nodes']);
                        $graph_json = wp_json_encode($graph_data);
                        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table_name}` WHERE workflow_id = %s", $import_id));
                        
                        if ($exists && $overwrite) {
                            $wpdb->update($table_name, ['graph' => $graph_json, 'node_count' => $node_count], ['workflow_id' => $import_id]);
                            self::sync_workflow_ids_cache();
                            echo __( '<div class="notice notice-success is-dismissible" style="margin-top:15px; margin-left:0; border-left-color: #10b981;"><p><strong>Blueprint imported and overwritten successfully!</strong></p></div>', 'skaaa-logic-engine' );
                        } elseif (!$exists) {
                            $wpdb->insert($table_name, [
                                'workflow_id' => $import_id,
                                'name'        => $import_id,
                                'app_id'      => 'skaaa_system',
                                'graph'       => $graph_json,
                                'node_count'  => $node_count,
                                'status'      => 'active'
                            ]);
                            self::sync_workflow_ids_cache();
                            echo __( '<div class="notice notice-success is-dismissible" style="margin-top:15px; margin-left:0; border-left-color: #10b981;"><p><strong>Blueprint imported successfully!</strong></p></div>', 'skaaa-logic-engine' );
                        } else {
                            echo __( '<div class="notice notice-error is-dismissible" style="margin-top:15px; margin-left:0;"><p><strong>Workflow ID already exists. Please check the Overwrite option.</strong></p></div>', 'skaaa-logic-engine' );
                        }
                    } else {
                        echo __( '<div class="notice notice-error is-dismissible" style="margin-top:15px; margin-left:0;"><p><strong>Invalid Blueprint format. Must contain nodes and edges.</strong></p></div>', 'skaaa-logic-engine' );
                    }
                } else {
                    echo __( '<div class="notice notice-error is-dismissible" style="margin-top:15px; margin-left:0;"><p><strong>Please select a valid JSON file.</strong></p></div>', 'skaaa-logic-engine' );
                }
            }
        }

        // Xử lý Lưu Form Line Builder
        if ( isset($_POST['skaaa_logic_save']) && check_admin_referer('skaaa_logic_nonce') ) {
            $form_id    = isset($_POST['skaaa_form_id']) ? sanitize_text_field( $_POST['skaaa_form_id'] ) : '';
            $raw_json   = wp_unslash( $_POST['skaaa_linear_graph'] );
            $graph      = json_decode($raw_json, true);

            if ( !is_array($graph) ) {
                $graph = [];
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'skaaa_data_sys_workflows';
            $node_count = isset($graph['nodes']) && is_array($graph['nodes']) ? count($graph['nodes']) : 0;

            $wpdb->update(
                $table_name,
                [
                    'graph'      => wp_json_encode($graph),
                    'node_count' => $node_count
                ],
                ['workflow_id' => $form_id]
            );
            self::sync_workflow_ids_cache();
            echo __( '<div class="notice notice-success is-dismissible" style="margin-top:15px; margin-left:0; border-left-color: #059669;"><p><strong>The network wire has successfully saved the complex JSON Graph structure!</strong></p></div>', 'skaaa-logic-engine' );
        }

        $current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
        ?>
        <div class="wrap" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-top: 15px;">
                <div style="background: #4f46e5; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);">
                    <span class="dashicons dashicons-networking" style="color: white; font-size: 28px; width: 28px; height: 28px;"></span>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2;">Skaaa Logic Engine</h1>
                    <p style="margin: 0; color: #6b7280; font-size: 13px;"><?php esc_html_e( 'Setting up automation & data flow (Skaaa-cement)', 'skaaa-logic-engine' ); ?></p>
                </div>
            </div>

            <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                <?php if ($current_view === 'builder'): ?>
                    <!-- Nhúng Giao diện Builder Full Width -->
                    <div style="flex: 1; width: 100%;">
                        <?php require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/admin/admin-builder-ui.php'; ?>
                    </div>
                <?php else: ?>
                    <!-- Màn hình Danh sách Quản lý (Manager UI) đè toàn bộ -->
                    <div style="flex: 1; width: 100%;">
                        <?php require_once SKAAA_LOGIC_ENGINE_DIR . 'includes/admin/admin-manager-ui.php'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function protect_system_tables( $protected_tables ) {
        global $wpdb;
        $protected_tables[] = $wpdb->prefix . 'skaaa_data_sys_workflows';
        $protected_tables[] = $wpdb->prefix . 'skaaa_data_sys_settings';
        return $protected_tables;
    }

    public function maybe_create_tables() {
        $db_version = get_option( 'skaaa_logic_db_version', '' );
        if ( $db_version === '1.3.0' ) {
            return;
        }

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Tạo bảng workflows
        $table_workflows = $wpdb->prefix . 'skaaa_data_sys_workflows';
        $sql_workflows = "CREATE TABLE `{$table_workflows}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `workflow_id` varchar(191) NOT NULL,
            `name` varchar(255) NOT NULL,
            `app_id` varchar(100) DEFAULT 'skaaa_system',
            `status` varchar(50) DEFAULT 'active',
            `node_count` int(11) DEFAULT 0,
            `graph` json DEFAULT NULL,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (`id`),
            UNIQUE KEY `workflow_id` (`workflow_id`)
        ) $charset_collate;";
        dbDelta( $sql_workflows );

        // 2. Tạo bảng settings phẳng cho hệ thống
        $table_settings = $wpdb->prefix . 'skaaa_data_sys_settings';
        $sql_settings = "CREATE TABLE `{$table_settings}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(191) NOT NULL,
            `setting_value` longtext DEFAULT NULL,
            `group_name` varchar(100) DEFAULT 'general',
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) $charset_collate;";
        dbDelta( $sql_settings );

        // Xóa option cũ
        delete_option( 'skaaa_logic_simple_workflows' );

        // Đăng ký vào dictionary để hiển thị trong Site Management
        $dictionary = get_option( 'skaaa_data_dictionary', [] );
        if ( ! is_array( $dictionary ) ) {
            $dictionary = [];
        }
        
        if ( ! isset( $dictionary[ $table_workflows ] ) ) {
            $dictionary[ $table_workflows ] = [];
        }
        $dictionary[ $table_workflows ]['__table_info'] = [
            'name'   => 'Workflows',
            'icon'   => 'dashicons-networking',
            'app_id' => 'skaaa_system'
        ];

        if ( ! isset( $dictionary[ $table_settings ] ) ) {
            $dictionary[ $table_settings ] = [];
        }
        $dictionary[ $table_settings ]['__table_info'] = [
            'name'   => 'System Settings',
            'icon'   => 'dashicons-admin-settings',
            'app_id' => 'skaaa_system'
        ];
        
        update_option( 'skaaa_data_dictionary', $dictionary );

        update_option( 'skaaa_logic_db_version', '1.3.0' );

        self::sync_workflow_ids_cache();
    }

    public static function sync_workflow_ids_cache() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skaaa_data_sys_workflows';
        $wpdb->suppress_errors( true );
        $ids = $wpdb->get_col( "SELECT workflow_id FROM `{$table_name}`" );
        $wpdb->suppress_errors( false );
        if ( ! is_array( $ids ) ) {
            $ids = [];
        }
        update_option( 'skaaa_logic_workflow_ids', $ids );
        delete_transient( 'skaaa_dynamic_tailwind_classes_cache' );
    }

    /**
     * Quét tất cả workflows để lấy các class Tailwind và đưa vào JIT compiler
     */
    public function scan_workflows_classes( $classes ) {
        error_log('[Skaaa Logic] scan_workflows_classes triggered. Initial classes length: ' . strlen($classes));
        global $wpdb;
        $table_name = $wpdb->prefix . 'skaaa_data_sys_workflows';
        
        // Quét DB lấy toàn bộ đồ thị của các workflow
        $wpdb->suppress_errors( true );
        $graphs = $wpdb->get_col( "SELECT graph FROM `{$table_name}`" );
        $wpdb->suppress_errors( false );
        
        error_log('[Skaaa Logic] scan_workflows_classes: Found ' . count($graphs) . ' workflows in DB');
        
        if ( empty( $graphs ) || ! is_array( $graphs ) ) {
            return $classes;
        }

        $workflow_classes = [];
        foreach ( $graphs as $graph_json ) {
            if ( empty( $graph_json ) ) {
                continue;
            }
            error_log('[Skaaa Logic] scan_workflows_classes: raw graph type: ' . gettype($graph_json) . ' | value: ' . substr($graph_json, 0, 150));
            $graph = json_decode( $graph_json, true );
            if ( ! is_array( $graph ) ) {
                error_log('[Skaaa Logic] scan_workflows_classes: Failed to decode JSON or not an array!');
                continue;
            }
            if ( empty( $graph['nodes'] ) ) {
                error_log('[Skaaa Logic] scan_workflows_classes: Graph nodes is empty!');
                continue;
            }

            foreach ( $graph['nodes'] as $node ) {
                if ( empty( $node['data'] ) ) {
                    continue;
                }
                $data = $node['data'];
                
                // Trích xuất từ các trường có khả năng chứa HTML hoặc class
                // 1. Render Template Node: template_html / raw_template
                if ( ! empty( $data['template_html'] ) ) {
                    $workflow_classes[] = $this->extract_classes_from_html( $data['template_html'] );
                }
                if ( ! empty( $data['raw_template'] ) ) {
                    $workflow_classes[] = $this->extract_classes_from_html( $data['raw_template'] );
                }
                
                // 2. Client Response Node: modal_content / message (cho toast)
                if ( ! empty( $data['modal_content'] ) ) {
                    $workflow_classes[] = $this->extract_classes_from_html( $data['modal_content'] );
                }
                if ( ! empty( $data['message'] ) ) {
                    $workflow_classes[] = $this->extract_classes_from_html( $data['message'] );
                }
            }
        }

        $workflow_classes_string = implode( ' ', array_filter( $workflow_classes ) );
        error_log('[Skaaa Logic] scan_workflows_classes: Extracted classes: ' . $workflow_classes_string);
        if ( ! empty( $workflow_classes_string ) ) {
            $classes .= ' ' . $workflow_classes_string;
        }

        return $classes;
    }

    /**
     * Regex trích xuất class từ HTML hoặc Gutenberg Block JSON
     */
    private function extract_classes_from_html( $html ) {
        if ( empty( $html ) || ! is_string( $html ) ) {
            return '';
        }
        $classes = [];
        // Tìm thuộc tính class="..."
        if ( preg_match_all( '/class=["\']([^"\']+)["\']/', $html, $matches ) ) {
            foreach ( $matches[1] as $class_attr ) {
                $parts = explode( ' ', $class_attr );
                foreach ( $parts as $part ) {
                    $part = trim( $part );
                    if ( ! empty( $part ) ) {
                        $classes[] = $part;
                    }
                }
            }
        }
        // Tìm thuộc tính "tailwindClasses":"..." trong bình luận block Gutenberg
        if ( preg_match_all( '/"tailwindClasses"\s*:\s*"([^"]+)"/', $html, $matches ) ) {
            foreach ( $matches[1] as $class_attr ) {
                $parts = explode( ' ', $class_attr );
                foreach ( $parts as $part ) {
                    $part = trim( $part );
                    if ( ! empty( $part ) ) {
                        $classes[] = $part;
                    }
                }
            }
        }
        return implode( ' ', array_unique( $classes ) );
    }

    /**
     * Quét tất cả các bảng phẳng của ứng dụng để trích xuất các class Tailwind động
     */
    public function scan_database_flat_tables_classes( $classes ) {
        // Thử lấy từ transient cache trước
        $cached_classes = get_transient( 'skaaa_dynamic_tailwind_classes_cache' );
        if ( $cached_classes !== false ) {
            error_log('[Skaaa Logic] scan_database_flat_tables_classes: Loaded from transient cache.');
            if ( ! empty( $cached_classes ) ) {
                $classes .= ' ' . $cached_classes;
            }
            return $classes;
        }

        error_log('[Skaaa Logic] scan_database_flat_tables_classes: Cache miss. Scanning DB flat tables...');
        global $wpdb;
        $dictionary = get_option( 'skaaa_data_dictionary', [] );
        if ( empty( $dictionary ) || ! is_array( $dictionary ) ) {
            return $classes;
        }

        $all_extracted_classes = [];
        $app_table_prefix = $wpdb->prefix . 'skaaa_data_app_';

        foreach ( $dictionary as $table_name => $fields ) {
            // Chỉ quét các bảng phẳng của app
            if ( ! str_starts_with( $table_name, $app_table_prefix ) ) {
                continue;
            }

            if ( ! is_array( $fields ) ) {
                continue;
            }

            // Tìm các cột chứa text/HTML
            $text_columns = [];
            foreach ( $fields as $field_name => $field_meta ) {
                if ( $field_name === '__table_info' ) {
                    continue;
                }
                $type = isset( $field_meta['type'] ) ? $field_meta['type'] : '';
                if ( in_array( $type, [ 'long_text', 'rich_text', 'text' ], true ) ) {
                    $text_columns[] = $field_name;
                }
            }

            if ( empty( $text_columns ) ) {
                continue;
            }

            // Kiểm tra bảng có thực sự tồn tại trong DB không để tránh lỗi SQL
            $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
            if ( ! $table_exists ) {
                continue;
            }

            // Quét dữ liệu từ mỗi cột text
            foreach ( $text_columns as $column ) {
                $results = $wpdb->get_col( "SELECT `{$column}` FROM `{$table_name}` WHERE `{$column}` IS NOT NULL AND `{$column}` != ''" );
                if ( ! empty( $results ) && is_array( $results ) ) {
                    foreach ( $results as $row_content ) {
                        $extracted = $this->extract_classes_from_html( $row_content );
                        if ( ! empty( $extracted ) ) {
                            $all_extracted_classes[] = $extracted;
                        }
                    }
                }
            }
        }

        $flat_db_classes = implode( ' ', array_filter( $all_extracted_classes ) );
        
        // Loại bỏ các class trùng lặp để chuỗi cache gọn gàng
        if ( ! empty( $flat_db_classes ) ) {
            $flat_db_classes = implode( ' ', array_unique( array_filter( explode( ' ', $flat_db_classes ) ) ) );
        }

        error_log('[Skaaa Logic] scan_database_flat_tables_classes: Finished scanning. Extracted classes: ' . $flat_db_classes);

        // Lưu vào cache transient 12 tiếng
        set_transient( 'skaaa_dynamic_tailwind_classes_cache', $flat_db_classes, 12 * HOUR_IN_SECONDS );

        if ( ! empty( $flat_db_classes ) ) {
            $classes .= ' ' . $flat_db_classes;
        }

        return $classes;
    }
}

if ( ! function_exists( 'skaaa_get_system_setting' ) ) {
    /**
     * Lấy giá trị cấu hình hệ thống từ bảng phẳng wp_skaaa_data_sys_settings
     *
     * @param string $key Khoá cấu hình
     * @param mixed $default Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    function skaaa_get_system_setting( $key, $default = null ) {
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
}

if ( ! function_exists( 'skaaa_set_system_setting' ) ) {
    /**
     * Lưu giá trị cấu hình hệ thống vào bảng phẳng wp_skaaa_data_sys_settings
     *
     * @param string $key Khoá cấu hình
     * @param mixed $value Giá trị cần lưu (hỗ trợ array/object tự động chuyển JSON)
     * @param string $group Phân nhóm cấu hình (mặc định: general)
     * @return bool
     */
    function skaaa_set_system_setting( $key, $value, $group = 'general' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skaaa_data_sys_settings';
        
        $serialized_value = ( is_array( $value ) || is_object( $value ) ) ? wp_json_encode( $value ) : $value;
        
        $wpdb->suppress_errors( true );
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table_name}` WHERE setting_key = %s", $key ) );
        $wpdb->suppress_errors( false );
        
        if ( $exists ) {
            $result = $wpdb->update(
                $table_name,
                array( 'setting_value' => $serialized_value, 'group_name' => $group ),
                array( 'setting_key' => $key )
            );
        } else {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'setting_key'   => $key,
                    'setting_value' => $serialized_value,
                    'group_name'    => $group
                )
            );
        }
        return $result !== false;
    }
}
