<?php
/**
 * Lớp Cấu Hình Tập Trung Hệ Sinh Thái (Skaaai_Sync_Config)
 *
 * Đóng vai trò Single Source of Truth (SSoT) cho toàn bộ các quy tắc,
 * scopes, blacklists và metadata đồng bộ giữa Localhost và Live Webhost.
 *
 * @package Skaaai
 * @version 1.5.5
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Sync_Config {

    /**
     * Danh sách 8 scopes đồng bộ của Hệ Sinh Thái
     */
    public const SCOPES = [
        'presets',
        'organisms',
        'theme_templates',
        'workflows',
        'custom_tables',
        'pages',
        'posts',
        'settings',
    ];

    /**
     * Danh sách các tùy chọn nhạy cảm tuyệt đối cấm ghi đè trên máy chủ Live (Safeguard Layer 1)
     */
    public const BLACKLISTED_OPTIONS = [
        'siteurl',
        'home',
        'admin_email',
        'new_admin_email',
        'active_plugins',
        'users_can_register',
        'mailserver_url',
        'mailserver_login',
        'mailserver_pass',
    ];

    /**
     * Tiền tố các bảng hệ thống lõi không xuất trong custom_tables
     */
    public const SYSTEM_TABLE_PREFIXES = [
        'sys_',
    ];

    /**
     * Hậu tố các bảng dữ liệu nhạy cảm không xuất trong custom_tables
     */
    public const BLACKLISTED_TABLE_SUFFIXES = [
        '_submissions',
    ];

    /**
     * Danh sách các bảng phẳng hệ thống của Skaaa
     */
    public const SYSTEM_TABLES = [
        'sys_presets',
        'sys_organisms',
        'sys_theme_templates',
        'sys_workflows',
        'sys_apps',
        'sys_settings',
    ];

    /**
     * REST API Endpoints & Namespaces
     */
    public const REST_NAMESPACE            = 'skaaai/v1';
    public const ENDPOINT_SYNC_ECOSYSTEM   = '/sync-ecosystem';
    public const ENDPOINT_EXPORT_ECOSYSTEM = '/export-ecosystem';
    public const ENDPOINT_EXPORT_POST      = '/export-post';
    public const ENDPOINT_CHECK_STATUS     = '/check-posts-status';
    public const ENDPOINT_UPLOAD_MEDIA     = '/upload-media';
    public const ENDPOINT_DELETE_FILE      = '/delete-file';
    public const ENDPOINT_HANDSHAKE        = '/handshake';

    /**
     * Network Timeouts (giây)
     */
    public const TIMEOUT_LONG  = 120;
    public const TIMEOUT_MEDIA = 45;
    public const TIMEOUT_FAST  = 15;

    /**
     * Lấy danh sách tất cả scopes hợp lệ
     *
     * @return array
     */
    public static function get_all_scopes(): array {
        return self::SCOPES;
    }

    /**
     * Kiểm tra xem một tùy chọn có nằm trong Blacklist hay không
     *
     * @param string $option_name
     * @return bool
     */
    public static function is_blacklisted_option( string $option_name ): bool {
        return in_array( $option_name, self::BLACKLISTED_OPTIONS, true );
    }

    /**
     * Kiểm tra xem một bảng phẳng có phải bảng hệ thống hoặc bị cấm export hay không
     *
     * @param string $table_name Tên bảng đầy đủ
     * @param string $prefix     Tiền tố (ví dụ: wp_skaaa_data_)
     * @return bool
     */
    public static function is_custom_table_allowed( string $table_name, string $prefix ): bool {
        $suffix = substr( $table_name, strlen( $prefix ) );
        if ( empty( $suffix ) ) {
            return false;
        }

        foreach ( self::SYSTEM_TABLE_PREFIXES as $sys_pfx ) {
            if ( str_starts_with( $suffix, $sys_pfx ) ) {
                return false;
            }
        }

        foreach ( self::BLACKLISTED_TABLE_SUFFIXES as $bl_sfx ) {
            if ( str_ends_with( $suffix, $bl_sfx ) ) {
                return false;
            }
        }

        return true;
    }
}
