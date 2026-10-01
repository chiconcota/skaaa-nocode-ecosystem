<?php
/**
 * Tailwind Configuration Maps
 *
 * @package Skaaa_Builder_Core
 */

namespace Skaaa\Builder\Design;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tailwind_Config
 */
class Tailwind_Config {
	protected static $rules = null;

	public static $media_queries = array();
	public static $basic_colors = array();
	public static $font_family = array();
	public static $weights = array();
	public static $shadow_map = array();
	public static $max_w_map = array();
	public static $margin_auto_map = array();
	public static $layout_map = array();
	public static $size_map = array();
	public static $text_align_map = array();
	public static $text_deco_map = array();
	public static $text_misc_map = array();
	public static $whitespace_map = array();
	public static $leading_map = array();
	public static $tracking_map = array();
	public static $border_style_map = array();
	public static $border_basic_map = array();
	public static $ring_basic = array();
	public static $ring_offset_basic = array();
	public static $bg_util_map = array();
	public static $grad_basic = array();
	public static $radius_map = array();
	public static $flex_extra = array();
	public static $transition_map = array();
	public static $ease_map = array();
	public static $blur_map = array();
	public static $palette = array();

	/**
	 * Nạp quy tắc từ tailwind-rules.json (Single Source of Truth)
	 */
	public static function get_rules(): array {
		if ( null === self::$rules ) {
			$json_file = SKAAA_DESIGN_PATH . 'inc/design-engine/tailwind-rules.json';
			if ( file_exists( $json_file ) ) {
				$decoded = json_decode( file_get_contents( $json_file ), true );
				if ( is_array( $decoded ) ) {
					self::$rules = $decoded;
					self::populate_static_properties( $decoded );
				}
			}
			if ( null === self::$rules ) {
				self::$rules = array();
			}
		}
		return self::$rules;
	}

	/**
	 * Đồng bộ dữ liệu JSON sang static properties phục vụ tương thích ngược.
	 */
	private static function populate_static_properties( array $rules ): void {
		self::$media_queries      = $rules['mediaQueries'] ?? array();
		self::$basic_colors       = $rules['basicColors'] ?? array();
		self::$font_family        = $rules['fontFamily'] ?? array();
		self::$weights            = $rules['weights'] ?? array();
		self::$shadow_map         = $rules['shadowMap'] ?? array();
		self::$max_w_map          = $rules['maxWMap'] ?? array();
		self::$margin_auto_map    = $rules['marginAutoMap'] ?? array();
		self::$layout_map         = $rules['layoutMap'] ?? array();
		self::$size_map           = $rules['sizeMap'] ?? array();
		self::$text_align_map     = $rules['textAlignMap'] ?? array();
		self::$text_deco_map      = $rules['textDecoMap'] ?? array();
		self::$text_misc_map      = $rules['textMiscMap'] ?? array();
		self::$whitespace_map     = $rules['whitespaceMap'] ?? array();
		self::$leading_map        = $rules['leadingMap'] ?? array();
		self::$tracking_map       = $rules['trackingMap'] ?? array();
		self::$border_style_map   = $rules['borderStyleMap'] ?? array();
		self::$border_basic_map   = $rules['borderBasicMap'] ?? array();
		self::$ring_basic         = $rules['ringBasic'] ?? array();
		self::$ring_offset_basic  = $rules['ringOffsetBasic'] ?? array();
		self::$bg_util_map        = $rules['bgUtilMap'] ?? array();
		self::$grad_basic         = $rules['gradBasic'] ?? array();
		self::$radius_map         = $rules['radiusMap'] ?? array();
		self::$flex_extra         = $rules['flexExtra'] ?? array();
		self::$transition_map     = $rules['transitionMap'] ?? array();
		self::$ease_map           = $rules['easeMap'] ?? array();
		self::$blur_map           = $rules['blurMap'] ?? array();
		self::$palette            = $rules['palette'] ?? array();
	}

	/**
	 * Dam bao static properties luon duoc khoi tao neu chua duoc nap
	 */
	public static function init(): void {
		self::get_rules();
	}


	/**
	 * Returns the base global CSS resets for Skaaa blocks.
	 * This is critical for 100% layout fidelity between the editor and frontend.
	 *
	 * @return string CSS resets
	 */
	public static function get_core_reset_css(): string {
		$css = "/* Skaaa Core Block Resets (Atomic Specificity) */\n";
		
		$typography = Tailwind_Color_Registry::get_typography_config();
		$primary_font = wp_strip_all_tags( $typography['primary'] );
		$secondary_font = wp_strip_all_tags( $typography['secondary'] );
		$mono_font = isset( $typography['mono'] ) ? wp_strip_all_tags( $typography['mono'] ) : 'IBM Plex Mono, monospace';
		$custom_font_url = esc_url( $typography['customFontUrl'] );
		
		if ( ! empty( $custom_font_url ) ) {
			$css .= "@font-face {\n";
			$css .= "  font-family: 'SkaaaCustomFont';\n";
			$css .= "  src: url('{$custom_font_url}') format('woff2');\n";
			$css .= "  font-weight: normal;\n";
			$css .= "  font-style: normal;\n";
			$css .= "  font-display: swap;\n";
			$css .= "}\n";
		}
		
		// Container width setup
		$tokens = Tailwind_Color_Registry::get_tokens_config();
		$container_width = isset( $tokens['containerWidth'] ) ? wp_strip_all_tags( $tokens['containerWidth'] ) : '1280px';
		$block_gap = isset( $tokens['blockGap'] ) ? wp_strip_all_tags( $tokens['blockGap'] ) : '1.5rem';
		$content_padding = isset( $tokens['contentPadding'] ) ? wp_strip_all_tags( $tokens['contentPadding'] ) : '1rem';
		$border_radius = isset( $tokens['borderRadius'] ) ? wp_strip_all_tags( $tokens['borderRadius'] ) : '0.25rem';
		$box_shadow = isset( $tokens['boxShadow'] ) ? wp_strip_all_tags( $tokens['boxShadow'] ) : '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)';
		$transition_duration = isset( $tokens['transitionDuration'] ) ? wp_strip_all_tags( $tokens['transitionDuration'] ) : '150ms';

		// 1. Global Typography Reset (Tailwind Standard) for Barebone Themes
		$css .= ":root {\n";
		$css .= "  --font-primary: {$primary_font};\n";
		$css .= "  --font-secondary: {$secondary_font};\n";
		$css .= "  --font-mono: {$mono_font};\n";
		$css .= "  --skaaa-container-width: {$container_width};\n";
		$css .= "  --skaaa-block-gap: {$block_gap};\n";
		$css .= "  --skaaa-content-padding: {$content_padding};\n";
		$css .= "  --skaaa-border-radius: {$border_radius};\n";
		$css .= "  --skaaa-box-shadow: {$box_shadow};\n";
		$css .= "  --skaaa-transition-duration: {$transition_duration};\n";
		$css .= "}\n";
		// Gutenberg Editor Width & Block Margin Overrides (Layout Parity - High Specificity, Zero !important)
		$css .= "html body.skaaaaa-builder .editor-styles-wrapper .block-editor-block-list__layout.is-root-container, .editor-styles-wrapper.editor-styles-wrapper .block-editor-block-list__layout.is-root-container { max-width: 100%; width: 100%; padding: 0; margin: 0; }\n";
		$css .= "html body.skaaaaa-builder .editor-styles-wrapper .wp-block, .editor-styles-wrapper.editor-styles-wrapper .wp-block { max-width: none; margin-left: 0; margin-right: 0; }\n";
		$css .= "html body.skaaaaa-builder .editor-styles-wrapper .flex > .block-editor-block-list__block, .editor-styles-wrapper.editor-styles-wrapper .flex > .block-editor-block-list__block, html body.skaaaaa-builder .editor-styles-wrapper .inline-flex > .block-editor-block-list__block, .editor-styles-wrapper.editor-styles-wrapper .inline-flex > .block-editor-block-list__block { width: auto; max-width: none; }\n";
		$css .= ".editor-styles-wrapper .skaaapine-wrapper { display: contents; }\n";

		$css .= "html body.skaaaaa-builder .skaaa-container, .editor-styles-wrapper .skaaa-container { width: 100%; max-width: var(--skaaa-container-width); margin-left: auto; margin-right: auto; padding: var(--skaaa-content-padding); }\n";
		$css .= "html body.skaaaaa-builder .skaaa-default-content { width: 100%; }\n";
		$css .= "html body.skaaaaa-builder .skaaa-default-content > :not([class*=\"wp-block-skaaaaa-builder\"]):not([class*=\"skaaa-\"]) { max-width: var(--skaaa-container-width); margin-left: auto; margin-right: auto; padding-left: var(--skaaa-content-padding); padding-right: var(--skaaa-content-padding); }\n";
		$css .= "html body.skaaaaa-builder .skaaa-container-block:not([class*=\"grid\"]):not([class*=\"flex\"]) > * + *, .editor-styles-wrapper .skaaa-container-block:not([class*=\"grid\"]):not([class*=\"flex\"]) > * + * { margin-top: var(--skaaa-block-gap); }\n";
		if ( is_admin() ) {
			$css .= ".editor-styles-wrapper { font-family: var(--font-primary), ui-sans-serif, system-ui, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\"; background-color: var(--skaaa-color-background, #ffffff); color: var(--skaaa-color-text, #111827); }\n";
			$css .= ".editor-styles-wrapper h1, .editor-styles-wrapper h2, .editor-styles-wrapper h3, .editor-styles-wrapper h4, .editor-styles-wrapper h5, .editor-styles-wrapper h6 { font-family: var(--font-secondary), ui-sans-serif, system-ui, sans-serif; }\n";
			$css .= ".editor-styles-wrapper code, .editor-styles-wrapper kbd, .editor-styles-wrapper samp, .editor-styles-wrapper pre { font-family: var(--font-mono), ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, \"Liberation Mono\", \"Courier New\", monospace; }\n";
		} else {
			$css .= "html body.skaaaaa-builder { font-family: var(--font-primary), ui-sans-serif, system-ui, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\"; background-color: var(--skaaa-color-background, #ffffff); color: var(--skaaa-color-text, #111827); transition: background-color 0.3s ease, color 0.3s ease; min-height: 100vh; }\n";
			$css .= "html body.skaaaaa-builder h1, html body.skaaaaa-builder h2, html body.skaaaaa-builder h3, html body.skaaaaa-builder h4, html body.skaaaaa-builder h5, html body.skaaaaa-builder h6 { font-family: var(--font-secondary), ui-sans-serif, system-ui, sans-serif; }\n";
			$css .= "html body.skaaaaa-builder code, html body.skaaaaa-builder kbd, html body.skaaaaa-builder samp, html body.skaaaaa-builder pre { font-family: var(--font-mono), ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, \"Liberation Mono\", \"Courier New\", monospace; }\n";
		}

		$prefixes = array(
			"html body.skaaaaa-builder",
			".editor-styles-wrapper"
		);

		$build_rule = function( $target ) use ( $prefixes ) {
			$parts = array();
			foreach ( $prefixes as $prefix ) {
				if ( $target === '' ) {
					$parts[] = "$prefix :where([class*='wp-block-skaaaaa-builder'])";
				} else {
					$parts[] = "$prefix :where({$target}[class*='wp-block-skaaaaa-builder'])";
					$parts[] = "$prefix :where([class*='wp-block-skaaaaa-builder'] {$target})";
				}
			}
			return implode( ', ', $parts );
		};

		$css .= "html body.skaaaaa-builder *, html body.skaaaaa-builder ::before, html body.skaaaaa-builder ::after, .editor-styles-wrapper *, .editor-styles-wrapper ::before, .editor-styles-wrapper ::after { box-sizing: border-box; border-width: 0; border-style: solid; border-color: #e5e7eb; }\n";
		$css .= $build_rule( '' ) . " { --wp--style--block-gap: 0px; margin: 0; }\n";
		$css .= $build_rule( 'button:not(:where(.components-button))' ) . " { border: 0; background: none; padding: 0; margin: 0; cursor: pointer; font-family: inherit; }\n";
		$css .= $build_rule( 'a' ) . " { text-decoration: none; color: inherit; }\n";
		$css .= $build_rule( 'a.underline' ) . " { text-decoration: underline; }\n";

		// Typography Margin Resets (Tailwind Preflight Parity)
		$typography_elements = array( 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'figure', 'hr' );
		foreach ( $typography_elements as $el ) {
			$css .= $build_rule( $el ) . " { margin: 0; }\n";
		}

		// List Resets (Tailwind Preflight Parity)
		$list_elements = array( 'ul', 'ol' );
		foreach ( $list_elements as $el ) {
			$css .= $build_rule( $el ) . " { list-style: none; margin: 0; padding: 0; }\n";
		}

		// Form Resets (Tailwind Preflight Parity)
		$form_elements = array( 'input:not([type="checkbox"]):not([type="radio"])', 'select', 'textarea', 'option', 'optgroup' );
		foreach ( $form_elements as $el ) {
			$css .= $build_rule( $el ) . " { appearance: none; background-color: transparent; border-width: 0; border-radius: 0; padding: 0; border-color: #e5e7eb; outline: none; font-family: inherit; font-size: 100%; font-weight: inherit; line-height: inherit; color: inherit; margin: 0; }\n";
		}

		// Animation Keyframes (Tailwind Preflight Parity)
		$css .= "@keyframes blob { 0% { transform: translate(0px, 0px) scale(1); } 33% { transform: translate(30px, -50px) scale(1.1); } 66% { transform: translate(-20px, 20px) scale(0.9); } 100% { transform: translate(0px, 0px) scale(1); } }\n";
		$css .= "@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }\n";
		$css .= "@keyframes ping { 75%, 100% { transform: scale(2); opacity: 0; } }\n";
		$css .= "@keyframes pulse { 50% { opacity: .5; } }\n";
		$css .= "@keyframes bounce { 0%, 100% { transform: translateY(-25%); animation-timing-function: cubic-bezier(0.8,0,1,1); } 50% { transform: none; animation-timing-function: cubic-bezier(0,0,0.2,1); } }\n";

		return $css;
	}
}
