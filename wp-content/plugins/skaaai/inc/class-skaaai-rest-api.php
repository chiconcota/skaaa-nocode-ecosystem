<?php
/**
 * Lớp Quản Trị REST API (Skaaai_Rest_Api)
 *
 * Chịu trách nhiệm đăng ký các Route tiếp nhận Handshake,
 * Đồng bộ bài viết, và Triển khai code an toàn.
 *
 * @package Skaaai
 * @version 1.0.0
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class Rest_Api {

    const NAMESPACE = 'skaaai/v1';

    /**
     * Đăng ký các endpoints
     */
    public static function register_routes(): void {
        // 1. Endpoint Handshake kiểm tra kết nối
        register_rest_route( self::NAMESPACE, '/handshake', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ self::class, 'handle_handshake' ],
            'permission_callback' => [ self::class, 'verify_token_permission' ],
        ] );

        // 2. Endpoint tiếp nhận đồng bộ bài viết
        register_rest_route( self::NAMESPACE, '/push-post', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ self::class, 'handle_push_post' ],
            'permission_callback' => [ self::class, 'verify_token_permission' ],
        ] );

        // 3. Endpoint triển khai file mã nguồn/node mới
        register_rest_route( self::NAMESPACE, '/deploy-file', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ self::class, 'handle_deploy_file' ],
            'permission_callback' => [ self::class, 'verify_token_permission' ],
        ] );

        // 4. Endpoint xóa file trong sandbox
        register_rest_route( self::NAMESPACE, '/delete-file', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ self::class, 'handle_delete_file' ],
            'permission_callback' => [ self::class, 'verify_token_permission' ],
        ] );

        // 5. Endpoint lấy danh sách custom nodes
        register_rest_route( self::NAMESPACE, '/list-custom-nodes', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ self::class, 'handle_list_nodes' ],
            'permission_callback' => [ self::class, 'verify_token_permission' ],
        ] );
    }

    /**
     * Xác thực Token trong Header hoặc Parameter
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public static function verify_token_permission( \WP_REST_Request $request ): bool|\WP_Error {
        $token = $request->get_header( 'x_skaaai_token' );
        if ( empty( $token ) ) {
            $token = $request->get_param( 'token' );
        }

        if ( ! Pairing::validate_token( $token ) ) {
            return new \WP_Error(
                'skaaai_forbidden',
                __( 'Invalid or missing Skaaai security token.', 'skaaai' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Xử lý request Handshake
     */
    public static function handle_handshake( \WP_REST_Request $request ): \WP_REST_Response {
        $can_deploy_code = (bool) Core::get_setting( 'skaaai_allow_code_deploy', false );

        return new \WP_REST_Response( [
            'success'           => true,
            'site_name'         => get_bloginfo( 'name' ),
            'site_url'          => site_url(),
            'version'           => SKAAAI_VERSION,
            'role'              => Core::get_setting( 'skaaai_role', 'receiver' ),
            'can_deploy_code'   => $can_deploy_code,
            'allow_code_deploy' => $can_deploy_code,
            'time'              => time(),
        ], 200 );
    }

    /**
     * Xử lý request đồng bộ bài viết
     */
    public static function handle_push_post( \WP_REST_Request $request ): \WP_REST_Response {
        $params = $request->get_json_params();
        if ( empty( $params ) || ! is_array( $params ) ) {
            $params = $request->get_params();
        }

        $result = Sync_Post::process_incoming_post( $params );
        $status = $result['success'] ? 200 : ( ! empty( $result['conflict'] ) ? 409 : 400 );

        return new \WP_REST_Response( $result, $status );
    }

    /**
     * Xử lý request deploy file mã nguồn
     */
    public static function handle_deploy_file( \WP_REST_Request $request ): \WP_REST_Response {
        $params    = $request->get_json_params() ?: $request->get_params();
        $filename  = sanitize_text_field( $params['filename'] ?? '' );
        $code      = $params['code'] ?? '';
        $node_meta = is_array( $params['node_meta'] ?? null ) ? $params['node_meta'] : [];
        $overwrite = ! empty( $params['overwrite'] );

        $result = File_Deployer::deploy_file( $filename, $code, $node_meta, $overwrite );
        $status = $result['success'] ? 200 : 400;

        return new \WP_REST_Response( $result, $status );
    }

    /**
     * Xử lý request xóa file
     */
    public static function handle_delete_file( \WP_REST_Request $request ): \WP_REST_Response {
        $params   = $request->get_json_params() ?: $request->get_params();
        $filename = sanitize_text_field( $params['filename'] ?? '' );

        $result = File_Deployer::delete_file( $filename );
        $status = $result['success'] ? 200 : 400;

        return new \WP_REST_Response( $result, $status );
    }

    /**
     * Lấy danh sách custom nodes
     */
    public static function handle_list_nodes( \WP_REST_Request $request ): \WP_REST_Response {
        $files = File_Deployer::get_deployed_files();
        return new \WP_REST_Response( [
            'success' => true,
            'files'   => $files,
        ], 200 );
    }
}
