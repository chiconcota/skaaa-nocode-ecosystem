<?php
/**
 * Design Engine Core Class
 *
 * @package Skaaa_Builder_Core
 */

namespace Skaaa\Builder\Design;

defined('ABSPATH') || exit;

/**
 * Class Core
 */
class Core
{

    /**
     * Instance of this class.
     *
     * @var Core
     */
    protected static $instance = null;

    /**
     * Style Manager instance.
     *
     * @var Style_Manager
     */
    public $style_manager;

    /**
     * Tailwind Compiler instance.
     *
     * @var Tailwind_Compiler
     */
    public $compiler;

    /**
     * Return an instance of this class.
     *
     * @return Core A single instance of this class.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Core constructor.
     */
    private function __construct()
    {
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Initialize components.
     */
    private function init_components()
    {
        $this->style_manager = new Style_Manager();
        $this->compiler = new Tailwind_Compiler();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks()
    {
        // Enqueue Tailwind CDN for Frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tailwind_frontend'));

        // Enqueue Editor Helper
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));

        // Enqueue Material Icons
        add_action('wp_enqueue_scripts', array($this, 'enqueue_design_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_design_assets'));
        add_action('admin_init', function() {
            add_editor_style('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap');
        });

        // Force injection with high priority to ensure it comes after theme styles
        add_action('wp_head', array($this, 'inject_tailwind_cdn'), 999);
        
        // Anti-FOUC script for Dark Mode (Very high priority)
        add_action('wp_head', array($this, 'inject_anti_fouc_script'), 0);

        // Architecture Scope: Add .skaaaaa-builder to body to enable JIT Scoped CSS
        add_filter('body_class', array($this, 'add_skaaa_builder_class'));
        add_filter('admin_body_class', array($this, 'add_skaaa_builder_admin_class'));

        // Note: HTML attribute injection (inject_html_attributes) was moved to blocks/init.php to avoid duplicates.
    }



    /**
     * Add .skaaaaa-builder class to body.
     */
    public function add_skaaa_builder_class($classes)
    {
        $classes[] = 'skaaaaa-builder';
        return $classes;
    }

    /**
     * Add .skaaaaa-builder class to admin body.
     */
    public function add_skaaa_builder_admin_class($classes)
    {
        return $classes . ' skaaaaa-builder';
    }

    /**
     * Enqueue global design assets.
     */
    public function enqueue_design_assets()
    {
        wp_enqueue_style('material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap', array(), '1.0.0');

        // Enqueue Vanilla CSS Variables (Physical Cache)
        $upload_dir = wp_upload_dir();
        $tokens_css_path = trailingslashit($upload_dir['basedir']) . 'skaaa-data/tokens.css';
        $tokens_css_url = trailingslashit($upload_dir['baseurl']) . 'skaaa-data/tokens.css';
        
        if (file_exists($tokens_css_path)) {
            wp_enqueue_style('skaaa-design-tokens-vars', $tokens_css_url, array(), filemtime($tokens_css_path));
        }
    }

    /**
     * Enqueue Alpine Store & Runtime (Alpine.js and Alpine-AJAX are enqueued globally on-demand)
     */
    public function enqueue_tailwind_frontend()
    {
        // Enqueue Alpine AJAX runtime & Alpine.js
        if (wp_script_is('skaaa-alpine-ajax', 'registered')) {
            wp_enqueue_script('skaaa-alpine-ajax');
        } else {
            $ajax_js  = SKAAA_DESIGN_PATH . 'assets/js/vendor/alpine-ajax.min.js';
            $ajax_ver = file_exists($ajax_js) ? filemtime($ajax_js) : '0.12.7';
            wp_register_script(
                'skaaa-alpine-ajax',
                SKAAA_DESIGN_URL . 'assets/js/vendor/alpine-ajax.min.js',
                array('skaaa-frontend'),
                $ajax_ver,
                true
            );
            wp_enqueue_script('skaaa-alpine-ajax');
        }

        // Enqueue local Alpine.js (so we can attach inline scripts)
        wp_enqueue_script('skaaa-alpine');

        // Alpine Store for UI Ecosystem Shared State
        $alpine_store_script = "
        document.addEventListener('alpine:init', () => {
            Alpine.store('skaaaBuilder', {
                data: {},
                get(key) { return this.data[key]; },
                set(key, value) { this.data[key] = value; }
            });
        });
        ";
        wp_add_inline_script('skaaa-alpine', $alpine_store_script, 'before');
    }

    public function enqueue_editor_assets()
    {
        wp_enqueue_style('material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap', array(), '1.0.0');
        wp_enqueue_script(
            'skaaawind-compiler',
            SKAAA_DESIGN_URL . 'assets/js/skaaawind.js',
            array(),
            time(),
            true
        );
        wp_enqueue_script(
            'skaaa-editor-helper',
            SKAAA_DESIGN_URL . 'assets/js/skaaa-editor-helper.js',
            array('skaaawind-compiler'),
            time(),
            true
        );

        // Pass theme config to JS
        wp_localize_script('skaaa-editor-helper', 'skaaaEditorConfig', array(
            'tailwindRules'       => \Skaaa\Builder\Design\Tailwind_Config::get_rules(),
            'editorResetCss'      => \Skaaa\Builder\Design\Tailwind_Config::get_core_reset_css(),
            'brandColorsJson'     => \Skaaa\Builder\Design\Tailwind_Color_Registry::get_custom_colors(),
            'darkBrandColorsJson' => \Skaaa\Builder\Design\Tailwind_Color_Registry::get_custom_dark_colors(),
            // Vẫn giữ brandColorsCss fallback nếu file JS chưa được cập nhật cache.
            'brandColorsCss'      => \Skaaa\Builder\Design\Tailwind_Config::get_core_reset_css() . "\n" . \Skaaa\Builder\Design\Tailwind_Color_Registry::get_brand_colors_css(),
            'dummyPostId'         => get_option('skaaa_organism_dummy_post_id'),
            'adminUrl' => admin_url(),
            'isDataProActive' => class_exists('\Skaaa_System_Framework\Dependency_Manager') && \Skaaa_System_Framework\Dependency_Manager::is_data_pro_active(),
        ));

        // Inject Organisms Cache to JS (Zero-Query) - Conditionally via Graceful Fallback
        $organisms_data = array();

        $upload_dir = wp_upload_dir();
        $cache_file = trailingslashit($upload_dir['basedir']) . 'skaaa-data/organisms.json';

        if (file_exists($cache_file)) {
            $file_contents = file_get_contents($cache_file);
            if (!empty($file_contents)) {
                $decoded = json_decode($file_contents, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    foreach ($decoded as $org) {
                        if (isset($org['id'])) {
                            $organisms_data[$org['id']] = array(
                                'id'           => $org['id'],
                                'name'         => !empty($org['name']) ? $org['name'] : $org['id'],
                                'category'     => !empty($org['category']) ? $org['category'] : '',
                                'html_content' => !empty($org['html_content']) ? $org['html_content'] : '',
                            );
                        }
                    }
                }
            }
        }

        wp_add_inline_script(
            'skaaa-editor-helper',
            'window.skaaaOrganismsCache = ' . wp_json_encode($organisms_data) . ';',
            'before'
        );

        // Enqueue Bridge Parser if enabled
        if (get_option('skaaa_bridge_enabled', 'yes') === 'yes') {
            wp_enqueue_script(
                'skaaa-bridge-parser',
                SKAAA_DESIGN_URL . 'assets/js/html-to-blocks.js',
                array('wp-blocks', 'wp-data', 'wp-util'),
                time(),
                true
            );
        }

        // Enqueue Editor Dark Mode Toggle
        wp_enqueue_style(
            'skaaa-editor-dark-mode',
            SKAAA_DESIGN_URL . 'assets/css/skaaa-editor-dark-mode.css',
            array(),
            defined( 'SKAAA_DESIGN_VERSION' ) ? SKAAA_DESIGN_VERSION : time()
        );

        wp_enqueue_script(
            'skaaa-editor-dark-mode',
            SKAAA_DESIGN_URL . 'assets/js/skaaa-editor-dark-mode.js',
            array('skaaa-editor-helper'),
            defined( 'SKAAA_DESIGN_VERSION' ) ? SKAAA_DESIGN_VERSION : time(),
            true
        );

        wp_localize_script(
            'skaaa-editor-dark-mode',
            'skaaaEditorDarkModeConfig',
            array(
                'i18n' => array(
                    'darkMode'      => __( 'Dark Mode', 'skaaa-no-code-design' ),
                    'lightMode'     => __( 'Light Mode', 'skaaa-no-code-design' ),
                    'switchToDark'  => __( 'Switch to Dark Mode (Preview)', 'skaaa-no-code-design' ),
                    'switchToLight' => __( 'Switch to Light Mode (Preview)', 'skaaa-no-code-design' ),
                ),
            )
        );
    }

    /**
     * Inject Anti-FOUC script for Dark Mode.
     */
    public function inject_anti_fouc_script()
    {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        echo "<script id='skaaa-anti-fouc'>
            (function() {
                try {
                    var skaaaTheme = localStorage.getItem('skaaa_dark_mode');
                    if (skaaaTheme === 'true') {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>\n";
    }

    /**
     * Inject Tailwind CDN or Local JIT CSS.
     */
    public function inject_tailwind_cdn()
    {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        $classes_to_scan = '';

        // 1. Frontend Context -> Local JIT Compilation (Production)
        if ( is_home() || is_archive() || is_front_page() || is_search() ) {
            global $wp_query;
            if ( ! empty( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
                foreach ( $wp_query->posts as $p ) {
                    $classes_to_scan .= $this->style_manager->scan_post_classes( $p->ID ) . ' ';
                }
            }
        } else {
            $post_id = get_the_ID();
            if ($post_id) {
                $classes_to_scan .= $this->style_manager->scan_post_classes($post_id) . ' ';
            }
        }

        // 2. Scan Theme Builder Organisms if Active
        global $skaaa_active_theme_organisms;
        if (!empty($skaaa_active_theme_organisms) && is_array($skaaa_active_theme_organisms)) {
            foreach ($skaaa_active_theme_organisms as $org_id) {
                $classes_to_scan .= $this->style_manager->scan_organism_classes($org_id) . ' ';
            }
        }

        // Cho phép các plugin khác đóng góp các class cần scan qua filter
        $classes_to_scan = apply_filters( 'skaaa_design_classes_to_scan', $classes_to_scan );

        if (!empty(trim($classes_to_scan))) {
            // Compile classes to CSS
            $result = $this->compiler->compile_classes($classes_to_scan);
            $css = $result['css'];
            $unresolved = $result['unresolved'];

            // Output Compiled CSS
            if ($css) {
                $scanned_debug = '/* Scanned Classes: ' . esc_html(implode(', ', array_unique(array_filter(explode(' ', $classes_to_scan))))) . ' */';
                echo "<style id='skaaa-jit-styles'>\n{$scanned_debug}\n{$css}\n</style>" . "\n";
            }

            // HYBRID FALLBACK: Removed per Zero CDN Policy.
            if (!empty($unresolved)) {
                echo "<!-- Unresolved classes (Zero CDN Policy Active): " . esc_html(implode(', ', $unresolved)) . " -->\n";
            }
        }
    }
}
