<?php
namespace Skaaa\Design\Api;

defined( 'ABSPATH' ) || exit;

class Design_Tokens_Compiler {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Lắng nghe các event từ Skaaa Data Pro
        add_action( 'skaaa_data_row_created', [ $this, 'maybe_compile_tokens' ], 10, 2 );
        add_action( 'skaaa_data_cell_updated', [ $this, 'maybe_compile_tokens' ], 10, 4 );
        add_action( 'skaaa_data_row_deleted', [ $this, 'maybe_compile_tokens' ], 10, 2 );
    }

    public function maybe_compile_tokens( $table_name, $row_id = 0, $column_name = '', $value = '' ) {
        global $wpdb;
        $presets_table = $wpdb->prefix . 'skaaa_data_sys_presets';
        
        // Chỉ compile nếu có thay đổi trong bảng sys_presets
        if ( $table_name !== $presets_table ) {
            return;
        }

        $this->compile_tokens_to_json();
    }

    public function compile_tokens_to_json() {
        global $wpdb;
        $presets_table = $wpdb->prefix . 'skaaa_data_sys_presets';

        // Lấy tất cả các rows
        $rows = $wpdb->get_results( "SELECT * FROM {$presets_table}", ARRAY_A );

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
                        $data['tokens'][$key] = $val;
                        break;
                    case 'preset_component':
                        $data['components'][] = [
                            'name' => $name,
                            'value' => $val
                        ];
                        break;
                }
            }
        }

        $json_string = wp_json_encode( $data );
        $this->export_physical_cache( $json_string, $data );
    }

    private function export_physical_cache( $json_string, $data ) {
        $upload_dir = wp_upload_dir();
        $skaaa_dir = trailingslashit( $upload_dir['basedir'] ) . 'skaaa-data';
        
        if ( ! file_exists( $skaaa_dir ) ) {
            wp_mkdir_p( $skaaa_dir );
        }

        // Save tokens.json
        $json_file_path = $skaaa_dir . '/tokens.json';
        file_put_contents( $json_file_path, $json_string );

        // Generate and save tokens.css for Vanilla CSS support
        $css_content = "/* Skaaa Design System - Auto Generated Custom Properties */\n:root {\n";

        if ( ! empty( $data['colors'] ) ) {
            foreach ( $data['colors'] as $key => $value ) {
                if ( ! empty( $value ) ) {
                    $css_content .= "  --skaaa-color-{$key}: {$value};\n";
                }
            }
        }

        if ( ! empty( $data['typography'] ) ) {
            foreach ( $data['typography'] as $key => $value ) {
                if ( ! empty( $value ) && $key !== 'customFontUrl' ) {
                    $css_content .= "  --skaaa-font-{$key}: {$value};\n";
                }
            }
        }

        if ( ! empty( $data['tokens'] ) ) {
            foreach ( $data['tokens'] as $key => $value ) {
                if ( ! empty( $value ) ) {
                    // Convert camelCase/snake_case to kebab-case
                    $kebab_key = str_replace( '_', '-', strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', $key ) ) );
                    $css_content .= "  --skaaa-sys-{$kebab_key}: {$value};\n";
                }
            }
        }

        if ( ! empty( $data['darkColors'] ) ) {
            foreach ( $data['darkColors'] as $key => $value ) {
                if ( ! empty( $value ) ) {
                    $css_content .= "  --skaaa-sys-color-dark-{$key}: {$value};\n";
                }
            }
        }

        $css_content .= "}\n\n";

        // Generate .dark mappings
        if ( ! empty( $data['darkColors'] ) ) {
            $css_content .= "html.dark, .editor-styles-wrapper.dark, .editor-styles-wrapper .dark, body.dark {\n";
            foreach ( $data['darkColors'] as $key => $value ) {
                if ( ! empty( $value ) ) {
                    // Cập nhật giá trị biến Light Mode thành giá trị Dark Mode
                    $css_content .= "  --skaaa-color-{$key}: var(--skaaa-sys-color-dark-{$key});\n";
                }
            }
            $css_content .= "}\n";
        }

        $css_file_path = $skaaa_dir . '/tokens.css';
        file_put_contents( $css_file_path, $css_content );
    }
}
