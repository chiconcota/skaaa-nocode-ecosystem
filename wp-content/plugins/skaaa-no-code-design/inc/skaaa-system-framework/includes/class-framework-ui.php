<?php
namespace Skaaa_System_Framework;

defined('ABSPATH') || exit;

class Framework_UI
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_init', array($this, 'save_settings'));
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('skaaa_system_dashboard_modules', array($this, 'render_default_modules'), 99);
        
        // AJAX hooks cho quản lý Extensions trực tiếp
        add_action('wp_ajax_skaaa_system_toggle_node_status', array($this, 'ajax_toggle_node_status'));
        add_action('wp_ajax_skaaa_system_delete_node_plugin', array($this, 'ajax_delete_node_plugin'));
    }

    public function save_settings()
    {
        if (isset($_POST['skaaa_settings_nonce']) && wp_verify_nonce($_POST['skaaa_settings_nonce'], 'skaaa_save_settings')) {
            if (!current_user_can('manage_options')) {
                wp_die(__( 'Not enough authority.', 'skaaa-no-code-design' ));
            }

            // Lưu trạng thái checkbox (nếu checked thì tồn tại biến POST, nếu không thì vắng mặt)
            $dev_mode = isset($_POST['skaaa_system_dev_mode']) ? '1' : '0';
            update_option('skaaa_system_dev_mode', $dev_mode);

            $url = add_query_arg('settings-updated', 'true', wp_get_referer());
            wp_safe_redirect($url ? $url : admin_url('admin.php?page=skaaa-system-dashboard'));
            exit;
        }
    }

    public function render_default_modules()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Tự động kiểm tra Skaaa No-code Design (đang chạy framework thì hiển nhiên active nhưng nếu framework load từ chỗ khác thì sao?)
        // Ở đây giả định Skaaa No-Code Design đã active nếu chạy code này, hoặc được inject từ chính plugin của nó.

        // Mảng cấu hình các module lõi cần điểm danh
        $ecosystem_modules = [
            'skaaa-data-pro' => [
                'name' => 'Skaaa Data Pro',
                'desc' => __( 'Administer Flat Tables (skaaa_data_*), Schema Manager and Strategy Pattern DataGrid. ', 'skaaa-no-code-design' ),
                'icon' => 'database',
                'path' => 'skaaa-data-pro/skaaa-data-pro.php',
                'url' => 'https://skaaa.vn/data-pro' // Link tải plugin
            ],
            'skaaa-logic-engine' => [
                'name' => 'Skaaa Logic Engine',
                'desc' => __( 'Cement binds the ecosystem. ', 'skaaa-no-code-design' ),
                'icon' => 'account_tree',
                'path' => 'skaaa-logic-engine/skaaa-logic-engine.php',
                'url' => 'https://skaaa.vn/logic-engine'
            ],
            'skaaai' => [
                'name' => 'Skaaai (Bridge & Sync)',
                'desc' => __( 'Bidirectional Sync Bridge, Remote Code Deployer and Local Agent Harness.', 'skaaa-no-code-design' ),
                'icon' => 'sync_alt',
                'path' => 'skaaai/skaaai.php',
                'url' => 'https://skaaa.vn/skaaai'
            ]
        ];

        foreach ($ecosystem_modules as $id => $mod) {
            // Nếu đã active thì plugin đó TỰ động hook thẻ của nó vào rồi. 
            // Framework chỉ lo phần hiển thị Fallback (Chưa cài / Chưa kích hoạt).
            if (!is_plugin_active($mod['path'])) {
                $is_installed = file_exists(WP_PLUGIN_DIR . '/' . $mod['path']);
                $status_class = $is_installed ? 'bg-slate-200 text-slate-700 border-slate-300' : 'bg-rose-100 text-rose-700 border-rose-200';
                $status_text = $is_installed ? __( 'Not activated yet', 'skaaa-no-code-design' ) : __( 'Not installed yet', 'skaaa-no-code-design' );
                ?>
                <div class="module-card rounded-2xl p-6 flex flex-col sm:flex-row gap-6 relative overflow-hidden mt-4 group opacity-75 grayscale-[40%] hover:grayscale-0 transition-all duration-500">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-300 group-hover:bg-indigo-400 transition-colors"></div>
                    <div class="w-16 h-16 bg-slate-100 text-slate-500 rounded-2xl flex items-center justify-center flex-shrink-0 border border-slate-200 shadow-inner group-hover:bg-indigo-50 group-hover:text-indigo-500 group-hover:border-indigo-100 transition-all duration-300">
                        <span class="material-symbols-outlined text-[32px]"><?php echo esc_attr($mod['icon']); ?></span>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold text-slate-800 text-lg m-0 border-0 pt-0 pb-0">
                                    <?php echo esc_html($mod['name']); ?></h3>
                                <p class="text-sm text-slate-500 mt-2 leading-relaxed"><?php echo esc_html($mod['desc']); ?></p>
                            </div>
                            <span class="h-[24px] inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold border shadow-sm <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>
                        </div>
                        <div class="mt-5 flex gap-3 text-sm">
                            <?php if ($is_installed): ?>
                                <button class="border-0 bg-slate-100 text-slate-500 px-4 py-2 rounded-lg font-medium cursor-not-allowed flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">power_off</span> <?php esc_html_e( 'Please enable in Plugins tab', 'skaaa-no-code-design' ); ?>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo esc_url($mod['url']); ?>" target="_blank"
                                    class="inline-flex items-center gap-1 text-indigo-600 font-semibold hover:text-indigo-800 transition-colors bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-lg no-underline">
                                    <span class="material-symbols-outlined text-[18px]">download</span> <?php esc_html_e( 'Download Plugin', 'skaaa-no-code-design' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>

        <!-- Theme: Skaaa Canvas -->
        <?php
        $theme_obj = wp_get_theme('skaaa-canvas');
        $theme_exists = $theme_obj->exists();
        $theme_active = (get_template() === 'skaaa-canvas' || get_stylesheet() === 'skaaa-canvas');

        // CSS Style theo trạng thái Theme
        $t_wrap_style = $theme_active ? 'module-card rounded-2xl p-6 flex flex-col sm:flex-row gap-6 relative overflow-hidden mt-4 group' : 'module-card rounded-2xl p-6 flex flex-col sm:flex-row gap-6 relative overflow-hidden mt-4 group opacity-75 grayscale-[40%] hover:grayscale-0 transition-all duration-500';
        $t_icon_style = $theme_active ? 'bg-gradient-to-br from-indigo-50 to-purple-50 text-indigo-600 border border-indigo-100 shadow-inner group-hover:scale-105 transition-transform duration-300' : 'bg-slate-100 text-slate-500 border-slate-200 shadow-inner group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-all duration-300';
        $t_title_style = $theme_active ? 'text-slate-900' : 'text-slate-800';
        ?>
        <div class="<?php echo esc_attr($t_wrap_style); ?>">
            <div class="absolute top-0 left-0 w-1.5 h-full <?php echo $theme_active ? 'bg-gradient-to-b from-indigo-400 to-purple-600' : 'bg-slate-300 group-hover:bg-indigo-400'; ?> transition-colors"></div>
            <?php if ($theme_active): ?>
                <div class="absolute top-0 right-0 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-[10px] font-bold px-4 py-1.5 rounded-bl-xl uppercase tracking-wider shadow-sm">
                    <?php esc_html_e( 'Active Theme', 'skaaa-no-code-design' ); ?>
                </div>
            <?php endif; ?>
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0 <?php echo esc_attr($t_icon_style); ?>">
                <span class="material-symbols-outlined text-[32px]">web</span>
            </div>
            <div class="flex-1">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="m-0 pt-0 pb-0 border-0 font-bold text-lg <?php echo esc_attr($t_title_style); ?>"><?php esc_html_e( 'Skaaa Canvas Theme', 'skaaa-no-code-design' ); ?></h3>
                        <p class="text-sm text-slate-600 mt-2 leading-relaxed"><?php esc_html_e( 'Barebone WP Theme (Zero CSS). ', 'skaaa-no-code-design' ); ?></p>
                    </div>
                    <?php if (!$theme_active): ?>
                        <?php
                        $t_status_class = $theme_exists ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-rose-50 text-rose-600 border-rose-200';
                        $t_status_text = $theme_exists ? __( 'Not activated yet', 'skaaa-no-code-design' ) : __( 'Not installed yet', 'skaaa-no-code-design' );
                        ?>
                        <span class="h-[24px] inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold border shadow-sm <?php echo esc_attr($t_status_class); ?>">
                            <?php echo esc_html($t_status_text); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="mt-5 flex gap-4 text-sm">
                    <?php if ($theme_active): ?>
                        <a href="#" class="inline-flex items-center gap-1 text-indigo-600 font-semibold hover:text-indigo-800 transition-colors bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-lg no-underline">
                            <span class="material-symbols-outlined text-[18px]">brush</span> <?php esc_html_e( 'Customize Theme.json', 'skaaa-no-code-design' ); ?>
                        </a>
                    <?php elseif ($theme_exists): ?>
                        <a href="<?php echo admin_url('themes.php'); ?>" class="inline-flex items-center gap-1 text-slate-700 font-semibold hover:text-indigo-700 transition-colors bg-slate-100 hover:bg-indigo-50 px-4 py-2 rounded-lg no-underline border border-slate-200 hover:border-indigo-200">
                            <span class="material-symbols-outlined text-[18px]">toggle_on</span> <?php esc_html_e( 'Activate in Appearance', 'skaaa-no-code-design' ); ?>
                        </a>
                    <?php else: ?>
                        <a href="https://skaaa.vn/canvas-theme" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 font-semibold hover:text-indigo-800 transition-colors bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-lg no-underline">
                            <span class="material-symbols-outlined text-[18px]">download</span> <?php esc_html_e( 'Download Base Theme', 'skaaa-no-code-design' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function register_menu()
    {
        add_menu_page(
            __('Skaaa Ecosystem', 'skaaa-no-code-design'),
            __('Skaaa Ecosystem', 'skaaa-no-code-design'),
            'manage_options',
            'skaaa-system-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-building',
            3
        );
    }

    public function enqueue_assets($hook)
    {
        if ('toplevel_page_skaaa-system-dashboard' !== $hook) {
            return;
        }

        wp_enqueue_script('wp-util');

        wp_enqueue_script(
            'skaaa-system-ai',
            SKAAA_SYSTEM_FRAMEWORK_URL . 'assets/js/ai-blueprint.js',
            array('jquery', 'wp-util'),
            SKAAA_SYSTEM_FRAMEWORK,
            true
        );

        wp_localize_script('skaaa-system-ai', 'skaaaSystemObj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skaaa_ai_blueprint_nonce'),
            'addon_nonce' => wp_create_nonce('skaaa_system_addon_nonce')
        ));
    }

    /**
     * AJAX Bật / Tắt mềm một node (Lưu trạng thái vô hiệu hóa vào bảng phẳng settings)
     */
    public function ajax_toggle_node_status()
    {
        check_ajax_referer('skaaa_system_addon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'skaaa-no-code-design')));
        }

        $node_type = isset($_POST['node_type']) ? sanitize_text_field($_POST['node_type']) : '';
        $active    = isset($_POST['active']) ? (int)$_POST['active'] : 0;

        if (empty($node_type)) {
            wp_send_json_error(array('message' => __('Node type not specified.', 'skaaa-no-code-design')));
        }

        if (!function_exists('skaaa_get_system_setting') || !function_exists('skaaa_set_system_setting')) {
            wp_send_json_error(array('message' => __('System Settings flat table is not available.', 'skaaa-no-code-design')));
        }

        $disabled_nodes = skaaa_get_system_setting('skaaa_disabled_nodes', array());
        if (!is_array($disabled_nodes)) {
            $disabled_nodes = array();
        }

        if ($active === 1) {
            // Kích hoạt: xóa khỏi mảng bị tắt
            $disabled_nodes = array_values(array_diff($disabled_nodes, array($node_type)));
            $status = 'active';
        } else {
            // Vô hiệu hóa: thêm vào mảng bị tắt nếu chưa có
            if (!in_array($node_type, $disabled_nodes, true)) {
                $disabled_nodes[] = $node_type;
            }
            $status = 'inactive';
        }

        skaaa_set_system_setting('skaaa_disabled_nodes', $disabled_nodes, 'logic_engine');

        wp_send_json_success(array(
            'status'  => $status,
            'message' => $status === 'active' ? __('Activated node successfully.', 'skaaa-no-code-design') : __('Disabled node successfully.', 'skaaa-no-code-design')
        ));
    }

    /**
     * AJAX Xóa vật lý plugin addon chứa node được chọn
     */
    public function ajax_delete_node_plugin()
    {
        check_ajax_referer('skaaa_system_addon_nonce', 'nonce');

        if (!current_user_can('delete_plugins')) {
            wp_send_json_error(array('message' => __('Insufficient permissions to delete plugins.', 'skaaa-no-code-design')));
        }

        $node_type = isset($_POST['node_type']) ? sanitize_text_field($_POST['node_type']) : '';
        if (empty($node_type)) {
            wp_send_json_error(array('message' => __('Node type not specified.', 'skaaa-no-code-design')));
        }

        // Lấy thông tin plugin file thông qua class Reflection
        $plugin_file = $this->get_plugin_file_by_node_type($node_type);

        if (empty($plugin_file)) {
            wp_send_json_error(array('message' => __('Cannot determine the plugin file for this extension.', 'skaaa-no-code-design')));
        }

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Tắt plugin trước khi xóa
        if (is_plugin_active($plugin_file)) {
            deactivate_plugins($plugin_file);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $result = delete_plugins(array($plugin_file));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete plugin files.', 'skaaa-no-code-design')));
        }

        wp_send_json_success(array('message' => __('Deleted extension plugin successfully.', 'skaaa-no-code-design')));
    }

    /**
     * Tìm file chính của plugin chứa node qua Reflection class
     */
    private function get_plugin_file_by_node_type($node_type)
    {
        if (!class_exists('Skaaa_Node_Registry')) {
            return '';
        }

        $registry = \Skaaa_Node_Registry::instance();
        $all_nodes = $registry->get_all_nodes();
        $target_node = null;

        foreach ($all_nodes as $node) {
            if ($node['type'] === $node_type) {
                $target_node = $node;
                break;
            }
        }

        // 1. Quét theo Class Reflection nếu class đã được load
        if (!empty($target_node) && !empty($target_node['class']) && class_exists($target_node['class'])) {
            try {
                $reflector = new \ReflectionClass($target_node['class']);
                $file_path = wp_normalize_path($reflector->getFileName());
                $plugins_dir = wp_normalize_path(WP_PLUGIN_DIR);

                if (str_starts_with($file_path, $plugins_dir)) {
                    $relative_path = ltrim(str_replace($plugins_dir, '', $file_path), '/');
                    $parts = explode('/', $relative_path);
                    $plugin_folder = $parts[0];

                    if (!function_exists('get_plugins')) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }

                    $all_plugins = get_plugins();
                    foreach ($all_plugins as $plugin_file => $data) {
                        if (str_starts_with($plugin_file, $plugin_folder . '/')) {
                            return $plugin_file;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Bỏ qua để chạy phương pháp quét file dự phòng ở dưới
            }
        }

        // 2. Phương pháp dự phòng tối thượng: Quét nội dung các file plugin chính bắt đầu bằng 'skaaa-' để tìm node_type
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $core_plugins = array(
            'skaaa-no-code-design/skaaa-no-code-design.php',
            'skaaa-data-pro/skaaa-data-pro.php',
            'skaaa-logic-engine/skaaa-logic-engine.php',
            'skaaa-bridge/skaaa-bridge.php'
        );

        foreach ($all_plugins as $plugin_file => $data) {
            if (str_starts_with($plugin_file, 'skaaa-') && !in_array($plugin_file, $core_plugins, true)) {
                $full_path = WP_PLUGIN_DIR . '/' . $plugin_file;
                if (file_exists($full_path)) {
                    $content = file_get_contents($full_path);
                    if ($content !== false && strpos($content, $node_type) !== false) {
                        return $plugin_file;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Lấy danh sách các node tùy biến ngoài Core để quản lý
     */
    public static function get_custom_pluggable_nodes()
    {
        if (!class_exists('Skaaa_Node_Registry')) {
            return array();
        }

        $registry = \Skaaa_Node_Registry::instance();
        
        // Quét trực tiếp để xem node nào bị tắt
        $disabled_nodes = array();
        if (function_exists('skaaa_get_system_setting')) {
            $disabled_nodes = skaaa_get_system_setting('skaaa_disabled_nodes', array());
            if (!is_array($disabled_nodes)) {
                $disabled_nodes = array();
            }
        }

        // Lấy toàn bộ node được đăng ký
        $all_nodes = $registry->get_all_nodes();
        $custom_nodes = array();

        // 10 primitive nodes mặc định của core
        $core_node_types = array(
            'TriggerNode',
            'SetDataNode',
            'DBActionNode',
            'DBQueryNode',
            'ConditionNode',
            'SwitchNode',
            'IteratorNode',
            'ApiNode',
            'ClientResponseNode',
            'RenderTemplateNode'
        );

        foreach ($all_nodes as $node) {
            // Chỉ lấy các node không thuộc lõi core
            if (!in_array($node['type'], $core_node_types, true)) {
                $node_type = $node['type'];
                $is_disabled_soft = in_array($node_type, $disabled_nodes, true);
                
                $custom_nodes[$node_type] = array(
                    'type'        => $node_type,
                    'class'       => $node['class'],
                    'label'       => $node['label'],
                    'icon'        => $node['icon'],
                    'description' => $node['description'],
                    'color'       => $node['color'],
                    'category'    => $node['category'],
                    'active'      => !$is_disabled_soft
                );
            }
        }

        return $custom_nodes;
    }

    public function render_dashboard()
    {
        include SKAAA_SYSTEM_FRAMEWORK_PATH . '/views/dashboard-ui.php';
    }
}
