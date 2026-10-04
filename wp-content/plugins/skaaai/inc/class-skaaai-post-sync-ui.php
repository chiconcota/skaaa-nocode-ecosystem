<?php
/**
 * Lớp Quản Lý Giao Diện Đồng Bộ Bài Viết (Skaaai_Post_Sync_UI)
 *
 * Chịu trách nhiệm:
 * 1. Thêm cột trạng thái "Skaaa Sync" trên danh sách bài viết (edit.php).
 * 2. Cung cấp nút bấm Push to Live tức thì qua AJAX cho từng dòng.
 * 3. Hỗ trợ thao tác đẩy hàng loạt (Bulk Push to Live).
 * 4. Tích hợp nút bấm 1-Click "Push to Live" trên thanh công cụ Gutenberg Editor.
 *
 * @package Skaaai
 * @version 1.3.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Post_Sync_UI {

    /**
     * Khởi tạo các hook
     */
    public static function init(): void {
        $role = Core::get_setting( 'skaaai_role', 'receiver' );

        // Các tính năng UI đẩy bài chỉ kích hoạt trên website đóng vai trò Sender (Localhost)
        if ( 'sender' !== $role ) {
            return;
        }

        // Cột trạng thái đồng bộ trên danh sách bài viết / trang
        add_filter( 'manage_posts_columns', [ self::class, 'add_sync_column' ] );
        add_action( 'manage_posts_custom_column', [ self::class, 'render_sync_column' ], 10, 2 );
        add_filter( 'manage_pages_columns', [ self::class, 'add_sync_column' ] );
        add_action( 'manage_pages_custom_column', [ self::class, 'render_sync_column' ], 10, 2 );

        // Đăng ký cho các Custom Post Types công khai
        $post_types = get_post_types( [ 'public' => true, '_builtin' => false ] );
        foreach ( $post_types as $pt ) {
            add_filter( "manage_{$pt}_posts_columns", [ self::class, 'add_sync_column' ] );
            add_action( "manage_{$pt}_posts_custom_column", [ self::class, 'render_sync_column' ], 10, 2 );
        }

        // Thao tác hàng loạt (Bulk Actions)
        add_filter( 'bulk_actions-edit-post', [ self::class, 'register_bulk_actions' ] );
        add_filter( 'bulk_actions-edit-page', [ self::class, 'register_bulk_actions' ] );
        add_filter( 'handle_bulk_actions-edit-post', [ self::class, 'handle_bulk_actions' ], 10, 3 );
        add_filter( 'handle_bulk_actions-edit-page', [ self::class, 'handle_bulk_actions' ], 10, 3 );
        add_action( 'admin_notices', [ self::class, 'render_bulk_action_notice' ] );

        // Tài nguyên JavaScript & CSS cho trang danh sách (edit.php)
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_post_list_assets' ] );

        // Tài nguyên cho Gutenberg Block Editor
        add_action( 'enqueue_block_editor_assets', [ self::class, 'enqueue_block_editor_assets' ] );

        // AJAX handler đẩy bài viết
        add_action( 'wp_ajax_skaaai_push_post', [ self::class, 'ajax_push_post' ] );

        // AJAX handler kéo bài viết từ Live
        add_action( 'wp_ajax_skaaai_pull_post', [ self::class, 'ajax_pull_post' ] );

        // AJAX handler đối soát trạng thái bài viết hàng loạt
        add_action( 'wp_ajax_skaaai_check_remote_status', [ self::class, 'ajax_check_remote_status' ] );

        // AJAX handler lấy báo cáo Diff đơn bài trước khi kéo
        add_action( 'wp_ajax_skaaai_get_post_diff', [ self::class, 'ajax_get_post_diff' ] );

        // Hộp thoại đối soát Diff đơn bài trên trang danh sách edit.php
        add_action( 'admin_footer-edit.php', [ self::class, 'render_post_diff_modal' ] );
    }

    /**
     * Thêm cột "Skaaa Sync" vào danh sách bài viết
     *
     * @param array $columns
     * @return array
     */
    public static function add_sync_column( array $columns ): array {
        $new_columns = [];
        foreach ( $columns as $key => $title ) {
            $new_columns[ $key ] = $title;
            if ( 'title' === $key ) {
                $new_columns['skaaa_sync'] = __( 'Skaaa Sync', 'skaaai' );
            }
        }

        if ( ! isset( $new_columns['skaaa_sync'] ) ) {
            $new_columns['skaaa_sync'] = __( 'Skaaa Sync', 'skaaai' );
        }

        return $new_columns;
    }

    /**
     * Render nội dung cột "Skaaa Sync" cho từng dòng bài viết
     *
     * @param string $column  Tên cột
     * @param int    $post_id ID bài viết
     * @return void
     */
    public static function render_sync_column( string $column, int $post_id ): void {
        if ( 'skaaa_sync' !== $column ) {
            return;
        }

        $sync_data = Sync_Post::get_sync_status( $post_id );
        $status    = $sync_data['status'];
        $icon      = $sync_data['badge_icon'];
        $label     = $sync_data['label'];
        $permalink = $sync_data['remote_permalink'];
        $last_sync = $sync_data['last_synced'];

        $tooltip = $last_sync
            ? sprintf( __( 'Last synced: %s', 'skaaai' ), $last_sync )
            : __( 'Never synchronized to Live webhost', 'skaaai' );

        ?>
        <div class="skaaai-sync-cell" data-post-id="<?php echo esc_attr( $post_id ); ?>">
            <span class="skaaai-sync-badge skaaai-badge-<?php echo esc_attr( $status ); ?>" title="<?php echo esc_attr( $tooltip ); ?>">
                <?php echo esc_html( $icon . ' ' . $label ); ?>
            </span>
            <div class="skaaai-sync-actions">
                <button type="button" class="button button-small skaaai-push-row-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php esc_attr_e( 'Push to Live Webhost', 'skaaai' ); ?>">
                    <span class="dashicons dashicons-cloud-upload"></span>
                    <span class="skaaai-btn-text"><?php esc_html_e( 'Push', 'skaaai' ); ?></span>
                </button>
                <button type="button" class="button button-small skaaai-pull-row-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php esc_attr_e( 'Pull from Live Webhost', 'skaaai' ); ?>">
                    <span class="dashicons dashicons-cloud-download"></span>
                    <span class="skaaai-btn-text"><?php esc_html_e( 'Pull', 'skaaai' ); ?></span>
                </button>
                <?php if ( ! empty( $permalink ) ) : ?>
                    <a href="<?php echo esc_url( $permalink ); ?>" target="_blank" rel="noopener noreferrer" class="skaaai-view-live-link" title="<?php esc_attr_e( 'View published page on Live host', 'skaaai' ); ?>">
                        <span class="dashicons dashicons-external"></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Đăng ký thao tác hàng loạt
     *
     * @param array $bulk_actions
     * @return array
     */
    public static function register_bulk_actions( array $bulk_actions ): array {
        $bulk_actions['skaaai_bulk_push'] = __( '🚀 Push to Live (Skaaa)', 'skaaai' );
        $bulk_actions['skaaai_bulk_pull'] = __( '📥 Pull from Live (Skaaa)', 'skaaai' );
        return $bulk_actions;
    }

    /**
     * Xử lý thực thi thao tác hàng loạt
     *
     * @param string $redirect_to
     * @param string $action
     * @param array  $post_ids
     * @return string
     */
    public static function handle_bulk_actions( string $redirect_to, string $action, array $post_ids ): string {
        if ( 'skaaai_bulk_push' === $action ) {
            $success_count = 0;
            $fail_count    = 0;

            foreach ( $post_ids as $post_id ) {
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    $fail_count++;
                    continue;
                }

                $res = Sync_Post::push_post_to_remote( (int) $post_id );
                if ( ! empty( $res['success'] ) ) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            }

            return add_query_arg( [
                'skaaai_bulk_pushed' => $success_count,
                'skaaai_bulk_failed' => $fail_count,
            ], $redirect_to );
        }

        if ( 'skaaai_bulk_pull' === $action ) {
            $success_count = 0;
            $fail_count    = 0;

            foreach ( $post_ids as $post_id ) {
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    $fail_count++;
                    continue;
                }

                $res = Sync_Pull::pull_post_from_remote( (int) $post_id );
                if ( ! empty( $res['success'] ) ) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            }

            return add_query_arg( [
                'skaaai_bulk_pulled' => $success_count,
                'skaaai_bulk_failed' => $fail_count,
            ], $redirect_to );
        }

        return $redirect_to;
    }

    /**
     * Hiển thị thông báo kết quả sau khi chạy thao tác hàng loạt
     *
     * @return void
     */
    public static function render_bulk_action_notice(): void {
        if ( ! isset( $_GET['skaaai_bulk_pushed'] ) ) {
            return;
        }

        $pushed = (int) $_GET['skaaai_bulk_pushed'];
        $failed = (int) ( $_GET['skaaai_bulk_failed'] ?? 0 );

        if ( $pushed > 0 ) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html( sprintf( _n( '%d post pushed to Live webhost successfully.', '%d posts pushed to Live webhost successfully.', $pushed, 'skaaai' ), $pushed ) );
            if ( $failed > 0 ) {
                echo ' ' . esc_html( sprintf( _n( '(%d post failed)', '(%d posts failed)', $failed, 'skaaai' ), $failed ) );
            }
            echo '</p></div>';
        } elseif ( $failed > 0 ) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo esc_html( sprintf( _n( '%d post failed to push to Live webhost.', '%d posts failed to push to Live webhost.', $failed, 'skaaai' ), $failed ) );
            echo '</p></div>';
        }

        if ( isset( $_GET['skaaai_bulk_pulled'] ) ) {
            $pulled        = (int) $_GET['skaaai_bulk_pulled'];
            $pull_failed   = (int) ( $_GET['skaaai_bulk_failed'] ?? 0 );

            if ( $pulled > 0 ) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo esc_html( sprintf( _n( '%d post pulled from Live webhost successfully.', '%d posts pulled from Live webhost successfully.', $pulled, 'skaaai' ), $pulled ) );
                if ( $pull_failed > 0 ) {
                    echo ' ' . esc_html( sprintf( _n( '(%d post failed)', '(%d posts failed)', $pull_failed, 'skaaai' ), $pull_failed ) );
                }
                echo '</p></div>';
            } elseif ( $pull_failed > 0 ) {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo esc_html( sprintf( _n( '%d post failed to pull from Live webhost.', '%d posts failed to pull from Live webhost.', $pull_failed, 'skaaai' ), $pull_failed ) );
                echo '</p></div>';
            }
        }
    }

    /**
     * Nạp assets cho trang danh sách bài viết (edit.php)
     *
     * @param string $hook
     * @return void
     */
    public static function enqueue_post_list_assets( string $hook ): void {
        if ( 'edit.php' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'skaaai-post-list-css',
            SKAAAI_URL . 'assets/css/skaaai-editor-toolbar.css',
            [],
            SKAAAI_VERSION
        );

        wp_enqueue_script(
            'skaaai-post-list-js',
            SKAAAI_URL . 'assets/js/skaaai-post-list.js',
            [ 'jquery' ],
            SKAAAI_VERSION,
            true
        );

        wp_localize_script( 'skaaai-post-list-js', 'skaaaiPostList', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'skaaai_post_sync_nonce' ),
            'i18n'     => [
                'pushing'       => __( 'Pushing...', 'skaaai' ),
                'pushed'        => __( 'Synced', 'skaaai' ),
                'push_failed'   => __( 'Push Failed', 'skaaai' ),
                'pulling'       => __( 'Pulling...', 'skaaai' ),
                'pulled'        => __( 'Synced', 'skaaai' ),
                'pull_failed'   => __( 'Pull Failed', 'skaaai' ),
                'confirm_force' => __( 'A newer revision exists on the Live webhost. Do you want to force overwrite?', 'skaaai' ),
                'confirm_pull'  => __( 'Pulling will overwrite local content with the latest version from Live. A revision backup will be created. Continue?', 'skaaai' ),
                'remote_ahead'  => __( 'Remote Ahead', 'skaaai' ),
                'check_remote'  => __( 'Check Remote', 'skaaai' ),
                'diff_loading'  => __( 'Fetching comparison data from Live webhost...', 'skaaai' ),
                'diff_error'    => __( 'Failed to fetch post comparison details from Live.', 'skaaai' ),
                'approve_pull'  => __( 'Approve & Pull', 'skaaai' ),
                'cancel'        => __( 'Cancel', 'skaaai' ),
            ],
        ] );
    }

    /**
     * Nạp assets cho Gutenberg Block Editor
     *
     * @return void
     */
    public static function enqueue_block_editor_assets(): void {
        global $post;
        $post_id = $post ? $post->ID : 0;
        if ( ! $post_id ) {
            return;
        }

        $remote_url   = Core::get_setting( 'skaaai_remote_url', '' );
        $remote_token = Core::get_setting( 'skaaai_remote_token', '' );
        $is_paired    = ! empty( $remote_url ) && ! empty( $remote_token );
        $sync_data    = Sync_Post::get_sync_status( $post_id );

        wp_enqueue_style(
            'skaaai-editor-toolbar-css',
            SKAAAI_URL . 'assets/css/skaaai-editor-toolbar.css',
            [],
            SKAAAI_VERSION
        );

        wp_enqueue_script(
            'skaaai-editor-toolbar-js',
            SKAAAI_URL . 'assets/js/skaaai-editor-toolbar.js',
            [ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-notices', 'wp-i18n' ],
            SKAAAI_VERSION,
            true
        );

        wp_localize_script( 'skaaai-editor-toolbar-js', 'skaaaiEditorSync', [
            'ajax_url'         => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'skaaai_post_sync_nonce' ),
            'post_id'          => $post_id,
            'role'             => 'sender',
            'is_paired'        => $is_paired,
            'remote_url'       => $remote_url,
            'sync_status'      => $sync_data['status'],
            'sync_label'       => $sync_data['label'],
            'badge_icon'       => $sync_data['badge_icon'],
            'last_synced'      => $sync_data['last_synced'],
            'remote_permalink' => $sync_data['remote_permalink'],
            'i18n'             => [
                'push_to_live'        => __( 'Push to Live', 'skaaai' ),
                'pushing'             => __( 'Pushing to Live...', 'skaaai' ),
                'push_success'        => __( 'Post pushed to Live webhost successfully!', 'skaaai' ),
                'pull_from_live'      => __( 'Pull from Live', 'skaaai' ),
                'pulling_from_live'   => __( 'Pulling from Live...', 'skaaai' ),
                'pull_success'        => __( 'Post pulled from Live webhost successfully!', 'skaaai' ),
                'confirm_pull_editor' => __( 'Pulling will overwrite your current local editor content with the latest version from Live. A revision backup will be created. Are you sure you want to proceed?', 'skaaai' ),
                'view_live'           => __( 'View on Live', 'skaaai' ),
                'conflict_detected'   => __( 'Conflict Detected: The live website has a newer revision of this post.', 'skaaai' ),
                'force_push'          => __( 'Force Overwrite Live', 'skaaai' ),
                'not_paired_warning'  => __( 'Skaaa Bridge is not paired. Please connect your site in Skaaa Bridge settings first.', 'skaaai' ),
                'saving_post_first'   => __( 'Saving post changes before pushing...', 'skaaai' ),
                'skaaa_sync'          => __( 'Skaaa Sync', 'skaaai' ),
                'last_synced_label'   => __( 'Last Synced:', 'skaaai' ),
                'never'               => __( 'Never', 'skaaai' ),
                're_sync'             => __( 'Re-sync to Live', 'skaaai' ),
                'settings_link'       => admin_url( 'admin.php?page=skaaai-settings' ),
            ],
        ] );
    }

    /**
     * AJAX Xử lý đẩy bài viết lên Live Webhost
     *
     * @return void
     */
    public static function ajax_push_post(): void {
        check_ajax_referer( 'skaaai_post_sync_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        $force   = ! empty( $_POST['force'] );

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied or invalid post ID.', 'skaaai' ) ] );
        }

        $result = Sync_Post::push_post_to_remote( $post_id, $force );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } elseif ( ! empty( $result['conflict'] ) ) {
            wp_send_json_error( $result, 409 );
        } else {
            wp_send_json_error( $result, 400 );
        }
    }

    /**
     * AJAX Xử lý kéo bài viết từ Live Webhost về Localhost
     *
     * @return void
     */
    public static function ajax_pull_post(): void {
        check_ajax_referer( 'skaaai_post_sync_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied or invalid post ID.', 'skaaai' ) ] );
        }

        $result = Sync_Pull::pull_post_from_remote( $post_id );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result, 400 );
        }
    }

    /**
     * AJAX Xử lý đối soát trạng thái bài viết hàng loạt với Live
     *
     * @return void
     */
    public static function ajax_check_remote_status(): void {
        check_ajax_referer( 'skaaai_post_sync_nonce', 'nonce' );

        $post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', (array) $_POST['post_ids'] ) : [];
        if ( empty( $post_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'No post IDs provided.', 'skaaai' ) ] );
        }

        $result = Sync_Pull::check_remote_status_batch( $post_ids );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result, 400 );
        }
    }

    /**
     * AJAX Lấy thông tin so sánh Diff trước khi kéo bài viết về
     *
     * @return void
     */
    public static function ajax_get_post_diff(): void {
        check_ajax_referer( 'skaaai_post_sync_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied or invalid post ID.', 'skaaai' ) ] );
        }

        $result = Sync_Pull::get_post_diff( $post_id );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result['diff'] );
        } else {
            wp_send_json_error( $result, 400 );
        }
    }

    /**
     * Render Hộp thoại xem trước đối soát Diff cho bài viết đơn lẻ
     */
    public static function render_post_diff_modal(): void {
        ?>
        <div id="skaaai-post-diff-modal" class="skaaai-modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="skaaai-post-diff-title">
            <div class="skaaai-modal-dialog" style="max-width: 680px;">
                <div class="skaaai-modal-header">
                    <h3 id="skaaai-post-diff-title">
                        <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Review Remote Changes (Diff Preview)', 'skaaai' ); ?>
                    </h3>
                    <button type="button" class="skaaai-modal-close" id="btn-post-diff-close" aria-label="<?php esc_attr_e( 'Close', 'skaaai' ); ?>">&times;</button>
                </div>
                <div class="skaaai-modal-body" id="skaaai-post-diff-body" style="padding: 20px;">
                    <!-- Populated dynamically via JS -->
                </div>
                <div class="skaaai-modal-footer">
                    <button type="button" class="button button-secondary" id="btn-post-diff-cancel">
                        <?php esc_html_e( 'Cancel', 'skaaai' ); ?>
                    </button>
                    <button type="button" class="button button-primary button-hero skaaai-btn-pull-gradient" id="btn-post-diff-approve">
                        <span class="dashicons dashicons-cloud-download"></span> <?php esc_html_e( 'Approve & Overwrite Local', 'skaaai' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}

