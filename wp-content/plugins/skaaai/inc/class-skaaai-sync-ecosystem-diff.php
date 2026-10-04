<?php
/**
 * Lớp Tạo Báo Cáo Đối Soát Đồng Bộ Hệ Sinh Thái (Skaaai_Sync_Ecosystem_Diff)
 *
 * Chịu trách nhiệm phân tích payload gửi đến và đối chiếu với dữ liệu hiện có
 * trên Live Webhost để lập bảng tổng hợp Diff Summary phục vụ Pre-flight Review Gate.
 *
 * @package Skaaai
 * @version 1.4.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Ecosystem_Diff {

    /**
     * Tạo báo cáo tóm tắt đối soát (Diff Summary)
     *
     * @param array $payload Dữ liệu từ Sender
     * @return array
     */
    public static function generate( array $payload ): array {
        global $wpdb;

        $diff = [
            'success'    => true,
            'dry_run'    => true,
            'message'    => __( 'Pre-flight dry-run completed. Review changes before executing.', 'skaaai' ),
            'summary'    => [],
            'safeguards' => [
                'protected_domains' => [ 'siteurl' => site_url(), 'home' => home_url() ],
                'protected_options' => Sync_Ecosystem::BLACKLISTED_OPTIONS,
                'protected_tables'  => [ 'wp_users', 'wp_usermeta', 'wp_skaaa_data_*_submissions' ],
            ],
        ];

        // 1. Phân tích Presets
        $presets_count = count( $payload['presets'] ?? [] );
        $diff['summary']['presets'] = [
            'count'  => $presets_count,
            'action' => sprintf( __( '%d Design Tokens will be updated and compiled.', 'skaaai' ), $presets_count ),
        ];

        // 2. Phân tích Organisms (True Mirror Parity)
        $org_table   = $wpdb->prefix . 'skaaa_data_sys_organisms';
        $org_summary = [];
        $matched_org_names = [];
        if ( ! empty( $payload['organisms'] ) ) {
            foreach ( $payload['organisms'] as $org ) {
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `{$org_table}` WHERE `name` = %s", $org['name'] ) );
                $org_summary[] = [
                    'name'     => $org['name'],
                    'category' => $org['category'] ?? 'general',
                    'action'   => $exists ? 'update' : 'create',
                ];
                $matched_org_names[] = $org['name'];
            }
        }
        // Phát hiện Organisms bị xóa trên nguồn
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$org_table}'" ) === $org_table ) {
            $local_orgs = $wpdb->get_col( "SELECT `name` FROM `{$org_table}`" ) ?: [];
            foreach ( $local_orgs as $lo_name ) {
                if ( ! in_array( $lo_name, $matched_org_names, true ) ) {
                    $org_summary[] = [
                        'name'     => $lo_name,
                        'category' => 'general',
                        'action'   => 'delete (missing remotely)',
                    ];
                }
            }
        }
        $diff['summary']['organisms'] = $org_summary;

        // 3. Phân tích Theme Templates
        $tpl_summary = [];
        if ( ! empty( $payload['theme_templates'] ) ) {
            foreach ( $payload['theme_templates'] as $tpl ) {
                $tpl_summary[] = [
                    'name'          => $tpl['name'],
                    'location'      => $tpl['location'],
                    'organism_name' => $tpl['organism_name'] ?? '',
                    'is_active'     => ! empty( $tpl['is_active'] ),
                ];
            }
        }
        $diff['summary']['theme_templates'] = $tpl_summary;

        // 4. Phân tích Workflows (True Mirror Parity)
        $wf_summary = [];
        $matched_wf_ids = [];
        $workflows_table = $wpdb->prefix . 'skaaa_data_sys_workflows';
        if ( ! empty( $payload['workflows'] ) ) {
            foreach ( $payload['workflows'] as $wf ) {
                $wf_summary[] = [
                    'workflow_id' => $wf['workflow_id'],
                    'name'        => $wf['name'],
                    'status'      => $wf['status'],
                ];
                $matched_wf_ids[] = $wf['workflow_id'];
            }
        }
        // Phát hiện Workflows bị xóa trên nguồn
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$workflows_table}'" ) === $workflows_table ) {
            $local_wfs = $wpdb->get_results( "SELECT `workflow_id`, `name` FROM `{$workflows_table}`", ARRAY_A ) ?: [];
            foreach ( $local_wfs as $lw ) {
                if ( ! in_array( $lw['workflow_id'], $matched_wf_ids, true ) ) {
                    $wf_summary[] = [
                        'workflow_id' => $lw['workflow_id'],
                        'name'        => $lw['name'] . ' (Deleted on Live)',
                        'status'      => 'deleted',
                    ];
                }
            }
        }
        $diff['summary']['workflows'] = $wf_summary;

        // 5. Phân tích Custom Tables
        $tbl_summary = [];
        if ( ! empty( $payload['custom_tables'] ) ) {
            foreach ( $payload['custom_tables'] as $suffix => $tbl_data ) {
                $tbl_summary[] = [
                    'table_name' => $wpdb->prefix . 'skaaa_data_' . $suffix,
                    'row_count'  => $tbl_data['row_count'] ?? 0,
                ];
            }
        }
        $diff['summary']['custom_tables'] = $tbl_summary;

        // 6. Phân tích Pages & Posts Chuyên Sâu (Modified, New, Deleted on Remote, Unchanged)
        $pages_analysis = self::analyze_content_items( $payload['pages'] ?? [], 'page' );
        $posts_analysis = self::analyze_content_items( $payload['posts'] ?? [], 'post' );

        $diff['summary']['pages']        = $pages_analysis['items'];
        $diff['summary']['pages_counts'] = $pages_analysis['counts'];

        $diff['summary']['posts']        = $posts_analysis['items'];
        $diff['summary']['posts_counts'] = $posts_analysis['counts'];

        $diff['summary']['content_items'] = array_merge( $pages_analysis['items'], $posts_analysis['items'] );
        $diff['summary']['content_counts'] = [
            'total'     => $pages_analysis['counts']['total'] + $posts_analysis['counts']['total'],
            'modified'  => $pages_analysis['counts']['modified'] + $posts_analysis['counts']['modified'],
            'new'       => $pages_analysis['counts']['new'] + $posts_analysis['counts']['new'],
            'deleted'   => $pages_analysis['counts']['deleted'] + $posts_analysis['counts']['deleted'],
            'unchanged' => $pages_analysis['counts']['unchanged'] + $posts_analysis['counts']['unchanged'],
        ];

        // 7. Phân tích Settings
        $diff['summary']['settings'] = [
            'blogname'            => $payload['settings']['general']['blogname'] ?? get_option( 'blogname' ),
            'blogdescription'     => $payload['settings']['general']['blogdescription'] ?? get_option( 'blogdescription' ),
            'show_on_front'       => $payload['settings']['reading']['show_on_front'] ?? 'page',
            'front_page_slug'     => $payload['settings']['reading']['page_on_front_slug'] ?? '',
            'permalink_structure' => $payload['settings']['permalink']['permalink_structure'] ?? get_option( 'permalink_structure' ),
        ];

        return $diff;
    }

    /**
     * Phân tích đối soát chuyên sâu cho các bài viết/trang nội dung
     *
     * @param array  $remote_items Danh sách các bài từ máy chủ Remote
     * @param string $post_type    Loại bài ('page' hoặc 'post')
     * @return array Báo cáo đối soát gồm items chi tiết và tổng hợp số liệu
     */
    public static function analyze_content_items( array $remote_items, string $post_type = 'page' ): array {
        $items             = [];
        $matched_local_ids = [];
        $counts            = [
            'total'     => 0,
            'modified'  => 0,
            'new'       => 0,
            'deleted'   => 0,
            'unchanged' => 0,
        ];

        $front_page_id = (int) get_option( 'page_on_front' );

        // 1. Đối chiếu các mục từ Remote gửi sang Localhost
        foreach ( $remote_items as $remote ) {
            $uuid   = sanitize_text_field( $remote['uuid'] ?? '' );
            $title  = sanitize_text_field( $remote['title'] ?? '' );
            $slug   = sanitize_title( $remote['slug'] ?? '' );
            $is_fp  = ! empty( $remote['is_front_page'] );

            $remote_modified = (int) ( $remote['last_modified'] ?? strtotime( $remote['modified_gmt'] ?? $remote['post_modified_gmt'] ?? 'now' ) );
            $remote_hash     = $remote['content_hash'] ?? md5( ( $title ?? '' ) . '|' . ( $remote['content'] ?? '' ) );

            $local_id = 0;
            if ( ! empty( $uuid ) ) {
                $local_id = Sync_Post::get_post_id_by_uuid( $uuid );
            }
            if ( ! $local_id && ! empty( $slug ) ) {
                $local_id = Sync_Post::get_post_id_by_slug( $slug, $post_type );
            }

            if ( $local_id ) {
                $matched_local_ids[] = (int) $local_id;
                $local_post          = get_post( $local_id );
                $local_modified      = (int) get_post_modified_time( 'U', true, $local_id );
                $local_hash          = md5( ( $local_post->post_title ?? '' ) . '|' . ( $local_post->post_content ?? '' ) );

                // So sánh xem có thay đổi không
                $time_diff            = $remote_modified - $local_modified;
                $is_content_identical = ( $remote_hash === $local_hash );

                if ( $is_content_identical || abs( $time_diff ) <= 2 ) {
                    $status = 'unchanged';
                    $action = __( 'Identical (Synced)', 'skaaai' );
                    $counts['unchanged']++;
                } else {
                    $status = 'modified';
                    if ( $remote_modified > ( $local_modified + 2 ) ) {
                        $diff_str = human_time_diff( $local_modified, $remote_modified );
                        /* translators: %s: Human readable time difference */
                        $action = sprintf( __( 'Remote is newer (+%s)', 'skaaai' ), $diff_str );
                    } elseif ( $local_modified > ( $remote_modified + 2 ) ) {
                        $diff_str = human_time_diff( $remote_modified, $local_modified );
                        /* translators: %s: Human readable time difference */
                        $action = sprintf( __( 'Local is newer (+%s)', 'skaaai' ), $diff_str );
                    } else {
                        $action = __( 'Content modified', 'skaaai' );
                    }
                    $counts['modified']++;
                }

                $items[] = [
                    'id'              => (int) $local_id,
                    'uuid'            => $uuid,
                    'title'           => $title ?: ( $local_post->post_title ?: __( '(No Title)', 'skaaai' ) ),
                    'slug'            => $slug ?: $local_post->post_name,
                    'post_type'       => $post_type,
                    'status'          => $status,
                    'action'          => $action,
                    'remote_modified' => $remote_modified ? wp_date( 'Y-m-d H:i', $remote_modified ) : '-',
                    'local_modified'  => $local_modified ? wp_date( 'Y-m-d H:i', $local_modified ) : '-',
                    'is_front_page'   => $is_fp || ( (int) $local_id === $front_page_id ),
                ];
            } else {
                // Mục mới trên Remote chưa có ở Localhost
                $status = 'new';
                $action = __( 'New on Live (will create locally)', 'skaaai' );
                $counts['new']++;

                $items[] = [
                    'id'              => 0,
                    'uuid'            => $uuid,
                    'title'           => $title ?: __( '(No Title)', 'skaaai' ),
                    'slug'            => $slug,
                    'post_type'       => $post_type,
                    'status'          => $status,
                    'action'          => $action,
                    'remote_modified' => $remote_modified ? wp_date( 'Y-m-d H:i', $remote_modified ) : '-',
                    'local_modified'  => '-',
                    'is_front_page'   => $is_fp,
                ];
            }
        }

        // 2. Chiều đối chiếu ngược: Quét các mục trên Localhost bị xóa trên Remote (True Mirror: cả publish và draft)
        $local_query = get_posts( [
            'post_type'      => $post_type,
            'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ] );

        foreach ( $local_query as $lid ) {
            $lid = (int) $lid;
            if ( ! in_array( $lid, $matched_local_ids, true ) ) {
                $lpost = get_post( $lid );
                if ( ! ( $lpost instanceof \WP_Post ) ) {
                    continue;
                }

                $l_uuid     = get_post_meta( $lid, '_skaaa_uuid', true ) ?: '';
                $l_modified = (int) get_post_modified_time( 'U', true, $lid );

                $status = 'deleted_on_remote';
                $action = __( 'Deleted on Live (Will be moved to Trash)', 'skaaai' );
                $counts['deleted']++;

                $items[] = [
                    'id'              => $lid,
                    'uuid'            => $l_uuid,
                    'title'           => $lpost->post_title ?: __( '(No Title)', 'skaaai' ),
                    'slug'            => $lpost->post_name,
                    'post_type'       => $post_type,
                    'status'          => $status,
                    'action'          => $action,
                    'remote_modified' => '-',
                    'local_modified'  => $l_modified ? wp_date( 'Y-m-d H:i', $l_modified ) : '-',
                    'is_front_page'   => ( $lid === $front_page_id ),
                ];
            }
        }

        $counts['total'] = count( $items );

        // Sắp xếp: Ưu tiên các mục có thay đổi (modified, new, deleted) lên đầu, unchanged xuống sau
        usort( $items, function( $a, $b ) {
            $priority = [ 'modified' => 1, 'new' => 2, 'deleted_on_remote' => 3, 'unchanged' => 4 ];
            $pa       = $priority[ $a['status'] ] ?? 5;
            $pb       = $priority[ $b['status'] ] ?? 5;
            if ( $pa !== $pb ) {
                return $pa <=> $pb;
            }
            return strcasecmp( $a['title'], $b['title'] );
        } );

        return [
            'counts' => $counts,
            'items'  => $items,
        ];
    }
}
