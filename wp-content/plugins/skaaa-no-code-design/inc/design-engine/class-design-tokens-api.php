<?php
namespace Skaaa\Design\Api;

defined( 'ABSPATH' ) || exit;

class Design_Tokens_API {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'skaaa-design/v1', '/tokens', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_tokens' ],
                'permission_callback' => [ $this, 'check_permission' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_tokens' ],
                'permission_callback' => [ $this, 'check_permission' ],
            ]
        ] );
    }

    public function check_permission() {
        return current_user_can( 'manage_options' );
    }

    public function get_tokens( \WP_REST_Request $request ) {
        // Read directly from DB for Admin Dashboard to get original Names and IDs
        if ( class_exists( '\Skaaa\Design\Api\Design_Tokens_Compiler' ) ) {
            global $wpdb;
            $presets_table = $wpdb->prefix . 'skaaa_data_sys_presets';
            $rows = $wpdb->get_results( "SELECT * FROM {$presets_table}", ARRAY_A );

            if ( empty( $rows ) ) {
                self::seed_default_tokens();
                $rows = $wpdb->get_results( "SELECT * FROM {$presets_table}", ARRAY_A );
            }

            $data = [
                'brand' => [],
                'colors' => [],
                'darkColors' => [],
                'typography' => [],
                'typography_scale' => [],
                'tokens' => [],
                'components' => []
            ];

            if ( $rows ) {
                foreach ( $rows as $row ) {
                    $type = $row['type'];
                    $name = $row['name'];
                    $val = $row['value'];

                    if ( empty( $name ) ) continue;

                    $key = sanitize_key( str_replace( '-', '_', sanitize_title( $name ) ) );

                    switch ( $type ) {
                        case 'token_brand':
                            $camel_key = lcfirst( str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', sanitize_title( $name ) ) ) ) );
                            $data['brand'][$camel_key] = $val;
                            if ( strtolower( $camel_key ) === 'logourl' ) {
                                $data['brand']['logoUrl'] = $val;
                                $data['brand']['logourl'] = $val;
                            }
                            break;
                        case 'token_color':
                            $data['colors'][$key] = $val;
                            break;
                        case 'token_dark_color':
                            $data['darkColors'][$key] = $val;
                            break;
                        case 'token_font':
                            $data['typography'][$key] = $val;
                            break;
                        case 'preset_typography':
                            $data['typography_scale'][$key] = $val;
                            break;
                        case 'token_spacing':
                        case 'token_radius':
                        case 'token_shadow':
                            $camel_key = lcfirst( str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', sanitize_title( $name ) ) ) ) );
                            $data['tokens'][$camel_key] = $val;
                            break;
                        case 'preset_component':
                            $data['components'][] = [
                                'id' => $row['id'],
                                'name' => $name,
                                'value' => $val
                            ];
                            break;
                    }
                }
            }
            
            return rest_ensure_response( [
                'success' => true,
                'data'    => $data
            ] );
        }

        return rest_ensure_response( [
            'success' => false,
            'message' => 'Design_Tokens_Compiler not found'
        ] );
    }

    public function save_tokens( \WP_REST_Request $request ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skaaa_data_sys_presets';

        $body = $request->get_json_params();
        if ( empty( $body ) ) {
            return new \WP_Error( 'invalid_data', 'No data provided', [ 'status' => 400 ] );
        }

        // Mapping sections to types
        $section_type_map = [
            'brand' => 'token_brand',
            'colors' => 'token_color',
            'darkColors' => 'token_dark_color',
            'typography' => 'token_font',
            'typography_scale' => 'preset_typography',
            'components' => 'preset_component'
        ];

        $token_type_map = [
            'borderRadius' => 'token_radius',
            'boxShadow' => 'token_shadow',
            'containerWidth' => 'token_spacing',
            'transitionDuration' => 'token_spacing',
            'blockGap' => 'token_spacing',
            'contentPadding' => 'token_spacing'
        ];

        $wpdb->query( "START TRANSACTION" );

        try {
            // Xóa tất cả token cũ để đồng bộ 1 chiều từ payload
            $wpdb->query( "TRUNCATE TABLE {$table_name}" );

            foreach ( $section_type_map as $section => $db_type ) {
                if ( isset( $body[$section] ) && is_array( $body[$section] ) ) {
                    if ( $section === 'components' ) {
                        foreach ( $body[$section] as $preset ) {
                            if ( isset($preset['name']) && isset($preset['value']) ) {
                                $wpdb->insert(
                                    $table_name,
                                    [
                                        'name' => sanitize_text_field( $preset['name'] ),
                                        'type' => $db_type,
                                        'value' => sanitize_text_field( $preset['value'] )
                                    ],
                                    [ '%s', '%s', '%s' ]
                                );
                            }
                        }
                    } else {
                        foreach ( $body[$section] as $key => $val ) {
                            if ( $section === 'brand' ) {
                                $name = ucwords( preg_replace( '/([a-z])([A-Z])/', '$1 $2', $key ) );
                            } else {
                                $name = ucfirst(str_replace(['_', '-'], ' ', $key));
                            }
                            $wpdb->insert(
                                $table_name,
                                [
                                    'name' => $name,
                                    'type' => $db_type,
                                    'value' => is_string($val) ? $val : wp_json_encode($val)
                                ],
                                [ '%s', '%s', '%s' ]
                            );
                        }
                    }
                }
            }

            // Xử lý section 'tokens'
            if ( isset( $body['tokens'] ) && is_array( $body['tokens'] ) ) {
                foreach ( $body['tokens'] as $key => $val ) {
                    if ( ! isset( $token_type_map[$key] ) ) {
                        continue; // Chỉ lưu các token hợp lệ để tránh rác/trùng lặp (vd: blockgap vs blockGap)
                    }

                    $type = $token_type_map[$key];
                    $name = ucwords( preg_replace( '/([a-z])([A-Z])/', '$1 $2', $key ) ); // Convert camelCase to Space Case
                    
                    // Fix: Chuẩn hóa giá trị, loại bỏ khoảng trắng thừa giữa số và đơn vị (vd: '0 px' -> '0px')
                    $safe_val = is_string($val) ? trim($val) : wp_json_encode($val);
                    $safe_val = preg_replace('/^([\d\.]+)\s+(px|rem|em|%|vh|vw|ms|s)$/i', '$1$2', $safe_val);

                    $wpdb->insert(
                        $table_name,
                        [
                            'name' => $name,
                            'type' => $type,
                            'value' => $safe_val
                        ],
                        [ '%s', '%s', '%s' ]
                    );
                }
            }

            $wpdb->query( "COMMIT" );

            // Gọi compiler để xuất file cache
            if ( class_exists( '\Skaaa\Design\Api\Design_Tokens_Compiler' ) ) {
                \Skaaa\Design\Api\Design_Tokens_Compiler::get_instance()->compile_tokens_to_json();
            }

            return rest_ensure_response( [
                'success' => true,
                'message' => __( 'Saved Design Tokens successfully.', 'skaaa-no-code-design' ),
            ] );

        } catch ( \Exception $e ) {
            $wpdb->query( "ROLLBACK" );
            return new \WP_Error( 'db_error', __( 'Unable to save to database', 'skaaa-no-code-design' ), [ 'status' => 500 ] );
        }
    }

    public static function get_default_tokens_data() {
        return [
            'brand' => [
                'logoUrl' => '',
            ],
            'colors' => [
                'primary'    => '#3b82f6',
                'secondary'  => '#10b981',
                'tertiary'   => '#f59e0b',
                'surface'    => '#ffffff',
                'background' => '#f9fafb',
                'text'       => '#111827',
                'border'     => '#e5e7eb',
                'success'    => '#10b981',
                'warning'    => '#f59e0b',
                'error'      => '#ef4444',
                'info'       => '#3b82f6',
            ],
            'darkColors' => [
                'primary'    => '#60a5fa',
                'secondary'  => '#34d399',
                'tertiary'   => '#fbbf24',
                'surface'    => '#1f2937',
                'background' => '#111827',
                'text'       => '#f9fafb',
                'border'     => '#374151',
                'success'    => '#34d399',
                'warning'    => '#fbbf24',
                'error'      => '#f87171',
                'info'       => '#60a5fa',
            ],
            'typography' => [
                'primary'       => 'Inter, sans-serif',
                'secondary'     => 'Outfit, sans-serif',
                'mono'          => 'IBM Plex Mono, monospace',
                'customFontUrl' => '',
            ],
            'typography_scale' => [
                'h1'    => 'text-5xl font-bold tracking-tight leading-tight',
                'h2'    => 'text-4xl font-bold tracking-tight leading-tight',
                'h3'    => 'text-2xl font-semibold tracking-tight leading-snug',
                'h4'    => 'text-lg font-bold leading-relaxed',
                'p'     => 'text-base font-normal leading-relaxed',
                'small' => 'text-sm font-normal leading-relaxed',
            ],
            'tokens' => [
                'borderRadius'       => '6px',
                'boxShadow'          => 'none',
                'containerWidth'     => '1280px',
                'transitionDuration' => '150ms',
                'blockGap'           => '1.5rem',
                'contentPadding'     => '1rem',
            ],
            'components' => [
                [ 'name' => 'Button Primary', 'value' => 'bg-primary text-white hover:bg-blue-700 px-4 py-2 rounded-md font-semibold transition' ],
                [ 'name' => 'Button Secondary', 'value' => 'bg-transparent border border-primary text-primary hover:bg-blue-50 px-4 py-2 rounded-md font-semibold transition' ],
                [ 'name' => 'Button Outline', 'value' => 'bg-transparent border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md font-semibold transition' ],
                [ 'name' => 'Card Default', 'value' => 'bg-surface border border-gray-200 rounded-md p-4' ],
                [ 'name' => 'Card Elevated', 'value' => 'bg-surface shadow-md rounded-md p-4' ],
                [ 'name' => 'Input Text', 'value' => 'bg-surface border border-gray-200 text-gray-900 text-sm rounded-md focus:ring-primary focus:border-primary block w-full p-2.5' ],
                [ 'name' => 'Input Label', 'value' => 'block mb-2 text-sm font-medium text-gray-900' ],
                [ 'name' => 'Badge Status', 'value' => 'bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-green-400' ],
                [ 'name' => 'Badge Filter', 'value' => 'bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-400 hover:bg-gray-200 cursor-pointer' ],
            ],
        ];
    }

    public static function seed_default_tokens() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skaaa_data_sys_presets';
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
            return;
        }

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        if ( intval( $count ) > 0 ) {
            return;
        }

        $defaults = self::get_default_tokens_data();

        $section_type_map = [
            'brand' => 'token_brand',
            'colors' => 'token_color',
            'darkColors' => 'token_dark_color',
            'typography' => 'token_font',
            'typography_scale' => 'preset_typography',
            'components' => 'preset_component'
        ];

        $token_type_map = [
            'borderRadius' => 'token_radius',
            'boxShadow' => 'token_shadow',
            'containerWidth' => 'token_spacing',
            'transitionDuration' => 'token_spacing',
            'blockGap' => 'token_spacing',
            'contentPadding' => 'token_spacing'
        ];

        foreach ( $section_type_map as $section => $db_type ) {
            if ( isset( $defaults[$section] ) && is_array( $defaults[$section] ) ) {
                if ( $section === 'components' ) {
                    foreach ( $defaults[$section] as $preset ) {
                        $wpdb->insert(
                            $table_name,
                            [
                                'name' => sanitize_text_field( $preset['name'] ),
                                'type' => $db_type,
                                'value' => sanitize_text_field( $preset['value'] )
                            ],
                            [ '%s', '%s', '%s' ]
                        );
                    }
                } else {
                    foreach ( $defaults[$section] as $key => $val ) {
                        $name = ucfirst( str_replace( ['_', '-'], ' ', $key ) );
                        $wpdb->insert(
                            $table_name,
                            [
                                'name' => $name,
                                'type' => $db_type,
                                'value' => is_string($val) ? $val : wp_json_encode($val)
                            ],
                            [ '%s', '%s', '%s' ]
                        );
                    }
                }
            }
        }

        if ( isset( $defaults['tokens'] ) && is_array( $defaults['tokens'] ) ) {
            foreach ( $defaults['tokens'] as $key => $val ) {
                if ( isset( $token_type_map[$key] ) ) {
                    $type = $token_type_map[$key];
                    $name = ucwords( preg_replace( '/([a-z])([A-Z])/', '$1 $2', $key ) );
                    $wpdb->insert(
                        $table_name,
                        [
                            'name' => $name,
                            'type' => $type,
                            'value' => is_string($val) ? $val : wp_json_encode($val)
                        ],
                        [ '%s', '%s', '%s' ]
                    );
                }
            }
        }

        if ( class_exists( '\Skaaa\Design\Api\Design_Tokens_Compiler' ) ) {
            \Skaaa\Design\Api\Design_Tokens_Compiler::get_instance()->compile_tokens_to_json();
        }
    }
}
