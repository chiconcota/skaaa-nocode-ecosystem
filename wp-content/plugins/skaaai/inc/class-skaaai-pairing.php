<?php
/**
 * Lớp Xử lý Ghép Đôi & Bảo Mật Kết Nối (Skaaai_Pairing)
 *
 * Chịu trách nhiệm sinh Pairing URI, kiểm tra tính hợp lệ của token
 * và thực hiện handshake hai chiều giữa Sender và Receiver.
 *
 * @package Skaaai
 * @version 1.0.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Pairing {

    const URI_PREFIX = 'skaaai_pair://';

    /**
     * Lấy hoặc sinh mới mã token bảo mật cho Receiver
     *
     * @return string
     */
    public static function get_or_create_token(): string {
        $token = Core::get_setting( 'skaaai_secret_token', '' );
        if ( empty( $token ) ) {
            $token = wp_generate_password( 64, false, false );
            Core::set_setting( 'skaaai_secret_token', $token );
        }
        return (string) $token;
    }

    /**
     * Tạo mới hoàn toàn mã token (dùng khi người dùng muốn đổi key)
     *
     * @return string
     */
    public static function regenerate_token(): string {
        $token = wp_generate_password( 64, false, false );
        Core::set_setting( 'skaaai_secret_token', $token );
        return $token;
    }

    /**
     * Tạo chuỗi Pairing URI để copy sang Localhost
     *
     * @return string
     */
    public static function get_pairing_uri(): string {
        $payload = [
            'url'       => get_site_url(),
            'token'     => self::get_or_create_token(),
            'site_name' => get_bloginfo( 'name' ),
            'version'   => SKAAAI_VERSION,
            'time'      => time(),
        ];

        return self::URI_PREFIX . base64_encode( (string) wp_json_encode( $payload ) );
    }

    /**
     * Phân tích chuỗi Pairing URI
     *
     * @param string $uri Chuỗi skaaai_pair://...
     * @return array|null
     */
    public static function parse_pairing_uri( string $uri ): ?array {
        $uri = trim( $uri );
        if ( ! str_starts_with( $uri, self::URI_PREFIX ) ) {
            return null;
        }

        $base64 = substr( $uri, strlen( self::URI_PREFIX ) );
        $json   = base64_decode( $base64, true );
        if ( ! $json ) {
            return null;
        }

        $data = json_decode( $json, true );
        if ( ! is_array( $data ) || empty( $data['url'] ) || empty( $data['token'] ) ) {
            return null;
        }

        return [
            'url'       => esc_url_raw( $data['url'] ),
            'token'     => sanitize_text_field( $data['token'] ),
            'site_name' => ! empty( $data['site_name'] ) ? sanitize_text_field( $data['site_name'] ) : '',
            'version'   => ! empty( $data['version'] ) ? sanitize_text_field( $data['version'] ) : '',
        ];
    }

    /**
     * Xác thực token bảo mật gửi đến Receiver
     *
     * @param string|null $provided_token
     * @return bool
     */
    public static function validate_token( ?string $provided_token ): bool {
        if ( empty( $provided_token ) ) {
            return false;
        }

        $expected_token = self::get_or_create_token();
        return hash_equals( $expected_token, trim( $provided_token ) );
    }

    /**
     * Thử nghiệm Handshake từ Sender sang Receiver
     *
     * @param string $remote_url URL của website nhận
     * @param string $token Mã token bảo mật
     * @return array
     */
    public static function test_remote_handshake( string $remote_url, string $token ): array {
        $clean_url = rtrim( $remote_url, '/' ) . '/wp-json/skaaai/v1/handshake';

        $response = wp_remote_get( $clean_url, [
            'timeout' => 15,
            'headers' => [
                'X-Skaaai-Token' => trim( $token ),
                'Accept'         => 'application/json',
            ],
            'sslverify' => false, // Cho phép cert local hoặc staging tự ký
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code === 200 && ! empty( $data['success'] ) ) {
            return [
                'success'   => true,
                'message'   => __( 'Connection established successfully!', 'skaaai' ),
                'site_name' => $data['site_name'] ?? '',
                'version'   => $data['version'] ?? '',
                'can_code'  => $data['can_deploy_code'] ?? false,
            ];
        }

        $error_msg = ! empty( $data['message'] ) ? $data['message'] : __( 'Invalid handshake response.', 'skaaai' );
        return [
            'success' => false,
            'message' => sprintf( __( 'Error %d: %s', 'skaaai' ), $code, $error_msg ),
        ];
    }
}
