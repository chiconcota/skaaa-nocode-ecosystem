<?php
/**
 * Lớp Dịch Vụ Xuất Dữ Liệu & Đối Soát Trạng Thái (Skaaai_Export_Service)
 *
 * Chịu trách nhiệm:
 * 1. Trích xuất đầy đủ dữ liệu một bài viết/trang (kèm UUID, nội dung Gutenberg, metadata).
 * 2. Đối soát hàng loạt bài viết để phát hiện trạng thái thay đổi (synced, remote_ahead, local_ahead).
 * 3. Xuất toàn bộ gói cấu hình Hệ sinh thái (Tokens, Organisms, Templates, Workflows).
 *
 * @package Skaaai
 * @version 1.5.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Export_Service {

    /**
     * Xuất dữ liệu một bài viết cụ thể
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    public static function export_post( \WP_REST_Request $request ): array {
        global $wpdb;

        $uuid           = sanitize_text_field( $request->get_param( 'uuid' ) ?? '' );
        $slug           = sanitize_title( $request->get_param( 'slug' ) ?? '' );
        $post_type      = sanitize_key( $request->get_param( 'post_type' ) ?? 'any' );
        $post_id_param  = absint( $request->get_param( 'post_id' ) ?? 0 );
        $target_post_id = null;

        // 1. Tìm theo UUID toàn cục
        if ( ! empty( $uuid ) ) {
            $target_post_id = Sync_Post::get_post_id_by_uuid( $uuid );
        }

        // 2. Fallback tìm theo Slug URL và Post Type
        if ( ! $target_post_id && ! empty( $slug ) ) {
            $target_post_id = Sync_Post::get_post_id_by_slug( $slug, 'any' === $post_type ? 'post' : $post_type );
            if ( ! $target_post_id && 'any' === $post_type ) {
                $target_post_id = Sync_Post::get_post_id_by_slug( $slug, 'page' );
            }
        }

        // 3. Fallback tìm theo Post ID trực tiếp
        if ( ! $target_post_id && $post_id_param > 0 ) {
            $existing = get_post( $post_id_param );
            if ( $existing instanceof \WP_Post ) {
                $target_post_id = $existing->ID;
            }
        }

        if ( ! $target_post_id ) {
            return [
                'success' => false,
                'message' => __( 'Post not found on the remote website.', 'skaaai' ),
            ];
        }

        $post = get_post( $target_post_id );
        if ( ! ( $post instanceof \WP_Post ) ) {
            return [
                'success' => false,
                'message' => __( 'Invalid post object retrieved.', 'skaaai' ),
            ];
        }

        // Đảm bảo bài viết có mã UUID nhất quán
        $existing_uuid = get_post_meta( $target_post_id, '_skaaa_uuid', true );
        if ( empty( $existing_uuid ) ) {
            $existing_uuid = ! empty( $uuid ) ? $uuid : wp_generate_uuid4();
            update_post_meta( $target_post_id, '_skaaa_uuid', $existing_uuid );
        }

        $last_modified     = (int) get_post_modified_time( 'U', true, $target_post_id );
        $thumbnail_id      = get_post_thumbnail_id( $target_post_id );
        $featured_url      = $thumbnail_id ? ( wp_get_attachment_url( $thumbnail_id ) ?: '' ) : '';
        $custom_post_meta  = self::get_safe_export_meta( $target_post_id );

        return [
            'success'            => true,
            'uuid'               => $existing_uuid,
            'remote_post_id'     => $post->ID,
            'title'              => $post->post_title,
            'content'            => $post->post_content,
            'slug'               => $post->post_name,
            'post_type'          => $post->post_type,
            'post_status'        => $post->post_status,
            'last_modified'      => $last_modified,
            'last_modified_gmt'  => $post->post_modified_gmt,
            'permalink'          => get_permalink( $post->ID ),
            'featured_media_url' => $featured_url,
            'origin_url'         => site_url(),
            'table_prefix'       => $wpdb->prefix,
            'meta'               => $custom_post_meta,
        ];
    }

    /**
     * Đối soát trạng thái đồng bộ hàng loạt giữa Local và Live
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    public static function check_posts_status( \WP_REST_Request $request ): array {
        $params = $request->get_json_params();
        if ( empty( $params ) || ! is_array( $params ) ) {
            $params = $request->get_params();
        }

        $posts_to_check = $params['posts'] ?? [];
        if ( ! is_array( $posts_to_check ) || empty( $posts_to_check ) ) {
            return [
                'success' => false,
                'message' => __( 'Missing or empty posts list for status check.', 'skaaai' ),
            ];
        }

        $statuses = [];

        foreach ( $posts_to_check as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $uuid           = sanitize_text_field( $item['uuid'] ?? '' );
            $slug           = sanitize_title( $item['slug'] ?? '' );
            $post_type      = sanitize_key( $item['post_type'] ?? 'post' );
            $local_modified = (int) ( $item['local_modified'] ?? 0 );

            if ( empty( $uuid ) && empty( $slug ) ) {
                continue;
            }

            $key            = ! empty( $uuid ) ? $uuid : $slug;
            $target_post_id = null;

            if ( ! empty( $uuid ) ) {
                $target_post_id = Sync_Post::get_post_id_by_uuid( $uuid );
            }

            if ( ! $target_post_id && ! empty( $slug ) ) {
                $target_post_id = Sync_Post::get_post_id_by_slug( $slug, $post_type );
            }

            if ( ! $target_post_id ) {
                $statuses[ $key ] = [
                    'status'          => 'not_found',
                    'remote_modified' => null,
                    'remote_post_id'  => null,
                    'permalink'       => '',
                ];
                continue;
            }

            $remote_post = get_post( $target_post_id );
            if ( ! ( $remote_post instanceof \WP_Post ) ) {
                $statuses[ $key ] = [
                    'status'          => 'not_found',
                    'remote_modified' => null,
                    'remote_post_id'  => null,
                    'permalink'       => '',
                ];
                continue;
            }

            // Đồng bộ lại UUID nếu thiếu
            $existing_uuid = get_post_meta( $target_post_id, '_skaaa_uuid', true );
            if ( empty( $existing_uuid ) && ! empty( $uuid ) ) {
                update_post_meta( $target_post_id, '_skaaa_uuid', $uuid );
            }

            $remote_modified = (int) get_post_modified_time( 'U', true, $target_post_id );

            // Xác định trạng thái chênh lệch thời gian (ngưỡng dung sai 2 giây)
            if ( abs( $remote_modified - $local_modified ) <= 2 ) {
                $status = 'synced';
            } elseif ( $remote_modified > ( $local_modified + 2 ) ) {
                $status = 'remote_ahead';
            } else {
                $status = 'local_ahead';
            }

            $statuses[ $key ] = [
                'status'          => $status,
                'remote_modified' => $remote_modified,
                'remote_post_id'  => $target_post_id,
                'permalink'       => get_permalink( $target_post_id ),
                'title'           => $remote_post->post_title,
                'slug'            => $remote_post->post_name,
                'uuid'            => $existing_uuid ?: $uuid,
            ];
        }

        return [
            'success'  => true,
            'statuses' => $statuses,
        ];
    }

    /**
     * Xuất gói cấu hình Hệ sinh thái tổng thể
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    public static function export_ecosystem( \WP_REST_Request $request ): array {
        $scopes = $request->get_param( 'scopes' );
        if ( ! empty( $scopes ) && is_string( $scopes ) ) {
            $scopes = array_map( 'trim', explode( ',', $scopes ) );
        }

        $all_scopes = [ 'presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'settings' ];

        $options = [
            'scopes'  => ( is_array( $scopes ) && ! empty( $scopes ) ) ? $scopes : $all_scopes,
            'dry_run' => (bool) $request->get_param( 'dry_run' ),
        ];

        $payload = Sync_Ecosystem_Sender::export_payload( $options );

        return [
            'success' => true,
            'payload' => $payload,
        ];
    }

    /**
     * Thu thập danh sách metadata an toàn để xuất
     *
     * @param int $post_id
     * @return array
     */
    private static function get_safe_export_meta( int $post_id ): array {
        $all_meta  = get_post_meta( $post_id );
        $safe_meta = [];

        // Chỉ xuất các meta bắt đầu bằng _skaaa_ hoặc các thuộc tính không nhạy cảm
        foreach ( $all_meta as $key => $values ) {
            if ( str_starts_with( $key, '_skaaa_' ) ) {
                // Bỏ qua các meta tạm thời
                if ( in_array( $key, [ '_skaaa_remote_permalink', '_skaaa_remote_post_id' ], true ) ) {
                    continue;
                }
                $safe_meta[ $key ] = maybe_unserialize( $values[0] ?? '' );
            }
        }

        return $safe_meta;
    }
}
