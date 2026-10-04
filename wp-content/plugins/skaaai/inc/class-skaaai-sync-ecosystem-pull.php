<?php
/**
 * Lớp Động Cơ Kéo Hệ Sinh Thái Từ Live Webhost Về Localhost (Skaaai_Sync_Ecosystem_Pull)
 *
 * Chịu trách nhiệm:
 * 1. Gọi REST API Live Webhost để trích xuất toàn bộ Hệ Sinh Thái (Design Tokens,
 *    Organisms, Theme Templates, Logic Workflows, Custom Flat Tables, All Pages & Settings).
 * 2. Phân tích đối soát trước (Pre-flight Dry-run Diff) để người dùng xem trước và duyệt.
 * 3. Thực thi kéo về và áp dụng Reverse Transformers:
 *    - Hoán đổi ngược tên miền (Live ➔ Localhost).
 *    - Hoán đổi ngược tiền tố CSDL phẳng (Live prefix ➔ Local prefix).
 *    - Reverse Sideload Media: Tải ảnh từ Live về thư mục uploads/ trên Localhost.
 * 4. Tuân thủ Thiết Quân Luật Bảo Mật 4 Lớp.
 *
 * @package Skaaai
 * @version 1.5.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Ecosystem_Pull {

    /**
     * Kéo gói Hệ Sinh Thái từ máy chủ Live về Localhost
     *
     * @param bool  $dry_run Nếu là true, chỉ phân tích đối soát chứ không ghi đè CSDL
     * @param array $scopes  Danh sách các phân hệ cần kéo
     * @return array
     */
    public static function pull_from_remote( bool $dry_run = false, array $scopes = [] ): array {
        $remote_url   = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $remote_token = sanitize_text_field( Core::get_setting( 'skaaai_remote_token', '' ) );

        if ( empty( $remote_url ) || empty( $remote_token ) ) {
            return [
                'success' => false,
                'message' => __( 'Remote website is not paired. Please configure pairing in Skaaa Bridge settings.', 'skaaai' ),
            ];
        }

        $all_scopes = [ 'presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'posts', 'settings' ];
        $target_scopes = ( ! empty( $scopes ) && is_array( $scopes ) ) ? $scopes : $all_scopes;

        // 1. Gửi yêu cầu trích xuất toàn bộ Hệ Sinh Thái sang Live
        $endpoint = add_query_arg( [
            'scopes'  => implode( ',', $target_scopes ),
            'dry_run' => $dry_run ? 1 : 0,
        ], rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/export-ecosystem' );

        $response = wp_remote_get( $endpoint, [
            'timeout'   => 120,
            'headers'   => [
                'X-Skaaai-Token' => $remote_token,
                'Accept'         => 'application/json',
            ],
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

        if ( 200 !== $status_code || empty( $data['success'] ) || empty( $data['payload'] ) ) {
            $err_msg = $data['message'] ?? sprintf( __( 'Remote export failed (HTTP %d).', 'skaaai' ), $status_code );
            return [
                'success' => false,
                'message' => $err_msg,
            ];
        }

        $payload = $data['payload'];

        // 2. Chế độ Xem Trước Đối Soát (Dry-run Diff)
        if ( $dry_run ) {
            $diff_report = Sync_Ecosystem_Diff::generate( $payload );
            $diff_report['direction'] = 'pull';
            return $diff_report;
        }

        // 3. Chế độ Thực Thi: Áp dụng Reverse Transformers
        self::apply_reverse_transformers( $payload );

        // 4. Ủy quyền ghi đè an toàn qua Sync_Ecosystem
        $result = Sync_Ecosystem::process_incoming_ecosystem( $payload );
        $result['direction'] = 'pull';

        return $result;
    }

    /**
     * Áp dụng các bộ chuyển đổi ngược (Reverse Transformers)
     *
     * @param array $payload Gói dữ liệu hệ sinh thái được tham chiếu để cập nhật trực tiếp
     * @return void
     */
    private static function apply_reverse_transformers( array &$payload ): void {
        global $wpdb;

        $remote_origin_url = esc_url_raw( $payload['origin_url'] ?? '' );
        $local_site_url    = site_url();

        // 1. Reverse Transformers cho Cấu kiện Organisms
        if ( ! empty( $payload['organisms'] ) && is_array( $payload['organisms'] ) ) {
            foreach ( $payload['organisms'] as &$org ) {
                if ( ! empty( $org['html_content'] ) ) {
                    // Tải ảnh về uploads/ cục bộ
                    if ( ! empty( $remote_origin_url ) ) {
                        $org['html_content'] = Sync_Pull::sideload_remote_images_to_local( $org['html_content'], $remote_origin_url );
                        $org['html_content'] = Sync_Post::rewrite_domain_urls( $org['html_content'], $remote_origin_url );
                    }
                    // Chuẩn hóa prefix bảng phẳng về local
                    $org['html_content'] = Sync_Post::rewrite_table_prefixes( $org['html_content'] );
                }

                if ( ! empty( $org['json_content'] ) && is_string( $org['json_content'] ) ) {
                    if ( ! empty( $remote_origin_url ) ) {
                        $org['json_content'] = Sync_Post::rewrite_domain_urls( $org['json_content'], $remote_origin_url );
                    }
                    $org['json_content'] = Sync_Post::rewrite_table_prefixes( $org['json_content'] );
                }
            }
            unset( $org );
        }

        // 2. Reverse Transformers cho Skaaa Logic Workflows
        if ( ! empty( $payload['workflows'] ) && is_array( $payload['workflows'] ) ) {
            foreach ( $payload['workflows'] as &$wf ) {
                if ( ! empty( $wf['graph'] ) ) {
                    $graph_str = is_array( $wf['graph'] ) ? wp_json_encode( $wf['graph'] ) : (string) $wf['graph'];
                    if ( ! empty( $remote_origin_url ) ) {
                        $graph_str = Sync_Post::rewrite_domain_urls( $graph_str, $remote_origin_url );
                    }
                    $graph_str = Sync_Post::rewrite_table_prefixes( $graph_str );
                    $wf['graph'] = $graph_str;
                }
            }
            unset( $wf );
        }

        // 3. Reverse Transformers cho Toàn Bộ Trang (Pages) & Bài Viết (Posts)
        $content_buckets = [ 'pages', 'posts' ];
        foreach ( $content_buckets as $bucket ) {
            if ( ! empty( $payload[ $bucket ] ) && is_array( $payload[ $bucket ] ) ) {
                foreach ( $payload[ $bucket ] as &$item ) {
                    if ( ! empty( $item['content'] ) ) {
                        // Sideload ảnh từ remote về local
                        if ( ! empty( $remote_origin_url ) ) {
                            $item['content'] = Sync_Pull::sideload_remote_images_to_local( $item['content'], $remote_origin_url );
                            $item['content'] = Sync_Post::rewrite_domain_urls( $item['content'], $remote_origin_url );
                        }
                        // Chuẩn hóa prefix bảng phẳng về local
                        $item['content'] = Sync_Post::rewrite_table_prefixes( $item['content'] );
                    }
                }
                unset( $item );
            }
        }

        // 4. Reverse Transformers cho Custom Flat Tables (Bảng phẳng CSDL)
        if ( ! empty( $payload['custom_tables'] ) && is_array( $payload['custom_tables'] ) ) {
            foreach ( $payload['custom_tables'] as &$tbl_data ) {
                if ( ! empty( $tbl_data['rows'] ) && is_array( $tbl_data['rows'] ) ) {
                    foreach ( $tbl_data['rows'] as &$row ) {
                        foreach ( $row as $col => &$val ) {
                            if ( is_string( $val ) && ( str_contains( $val, '_skaaa_data_' ) || str_contains( $val, 'skaaa_data_' ) ) ) {
                                $val = Sync_Post::rewrite_table_prefixes( $val );
                            }
                            if ( is_string( $val ) && ! empty( $remote_origin_url ) && str_contains( $val, $remote_origin_url ) ) {
                                $val = str_replace( $remote_origin_url, $local_site_url, $val );
                            }
                        }
                        unset( $val );
                    }
                    unset( $row );
                }
            }
            unset( $tbl_data );
        }
    }
}
