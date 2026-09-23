<?php
/**
 * Lớp Khởi Tạo Buồng Lái Agent & Bộ Nhớ Dưới Localhost (Skaaai_Harness_Initializer)
 *
 * Chịu trách nhiệm:
 * 1. Đảm bảo quy tắc Sender-Only (chỉ cho phép website Localhost khởi tạo, cấm Receiver).
 * 2. Xuất bản cấu trúc buồng lái .agent/ và bộ nhớ .skaaa-ai/ vào thư mục gốc ABSPATH (app/public/).
 * 3. Kiểm tra trạng thái sẵn sàng của buồng lái để hiển thị trên Admin UI.
 *
 * @package Skaaai
 * @version 1.1.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Harness_Initializer {

    /**
     * Kiểm tra xem website hiện tại có đóng vai trò là Sender (Localhost) hay không
     *
     * @return bool
     */
    public static function is_sender(): bool {
        $role = Core::get_setting( 'skaaai_role', 'receiver' );
        return $role === 'sender';
    }

    /**
     * Lấy đường dẫn thư mục gốc WordPress (thường là .../app/public/)
     *
     * @return string
     */
    public static function get_target_base_dir(): string {
        return wp_normalize_path( ABSPATH );
    }

    /**
     * Lấy đường dẫn thư mục chứa template scaffold bên trong plugin
     *
     * @return string
     */
    public static function get_scaffold_source_dir(): string {
        return wp_normalize_path( SKAAAI_DIR . 'scaffold/' );
    }

    /**
     * Kiểm tra trạng thái khởi tạo hiện tại của buồng lái .agent/ và .skaaa-ai/
     *
     * @return array
     */
    public static function check_status(): array {
        $base_dir       = self::get_target_base_dir();
        $agent_dir      = $base_dir . '.agent';
        $skaaa_ai_dir   = $base_dir . '.skaaa-ai';

        $is_sender      = self::is_sender();
        $is_writable    = wp_is_writable( $base_dir );
        $agent_exists   = is_dir( $agent_dir );
        $skaaa_ai_exist = is_dir( $skaaa_ai_dir );
        $is_initialized = $agent_exists && $skaaa_ai_exist;

        // Đếm số lượng files trong các thư mục nếu tồn tại
        $agent_files_count    = $agent_exists ? self::count_files_recursive( $agent_dir ) : 0;
        $skaaa_ai_files_count = $skaaa_ai_exist ? self::count_files_recursive( $skaaa_ai_dir ) : 0;

        return [
            'is_sender'         => $is_sender,
            'is_writable'       => $is_writable,
            'agent_exists'      => $agent_exists,
            'skaaa_ai_exists'   => $skaaa_ai_exist,
            'is_initialized'    => $is_initialized,
            'base_dir'          => $base_dir,
            'agent_dir'         => $agent_dir,
            'skaaa_ai_dir'      => $skaaa_ai_dir,
            'agent_files'       => $agent_files_count,
            'skaaa_ai_files'    => $skaaa_ai_files_count,
        ];
    }

    /**
     * Khởi tạo hoặc tái đồng bộ cấu trúc buồng lái .agent/ và bộ nhớ .skaaa-ai/
     *
     * @param bool $overwrite_existing Có ghi đè file có sẵn hay giữ nguyên
     * @return array
     */
    public static function initialize( bool $overwrite_existing = false ): array {
        // 1. Kiểm tra vai trò: Cấm tuyệt đối trên Receiver (Live Webhost)
        if ( ! self::is_sender() ) {
            return [
                'success' => false,
                'message' => __( 'Security Alert: Agent Harness can only be initialized on a Local Dev site (Sender mode). Live Webhost is protected.', 'skaaai' ),
            ];
        }

        // 2. Kiểm tra quyền quản trị viên
        if ( ! current_user_can( 'manage_options' ) ) {
            return [
                'success' => false,
                'message' => __( 'Permission denied. Administrator capabilities required.', 'skaaai' ),
            ];
        }

        // 3. Khởi tạo WordPress Filesystem API
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        if ( ! $wp_filesystem ) {
            return [
                'success' => false,
                'message' => __( 'Could not initialize WP_Filesystem API.', 'skaaai' ),
            ];
        }

        $base_dir   = self::get_target_base_dir();
        $source_dir = self::get_scaffold_source_dir();

        if ( ! is_dir( $source_dir ) ) {
            return [
                'success' => false,
                'message' => __( 'Scaffold source template directory not found in Skaaai plugin.', 'skaaai' ),
            ];
        }

        // 4. Tạo các thư mục thiết yếu tại ABSPATH
        $dirs_to_ensure = [
            $base_dir . '.agent',
            $base_dir . '.agent/rules',
            $base_dir . '.agent/workflows',
            $base_dir . '.agent/skills',
            $base_dir . '.agent/skills/skaaa-builder',
            $base_dir . '.agent/skills/skaaa-flat-db',
            $base_dir . '.agent/skills/skaaa-sync',
            $base_dir . '.agent/harness',
            $base_dir . '.skaaa-ai',
            $base_dir . '.skaaa-ai/1-overview',
            $base_dir . '.skaaa-ai/2-memory',
        ];

        foreach ( $dirs_to_ensure as $dir ) {
            if ( ! $wp_filesystem->is_dir( $dir ) ) {
                $created = $wp_filesystem->mkdir( $dir, FS_CHMOD_DIR );
                if ( ! $created && ! is_dir( $dir ) ) {
                    wp_mkdir_p( $dir ); // Fallback PHP native nếu FS_CHMOD_DIR kén
                }
            }
        }

        // 5. Sao chép đệ quy toàn bộ template từ plugin scaffold sang ABSPATH
        $copy_stats = self::copy_recursive( $source_dir, $base_dir, $overwrite_existing, $wp_filesystem );

        $status = self::check_status();

        return [
            'success' => true,
            'message' => sprintf(
                __( 'Local Agent Cockpit initialized successfully! Created %1$d directories, deployed %2$d template files.', 'skaaai' ),
                $copy_stats['dirs'],
                $copy_stats['files']
            ),
            'stats'   => $copy_stats,
            'status'  => $status,
        ];
    }

    /**
     * Sao chép đệ quy file và folder từ nguồn sang đích
     *
     * @param string $source Thư mục nguồn
     * @param string $destination Thư mục đích
     * @param bool   $overwrite Ghi đè file nếu đã có
     * @param object $wp_filesystem WordPress Filesystem object
     * @return array
     */
    private static function copy_recursive( string $source, string $destination, bool $overwrite, object $wp_filesystem ): array {
        $stats = [
            'dirs'  => 0,
            'files' => 0,
        ];

        $source      = rtrim( wp_normalize_path( $source ), '/' ) . '/';
        $destination = rtrim( wp_normalize_path( $destination ), '/' ) . '/';

        if ( ! is_dir( $source ) ) {
            return $stats;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $iterator as $item ) {
            $sub_path    = substr( wp_normalize_path( $item->getPathname() ), strlen( $source ) );
            $target_path = $destination . $sub_path;

            if ( $item->isDir() ) {
                if ( ! $wp_filesystem->is_dir( $target_path ) ) {
                    if ( $wp_filesystem->mkdir( $target_path, FS_CHMOD_DIR ) || is_dir( $target_path ) ) {
                        $stats['dirs']++;
                    }
                }
            } else {
                $target_dir = dirname( $target_path );
                if ( ! $wp_filesystem->is_dir( $target_dir ) ) {
                    $wp_filesystem->mkdir( $target_dir, FS_CHMOD_DIR );
                }

                if ( ! $wp_filesystem->exists( $target_path ) || $overwrite ) {
                    $contents = file_get_contents( $item->getPathname() );
                    if ( false !== $contents && $wp_filesystem->put_contents( $target_path, $contents, FS_CHMOD_FILE ) ) {
                        $stats['files']++;
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Đếm tổng số file đệ quy trong thư mục
     *
     * @param string $dir Thư mục cần đếm
     * @return int
     */
    private static function count_files_recursive( string $dir ): int {
        if ( ! is_dir( $dir ) ) {
            return 0;
        }
        $count = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ( $iterator as $file ) {
                if ( $file->isFile() ) {
                    $count++;
                }
            }
        } catch ( \Exception $e ) {
            return 0;
        }
        return $count;
    }

    /**
     * Render tab nội dung Buồng lái Agent Scaffolding & Memory trong Admin Skaaai
     */
    public static function render_admin_tab(): void {
        $status = self::check_status();
        ?>
        <section id="tab-agent-cockpit" class="skaaai-tab-pane">
            <div class="skaaai-card">
                <div class="skaaai-card-header-flex" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
                    <div>
                        <h3 style="margin-top:0;"><?php esc_html_e( 'Local Agent Cockpit & Memory Scaffolding', 'skaaai' ); ?></h3>
                        <p class="description" style="max-width:700px;">
                            <?php esc_html_e( 'Deploy local rules, workflows, and memory folders (.agent/ and .skaaa-ai/) into your website root (app/public/). This gives AI Agents (Antigravity/Cursor) full context and toolsets to operate directly on this site.', 'skaaai' ); ?>
                        </p>
                    </div>
                    <div id="harness-status-pill">
                        <?php if ( $status['is_initialized'] ) : ?>
                            <span class="skaaai-status-pill pill-green">● <?php esc_html_e( 'Ready for AI', 'skaaai' ); ?></span>
                        <?php else : ?>
                            <span class="skaaai-status-pill pill-amber">○ <?php esc_html_e( 'Not Initialized', 'skaaai' ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="skaaai-harness-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                    <div class="skaaai-harness-box" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
                        <h4 style="margin:0 0 10px 0;display:flex;align-items:center;gap:6px;">
                            <span class="dashicons dashicons-admin-generic" style="color:#0284c7;"></span> <?php esc_html_e( 'Agent Scaffolding (.agent/)', 'skaaai' ); ?>
                        </h4>
                        <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Contains rules, workflows, and local execution tools for AI.', 'skaaai' ); ?></p>
                        <ul class="skaaai-path-list" style="margin:0;padding-left:18px;font-size:13px;line-height:1.8;">
                            <li><code>.agent/rules/skaaa-blocks.md</code></li>
                            <li><code>.agent/skills/skaaa-builder/SKILL.md</code></li>
                            <li><code>.agent/skills/skaaa-flat-db/SKILL.md</code></li>
                            <li><code>.agent/skills/skaaa-sync/SKILL.md</code></li>
                            <li><code>.agent/workflows/push_to_live.md</code></li>
                            <li><code>.agent/harness/README.md</code></li>
                        </ul>
                    </div>
                    <div class="skaaai-harness-box" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
                        <h4 style="margin:0 0 10px 0;display:flex;align-items:center;gap:6px;">
                            <span class="dashicons dashicons-book" style="color:#059669;"></span> <?php esc_html_e( 'Site Memory, Map & Design (.skaaa-ai/)', 'skaaai' ); ?>
                        </h4>
                        <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Stores brand identity, site map, schema overview, and task checkpoints.', 'skaaai' ); ?></p>
                        <ul class="skaaai-path-list" style="margin:0;padding-left:18px;font-size:13px;line-height:1.8;">
                            <li><code>.skaaa-ai/1-overview/design.md</code> (Brand & Design Tokens)</li>
                            <li><code>.skaaa-ai/1-overview/site_map.md</code></li>
                            <li><code>.skaaa-ai/2-memory/checkpoint.md</code></li>
                            <li><code>.skaaa-ai/2-memory/decision-log.md</code></li>
                        </ul>
                    </div>
                </div>

                <div class="skaaai-harness-actions" style="border-top:1px solid #e2e8f0;padding-top:20px;">
                    <div class="skaaai-checkbox-wrap" style="margin-bottom: 14px;">
                        <label class="skaaai-checkbox-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="skaaai-harness-overwrite" value="1">
                            <span><?php esc_html_e( 'Overwrite existing template files if already present', 'skaaai' ); ?></span>
                        </label>
                    </div>

                    <div class="skaaai-btn-group" style="display:flex;align-items:center;gap:12px;">
                        <button type="button" id="btn-init-agent-harness" class="button button-primary button-hero">
                            <span class="dashicons dashicons-superhero" style="font-size:18px;vertical-align:middle;margin-top:-2px;margin-right:4px;"></span>
                            <span class="btn-text">
                                <?php echo $status['is_initialized'] ? esc_html__( 'Re-sync Agent Harness & Memory', 'skaaai' ) : esc_html__( 'Initialize Agent Harness & Memory', 'skaaai' ); ?>
                            </span>
                        </button>
                        <span class="spinner" id="harness-spinner" style="float:none;margin:0;"></span>
                    </div>
                    <div id="harness-notice-container" class="skaaai-notice-box" style="display:none;margin-top:14px;"></div>
                </div>
            </div>
        </section>
        <?php
    }
}
