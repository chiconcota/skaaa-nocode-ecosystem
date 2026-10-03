<?php
/**
 * Flat Database Inspector & Safe Query Runner CLI Tool (db-tool.php)
 *
 * Provides safe, non-blocking CLI inspection and querying of Skaaa flat tables (wp_skaaa_data_*).
 * Prevents interactive MySQL CLI crashes and shell hanging (resolves MISTAKE-001).
 *
 * Usage:
 *   php .agent/harness/db-tool.php --list-tables
 *   php .agent/harness/db-tool.php --schema=wp_skaaa_data_leads
 *   php .agent/harness/db-tool.php --sample=wp_skaaa_data_leads [--limit=5]
 *   php .agent/harness/db-tool.php --query="SELECT * FROM wp_skaaa_data_leads LIMIT 5"
 *   php .agent/harness/db-tool.php --status
 *
 * Options:
 *   --format=table|json   Output format (default: table)
 *   --limit=N             Number of sample rows to retrieve (default: 5, max: 100)
 *   --force               Allow write/destructive operations in --query
 *   --help, -h            Show usage help
 *
 * @package Skaaai
 * @version 1.2.3
 */

// Define clean CLI die handler before WordPress loads
if ( ! defined( 'WP_DIE_HANDLER' ) ) {
    function skaaa_db_cli_die_handler( $message, $title = '', $args = [] ): void {
        $clean_msg = trim( strip_tags( (string) $message ) );
        fwrite( STDERR, "\n\033[31m[WORDPRESS/DATABASE ERROR]\033[0m {$clean_msg}\n" );
        fwrite( STDERR, "Hint: If running under Local by Flywheel, please start the site in the Local app.\n\n" );
        exit( 1 );
    }
    define( 'WP_DIE_HANDLER', 'skaaa_db_cli_die_handler' );
}

class Skaaa_DB_Tool {

    private static string $format = 'table';

    public static function run( array $args ): void {
        $options = self::parse_args( $args );

        // 1. Show help without requiring database
        if ( isset( $options['help'] ) || isset( $options['h'] ) || empty( $options ) ) {
            self::show_help();
            exit( 0 );
        }

        self::$format = strtolower( $options['format'] ?? 'table' );

        // 2. Pre-flight check database connectivity before booting WordPress
        self::preflight_db_check();

        // 3. Bootstrap WordPress environment
        self::bootstrap_wordpress();

        // 4. Verify database connection
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! empty( $wpdb->error ) ) {
            self::output_error( 'Database connection is not available. Please ensure your local server is running.' );
            exit( 1 );
        }

        try {
            if ( isset( $options['status'] ) ) {
                self::handle_status();
            } elseif ( isset( $options['list-tables'] ) || isset( $options['tables'] ) ) {
                self::handle_list_tables();
            } elseif ( isset( $options['schema'] ) ) {
                self::handle_schema( sanitize_text_field( (string) $options['schema'] ) );
            } elseif ( isset( $options['sample'] ) ) {
                $limit = max( 1, min( 100, (int) ( $options['limit'] ?? 5 ) ) );
                self::handle_sample( sanitize_text_field( (string) $options['sample'] ), $limit );
            } elseif ( isset( $options['set-tokens'] ) ) {
                self::handle_set_tokens( (string) $options['set-tokens'] );
            } elseif ( isset( $options['save-organism'] ) ) {
                $name          = sanitize_text_field( (string) ( $options['name'] ?? '' ) );
                $category      = sanitize_text_field( (string) ( $options['category'] ?? 'general' ) );
                $as_template   = isset( $options['as-template'] ) ? sanitize_key( (string) $options['as-template'] ) : '';
                $template_name = isset( $options['template-name'] ) ? sanitize_text_field( (string) $options['template-name'] ) : '';
                self::handle_save_organism( (string) $options['save-organism'], $name, $category, $as_template, $template_name );
            } elseif ( isset( $options['list-organisms'] ) ) {
                self::handle_list_organisms();
            } elseif ( isset( $options['list-templates'] ) ) {
                self::handle_list_templates();
            } elseif ( isset( $options['query'] ) ) {
                $force = isset( $options['force'] );
                self::handle_query( (string) $options['query'], $force );
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

    private static function preflight_db_check(): void {
        $config_file = self::locate_wp_file( 'wp-config.php' );

        if ( ! $config_file || ! file_exists( $config_file ) ) {
            return;
        }

        $content = (string) file_get_contents( $config_file );
        $host    = 'localhost';
        $user    = 'root';
        $pass    = 'root';
        $name    = 'local';

        if ( preg_match( "/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $m ) ) {
            $host = $m[1];
        }
        if ( preg_match( "/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $m ) ) {
            $user = $m[1];
        }
        if ( preg_match( "/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $m ) ) {
            $pass = $m[1];
        }
        if ( preg_match( "/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $m ) ) {
            $name = $m[1];
        }

        $local_socket = self::detect_local_mysql_socket();
        if ( $local_socket && file_exists( $local_socket ) ) {
            @ini_set( 'mysqli.default_socket', $local_socket );
            @ini_set( 'pdo_mysql.default_socket', $local_socket );
        }

        // Test connection with short timeout
        $link = @mysqli_init();
        if ( $link ) {
            @mysqli_options( $link, MYSQLI_OPT_CONNECT_TIMEOUT, 2 );
            $port   = 3306;
            $socket = $local_socket;

            if ( str_contains( $host, ':' ) ) {
                [ $host_part, $extra ] = explode( ':', $host, 2 );
                if ( is_numeric( $extra ) ) {
                    $port   = (int) $extra;
                    $host   = $host_part;
                } else {
                    $socket = $extra;
                    $host   = $host_part;
                }
            }

            try {
                $connected = @mysqli_real_connect( $link, $host, $user, $pass, $name, $port, $socket );
                if ( $connected ) {
                    @mysqli_close( $link );
                    return; // DB is reachable
                }
            } catch ( \Throwable $e ) {
                // Connection failed
            }
        }

        self::output_error(
            "Cannot connect to MySQL database at '{$host}' (DB: '{$name}').\n" .
            "  Hint: If using Local by Flywheel, please start your site in the Local app (Click 'Start Site')."
        );
        exit( 1 );
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

    private static function handle_status(): void {
        global $wpdb;

        $mysql_ver   = $wpdb->db_version();
        $prefix      = $wpdb->prefix;
        $db_name     = DB_NAME;
        $db_host     = DB_HOST;

        $tables = $wpdb->get_col( "SHOW TABLES LIKE '{$prefix}skaaa_data_%'" );
        $count  = is_array( $tables ) ? count( $tables ) : 0;

        $data = [
            'status'             => 'connected',
            'database'           => $db_name,
            'host'               => $db_host,
            'prefix'             => $prefix,
            'mysql_version'      => $mysql_ver,
            'skaaa_tables_count' => $count,
        ];

        if ( self::$format === 'json' ) {
            echo json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        echo "\n\033[1;32m=== SKAAA FLAT DATABASE STATUS ===\033[0m\n";
        echo "Database:       \033[36m{$db_name}\033[0m\n";
        echo "Host:           {$db_host}\n";
        echo "Table Prefix:   {$prefix}\n";
        echo "MySQL Version:  {$mysql_ver}\n";
        echo "Skaaa Tables:   \033[33m{$count} flat tables found\033[0m\n\n";
    }

    private static function handle_list_tables(): void {
        global $wpdb;

        $prefix = $wpdb->prefix;
        $sql    = "SHOW TABLE STATUS LIKE '{$prefix}skaaa_data_%'";
        $rows   = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $rows ) ) {
            $rows = $wpdb->get_results( "SHOW TABLE STATUS LIKE '%skaaa%'", ARRAY_A );
        }

        if ( empty( $rows ) ) {
            if ( self::$format === 'json' ) {
                echo json_encode( [ 'tables' => [] ] ) . "\n";
            } else {
                echo "\n\033[33mNo Skaaa flat tables found (matching pattern: {$prefix}skaaa_data_*).\033[0m\n\n";
            }
            return;
        }

        $formatted = [];
        foreach ( $rows as $row ) {
            $name       = $row['Name'] ?? '';
            $records    = (int) ( $row['Rows'] ?? 0 );
            $data_kb    = round( ( (int) ( $row['Data_length'] ?? 0 ) ) / 1024, 2 );
            $index_kb   = round( ( (int) ( $row['Index_length'] ?? 0 ) ) / 1024, 2 );
            $collation  = $row['Collation'] ?? '';

            $formatted[] = [
                'table'     => $name,
                'rows'      => $records,
                'data_kb'   => $data_kb,
                'index_kb'  => $index_kb,
                'collation' => $collation,
            ];
        }

        if ( self::$format === 'json' ) {
            echo json_encode( [ 'tables' => $formatted ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        echo "\n\033[1;32m=== SKAAA FLAT TABLES (" . count( $formatted ) . ") ===\033[0m\n";
        self::render_table(
            [ 'Table Name', 'Rows', 'Data (KB)', 'Index (KB)', 'Collation' ],
            array_map( function( $t ) {
                return [ $t['table'], (string) $t['rows'], (string) $t['data_kb'], (string) $t['index_kb'], $t['collation'] ];
            }, $formatted )
        );
    }

    private static function handle_schema( string $table ): void {
        global $wpdb;

        $table = sanitize_key( $table );
        if ( preg_match( '/(?:^|_)skaaa_data_(.+)$/', $table, $matches ) ) {
            $table = $wpdb->prefix . 'skaaa_data_' . $matches[1];
        } elseif ( ! str_starts_with( $table, $wpdb->prefix ) ) {
            $table = $wpdb->prefix . ( str_starts_with( $table, 'skaaa_data_' ) ? $table : 'skaaa_data_' . $table );
        }

        $columns = $wpdb->get_results( "DESCRIBE `{$table}`", ARRAY_A );

        if ( empty( $columns ) ) {
            self::output_error( "Table '{$table}' does not exist or cannot be inspected." );
            exit( 1 );
        }

        if ( self::$format === 'json' ) {
            echo json_encode( [ 'table' => $table, 'columns' => $columns ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        echo "\n\033[1;32m=== SCHEMA: {$table} ===\033[0m\n";
        self::render_table(
            [ 'Field', 'Type', 'Null', 'Key', 'Default', 'Extra' ],
            array_map( function( $c ) {
                return [
                    $c['Field'] ?? '',
                    $c['Type'] ?? '',
                    $c['Null'] ?? '',
                    $c['Key'] ?? '',
                    (string) ( $c['Default'] ?? 'NULL' ),
                    $c['Extra'] ?? '',
                ];
            }, $columns )
        );
    }

    private static function handle_sample( string $table, int $limit ): void {
        global $wpdb;

        $table = sanitize_key( $table );
        if ( preg_match( '/(?:^|_)skaaa_data_(.+)$/', $table, $matches ) ) {
            $table = $wpdb->prefix . 'skaaa_data_' . $matches[1];
        } elseif ( ! str_starts_with( $table, $wpdb->prefix ) ) {
            $table = $wpdb->prefix . ( str_starts_with( $table, 'skaaa_data_' ) ? $table : 'skaaa_data_' . $table );
        }

        $rows = $wpdb->get_results( "SELECT * FROM `{$table}` LIMIT {$limit}", ARRAY_A );

        if ( false === $rows ) {
            self::output_error( "Query failed: " . $wpdb->last_error );
            exit( 1 );
        }

        if ( empty( $rows ) ) {
            if ( self::$format === 'json' ) {
                echo json_encode( [ 'table' => $table, 'rows' => [] ] ) . "\n";
            } else {
                echo "\n\033[33mTable '{$table}' is empty (0 records).\033[0m\n\n";
            }
            return;
        }

        if ( self::$format === 'json' ) {
            echo json_encode( [ 'table' => $table, 'count' => count( $rows ), 'rows' => $rows ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
            return;
        }

        echo "\n\033[1;32m=== SAMPLE DATA: {$table} (Limit: {$limit}) ===\033[0m\n";
        $headers = array_keys( $rows[0] );
        $table_rows = [];
        foreach ( $rows as $row ) {
            $formatted_row = [];
            foreach ( $headers as $col ) {
                $val = $row[ $col ] ?? 'NULL';
                if ( strlen( (string) $val ) > 40 ) {
                    $val = substr( (string) $val, 0, 37 ) . '...';
                }
                $formatted_row[] = (string) $val;
            }
            $table_rows[] = $formatted_row;
        }

        self::render_table( $headers, $table_rows );
    }

    private static function handle_query( string $query, bool $force ): void {
        global $wpdb;

        $trimmed = trim( $query );
        $upper   = strtoupper( $trimmed );

        $destructive = [ 'DROP', 'TRUNCATE', 'ALTER', 'DELETE', 'UPDATE', 'INSERT' ];
        foreach ( $destructive as $kw ) {
            if ( preg_match( '/\b' . $kw . '\b/i', $upper ) ) {
                if ( ! $force ) {
                    self::output_error( "Destructive query detected containing '{$kw}'. To execute, add --force flag." );
                    exit( 1 );
                }
                break;
            }
        }

        if ( str_starts_with( $upper, 'SELECT' ) && ! preg_match( '/\bLIMIT\b/i', $upper ) ) {
            $trimmed .= ' LIMIT 50';
        }

        $results = $wpdb->get_results( $trimmed, ARRAY_A );

        if ( ! empty( $wpdb->last_error ) ) {
            self::output_error( "SQL Error: " . $wpdb->last_error );
            exit( 1 );
        }

        if ( self::$format === 'json' ) {
            echo json_encode( [ 'query' => $trimmed, 'count' => count( $results ?? [] ), 'results' => $results ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
            return;
        }

        if ( empty( $results ) ) {
            echo "\n\033[33mQuery executed successfully. Result set is empty (0 rows).\033[0m\n\n";
            return;
        }

        echo "\n\033[1;32m=== QUERY RESULTS (" . count( $results ) . " rows) ===\033[0m\n";
        $headers = array_keys( $results[0] );
        $table_rows = [];
        foreach ( $results as $row ) {
            $formatted_row = [];
            foreach ( $headers as $col ) {
                $val = $row[ $col ] ?? 'NULL';
                if ( strlen( (string) $val ) > 40 ) {
                    $val = substr( (string) $val, 0, 37 ) . '...';
                }
                $formatted_row[] = (string) $val;
            }
            $table_rows[] = $formatted_row;
        }

        self::render_table( $headers, $table_rows );
    }

    private static function render_table( array $headers, array $rows ): void {
        $widths = [];
        foreach ( $headers as $i => $h ) {
            $widths[ $i ] = mb_strlen( $h );
        }
        foreach ( $rows as $row ) {
            foreach ( $row as $i => $cell ) {
                $len = mb_strlen( (string) $cell );
                if ( $len > ( $widths[ $i ] ?? 0 ) ) {
                    $widths[ $i ] = $len;
                }
            }
        }

        $border = '+';
        foreach ( $widths as $w ) {
            $border .= str_repeat( '-', $w + 2 ) . '+';
        }
        echo $border . "\n";

        echo '|';
        foreach ( $headers as $i => $h ) {
            echo ' ' . str_pad( $h, $widths[ $i ] ) . ' |';
        }
        echo "\n" . $border . "\n";

        foreach ( $rows as $row ) {
            echo '|';
            foreach ( $row as $i => $cell ) {
                echo ' ' . str_pad( (string) $cell, $widths[ $i ] ) . ' |';
            }
            echo "\n";
        }
        echo $border . "\n\n";
    }

    private static function output_error( string $msg ): void {
        if ( self::$format === 'json' ) {
            echo json_encode( [ 'error' => true, 'message' => $msg ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
        } else {
            fwrite( STDERR, "\n\033[31m[ERROR]\033[0m {$msg}\n\n" );
        }
    }

    private static function handle_set_tokens( string $input ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'skaaa_data_sys_presets';

        // Check if table exists
        $check_table = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        if ( empty( $check_table ) ) {
            self::output_error( "Table '{$table}' does not exist. Please ensure Skaaa Data Pro or Skaaa No-Code Design is active." );
            exit( 1 );
        }

        $raw_json = $input;
        if ( file_exists( $input ) ) {
            $raw_json = (string) file_get_contents( $input );
        }

        $data = json_decode( $raw_json, true );
        if ( ! is_array( $data ) ) {
            self::output_error( "Invalid JSON provided for --set-tokens. Please pass a valid JSON string or file path." );
            exit( 1 );
        }

        $updated_items = [];

        // 1. Brand Logo
        if ( isset( $data['brand']['logourl'] ) || isset( $data['brand']['logo'] ) ) {
            $logo_url = (string) ( $data['brand']['logourl'] ?? $data['brand']['logo'] );
            self::upsert_preset( $table, 'token_brand', 'LogoUrl', $logo_url );
            $updated_items[] = [ 'token_brand', 'LogoUrl', $logo_url ];
        }

        // 2. Light Colors (token_color)
        if ( isset( $data['colors'] ) && is_array( $data['colors'] ) ) {
            foreach ( $data['colors'] as $key => $val ) {
                $preset_name = ucfirst( trim( (string) $key ) );
                self::upsert_preset( $table, 'token_color', $preset_name, (string) $val );
                $updated_items[] = [ 'token_color', $preset_name, (string) $val ];
            }
        }

        // 3. Dark Colors (token_dark_color)
        if ( isset( $data['darkColors'] ) && is_array( $data['darkColors'] ) ) {
            foreach ( $data['darkColors'] as $key => $val ) {
                $preset_name = ucfirst( trim( (string) $key ) );
                self::upsert_preset( $table, 'token_dark_color', $preset_name, (string) $val );
                $updated_items[] = [ 'token_dark_color', $preset_name, (string) $val ];
            }
        }

        // 4. Fonts (token_font)
        if ( isset( $data['fonts'] ) && is_array( $data['fonts'] ) ) {
            $font_map = [
                'primary'   => 'PrimaryFont',
                'secondary' => 'SecondaryFont',
                'mono'      => 'MonoFont',
            ];
            foreach ( $data['fonts'] as $key => $val ) {
                $preset_name = $font_map[ strtolower( (string) $key ) ] ?? ucfirst( (string) $key );
                self::upsert_preset( $table, 'token_font', $preset_name, (string) $val );
                $updated_items[] = [ 'token_font', $preset_name, (string) $val ];
            }
        }

        // 5. Trigger Compiler if available
        $compiled = false;
        if ( class_exists( '\Skaaa\Design\Api\Design_Tokens_Compiler' ) ) {
            try {
                \Skaaa\Design\Api\Design_Tokens_Compiler::get_instance()->compile_tokens_to_json();
                $compiled = true;
            } catch ( \Throwable $e ) {
                // Ignore compile error
            }
        }

        if ( self::$format === 'json' ) {
            echo json_encode( [
                'success'       => true,
                'updated_count' => count( $updated_items ),
                'compiled'      => $compiled,
                'tokens'        => $updated_items,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
            return;
        }

        echo "\n\033[1;32m=== DESIGN TOKENS PERSISTED TO DATABASE (" . count( $updated_items ) . " tokens) ===\033[0m\n";
        if ( $compiled ) {
            echo "\033[32m✔ Triggered Design_Tokens_Compiler -> Exported physical cache (tokens.json & CSS variables)\033[0m\n\n";
        } else {
            echo "\033[33m✔ Updated database table {$table} successfully.\033[0m\n\n";
        }

        $headers = [ 'Type', 'Name', 'Value' ];
        self::render_table( $headers, $updated_items );
    }

    private static function upsert_preset( string $table, string $type, string $name, string $value ): void {
        global $wpdb;
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE type = %s AND name = %s", $type, $name ) );
        if ( $existing ) {
            $wpdb->update( $table, [ 'value' => $value ], [ 'id' => (int) $existing ], [ '%s' ], [ '%d' ] );
        } else {
            $wpdb->insert( $table, [ 'type' => $type, 'name' => $name, 'value' => $value ], [ '%s', '%s', '%s' ] );
        }
    }

    private static function handle_save_organism( string $content_or_file, string $name, string $category, string $as_template = '', string $template_name = '' ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'skaaa_data_sys_organisms';

        if ( empty( $name ) ) {
            self::output_error( "--name is required when saving an organism. E.g.: --name=\"HeaderBar\"" );
            exit( 1 );
        }

        $markup = $content_or_file;
        if ( file_exists( $content_or_file ) ) {
            $markup = (string) file_get_contents( $content_or_file );
        }

        // Check if table exists
        $check_table = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        if ( empty( $check_table ) ) {
            self::output_error( "Table '{$table}' does not exist. Please ensure Skaaa Data Pro or Skaaa No-Code Design is active." );
            exit( 1 );
        }

        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s", $name ), ARRAY_A );
        if ( $existing ) {
            $wpdb->update(
                $table,
                [
                    'category'     => $category,
                    'html_content' => $markup,
                ],
                [ 'id' => (int) $existing['id'] ],
                [ '%s', '%s' ],
                [ '%d' ]
            );
            $organism_id = (int) $existing['id'];
            $action = 'Updated';
        } else {
            $wpdb->insert(
                $table,
                [
                    'type'         => 'organism',
                    'name'         => $name,
                    'category'     => $category,
                    'html_content' => $markup,
                ],
                [ '%s', '%s', '%s', '%s' ]
            );
            $organism_id = (int) $wpdb->insert_id;
            $action = 'Created';
        }

        $template_info = null;
        if ( ! empty( $as_template ) ) {
            $loc = strtolower( trim( $as_template ) );
            $tpl_table = $wpdb->prefix . 'skaaa_data_sys_theme_templates';
            $check_tpl = $wpdb->get_var( "SHOW TABLES LIKE '{$tpl_table}'" );

            if ( ! empty( $check_tpl ) ) {
                $final_tpl_name = ! empty( $template_name ) ? $template_name : $name;
                $conditions_json = json_encode( [
                    'rules' => [
                        [ 'rule' => 'all', 'type' => 'include' ],
                    ],
                ] );

                // Check existing template by location & name, or active template for this location
                $existing_tpl = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$tpl_table} WHERE location = %s AND name = %s", $loc, $final_tpl_name ), ARRAY_A );
                if ( ! $existing_tpl ) {
                    $existing_tpl = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$tpl_table} WHERE location = %s AND is_active = 1", $loc ), ARRAY_A );
                }

                if ( $existing_tpl ) {
                    $wpdb->update(
                        $tpl_table,
                        [
                            'name'        => $final_tpl_name,
                            'organism_id' => $organism_id,
                            'conditions'  => $conditions_json,
                            'is_active'   => 1,
                        ],
                        [ 'id' => (int) $existing_tpl['id'] ],
                        [ '%s', '%d', '%s', '%d' ],
                        [ '%d' ]
                    );
                    $template_info = [
                        'id'       => (int) $existing_tpl['id'],
                        'name'     => $final_tpl_name,
                        'location' => $loc,
                        'action'   => 'Updated',
                    ];
                } else {
                    $wpdb->insert(
                        $tpl_table,
                        [
                            'name'        => $final_tpl_name,
                            'location'    => $loc,
                            'organism_id' => $organism_id,
                            'conditions'  => $conditions_json,
                            'is_active'   => 1,
                        ],
                        [ '%s', '%s', '%d', '%s', '%d' ]
                    );
                    $template_info = [
                        'id'       => (int) $wpdb->insert_id,
                        'name'     => $final_tpl_name,
                        'location' => $loc,
                        'action'   => 'Created',
                    ];
                }
            }
        }

        if ( self::$format === 'json' ) {
            $json_payload = [
                'success'     => true,
                'action'      => $action,
                'organism_id' => $organism_id,
                'name'        => $name,
                'category'    => $category,
            ];
            if ( $template_info ) {
                $json_payload['theme_template'] = $template_info;
            }
            echo json_encode( $json_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
            return;
        }

        echo "\n\033[1;32m=== ORGANISM {$action} SUCCESSFULLY ===\033[0m\n";
        echo "ID:             \033[1m{$organism_id}\033[0m\n";
        echo "Name:           \033[1m{$name}\033[0m\n";
        echo "Category:       \033[1m{$category}\033[0m\n";
        echo "Organism Table: {$table}\n";

        if ( $template_info ) {
            echo "\n\033[1;36m=== THEME TEMPLATE {$template_info['action']} (Theme Builder) ===\033[0m\n";
            echo "Template ID:    \033[1m{$template_info['id']}\033[0m\n";
            echo "Template Name:  \033[1m{$template_info['name']}\033[0m\n";
            echo "Location (Tab): \033[1;33m{$template_info['location']}\033[0m\n";
            echo "Status:         \033[32mActive (100% Global - entire site)\033[0m\n";
            echo "Theme Builder:  \033[35mwp-admin/admin.php?page=skaaa-theme-builder (Tab: " . ucfirst( $template_info['location'] ) . ")\033[0m\n\n";
        } else {
            echo "\n";
        }
    }

    private static function handle_list_organisms(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'skaaa_data_sys_organisms';
        $rows = $wpdb->get_results( "SELECT id, name, category, created_at FROM {$table} ORDER BY id ASC", ARRAY_A );

        if ( self::$format === 'json' ) {
            echo json_encode( [ 'organisms' => $rows ?? [] ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        if ( empty( $rows ) ) {
            echo "\n\033[33mNo organisms found in {$table} (0 records).\033[0m\n\n";
            return;
        }

        echo "\n\033[1;32m=== REUSABLE ORGANISMS (" . count( $rows ) . ") ===\033[0m\n";
        $headers = [ 'ID', 'Name', 'Category', 'Created At' ];
        $formatted = [];
        foreach ( $rows as $r ) {
            $formatted[] = [ (string) $r['id'], (string) $r['name'], (string) $r['category'], (string) $r['created_at'] ];
        }
        self::render_table( $headers, $formatted );
    }

    private static function handle_list_templates(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'skaaa_data_sys_theme_templates';
        $check_table = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        if ( empty( $check_table ) ) {
            self::output_error( "Table '{$table}' does not exist. Please ensure Skaaa No-Code Design is active." );
            return;
        }

        $rows = $wpdb->get_results( "SELECT id, name, location, organism_id, is_active, created_at FROM {$table} ORDER BY id ASC", ARRAY_A );

        if ( self::$format === 'json' ) {
            echo json_encode( [ 'templates' => $rows ?? [] ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
            return;
        }

        if ( empty( $rows ) ) {
            echo "\n\033[33mNo theme templates found in {$table} (0 records).\033[0m\n";
            echo "Hint: Use --save-organism=\"path/to/header.html\" --name=\"HeaderBar\" --category=\"header\" --as-template=header to register.\n\n";
            return;
        }

        echo "\n\033[1;32m=== SKAAA THEME TEMPLATES (" . count( $rows ) . ") ===\033[0m\n";
        $headers = [ 'ID', 'Template Name', 'Location', 'Organism ID', 'Status', 'Created At' ];
        $formatted = [];
        foreach ( $rows as $r ) {
            $formatted[] = [
                (string) $r['id'],
                (string) $r['name'],
                (string) $r['location'],
                (string) ( $r['organism_id'] ?: 'None' ),
                ( (int) $r['is_active'] === 1 ) ? 'Active' : 'Draft',
                (string) ( $r['created_at'] ?? 'N/A' ),
            ];
        }
        self::render_table( $headers, $formatted );
    }

    private static function show_help(): void {
        echo <<<HELP

\033[1;36mSKAAA FLAT DATABASE INSPECTOR & SAFE QUERY RUNNER (db-tool.php)\033[0m
Usage:
  php .agent/harness/db-tool.php [command] [options]

Commands:
  --list-tables           List all Skaaa flat tables (wp_skaaa_data_*)
  --schema=TABLE          Inspect columns and types for a specific table
  --sample=TABLE          View sample rows from a table (default limit: 5)
  --query="SQL"           Execute a safe SELECT query (appends LIMIT 50 automatically)
  --status                Check database connection status and prefix

Design Tokens, Organisms & Theme Builder Commands:
  --set-tokens=JSON|FILE  Batch update Design Tokens in wp_skaaa_data_sys_presets and compile tokens.json
  --save-organism=MARKUP  Save/Update reusable component in wp_skaaa_data_sys_organisms
  --list-organisms        List all saved organisms in wp_skaaa_data_sys_organisms
  --list-templates        List all Theme Templates in wp_skaaa_data_sys_theme_templates

Options:
  --name=NAME             Organism name (required for --save-organism, e.g. "HeaderBar")
  --category=CAT          Organism category (header|footer|hero|card|general, default: general)
  --as-template=LOC       Register/Link as global Theme Template (header|footer|single|archive|404|app_layout)
  --template-name=NAME    Theme Template name in Theme Builder (default: same as --name)
  --format=table|json     Output format (default: table)
  --limit=N               Number of rows for sample (default: 5, max: 100)
  --force                 Permit write/destructive statements in --query
  --help, -h              Display this help menu

Examples:
  php .agent/harness/db-tool.php --set-tokens='{"brand":{"logourl":"http://.../logo.svg"},"colors":{"primary":"#f59e0b"}}'
  php .agent/harness/db-tool.php --save-organism="path/to/header.html" --name="HeaderBar" --category="header" --as-template=header
  php .agent/harness/db-tool.php --save-organism="path/to/footer.html" --name="FooterBar" --category="footer" --as-template=footer
  php .agent/harness/db-tool.php --list-templates
  php .agent/harness/db-tool.php --list-organisms
  php .agent/harness/db-tool.php --list-tables

HELP;
    }
}

// Execute CLI
Skaaa_DB_Tool::run( $argv ?? [] );
