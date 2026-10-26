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

        // 1.1. Fallback: Nếu chưa có UUID, tìm kiếm theo slug và post_type để ghép đôi (chống sinh bài -2)
        if ( ! $existing_post_id && ! empty( $payload['slug'] ) ) {
            $existing_post_id = self::get_post_id_by_slug( sanitize_title( $payload['slug'] ), $post_type );
            if ( $existing_post_id ) {
                update_post_meta( $existing_post_id, '_skaaa_uuid', $uuid );
            }
        }

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

        // 3. Tải và nội địa hóa ảnh (Asset Sideloading) TRƯỚC khi hoán đổi tên miền
        $processed_content = self::sideload_remote_images( $raw_content, $origin_url );

        // 4. Hoán đổi tên miền còn lại (Domain Replacement)
        $processed_content = self::rewrite_domain_urls( $processed_content, $origin_url );

        // 4.1. Chuẩn hóa Table Prefix cho các block (ví dụ skaaaaa-builder/loop sourceTable)
        $processed_content = self::rewrite_table_prefixes( $processed_content );

        // 5. Chuẩn bị dữ liệu bài viết (bọc wp_slash chống lỗi stripslashes của WP Core nuốt mất dấu \)
        $post_args = [
            'post_title'   => wp_slash( $title ),
            'post_content' => wp_slash( $processed_content ),
            'post_status'  => $post_status,
            'post_type'    => $post_type,
        ];

        if ( ! empty( $payload['slug'] ) ) {
            $post_args['post_name'] = sanitize_title( $payload['slug'] );
        }

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

        // Khôi phục lại bộ lọc KSES và ngữ cảnh người dùng
        if ( $prev_user_id !== get_current_user_id() ) {
            wp_set_current_user( $prev_user_id );
        }
        if ( function_exists( 'kses_init_filters' ) ) {
            kses_init_filters();
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

        // Xử lý và đồng bộ ảnh đại diện (Featured Image)
        $featured_image_url = $payload['featured_image_url'] ?? '';
        Sync_Media::attach_featured_image( $post_id, $featured_image_url, $origin_url );

        // 6. Kích hoạt biên dịch Tailwind JIT CSS trên hosting
        do_action( 'skaaa_after_post_synced', $post_id, $processed_content );

        // 7. Tự động xóa sạch Cache hosting cho bài viết (Safeguard Layer 4)
        Sync_Cache::invalidate( $post_id );

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
     * Tìm bài viết dựa vào post_name (slug) và post_type để ghép đôi bài cũ
     *
     * @param string $slug
     * @param string $post_type
     * @return int|null
     */
    public static function get_post_id_by_slug( string $slug, string $post_type = 'post' ): ?int {
        global $wpdb;
        $post_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND post_status != 'trash' LIMIT 1",
            $slug,
            $post_type
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
     * Chuẩn hóa tiền tố bảng database (sourceTable) trong nội dung và thuộc tính Gutenberg
     * Đảm bảo tương thích khi di chuyển dữ liệu giữa các máy có $table_prefix khác nhau (vd: wp_ vs wpxi_)
     *
     * @param string $content
     * @return string
     */
    public static function rewrite_table_prefixes( string $content ): string {
        global $wpdb;
        if ( empty( $content ) ) {
            return $content;
        }

        // Thay thế các mẫu "sourceTable":"...skaaa_data_abc" thành "sourceTable":"{wpdb->prefix}skaaa_data_abc"
        return preg_replace(
            '/(\\\\?"sourceTable\\\\?"\s*:\s*\\\\?")(?:[a-zA-Z0-9]+_)?skaaa_data_([a-zA-Z0-9_]+)(\\\\?")/',
            '${1}' . $wpdb->prefix . 'skaaa_data_${2}${3}',
            $content
        );
    }

    /**
     * Tự động tải hình ảnh từ URL nguồn và lưu vào Media Library của Live host
     * Ủy quyền sang Sync_Media::sideload_remote_images
     *
     * @param string $content
     * @param string $origin_url
     * @return string
     */
    public static function sideload_remote_images( string $content, string $origin_url ): string {
        return Sync_Media::sideload_remote_images( $content, $origin_url );
    }

    /**
     * Tiếp nhận và lưu trữ file media gửi trực tiếp từ máy Sender
     * Ủy quyền sang Sync_Media::handle_incoming_media
     *
     * @param array $params Tham số gửi từ Sender
     * @return array
     */
    public static function handle_incoming_media( array $params ): array {
        return Sync_Media::handle_incoming_media( $params );
    }

    /**
     * Quét nội dung bài viết ở Local, đọc file ảnh trên ổ đĩa và đẩy thẳng lên Live qua REST API
     * Ủy quyền sang Sync_Media::prepare_and_sync_local_media
     *
     * @param string $content Nội dung bài viết
     * @param string $remote_url URL Live Webhost
     * @param string $remote_token Secret token
     * @return string Nội dung bài viết đã hoán đổi link ảnh Live
     */
    public static function prepare_and_sync_local_media( string $content, string $remote_url, string $remote_token ): string {
        return Sync_Media::prepare_and_sync_local_media( $content, $remote_url, $remote_token );
    }

    /**
     * Tự động gán skaaa_uuid cho bài viết nếu chưa có (hook wp_insert_post)
     *
     * @param int      $post_id ID bài viết
     * @param \WP_Post $post    Đối tượng bài viết
     * @param bool     $update  Có phải thao tác cập nhật hay không
     * @return void
     */
    public static function ensure_post_uuid( int $post_id, \WP_Post $post, bool $update ): void {
        // Bỏ qua nếu là autosave, revision, auto-draft hoặc trash
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( in_array( $post->post_status, [ 'auto-draft', 'trash' ], true ) ) {
            return;
        }

        $existing_uuid = get_post_meta( $post_id, '_skaaa_uuid', true );
        if ( empty( $existing_uuid ) ) {
            $new_uuid = wp_generate_uuid4();
            update_post_meta( $post_id, '_skaaa_uuid', $new_uuid );
        }
    }

    /**
     * Xác định trạng thái đồng bộ của bài viết
     *
     * @param int $post_id ID bài viết
     * @return array
     */
    public static function get_sync_status( int $post_id ): array {
        $uuid             = get_post_meta( $post_id, '_skaaa_uuid', true );
        $last_synced      = get_post_meta( $post_id, '_skaaa_last_synced', true );
        $remote_permalink = get_post_meta( $post_id, '_skaaa_remote_permalink', true );
        $remote_post_id   = get_post_meta( $post_id, '_skaaa_remote_post_id', true );

        if ( empty( $uuid ) ) {
            $uuid = wp_generate_uuid4();
            update_post_meta( $post_id, '_skaaa_uuid', $uuid );
        }

        if ( empty( $last_synced ) ) {
            return [
                'status'           => 'not_synced',
                'label'            => __( 'Not Synced', 'skaaai' ),
                'badge_icon'       => '⚪',
                'last_synced'      => null,
                'remote_permalink' => '',
                'remote_post_id'   => null,
                'uuid'             => $uuid,
            ];
        }

        $last_synced_time = strtotime( $last_synced );
        $modified_time    = get_post_modified_time( 'U', true, $post_id );

        // Nếu modified_time lớn hơn last_synced_time quá 2 giây thì là local ahead
        if ( $modified_time > ( $last_synced_time + 2 ) ) {
            return [
                'status'           => 'ahead',
                'label'            => __( 'Local Ahead', 'skaaai' ),
                'badge_icon'       => '⬆️',
                'last_synced'      => $last_synced,
                'remote_permalink' => $remote_permalink ?: '',
                'remote_post_id'   => $remote_post_id ? (int) $remote_post_id : null,
                'uuid'             => $uuid,
            ];
        }

        return [
            'status'           => 'synced',
            'label'            => __( 'Synced', 'skaaai' ),
            'badge_icon'       => '🟢',
            'last_synced'      => $last_synced,
            'remote_permalink' => $remote_permalink ?: '',
            'remote_post_id'   => $remote_post_id ? (int) $remote_post_id : null,
            'uuid'             => $uuid,
        ];
    }

    /**
     * Đẩy bài viết từ Localhost sang máy Live Webhost
     *
     * @param int  $post_id ID bài viết trên Localhost
     * @param bool $force   Bỏ qua cảnh báo xung đột (Force overwrite)
     * @return array
     */
    public static function push_post_to_remote( int $post_id, bool $force = false ): array {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return [
                'success' => false,
                'message' => __( 'Post not found.', 'skaaai' ),
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

        // Đảm bảo UUID tồn tại
        $uuid = get_post_meta( $post_id, '_skaaa_uuid', true );
        if ( empty( $uuid ) ) {
            $uuid = wp_generate_uuid4();
            update_post_meta( $post_id, '_skaaa_uuid', $uuid );
        }

        // 1. Quét và đồng bộ media cục bộ nhúng trong bài viết sang Live host trực tiếp
        Sync_Media::clear_session_cache();
        $synced_content = self::prepare_and_sync_local_media( $post->post_content, $remote_url, $remote_token );

        $thumb_id         = get_post_thumbnail_id( $post_id );
        $thumb_url        = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '';
        $synced_thumb_url = '';
        if ( ! empty( $thumb_url ) ) {
            $synced_thumb_url = Sync_Media::prepare_and_sync_local_media( $thumb_url, $remote_url, $remote_token );
        }

        $payload = [
            'uuid'               => $uuid,
            'title'              => $post->post_title,
            'content'            => $synced_content,
            'featured_image_url' => $synced_thumb_url,
            'post_type'          => $post->post_type,
            'post_status'        => $post->post_status,
            'slug'               => $post->post_name,
            'origin_url'         => site_url(),
            'last_modified'      => get_post_modified_time( 'U', true, $post_id ),
            'force'              => $force,
        ];

        $endpoint = rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/push-post';
        $response = wp_remote_post( $endpoint, [
            'timeout'   => 30,
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

        if ( 409 === $status_code ) {
            return [
                'success'  => false,
                'conflict' => true,
                'message'  => $data['message'] ?? __( 'Conflict detected: The live website has a newer revision of this post.', 'skaaai' ),
                'post_id'  => $post_id,
            ];
        }

        if ( ! empty( $data['success'] ) ) {
            // Cập nhật metadata đồng bộ thành công trên Localhost
            update_post_meta( $post_id, '_skaaa_last_synced', current_time( 'mysql' ) );
            if ( ! empty( $data['permalink'] ) ) {
                update_post_meta( $post_id, '_skaaa_remote_permalink', esc_url_raw( $data['permalink'] ) );
            }
            if ( ! empty( $data['post_id'] ) ) {
                update_post_meta( $post_id, '_skaaa_remote_post_id', (int) $data['post_id'] );
            }

            return [
                'success'        => true,
                'message'        => $data['message'] ?? __( 'Post pushed to Live webhost successfully!', 'skaaai' ),
                'permalink'      => $data['permalink'] ?? '',
                'remote_post_id' => $data['post_id'] ?? null,
                'post_id'        => $post_id,
                'last_synced'    => current_time( 'mysql' ),
                'sync_status'    => 'synced',
            ];
        }

        $err_msg = $data['message'] ?? sprintf( __( 'Remote error (HTTP %d).', 'skaaai' ), $status_code );
        return [
            'success' => false,
            'message' => $err_msg,
        ];
    }

    /**
     * Kéo một bài viết từ Live về ghi đè an toàn lên bài viết Localhost
     * (Ủy quyền xử lý cho lớp Skaaai\Sync_Pull)
     *
     * @param int $post_id ID bài viết trên Localhost
     * @return array
     */
    public static function pull_post_from_remote( int $post_id ): array {
        return Sync_Pull::pull_post_from_remote( $post_id );
    }
}

