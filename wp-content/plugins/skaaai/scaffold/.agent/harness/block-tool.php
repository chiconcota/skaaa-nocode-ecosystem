<?php
/**
 * Block Synthesizer & Validator CLI Tool (block-tool.php)
 *
 * Provides syntax validation, Flat DOM compliance auditing, Skaaapine verification,
 * and 1-click test page creation for Skaaa Gutenberg Atomic Blocks.
 *
 * Usage:
 *   php .agent/harness/block-tool.php --validate="<markup_or_file_path>"
 *   php .agent/harness/block-tool.php --render="<markup_or_file_path>"
 *   php .agent/harness/block-tool.php --create-test-page="<markup_or_file_path>" [--title="Page Title"]
 *   php .agent/harness/block-tool.php --help
 *
 * Options:
 *   --format=table|json   Output format (default: table)
 *   --title="Title"       Title for the test page (default: "Skaaa Block Test")
 *   --status=draft|publish Status for the created page (default: publish)
 *   --help, -h            Show usage help
 *
 * @package Skaaai
 * @version 1.2.3
 */

// Define clean CLI die handler before WordPress loads
if ( ! defined( 'WP_DIE_HANDLER' ) ) {
    function skaaa_cli_die_handler( $message, $title = '', $args = [] ): void {
        $clean_msg = trim( strip_tags( (string) $message ) );
        fwrite( STDERR, "\n\033[31m[WORDPRESS/DATABASE ERROR]\033[0m {$clean_msg}\n" );
        fwrite( STDERR, "Hint: If running under Local by Flywheel, please start the site in the Local app.\n\n" );
        exit( 1 );
    }
    define( 'WP_DIE_HANDLER', 'skaaa_cli_die_handler' );
}

class Skaaa_Block_Tool {

    private static string $format = 'table';

    public static function run( array $args ): void {
        $options = self::parse_args( $args );

        // 1. Show help without requiring WordPress bootstrap
        if ( isset( $options['help'] ) || isset( $options['h'] ) || empty( $options ) ) {
            self::show_help();
            exit( 0 );
        }

        self::$format = strtolower( $options['format'] ?? 'table' );

        try {
            // 2. Validate can run in standalone mode (no DB needed)
            if ( isset( $options['validate'] ) ) {
                $content = self::get_input_content( (string) $options['validate'] );
                self::handle_validate( $content );
            } elseif ( isset( $options['render'] ) ) {
                self::bootstrap_wordpress();
                $content = self::get_input_content( (string) $options['render'] );
                self::handle_render( $content );
            } elseif ( isset( $options['create-test-page'] ) ) {
                self::bootstrap_wordpress();
                $content = self::get_input_content( (string) $options['create-test-page'] );
                $title   = (string) ( $options['title'] ?? 'Skaaa Block Test - ' . current_time( 'Y-m-d H:i:s' ) );
                $status  = (string) ( $options['status'] ?? 'publish' );
                self::handle_create_test_page( $content, $title, $status );
            } else {
                self::show_help();
            }
        } catch ( \Throwable $e ) {
            self::output_error( $e->getMessage() );
            exit( 1 );
        }
    }

    /**
     * Locate WordPress file by ascending directory tree.
     */
    private static function locate_wp_file( string $filename ): ?string {
        if ( defined( 'ABSPATH' ) && file_exists( ABSPATH . $filename ) ) {
            return ABSPATH . $filename;
        }

        $start_dirs = [
            __DIR__,
            getcwd() ?: '',
        ];

        foreach ( $start_dirs as $start ) {
            if ( empty( $start ) ) {
                continue;
            }
            $current = $start;
            for ( $i = 0; $i < 10; $i++ ) {
                $check = $current . '/' . $filename;
                if ( file_exists( $check ) ) {
                    return $check;
                }
                $parent = dirname( $current );
                if ( $parent === $current ) {
                    break;
                }
                $current = $parent;
            }
        }

        $fallbacks = [
            '/var/www/html/' . $filename,
        ];
        foreach ( $fallbacks as $fb ) {
            if ( file_exists( $fb ) ) {
                return $fb;
            }
        }

        return null;
    }

    /**
     * Auto-detect Local by Flywheel MySQL UNIX socket.
     */
    private static function detect_local_mysql_socket(): ?string {
        $env_socket = getenv( 'DB_SOCKET' ) ?: getenv( 'MYSQL_UNIX_PORT' );
        if ( $env_socket && file_exists( $env_socket ) ) {
            return $env_socket;
        }

        $home = getenv( 'HOME' ) ?: ( $_SERVER['HOME'] ?? '' );
        if ( empty( $home ) && ! empty( $_SERVER['USERPROFILE'] ) ) {
            $home = $_SERVER['USERPROFILE'];
        }

        if ( empty( $home ) ) {
            return null;
        }

        $current_dirs = [
            realpath( __DIR__ ) ?: __DIR__,
            realpath( getcwd() ?: '' ) ?: ( getcwd() ?: '' ),
        ];

        // 1. Try matching Local sites.json to current path
        $sites_json_candidates = [
            $home . '/.config/Local/sites.json',
            $home . '/Library/Application Support/Local/sites.json',
            ( getenv( 'APPDATA' ) ?: '' ) . '/Local/sites.json',
        ];

        foreach ( $sites_json_candidates as $sites_file ) {
            if ( ! file_exists( $sites_file ) || ! is_readable( $sites_file ) ) {
                continue;
            }
            $sites_data = json_decode( (string) file_get_contents( $sites_file ), true );
            if ( ! is_array( $sites_data ) ) {
                continue;
            }

            foreach ( $sites_data as $site_id => $site_info ) {
                if ( empty( $site_info['path'] ) ) {
                    continue;
                }
                $normalized_site_path = str_replace( '~', $home, $site_info['path'] );
                $real_site_path       = realpath( $normalized_site_path ) ?: $normalized_site_path;

                foreach ( $current_dirs as $c_dir ) {
                    if ( ! empty( $c_dir ) && str_starts_with( $c_dir, $real_site_path ) ) {
                        $socket_path = $home . "/.config/Local/run/{$site_id}/mysql/mysqld.sock";
                        if ( file_exists( $socket_path ) ) {
                            return $socket_path;
                        }
                        $mac_socket = $home . "/Library/Application Support/Local/run/{$site_id}/mysql/mysqld.sock";
                        if ( file_exists( $mac_socket ) ) {
                            return $mac_socket;
                        }
                    }
                }
            }
        }

        // 2. Fallback: Scan active Local run directories for existing mysqld.sock
        $scan_patterns = [
            $home . '/.config/Local/run/*/mysql/mysqld.sock',
            $home . '/Library/Application Support/Local/run/*/mysql/mysqld.sock',
        ];

        foreach ( $scan_patterns as $pattern ) {
            $matches = glob( $pattern );
            if ( ! empty( $matches ) ) {
                foreach ( $matches as $sock ) {
                    if ( file_exists( $sock ) ) {
                        return $sock;
                    }
                }
            }
        }

        return null;
    }

    private static function bootstrap_wordpress(): void {
        if ( defined( 'ABSPATH' ) ) {
            return;
        }

        // Auto-configure Local by Flywheel socket before WordPress connects
        $socket = self::detect_local_mysql_socket();
        if ( $socket && file_exists( $socket ) ) {
            @ini_set( 'mysqli.default_socket', $socket );
            @ini_set( 'pdo_mysql.default_socket', $socket );
        }

        $wp_load = self::locate_wp_file( 'wp-load.php' );

        if ( ! $wp_load || ! file_exists( $wp_load ) ) {
            fwrite( STDERR, "\033[31m[ERROR]\033[0m Could not locate wp-load.php. Please run within a WordPress installation.\n" );
            exit( 1 );
        }

        @ini_set( 'display_errors', '0' );
        require_once $wp_load;

        if ( ! defined( 'ABSPATH' ) ) {
            fwrite( STDERR, "\033[31m[ERROR]\033[0m WordPress bootstrap failed. ABSPATH not defined.\n" );
            exit( 1 );
        }
    }

    private static function parse_args( array $args ): array {
        $options = [];
        foreach ( array_slice( $args, 1 ) as $arg ) {
            if ( str_starts_with( $arg, '--' ) ) {
                $eq = strpos( $arg, '=' );
                if ( false !== $eq ) {
                    $key   = substr( $arg, 2, $eq - 2 );
                    $val   = substr( $arg, $eq + 1 );
                    $options[ $key ] = $val;
                } else {
                    $options[ substr( $arg, 2 ) ] = true;
                }
            } elseif ( str_starts_with( $arg, '-' ) ) {
                $options[ substr( $arg, 1 ) ] = true;
            }
        }
        return $options;
    }

    private static function get_input_content( string $input ): string {
        if ( file_exists( $input ) && is_readable( $input ) ) {
            return (string) file_get_contents( $input );
        }
        return $input;
    }

    private static function handle_validate( string $content ): void {
        $issues = [];
        $blocks_count = 0;

        // 1. Kiểm tra Flat DOM: Phát hiện thẻ HTML thô bao bọc ngoài Gutenberg comments
        $stripped = preg_replace( '/<!--\s*\/?wp:.*?-->/s', '', $content );
        if ( preg_match_all( '/<(div|main|section|article|aside|header|footer)\b[^>]*>/i', $stripped, $raw_matches ) ) {
            foreach ( $raw_matches[0] as $raw_tag ) {
                $issues[] = [
                    'severity' => 'ERROR',
                    'rule'     => 'Flat DOM Violation',
                    'message'  => "Raw HTML tag {$raw_tag} detected outside Gutenberg block comments. All layout wrappers must use 'skaaaaa-builder/container' block.",
                    'fix'      => 'Remove raw HTML tags and replace with <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"..."} -->',
                ];
            }
        }

        // 2. Kiểm tra cú pháp comment của các khối Skaaa Atomic Blocks
        $self_closing_types = [ 'text', 'button', 'image', 'icon', 'video', 'svg', 'code', 'input', 'select', 'form-rich-text', 'organism-ref', 'html2tailwind' ];
        $open_close_types   = [ 'container', 'loop', 'list', 'list-item' ];

        if ( preg_match_all( '/<!--\s*wp:(skaaaaa-builder\/([a-z0-9_-]+))\s*(\{.*?\})?\s*(\/)?-->/s', $content, $block_matches, PREG_SET_ORDER ) ) {
            $blocks_count = count( $block_matches );

            foreach ( $block_matches as $bm ) {
                $full_name  = $bm[1];
                $block_type = $bm[2];
                $json_raw   = trim( $bm[3] ?? '' );
                $is_self    = ! empty( $bm[4] );

                // 2.1 Kiểm tra thuộc tính JSON
                if ( ! empty( $json_raw ) ) {
                    $json_data = json_decode( $json_raw, true );
                    if ( null === $json_data && JSON_ERROR_NONE !== json_last_error() ) {
                        $issues[] = [
                            'severity' => 'ERROR',
                            'rule'     => 'JSON Attributes Malformed',
                            'message'  => "Invalid JSON attribute string in block {$full_name}: " . json_last_error_msg(),
                            'fix'      => 'Verify quotation marks and escape special characters inside attributes.',
                        ];
                    } elseif ( is_array( $json_data ) ) {
                        // Kiểm tra chuẩn tên thuộc tính với skaaa-no-code-design
                        if ( isset( $json_data['tag'] ) && ! isset( $json_data['tagName'] ) ) {
                            $issues[] = [
                                'severity' => 'ERROR',
                                'rule'     => 'Schema Mismatch (tagName)',
                                'message'  => "Block {$full_name} uses deprecated attribute 'tag': \"{$json_data['tag']}\". Skaaa Design block schema strictly requires 'tagName'.",
                                'fix'      => "Replace '\"tag\":\"...\"' with '\"tagName\":\"{$json_data['tag']}\"'.",
                            ];
                        }
                        if ( isset( $json_data['classes'] ) && ! isset( $json_data['tailwindClasses'] ) ) {
                            $issues[] = [
                                'severity' => 'ERROR',
                                'rule'     => 'Schema Mismatch (tailwindClasses)',
                                'message'  => "Block {$full_name} uses deprecated attribute 'classes': \"{$json_data['classes']}\". Skaaa Design block schema strictly requires 'tailwindClasses'.",
                                'fix'      => "Replace '\"classes\":\"...\"' with '\"tailwindClasses\":\"{$json_data['classes']}\"'.",
                            ];
                        }

                        // 2.1.1 Thẩm định khối Button Native
                        if ( $block_type === 'button' ) {
                            if ( isset( $json_data['actionType'] ) ) {
                                $allowed_actions = [ 'link', 'submit', 'logic_api', 'theme_toggle' ];
                                if ( ! in_array( $json_data['actionType'], $allowed_actions, true ) ) {
                                    $issues[] = [
                                        'severity' => 'ERROR',
                                        'rule'     => 'Button Action Invalid',
                                        'message'  => "Block {$full_name} has invalid actionType '{$json_data['actionType']}'. Allowed values: " . implode( ', ', $allowed_actions ),
                                        'fix'      => "Use one of: 'link', 'submit', 'logic_api', 'theme_toggle'.",
                                    ];
                                }
                            }
                        }

                        // 2.1.2 Thẩm định chống lạm dụng Code Block cho UI Native
                        if ( $block_type === 'code' && ! empty( $json_data['inlineCode'] ) ) {
                            $code_str = (string) $json_data['inlineCode'];
                            if ( preg_match( '/<(button|form|input|select|img|video)\b|<svg\b|onclick\s*=|theme_toggle|\$store\.skaaaTheme/i', $code_str ) ) {
                                $issues[] = [
                                    'severity' => 'ERROR',
                                    'rule'     => 'Inline Code Abuse',
                                    'message'  => "Block '{$full_name}' contains raw HTML/JS for native UI elements (button, form, input, img, video, svg, or theme toggle). Skaaa provides native atomic blocks ('skaaaaa-builder/image', 'skaaaaa-builder/button', 'skaaaaa-builder/icon', 'skaaaaa-builder/video', 'skaaaaa-builder/input', 'skaaaaa-builder/select', 'skaaaaa-builder/svg'). Using inline code breaks No-Code editing, bypasses JIT compiler media queries, and causes black boxes in Gutenberg Editor.",
                                    'fix'      => "Replace with native Skaaa blocks: <!-- wp:skaaaaa-builder/image {...} /-->, <!-- wp:skaaaaa-builder/button {...} /-->, or <!-- wp:skaaaaa-builder/container {\"tagName\":\"form\",\"isSkaaaForm\":true} -->.",
                                ];
                            }
                        }

                        // 2.1.3 Thẩm định Form Container kích hoạt Skaaa Form Engine
                        if ( $block_type === 'container' && isset( $json_data['tagName'] ) && 'form' === $json_data['tagName'] ) {
                            if ( empty( $json_data['isSkaaaForm'] ) ) {
                                $issues[] = [
                                    'severity' => 'WARNING',
                                    'rule'     => 'Skaaa Form Engine Inactive',
                                    'message'  => "Form container ('tagName': 'form') lacks '\"isSkaaaForm\": true'. The Skaaa Form Engine (Alpine Controller, automatic validation & submission) will not activate.",
                                    'fix'      => 'Add "isSkaaaForm": true and "formActionId": "insert_{table_slug}" to container attributes.',
                                ];
                            }
                        }

                        // 2.1.4 Thẩm định SVG Block bắt buộc có kích thước rõ ràng
                        if ( $block_type === 'svg' ) {
                            $tw = $json_data['tailwindClasses'] ?? '';
                            if ( ! preg_match( '/\bw-[0-9a-z\[\]\.\/]+\b/', $tw ) || ! preg_match( '/\bh-[0-9a-z\[\]\.\/]+\b/', $tw ) ) {
                                $issues[] = [
                                    'severity' => 'WARNING',
                                    'rule'     => 'SVG Dimension Missing',
                                    'message'  => "Block '{$full_name}' lacks explicit width/height in tailwindClasses (e.g. 'w-6 h-6 shrink-0'). Without explicit dimensions, SVGs risk collapsing to 2px x 2px in browser rendering.",
                                    'fix'      => "Add 'w-6 h-6 shrink-0' or appropriate dimensions to 'tailwindClasses'.",
                                ];
                            }
                        }

                        // 2.1.5 Thẩm định khối Image Native & Aspect Ratio
                        if ( $block_type === 'image' ) {
                            if ( empty( $json_data['aspectRatio'] ) ) {
                                $tw = $json_data['tailwindClasses'] ?? '';
                                if ( ! preg_match( '/\baspect-/', $tw ) ) {
                                    $issues[] = [
                                        'severity' => 'INFO',
                                        'rule'     => 'Image Aspect Ratio Notice',
                                        'message'  => "Block '{$full_name}' does not specify 'aspectRatio'. Skaaa Image render.php defaults to 'aspect-square' (1:1). If your image is portrait or 16:9, specify 'aspectRatio': 'aspect-auto' or 'aspect-[460/580]' to avoid square cropping.",
                                        'fix'      => 'Add "aspectRatio": "aspect-auto" or custom ratio like "aspect-[W/H]" if image is not 1:1 square.',
                                    ];
                                }
                            }
                        }
                    }
                }

                // 2.2 Kiểm tra dạng tự đóng vs đóng mở
                if ( in_array( $block_type, $self_closing_types, true ) && ! $is_self ) {
                    $issues[] = [
                        'severity' => 'WARNING',
                        'rule'     => 'Self-Closing Comment Protocol',
                        'message'  => "Atomic block '{$block_type}' should be self-closing (<!-- wp:{$full_name} ... /-->).",
                        'fix'      => "Append '/--> ' to make it a self-closing comment.",
                    ];
                }

                if ( in_array( $block_type, $open_close_types, true ) && $is_self ) {
                    $issues[] = [
                        'severity' => 'ERROR',
                        'rule'     => 'Container Block Closure',
                        'message'  => "Container block '{$block_type}' cannot be self-closing. It must enclose inner blocks.",
                        'fix'      => "Use <!-- wp:{$full_name} ... --> ... <!-- /wp:{$full_name} --> syntax.",
                    ];
                }
            }
        } else {
            $issues[] = [
                'severity' => 'WARNING',
                'rule'     => 'No Blocks Found',
                'message'  => 'No Skaaa block comments (<!-- wp:skaaaaa-builder/... -->) detected in input content.',
                'fix'      => 'Ensure your markup uses proper Gutenberg block syntax.',
            ];
        }

        // 3. Kiểm tra Skaaapine / Alpine.js directives
        // Bắt mọi @click không có .prevent (kể cả khi nằm trong chuỗi JSON escape)
        if ( preg_match_all( '/@click(?!\.prevent)\b[^"\'\s>\}]*/i', $content, $click_matches ) ) {
            foreach ( $click_matches[0] as $bad_click ) {
                $issues[] = [
                    'severity' => 'ERROR',
                    'rule'     => 'Skaaapine Click Protocol',
                    'message'  => "Directive '{$bad_click}' lacks .prevent modifier. Click events in Skaaa must use @click.prevent to prevent URL hash '#' and unwanted page scrolling.",
                    'fix'      => "Change to @click.prevent='...'",
                ];
            }
        }

        // Bắt sự kiện onclick thô
        if ( preg_match_all( '/\bonclick\s*=/i', $content, $raw_onclick ) ) {
            $issues[] = [
                'severity' => 'ERROR',
                'rule'     => 'Raw Onclick Forbidden',
                'message'  => "Raw 'onclick' attribute detected. All dynamic events must use Alpine.js / Skaaapine directives (@click.prevent).",
                'fix'      => "Replace onclick with @click.prevent='...'",
            ];
        }

        $x_data_matches = substr_count( $content, 'x-data' );
        if ( $x_data_matches > 1 ) {
            $issues[] = [
                'severity' => 'WARNING',
                'rule'     => 'Skaaapine Scope Shadowing',
                'message'  => "Multiple x-data declarations found ({$x_data_matches}). Inter-block communication must exclusively use Alpine.store.",
                'fix'      => 'Use Alpine.store("storeName") for cross-block state rather than nested x-data scopes.',
            ];
        }

        // 4. Kiểm tra Zero Inline CSS & Zero !important
        if ( preg_match_all( '/style\s*=\s*["\'][^"\']+["\']/i', $content, $style_matches ) ) {
            foreach ( $style_matches[0] as $style_str ) {
                $issues[] = [
                    'severity' => 'WARNING',
                    'rule'     => 'Zero Inline CSS Directive',
                    'message'  => "Inline CSS detected: '{$style_str}'. All styling must use Tailwind utility classes in 'tailwindClasses' attribute.",
                    'fix'      => 'Convert inline styles to Tailwind v4 classes in tailwindClasses attribute.',
                ];
            }
        }

        if ( preg_match( '/\b![a-z0-9_-]+\b|!important/i', $content ) ) {
            $issues[] = [
                'severity' => 'WARNING',
                'rule'     => 'Zero !important Directive',
                'message'  => "Usage of !important or '!class' flag detected in styling.",
                'fix'      => 'Rely on natural CSS specificity rather than !important.',
            ];
        }

        // 5. Kiểm tra Fallback Ảnh trong thẻ <img>
        if ( preg_match_all( '/<img\b[^>]*>/i', $content, $img_matches ) ) {
            foreach ( $img_matches[0] as $img_tag ) {
                if ( ! str_contains( $img_tag, 'onerror' ) ) {
                    $issues[] = [
                        'severity' => 'INFO',
                        'rule'     => 'Media Fallback Safety',
                        'message'  => "Image tag lacks 'onerror' fallback attribute: " . substr( $img_tag, 0, 50 ) . '...',
                        'fix'      => "Add onerror=\"this.src='...'\" for bulletproof image fallback.",
                    ];
                }
            }
        }

        // Phân loại kết quả
        $errors   = array_filter( $issues, fn( $i ) => $i['severity'] === 'ERROR' );
        $warnings = array_filter( $issues, fn( $i ) => $i['severity'] === 'WARNING' );
        $is_valid = empty( $errors );

        if ( self::$format === 'json' ) {
            echo json_encode( [
                'is_valid'     => $is_valid,
                'blocks_count' => $blocks_count,
                'errors_count' => count( $errors ),
                'warns_count'  => count( $warnings ),
                'issues'       => array_values( $issues ),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        echo "\n\033[1;36m=== SKAAA BLOCK VALIDATION REPORT ===\033[0m\n";
        echo "Blocks Detected:  \033[33m{$blocks_count}\033[0m\n";
        echo "Status:           " . ( $is_valid ? "\033[1;32mPASSED (Valid)\033[0m" : "\033[1;31mFAILED (Contains Errors)\033[0m" ) . "\n";
        echo "Errors:           \033[31m" . count( $errors ) . "\033[0m\n";
        echo "Warnings:         \033[33m" . count( $warnings ) . "\033[0m\n\n";

        if ( empty( $issues ) ) {
            echo "\033[1;32m✓ 100% Compliant: Clean Flat DOM, valid comment syntax, and correct Skaaapine protocol!\033[0m\n\n";
            return;
        }

        foreach ( array_values( $issues ) as $idx => $issue ) {
            $num   = $idx + 1;
            $color = match ( $issue['severity'] ) {
                'ERROR'   => "\033[1;31m",
                'WARNING' => "\033[1;33m",
                default   => "\033[1;34m",
            };
            echo "{$color}[{$issue['severity']}]\033[0m #{$num} \033[1m{$issue['rule']}\033[0m\n";
            echo "  Message: {$issue['message']}\n";
            echo "  Fix:     \033[32m{$issue['fix']}\033[0m\n\n";
        }
    }

    private static function handle_render( string $content ): void {
        if ( ! function_exists( 'do_blocks' ) ) {
            self::output_error( 'WordPress do_blocks() function is not available.' );
            exit( 1 );
        }

        $html = do_blocks( $content );

        if ( self::$format === 'json' ) {
            echo json_encode( [
                'raw_length'      => strlen( $content ),
                'rendered_length' => strlen( $html ),
                'html'            => $html,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        echo "\n\033[1;32m=== COMPILED FRONTEND HTML ===\033[0m\n";
        echo $html . "\n\n";
    }

    private static function handle_create_test_page( string $content, string $title, string $status ): void {
        // Establish Administrator context and disable KSES filters in CLI
        // to prevent stripping SVG code or HTML markup inside Gutenberg block comments
        if ( function_exists( 'kses_remove_filters' ) ) {
            kses_remove_filters();
        }
        if ( function_exists( 'get_users' ) ) {
            $admins = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
            if ( ! empty( $admins[0] ) ) {
                wp_set_current_user( $admins[0]->ID );
            }
        }

        $post_data = [
            'post_title'   => wp_slash( sanitize_text_field( $title ) ),
            'post_content' => wp_slash( $content ),
            'post_status'  => in_array( $status, [ 'draft', 'publish' ], true ) ? $status : 'publish',
            'post_type'    => 'page',
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            self::output_error( 'Failed to create test page: ' . $post_id->get_error_message() );
            exit( 1 );
        }

        $permalink = get_permalink( $post_id );
        $edit_url  = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

        if ( self::$format === 'json' ) {
            echo json_encode( [
                'success'   => true,
                'post_id'   => $post_id,
                'title'     => $title,
                'status'    => $status,
                'permalink' => $permalink,
                'edit_url'  => $edit_url,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        echo "\n\033[1;32m=== TEST PAGE CREATED SUCCESSFULLY ===\033[0m\n";
        echo "Post ID:     \033[36m{$post_id}\033[0m\n";
        echo "Title:       {$title}\n";
        echo "Status:      \033[33m{$status}\033[0m\n";
        echo "Preview URL: \033[1;34m{$permalink}\033[0m\n";
        echo "Edit in WP:  \033[35m{$edit_url}\033[0m\n\n";
    }

    private static function output_error( string $msg ): void {
        if ( self::$format === 'json' ) {
            echo json_encode( [ 'error' => true, 'message' => $msg ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
        } else {
            fwrite( STDERR, "\n\033[31m[ERROR]\033[0m {$msg}\n\n" );
        }
    }

    private static function show_help(): void {
        echo <<<HELP

\033[1;36mSKAAA BLOCK SYNTHESIZER & VALIDATOR TOOL (block-tool.php)\033[0m
Usage:
  php .agent/harness/block-tool.php [command] [options]

Commands:
  --validate="<markup_or_file>"   Audit block comments for Flat DOM, self-closing syntax & Skaaapine
  --render="<markup_or_file>"     Compile Gutenberg blocks into frontend HTML via do_blocks()
  --create-test-page="<markup>"   Create a WordPress page with the given blocks and return preview link

Options:
  --format=table|json             Output format (default: table)
  --title="Title"                 Custom title for created test page
  --status=draft|publish          Post status for test page (default: publish)
  --help, -h                      Display this help menu

Examples:
  php .agent/harness/block-tool.php --validate='<!-- wp:skaaaaa-builder/container {"tag":"div"} --><!-- /wp:skaaaaa-builder/container -->'
  php .agent/harness/block-tool.php --validate=path/to/template.html --format=json
  php .agent/harness/block-tool.php --create-test-page='<!-- wp:skaaaaa-builder/text {"content":"Hello World"} /-->' --title="Hero Test"

HELP;
    }
}

// Execute CLI
Skaaa_Block_Tool::run( $argv ?? [] );
