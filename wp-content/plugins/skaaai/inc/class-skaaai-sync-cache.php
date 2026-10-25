<?php
/**
 * Lớp Quản Lý Xóa Bộ Nhớ Đệm & LiteSpeed Server Purge (Sync_Cache)
 *
 * Chịu trách nhiệm phát lệnh xóa cache toàn diện (Server-level LiteSpeed, Page Cache,
 * Object Cache, Rewrite Rules và tệp cache cấu kiện tĩnh organisms.json).
 *
 * @package Skaaai
 * @version 1.6.5
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Cache {

    /**
     * Tự động xóa sạch Cache hosting và làm mới Rewrite Rules (Safeguard Layer 4)
     *
     * @param int|string|null $target_post_id ID bài viết hoặc URL cụ thể (null = toàn bộ website)
     */
    public static function invalidate( int|string|null $target_post_id = null ): void {
        try {
            // 1. LiteSpeed Server Native Header (X-LiteSpeed-Purge)
            if ( ! headers_sent() ) {
                $header_value = self::build_litespeed_purge_header_value( $target_post_id );
                if ( ! empty( $header_value ) ) {
                    header( 'X-LiteSpeed-Purge: ' . $header_value );
                }
            }

            // 2. LiteSpeed Cache Plugin (Gọi trực tiếp method class nếu plugin đang chạy)
            if ( class_exists( '\LiteSpeed\Purge' ) ) {
                if ( ! empty( $target_post_id ) && is_numeric( $target_post_id ) && method_exists( '\LiteSpeed\Purge', 'purge_post' ) ) {
                    \LiteSpeed\Purge::purge_post( (int) $target_post_id );
                } elseif ( method_exists( '\LiteSpeed\Purge', 'purge_all' ) ) {
                    \LiteSpeed\Purge::purge_all();
                }
            }
            do_action( 'litespeed_purge_all' );

            // 3. WordPress Core Object Cache & Specific Post Cache
            if ( ! empty( $target_post_id ) && is_numeric( $target_post_id ) ) {
                clean_post_cache( (int) $target_post_id );
            }
            wp_cache_flush();

            // 4. Các giải pháp Page Cache phổ biến khác
            // WP Rocket
            if ( function_exists( 'rocket_clean_domain' ) ) {
                if ( ! empty( $target_post_id ) && is_numeric( $target_post_id ) && function_exists( 'rocket_clean_post' ) ) {
                    rocket_clean_post( (int) $target_post_id );
                } else {
                    rocket_clean_domain();
                }
            }

            // W3 Total Cache
            if ( function_exists( 'w3tc_flush_all' ) ) {
                if ( ! empty( $target_post_id ) && is_numeric( $target_post_id ) && function_exists( 'w3tc_flush_post' ) ) {
                    w3tc_flush_post( (int) $target_post_id );
                } else {
                    w3tc_flush_all();
                }
            }

            // WP Super Cache
            if ( function_exists( 'wp_cache_clear_cache' ) ) {
                wp_cache_clear_cache();
            }

            // WP Fastest Cache
            if ( class_exists( 'WpFastestCache' ) ) {
                $wpfc = new \WpFastestCache();
                if ( method_exists( $wpfc, 'deleteCache' ) ) {
                    $wpfc->deleteCache( true );
                }
            }

            // Breeze (Cloudways)
            if ( class_exists( 'Breeze_PurgeCache' ) && method_exists( 'Breeze_PurgeCache', 'breeze_cache_flush' ) ) {
                \Breeze_PurgeCache::breeze_cache_flush();
            }

            // SG Optimizer (SiteGround)
            if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
                sg_cachepress_purge_cache();
            }

            // Autoptimize
            if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
                \autoptimizeCache::clearall();
            }

            // Nginx Helper (FastCGI Cache)
            do_action( 'rt_nginx_helper_purge_all' );

            // Cloudflare Plugin
            do_action( 'cloudflare_purge_everything' );

            // 5. Làm mới Rewrite Rules nếu cần
            flush_rewrite_rules( false );
        } catch ( \Throwable $e ) {
            error_log( '[Skaaai Sync_Cache] Invalidate error: ' . $e->getMessage() );
        }
    }

    /**
     * Đính kèm Header xóa Cache vào đối tượng WP_REST_Response
     * Đảm bảo Web Server LiteSpeed bắt được lệnh purge ngay khi trả về JSON.
     *
     * @param \WP_REST_Response $response
     * @param int|string|null   $target_post_id
     * @return \WP_REST_Response
     */
    public static function attach_purge_header( \WP_REST_Response $response, int|string|null $target_post_id = null ): \WP_REST_Response {
        $header_value = self::build_litespeed_purge_header_value( $target_post_id );
        if ( ! empty( $header_value ) ) {
            $response->header( 'X-LiteSpeed-Purge', $header_value );
        }
        $response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0' );
        return $response;
    }

    /**
     * Tạo giá trị cho header X-LiteSpeed-Purge
     *
     * @param int|string|null $target_post_id
     * @return string
     */
    public static function build_litespeed_purge_header_value( int|string|null $target_post_id = null ): string {
        if ( empty( $target_post_id ) ) {
            return '*';
        }

        $urls = [];
        if ( is_numeric( $target_post_id ) ) {
            $post_url = get_permalink( (int) $target_post_id );
            if ( $post_url ) {
                $urls[] = $post_url;
            }
        } elseif ( is_string( $target_post_id ) ) {
            $urls[] = $target_post_id;
        }

        // Luôn kèm theo trang chủ vì Header/Footer/Bài mới thường ảnh hưởng trang chủ
        $urls[] = home_url( '/' );

        $purge_parts = [];
        foreach ( array_unique( $urls ) as $u ) {
            $path = wp_parse_url( $u, PHP_URL_PATH ) ?: '/';
            $purge_parts[] = 'url=' . $path;
        }

        return ! empty( $purge_parts ) ? implode( ',', $purge_parts ) : '*';
    }

    /**
     * Tự động tái tạo file cache cấu kiện tĩnh (wp-content/uploads/skaaa-data/organisms.json)
     */
    public static function refresh_organisms_cache(): void {
        try {
            // 1. Kích hoạt hook toàn cục tuân thủ Decoupled Microservices
            do_action( 'skaaa_refresh_organisms_cache' );

            // 2. Fallback trực tiếp qua Singleton instance nếu hook chưa được đăng ký
            if ( class_exists( '\Skaaa\Design\Api\Organisms_API' ) && method_exists( '\Skaaa\Design\Api\Organisms_API', 'get_instance' ) ) {
                $api = \Skaaa\Design\Api\Organisms_API::get_instance();
                if ( method_exists( $api, 'export_physical_cache' ) ) {
                    $api->export_physical_cache();
                }
            }
        } catch ( \Throwable $e ) {
            // Không bao giờ để lỗi cache làm sập tiến trình đồng bộ hệ sinh thái
            error_log( '[Skaaai Sync_Cache] Failed to refresh organisms cache: ' . $e->getMessage() );
        }
    }
}
