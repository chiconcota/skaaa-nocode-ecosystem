<?php
/**
 * Lớp Động Cơ Kéo Dữ Liệu Từ Live Webhost (Skaaai_Sync_Pull)
 *
 * Chịu trách nhiệm:
 * 1. Kéo nội dung bài viết từ Live Webhost về Localhost qua REST API.
 * 2. Bảo vệ dữ liệu bằng cơ chế Revision-First (tạo bản sao lưu trước khi ghi đè).
 * 3. Hoán đổi ngược tên miền (Live ➔ Local) và tiền tố CSDL phẳng (skaaa_data_*).
 * 4. Tải hình ảnh mới từ Live về Local Media Library (Reverse Sideload Media).
 * 5. Truy vấn đối soát chênh lệch thời gian hàng loạt với Live host.
 *
 * @package Skaaai
 * @version 1.5.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Pull {

    /**
     * Kéo một bài viết từ Live về ghi đè an toàn lên bài viết Localhost
     *
     * @param int $post_id ID bài viết trên Localhost
     * @return array
     */
    public static function pull_post_from_remote( int $post_id ): array {
        $post = get_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) ) {
            return [
                'success' => false,
                'message' => __( 'Local post not found.', 'skaaai' ),
            ];
        }

        $remote_url   = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $remote_token = sanitize_text_field( Core::get_setting( 'skaaai_remote_token', '' ) );

        if ( empty( $remote_url ) || empty( $remote_token ) ) {
            return [
                'success' => false,
                'message' => __( 'Remote website is not paired. Please configure pairing in Skaaai settings.', 'skaaai' ),
            ];
        }

        $uuid = get_post_meta( $post_id, '_skaaa_uuid', true );
        if ( empty( $uuid ) ) {
            $uuid = wp_generate_uuid4();
            update_post_meta( $post_id, '_skaaa_uuid', $uuid );
        }

        // 1. Gửi yêu cầu trích xuất dữ liệu từ máy chủ Live
        $endpoint = add_query_arg(
            [
                'uuid'      => rawurlencode( $uuid ),
                'slug'      => rawurlencode( $post->post_name ),
                'post_type' => rawurlencode( $post->post_type ),
            ],
            rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/export-post'
        );

        $response = wp_remote_get( $endpoint, [
            'timeout'   => 30,
            'headers'   => [
                'X-Skaaai-Token' => $remote_token,
                'Accept'         => 'application/json',
            ],
            'sslverify' => false,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => sprintf(
                    __( 'Network error communicating with Live Webhost: %s', 'skaaai' ),
                    $response->get_error_message()
                ),
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
            ];
        }

        // 2. TẠO REVISION BẢO VỆ DỮ LIỆU CŨ (Zero Data Loss)
        if ( function_exists( 'wp_save_post_revision' ) ) {
            wp_save_post_revision( $post_id );
        }

        $remote_title       = $data['title'] ?? $post->post_title;
        $remote_content     = $data['content'] ?? '';
        $remote_permalink   = $data['permalink'] ?? '';
        $remote_post_id     = $data['remote_post_id'] ?? null;
        $featured_media_url = $data['featured_media_url'] ?? '';

        // 3. ĐẢO CHIỀU DỮ LIỆU (Reverse Transformers)
        // 3.1. Tải ảnh từ Live về Local Media Library
        $processed_content = self::sideload_remote_images_to_local( $remote_content, $remote_url );

        // 3.2. Hoán đổi prefix bảng phẳng CSDL về prefix Localhost
        $processed_content = Sync_Post::rewrite_table_prefixes( $processed_content );

        // 3.3. Hoán đổi tên miền Live về tên miền Localhost
        $processed_content = Sync_Post::rewrite_domain_urls( $processed_content, $remote_url );

        // 3.4. Xử lý ảnh đại diện (Featured Image) nếu có
        if ( ! empty( $featured_media_url ) ) {
            self::sideload_featured_image( $post_id, $featured_media_url, $remote_url );
        }

        // 4. GHI ĐÈ BÀI VIẾT CỤC BỘ DƯỚI NGỮ CẢNH AN TOÀN (Bypass KSES & wp_slash)
        $prev_user_id = get_current_user_id();
        $admins       = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
        if ( ! empty( $admins ) ) {
            wp_set_current_user( $admins[0]->ID );
        }

        if ( function_exists( 'kses_remove_filters' ) ) {
            kses_remove_filters();
        }

        $update_result = wp_update_post( [
            'ID'           => $post_id,
            'post_title'   => wp_slash( $remote_title ),
            'post_content' => wp_slash( $processed_content ),
        ], true );

        if ( $prev_user_id !== get_current_user_id() ) {
            wp_set_current_user( $prev_user_id );
        }
        if ( function_exists( 'kses_init_filters' ) ) {
            kses_init_filters();
        }

        if ( is_wp_error( $update_result ) ) {
            return [
                'success' => false,
                'message' => $update_result->get_error_message(),
            ];
        }

        // 5. CẬP NHẬT METADATA ĐỒNG BỘ
        $current_now = current_time( 'mysql' );
        update_post_meta( $post_id, '_skaaa_uuid', $uuid );
        update_post_meta( $post_id, '_skaaa_last_synced', $current_now );
        if ( ! empty( $remote_permalink ) ) {
            update_post_meta( $post_id, '_skaaa_remote_permalink', esc_url_raw( $remote_permalink ) );
        }
        if ( ! empty( $remote_post_id ) ) {
            update_post_meta( $post_id, '_skaaa_remote_post_id', (int) $remote_post_id );
        }

        // 6. Kích hoạt biên dịch Tailwind JIT CSS trên Localhost
        do_action( 'skaaa_after_post_synced', $post_id, $processed_content );

        return [
            'success'          => true,
            'message'          => __( 'Post pulled from Live webhost successfully!', 'skaaai' ),
            'post_id'          => $post_id,
            'title'            => $remote_title,
            'permalink'        => get_permalink( $post_id ),
            'remote_permalink' => $remote_permalink,
            'last_synced'      => $current_now,
            'sync_status'      => 'synced',
        ];
    }

    /**
     * Tải hình ảnh nhúng từ Live về Local Media Library
     *
     * @param string $content    Mã nội dung khối Gutenberg
     * @param string $remote_url URL gốc của máy chủ Live
     * @return string
     */
    public static function sideload_remote_images_to_local( string $content, string $remote_url ): string {
        if ( empty( $remote_url ) || empty( $content ) ) {
            return $content;
        }

        $remote_clean = rtrim( $remote_url, '/' );

        // Quét tất cả link ảnh
        $pattern = '/(?:https?:\/\/[^\s"\'\\\]+|(?:\\?\/)wp-content(?:\\?\/)uploads[^\s"\'\\\]+)\.(?:jpg|jpeg|png|gif|webp|svg)/i';
        if ( ! preg_match_all( $pattern, $content, $matches ) ) {
            return $content;
        }

        $urls = array_unique( $matches[0] );

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        foreach ( $urls as $raw_url ) {
            $clean_url = str_replace( '\\/', '/', $raw_url );
            if ( str_starts_with( $clean_url, '/' ) ) {
                $full_download_url = $remote_clean . $clean_url;
            } else {
                $full_download_url = $clean_url;
            }

            // Chỉ tải các ảnh xuất phát từ domain của remote live
            if ( ! str_starts_with( $full_download_url, $remote_clean ) ) {
                continue;
            }

            $tmp_file = download_url( $full_download_url );
            if ( is_wp_error( $tmp_file ) ) {
                continue;
            }

            $file_array = [
                'name'     => basename( parse_url( $full_download_url, PHP_URL_PATH ) ),
                'tmp_name' => $tmp_file,
            ];

            $attachment_id = media_handle_sideload( $file_array, 0 );
            if ( is_wp_error( $attachment_id ) ) {
                @unlink( $tmp_file );
                continue;
            }

            $new_url = wp_get_attachment_url( $attachment_id );
            if ( $new_url ) {
                $content = str_replace( $raw_url, $new_url, $content );
                $content = str_replace( $clean_url, $new_url, $content );
                $content = str_replace(
                    str_replace( '/', '\\/', $clean_url ),
                    str_replace( '/', '\\/', $new_url ),
                    $content
                );
            }
        }

        return $content;
    }

    /**
     * Tải và gán ảnh đại diện (Featured Image) từ Live về Local
     *
     * @param int    $post_id
     * @param string $media_url
     * @param string $remote_url
     * @return void
     */
    public static function sideload_featured_image( int $post_id, string $media_url, string $remote_url ): void {
        if ( empty( $media_url ) ) {
            return;
        }

        $remote_clean = rtrim( $remote_url, '/' );
        $clean_url    = str_replace( '\\/', '/', $media_url );

        if ( str_starts_with( $clean_url, '/' ) ) {
            $full_url = $remote_clean . $clean_url;
        } else {
            $full_url = $clean_url;
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp_file = download_url( $full_url );
        if ( is_wp_error( $tmp_file ) ) {
            return;
        }

        $file_array = [
            'name'     => basename( parse_url( $full_url, PHP_URL_PATH ) ),
            'tmp_name' => $tmp_file,
        ];

        $attachment_id = media_handle_sideload( $file_array, $post_id );
        if ( ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $post_id, $attachment_id );
        } else {
            @unlink( $tmp_file );
        }
    }

    /**
     * Truy vấn đối soát trạng thái bài viết hàng loạt từ máy chủ Live
     *
     * @param array $post_ids Danh sách ID bài viết trên Localhost
     * @return array
     */
    public static function check_remote_status_batch( array $post_ids ): array {
        $remote_url   = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $remote_token = sanitize_text_field( Core::get_setting( 'skaaai_remote_token', '' ) );

        if ( empty( $remote_url ) || empty( $remote_token ) || empty( $post_ids ) ) {
            return [
                'success' => false,
                'message' => __( 'Remote connection not configured or empty post list.', 'skaaai' ),
            ];
        }

        $posts_payload = [];
        foreach ( $post_ids as $pid ) {
            $post = get_post( (int) $pid );
            if ( ! ( $post instanceof \WP_Post ) ) {
                continue;
            }

            $uuid = get_post_meta( $post->ID, '_skaaa_uuid', true );
            if ( empty( $uuid ) ) {
                $uuid = wp_generate_uuid4();
                update_post_meta( $post->ID, '_skaaa_uuid', $uuid );
            }

            $posts_payload[] = [
                'uuid'           => $uuid,
                'slug'           => $post->post_name,
                'post_type'      => $post->post_type,
                'local_modified' => (int) get_post_modified_time( 'U', true, $post->ID ),
                'local_post_id'  => $post->ID,
            ];
        }

        $endpoint = rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/check-posts-status';
        $response = wp_remote_post( $endpoint, [
            'timeout'   => 20,
            'headers'   => [
                'X-Skaaai-Token' => $remote_token,
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
            ],
            'body'      => wp_json_encode( [ 'posts' => $posts_payload ] ),
            'sslverify' => false,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data['success'] ) || empty( $data['statuses'] ) ) {
            return [
                'success' => false,
                'message' => $data['message'] ?? __( 'Failed to check status from Live webhost.', 'skaaai' ),
            ];
        }

        return [
            'success'  => true,
            'statuses' => $data['statuses'],
        ];
    }

    /**
     * Lấy báo cáo đối soát (Diff Preview) so sánh bài viết cục bộ và bài viết trên Live
     *
     * @param int $post_id ID bài viết cục bộ
     * @return array
     */
    public static function get_post_diff( int $post_id ): array {
        $post = get_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) ) {
            return [
                'success' => false,
                'message' => __( 'Local post not found.', 'skaaai' ),
            ];
        }

        $remote_url   = esc_url_raw( Core::get_setting( 'skaaai_remote_url', '' ) );
        $remote_token = sanitize_text_field( Core::get_setting( 'skaaai_remote_token', '' ) );

        if ( empty( $remote_url ) || empty( $remote_token ) ) {
            return [
                'success' => false,
                'message' => __( 'Remote website is not paired. Please configure pairing in Skaaa Bridge settings.', 'skaaai' ),
            ];
        }

        $uuid = get_post_meta( $post->ID, '_skaaa_uuid', true );
        $params = [];
        if ( ! empty( $uuid ) ) {
            $params['uuid'] = $uuid;
        } else {
            $params['slug']      = $post->post_name;
            $params['post_type'] = $post->post_type;
        }

        $endpoint = add_query_arg( $params, rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/export-post' );
        $response = wp_remote_get( $endpoint, [
            'timeout'   => 15,
            'headers'   => [
                'X-Skaaai-Token' => $remote_token,
                'Accept'         => 'application/json',
            ],
            'sslverify' => false,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data['success'] ) || empty( $data['post'] ) ) {
            return [
                'success' => false,
                'message' => $data['message'] ?? __( 'Post not found on Live webhost.', 'skaaai' ),
            ];
        }

        $remote_data = $data['post'];

        $local_modified_ts  = (int) get_post_modified_time( 'U', true, $post->ID );
        $remote_modified_ts = ! empty( $remote_data['modified_gmt'] ) ? (int) strtotime( $remote_data['modified_gmt'] ) : 0;

        $local_thumb_id = get_post_thumbnail_id( $post->ID );
        $local_thumb    = $local_thumb_id ? wp_get_attachment_image_url( $local_thumb_id, 'thumbnail' ) : '';
        $remote_thumb   = $remote_data['featured_image']['url'] ?? '';

        $local_blocks  = parse_blocks( $post->post_content );
        $remote_blocks = parse_blocks( $remote_data['content'] ?? '' );

        $count_blocks = function( $blocks ) {
            $count = 0;
            foreach ( $blocks as $b ) {
                if ( ! empty( $b['blockName'] ) || ! empty( trim( $b['innerHTML'] ?? '' ) ) ) {
                    $count++;
                }
            }
            return $count;
        };

        $local_block_count  = $count_blocks( $local_blocks );
        $remote_block_count = $count_blocks( $remote_blocks );

        return [
            'success' => true,
            'diff'    => [
                'post_id' => $post->ID,
                'local'   => [
                    'title'          => $post->post_title,
                    'slug'           => $post->post_name,
                    'modified'       => get_post_modified_time( 'Y-m-d H:i:s', false, $post->ID ),
                    'modified_ts'    => $local_modified_ts,
                    'featured_image' => $local_thumb,
                    'block_count'    => $local_block_count,
                    'content_length' => strlen( $post->post_content ),
                ],
                'remote'  => [
                    'title'          => $remote_data['title'],
                    'slug'           => $remote_data['slug'],
                    'modified'       => $remote_data['modified'],
                    'modified_ts'    => $remote_modified_ts,
                    'featured_image' => $remote_thumb,
                    'block_count'    => $remote_block_count,
                    'content_length' => strlen( $remote_data['content'] ?? '' ),
                    'permalink'      => $remote_data['permalink'] ?? '',
                ],
                'comparison' => [
                    'title_diff'      => ( $post->post_title !== $remote_data['title'] ),
                    'image_diff'      => ( (bool) $local_thumb !== (bool) $remote_thumb ),
                    'blocks_diff'     => ( $local_block_count !== $remote_block_count ),
                    'time_diff_sec'   => ( $remote_modified_ts - $local_modified_ts ),
                    'is_remote_newer' => ( $remote_modified_ts > ( $local_modified_ts + 2 ) ),
                ],
            ],
        ];
    }
}
