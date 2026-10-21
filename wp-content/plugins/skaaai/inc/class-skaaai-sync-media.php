<?php
/**
 * Lớp Xử Lý Đồng Bộ & Chống Trùng Lặp Media (Skaaai_Sync_Media)
 *
 * Chịu trách nhiệm:
 * 1. Tiếp nhận và lưu trữ file nhị phân với cơ chế Idempotent (Chống trùng lặp qua MD5 Hash).
 * 2. Tái sử dụng Attachment hiện có nếu file cùng nội dung đã tồn tại trên Live.
 * 3. Ghi đè tệp tin vật lý thay vì sinh hậu tố -1, -2, -33 khi cùng tên file.
 * 4. Đồng bộ toàn bộ Thư viện Media (Standalone Media Library) sang máy chủ Live.
 * 5. Cung cấp bộ nhớ đệm Static Session Cache chống gửi trùng lặp file trong một phiên đồng bộ.
 * 6. Tiện ích dọn dẹp các tệp media rác trùng lặp trên hosting.
 *
 * @package Skaaai
 * @version 1.6.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Media {

    /**
     * Bộ nhớ đệm Static Cache trong một phiên request/sync
     * Key: relative_path hoặc file_path => Value: ['url' => ..., 'id' => ...]
     *
     * @var array
     */
    private static array $session_cache = [];

    /**
     * Xóa sạch Session Cache
     */
    public static function clear_session_cache(): void {
        self::$session_cache = [];
    }

    /**
     * Xử lý request tải file media từ Sender sang Receiver (Live Host)
     * Đảm bảo tính Idempotent 100%: Không nhân bản file nếu hash trùng.
     *
     * @param array $params Tham số gửi từ Sender
     * @return array
     */
    public static function handle_incoming_media( array $params ): array {
        $filename      = sanitize_file_name( $params['filename'] ?? '' );
        $file_data     = $params['file_data'] ?? '';
        $file_hash     = sanitize_text_field( $params['file_hash'] ?? '' );
        $relative_path = sanitize_text_field( $params['relative_path'] ?? '' );
        $post_id       = absint( $params['post_id'] ?? 0 );

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

        // Tính toán hash MD5 nếu sender chưa gửi
        if ( empty( $file_hash ) ) {
            $file_hash = md5( $decoded_data );
        }

        // BƯỚC 1: Kiểm tra xem đã có attachment nào trên Live có cùng _skaaa_file_hash chưa
        $existing_by_hash = self::find_attachment_by_hash( $file_hash );
        if ( $existing_by_hash ) {
            $existing_url = wp_get_attachment_url( $existing_by_hash->ID );
            if ( $existing_url ) {
                return [
                    'success'   => true,
                    'url'       => $existing_url,
                    'id'        => $existing_by_hash->ID,
                    'reused'    => true,
                    'file_path' => get_attached_file( $existing_by_hash->ID ),
                    'message'   => __( 'Reused existing attachment with matching hash.', 'skaaai' ),
                ];
            }
        }

        // BƯỚC 2: Kiểm tra xem có attachment nào cùng tên file hoặc relative path chưa
        $existing_by_name = self::find_attachment_by_filename( $filename, $relative_path );
        if ( $existing_by_name ) {
            $attached_file_path = get_attached_file( $existing_by_name->ID );

            if ( $attached_file_path && file_exists( $attached_file_path ) ) {
                $disk_hash = md5_file( $attached_file_path );

                // Nếu nội dung trên đĩa hoàn toàn khớp với hash gửi sang
                if ( $disk_hash === $file_hash ) {
                    update_post_meta( $existing_by_name->ID, '_skaaa_file_hash', $file_hash );
                    if ( ! empty( $relative_path ) ) {
                        update_post_meta( $existing_by_name->ID, '_skaaa_source_file', $relative_path );
                    }

                    return [
                        'success'   => true,
                        'url'       => wp_get_attachment_url( $existing_by_name->ID ),
                        'id'        => $existing_by_name->ID,
                        'reused'    => true,
                        'file_path' => $attached_file_path,
                        'message'   => __( 'Reused existing attachment matching filename and disk content.', 'skaaai' ),
                    ];
                }

                // Nếu cùng tên nhưng nội dung thay đổi (người dùng update ảnh mới cùng tên): Ghi đè trực tiếp
                if ( is_writable( $attached_file_path ) ) {
                    file_put_contents( $attached_file_path, $decoded_data );

                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attach_data = wp_generate_attachment_metadata( $existing_by_name->ID, $attached_file_path );
                    wp_update_attachment_metadata( $existing_by_name->ID, $attach_data );

                    update_post_meta( $existing_by_name->ID, '_skaaa_file_hash', $file_hash );
                    if ( ! empty( $relative_path ) ) {
                        update_post_meta( $existing_by_name->ID, '_skaaa_source_file', $relative_path );
                    }

                    return [
                        'success'   => true,
                        'url'       => wp_get_attachment_url( $existing_by_name->ID ),
                        'id'        => $existing_by_name->ID,
                        'overwrote' => true,
                        'file_path' => $attached_file_path,
                        'message'   => __( 'Overwrote existing attachment file without duplicating.', 'skaaai' ),
                    ];
                }
            }
        }

        // BƯỚC 3: Nếu là file hoàn toàn mới, tải vào uploads và tạo attachment mới
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
        if ( ! is_wp_error( $attach_id ) && $attach_id > 0 ) {
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            // Lưu metadata định danh chống trùng lặp
            update_post_meta( $attach_id, '_skaaa_file_hash', $file_hash );
            if ( ! empty( $relative_path ) ) {
                update_post_meta( $attach_id, '_skaaa_source_file', $relative_path );
            }
        } else {
            $attach_id = 0;
        }

        return [
            'success'   => true,
            'url'       => $file_url,
            'id'        => $attach_id,
            'file_path' => $file_path,
            'created'   => true,
        ];
    }

    /**
     * Tra cứu Attachment đã tồn tại theo mã băm MD5
     *
     * @param string $hash
     * @return \WP_Post|null
     */
    public static function find_attachment_by_hash( string $hash ): ?\WP_Post {
        global $wpdb;
        if ( empty( $hash ) ) {
            return null;
        }

        $post_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_skaaa_file_hash' AND meta_value = %s LIMIT 1",
                $hash
            )
        );

        if ( $post_id > 0 ) {
            $post = get_post( $post_id );
            if ( $post && 'attachment' === $post->post_type ) {
                return $post;
            }
        }

        return null;
    }

    /**
     * Tra cứu Attachment đã tồn tại theo tên file hoặc relative path
     *
     * @param string $filename
     * @param string $relative_path
     * @return \WP_Post|null
     */
    public static function find_attachment_by_filename( string $filename, string $relative_path = '' ): ?\WP_Post {
        global $wpdb;

        if ( ! empty( $relative_path ) ) {
            $clean_rel = ltrim( str_replace( [ '\/', '\\' ], '/', $relative_path ), '/' );
            $post_id   = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_skaaa_source_file' AND meta_value = %s LIMIT 1",
                    $clean_rel
                )
            );
            if ( $post_id > 0 ) {
                $post = get_post( $post_id );
                if ( $post && 'attachment' === $post->post_type ) {
                    return $post;
                }
            }
        }

        if ( ! empty( $filename ) ) {
            $like_pattern = '%' . $wpdb->esc_like( $filename );
            $post_id      = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND (meta_value = %s OR meta_value LIKE %s) LIMIT 1",
                    $filename,
                    $like_pattern
                )
            );
            if ( $post_id > 0 ) {
                $post = get_post( $post_id );
                if ( $post && 'attachment' === $post->post_type ) {
                    return $post;
                }
            }
        }

        return null;
    }

    /**
     * Quét nội dung chuỗi ở Local, đọc file ảnh trên ổ đĩa và đẩy thẳng lên Live qua REST API
     * Áp dụng Static Session Cache chống gửi trùng lặp file trong cùng 1 lượt sync.
     *
     * @param string $content      Nội dung văn bản / HTML / JSON
     * @param string $remote_url   URL Live Webhost
     * @param string $remote_token Secret token
     * @return string Nội dung đã hoán đổi link ảnh Live
     */
    public static function prepare_and_sync_local_media( string $content, string $remote_url, string $remote_token ): string {
        if ( empty( $content ) || empty( $remote_url ) || empty( $remote_token ) ) {
            return $content;
        }

        $pattern = '~(?:https?://[^"\'\s]+?)?(?:/|\\\\/)+wp-content(?:/|\\\\/)+uploads(?:/|\\\\/)+([^"\'\s]+?\.(?:jpg|jpeg|png|gif|webp|svg))~i';
        if ( ! preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
            return $content;
        }

        $upload_dir = wp_upload_dir();
        $basedir    = $upload_dir['basedir'];

        foreach ( $matches as $match ) {
            $raw_match = $match[0];
            $rel_path  = $match[1];
            $clean_rel = str_replace( [ '\/', '\\' ], '/', $rel_path );
            $local_path = $basedir . '/' . ltrim( $clean_rel, '/' );

            if ( ! file_exists( $local_path ) ) {
                $alt_path = ABSPATH . 'wp-content/uploads/' . ltrim( $clean_rel, '/' );
                if ( file_exists( $alt_path ) ) {
                    $local_path = $alt_path;
                }
            }

            if ( ! file_exists( $local_path ) && defined( 'ABSPATH' ) ) {
                $sibling_matches = glob( dirname( ABSPATH, 2 ) . '/*/app/public/wp-content/uploads/' . ltrim( $clean_rel, '/' ) );
                if ( ! empty( $sibling_matches[0] ) && file_exists( $sibling_matches[0] ) ) {
                    $local_path = $sibling_matches[0];
                }
            }

            if ( ! file_exists( $local_path ) || ! is_readable( $local_path ) ) {
                continue;
            }

            // Kiểm tra cache tĩnh toàn phiên (Session Cache)
            if ( isset( self::$session_cache[ $clean_rel ] ) ) {
                $remote_info = self::$session_cache[ $clean_rel ];
            } else {
                $file_contents = file_get_contents( $local_path );
                if ( empty( $file_contents ) ) {
                    continue;
                }

                $filename        = basename( $clean_rel );
                $file_hash       = md5( $file_contents );
                $upload_endpoint = rtrim( $remote_url, '/' ) . '/wp-json/' . Sync_Config::REST_NAMESPACE . Sync_Config::ENDPOINT_UPLOAD_MEDIA;

                $response = wp_remote_post( $upload_endpoint, [
                    'timeout'   => Sync_Config::TIMEOUT_MEDIA,
                    'headers'   => [
                        'X-Skaaai-Token' => $remote_token,
                        'Content-Type'   => 'application/json',
                        'Accept'         => 'application/json',
                    ],
                    'body'      => wp_json_encode( [
                        'filename'      => $filename,
                        'file_data'     => base64_encode( $file_contents ),
                        'file_hash'     => $file_hash,
                        'relative_path' => $clean_rel,
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
                self::$session_cache[ $clean_rel ] = $remote_info;
            }

            $new_remote_url     = $remote_info['url'];
            $new_remote_escaped = str_replace( '/', '\\/', $new_remote_url );

            $content = str_replace( $raw_match, $new_remote_url, $content );
            $raw_escaped = str_replace( '/', '\\/', $raw_match );
            $content = str_replace( $raw_escaped, $new_remote_escaped, $content );

            $content = str_replace( '"/wp-content/uploads/' . $clean_rel . '"', '"' . $new_remote_url . '"', $content );
            $content = str_replace( '"\\/wp-content\\/uploads\\/' . str_replace( '/', '\\/', $clean_rel ) . '"', '"' . $new_remote_escaped . '"', $content );
        }

        return $content;
    }

    /**
     * Đồng bộ toàn bộ Thư viện Media (Standalone Media Library) từ Local lên Live
     *
     * @param string $remote_url   URL Live Webhost
     * @param string $remote_token Secret token
     * @param int    $limit        Số lượng file tối đa quét
     * @return array Thống kê kết quả đồng bộ
     */
    public static function sync_media_library( string $remote_url, string $remote_token, int $limit = 100 ): array {
        $stats = [
            'total'     => 0,
            'synced'    => 0,
            'reused'    => 0,
            'failed'    => 0,
        ];

        if ( empty( $remote_url ) || empty( $remote_token ) ) {
            return $stats;
        }

        $attachments = get_posts( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $limit,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ] );

        if ( empty( $attachments ) ) {
            return $stats;
        }

        $stats['total'] = count( $attachments );
        $upload_dir     = wp_upload_dir();
        $basedir        = $upload_dir['basedir'];

        foreach ( $attachments as $att ) {
            $attached_file = get_post_meta( $att->ID, '_wp_attached_file', true );
            if ( empty( $attached_file ) ) {
                continue;
            }

            $local_path = $basedir . '/' . ltrim( $attached_file, '/' );
            if ( ! file_exists( $local_path ) ) {
                $alt_path = ABSPATH . 'wp-content/uploads/' . ltrim( $attached_file, '/' );
                if ( file_exists( $alt_path ) ) {
                    $local_path = $alt_path;
                } else {
                    $stats['failed']++;
                    continue;
                }
            }

            $clean_rel = str_replace( [ '\/', '\\' ], '/', $attached_file );

            // Nếu đã đồng bộ trong phiên này, ghi nhận reused
            if ( isset( self::$session_cache[ $clean_rel ] ) ) {
                $stats['reused']++;
                continue;
            }

            $file_contents = file_get_contents( $local_path );
            if ( empty( $file_contents ) ) {
                $stats['failed']++;
                continue;
            }

            $filename        = basename( $clean_rel );
            $file_hash       = md5( $file_contents );
            $upload_endpoint = rtrim( $remote_url, '/' ) . '/wp-json/' . Sync_Config::REST_NAMESPACE . Sync_Config::ENDPOINT_UPLOAD_MEDIA;

            $response = wp_remote_post( $upload_endpoint, [
                'timeout'   => Sync_Config::TIMEOUT_MEDIA,
                'headers'   => [
                    'X-Skaaai-Token' => $remote_token,
                    'Content-Type'   => 'application/json',
                    'Accept'         => 'application/json',
                ],
                'body'      => wp_json_encode( [
                    'filename'      => $filename,
                    'file_data'     => base64_encode( $file_contents ),
                    'file_hash'     => $file_hash,
                    'relative_path' => $clean_rel,
                ] ),
                'sslverify' => false,
            ] );

            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                $stats['failed']++;
                continue;
            }

            $res_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( empty( $res_body['success'] ) ) {
                $stats['failed']++;
                continue;
            }

            self::$session_cache[ $clean_rel ] = [
                'url' => $res_body['url'] ?? '',
                'id'  => $res_body['id'] ?? 0,
            ];

            if ( ! empty( $res_body['reused'] ) ) {
                $stats['reused']++;
            } else {
                $stats['synced']++;
            }
        }

        return $stats;
    }

    /**
     * Tự động tải hình ảnh từ URL nguồn và lưu vào Media Library của Live host
     * (Hỗ trợ fallback khi nội dung có URL tuyệt đối từ máy Local)
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

            if ( ! str_starts_with( $full_download_url, $origin_clean ) ) {
                continue;
            }

            $filename = basename( parse_url( $full_download_url, PHP_URL_PATH ) );

            // Kiểm tra xem attachment đã tồn tại chưa trước khi tải về
            $existing = self::find_attachment_by_filename( $filename );
            if ( $existing ) {
                $existing_url = wp_get_attachment_url( $existing->ID );
                if ( $existing_url ) {
                    $content = str_replace( $raw_url, $existing_url, $content );
                    $content = str_replace( $clean_url, $existing_url, $content );
                    $content = str_replace( str_replace( '/', '\\/', $clean_url ), str_replace( '/', '\\/', $existing_url ), $content );
                    continue;
                }
            }

            $tmp_file = download_url( $full_download_url );
            if ( is_wp_error( $tmp_file ) ) {
                continue;
            }

            $file_array = [
                'name'     => $filename,
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
                $content = str_replace( str_replace( '/', '\\/', $clean_url ), str_replace( '/', '\\/', $new_url ), $content );
            }
        }

        return $content;
    }

    /**
     * Dọn dẹp các tệp media rác bị nhân bản trùng lặp trên host đích (-1, -2 ... -33)
     *
     * @return array Danh sách các ID attachment đã xóa và số lượng
     */
    public static function cleanup_duplicate_attachments(): array {
        global $wpdb;

        $attachments = get_posts( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ] );

        $deleted_ids = [];
        $hashes      = [];
        $base_names  = [];

        foreach ( $attachments as $att ) {
            $file_path = get_attached_file( $att->ID );
            if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
                continue;
            }

            $hash = md5_file( $file_path );
            if ( empty( $hash ) ) {
                continue;
            }

            $attached_file = get_post_meta( $att->ID, '_wp_attached_file', true );
            $filename      = basename( $attached_file );

            // Chuẩn hóa tên gốc (loại bỏ hậu tố -1, -2 ... -33)
            // Ví dụ: lytatthanh_logo-12.png -> lytatthanh_logo.png
            $clean_base = preg_replace( '/(-\d+)(\.[a-zA-Z0-9]+)$/', '$2', $filename );

            // Trường hợp 1: Trùng lặp chính xác mã băm nội dung file
            if ( isset( $hashes[ $hash ] ) ) {
                $deleted_ids[] = $att->ID;
                wp_delete_attachment( $att->ID, true );
                continue;
            }

            // Trường hợp 2: Khác mã băm nhưng là hậu tố tự tăng của cùng 1 ảnh gốc
            if ( preg_match( '/-\d+\.[a-zA-Z0-9]+$/', $filename ) && isset( $base_names[ $clean_base ] ) ) {
                $deleted_ids[] = $att->ID;
                wp_delete_attachment( $att->ID, true );
                continue;
            }

            $hashes[ $hash ]           = $att->ID;
            $base_names[ $clean_base ] = $att->ID;
            update_post_meta( $att->ID, '_skaaa_file_hash', $hash );
        }

        return [
            'success'       => true,
            'deleted_count' => count( $deleted_ids ),
            'deleted_ids'   => $deleted_ids,
            'message'       => sprintf( __( 'Cleaned up %d duplicate media attachments.', 'skaaai' ), count( $deleted_ids ) ),
        ];
    }
}
