<?php
/**
 * Lớp Đóng Gói & Xuất Bản Hệ Sinh Thái Từ Localhost (Skaaai_Sync_Ecosystem_Sender)
 *
 * Chịu trách nhiệm trích xuất, đóng gói, tải trước media nhúng và đẩy toàn bộ
 * payload Hệ sinh thái sang Live Webhost an toàn qua REST API.
 *
 * @package Skaaai
 * @version 1.4.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Ecosystem_Sender {

    /**
     * Trích xuất và đóng gói toàn bộ Hệ Sinh Thái từ máy nguồn (Localhost)
     *
     * @param array $options Cấu hình trích xuất (dry_run, scopes)
     * @return array
     */
    public static function export_payload( array $options = [] ): array {
        global $wpdb;

        $scopes = $options['scopes'] ?? [ 'presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'settings' ];

        $payload = [
            'origin_url'   => site_url(),
            'table_prefix' => $wpdb->prefix,
            'timestamp'    => time(),
            'version'      => defined( 'SKAAAI_VERSION' ) ? SKAAAI_VERSION : '1.5.0',
            'dry_run'      => ! empty( $options['dry_run'] ),
            'scopes'       => $scopes,
        ];

        // 1. Design Tokens (wp_skaaa_data_sys_presets)
        if ( in_array( 'presets', $scopes, true ) ) {
            $presets_table = $wpdb->prefix . 'skaaa_data_sys_presets';
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$presets_table}'" ) === $presets_table ) {
                $payload['presets'] = $wpdb->get_results(
                    "SELECT `type`, `name`, `value` FROM `{$presets_table}` ORDER BY `id` ASC",
                    ARRAY_A
                ) ?: [];
            } else {
                $payload['presets'] = [];
            }
        }

        // 2. Organisms (wp_skaaa_data_sys_organisms)
        if ( in_array( 'organisms', $scopes, true ) ) {
            $organisms_table = $wpdb->prefix . 'skaaa_data_sys_organisms';
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$organisms_table}'" ) === $organisms_table ) {
                $payload['organisms'] = $wpdb->get_results(
                    "SELECT `id`, `name`, `type`, `category`, `title`, `block_name`, `json_content`, `html_content` FROM `{$organisms_table}` ORDER BY `id` ASC",
                    ARRAY_A
                ) ?: [];
            } else {
                $payload['organisms'] = [];
            }
        }

        // 3. Theme Templates (wp_skaaa_data_sys_theme_templates)
        if ( in_array( 'theme_templates', $scopes, true ) ) {
            $tpl_table = $wpdb->prefix . 'skaaa_data_sys_theme_templates';
            $org_table = $wpdb->prefix . 'skaaa_data_sys_organisms';
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tpl_table}'" ) === $tpl_table ) {
                $templates = $wpdb->get_results(
                    "SELECT `id`, `name`, `location`, `organism_id`, `conditions`, `is_active` FROM `{$tpl_table}` ORDER BY `id` ASC",
                    ARRAY_A
                ) ?: [];

                // Đính kèm organism_name để bên nhận ánh xạ ID chính xác
                foreach ( $templates as &$tpl ) {
                    $org_id = (int) ( $tpl['organism_id'] ?? 0 );
                    $tpl['organism_name'] = '';
                    if ( $org_id > 0 && ! empty( $payload['organisms'] ) ) {
                        foreach ( $payload['organisms'] as $org ) {
                            if ( (int) $org['id'] === $org_id ) {
                                $tpl['organism_name'] = $org['name'];
                                break;
                            }
                        }
                    }
                    if ( empty( $tpl['organism_name'] ) && $org_id > 0 ) {
                        $tpl['organism_name'] = $wpdb->get_var( $wpdb->prepare( "SELECT `name` FROM `{$org_table}` WHERE `id` = %d", $org_id ) ) ?: '';
                    }
                }
                unset( $tpl );
                $payload['theme_templates'] = $templates;
            } else {
                $payload['theme_templates'] = [];
            }
        }

        // 4. Skaaa Logic Workflows (wp_skaaa_data_sys_workflows)
        if ( in_array( 'workflows', $scopes, true ) ) {
            $workflows_table = $wpdb->prefix . 'skaaa_data_sys_workflows';
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$workflows_table}'" ) === $workflows_table ) {
                $payload['workflows'] = $wpdb->get_results(
                    "SELECT `workflow_id`, `name`, `app_id`, `status`, `node_count`, `graph` FROM `{$workflows_table}` ORDER BY `id` ASC",
                    ARRAY_A
                ) ?: [];
            } else {
                $payload['workflows'] = [];
            }
        }

        // 5. CSDL Bảng Phẳng Ứng Dụng (Custom Flat Tables)
        if ( in_array( 'custom_tables', $scopes, true ) ) {
            $payload['custom_tables'] = self::export_custom_flat_tables();
        }

        // 6. Toàn Bộ Các Trang Đang Publish (Pages)
        if ( in_array( 'pages', $scopes, true ) ) {
            $payload['pages'] = self::export_published_pages();
        }

        // 7. Toàn Bộ Các Bài Viết Đang Publish (Blog Posts)
        if ( in_array( 'posts', $scopes, true ) || in_array( 'pages', $scopes, true ) ) {
            $payload['posts'] = self::export_published_posts();
        }

        // 7. Cấu Hình & Thiết Lập Hệ Thống (Site Setup)
        if ( in_array( 'settings', $scopes, true ) ) {
            $front_page_id = (int) get_option( 'page_on_front' );
            $front_uuid    = $front_page_id ? get_post_meta( $front_page_id, '_skaaa_uuid', true ) : '';
            $front_slug    = $front_page_id ? get_post_field( 'post_name', $front_page_id ) : '';

            $payload['settings'] = [
                'general' => [
                    'blogname'        => get_option( 'blogname' ),
                    'blogdescription' => get_option( 'blogdescription' ),
                    'timezone_string' => get_option( 'timezone_string' ),
                    'gmt_offset'      => get_option( 'gmt_offset' ),
                    'date_format'     => get_option( 'date_format' ),
                    'time_format'     => get_option( 'time_format' ),
                    'start_of_week'   => get_option( 'start_of_week' ),
                ],
                'reading' => [
                    'show_on_front'      => get_option( 'show_on_front', 'posts' ),
                    'page_on_front_uuid' => $front_uuid,
                    'page_on_front_slug' => $front_slug,
                ],
                'permalink' => [
                    'permalink_structure' => get_option( 'permalink_structure' ),
                ],
            ];
        }

        return $payload;
    }

    /**
     * Trích xuất các bảng phẳng ứng dụng (loại trừ sys_* và *_submissions)
     *
     * @return array
     */
    private static function export_custom_flat_tables(): array {
        global $wpdb;

        $prefix       = $wpdb->prefix . 'skaaa_data_';
        $escaped_like = $wpdb->esc_like( $prefix ) . '%';
        $tables       = $wpdb->get_col( $wpdb->prepare( "SHOW TABLES LIKE %s", $escaped_like ) ) ?: [];

        $custom_tables = [];

        foreach ( $tables as $table ) {
            $suffix = substr( $table, strlen( $prefix ) );

            // Bỏ qua các bảng hệ thống sys_*
            if ( str_starts_with( $suffix, 'sys_' ) ) {
                continue;
            }

            // Bỏ qua các bảng chứa dữ liệu form submissions của khách (Safeguard Layer 3)
            if ( str_ends_with( $suffix, '_submissions' ) || str_contains( $suffix, 'submission' ) ) {
                continue;
            }

            $create_row = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
            $create_sql = $create_row[1] ?? '';
            $normalized_create = str_replace( "`{$table}`", "`{{TABLE_NAME}}`", $create_sql );

            $rows = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A ) ?: [];

            $custom_tables[ $suffix ] = [
                'table_suffix' => $suffix,
                'create_sql'   => $normalized_create,
                'rows'         => $rows,
                'row_count'    => count( $rows ),
            ];
        }

        return $custom_tables;
    }

    /**
     * Trích xuất danh sách tất cả các trang WordPress đang xuất bản
     *
     * @return array
     */
    private static function export_published_pages(): array {
        $posts = get_posts( [
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ] );

        $pages = [];
        $front_page_id = (int) get_option( 'page_on_front' );

        foreach ( $posts as $post ) {
            $uuid = get_post_meta( $post->ID, '_skaaa_uuid', true );
            if ( empty( $uuid ) ) {
                $uuid = wp_generate_uuid4();
                update_post_meta( $post->ID, '_skaaa_uuid', $uuid );
            }

            $pages[] = [
                'id'            => $post->ID,
                'uuid'          => $uuid,
                'title'         => $post->post_title,
                'slug'          => $post->post_name,
                'content'       => $post->post_content,
                'post_type'     => $post->post_type,
                'post_status'   => $post->post_status,
                'menu_order'    => $post->menu_order,
                'is_front_page' => ( $post->ID === $front_page_id ),
                'last_modified' => get_post_modified_time( 'U', true, $post->ID ),
                'modified_gmt'  => $post->post_modified_gmt,
                'content_hash'  => md5( ( $post->post_title ?? '' ) . '|' . ( $post->post_content ?? '' ) ),
            ];
        }

        return $pages;
    }

    /**
     * Trích xuất danh sách tất cả các bài viết blog (Posts) đang xuất bản
     *
     * @return array
     */
    private static function export_published_posts(): array {
        $posts = get_posts( [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        $items = [];
        foreach ( $posts as $post ) {
            $uuid = get_post_meta( $post->ID, '_skaaa_uuid', true );
            if ( empty( $uuid ) ) {
                $uuid = wp_generate_uuid4();
                update_post_meta( $post->ID, '_skaaa_uuid', $uuid );
            }

            $items[] = [
                'id'            => $post->ID,
                'uuid'          => $uuid,
                'title'         => $post->post_title,
                'slug'          => $post->post_name,
                'content'       => $post->post_content,
                'post_type'     => $post->post_type,
                'post_status'   => $post->post_status,
                'menu_order'    => $post->menu_order,
                'is_front_page' => false,
                'last_modified' => get_post_modified_time( 'U', true, $post->ID ),
                'modified_gmt'  => $post->post_modified_gmt,
                'content_hash'  => md5( ( $post->post_title ?? '' ) . '|' . ( $post->post_content ?? '' ) ),
            ];
        }

        return $items;
    }

    /**
     * Đẩy toàn bộ Hệ Sinh Thái từ máy Localhost sang Live Webhost
     *
     * @param bool  $dry_run Chế độ kiểm tra đối soát, không ghi CSDL
     * @param array $scopes  Các phân vùng muốn đồng bộ
     * @return array
     */
    public static function push_to_remote( bool $dry_run = false, array $scopes = [] ): array {
        $remote_url   = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $remote_token = sanitize_text_field( Core::get_setting( 'skaaai_remote_token', '' ) );

        if ( empty( $remote_url ) || empty( $remote_token ) ) {
            return [
                'success' => false,
                'message' => __( 'Remote website is not paired. Please configure pairing in Skaaa Bridge settings.', 'skaaai' ),
            ];
        }

        // 1. Thu thập payload
        $payload = self::export_payload( [
            'dry_run' => $dry_run,
            'scopes'  => $scopes,
        ] );

        // 2. Nếu ở chế độ Thực thi (không phải dry-run), chủ động đẩy file ảnh nhúng
        if ( ! $dry_run && ! empty( $payload['pages'] ) ) {
            foreach ( $payload['pages'] as &$page ) {
                $page['content'] = Sync_Post::prepare_and_sync_local_media( $page['content'], $remote_url, $remote_token );
            }
            unset( $page );

            if ( ! empty( $payload['organisms'] ) ) {
                foreach ( $payload['organisms'] as &$org ) {
                    if ( ! empty( $org['html_content'] ) ) {
                        $org['html_content'] = Sync_Post::prepare_and_sync_local_media( $org['html_content'], $remote_url, $remote_token );
                    }
                }
                unset( $org );
            }
        }

        // 3. Gửi payload sang Live Webhost
        $endpoint = rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/sync-ecosystem';
        $response = wp_remote_post( $endpoint, [
            'timeout'   => 120,
            'headers'   => [
                'X-Skaaai-Token' => $remote_token,
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
            ],
            'body'      => wp_json_encode( $payload ),
            'sslverify' => false,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => sprintf( __( 'Network error communicating with Live Webhost: %s', 'skaaai' ), $response->get_error_message() ),
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );
        $data        = json_decode( $body, true );

        if ( 200 !== $status_code || empty( $data['success'] ) ) {
            $err_msg = $data['message'] ?? sprintf( __( 'Remote error (HTTP %d).', 'skaaai' ), $status_code );
            return [
                'success' => false,
                'message' => $err_msg,
                'data'    => $data,
            ];
        }

        return $data;
    }
}
