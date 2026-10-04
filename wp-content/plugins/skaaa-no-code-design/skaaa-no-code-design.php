<?php
/**
 * Plugin Name: Skaaa No-Code Design
 * Description: The UI design engine and blocks framework for Skaaa No-Code Builder.
 * Version: 2.4.8
 * Author: Skaaa Team
 * Text Domain: skaaa-no-code-design
 *
 * @package Skaaa_No_Code_Design
 */

defined( 'ABSPATH' ) || exit;

// Define Constants
define( 'SKAAA_DESIGN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SKAAA_DESIGN_URL', plugin_dir_url( __FILE__ ) );

// Load Text Domain
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'skaaa-no-code-design', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// 4. Load System Framework (Drop-in, Must load early)
require_once SKAAA_DESIGN_PATH . 'inc/skaaa-system-framework/init.php';

/**
 * Load Modules
 */
function skaaa_no_code_design_init() {
	// 0. Utilities
	require_once SKAAA_DESIGN_PATH . 'inc/utils/class-assets.php';
	require_once SKAAA_DESIGN_PATH . 'inc/utils/class-dynamic-data.php';

	// 1. Data Engine
	require_once SKAAA_DESIGN_PATH . 'inc/data-engine/data-engine.php';

	// 2. Logic Engine
	require_once SKAAA_DESIGN_PATH . 'inc/logic-engine/logic-engine.php';

	// 3. Design Engine
	require_once SKAAA_DESIGN_PATH . 'inc/design-engine/design-engine.php';
    
    // Dashboard Integration (Module Hooking)
    require_once SKAAA_DESIGN_PATH . 'inc/design-engine/class-dashboard-integration.php';
    if ( class_exists( 'Skaaa_Design_Dashboard_Integration' ) ) {
        Skaaa_Design_Dashboard_Integration::init();
    }

    // Design Tokens (Brand & Theme Options)
    require_once SKAAA_DESIGN_PATH . 'inc/design-engine/class-design-tokens-api.php';
    require_once SKAAA_DESIGN_PATH . 'inc/design-engine/class-design-tokens-compiler.php';
    require_once SKAAA_DESIGN_PATH . 'inc/design-engine/class-design-tokens-ui.php';
    require_once SKAAA_DESIGN_PATH . 'inc/design-engine/class-organisms-api.php';
    require_once SKAAA_DESIGN_PATH . 'inc/design-engine/class-organism-editor.php';
    require_once SKAAA_DESIGN_PATH . 'inc/design-engine/class-design-workspace-ui.php';
    
    // Theme Builder
    require_once SKAAA_DESIGN_PATH . 'inc/theme-builder/class-skaaa-theme-builder.php';

    if ( class_exists( '\Skaaa\Design\Api\Design_Tokens_API' ) ) {
        \Skaaa\Design\Api\Design_Tokens_API::get_instance();
    }
    if ( class_exists( '\Skaaa\Design\Api\Design_Tokens_Compiler' ) ) {
        \Skaaa\Design\Api\Design_Tokens_Compiler::get_instance();
    }
    if ( class_exists( '\Skaaa\Design\Api\Organisms_API' ) ) {
        \Skaaa\Design\Api\Organisms_API::get_instance();
    }
    if ( class_exists( '\Skaaa\Design\Admin\Design_Tokens_UI' ) ) {
        \Skaaa\Design\Admin\Design_Tokens_UI::get_instance();
    }
    if ( class_exists( '\Skaaa\Design\Admin\Design_Workspace_UI' ) ) {
        \Skaaa\Design\Admin\Design_Workspace_UI::get_instance();
    }
    if ( class_exists( '\Skaaa\Builder\Design\Organism_Editor' ) ) {
        \Skaaa\Builder\Design\Organism_Editor::instance();
    }
    if ( class_exists( '\Skaaa_No_Code_Design\Theme_Builder\Skaaa_Theme_Builder' ) ) {
        $theme_builder = new \Skaaa_No_Code_Design\Theme_Builder\Skaaa_Theme_Builder();
        $theme_builder->init();
    }

	// 4. Blocks System
	require_once SKAAA_DESIGN_PATH . 'blocks/init.php';
}

add_action( 'plugins_loaded', 'skaaa_no_code_design_init' );

// Migrate Database Sys Presets
add_action('admin_init', function() {
    if ( ! get_option('skaaa_temp_fix_sys_presets_db_5', false) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'skaaa_data_sys_presets';
        
        if ( $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table ) {
            $cols = $wpdb->get_col("DESCRIBE {$table}", 0);
            if ($cols && ! in_array('name', $cols)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN name VARCHAR(255) DEFAULT ''");
            }
            if ($cols && ! in_array('type', $cols)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN type VARCHAR(255) DEFAULT ''");
            }
            if ($cols && ! in_array('value', $cols)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN value LONGTEXT");
            }
            if ($cols && in_array('json_content', $cols)) {
                $wpdb->query("ALTER TABLE {$table} DROP COLUMN json_content");
            }
            
            $dict = get_option('skaaa_data_dictionary', []);
            if (isset($dict[$table])) {
                unset($dict[$table]['json_content']);
                $dict[$table]['name'] = array(
                    'label' => 'Name',
                    'type' => 'short_text',
                    'options' => ''
                );
                $dict[$table]['type'] = array(
                    'label' => 'Type', 
                    'type' => 'enum', 
                    'options' => 'token_brand, token_color, token_dark_color, token_font, token_spacing, token_radius, token_shadow, preset_typography, preset_component'
                );
                $dict[$table]['value'] = array(
                    'label' => 'Value', 
                    'type' => 'long_text', 
                    'options' => ''
                );
                update_option('skaaa_data_dictionary', $dict);
            }
        }
        
        update_option('skaaa_temp_fix_sys_presets_db_5', true);
    }
});
