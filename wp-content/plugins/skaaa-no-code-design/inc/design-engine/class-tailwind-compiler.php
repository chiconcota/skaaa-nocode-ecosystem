<?php
/**
 * Tailwind Compiler Class
 *
 * Handles JIT compilation of Tailwind classes.
 * Refactored: Config data and Color Registry moved to isolated static classes.
 *
 * @package Skaaa_Builder_Core
 */

namespace Skaaa\Builder\Design;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tailwind_Compiler
 */
class Tailwind_Compiler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		Tailwind_Config::init();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_filter( 'skaaa_compile_tailwind', array( $this, 'compile_classes' ) );
	}

	/**
	 * Compile Tailwind classes to CSS.
	 *
	 * @param string $classes Space-separated classes.
	 * @return array { css: string, unresolved: array }
	 */
	public function compile_classes( $classes ): array {
		if ( empty( $classes ) ) {
			return array( 'css' => '', 'unresolved' => array() );
		}

		if ( empty( Tailwind_Config::$media_queries ) ) {
			Tailwind_Config::init();
		}

		$class_list = array_unique( array_filter( preg_split( '/\s+/', $classes ) ) );
		$compiled_css = Tailwind_Config::get_core_reset_css();
		$unresolved   = array();

		$media_queries = Tailwind_Config::$media_queries;
		$responsive_css = array();

		foreach ( $class_list as $class ) {
			// Skip internal Semantic/WordPress classes so they don't trigger unresolved/CDN
			// Removed 'has-' and 'block-' because Tailwind uses has-[:checked] and similar
			if ( preg_match('/^(wp-|skaaa-|is-|components-|editor-)/', $class) ) {
				continue;
			}

			// Extract responsive prefix or pseudo-class if any
			$prefix       = '';
			$pseudo       = '';
			$group_prefix = '';
			$custom_media = '';
			$is_dark      = false;
			$base_class   = $class;

			if ( strpos( $class, ':' ) !== false ) {
				$modifiers = explode( ':', $class );
				$base_class = array_pop( $modifiers );
				
				$is_valid_modifiers = true;

				foreach ( $modifiers as $mod ) {
					if ( isset( $media_queries[ $mod ] ) ) {
						$prefix = $mod;
					} elseif ( $mod === 'dark' ) {
						$is_dark = true;
					} elseif ( in_array( $mod, array( 'hover', 'focus', 'focus-within', 'focus-visible', 'active', 'disabled', 'checked', 'target', 'indeterminate', 'required', 'valid', 'invalid', 'read-only', 'empty', 'default', 'in-range', 'out-of-range', 'placeholder-shown', 'autofill' ) ) ) {
						$pseudo .= ':' . $mod;
					} elseif ( in_array( $mod, array( 'before', 'after', 'placeholder', 'first-letter', 'first-line', 'marker', 'selection', 'file', 'backdrop' ) ) ) {
						$pseudo .= '::' . $mod;
					} elseif ( $mod === 'not-checked' ) {
						$pseudo .= ':not(:checked)';
					} elseif ( strpos( $mod, 'group-has-' ) === 0 ) {
						$state = str_replace( 'group-has-', '', $mod );
						$group_prefix .= ".group:has(:{$state}) ";
					} elseif ( strpos( $mod, 'group-' ) === 0 ) {
						$state = str_replace( 'group-', '', $mod );
						$group_prefix .= ".group:{$state} ";
					} elseif ( strpos( $mod, 'peer-has-' ) === 0 ) {
						$state = str_replace( 'peer-has-', '', $mod );
						$group_prefix .= ".peer:has(:{$state}) ~ ";
					} elseif ( strpos( $mod, 'peer-' ) === 0 ) {
						$state = str_replace( 'peer-', '', $mod );
						$group_prefix .= ".peer:{$state} ~ ";
					} elseif ( strpos( $mod, 'has-' ) === 0 ) {
						$state = str_replace( 'has-', '', $mod );
						$pseudo .= ":has(:{$state})";
					} elseif ( $mod === 'forced-colors' ) {
						$custom_media = '@media (forced-colors: active) { ';
					} else {
						$is_valid_modifiers = false;
						break;
					}
				}

				if ( ! $is_valid_modifiers ) {
					$unresolved[] = $class;
					continue;
				}
			}

			// Handle negative prefix
			$is_negative = false;
			if ( strpos( $base_class, '-' ) === 0 && strlen( $base_class ) > 1 ) {
				$is_negative = true;
				$base_class  = substr( $base_class, 1 );
			}

			$css_rule = $this->resolve_class( $base_class, $is_dark );

			// Apply negation
			if ( $is_negative && $css_rule ) {
				$css_rule = preg_replace( '/:\s*([0-9])/', ': -$1', $css_rule );
				$css_rule = preg_replace( '/:\s*calc/', ': calc(-1 * ', $css_rule );
			}

			if ( $css_rule ) {
				$escaped_class = str_replace(
					array( ':', '[', ']', '/', '.', '(', ')', ',', '#', '%', '!', '@', '=' ),
					array( '\:', '\[', '\]', '\/', '\.', '\(', '\)', '\,', '\#', '\%', '\!', '\@', '\=' ),
					$class
				);

				$selector_suffix = $pseudo;
				
				// Handle before/after pseudo-elements inserting empty content if missing
				if ( ( strpos( $pseudo, '::before' ) !== false || strpos( $pseudo, '::after' ) !== false ) && strpos( $css_rule, 'content:' ) === false ) {
					$css_rule = 'content: ""; ' . $css_rule;
				}

				$base_selector = 'html body.skaaaaa-builder';
				$editor_base_selector = '.editor-styles-wrapper';

				if ( $is_dark ) {
					$base_selector = 'html.dark body.skaaaaa-builder';
					$editor_base_selector = ':is(.editor-styles-wrapper.dark, .editor-styles-wrapper .dark, html.dark .editor-styles-wrapper, body.dark .editor-styles-wrapper)';
				}

				if ( strpos( $css_rule, '&' ) === 0 ) {
					$rule_body_skaaa = str_replace( '&', "{$base_selector} {$group_prefix}.{$escaped_class}.{$escaped_class}{$selector_suffix}", $css_rule );
					$full_rule = "{$rule_body_skaaa}";
				} else {
					$full_rule = "{$base_selector} {$group_prefix}.{$escaped_class}.{$escaped_class}{$selector_suffix} { {$css_rule} }\n{$editor_base_selector} {$group_prefix}.{$escaped_class}.{$escaped_class}{$selector_suffix} { {$css_rule} }";
				}

				if ( $custom_media ) {
					$compiled_css .= "\n" . $custom_media . "\n" . "/* Source: {$class} */\n" . $full_rule . "\n}\n";
				} elseif ( $prefix ) {
					if ( isset( $media_queries[ $prefix ] ) ) {
						if ( ! isset( $responsive_css[ $prefix ] ) ) {
							$responsive_css[ $prefix ] = "";
						}
						$responsive_css[ $prefix ] .= "/* Source: {$class} */\n" . $full_rule . "\n";
					} else {
						$unresolved[] = $class;
						continue;
					}
				} else {
					$compiled_css .= "/* Source: {$class} */\n" . $full_rule . "\n";
				}
			} else {
				$unresolved[] = $class;
			}
		}

		foreach ( $media_queries as $prefix => $query ) {
			if ( ! empty( $responsive_css[ $prefix ] ) ) {
				$compiled_css .= "\n" . $query . "\n" . $responsive_css[ $prefix ] . "}\n";
			}
		}

		return array(
			'css'        => $compiled_css,
			'unresolved' => $unresolved,
		);
	}

	/**
	 * Resolve a single Tailwind class to its CSS rule.
	 */
	private function resolve_class( $class, $is_dark = false ): ?string {
		// 0. Custom Brand Colors
		$colors = $is_dark ? Tailwind_Color_Registry::get_custom_dark_colors() : null;
		$custom_result = Tailwind_Color_Registry::resolve_custom_color( $class, $colors );
		if ( $custom_result ) {
			return $custom_result;
		}

		// 0.5 Support Arbitrary Colors: bg-[#1da1f2] or text-[#ff0000]
		if ( preg_match( '/^(text|bg|border|ring|from|via|to)-\[#([a-fA-F0-9]{3,8})\](?:\/([0-9]+))?$/', $class, $matches ) ) {
			$prefix   = $matches[1];
			$hex      = '#' . $matches[2];
			$opacity  = isset( $matches[3] ) ? intval( $matches[3] ) : null;
			if ( $opacity !== null ) {
				$rgb   = Tailwind_Color_Registry::hex_to_rgb( $hex );
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

			$prop_map = array(
				'bg'     => 'background-color',
				'text'   => 'color',
				'border' => 'border-color',
				'ring'   => '--tw-ring-color',
			);

			if ( isset( $prop_map[ $prefix ] ) ) {
				return "{$prop_map[ $prefix ]}: {$color};";
			}
		}

		// 1. Colors (text-*, bg-*)
		if ( preg_match( '/^(text|bg)-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/', $class, $matches ) ) {
			$type    = $matches[1] === 'text' ? 'color' : 'background-color';
			$hex     = Tailwind_Color_Registry::get_color_hex( $matches[2], $matches[3] );
			if ( ! $hex ) {
				return null;
			}
			if ( isset( $matches[4] ) ) {
				$rgb   = Tailwind_Color_Registry::hex_to_rgb( $hex );
				$alpha = round( intval( $matches[4] ) / 100, 2 );
				return "{$type}: rgba({$rgb}, {$alpha});";
			}
			return "{$type}: {$hex};";
		}

		// 1.2 Basic colors
		if ( isset( Tailwind_Config::$basic_colors[ $class ] ) ) {
			return Tailwind_Config::$basic_colors[ $class ];
		}

		// 1.3 Basic colors with opacity
		if ( preg_match( '/^(text|bg)-(white|black)\/([0-9]+)$/', $class, $matches ) ) {
			$type    = $matches[1] === 'text' ? 'color' : 'background-color';
			$hex     = $matches[2] === 'white' ? '#ffffff' : '#000000';
			$rgb     = Tailwind_Color_Registry::hex_to_rgb( $hex );
			$alpha   = round( intval( $matches[3] ) / 100, 2 );
			return "{$type}: rgba({$rgb}, {$alpha});";
		}

		// 2. Font Weights & Font Families
		if ( isset( Tailwind_Config::$font_family[ $class ] ) ) {
			return Tailwind_Config::$font_family[ $class ];
		}
		if ( isset( Tailwind_Config::$weights[ $class ] ) ) {
			return Tailwind_Config::$weights[ $class ];
		}

		// 3. Spacing
		if ( preg_match( '/^([pm][trblxyse]?)-([0-9\.]+)$/', $class, $matches ) ) {
			return $this->resolve_spacing( $matches[1], $matches[2] );
		}
		if ( preg_match( '/^([pm][trblxyse]?)-\[(.+)\]$/', $class, $matches ) ) {
			$type = $matches[1][0] === 'p' ? 'padding' : 'margin';
			$val  = str_replace( '_', ' ', $matches[2] );
			$dir  = substr( $matches[1], 1 );
			$dir_map = array(
				''  => "{$type}: {$val};",
				'x' => "{$type}-left: {$val}; {$type}-right: {$val};",
				'y' => "{$type}-top: {$val}; {$type}-bottom: {$val};",
				't' => "{$type}-top: {$val};",
				'b' => "{$type}-bottom: {$val};",
				'l' => "{$type}-left: {$val};",
				'r' => "{$type}-right: {$val};",
				's' => "{$type}-inline-start: {$val};",
				'e' => "{$type}-inline-end: {$val};",
			);
			return $dir_map[ $dir ] ?? null;
		}

		// 4. Dimensions
		if ( preg_match( '/^(w|h)-(.+)$/', $class, $matches ) ) {
			$prop   = $matches[1] === 'w' ? 'width' : 'height';
			$mapped = $this->resolve_dimension( $matches[2] );
			return $mapped ? "{$prop}: {$mapped};" : null;
		}
		if ( preg_match( '/^size-(.+)$/', $class, $matches ) ) {
			$mapped = $this->resolve_dimension( $matches[1] );
			return $mapped ? "width: {$mapped}; height: {$mapped};" : null;
		}
		if ( preg_match( '/^(min|max)-(w|h)-(.+)$/', $class, $matches ) ) {
			$prop   = $matches[2] === 'w' ? 'width' : 'height';
			$value  = $matches[3];
			$mapped = $this->resolve_dimension( $value );
			if ( $value === 'none' ) $mapped = 'none';
			elseif ( $value === 'full' ) $mapped = '100%';
			
			if ( $mapped ) {
				return "{$matches[1]}-{$prop}: {$mapped};";
			}
		}

		// 4.1 Object Fit & Aspect Ratio
		if ( preg_match( '/^object-(cover|contain|fill|none|scale-down)$/', $class, $matches ) ) {
			return "object-fit: {$matches[1]};";
		}
		if ( preg_match( '/^aspect-(video|square|auto|(\d+)\/(\d+))$/', $class, $matches ) ) {
			$val = $matches[1];
			if ( $val === 'video' ) $val = '16 / 9';
			elseif ( $val === 'square' ) $val = '1 / 1';
			elseif ( strpos($val, '/') !== false ) $val = str_replace('/', ' / ', $val);
			return "aspect-ratio: {$val};";
		}
		if ( preg_match( '/^aspect-\[(.+)\]$/', $class, $matches ) ) {
			$val = str_replace( '/', ' / ', $matches[1] );
			return "aspect-ratio: {$val};";
		}

		// 4.2 Shadows & Z-Index & Container & Max Width & Auto Margin
		if ( isset( Tailwind_Config::$shadow_map[ $class ] ) ) return Tailwind_Config::$shadow_map[ $class ];
		if ( preg_match( '/^shadow-\[(.+)\]$/', $class, $matches ) ) {
			$val = str_replace( '_', ' ', $matches[1] );
			return "box-shadow: {$val};";
		}
		if ( preg_match( '/^shadow-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/', $class, $matches ) ) {
			$hex = Tailwind_Color_Registry::get_color_hex( $matches[1], $matches[2] );
			if ( $hex ) {
				if ( isset( $matches[3] ) ) {
					$rgb   = Tailwind_Color_Registry::hex_to_rgb( $hex );
					$alpha = round( intval( $matches[3] ) / 100, 2 );
					return "--tw-shadow-color: rgba({$rgb}, {$alpha}); box-shadow: 0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -2px var(--tw-shadow-color);";
				}
				return "--tw-shadow-color: {$hex}; box-shadow: 0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -2px var(--tw-shadow-color);";
			}
		}
		if ( preg_match( '/^z-(\d+)$/', $class, $matches ) ) return "z-index: {$matches[1]};";
		if ( $class === 'z-auto' ) return "z-index: auto;";
		if ( $class === 'container' || $class === 'skaaa-container' ) {
			$tokens = Tailwind_Color_Registry::get_tokens_config();
			$container_width = isset( $tokens['containerWidth'] ) ? $tokens['containerWidth'] : '1280px';
			return "width: 100%; max-width: {$container_width}; margin-right: auto; margin-left: auto;";
		}
		if ( preg_match( '/^max-w-([a-z0-9]+)$/', $class, $matches ) ) {
			if ( $matches[1] === '7xl' ) {
				$tokens = Tailwind_Color_Registry::get_tokens_config();
				$container_width = isset( $tokens['containerWidth'] ) ? $tokens['containerWidth'] : '1280px';
				return "max-width: {$container_width};";
			}
			if ( isset( Tailwind_Config::$max_w_map[ $matches[1] ] ) ) return "max-width: " . Tailwind_Config::$max_w_map[ $matches[1] ] . ";";
		}
		if ( isset( Tailwind_Config::$margin_auto_map[ $class ] ) ) return Tailwind_Config::$margin_auto_map[ $class ];

		// 5. Flexbox & Grid
		if ( isset( Tailwind_Config::$layout_map[ $class ] ) ) return Tailwind_Config::$layout_map[ $class ];
		if ( preg_match( '/^gap-([0-9\.]+)$/', $class, $matches ) ) {
			$val_str = number_format( floatval( $matches[1] ) * 0.25, 3, '.', '' );
			return "gap: {$val_str}rem; row-gap: {$val_str}rem; column-gap: {$val_str}rem; --wp--style--block-gap: {$val_str}rem;";
		}
		if ( preg_match( '/^gap-(x|y)-([0-9\.]+)$/', $class, $matches ) ) {
			$prop = $matches[1] === 'x' ? 'column-gap' : 'row-gap';
			$val_str = number_format( floatval( $matches[2] ) * 0.25, 3, '.', '' );
			return "{$prop}: {$val_str}rem;";
		}
		if ( preg_match( '/^grid-cols-([1-9]|1[0-2])$/', $class, $matches ) ) return "grid-template-columns: repeat({$matches[1]}, minmax(0, 1fr));";
		if ( preg_match( '/^grid-cols-\[(.+)\]$/', $class, $matches ) ) {
			$val = str_replace( '_', ' ', $matches[1] );
			return "grid-template-columns: {$val};";
		}
		if ( preg_match( '/^col-span-([1-9]|1[0-2])$/', $class, $matches ) ) return "grid-column: span {$matches[1]} / span {$matches[1]};";
		if ( $class === 'col-span-full' ) return "grid-column: 1 / -1;";
		if ( preg_match( '/^grid-rows-([1-9]|1[0-2])$/', $class, $matches ) ) return "grid-template-rows: repeat({$matches[1]}, minmax(0, 1fr));";

		// 5.5 Typography Maps
		if ( preg_match( '/^text-([a-z0-9]+)$/', $class, $matches ) && isset( Tailwind_Config::$size_map[ $matches[1] ] ) ) {
			return Tailwind_Config::$size_map[ $matches[1] ];
		}
		if ( preg_match( '/^text-\[(.+?)\](?:\/(.+))?$/', $class, $matches ) ) {
			$val = str_replace( '_', ' ', $matches[1] );
			if ( str_starts_with( $val, 'rgb(' ) || str_starts_with( $val, 'rgba(' ) || str_starts_with( $val, 'hsl(' ) || str_starts_with( $val, 'hsla(' ) || str_starts_with( $val, '#' ) ) {
				return "color: {$val};";
			}
			if ( isset( $matches[2] ) ) {
				$lh = str_replace( '_', ' ', $matches[2] );
				if ( substr( $lh, 0, 1 ) === '[' && substr( $lh, -1 ) === ']' ) {
					$lh = substr( $lh, 1, -1 );
				}
				return "font-size: {$val}; line-height: {$lh};";
			}
			return "font-size: {$val};";
		}
		if ( isset( Tailwind_Config::$text_align_map[ $class ] ) ) return Tailwind_Config::$text_align_map[ $class ];
		if ( isset( Tailwind_Config::$text_deco_map[ $class ] ) ) return Tailwind_Config::$text_deco_map[ $class ];
		if ( isset( Tailwind_Config::$text_misc_map[ $class ] ) ) return Tailwind_Config::$text_misc_map[ $class ];
		if ( isset( Tailwind_Config::$whitespace_map[ $class ] ) ) return Tailwind_Config::$whitespace_map[ $class ];
		
		if ( preg_match( '/^leading-([a-z0-9\.]+)$/', $class, $matches ) ) {
			if ( isset( Tailwind_Config::$leading_map[ $matches[1] ] ) ) return "line-height: " . Tailwind_Config::$leading_map[ $matches[1] ] . ";";
			if ( is_numeric( $matches[1] ) ) return "line-height: " . number_format( floatval( $matches[1] ) * 0.25, 3, '.', '' ) . "rem;";
		}
		if ( preg_match( '/^tracking-([a-z]+)$/', $class, $matches ) && isset( Tailwind_Config::$tracking_map[ $matches[1] ] ) ) {
			return "letter-spacing: " . Tailwind_Config::$tracking_map[ $matches[1] ] . ";";
		}
		if ( preg_match( '/^line-clamp-([0-9]+)$/', $class, $matches ) ) {
			return "overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: {$matches[1]};";
		}
		if ( $class === 'line-clamp-none' ) return "overflow: visible; display: block; -webkit-box-orient: horizontal; -webkit-line-clamp: none;";
		
		if ( preg_match( '/^space-(x|y)-([0-9\.]+)$/', $class, $matches ) ) {
			$val = floatval( $matches[2] ) * 0.25;
			$prop = $matches[1] === 'x' ? 'margin-left' : 'margin-top';
			$opp = $matches[1] === 'x' ? 'margin-right' : 'margin-bottom';
			return "& > :not([hidden]) ~ :not([hidden]) { {$prop}: {$val}rem; {$opp}: 0; }";
		}

		// 6. Borders
		if ( $class === 'border' ) return 'border-width: 1px; border-style: solid;';
		if ( isset( Tailwind_Config::$border_style_map[ $class ] ) ) return Tailwind_Config::$border_style_map[ $class ];
		if ( preg_match( '/^border-([0-9]+)$/', $class, $matches ) ) return "border-width: {$matches[1]}px; border-style: solid;";
		if ( preg_match( '/^border-\[(.+)\]$/', $class, $matches ) ) {
			$val = str_replace( '_', ' ', $matches[1] );
			if ( ! preg_match( '/^(#|rgb|hsl)/', $val ) ) {
				return "border-width: {$val}; border-style: solid;";
			}
		}
		if ( preg_match( '/^border-([trblxyse])(?:-([0-9]+))?$/', $class, $matches ) ) {
			$width = isset( $matches[2] ) ? $matches[2] . 'px' : '1px';
			if ( $matches[1] === 'x' ) {
				return "border-left-width: {$width}; border-right-width: {$width}; border-style: solid;";
			}
			if ( $matches[1] === 'y' ) {
				return "border-top-width: {$width}; border-bottom-width: {$width}; border-style: solid;";
			}
			if ( $matches[1] === 's' ) {
				return "border-inline-start-width: {$width}; border-style: solid;";
			}
			if ( $matches[1] === 'e' ) {
				return "border-inline-end-width: {$width}; border-style: solid;";
			}
			$side_map = array( 't' => 'top', 'r' => 'right', 'b' => 'bottom', 'l' => 'left' );
			return "border-{$side_map[$matches[1]]}-width: {$width}; border-style: solid;";
		}
		if ( preg_match( '/^border-([trblxyse])-\[(.+)\]$/', $class, $matches ) ) {
			$val = str_replace( '_', ' ', $matches[2] );
			if ( $matches[1] === 'x' ) {
				return "border-left-width: {$val}; border-right-width: {$val}; border-style: solid;";
			}
			if ( $matches[1] === 'y' ) {
				return "border-top-width: {$val}; border-bottom-width: {$val}; border-style: solid;";
			}
			if ( $matches[1] === 's' ) {
				return "border-inline-start-width: {$val}; border-style: solid;";
			}
			if ( $matches[1] === 'e' ) {
				return "border-inline-end-width: {$val}; border-style: solid;";
			}
			$side_map = array( 't' => 'top', 'r' => 'right', 'b' => 'bottom', 'l' => 'left' );
			return "border-{$side_map[$matches[1]]}-width: {$val}; border-style: solid;";
		}
		if ( preg_match( '/^border-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/', $class, $matches ) ) {
			$hex = Tailwind_Color_Registry::get_color_hex( $matches[1], $matches[2] );
			if ( ! $hex ) {
				return null;
			}
			if ( isset( $matches[3] ) ) {
				$rgb   = Tailwind_Color_Registry::hex_to_rgb( $hex );
				$alpha = round( intval( $matches[3] ) / 100, 2 );
				return "border-color: rgba({$rgb}, {$alpha});";
			}
			return "border-color: {$hex};";
		}
		if ( preg_match( '/^border-(white|black)\/([0-9]+)$/', $class, $matches ) ) {
			$hex   = $matches[1] === 'white' ? '#ffffff' : '#000000';
			$rgb   = Tailwind_Color_Registry::hex_to_rgb( $hex );
			$alpha = round( intval( $matches[2] ) / 100, 2 );
			return "border-color: rgba({$rgb}, {$alpha});";
		}
		if ( isset( Tailwind_Config::$border_basic_map[ $class ] ) ) return Tailwind_Config::$border_basic_map[ $class ];

		// 6.6 Ring Width & Color
		if ( $class === 'ring' ) return 'box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);';
		if ( preg_match( '/^ring-([0-8])$/', $class, $matches ) ) {
			$width = $matches[1];
			// ring-0 => 0px transparent
			if ( $width === '0' ) return 'box-shadow: 0 0 0 0px transparent;';
			return "box-shadow: 0 0 0 {$width}px var(--tw-ring-color, rgba(59, 130, 246, 0.5));";
		}
		if ( $class === 'ring-inset' ) return '--tw-ring-inset: inset;';
		if ( preg_match( '/^ring-offset-([0-9]+)$/', $class, $matches ) ) return "--tw-ring-offset-width: {$matches[1]}px;";
		if ( preg_match( '/^ring-([a-z]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/', $class, $matches ) ) {
			$hex = Tailwind_Color_Registry::get_color_hex( $matches[1], $matches[2] );
			if ( isset( $matches[3] ) ) {
				$rgb   = Tailwind_Color_Registry::hex_to_rgb( $hex );
				$alpha = round( intval( $matches[3] ) / 100, 2 );
				return "--tw-ring-color: rgba({$rgb}, {$alpha});";
			}
			return "--tw-ring-color: {$hex};";
		}
		if ( isset( Tailwind_Config::$ring_basic[ $class ] ) ) return Tailwind_Config::$ring_basic[ $class ];

		if ( preg_match( '/^ring-offset-([a-z]+)-([1-9]00|950|50)$/', $class, $matches ) ) {
			return "--tw-ring-offset-color: " . Tailwind_Color_Registry::get_color_hex( $matches[1], $matches[2] ) . ";";
		}
		if ( isset( Tailwind_Config::$ring_offset_basic[ $class ] ) ) return Tailwind_Config::$ring_offset_basic[ $class ];

		// 6.7 Outline (Use !important to survive Gutenberg's .is-selected overrides in Editor)
		if ( $class === 'outline-none' ) return 'outline: 2px solid transparent !important; outline-offset: 2px !important;';
		if ( $class === 'outline' ) return 'outline-style: solid !important;';
		if ( preg_match( '/^outline-(dashed|dotted|double)$/', $class, $matches ) ) return "outline-style: {$matches[1]} !important;";
		if ( preg_match( '/^outline-([0-9]+)$/', $class, $matches ) ) return "outline-width: {$matches[1]}px !important; outline-style: solid !important;";
		if ( preg_match( '/^-?outline-offset-([0-9]+)$/', $class, $matches ) ) {
			$val = strpos( $class, '-' ) === 0 ? '-' . $matches[1] : $matches[1];
			return "outline-offset: {$val}px !important;";
		}
		if ( preg_match( '/^outline-([a-z]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/', $class, $matches ) ) {
			$hex = Tailwind_Color_Registry::get_color_hex( $matches[1], $matches[2] );
			if ( isset( $matches[3] ) ) {
				$rgb   = Tailwind_Color_Registry::hex_to_rgb( $hex );
				$alpha = round( intval( $matches[3] ) / 100, 2 );
				return "outline-color: rgba({$rgb}, {$alpha}) !important;";
			}
			return "outline-color: {$hex} !important;";
		}
		if ( $class === 'outline-white' ) return 'outline-color: #ffffff !important;';
		if ( $class === 'outline-black' ) return 'outline-color: #000000 !important;';
		if ( $class === 'outline-transparent' ) return 'outline-color: transparent !important;';

		// 6.9 Background & Gradients
		if ( isset( Tailwind_Config::$bg_util_map[ $class ] ) ) return Tailwind_Config::$bg_util_map[ $class ];
		if ( preg_match( '/^bg-gradient-to-(t|tr|r|br|b|bl|l|tl)$/', $class, $matches ) ) {
			$dir_map = array(
				't' => 'to top', 'tr' => 'to top right', 'r' => 'to right', 'br' => 'to bottom right',
				'b' => 'to bottom', 'bl' => 'to bottom left', 'l' => 'to left', 'tl' => 'to top left',
			);
			return "background-image: linear-gradient({$dir_map[$matches[1]]}, var(--tw-gradient-stops));";
		}
		if ( preg_match( '/^(from|via|to)-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/', $class, $matches ) ) {
			$hex = Tailwind_Color_Registry::get_color_hex( $matches[2], $matches[3] );
			if ( ! $hex ) {
				return null;
			}
			if ( isset( $matches[4] ) ) {
				$rgb   = Tailwind_Color_Registry::hex_to_rgb( $hex );
				$alpha = round( intval( $matches[4] ) / 100, 2 );
				$color = "rgba({$rgb}, {$alpha})";
			} else {
				$color = $hex;
			}
			$grad_map = array(
				'from' => "--tw-gradient-from: {$color}; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);",
				'via'  => "--tw-gradient-stops: var(--tw-gradient-from), {$color}, var(--tw-gradient-to, transparent);",
				'to'   => "--tw-gradient-to: {$color};",
			);
			return $grad_map[ $matches[1] ];
		}
		if ( isset( Tailwind_Config::$grad_basic[ $class ] ) ) return Tailwind_Config::$grad_basic[ $class ];

		// 6.12 Rounded
		if ( preg_match( '/^rounded(-[a-z0-9\-]+)?$/', $class, $matches ) ) {
			$type = $matches[1] ?? '';
			return isset( Tailwind_Config::$radius_map[ $type ] ) ? "border-radius: " . Tailwind_Config::$radius_map[ $type ] . ";" : null;
		}

		// 7. Display & Position
		if ( in_array( $class, array( 'hidden', 'block', 'inline-block', 'flex', 'grid', 'inline-flex' ) ) ) {
			return "display: " . ( $class === 'hidden' ? 'none' : $class ) . ";";
		}
		if ( $class === 'group' ) return '/* group marker */';
		if ( preg_match( '/^overflow-(auto|hidden|visible|scroll|x-auto|x-hidden|y-auto|y-hidden)$/', $class, $matches ) ) {
			$prop = 'overflow';
			if ( strpos( $matches[1], 'x-' ) === 0 ) $prop = 'overflow-x';
			if ( strpos( $matches[1], 'y-' ) === 0 ) $prop = 'overflow-y';
			return "{$prop}: " . str_replace( array( 'x-', 'y-' ), '', $matches[1] ) . ";";
		}
		if ( in_array( $class, array( 'relative', 'absolute', 'fixed', 'sticky', 'static' ) ) ) return "position: {$class};";
		
		if ( preg_match( '/^(inset|inset-x|inset-y|top|right|bottom|left|start|end)-(\d+\/\d+|\d+\.?\d*|full|auto|px)$/', $class, $matches ) ) {
			$val = $matches[2];
			if ( strpos( $val, '/' ) !== false ) {
				list( $n, $d ) = explode( '/', $val );
				$css_val = ( floatval( $n ) / floatval( $d ) * 100 ) . '%';
			} elseif ( $val === 'full' ) $css_val = '100%';
			elseif ( $val === 'auto' ) $css_val = 'auto';
			elseif ( $val === 'px' ) $css_val = '1px';
			elseif ( $val === '0' ) $css_val = '0px';
			else $css_val = number_format( floatval( $val ) * 0.25, 3, '.', '' ) . 'rem';

			$prop_map = array(
				'inset'   => "inset: {$css_val};",
				'inset-x' => "left: {$css_val}; right: {$css_val};",
				'inset-y' => "top: {$css_val}; bottom: {$css_val};",
				'top'     => "top: {$css_val};",
				'right'   => "right: {$css_val};",
				'bottom'  => "bottom: {$css_val};",
				'left'    => "left: {$css_val};",
				'start'   => "inset-inline-start: {$css_val};",
				'end'     => "inset-inline-end: {$css_val};",
			);
			return $prop_map[ $matches[1] ] ?? null;
		}
		if ( preg_match( '/^(inset|inset-x|inset-y|top|right|bottom|left|start|end)-\[(.+)\]$/', $class, $matches ) ) {
			$val = $matches[2];
			$prop_map = array(
				'inset'   => "inset: {$val};",
				'inset-x' => "left: {$val}; right: {$val};",
				'inset-y' => "top: {$val}; bottom: {$val};",
				'top'     => "top: {$val};",
				'right'   => "right: {$val};",
				'bottom'  => "bottom: {$val};",
				'left'    => "left: {$val};",
				'start'   => "inset-inline-start: {$val};",
				'end'     => "inset-inline-end: {$val};",
			);
			return $prop_map[ $matches[1] ] ?? null;
		}

		// 8. Transitions & Transforms
		if ( $class === 'transform' ) return '/* transform placeholder */';
		if ( preg_match( '/^transition(-[a-z]+)?$/', $class, $matches ) ) {
			$type = $matches[1] ?? '-all';
			return Tailwind_Config::$transition_map[ $type ] ?? null;
		}
		if ( preg_match( '/^duration-([0-9]+)$/', $class, $matches ) ) return "transition-duration: {$matches[1]}ms;";
		if ( preg_match( '/^ease-(linear|in|out|in-out)$/', $class, $matches ) ) {
			return isset( Tailwind_Config::$ease_map[ $matches[1] ] ) ? "transition-timing-function: " . Tailwind_Config::$ease_map[ $matches[1] ] . ";" : null;
		}
		if ( preg_match( '/^translate-([xy])-(\d+\.?\d*|full|px|1\/2|1\/3|2\/3|1\/4|2\/4|3\/4)$/', $class, $matches ) ) {
			$axis = $matches[1] === 'x' ? 'X' : 'Y';
			$frac_map = array( '1/2' => '50%', '1/3' => '33.333333%', '2/3' => '66.666667%', '1/4' => '25%', '2/4' => '50%', '3/4' => '75%' );
			if ( isset( $frac_map[ $matches[2] ] ) ) $css_val = $frac_map[ $matches[2] ];
			elseif ( $matches[2] === 'full' ) $css_val = '100%';
			elseif ( $matches[2] === 'px' ) $css_val = '1px';
			elseif ( $matches[2] === '0' ) $css_val = '0px';
			else $css_val = number_format( floatval( $matches[2] ) * 0.25, 3, '.', '' ) . 'rem';
			return "transform: translate{$axis}({$css_val});";
		}
		if ( preg_match( '/^translate-([xy])-\[(.+)\]$/', $class, $matches ) ) {
			return "transform: translate" . strtoupper($matches[1]) . "(" . str_replace( '_', ' ', $matches[2] ) . ");";
		}
		if ( preg_match( '/^rotate-([0-9]+)$/', $class, $matches ) ) return "transform: rotate({$matches[1]}deg);";
		if ( preg_match( '/^rotate-\[(.+)\]$/', $class, $matches ) ) return "transform: rotate({$matches[1]});";
		if ( preg_match( '/^scale-\[([0-9\.]+)\]$/', $class, $matches ) ) return "transform: scale({$matches[1]});";
		if ( preg_match( '/^scale-([0-9]+)$/', $class, $matches ) ) return "transform: scale(" . ( intval( $matches[1] ) / 100 ) . ");";

		// 9. Filters
		if ( preg_match( '/^backdrop-blur(-[a-z0-9]+)?$/', $class, $matches ) ) {
			$size = $matches[1] ?? '';
			$val = Tailwind_Config::$blur_map[ $size ] ?? null;
			return $val ? "-webkit-backdrop-filter: blur({$val}); backdrop-filter: blur({$val});" : null;
		}
		if ( preg_match( '/^blur(-[a-z0-9]+)?$/', $class, $matches ) ) {
			$size = $matches[1] ?? '';
			$val = Tailwind_Config::$blur_map[ $size ] ?? null;
			return $val ? "filter: blur({$val});" : null;
		}
		if ( preg_match( '/^brightness-([0-9]+)$/', $class, $matches ) ) return "filter: brightness(" . ( intval( $matches[1] ) / 100 ) . ");";
		if ( preg_match( '/^contrast-([0-9]+)$/', $class, $matches ) ) return "filter: contrast(" . ( intval( $matches[1] ) / 100 ) . ");";
		if ( preg_match( '/^opacity-([0-9]+)$/', $class, $matches ) ) return "opacity: " . ( intval( $matches[1] ) / 100 ) . ";";
		if ( $class === 'isolate' ) return 'isolation: isolate;';

		// 10. Extras & Accessibility
		if ( isset( Tailwind_Config::$flex_extra[ $class ] ) ) return Tailwind_Config::$flex_extra[ $class ];
		if ( preg_match( '/^(shrink|grow)-\[(.+)\]$/', $class, $matches ) ) {
			$prop = $matches[1] === 'shrink' ? 'flex-shrink' : 'flex-grow';
			$val  = str_replace( '_', ' ', $matches[2] );
			return "{$prop}: {$val};";
		}
		if ( preg_match( '/^order-([0-9]+)$/', $class, $matches ) ) return "order: {$matches[1]};";
		if ( preg_match( '/^cursor-(pointer|default|wait|text|move|not-allowed|grab|grabbing|auto)$/', $class, $matches ) ) return "cursor: {$matches[1]};";
		if ( $class === 'pointer-events-none' ) return 'pointer-events: none;';
		if ( $class === 'pointer-events-auto' ) return 'pointer-events: auto;';
		if ( preg_match( '/^select-(none|text|all|auto)$/', $class, $matches ) ) return "user-select: {$matches[1]}; -webkit-user-select: {$matches[1]};";
		
		if ( $class === 'sr-only' ) return 'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0;';
		if ( $class === 'not-sr-only' ) return 'position: static; width: auto; height: auto; padding: 0; margin: 0; overflow: visible; clip: auto; white-space: normal;';

		// 11. Animations & Delays
		if ( $class === 'animate-blob' ) return 'animation: blob 7s infinite;';
		if ( $class === 'animate-spin' ) return 'animation: spin 1s linear infinite;';
		if ( $class === 'animate-ping' ) return 'animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;';
		if ( $class === 'animate-pulse' ) return 'animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;';
		if ( $class === 'animate-bounce' ) return 'animation: bounce 1s infinite;';
		if ( preg_match( '/^(animation-delay|delay)-([0-9]+)$/', $class, $matches ) ) return "animation-delay: {$matches[2]}ms; transition-delay: {$matches[2]}ms;";
		
		if ( preg_match( '/^content-\[(.+)\]$/', $class, $matches ) ) {
			$val = str_replace( '_', ' ', $matches[1] );
			return "content: {$val};";
		}
		if ( $class === 'content-none' ) return "content: none;";

		return null;
	}

	/**
	 * Helper for spacing resolution.
	 */
	private function resolve_spacing( $prefix, $value ): string {
		$type = $prefix[0] === 'p' ? 'padding' : 'margin';
		$rem_str = number_format( floatval( $value ) * 0.25, 3, '.', '' );
		$dir  = substr( $prefix, 1 );

		$map = array(
			''  => "{$type}: {$rem_str}rem;",
			'x' => "{$type}-left: {$rem_str}rem; {$type}-right: {$rem_str}rem;",
			'y' => "{$type}-top: {$rem_str}rem; {$type}-bottom: {$rem_str}rem;",
			't' => "{$type}-top: {$rem_str}rem;",
			'b' => "{$type}-bottom: {$rem_str}rem;",
			'l' => "{$type}-left: {$rem_str}rem;",
			'r' => "{$type}-right: {$rem_str}rem;",
			's' => "{$type}-inline-start: {$rem_str}rem;",
			'e' => "{$type}-inline-end: {$rem_str}rem;",
		);

		return $map[ $dir ] ?? '';
	}

	/**
	 * Helper for dimension resolution.
	 */
	private function resolve_dimension( $value ): ?string {
		if ( $value === 'full' ) return '100%';
		if ( $value === 'screen' ) return '100vh';
		if ( $value === 'auto' ) return 'auto';
		if ( is_numeric( $value ) ) return number_format( floatval( $value ) * 0.25, 3, '.', '' ) . 'rem';
		if ( preg_match( '/^\[(.+)\]$/', $value, $matches ) ) return str_replace( '_', ' ', $matches[1] );
		if ( preg_match( '/^(\d+)\/(\d+)$/', $value, $matches ) ) {
			$num = floatval( $matches[1] );
			$den = floatval( $matches[2] );
			if ( $den > 0 ) {
				// Tailwind standard 6 decimal places for fractions like 1/3 (33.333333%)
				return number_format( ( $num / $den ) * 100, 6, '.', '' ) . '%';
			}
		}
		return null;
	}

	/**
	 * Static helper to call compile.
	 */
	public static function compile( $classes ): string {
		$result = apply_filters( 'skaaa_compile_tailwind', $classes );
		return is_array( $result ) ? ( $result['css'] ?? '' ) : (string) $result;
	}
}
