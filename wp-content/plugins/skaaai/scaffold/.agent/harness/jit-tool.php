<?php
/**
 * Tailwind JIT Pre-flight Checker CLI Tool (jit-tool.php)
 *
 * Provides instant syntax validation, unresolved class diagnostics,
 * suggestion hints, and CSS compilation preview against tailwind-rules.json.
 * Designed for UI/UX Designers and AI Agents before committing blocks.
 *
 * Usage:
 *   php .agent/harness/jit-tool.php --check="flex items-center bg-slate-900 text-white p-4"
 *   php .agent/harness/jit-tool.php --scan="path/to/template.html"
 *   php .agent/harness/jit-tool.php --scan="<!-- wp:skaaaaa-builder/container {\"classes\":\"flex gap-4\"} -->"
 *   php .agent/harness/jit-tool.php --rules
 *   php .agent/harness/jit-tool.php --help
 *
 * Options:
 *   --format=table|json   Output format (default: table)
 *   --compile             Include generated CSS preview for valid classes
 *   --help, -h            Show usage help
 *
 * @package Skaaai
 * @version 1.2.0
 */

declare(strict_types=1);

class Skaaa_JIT_Tool {

    private static string $format = 'table';
    private static bool $compile_preview = false;
    private static ?array $rules = null;
    private static ?string $rules_path = null;

    /**
     * Entry point for CLI execution.
     *
     * @param array $args $argv array
     */
    public static function run( array $args ): void {
        $options = self::parse_args( $args );

        if ( isset( $options['help'] ) || isset( $options['h'] ) || empty( $options ) ) {
            self::show_help();
            exit( 0 );
        }

        self::$format = strtolower( (string) ( $options['format'] ?? 'table' ) );
        self::$compile_preview = isset( $options['compile'] );

        // Load rules statically
        self::load_rules();

        try {
            if ( isset( $options['rules'] ) ) {
                self::handle_show_rules();
            } elseif ( isset( $options['check'] ) ) {
                $raw_classes = (string) $options['check'];
                self::handle_check_classes( $raw_classes );
            } elseif ( isset( $options['scan'] ) ) {
                $target = (string) $options['scan'];
                self::handle_scan( $target );
            } else {
                self::show_help();
            }
        } catch ( \Throwable $e ) {
            self::output_error( $e->getMessage() );
            exit( 1 );
        }
    }

    /**
     * Parse CLI arguments into key-value map.
     */
    private static function parse_args( array $args ): array {
        $options = [];
        array_shift( $args ); // remove script filename

        foreach ( $args as $arg ) {
            if ( str_starts_with( $arg, '--' ) ) {
                $parts = explode( '=', substr( $arg, 2 ), 2 );
                $key   = $parts[0];
                $val   = $parts[1] ?? true;
                $options[ $key ] = $val;
            } elseif ( str_starts_with( $arg, '-' ) ) {
                $key = substr( $arg, 1 );
                $options[ $key ] = true;
            }
        }

        return $options;
    }

    /**
     * Locate and load tailwind-rules.json without database reliance.
     */
    private static function load_rules(): void {
        if ( self::$rules !== null ) {
            return;
        }

        $candidates = [
            dirname( __DIR__, 4 ) . '/wp-content/plugins/skaaa-no-code-design/inc/design-engine/tailwind-rules.json',
            dirname( __DIR__, 3 ) . '/wp-content/plugins/skaaa-no-code-design/inc/design-engine/tailwind-rules.json',
            dirname( __DIR__, 2 ) . '/wp-content/plugins/skaaa-no-code-design/inc/design-engine/tailwind-rules.json',
            dirname( __DIR__, 5 ) . '/wp-content/plugins/skaaa-no-code-design/inc/design-engine/tailwind-rules.json',
            getcwd() . '/wp-content/plugins/skaaa-no-code-design/inc/design-engine/tailwind-rules.json',
        ];

        foreach ( $candidates as $path ) {
            if ( file_exists( $path ) ) {
                $content = @file_get_contents( $path );
                if ( $content ) {
                    $json = json_decode( $content, true );
                    if ( is_array( $json ) ) {
                        self::$rules = $json;
                        self::$rules_path = realpath( $path );
                        return;
                    }
                }
            }
        }

        // Minimal fallback in case design plugin is missing
        self::$rules = [
            'mediaQueries' => [ 'sm' => true, 'md' => true, 'lg' => true, 'xl' => true, '2xl' => true ],
            'palette'      => [ 'slate' => [], 'gray' => [], 'blue' => [], 'emerald' => [], 'red' => [] ],
            'layoutMap'    => [ 'flex' => 'display: flex;', 'grid' => 'display: grid;', 'items-center' => 'align-items: center;' ],
            'sizeMap'      => [ 'xs' => true, 'sm' => true, 'base' => true, 'lg' => true, 'xl' => true, '2xl' => true ],
        ];
        self::$rules_path = 'embedded-fallback';
    }

    /**
     * Handle `--rules` command to print rule dictionary summary.
     */
    private static function handle_show_rules(): void {
        $rules = self::$rules ?? [];
        $stats = [
            'rules_file'         => self::$rules_path,
            'media_queries'      => count( $rules['mediaQueries'] ?? [] ),
            'palette_colors'     => count( $rules['palette'] ?? [] ),
            'basic_colors'       => count( $rules['basicColors'] ?? [] ),
            'layout_utilities'   => count( $rules['layoutMap'] ?? [] ),
            'typography_sizes'   => count( $rules['sizeMap'] ?? [] ),
            'font_weights'       => count( $rules['weights'] ?? [] ),
            'box_shadows'        => count( $rules['shadowMap'] ?? [] ),
            'radius_variants'    => count( $rules['radiusMap'] ?? [] ),
            'flexbox_v4_extras'  => count( $rules['flexExtra'] ?? [] ),
        ];

        if ( self::$format === 'json' ) {
            echo json_encode( [ 'success' => true, 'rules' => $stats ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        echo "\n\033[1;36m=== SKAAA TAILWIND JIT RULE DICTIONARY ===\033[0m\n";
        echo "Rules Source: \033[33m" . ( self::$rules_path ?? 'Unknown' ) . "\033[0m\n";
        echo str_repeat( '-', 50 ) . "\n";
        foreach ( $stats as $key => $val ) {
            if ( $key === 'rules_file' ) continue;
            $label = ucwords( str_replace( '_', ' ', $key ) );
            printf( "  %-22s : \033[32m%s\033[0m definitions\n", $label, (string) $val );
        }
        echo str_repeat( '-', 50 ) . "\n";
        echo "Palette available: " . implode( ', ', array_keys( $rules['palette'] ?? [] ) ) . "\n\n";
    }

    /**
     * Handle `--check` command for a space-separated string of classes.
     */
    private static function handle_check_classes( string $raw_classes ): void {
        $class_list = array_values( array_unique( array_filter( preg_split( '/\s+/', trim( $raw_classes ) ) ?: [] ) ) );

        if ( empty( $class_list ) ) {
            self::output_error( 'No CSS classes provided to check.' );
            return;
        }

        self::evaluate_and_report( $class_list );
    }

    /**
     * Handle `--scan` command for a file path or raw markup string.
     */
    private static function handle_scan( string $target ): void {
        $content = '';
        $source_name = 'Inline Markup';

        if ( file_exists( $target ) && is_file( $target ) ) {
            $content = (string) file_get_contents( $target );
            $source_name = basename( $target );
        } else {
            $content = $target;
        }

        if ( empty( trim( $content ) ) ) {
            self::output_error( 'Target content is empty.' );
            return;
        }

        // Extract class strings from HTML / Gutenberg attributes
        $extracted_classes = [];

        // 1. Gutenberg comment attribute: "classes":"..."
        if ( preg_match_all( '/"classes"\s*:\s*"([^"]+)"/', $content, $matches ) ) {
            foreach ( $matches[1] as $cls_str ) {
                $tokens = preg_split( '/\s+/', $cls_str ) ?: [];
                foreach ( $tokens as $t ) {
                    if ( $t !== '' ) $extracted_classes[] = $t;
                }
            }
        }

        // 2. HTML class="..." or className="..."
        if ( preg_match_all( '/(?:class|className)\s*=\s*["\']([^"\']+)["\']/', $content, $matches ) ) {
            foreach ( $matches[1] as $cls_str ) {
                $tokens = preg_split( '/\s+/', $cls_str ) ?: [];
                foreach ( $tokens as $t ) {
                    if ( $t !== '' ) $extracted_classes[] = $t;
                }
            }
        }

        $unique_classes = array_values( array_unique( $extracted_classes ) );

        if ( empty( $unique_classes ) ) {
            self::output_error( "No CSS class attributes found in {$source_name}." );
            return;
        }

        if ( self::$format === 'table' ) {
            echo "\nScanned \033[36m{$source_name}\033[0m: Found \033[33m" . count( $unique_classes ) . "\033[0m unique classes.\n";
        }

        self::evaluate_and_report( $unique_classes, $source_name );
    }

    /**
     * Evaluate each class, compile CSS if requested, and format output.
     */
    private static function evaluate_and_report( array $classes, string $source = '' ): void {
        $results = [
            'valid'   => [],
            'skipped' => [],
            'invalid' => [],
        ];

        $compiled_css_blocks = [];

        foreach ( $classes as $class ) {
            // Check WordPress / Skaaa internal semantic classes
            if ( preg_match( '/^(wp-|skaaa-|is-|components-|editor-)/', $class ) ) {
                $results['skipped'][] = [
                    'class'    => $class,
                    'category' => 'Internal / Gutenberg',
                    'reason'   => 'Platform reserved class, passed directly without JIT overhead',
                ];
                continue;
            }

            $validation = self::validate_class( $class );

            if ( $validation['valid'] ) {
                $results['valid'][] = [
                    'class'    => $class,
                    'category' => $validation['category'],
                    'css'      => $validation['css'],
                ];
                if ( ! empty( $validation['css'] ) ) {
                    $compiled_css_blocks[] = "/* {$class} */\n" . self::format_css_rule( $class, $validation['css'] );
                }
            } else {
                $suggestion = self::get_suggestion( $class );
                $results['invalid'][] = [
                    'class'      => $class,
                    'reason'     => $validation['reason'] ?? 'Unrecognized Tailwind JIT utility',
                    'suggestion' => $suggestion,
                ];
            }
        }

        $total_count   = count( $classes );
        $valid_count   = count( $results['valid'] );
        $skipped_count = count( $results['skipped'] );
        $invalid_count = count( $results['invalid'] );
        $is_success    = ( $invalid_count === 0 );

        // 1. JSON Output format
        if ( self::$format === 'json' ) {
            $payload = [
                'success' => $is_success,
                'source'  => $source ?: 'cli-check',
                'summary' => [
                    'total'   => $total_count,
                    'valid'   => $valid_count,
                    'skipped' => $skipped_count,
                    'invalid' => $invalid_count,
                ],
                'details' => $results,
            ];

            if ( self::$compile_preview ) {
                $payload['compiled_css'] = implode( "\n\n", $compiled_css_blocks );
            }

            echo json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        // 2. ANSI Table / CLI Report format
        echo "\n\033[1;36m=== SKAAA TAILWIND JIT PRE-FLIGHT REPORT ===\033[0m\n";
        echo str_repeat( '=', 65 ) . "\n";

        // Summary Bar
        if ( $is_success ) {
            echo "\033[1;32m✓ ALL CLASSES VALID!\033[0m Passed {$valid_count}/{$total_count} checked classes.\n";
        } else {
            echo "\033[1;31m✗ SYNTAX ISSUES DETECTED!\033[0m {$invalid_count} invalid class(es) found out of {$total_count}.\n";
        }
        echo str_repeat( '-', 65 ) . "\n";

        // Display Invalid Classes with Suggestions
        if ( ! empty( $results['invalid'] ) ) {
            echo "\n\033[1;31m[UNRESOLVED / INVALID CLASSES]\033[0m\n";
            printf( "  %-30s | %-28s\n", "Class", "Diagnosis / Suggestion" );
            echo "  " . str_repeat( '-', 60 ) . "\n";

            foreach ( $results['invalid'] as $item ) {
                $sugg = ! empty( $item['suggestion'] ) ? "\033[33mHint: {$item['suggestion']}\033[0m" : "\033[31m{$item['reason']}\033[0m";
                printf( "  \033[31m%-30s\033[0m | %s\n", $item['class'], $sugg );
            }
            echo "\n";
        }

        // Display Skipped Internal Classes
        if ( ! empty( $results['skipped'] ) ) {
            echo "\033[1;34m[SKIPPED INTERNAL PLATFORM CLASSES]\033[0m\n";
            $skipped_names = array_column( $results['skipped'], 'class' );
            echo "  \033[90m" . implode( ', ', $skipped_names ) . "\033[0m\n\n";
        }

        // Display Valid Classes
        if ( ! empty( $results['valid'] ) ) {
            echo "\033[1;32m[VALID TAILWIND UTILITIES (" . count( $results['valid'] ) . ")]\033[0m\n";
            printf( "  %-30s | %-16s | %s\n", "Class", "Category", "Resolved CSS snippet" );
            echo "  " . str_repeat( '-', 60 ) . "\n";

            foreach ( array_slice( $results['valid'], 0, 30 ) as $item ) {
                $snippet = strlen( $item['css'] ) > 45 ? substr( $item['css'], 0, 42 ) . '...' : $item['css'];
                printf( "  \033[32m%-30s\033[0m | %-16s | \033[90m%s\033[0m\n", $item['class'], $item['category'], $snippet );
            }

            if ( count( $results['valid'] ) > 30 ) {
                echo "  ... and " . ( count( $results['valid'] ) - 30 ) . " more valid classes.\n";
            }
            echo "\n";
        }

        // CSS Preview if requested
        if ( self::$compile_preview && ! empty( $compiled_css_blocks ) ) {
            echo "\033[1;35m[COMPILED CSS PREVIEW]\033[0m\n";
            echo str_repeat( '-', 65 ) . "\n";
            echo implode( "\n\n", $compiled_css_blocks ) . "\n";
            echo str_repeat( '-', 65 ) . "\n\n";
        }

        echo str_repeat( '=', 65 ) . "\n\n";

        if ( ! $is_success ) {
            exit( 1 );
        }
    }

    /**
     * Validate an individual class against rules dictionary.
     *
     * @param string $class Full class name with optional prefixes
     * @return array [ 'valid' => bool, 'category' => string, 'css' => string, 'reason' => string ]
     */
    private static function validate_class( string $class ): array {
        $rules = self::$rules ?? [];

        // 1. Parse and validate modifier prefixes (e.g. sm:hover:bg-blue-600)
        $modifiers = [];
        $base_class = $class;

        if ( str_contains( $class, ':' ) ) {
            $parts = explode( ':', $class );
            $base_class = array_pop( $parts );
            $modifiers = $parts;

            foreach ( $modifiers as $mod ) {
                if ( ! self::is_valid_modifier( $mod ) ) {
                    return [
                        'valid'    => false,
                        'category' => 'invalid-modifier',
                        'css'      => '',
                        'reason'   => "Unknown or unsupported modifier '{$mod}:'",
                    ];
                }
            }
        }

        // 2. Strip negative prefix if present
        $is_negative = false;
        if ( str_starts_with( $base_class, '-' ) && strlen( $base_class ) > 1 ) {
            $is_negative = true;
            $base_class  = substr( $base_class, 1 );
        }

        // 3. Resolve base class
        $resolved = self::resolve_base_utility( $base_class, $rules );

        if ( $resolved['valid'] ) {
            if ( $is_negative && ! empty( $resolved['css'] ) ) {
                $resolved['css'] = preg_replace( '/:\s*([0-9])/', ': -$1', $resolved['css'] );
            }
            return $resolved;
        }

        return [
            'valid'    => false,
            'category' => 'unknown',
            'css'      => '',
            'reason'   => "Class '{$base_class}' is not recognized in Tailwind JIT rules",
        ];
    }

    /**
     * Verify whether a prefix modifier is valid.
     */
    private static function is_valid_modifier( string $mod ): bool {
        $rules = self::$rules ?? [];
        $media = $rules['mediaQueries'] ?? [
            'sm' => true, 'md' => true, 'lg' => true, 'xl' => true, '2xl' => true,
            'max-sm' => true, 'max-md' => true, 'max-lg' => true, 'max-xl' => true, 'max-2xl' => true,
        ];

        if ( isset( $media[ $mod ] ) ) {
            return true;
        }

        $allowed_pseudos = [
            'dark', 'hover', 'focus', 'focus-within', 'focus-visible', 'active',
            'disabled', 'checked', 'not-checked', 'target', 'indeterminate',
            'required', 'valid', 'invalid', 'read-only', 'empty', 'default',
            'in-range', 'out-of-range', 'placeholder-shown', 'autofill',
            'before', 'after', 'placeholder', 'first-letter', 'first-line',
            'marker', 'selection', 'file', 'backdrop', 'forced-colors',
        ];

        if ( in_array( $mod, $allowed_pseudos, true ) ) {
            return true;
        }

        // Group / Peer variants (e.g. group-hover, peer-checked, group-has-[:checked])
        if ( str_starts_with( $mod, 'group-' ) || str_starts_with( $mod, 'peer-' ) || str_starts_with( $mod, 'has-' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Resolve base class to CSS and Category.
     */
    private static function resolve_base_utility( string $cls, array $rules ): array {
        // Direct Map Matches from tailwind-rules.json
        $map_checks = [
            'basicColors'    => 'Color',
            'layoutMap'      => 'Layout',
            'weights'        => 'Font Weight',
            'fontFamily'     => 'Font Family',
            'shadowMap'      => 'Shadow',
            'marginAutoMap'  => 'Margin',
            'textAlignMap'   => 'Typography',
            'textDecoMap'    => 'Typography',
            'textMiscMap'    => 'Typography',
            'whitespaceMap'  => 'Typography',
            'borderStyleMap' => 'Border',
            'borderBasicMap' => 'Border',
            'ringBasic'      => 'Ring',
            'ringOffsetBasic'=> 'Ring',
            'bgUtilMap'      => 'Background',
            'gradBasic'      => 'Gradient',
            'flexExtra'      => 'Flexbox',
            'easeMap'        => 'Transition',
        ];

        foreach ( $map_checks as $mapKey => $cat ) {
            if ( isset( $rules[ $mapKey ][ $cls ] ) ) {
                return [
                    'valid'    => true,
                    'category' => $cat,
                    'css'      => (string) $rules[ $mapKey ][ $cls ],
                ];
            }
        }

        // Transitions (transition, transition-all, transition-colors, transition-opacity, transition-shadow, transition-transform)
        if ( preg_match( '/^transition(-[a-z]+)?$/', $cls, $m ) ) {
            $suffix = $m[1] ?? '-all';
            if ( isset( $rules['transitionMap'][ $suffix ] ) ) {
                return [
                    'valid'    => true,
                    'category' => 'Transition',
                    'css'      => (string) $rules['transitionMap'][ $suffix ],
                ];
            }
        }

        // Colored Shadows (e.g. shadow-blue-500/20, shadow-blue-500)
        if ( preg_match( '/^shadow-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/', $cls, $m ) ) {
            $palette = $m[1];
            $shade   = $m[2];
            $opacity = $m[3] ?? null;
            if ( isset( $rules['palette'][ $palette ][ $shade ] ) ) {
                $hex = $rules['palette'][ $palette ][ $shade ];
                $color_val = $opacity ? "rgba({$hex}, {$opacity}%)" : $hex;
                return [
                    'valid'    => true,
                    'category' => 'Shadow',
                    'css'      => "--tw-shadow-color: {$color_val}; box-shadow: 0 10px 15px -3px var(--tw-shadow-color);",
                ];
            }
        }

        // Typography Sizes (sizeMap keys are xs, sm, base, lg, xl, 2xl..9xl)
        if ( preg_match( '/^text-([a-z0-9]+)$/', $cls, $m ) ) {
            $size_key = $m[1];
            if ( isset( $rules['sizeMap'][ $size_key ] ) ) {
                return [
                    'valid'    => true,
                    'category' => 'Typography',
                    'css'      => (string) $rules['sizeMap'][ $size_key ],
                ];
            }
        }

        // Arbitrary Typography (e.g. text-[14px], text-[14px]/[20px], text-[#ff0000])
        if ( preg_match( '/^text-\[(.+?)\](?:\/(.+))?$/', $cls, $m ) ) {
            $val = str_replace( '_', ' ', $m[1] );
            if ( str_starts_with( $val, '#' ) || str_starts_with( $val, 'rgb' ) || str_starts_with( $val, 'hsl' ) ) {
                return [ 'valid' => true, 'category' => 'Color', 'css' => "color: {$val};" ];
            }
            $lh_part = isset( $m[2] ) ? " line-height: " . trim( $m[2], '[]' ) . ";" : '';
            return [ 'valid' => true, 'category' => 'Typography', 'css' => "font-size: {$val};{$lh_part}" ];
        }

        // Palette Colors: (text|bg|border|ring|from|via|to)-{palette}-{shade}(/{opacity})?
        if ( preg_match( '/^(text|bg|border|ring|from|via|to)-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/', $cls, $m ) ) {
            $prefix  = $m[1];
            $palette = $m[2];
            $shade   = $m[3];
            $opacity = $m[4] ?? null;

            if ( isset( $rules['palette'][ $palette ][ $shade ] ) ) {
                $hex = $rules['palette'][ $palette ][ $shade ];
                $prop_map = [
                    'bg'     => 'background-color',
                    'text'   => 'color',
                    'border' => 'border-color',
                    'ring'   => '--tw-ring-color',
                    'from'   => '--tw-gradient-from',
                    'to'     => '--tw-gradient-to',
                    'via'    => '--tw-gradient-stops',
                ];
                $prop = $prop_map[ $prefix ] ?? 'color';
                $val  = $opacity ? "rgba({$hex}, {$opacity}%)" : $hex;
                return [
                    'valid'    => true,
                    'category' => 'Color',
                    'css'      => "{$prop}: {$val};",
                ];
            }
        }

        // Basic Colors with Opacity (e.g. text-white/80, bg-black/50, border-white/20)
        if ( preg_match( '/^(text|bg|border|ring)-(white|black|transparent)\/([0-9]+)$/', $cls, $m ) ) {
            $hex = $m[2] === 'white' ? '#ffffff' : ( $m[2] === 'black' ? '#000000' : 'transparent' );
            $prop_map = [ 'bg' => 'background-color', 'text' => 'color', 'border' => 'border-color', 'ring' => '--tw-ring-color' ];
            return [
                'valid'    => true,
                'category' => 'Color',
                'css'      => "{$prop_map[$m[1]]}: rgba({$hex}, {$m[3]}%);",
            ];
        }

        // Arbitrary Colors: (text|bg|border|ring|from|via|to)-[#hex] or [rgb(...)]
        if ( preg_match( '/^(text|bg|border|ring|from|via|to)-\[(#[a-fA-F0-9]{3,8}|rgba?\(.+?\)|hsla?\(.+?\))\](?:\/([0-9]+))?$/', $cls, $m ) ) {
            $prop_map = [
                'bg'     => 'background-color',
                'text'   => 'color',
                'border' => 'border-color',
                'ring'   => '--tw-ring-color',
                'from'   => '--tw-gradient-from',
                'to'     => '--tw-gradient-to',
                'via'    => '--tw-gradient-stops',
            ];
            $val = isset( $m[3] ) ? "rgba({$m[2]}, {$m[3]}%)" : $m[2];
            return [
                'valid'    => true,
                'category' => 'Arbitrary Color',
                'css'      => "{$prop_map[$m[1]]}: {$val};",
            ];
        }

        // Spacing: (p|m)[trblxyse]?-([0-9\.]+|\[.+\])
        if ( preg_match( '/^([pm][trblxyse]?)-([0-9\.]+|\[.+\])$/', $cls, $m ) ) {
            $css = self::resolve_spacing( $m[1], $m[2] );
            return [
                'valid'    => true,
                'category' => 'Spacing',
                'css'      => $css,
            ];
        }

        // Dimensions: (w|h|size)-([0-9\.]+|[0-9]+\/[0-9]+|full|screen|auto|min|max|fit|\[.+\])
        if ( preg_match( '/^(w|h|size)-(.+)$/', $cls, $m ) ) {
            $dim_val = self::resolve_dimension( $m[2] );
            if ( $dim_val !== null ) {
                $prop = $m[1] === 'w' ? 'width' : ( $m[1] === 'h' ? 'height' : 'width / height' );
                $css  = $m[1] === 'size' ? "width: {$dim_val}; height: {$dim_val};" : "{$prop}: {$dim_val};";
                return [
                    'valid'    => true,
                    'category' => 'Dimension',
                    'css'      => $css,
                ];
            }
        }

        // Min/Max Dimensions: (min|max)-(w|h)-(...)
        if ( preg_match( '/^(min|max)-(w|h)-(.+)$/', $cls, $m ) ) {
            $dim_val = self::resolve_dimension( $m[3] );
            if ( $dim_val !== null ) {
                $prop = "{$m[1]}-" . ( $m[2] === 'w' ? 'width' : 'height' );
                return [
                    'valid'    => true,
                    'category' => 'Dimension',
                    'css'      => "{$prop}: {$dim_val};",
                ];
            }
        }

        // Max Width Map (e.g. max-w-7xl, max-w-md)
        if ( preg_match( '/^max-w-([a-z0-9]+)$/', $cls, $m ) ) {
            if ( isset( $rules['maxWMap'][ $m[1] ] ) ) {
                return [
                    'valid'    => true,
                    'category' => 'Dimension',
                    'css'      => "max-width: {$rules['maxWMap'][$m[1]]};",
                ];
            }
        }

        // Aspect Ratio & Object Fit
        if ( preg_match( '/^aspect-(video|square|auto|[0-9]+\/[0-9]+|\[.+\])$/', $cls, $m ) ) {
            $val = $m[1] === 'video' ? '16 / 9' : ( $m[1] === 'square' ? '1 / 1' : str_replace( '_', ' ', $m[1] ) );
            return [ 'valid' => true, 'category' => 'Layout', 'css' => "aspect-ratio: {$val};" ];
        }
        if ( preg_match( '/^object-(cover|contain|fill|none|scale-down)$/', $cls, $m ) ) {
            return [ 'valid' => true, 'category' => 'Layout', 'css' => "object-fit: {$m[1]};" ];
        }

        // Border Radius (e.g. rounded, rounded-xl, rounded-full, rounded-t-lg)
        if ( preg_match( '/^rounded(-[trblse]|tl|tr|bl|br)?(-[a-z0-9]+|-\[.+\])?$/', $cls, $m ) ) {
            $suffix = $m[2] ?? '';
            $radius = $rules['radiusMap'][ $suffix ] ?? '0.25rem';
            return [
                'valid'    => true,
                'category' => 'Border',
                'css'      => "border-radius: {$radius};",
            ];
        }

        // Border Width & Ring & Outline
        if ( $cls === 'border' ) {
            return [ 'valid' => true, 'category' => 'Border', 'css' => 'border-width: 1px; border-style: solid;' ];
        }
        if ( preg_match( '/^border-([0-9]+|\[.+\])$/', $cls, $m ) ) {
            $width = is_numeric( $m[1] ) ? "{$m[1]}px" : trim( $m[1], '[]' );
            return [ 'valid' => true, 'category' => 'Border', 'css' => "border-width: {$width}; border-style: solid;" ];
        }
        if ( preg_match( '/^border-([trblxyse])(?:-([0-9]+|\[.+\]))?$/', $cls, $m ) ) {
            $width = isset( $m[2] ) ? ( is_numeric( $m[2] ) ? "{$m[2]}px" : trim( $m[2], '[]' ) ) : '1px';
            return [ 'valid' => true, 'category' => 'Border', 'css' => "border-{$m[1]}-width: {$width}; border-style: solid;" ];
        }
        if ( $cls === 'ring' || $cls === 'ring-inset' || preg_match( '/^ring-([0-8]|\[.+\])$/', $cls ) ||
             $cls === 'outline' || $cls === 'outline-none' || preg_match( '/^outline-([0-9]+)$/', $cls ) ||
             preg_match( '/^ring-offset-([0-9]+)$/', $cls ) ) {
            return [ 'valid' => true, 'category' => 'Ring/Outline', 'css' => 'box-shadow / outline rule' ];
        }

        // Grid & Gap
        if ( preg_match( '/^grid-cols-([1-9]|1[0-2]|none|subgrid|\[.+\])$/', $cls, $m ) ) {
            $val = is_numeric( $m[1] ) ? "repeat({$m[1]}, minmax(0, 1fr))" : str_replace( '_', ' ', trim( $m[1], '[]' ) );
            return [ 'valid' => true, 'category' => 'Grid', 'css' => "grid-template-columns: {$val};" ];
        }
        if ( preg_match( '/^col-span-([1-9]|1[0-2]|full)$/', $cls, $m ) ) {
            $val = $m[1] === 'full' ? '1 / -1' : "span {$m[1]} / span {$m[1]}";
            return [ 'valid' => true, 'category' => 'Grid', 'css' => "grid-column: {$val};" ];
        }
        if ( preg_match( '/^gap-([0-9\.]+|\[.+\])$/', $cls, $m ) ) {
            $val = is_numeric( $m[1] ) ? ( floatval( $m[1] ) * 0.25 ) . 'rem' : trim( $m[1], '[]' );
            return [ 'valid' => true, 'category' => 'Grid/Flex', 'css' => "gap: {$val};" ];
        }
        if ( preg_match( '/^gap-(x|y)-([0-9\.]+|\[.+\])$/', $cls, $m ) ) {
            $prop = $m[1] === 'x' ? 'column-gap' : 'row-gap';
            $val  = is_numeric( $m[2] ) ? ( floatval( $m[2] ) * 0.25 ) . 'rem' : trim( $m[2], '[]' );
            return [ 'valid' => true, 'category' => 'Grid/Flex', 'css' => "{$prop}: {$val};" ];
        }

        // Typography: Leading, Tracking, Line-clamp
        if ( preg_match( '/^leading-([a-z0-9\.]+)$/', $cls, $m ) ) {
            if ( isset( $rules['leadingMap'][ $m[1] ] ) ) {
                return [ 'valid' => true, 'category' => 'Typography', 'css' => "line-height: {$rules['leadingMap'][$m[1]]};" ];
            }
            if ( is_numeric( $m[1] ) ) {
                return [ 'valid' => true, 'category' => 'Typography', 'css' => "line-height: " . ( floatval( $m[1] ) * 0.25 ) . "rem;" ];
            }
        }
        if ( preg_match( '/^tracking-([a-z]+)$/', $cls, $m ) && isset( $rules['trackingMap'][ $m[1] ] ) ) {
            return [ 'valid' => true, 'category' => 'Typography', 'css' => "letter-spacing: {$rules['trackingMap'][$m[1]]};" ];
        }
        if ( preg_match( '/^line-clamp-([0-9]+|none)$/', $cls, $m ) ) {
            return [ 'valid' => true, 'category' => 'Typography', 'css' => "-webkit-line-clamp: {$m[1]};" ];
        }

        // Display & Position
        if ( in_array( $cls, [ 'block', 'inline-block', 'inline', 'hidden', 'relative', 'absolute', 'fixed', 'sticky', 'static', 'container', 'skaaa-container', 'isolate', 'sr-only' ], true ) ) {
            $css = $cls === 'hidden' ? 'display: none;' : ( in_array( $cls, [ 'relative', 'absolute', 'fixed', 'sticky', 'static' ] ) ? "position: {$cls};" : "display: {$cls};" );
            return [ 'valid' => true, 'category' => 'Layout', 'css' => $css ];
        }
        if ( preg_match( '/^(top|right|bottom|left|inset|inset-x|inset-y)-([0-9\.]+|auto|full|px|\[.+\])$/', $cls, $m ) ) {
            $val = $m[2] === 'full' ? '100%' : ( $m[2] === 'auto' ? 'auto' : ( $m[2] === 'px' ? '1px' : ( is_numeric( $m[2] ) ? ( floatval( $m[2] ) * 0.25 ) . 'rem' : trim( $m[2], '[]' ) ) ) );
            return [ 'valid' => true, 'category' => 'Position', 'css' => "{$m[1]}: {$val};" ];
        }
        if ( preg_match( '/^z-([0-9]+|auto|\[.+\])$/', $cls, $m ) ) {
            return [ 'valid' => true, 'category' => 'Position', 'css' => "z-index: {$m[1]};" ];
        }
        if ( preg_match( '/^overflow-(hidden|auto|visible|scroll|x-hidden|x-auto|y-hidden|y-auto)$/', $cls, $m ) ) {
            return [ 'valid' => true, 'category' => 'Layout', 'css' => "overflow: {$m[1]};" ];
        }

        // Gradients
        if ( preg_match( '/^bg-gradient-to-(t|tr|r|br|b|bl|l|tl)$/', $cls, $m ) ) {
            return [ 'valid' => true, 'category' => 'Gradient', 'css' => 'background-image: linear-gradient(...);' ];
        }

        // Backdrop filters & Blurs
        if ( preg_match( '/^backdrop-blur(-[a-z0-9]+)?$/', $cls, $m ) || preg_match( '/^blur(-[a-z0-9]+)?$/', $cls, $m ) ) {
            $suffix = $m[1] ?? '';
            $val = $rules['blurMap'][ $suffix ] ?? '8px';
            return [ 'valid' => true, 'category' => 'Filter', 'css' => "backdrop-filter: blur({$val});" ];
        }
        if ( preg_match( '/^opacity-([0-9]+)$/', $cls, $m ) ) {
            return [ 'valid' => true, 'category' => 'Opacity', 'css' => "opacity: " . ( intval( $m[1] ) / 100 ) . ";" ];
        }

        // Transforms & Transitions
        if ( preg_match( '/^duration-([0-9]+)$/', $cls, $m ) ) return [ 'valid' => true, 'category' => 'Transition', 'css' => "transition-duration: {$m[1]}ms;" ];
        if ( preg_match( '/^delay-([0-9]+)$/', $cls, $m ) ) return [ 'valid' => true, 'category' => 'Transition', 'css' => "transition-delay: {$m[1]}ms;" ];
        if ( preg_match( '/^scale-([0-9]+)$/', $cls, $m ) ) return [ 'valid' => true, 'category' => 'Transform', 'css' => "transform: scale(" . ( intval( $m[1] ) / 100 ) . ");" ];
        if ( preg_match( '/^rotate-([0-9]+)$/', $cls, $m ) ) return [ 'valid' => true, 'category' => 'Transform', 'css' => "transform: rotate({$m[1]}deg);" ];
        if ( preg_match( '/^translate-([xy])-([0-9\.]+|full|px|\[.+\])$/', $cls, $m ) ) return [ 'valid' => true, 'category' => 'Transform', 'css' => "transform: translate(...);" ];

        // Cursor & Pointer
        if ( preg_match( '/^cursor-(pointer|default|wait|text|move|not-allowed|none|grab|grabbing)$/', $cls, $m ) ) return [ 'valid' => true, 'category' => 'Interactivity', 'css' => "cursor: {$m[1]};" ];
        if ( $cls === 'pointer-events-none' || $cls === 'pointer-events-auto' ) return [ 'valid' => true, 'category' => 'Interactivity', 'css' => $cls ];

        return [ 'valid' => false, 'category' => 'unknown', 'css' => '' ];
    }

    /**
     * Resolve spacing prefix and value to CSS.
     */
    private static function resolve_spacing( string $prefix, string $val ): string {
        $type = $prefix[0] === 'p' ? 'padding' : 'margin';
        $rem  = is_numeric( $val ) ? ( floatval( $val ) * 0.25 ) . 'rem' : trim( $val, '[]' );
        $dir  = substr( $prefix, 1 );

        return match ( $dir ) {
            'x'     => "{$type}-left: {$rem}; {$type}-right: {$rem};",
            'y'     => "{$type}-top: {$rem}; {$type}-bottom: {$rem};",
            't'     => "{$type}-top: {$rem};",
            'b'     => "{$type}-bottom: {$rem};",
            'l'     => "{$type}-left: {$rem};",
            'r'     => "{$type}-right: {$rem};",
            's'     => "{$type}-inline-start: {$rem};",
            'e'     => "{$type}-inline-end: {$rem};",
            default => "{$type}: {$rem};",
        };
    }

    /**
     * Resolve dimension values like full, screen, fractions, numbers, and [brackets].
     */
    private static function resolve_dimension( string $value ): ?string {
        if ( $value === 'full' ) return '100%';
        if ( $value === 'screen' ) return '100vh';
        if ( $value === 'auto' ) return 'auto';
        if ( $value === 'none' ) return 'none';
        if ( in_array( $value, [ 'min', 'max', 'fit' ], true ) ) return "{$value}-content";
        if ( is_numeric( $value ) ) return ( floatval( $value ) * 0.25 ) . 'rem';

        // Fractions like 1/2, 2/3, 1/4
        if ( preg_match( '/^(\d+)\/(\d+)$/', $value, $m ) ) {
            $den = floatval( $m[2] );
            if ( $den > 0 ) {
                return number_format( ( floatval( $m[1] ) / $den ) * 100, 4, '.', '' ) . '%';
            }
        }

        // Arbitrary brackets [350px]
        if ( preg_match( '/^\[(.+)\]$/', $value, $m ) ) {
            return str_replace( '_', ' ', $m[1] );
        }

        return null;
    }

    /**
     * Provide helpful suggestions for known UX/UI designer typos.
     */
    private static function get_suggestion( string $class ): string {
        $typo_map = [
            'flex-center'          => "Use 'items-center justify-center'",
            'align-center'         => "Use 'items-center'",
            'items-middle'         => "Use 'items-center'",
            'justify-items-center' => "Use 'justify-center items-center'",
            'text-bold'            => "Use 'font-bold'",
            'text-semibold'        => "Use 'font-semibold'",
            'text-medium'          => "Use 'font-medium'",
            'text-normal'          => "Use 'font-normal'",
            'rounded-border'       => "Use 'rounded'",
            'cursor-hand'          => "Use 'cursor-pointer'",
            'shadow-box'           => "Use 'shadow' or 'shadow-md'",
            'bg-blur'              => "Use 'backdrop-blur-md' or 'blur-sm'",
            'w-fill'               => "Use 'w-full'",
            'h-fill'               => "Use 'h-full'",
            'mx-center'            => "Use 'mx-auto'",
            'border-radius'        => "Use 'rounded' or 'rounded-xl'",
        ];

        if ( isset( $typo_map[ $class ] ) ) {
            return $typo_map[ $class ];
        }

        // Missing hyphen in colors: e.g. bg-slate900 -> bg-slate-900
        if ( preg_match( '/^(bg|text|border|ring)-([a-z]+)([1-9]00|950|50)$/', $class, $m ) ) {
            return "Missing hyphen: '{$m[1]}-{$m[2]}-{$m[3]}'";
        }

        // Missing brackets for custom pixel value: e.g. w-300px -> w-[300px]
        if ( preg_match( '/^(w|h|p|m|text)-([0-9]+(?:px|rem|%|vh|vw))$/', $class, $m ) ) {
            return "Arbitrary value requires square brackets: '{$m[1]}-[{$m[2]}]'";
        }

        return '';
    }

    /**
     * Format a simple CSS rule for preview.
     */
    private static function format_css_rule( string $class, string $css ): string {
        $escaped = str_replace( [ ':', '[', ']', '/', '.', '#', '%' ], [ '\:', '\[', '\]', '\/', '\.', '\#', '\%' ], $class );
        return ".{$escaped} { {$css} }";
    }

    /**
     * Standard error output handler.
     */
    private static function output_error( string $message ): void {
        if ( self::$format === 'json' ) {
            echo json_encode( [ 'success' => false, 'error' => $message ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
        } else {
            fwrite( STDERR, "\n\033[31m[ERROR]\033[0m {$message}\n\n" );
        }
    }

    /**
     * Show CLI help instructions.
     */
    private static function show_help(): void {
        echo <<<HELP

\033[1;36mSKAAA TAILWIND JIT PRE-FLIGHT CHECKER (jit-tool.php)\033[0m
Validates CSS utility classes against tailwind-rules.json before exporting blocks.

\033[1mUSAGE:\033[0m
  php .agent/harness/jit-tool.php --check="<classes>"   Check a space-separated class list
  php .agent/harness/jit-tool.php --scan="<target>"     Scan an HTML file or block template
  php .agent/harness/jit-tool.php --rules               Inspect loaded JIT rules dictionary
  php .agent/harness/jit-tool.php --help                Display this help screen

\033[1mOPTIONS:\033[0m
  --format=table|json  Output format (default: table)
  --compile            Include compiled CSS preview for valid classes
  -h, --help           Show help information

\033[1mEXAMPLES:\033[0m
  # 1. Quick check of classes with modifiers & arbitrary values
  php .agent/harness/jit-tool.php --check="flex items-center text-sm font-semibold bg-slate-900 hover:bg-slate-800 text-white p-4 rounded-xl"

  # 2. Diagnose invalid designer typos
  php .agent/harness/jit-tool.php --check="flex-center text-bold bg-slate900 w-300px"

  # 3. Scan a Gutenberg block template file
  php .agent/harness/jit-tool.php --scan="path/to/header-block.html"

  # 4. JSON format for AI Agent pipelines
  php .agent/harness/jit-tool.php --check="grid grid-cols-3 gap-6" --format=json

HELP;
        echo "\n";
    }
}

// Execute CLI
if ( php_sapi_name() === 'cli' && isset( $argv ) ) {
    Skaaa_JIT_Tool::run( $argv );
}
