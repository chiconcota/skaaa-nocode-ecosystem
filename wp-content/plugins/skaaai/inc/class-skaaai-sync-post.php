<?php
/**
 * Lớp Xử Lý Tiếp Nhận & Đồng Bộ Bài Viết (Skaaai_Sync_Post)
 *
 * Chịu trách nhiệm:
 * 1. Định danh bài viết theo skaaa_uuid (triệt tiêu xung đột ID tự tăng).
 * 2. Tự động hoán đổi Domain máy local thành Domain máy live.
 * 3. Tự động tải ảnh (Sideload Media) về Media Library của hosting.
 * 4. Tự động lưu WordPress Revision trước khi cập nhật.
 * 5. Kích hoạt bộ biên dịch Tailwind JIT trên server để đảm bảo CSS chuẩn 100%.
 *
 * @package Skaaai
 * @version 1.0.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Post {

    /**
     * Tiếp nhận payload bài viết từ máy Local và ghi nhận trên hosting
     *
     * @param array $payload Dữ liệu bài viết gửi từ Sender
     * @return array
     */
    public static function process_incoming_post( array $payload ): array {
        $uuid        = sanitize_text_field( $payload['uuid'] ?? '' );
        $title       = sanitize_text_field( $payload['title'] ?? __( 'Untitled Post', 'skaaai' ) );
        $raw_content = $payload['content'] ?? '';
        $post_type   = sanitize_key( $payload['post_type'] ?? 'post' );
        $post_status = sanitize_key( $payload['post_status'] ?? 'publish' );
        $origin_url  = esc_url_raw( $payload['origin_url'] ?? '' );
        $force_sync  = ! empty( $payload['force'] );

        if ( empty( $uuid ) ) {
            return [
                'success' => false,
                'message' => __( 'Missing required global identifier: skaaa_uuid', 'skaaai' ),
            ];
        }

        // 1. Tìm kiếm bài viết đã tồn tại trên hosting theo skaaa_uuid
        $existing_post_id = self::get_post_id_by_uuid( $uuid );

        // 2. Kiểm tra xung đột thời gian sửa đổi (Conflict Detection) nếu bài đã tồn tại
        if ( $existing_post_id && ! $force_sync && ! empty( $payload['last_modified'] ) ) {
            $remote_modified = get_post_modified_time( 'U', true, $existing_post_id );
            $local_modified  = (int) $payload['last_modified'];

            // Nếu remote mới hơn local quá 5 giây
            if ( $remote_modified > ( $local_modified + 5 ) ) {
                return [
                    'success'  => false,
                    'conflict' => true,
                    'message'  => __( 'Conflict detected: The live website has a newer revision of this post.', 'skaaai' ),
                    'post_id'  => $existing_post_id,
                ];
            }
        }

        // 3. Hoán đổi tên miền (Domain Replacement)
        $processed_content = self::rewrite_domain_urls( $raw_content, $origin_url );

        // 4. Tải và nội địa hóa ảnh (Asset Sideloading)
        $processed_content = self::sideload_remote_images( $processed_content, $origin_url );

        // 5. Chuẩn bị dữ liệu bài viết
        $post_args = [
            'post_title'   => $title,
            'post_content' => $processed_content,
            'post_status'  => $post_status,
            'post_type'    => $post_type,
        ];

        if ( ! empty( $payload['slug'] ) ) {
            $post_args['post_name'] = sanitize_title( $payload['slug'] );
        }

        if ( $existing_post_id ) {
            // Tự động tạo WordPress Revision trước khi ghi đè
            wp_save_post_revision( $existing_post_id );

            $post_args['ID'] = $existing_post_id;
            $post_id = wp_update_post( $post_args, true );
        } else {
            $post_id = wp_insert_post( $post_args, true );
            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_skaaa_uuid', $uuid );
            }
        }

        if ( is_wp_error( $post_id ) ) {
            return [
                'success' => false,
                'message' => $post_id->get_error_message(),
            ];
        }

        // Cập nhật lại uuid đảm bảo nhất quán
        update_post_meta( $post_id, '_skaaa_uuid', $uuid );
        update_post_meta( $post_id, '_skaaa_last_synced', current_time( 'mysql' ) );

        // 6. Kích hoạt biên dịch Tailwind JIT CSS trên hosting
        do_action( 'skaaa_after_post_synced', $post_id, $processed_content );

        return [
            'success'   => true,
            'message'   => __( 'Post successfully synchronized and published.', 'skaaai' ),
            'post_id'   => $post_id,
            'permalink' => get_permalink( $post_id ),
            'uuid'      => $uuid,
        ];
    }

    /**
     * Tìm bài viết dựa vào _skaaa_uuid
     *
     * @param string $uuid
     * @return int|null
     */
    public static function get_post_id_by_uuid( string $uuid ): ?int {
        global $wpdb;
        $post_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_skaaa_uuid' AND meta_value = %s LIMIT 1",
            $uuid
        ) );

        return $post_id ? (int) $post_id : null;
    }

    /**
     * Hoán đổi Domain của Localhost thành Domain của Web Live
     *
     * @param string $content Nội dung bài viết
     * @param string $origin_url URL nguồn máy local
     * @return string
     */
    public static function rewrite_domain_urls( string $content, string $origin_url ): string {
        if ( empty( $origin_url ) ) {
            return $content;
        }

        $origin_clean = rtrim( $origin_url, '/' );
        $target_clean = rtrim( site_url(), '/' );

        if ( $origin_clean === $target_clean ) {
            return $content;
        }

        // Thay thế các biến thể URL
        $content = str_replace( $origin_clean, $target_clean, $content );

        // Thay thế cả dạng URL có escape gạch chéo JSON: \/
        $origin_escaped = str_replace( '/', '\/', $origin_clean );
        $target_escaped = str_replace( '/', '\/', $target_clean );
        $content = str_replace( $origin_escaped, $target_escaped, $content );

        return $content;
    }

    /**
     * Tự động tải hình ảnh từ URL nguồn và lưu vào Media Library của Live host
     *
     * @param string $content
     * @param string $origin_url
     * @return string
     */
    public static function sideload_remote_images( string $content, string $origin_url ): string {
        if ( empty( $origin_url ) ) {
            return $content;
        }

        // Tìm tất cả các link ảnh có đuôi jpg, jpeg, png, gif, webp, svg
        $pattern = '/(https?:\/\/[^\s"\']+\.(?:jpg|jpeg|png|gif|webp|svg))/i';
        if ( ! preg_match_all( $pattern, $content, $matches ) ) {
            return $content;
        }

        $urls = array_unique( $matches[1] );
        $origin_clean = rtrim( $origin_url, '/' );

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        foreach ( $urls as $image_url ) {
            // Chỉ tải các ảnh xuất phát từ domain origin của máy local
            if ( ! str_starts_with( $image_url, $origin_clean ) ) {
                continue;
            }

            // Tải file tạm về server
            $tmp_file = download_url( $image_url );
            if ( is_wp_error( $tmp_file ) ) {
                continue;
            }

            $file_array = [
                'name'     => basename( parse_url( $image_url, PHP_URL_PATH ) ),
                'tmp_name' => $tmp_file,
            ];

            // Nạp vào Media Library
            $attachment_id = media_handle_sideload( $file_array, 0 );
            if ( is_wp_error( $attachment_id ) ) {
                @unlink( $tmp_file );
                continue;
            }

            // Lấy URL mới trên hosting và hoán đổi trong nội dung
            $new_url = wp_get_attachment_url( $attachment_id );
            if ( $new_url ) {
                $content = str_replace( $image_url, $new_url, $content );
            }
        }

        return $content;
    }
}
