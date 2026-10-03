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
     *
     * @param string $content
     * @param string $origin_url
     * @return string
     */
    public static function sideload_remote_images( string $content, string $origin_url ): string {
        if ( empty( $origin_url ) ) {
            return $content;
        }

        $origin_clean = rtrim( $origin_url, '/' );

        // Tìm tất cả các link ảnh (cả tuyệt đối lẫn tương đối và có escape gạch chéo)
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
                $full_download_url = $origin_clean . $clean_url;
            } else {
                $full_download_url = $clean_url;
            }

            // Chỉ tải các ảnh xuất phát từ domain origin của máy local
            if ( ! str_starts_with( $full_download_url, $origin_clean ) ) {
                continue;
            }

            // Tải file tạm về server
            $tmp_file = download_url( $full_download_url );
            if ( is_wp_error( $tmp_file ) ) {
                continue;
            }

            $file_array = [
                'name'     => basename( parse_url( $full_download_url, PHP_URL_PATH ) ),
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
                $content = str_replace( $raw_url, $new_url, $content );
                $content = str_replace( $clean_url, $new_url, $content );
                $content = str_replace( str_replace( '/', '\\/', $clean_url ), str_replace( '/', '\\/', $new_url ), $content );
            }
        }

        return $content;
    }

    /**
     * Tiếp nhận và lưu trữ file media gửi trực tiếp từ máy Sender
     *
     * @param array $params Tham số gửi từ Sender
     * @return array
     */
    public static function handle_incoming_media( array $params ): array {
        $filename  = sanitize_file_name( $params['filename'] ?? '' );
        $file_data = $params['file_data'] ?? '';
        $post_id   = absint( $params['post_id'] ?? 0 );

        if ( empty( $filename ) || empty( $file_data ) ) {
            return [
                'success' => false,
                'message' => __( 'Missing filename or file data.', 'skaaai' ),
            ];
        }

        $decoded_data = base64_decode( $file_data );
        if ( false === $decoded_data ) {
            return [
                'success' => false,
                'message' => __( 'Failed to decode base64 file data.', 'skaaai' ),
            ];
        }

        // Tải file vào thư mục uploads của WordPress
        $upload = wp_upload_bits( $filename, null, $decoded_data );
        if ( ! empty( $upload['error'] ) ) {
            return [
                'success' => false,
                'message' => $upload['error'],
            ];
        }

        $file_path = $upload['file'];
        $file_url  = $upload['url'];
        $file_type = wp_check_filetype( $filename, null );

        // Tạo attachment post trong Media Library
        $attachment = [
            'post_mime_type' => $file_type['type'] ?: 'image/png',
            'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'guid'           => $file_url,
        ];

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );
        if ( ! is_wp_error( $attach_id ) ) {
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
            wp_update_attachment_metadata( $attach_id, $attach_data );
        } else {
            $attach_id = 0;
        }

        return [
            'success'   => true,
            'url'       => $file_url,
            'id'        => $attach_id,
            'file_path' => $file_path,
        ];
    }

    /**
     * Quét nội dung bài viết ở Local, đọc file ảnh trên ổ đĩa và đẩy thẳng lên Live qua REST API
     *
     * @param string $content Nội dung bài viết
     * @param string $remote_url URL Live Webhost
     * @param string $remote_token Secret token
     * @return string Nội dung bài viết đã hoán đổi link ảnh Live
     */
    public static function prepare_and_sync_local_media( string $content, string $remote_url, string $remote_token ): string {
        if ( empty( $content ) || empty( $remote_url ) || empty( $remote_token ) ) {
            return $content;
        }

        // Tìm tất cả các link ảnh uploads trong content (cả dạng URL đầy đủ hoặc dạng tương đối /wp-content/uploads/...)
        $pattern = '~(?:https?://[^"\'\s]+?)?(?:/|\\\\/)+wp-content(?:/|\\\\/)+uploads(?:/|\\\\/)+([^"\'\s]+?\.(?:jpg|jpeg|png|gif|webp|svg))~i';
        if ( ! preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
            return $content;
        }

        $upload_dir = wp_upload_dir();
        $basedir    = $upload_dir['basedir'];

        $processed_files = [];

        foreach ( $matches as $match ) {
            $raw_match = $match[0];
            $rel_path  = $match[1];
            $clean_rel = str_replace( [ '\/', '\\' ], '/', $rel_path );
            $local_path = $basedir . '/' . ltrim( $clean_rel, '/' );

            // Nếu không tìm thấy trong uploads hiện tại, tìm trong wp-content/uploads
            if ( ! file_exists( $local_path ) ) {
                $alt_path = ABSPATH . 'wp-content/uploads/' . ltrim( $clean_rel, '/' );
                if ( file_exists( $alt_path ) ) {
                    $local_path = $alt_path;
                }
            }

            // Nếu vẫn không thấy, quét các site Local lân cận (hỗ trợ trường hợp làm việc đa site local)
            if ( ! file_exists( $local_path ) && defined( 'ABSPATH' ) ) {
                $sibling_matches = glob( dirname( ABSPATH, 2 ) . '/*/app/public/wp-content/uploads/' . ltrim( $clean_rel, '/' ) );
                if ( ! empty( $sibling_matches[0] ) && file_exists( $sibling_matches[0] ) ) {
                    $local_path = $sibling_matches[0];
                }
            }

            if ( ! file_exists( $local_path ) || ! is_readable( $local_path ) ) {
                continue;
            }

            if ( isset( $processed_files[ $clean_rel ] ) ) {
                $remote_info = $processed_files[ $clean_rel ];
            } else {
                $file_contents = file_get_contents( $local_path );
                if ( empty( $file_contents ) ) {
                    continue;
                }

                $filename = basename( $clean_rel );
                $upload_endpoint = rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/upload-media';

                $response = wp_remote_post( $upload_endpoint, [
                    'timeout'   => 45,
                    'headers'   => [
                        'X-Skaaai-Token' => $remote_token,
                        'Content-Type'   => 'application/json',
                        'Accept'         => 'application/json',
                    ],
                    'body'      => wp_json_encode( [
                        'filename'  => $filename,
                        'file_data' => base64_encode( $file_contents ),
                    ] ),
                    'sslverify' => false,
                ] );

                if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                    continue;
                }

                $res_body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( empty( $res_body['success'] ) || empty( $res_body['url'] ) ) {
                    continue;
                }

                $remote_info = [
                    'url' => $res_body['url'],
                    'id'  => $res_body['id'] ?? 0,
                ];
                $processed_files[ $clean_rel ] = $remote_info;
            }

            // Hoán đổi URL mới vào nội dung
            $new_remote_url = $remote_info['url'];
            $new_remote_escaped = str_replace( '/', '\\/', $new_remote_url );

            $content = str_replace( $raw_match, $new_remote_url, $content );
            $raw_escaped = str_replace( '/', '\\/', $raw_match );
            $content = str_replace( $raw_escaped, $new_remote_escaped, $content );

            // Thay thế cả dạng JSON attribute
            $content = str_replace( '"/wp-content/uploads/' . $clean_rel . '"', '"' . $new_remote_url . '"', $content );
            $content = str_replace( '"\\/wp-content\\/uploads\\/' . str_replace( '/', '\\/', $clean_rel ) . '"', '"' . $new_remote_escaped . '"', $content );
        }

        return $content;
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
        $synced_content = self::prepare_and_sync_local_media( $post->post_content, $remote_url, $remote_token );

        $payload = [
            'uuid'          => $uuid,
            'title'         => $post->post_title,
            'content'       => $synced_content,
            'post_type'     => $post->post_type,
            'post_status'   => $post->post_status,
            'slug'          => $post->post_name,
            'origin_url'    => site_url(),
            'last_modified' => get_post_modified_time( 'U', true, $post_id ),
            'force'         => $force,
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
}

