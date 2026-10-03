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

        // 2. Phân tích Organisms
        $org_table   = $wpdb->prefix . 'skaaa_data_sys_organisms';
        $org_summary = [];
        if ( ! empty( $payload['organisms'] ) ) {
            foreach ( $payload['organisms'] as $org ) {
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `{$org_table}` WHERE `name` = %s", $org['name'] ) );
                $org_summary[] = [
                    'name'     => $org['name'],
                    'category' => $org['category'] ?? 'general',
                    'action'   => $exists ? 'update' : 'create',
                ];
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

        // 4. Phân tích Workflows
        $wf_summary = [];
        if ( ! empty( $payload['workflows'] ) ) {
            foreach ( $payload['workflows'] as $wf ) {
                $wf_summary[] = [
                    'workflow_id' => $wf['workflow_id'],
                    'name'        => $wf['name'],
                    'status'      => $wf['status'],
                ];
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

        // 6. Phân tích Pages
        $pages_summary = [];
        if ( ! empty( $payload['pages'] ) ) {
            foreach ( $payload['pages'] as $page ) {
                $existing_id = Sync_Post::get_post_id_by_uuid( $page['uuid'] );
                if ( ! $existing_id && ! empty( $page['slug'] ) ) {
                    $existing_id = Sync_Post::get_post_id_by_slug( $page['slug'], 'page' );
                }
                $pages_summary[] = [
                    'title'         => $page['title'],
                    'slug'          => $page['slug'],
                    'action'        => $existing_id ? "update (ID: {$existing_id})" : 'create_new',
                    'is_front_page' => ! empty( $page['is_front_page'] ),
                ];
            }
        }
        $diff['summary']['pages'] = $pages_summary;

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
}
