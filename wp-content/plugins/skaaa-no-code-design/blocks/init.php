<?php
/**
 * Blocks initialization.
 * 
 * @package Skaaa_Builder_Core
 */

defined( 'ABSPATH' ) || exit;

// Load Code Block Queue early to handle frontend block scanning before wp_head
if ( class_exists( '\Skaaa_System_Framework\Dependency_Manager' ) && \Skaaa_System_Framework\Dependency_Manager::is_data_pro_active() ) {
	require_once SKAAA_DESIGN_PATH . 'blocks/class-skaaa-code-block-queue.php';
	Skaaa_Code_Block_Queue::init();
}

/**
 * Register blocks from the build directory.
 */
function skaaa_builder_core_register_blocks() {
    // Register from build directory (where metadata is copied/built)
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-container' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-text' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-image' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-icon' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-svg' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-button' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-video' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-list' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-list-item' );
    
    // Form Interface Blocks
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-input' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-select' );
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-form-rich-text' );

    // Skaaa Symbols (Organism Reference)
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-organism-ref' );

    // Skaaa Query Loop
    register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-loop' );

    // Register Bridge Import if enabled
    if ( get_option( 'skaaa_bridge_enabled', 'yes' ) === 'yes' ) {
        register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-bridge-import' );
    }

    // Skaaa Code Block (Chỉ đăng ký khi Skaaa Data Pro active)
    if ( class_exists( '\Skaaa_System_Framework\Dependency_Manager' ) && \Skaaa_System_Framework\Dependency_Manager::is_data_pro_active() ) {
        register_block_type( SKAAA_DESIGN_PATH . 'build/skaaa-code' );
    }
}

add_action( 'init', 'skaaa_builder_core_register_blocks' );

/**
 * Register custom block category for Skaaa Atomic blocks.
 */
function skaaa_no_code_design_block_categories( $categories, $post ) {
	$skaaa_categories = array(
		array(
			'slug'  => 'skaaa-atomic',
			'title' => __( 'Skaaa Atomic', 'skaaa-no-code-design' ),
			'icon'  => 'admin-customizer',
		),
		array(
			'slug'  => 'skaaa-molecules',
			'title' => __( 'Skaaa Molecules', 'skaaa-no-code-design' ),
			'icon'  => 'networking',
		),
	);

    // Graceful Fallback: Only show Organisms category if Skaaa Data Pro is active
    if ( class_exists( '\Skaaa_System_Framework\Dependency_Manager' ) && \Skaaa_System_Framework\Dependency_Manager::is_data_pro_active() ) {
        $skaaa_categories[] = array(
            'slug'  => 'skaaa-organisms',
            'title' => __( 'Skaaa Organisms', 'skaaa-no-code-design' ),
            'icon'  => 'superhero',
        );
    }

	return array_merge( $skaaa_categories, $categories );
}
add_filter( 'block_categories_all', 'skaaa_no_code_design_block_categories', 10, 2 );

/**
 * Enqueue editor extensions (HTML Attributes Panel)
 */
function skaaa_builder_core_enqueue_extensions() {
    $asset_path = SKAAA_DESIGN_PATH . 'build/extensions.asset.php';
    if ( file_exists( $asset_path ) ) {
        $assets = require $asset_path;
        wp_enqueue_script(
            'skaaaaa-builder-extensions',
            SKAAA_DESIGN_URL . 'build/extensions.js',
            $assets['dependencies'],
            $assets['version'],
            true
        );

        // Preload organisms cache into JS window object (Conditionally via Graceful Fallback)
        $organisms_data = array();
        
        // Only load if Skaaa Data Pro is active
        if ( class_exists( '\Skaaa_System_Framework\Dependency_Manager' ) && \Skaaa_System_Framework\Dependency_Manager::is_data_pro_active() ) {
            $organisms_cache_file = WP_CONTENT_DIR . '/uploads/skaaa-data/organisms.json';
            if ( file_exists( $organisms_cache_file ) ) {
                $json = file_get_contents( $organisms_cache_file );
                $data = json_decode( $json, true );
                if ( is_array( $data ) ) {
                    $organisms_data = $data;
                }
            }
        }
        
        wp_localize_script( 'skaaaaa-builder-extensions', 'skaaaOrganismsCache', $organisms_data );

        $skaaa_data_dict = get_option( 'skaaa_data_dictionary', array() );
        wp_localize_script( 'skaaaaa-builder-extensions', 'skaaaDataDictionary', $skaaa_data_dict );

        // Preload Design Tokens Cache into JS window object
        $tokens_data = array();
        $tokens_cache_file = WP_CONTENT_DIR . '/uploads/skaaa-data/tokens.json';
        if ( file_exists( $tokens_cache_file ) ) {
            $json = file_get_contents( $tokens_cache_file );
            $data = json_decode( $json, true );
            if ( is_array( $data ) ) {
                $tokens_data = $data;
            }
        }
        wp_localize_script( 'skaaaaa-builder-extensions', 'skaaaDesignTokens', $tokens_data );
    }
}
add_action( 'enqueue_block_editor_assets', 'skaaa_builder_core_enqueue_extensions' );

/**
 * Register Alpine.js and Alpine AJAX scripts (Local files)
 */
function skaaa_builder_core_register_alpine() {
    $ajax_js  = SKAAA_DESIGN_PATH . 'assets/js/vendor/alpine-ajax.min.js';
    $ajax_ver = file_exists( $ajax_js ) ? filemtime( $ajax_js ) : '0.12.7';
    wp_register_script(
        'skaaa-alpine-ajax',
        SKAAA_DESIGN_URL . 'assets/js/vendor/alpine-ajax.min.js',
        array( 'skaaa-frontend' ),
        $ajax_ver,
        true
    );

    wp_register_script(
        'skaaa-alpine',
        SKAAA_DESIGN_URL . 'assets/js/alpine.min.js',
        array( 'skaaa-alpine-ajax' ),
        '3.13.3',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'skaaa_builder_core_register_alpine' );
add_action( 'admin_enqueue_scripts', 'skaaa_builder_core_register_alpine' );

/**
 * Register Skaaa Frontend Engine (Alpine Controller cho Form).
 * QUAN TRỌNG: Script này load TRƯỚC Alpine.js (không có dependency).
 * Lý do: Alpine tự auto-start khi load. Nếu skaaa-frontend load SAU Alpine,
 * thì alpine:init event đã bắn xong → skaaaForm không được đăng ký.
 * Thứ tự: skaaa-frontend.js → alpine.min.js → Alpine phát alpine:init → skaaaForm đăng ký.
 */
function skaaa_builder_core_register_frontend_engine() {
    $js_file = SKAAA_DESIGN_PATH . 'assets/js/skaaa-frontend.js';
    $version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.3';
    wp_register_script(
        'skaaa-frontend',
        SKAAA_DESIGN_URL . 'assets/js/skaaa-frontend.js',
        array( 'wp-i18n' ),
        $version,
        true
    );

    // Truyền biến môi trường cho JS (REST URL)
    wp_localize_script( 'skaaa-frontend', 'skaaaEnv', array(
        'restUrl' => esc_url_raw( rest_url() ),
    ) );

    // Đảm bảo Alpine load SAU skaaa-frontend
    // Bằng cách set skaaa-frontend là dependency của skaaa-alpine
    // → Khi enqueue skaaa-alpine, nó sẽ kéo skaaa-frontend vào trước
}
add_action( 'wp_enqueue_scripts', 'skaaa_builder_core_register_frontend_engine' );
add_action( 'admin_enqueue_scripts', 'skaaa_builder_core_register_frontend_engine' );

/**
 * Render HTML Attributes & Alpine.js logic dynamically via PHP filter.
 * The zero-overhead Engine: only loads Alpine when x- attributes are used.
 */
function skaaa_builder_render_html_attributes( $block_content, $block ) {
    if ( ! empty( $block['blockName'] ) && strpos( $block['blockName'], 'skaaaaa-builder/' ) === 0 ) {
        $html_attrs = '';
        $has_alpine = false;
        $has_x_data = false;
        $has_x_show = false;
        
        if ( ! empty( $block['attrs']['htmlAttributes'] ) && is_array( $block['attrs']['htmlAttributes'] ) ) {
            foreach ( $block['attrs']['htmlAttributes'] as $attr ) {
                if ( ! empty( $attr['key'] ) ) {
                    if ( str_starts_with( $attr['key'], 'x-' ) || str_starts_with( $attr['key'], '@' ) || str_starts_with( $attr['key'], ':' ) ) {
                        $has_alpine = true;
                    }
                    if ( $attr['key'] === 'x-data' ) {
                        $has_x_data = true;
                    }
                    if ( $attr['key'] === 'x-show' ) {
                        $has_x_show = true;
                    }
                    $html_attrs .= ' ' . esc_attr( $attr['key'] ) . '="' . esc_attr( $attr['value'] ?? '' ) . '"';
                }
            }
        }
        
        // Zero-overhead enqueuing
        if ( $has_alpine ) {
            wp_enqueue_script( 'skaaa-alpine' );
        }

        // Inject into the very first HTML opening tag (preserving self-closing slash if present)
        if ( ! empty( $html_attrs ) ) {
            $block_content = preg_replace( '/^<([a-zA-Z0-9\-]+)(.*?)(\s*\/)?>/s', '<$1$2' . $html_attrs . '$3>', $block_content, 1 );
        }
    }
    return $block_content;
}
add_filter( 'render_block', 'skaaa_builder_render_html_attributes', 10, 2 );

