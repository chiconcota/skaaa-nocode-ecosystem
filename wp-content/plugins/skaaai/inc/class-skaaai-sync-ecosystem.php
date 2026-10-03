<?php
/**
 * Lớp Động Cơ Tiếp Nhận & Đồng Bộ Toàn Bộ Hệ Sinh Thái (Skaaai_Sync_Ecosystem)
 *
 * Chịu trách nhiệm tiếp nhận, đối soát (Dry-run), và ghi nhận nguyên tử
 * toàn bộ Hệ Sinh Thái Skaaa (Design Tokens, Organisms, Theme Templates,
 * Logic Workflows, CSDL Bảng Phẳng Ứng Dụng, All Pages & Full Site Setup).
 *
 * Tích hợp Thiết Quân Luật Bảo Mật 4 Lớp:
 * - Lớp 1: Blacklist Tên Miền (Không ghi đè siteurl, home).
 * - Lớp 2: Blacklist Quản Trị & Plugin (Không ghi đè admin_email, active_plugins).
 * - Lớp 3: Cách Ly Người Dùng & Dữ Liệu Form (Không chạm wp_users và các bảng *_submissions).
 * - Lớp 4: Auto Invalidate Cache (Tự động xóa LiteSpeed Cache & Object Cache).
 *
 * @package Skaaai
 * @version 1.4.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Ecosystem {

    /**
     * Danh sách tùy chọn WordPress cốt lõi bị cấm ghi đè (Blacklist Options)
     */
    const BLACKLISTED_OPTIONS = [
        'siteurl',
        'home',
        'admin_email',
        'new_admin_email',
        'active_plugins',
        'users_can_register',
        'mailserver_url',
        'mailserver_login',
        'mailserver_pass',
    ];

    /**
     * Ủy quyền trích xuất payload sang Sync_Ecosystem_Sender
     *
     * @param array $options
     * @return array
     */
    public static function export_ecosystem_payload( array $options = [] ): array {
        return Sync_Ecosystem_Sender::export_payload( $options );
    }

    /**
     * Ủy quyền đẩy payload sang Live Webhost
     *
     * @param bool  $dry_run
     * @param array $scopes
     * @return array
     */
    public static function push_ecosystem_to_remote( bool $dry_run = false, array $scopes = [] ): array {
        return Sync_Ecosystem_Sender::push_to_remote( $dry_run, $scopes );
    }

    /**
     * Ủy quyền tạo báo cáo đối soát sang Sync_Ecosystem_Diff
     *
     * @param array $payload
     * @return array
     */
    public static function generate_diff_summary( array $payload ): array {
        return Sync_Ecosystem_Diff::generate( $payload );
    }

    /**
     * Tiếp nhận và xử lý payload Hệ Sinh Thái trên máy đích (Receiver)
     *
     * @param array $payload Dữ liệu hệ sinh thái từ Sender
     * @return array
     */
    public static function process_incoming_ecosystem( array $payload ): array {
        $dry_run    = ! empty( $payload['dry_run'] );
        $origin_url = esc_url_raw( $payload['origin_url'] ?? '' );

        // Nếu là Dry-run, chỉ lập báo cáo đối soát và trả về ngay
        if ( $dry_run ) {
            return self::generate_diff_summary( $payload );
        }

        $results = [
            'success'   => true,
            'dry_run'   => false,
            'message'   => __( 'Full Ecosystem synchronized successfully.', 'skaaai' ),
            'timestamp' => current_time( 'mysql' ),
            'details'   => [],
        ];

        // 1. Đồng bộ Design Tokens (Presets)
        if ( isset( $payload['presets'] ) && is_array( $payload['presets'] ) ) {
            $results['details']['presets'] = self::apply_presets( $payload['presets'] );
        }

        // 2. Đồng bộ Cấu kiện Organisms
        $organism_map = [];
        if ( isset( $payload['organisms'] ) && is_array( $payload['organisms'] ) ) {
            $org_result = self::apply_organisms( $payload['organisms'], $origin_url );
            $results['details']['organisms'] = $org_result['summary'];
            $organism_map = $org_result['map'];
        }

        // 3. Đồng bộ Quy tắc Theme Templates (HeaderBar/FooterBar)
        if ( isset( $payload['theme_templates'] ) && is_array( $payload['theme_templates'] ) ) {
            $results['details']['theme_templates'] = self::apply_theme_templates( $payload['theme_templates'], $organism_map );
        }

        // 4. Đồng bộ Skaaa Logic Workflows
        if ( isset( $payload['workflows'] ) && is_array( $payload['workflows'] ) ) {
            $results['details']['workflows'] = self::apply_workflows( $payload['workflows'], $origin_url );
        }

        // 5. Đồng bộ CSDL Bảng Phẳng Ứng Dụng (Flat Tables)
        if ( isset( $payload['custom_tables'] ) && is_array( $payload['custom_tables'] ) ) {
            $results['details']['custom_tables'] = self::apply_custom_tables( $payload['custom_tables'], $origin_url );
        }

        // 6. Đồng bộ Toàn Bộ Các Trang (Pages & Media)
        $page_map = [];
        if ( isset( $payload['pages'] ) && is_array( $payload['pages'] ) ) {
            $page_result = self::apply_pages( $payload['pages'], $origin_url );
            $results['details']['pages'] = $page_result['summary'];
            $page_map = $page_result['map'];
        }

        // 7. Đồng bộ Cấu Hình & Thiết Lập Hệ Thống (Site Setup)
        if ( isset( $payload['settings'] ) && is_array( $payload['settings'] ) ) {
            $results['details']['settings'] = self::apply_settings( $payload['settings'], $page_map );
        }

        // 8. Tự động Xóa Cache Hosting (Safeguard Layer 4)
        self::invalidate_caches();

        return $results;
    }

    /**
     * Cập nhật Design Tokens vào wp_skaaa_data_sys_presets và biên dịch cache
     *
     * @param array $presets
     * @return array
     */
    private static function apply_presets( array $presets ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'skaaa_data_sys_presets';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            return [ 'count' => 0, 'compiled' => false ];
        }

        $count = 0;
        foreach ( $presets as $preset ) {
            $type  = sanitize_text_field( $preset['type'] ?? 'token_color' );
            $name  = sanitize_text_field( $preset['name'] ?? '' );
            $value = $preset['value'] ?? '';

            if ( empty( $name ) ) {
                continue;
            }

            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `{$table}` WHERE `type` = %s AND `name` = %s", $type, $name ) );
            if ( $existing ) {
                $wpdb->update( $table, [ 'value' => $value ], [ 'id' => (int) $existing ], [ '%s' ], [ '%d' ] );
            } else {
                $wpdb->insert( $table, [ 'type' => $type, 'name' => $name, 'value' => $value ], [ '%s', '%s', '%s' ] );
            }
            $count++;
        }

        $compiled = false;
        if ( class_exists( '\Skaaa\Design\Api\Design_Tokens_Compiler' ) ) {
            try {
                \Skaaa\Design\Api\Design_Tokens_Compiler::get_instance()->compile_tokens_to_json();
                $compiled = true;
            } catch ( \Throwable $e ) {
                // Tiếp tục an toàn
            }
        }

        do_action( 'skaaa_tokens_updated' );

        return [
            'count'    => $count,
            'compiled' => $compiled,
        ];
    }

    /**
     * Cập nhật cấu kiện Organisms vào wp_skaaa_data_sys_organisms
     *
     * @param array  $organisms
     * @param string $origin_url
     * @return array
     */
    private static function apply_organisms( array $organisms, string $origin_url ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'skaaa_data_sys_organisms';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            return [ 'summary' => [ 'count' => 0 ], 'map' => [] ];
        }

        $map   = [];
        $count = 0;

        foreach ( $organisms as $org ) {
            $name         = sanitize_text_field( $org['name'] ?? '' );
            $category     = sanitize_text_field( $org['category'] ?? 'general' );
            $title        = sanitize_text_field( $org['title'] ?? '' );
            $block_name   = sanitize_text_field( $org['block_name'] ?? '' );
            $html_content = $org['html_content'] ?? '';
            $json_content = $org['json_content'] ?? '';

            if ( empty( $name ) ) {
                continue;
            }

            if ( ! empty( $origin_url ) ) {
                $html_content = Sync_Post::rewrite_domain_urls( $html_content, $origin_url );
                if ( ! empty( $json_content ) ) {
                    $json_content = Sync_Post::rewrite_domain_urls( $json_content, $origin_url );
                }
            }

            $html_content = Sync_Post::rewrite_table_prefixes( $html_content );
            if ( ! empty( $json_content ) ) {
                $json_content = Sync_Post::rewrite_table_prefixes( $json_content );
            }

            $existing = $wpdb->get_row( $wpdb->prepare( "SELECT `id` FROM `{$table}` WHERE `name` = %s", $name ), ARRAY_A );
            if ( $existing ) {
                $org_id = (int) $existing['id'];
                $wpdb->update(
                    $table,
                    [
                        'category'     => $category,
                        'title'        => $title,
                        'block_name'   => $block_name,
                        'html_content' => $html_content,
                        'json_content' => $json_content,
                    ],
                    [ 'id' => $org_id ],
                    [ '%s', '%s', '%s', '%s', '%s' ],
                    [ '%d' ]
                );
            } else {
                $wpdb->insert(
                    $table,
                    [
                        'type'         => 'organism',
                        'name'         => $name,
                        'category'     => $category,
                        'title'        => $title,
                        'block_name'   => $block_name,
                        'html_content' => $html_content,
                        'json_content' => $json_content,
                    ],
                    [ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
                );
                $org_id = (int) $wpdb->insert_id;
            }

            $map[ $name ] = $org_id;
            $count++;
        }

        return [
            'summary' => [ 'count' => $count ],
            'map'     => $map,
        ];
    }

    /**
     * Cập nhật quy tắc Theme Templates vào wp_skaaa_data_sys_theme_templates
     *
     * @param array $templates
     * @param array $organism_map
     * @return array
     */
    private static function apply_theme_templates( array $templates, array $organism_map ): array {
        global $wpdb;
        $table     = $wpdb->prefix . 'skaaa_data_sys_theme_templates';
        $org_table = $wpdb->prefix . 'skaaa_data_sys_organisms';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            return [ 'count' => 0 ];
        }

        $count = 0;
        foreach ( $templates as $tpl ) {
            $name       = sanitize_text_field( $tpl['name'] ?? '' );
            $location   = sanitize_key( $tpl['location'] ?? 'header' );
            $org_name   = sanitize_text_field( $tpl['organism_name'] ?? '' );
            $conditions = is_array( $tpl['conditions'] ?? null ) ? wp_json_encode( $tpl['conditions'] ) : (string) ( $tpl['conditions'] ?? '' );
            $is_active  = ! empty( $tpl['is_active'] ) ? 1 : 0;

            $target_org_id = $organism_map[ $org_name ] ?? 0;
            if ( ! $target_org_id && ! empty( $org_name ) ) {
                $target_org_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `{$org_table}` WHERE `name` = %s", $org_name ) );
            }

            $existing_tpl = $wpdb->get_row( $wpdb->prepare( "SELECT `id` FROM `{$table}` WHERE `location` = %s AND `name` = %s", $location, $name ), ARRAY_A );
            if ( ! $existing_tpl && $is_active ) {
                $existing_tpl = $wpdb->get_row( $wpdb->prepare( "SELECT `id` FROM `{$table}` WHERE `location` = %s AND `is_active` = 1", $location ), ARRAY_A );
            }

            if ( $existing_tpl ) {
                $wpdb->update(
                    $table,
                    [
                        'name'        => $name,
                        'organism_id' => $target_org_id,
                        'conditions'  => $conditions,
                        'is_active'   => $is_active,
                    ],
                    [ 'id' => (int) $existing_tpl['id'] ],
                    [ '%s', '%d', '%s', '%d' ],
                    [ '%d' ]
                );
            } else {
                $wpdb->insert(
                    $table,
                    [
                        'name'        => $name,
                        'location'    => $location,
                        'organism_id' => $target_org_id,
                        'conditions'  => $conditions,
                        'is_active'   => $is_active,
                    ],
                    [ '%s', '%s', '%d', '%s', '%d' ]
                );
            }

            $count++;
        }

        return [ 'count' => $count ];
    }

    /**
     * Cập nhật workflows vào wp_skaaa_data_sys_workflows
     *
     * @param array  $workflows
     * @param string $origin_url
     * @return array
     */
    private static function apply_workflows( array $workflows, string $origin_url ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'skaaa_data_sys_workflows';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            return [ 'count' => 0 ];
        }

        $count = 0;
        foreach ( $workflows as $wf ) {
            $workflow_id = sanitize_text_field( $wf['workflow_id'] ?? '' );
            $name        = sanitize_text_field( $wf['name'] ?? '' );
            $app_id      = sanitize_text_field( $wf['app_id'] ?? 'skaaa_system' );
            $status      = sanitize_key( $wf['status'] ?? 'active' );
            $node_count  = (int) ( $wf['node_count'] ?? 0 );
            $graph       = is_array( $wf['graph'] ?? null ) ? wp_json_encode( $wf['graph'] ) : (string) ( $wf['graph'] ?? '' );

            if ( empty( $workflow_id ) ) {
                continue;
            }

            if ( ! empty( $origin_url ) && ! empty( $graph ) ) {
                $graph = Sync_Post::rewrite_domain_urls( $graph, $origin_url );
            }

            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `{$table}` WHERE `workflow_id` = %s", $workflow_id ) );
            if ( $existing ) {
                $wpdb->update(
                    $table,
                    [
                        'name'       => $name,
                        'app_id'     => $app_id,
                        'status'     => $status,
                        'node_count' => $node_count,
                        'graph'      => $graph,
                    ],
                    [ 'id' => (int) $existing ],
                    [ '%s', '%s', '%s', '%d', '%s' ],
                    [ '%d' ]
                );
            } else {
                $wpdb->insert(
                    $table,
                    [
                        'workflow_id' => $workflow_id,
                        'name'        => $name,
                        'app_id'      => $app_id,
                        'status'      => $status,
                        'node_count'  => $node_count,
                        'graph'       => $graph,
                    ],
                    [ '%s', '%s', '%s', '%s', '%d', '%s' ]
                );
            }

            $count++;
        }

        if ( is_callable( [ '\Skaaa_Logic_Core', 'sync_workflow_ids_cache' ] ) ) {
            call_user_func( [ '\Skaaa_Logic_Core', 'sync_workflow_ids_cache' ] );
        }
        do_action( 'skaaa_workflows_updated' );

        return [ 'count' => $count ];
    }

    /**
     * Cập nhật schema và nội dung cho các bảng phẳng ứng dụng
     *
     * @param array  $custom_tables
     * @param string $origin_url
     * @return array
     */
    private static function apply_custom_tables( array $custom_tables, string $origin_url ): array {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $applied = [];

        foreach ( $custom_tables as $suffix => $tbl_data ) {
            $suffix = sanitize_key( $suffix );

            // Bảo vệ nghiêm ngặt chống can thiệp bảng cấm (Safeguards)
            if ( str_starts_with( $suffix, 'sys_' ) || str_ends_with( $suffix, '_submissions' ) || str_contains( $suffix, 'submission' ) ) {
                continue;
            }

            $table_name = $wpdb->prefix . 'skaaa_data_' . $suffix;
            $create_sql = str_replace( '{{TABLE_NAME}}', $table_name, $tbl_data['create_sql'] ?? '' );

            if ( ! empty( $create_sql ) ) {
                dbDelta( $create_sql );
            }

            $rows_count = 0;
            if ( ! empty( $tbl_data['rows'] ) && is_array( $tbl_data['rows'] ) && $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
                foreach ( $tbl_data['rows'] as $row ) {
                    if ( ! is_array( $row ) ) {
                        continue;
                    }

                    if ( ! empty( $origin_url ) ) {
                        foreach ( $row as $k => &$v ) {
                            if ( is_string( $v ) ) {
                                $v = Sync_Post::rewrite_domain_urls( $v, $origin_url );
                            }
                        }
                        unset( $v );
                    }

                    $row_id = isset( $row['id'] ) ? (int) $row['id'] : 0;
                    if ( $row_id > 0 ) {
                        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `{$table_name}` WHERE `id` = %d", $row_id ) );
                        if ( $exists ) {
                            $wpdb->update( $table_name, $row, [ 'id' => $row_id ] );
                        } else {
                            $wpdb->insert( $table_name, $row );
                        }
                    } else {
                        $wpdb->insert( $table_name, $row );
                    }
                    $rows_count++;
                }
            }

            $applied[ $suffix ] = [
                'table_name' => $table_name,
                'rows_count' => $rows_count,
            ];
        }

        return $applied;
    }

    /**
     * Đồng bộ danh sách tất cả các trang
     *
     * @param array  $pages
     * @param string $origin_url
     * @return array
     */
    private static function apply_pages( array $pages, string $origin_url ): array {
        $count   = 0;
        $map     = [];
        $summary = [];

        // Tạm thời vô hiệu hóa KSES và thiết lập ngữ cảnh Administrator để bảo tồn 100% mã SVG và HTML nâng cao
        if ( function_exists( 'kses_remove_filters' ) ) {
            kses_remove_filters();
        }
        $prev_user_id = get_current_user_id();
        if ( function_exists( 'get_users' ) ) {
            $admins = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
            if ( ! empty( $admins[0] ) ) {
                wp_set_current_user( $admins[0]->ID );
            }
        }

        foreach ( $pages as $page ) {
            $uuid   = sanitize_text_field( $page['uuid'] ?? '' );
            $title  = sanitize_text_field( $page['title'] ?? __( 'Untitled Page', 'skaaai' ) );
            $slug   = sanitize_title( $page['slug'] ?? '' );
            $order  = (int) ( $page['menu_order'] ?? 0 );
            $status = sanitize_key( $page['post_status'] ?? 'publish' );
            $raw_content = $page['content'] ?? '';

            if ( empty( $uuid ) ) {
                continue;
            }

            $existing_id = Sync_Post::get_post_id_by_uuid( $uuid );
            if ( ! $existing_id && ! empty( $slug ) ) {
                $existing_id = Sync_Post::get_post_id_by_slug( $slug, 'page' );
                if ( $existing_id ) {
                    update_post_meta( $existing_id, '_skaaa_uuid', $uuid );
                }
            }

            $processed_content = Sync_Post::sideload_remote_images( $raw_content, $origin_url );
            $processed_content = Sync_Post::rewrite_domain_urls( $processed_content, $origin_url );
            $processed_content = Sync_Post::rewrite_table_prefixes( $processed_content );

            $post_args = [
                'post_title'   => wp_slash( $title ),
                'post_content' => wp_slash( $processed_content ),
                'post_status'  => $status,
                'post_type'    => 'page',
                'menu_order'   => $order,
            ];

            if ( ! empty( $slug ) ) {
                $post_args['post_name'] = $slug;
            }

            if ( $existing_id ) {
                wp_save_post_revision( $existing_id );
                $post_args['ID'] = $existing_id;
                $post_id = wp_update_post( $post_args, true );
            } else {
                $post_id = wp_insert_post( $post_args, true );
            }

            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_skaaa_uuid', $uuid );
                update_post_meta( $post_id, '_skaaa_last_synced', current_time( 'mysql' ) );

                do_action( 'skaaa_after_post_synced', $post_id, $processed_content );

                $map[ $uuid ] = $post_id;
                if ( ! empty( $slug ) ) {
                    $map[ 'slug:' . $slug ] = $post_id;
                }

                $summary[] = [
                    'id'            => $post_id,
                    'title'         => $title,
                    'slug'          => $slug,
                    'is_front_page' => ! empty( $page['is_front_page'] ),
                    'action'        => $existing_id ? 'updated' : 'inserted',
                ];
                $count++;
            }
        }

        // Khôi phục lại bộ lọc KSES và ngữ cảnh người dùng
        if ( $prev_user_id !== get_current_user_id() ) {
            wp_set_current_user( $prev_user_id );
        }
        if ( function_exists( 'kses_init_filters' ) ) {
            kses_init_filters();
        }

        return [
            'summary' => [ 'count' => $count, 'pages' => $summary ],
            'map'     => $map,
        ];
    }

    /**
     * Cập nhật Cấu hình & Thiết lập Toàn diện (General, Reading, Permalinks)
     *
     * @param array $settings
     * @param array $page_map
     * @return array
     */
    private static function apply_settings( array $settings, array $page_map ): array {
        $applied = [];

        // 1. General Settings
        if ( ! empty( $settings['general'] ) && is_array( $settings['general'] ) ) {
            $allowed_general = [
                'blogname'        => 'sanitize_text_field',
                'blogdescription' => 'sanitize_text_field',
                'timezone_string' => 'sanitize_text_field',
                'gmt_offset'      => 'sanitize_text_field',
                'date_format'     => 'sanitize_text_field',
                'time_format'     => 'sanitize_text_field',
                'start_of_week'   => 'absint',
            ];

            foreach ( $allowed_general as $opt_key => $sanitizer ) {
                if ( isset( $settings['general'][ $opt_key ] ) && ! in_array( $opt_key, self::BLACKLISTED_OPTIONS, true ) ) {
                    $sanitized_val = call_user_func( $sanitizer, $settings['general'][ $opt_key ] );
                    update_option( $opt_key, $sanitized_val );
                    $applied[ $opt_key ] = $sanitized_val;
                }
            }
        }

        // 2. Reading Settings (Trang Chủ Tĩnh)
        if ( ! empty( $settings['reading'] ) && is_array( $settings['reading'] ) ) {
            $show_on_front = $settings['reading']['show_on_front'] ?? 'page';
            if ( 'page' === $show_on_front ) {
                $front_uuid = $settings['reading']['page_on_front_uuid'] ?? '';
                $front_slug = $settings['reading']['page_on_front_slug'] ?? '';

                $target_front_id = 0;
                if ( ! empty( $front_uuid ) && isset( $page_map[ $front_uuid ] ) ) {
                    $target_front_id = (int) $page_map[ $front_uuid ];
                } elseif ( ! empty( $front_slug ) && isset( $page_map[ 'slug:' . $front_slug ] ) ) {
                    $target_front_id = (int) $page_map[ 'slug:' . $front_slug ];
                } elseif ( ! empty( $front_uuid ) ) {
                    $target_front_id = (int) Sync_Post::get_post_id_by_uuid( $front_uuid );
                } elseif ( ! empty( $front_slug ) ) {
                    $target_front_id = (int) Sync_Post::get_post_id_by_slug( $front_slug, 'page' );
                }

                if ( $target_front_id > 0 ) {
                    update_option( 'show_on_front', 'page' );
                    update_option( 'page_on_front', $target_front_id );
                    $applied['show_on_front'] = 'page';
                    $applied['page_on_front'] = $target_front_id;
                }
            }
        }

        // 3. Permalink Structure
        if ( ! empty( $settings['permalink']['permalink_structure'] ) ) {
            $perm = sanitize_text_field( $settings['permalink']['permalink_structure'] );
            if ( ! empty( $perm ) && ! in_array( 'permalink_structure', self::BLACKLISTED_OPTIONS, true ) ) {
                update_option( 'permalink_structure', $perm );
                $applied['permalink_structure'] = $perm;
            }
        }

        return $applied;
    }

    /**
     * Tự động xóa sạch Cache hosting và làm mới Rewrite Rules (Safeguard Layer 4)
     *
     * @return void
     */
    public static function invalidate_caches(): void {
        do_action( 'litespeed_purge_all' );
        wp_cache_flush();

        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
        }

        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
        }

        flush_rewrite_rules( false );
    }
}
