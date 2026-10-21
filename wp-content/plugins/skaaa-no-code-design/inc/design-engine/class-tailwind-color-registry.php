<?php
/**
 * Tailwind Color Registry
 *
 * @package Skaaa_Builder_Core
 */

namespace Skaaa\Builder\Design;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tailwind_Color_Registry
 */
class Tailwind_Color_Registry {

	/**
	 * Hex to RGB helper.
	 */
	public static function hex_to_rgb( string $hex ): string {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return intval( substr( $hex, 0, 2 ), 16 ) . ', ' . intval( substr( $hex, 2, 2 ), 16 ) . ', ' . intval( substr( $hex, 4, 2 ), 16 );
	}

	/**
	 * Mock helper for Tailwind color palette.
	 */
	public static function get_color_hex( $color, $shade ) {
		$custom_colors = self::get_custom_colors();
		if ( isset( $custom_colors[ $color ] ) ) {
			return $custom_colors[ $color ];
		}

		$rules = Tailwind_Config::get_rules();
		$palette = $rules['palette'] ?? array();

		if ( isset( $palette[ $color ][ $shade ] ) ) {
			return $palette[ $color ][ $shade ];
		}

		if ( isset( $palette[ $color ]['default'] ) ) {
			return $palette[ $color ]['default'];
		}

		return null;
	}

	/**
	 * Lấy bảng màu tùy chỉnh từ Physical Cache (tokens.json) hoặc DB.
	 *
	 * @return array Associative array { 'primary' => '#0d46f2', ... }
	 */
	public static function get_custom_colors(): array {
		$upload_dir = wp_upload_dir();
		$cache_file = trailingslashit( $upload_dir['basedir'] ) . 'skaaa-data/tokens.json';
		$colors = array();

		// 1. Đọc từ Physical Cache
		if ( file_exists( $cache_file ) ) {
			$json_data = file_get_contents( $cache_file );
			$tokens = json_decode( $json_data, true );
			if ( isset( $tokens['colors'] ) && is_array( $tokens['colors'] ) ) {
				$colors = $tokens['colors'];
			}
		}

		// 2. Fallback: Nếu không có file cache, đọc từ Database
		if ( empty( $colors ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'skaaa_data_sys_presets';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
				$rows = $wpdb->get_results( "SELECT name, value FROM {$table_name} WHERE type = 'token_color'", ARRAY_A );
				if ( $rows ) {
					foreach ( $rows as $row ) {
						$key = sanitize_key( str_replace( '-', '_', sanitize_title( $row['name'] ) ) );
						$colors[ $key ] = $row['value'];
					}
				}
			}
		}

		// 3. Fallback: Default colors
		if ( empty( $colors ) ) {
			$colors = array(
				'primary'          => '#0d46f2',
				'background-light' => '#f5f6f8',
				'background-dark'  => '#101422',
				'secondary'        => '#1e293b',
			);
		}
		return $colors;
	}

	/**
	 * Lấy bảng màu Dark Mode từ Physical Cache (tokens.json).
	 *
	 * @return array Associative array { 'primary' => '#60a5fa', ... }
	 */
	public static function get_custom_dark_colors(): array {
		$upload_dir = wp_upload_dir();
		$cache_file = trailingslashit( $upload_dir['basedir'] ) . 'skaaa-data/tokens.json';
		$colors = array();

		if ( file_exists( $cache_file ) ) {
			$json_data = file_get_contents( $cache_file );
			$tokens = json_decode( $json_data, true );
			if ( isset( $tokens['darkColors'] ) && is_array( $tokens['darkColors'] ) ) {
				$colors = $tokens['darkColors'];
			}
		}

		if ( empty( $colors ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'skaaa_data_sys_presets';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
				$rows = $wpdb->get_results( "SELECT name, value FROM {$table_name} WHERE type = 'token_dark_color'", ARRAY_A );
				if ( $rows ) {
					foreach ( $rows as $row ) {
						$key = sanitize_key( str_replace( '-', '_', sanitize_title( $row['name'] ) ) );
						$colors[ $key ] = $row['value'];
					}
				}
			}
		}

		if ( empty( $colors ) ) {
			$colors = array(
				'primary'    => '#60a5fa',
				'background' => '#111827',
			);
		}
		return $colors;
	}

	/**
	 * Lấy thông tin Typography từ tokens.json
	 */
	public static function get_typography_config(): array {
		$upload_dir = wp_upload_dir();
		$cache_file = trailingslashit( $upload_dir['basedir'] ) . 'skaaa-data/tokens.json';
		$typography = array(
			'primary' => 'Inter, sans-serif',
			'secondary' => 'Outfit, sans-serif',
			'mono' => 'IBM Plex Mono, monospace',
			'customFontUrl' => ''
		);

		if ( file_exists( $cache_file ) ) {
			$json_data = file_get_contents( $cache_file );
			$tokens = json_decode( $json_data, true );
			if ( isset( $tokens['typography'] ) && is_array( $tokens['typography'] ) ) {
				$typography = wp_parse_args( $tokens['typography'], $typography );
			}
		}

		return $typography;
	}

	/**
	 * Lấy thông tin Tokens cơ bản từ tokens.json (container width, border radius, v.v)
	 */
	public static function get_tokens_config(): array {
		$upload_dir = wp_upload_dir();
		$cache_file = trailingslashit( $upload_dir['basedir'] ) . 'skaaa-data/tokens.json';
		$sys_tokens = array(
			'borderRadius' => '8px',
			'boxShadow' => 'none',
			'containerWidth' => '1280px',
			'transitionDuration' => '150ms',
			'blockGap' => '1.5rem',
			'contentPadding' => '1rem'
		);

		if ( file_exists( $cache_file ) ) {
			$json_data = file_get_contents( $cache_file );
			$parsed = json_decode( $json_data, true );
			if ( isset( $parsed['tokens'] ) && is_array( $parsed['tokens'] ) ) {
				// Convert snake_case keys from tokens.json to camelCase to match defaults
				$camel_tokens = array();
				foreach ( $parsed['tokens'] as $key => $val ) {
					$camel_key = lcfirst( str_replace( ' ', '', ucwords( str_replace( array( '_', '-' ), ' ', $key ) ) ) );
					$camel_tokens[ $camel_key ] = $val;
				}
				$sys_tokens = wp_parse_args( $camel_tokens, $sys_tokens );
			}
		}

		return $sys_tokens;
	}

	/**
	 * Resolve custom brand color classes.
	 *
	 * Hỗ trợ: bg-{name}, text-{name}, border-{name}, ring-{name}
	 * Hỗ trợ opacity modifier: bg-{name}/{opacity}
	 *
	 * @param string $class Tailwind class.
	 * @param array|null $colors Mảng màu tùy chỉnh (tùy chọn)
	 * @return string|null CSS rule hoặc null nếu không khớp.
	 */
	public static function resolve_custom_color( string $class, ?array $colors = null ): ?string {
		$custom_colors = $colors ?? self::get_custom_colors();
		if ( empty( $custom_colors ) ) {
			return null;
		}

		// Pattern: {prefix}-{color_name} hoặc {prefix}-{color_name}/{opacity}
		if ( ! preg_match( '/^(bg|text|border|ring|shadow|from|via|to)-([a-z0-9-]+?)(?:\/([0-9]+))?$/', $class, $matches ) ) {
			return null;
		}

		$prefix     = $matches[1];
		$color_name = $matches[2];
		$opacity    = isset( $matches[3] ) ? intval( $matches[3] ) : null;

		if ( ! isset( $custom_colors[ $color_name ] ) ) {
			return null;
		}

		$hex = $custom_colors[ $color_name ];
		if ( null !== $opacity ) {
			$rgb   = self::hex_to_rgb( $hex );
			$alpha = round( $opacity / 100, 2 );
			$color = "rgba({$rgb}, {$alpha})";
		} else {
			$color = $hex;
		}

		if ( $prefix === 'from' ) {
			return "--tw-gradient-from: {$color}; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);";
		}
		if ( $prefix === 'via' ) {
			return "--tw-gradient-stops: var(--tw-gradient-from), {$color}, var(--tw-gradient-to, transparent);";
		}
		if ( $prefix === 'to' ) {
			return "--tw-gradient-to: {$color};";
		}

		$css_prop_map = array(
			'bg'     => 'background-color',
			'text'   => 'color',
			'border' => 'border-color',
			'ring'   => '--tw-ring-color',
			'shadow' => '--tw-shadow-color',
		);

		if ( isset( $css_prop_map[ $prefix ] ) ) {
			return "{$css_prop_map[ $prefix ]}: {$color};";
		}

		return null;
	}

	/**
	 * Generate a complete CSS block for all custom brand colors.
	 * This is useful for pre-generating "Source of Truth" styles for the editor.
	 *
	 * @return string CSS block.
	 */
	public static function get_brand_colors_css(): string {
		$custom_colors = self::get_custom_colors();
		if ( empty( $custom_colors ) ) {
			return '';
		}

		$css = "/* Skaaa Brand Colors - Plugin Source Truth */\n";
		foreach ( array_keys( $custom_colors ) as $name ) {
			$base_classes = array(
				"bg-{$name}",
				"text-{$name}",
				"border-{$name}",
				"shadow-{$name}",
			);

			foreach ( $base_classes as $base ) {
				$classes_to_compile = array( $base, "{$base}/10", "{$base}/20", "{$base}/30", "{$base}/40", "{$base}/50", "{$base}/60", "{$base}/70", "{$base}/80", "{$base}/90" );
				foreach ( $classes_to_compile as $class ) {
					$rule = self::resolve_custom_color( $class, $custom_colors );
					if ( $rule ) {
						// Add !important to ensure it overrides Tailwind CDN's !important rule
						$rule = str_replace( ';', ' !important;', $rule );
						$escaped = str_replace( array( '/', '.' ), array( '\/', '\.' ), $class );
						$css .= ".{$escaped} { {$rule} }\n";
						$css .= "html body.skaaaaa-builder .{$escaped}.{$escaped} { {$rule} }\n";
						$css .= ".editor-styles-wrapper .{$escaped}.{$escaped} { {$rule} }\n";
					}
				}
			}
		}

		// Thêm CSS dành riêng cho chế độ Dark Mode từ Dark Colors
		$custom_dark_colors = self::get_custom_dark_colors();
		if ( ! empty( $custom_dark_colors ) ) {
			foreach ( array_keys( $custom_dark_colors ) as $name ) {
				$base_classes = array(
					"bg-{$name}",
					"text-{$name}",
					"border-{$name}",
					"shadow-{$name}",
				);

				foreach ( $base_classes as $base ) {
					$classes_to_compile = array( $base, "{$base}/10", "{$base}/20", "{$base}/30", "{$base}/40", "{$base}/50", "{$base}/60", "{$base}/70", "{$base}/80", "{$base}/90" );
					foreach ( $classes_to_compile as $class ) {
						$rule = self::resolve_custom_color( $class, $custom_dark_colors );
						if ( $rule ) {
							// Add !important to ensure it overrides Tailwind CDN's !important rule
							$rule = str_replace( ';', ' !important;', $rule );
							$escaped = str_replace( array( '/', '.' ), array( '\/', '\.' ), $class );
							$dark_escaped = "dark\\:{$escaped}";
							$css .= ".dark .{$dark_escaped} { {$rule} }\n";
							$css .= "html.dark body.skaaaaa-builder .{$dark_escaped}.{$dark_escaped} { {$rule} }\n";
							$css .= "html.dark .editor-styles-wrapper .{$dark_escaped}.{$dark_escaped} { {$rule} }\n";
							$css .= ":is(.editor-styles-wrapper.dark, .editor-styles-wrapper .dark, body.dark .editor-styles-wrapper) .{$dark_escaped}.{$dark_escaped} { {$rule} }\n";
						}
					}
				}
			}
		}

		return $css;
	}
}
