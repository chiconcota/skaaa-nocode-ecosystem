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
 * @version 1.2.0
 */

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

    private static function preflight_db_check(): void {
        $search_paths = [
            dirname( __DIR__, 2 ) . '/wp-config.php',
            dirname( __DIR__, 3 ) . '/wp-config.php',
            dirname( __DIR__, 4 ) . '/wp-config.php',
            '/var/www/html/wp-config.php',
        ];

        $config_file = null;
        foreach ( $search_paths as $file ) {
            if ( file_exists( $file ) ) {
                $config_file = $file;
                break;
            }
        }

        if ( ! $config_file ) {
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

        // Test connection with short timeout
        $link = @mysqli_init();
        if ( $link ) {
            @mysqli_options( $link, MYSQLI_OPT_CONNECT_TIMEOUT, 1 );
            $port = 3306;
            $socket = null;
            if ( str_contains( $host, ':' ) ) {
                [ $host_part, $extra ] = explode( ':', $host, 2 );
                if ( is_numeric( $extra ) ) {
                    $port = (int) $extra;
                    $host = $host_part;
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
                // Connection failed (e.g. socket not available or server offline)
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

        $search_paths = [
            dirname( __DIR__, 2 ) . '/wp-load.php',
            dirname( __DIR__, 3 ) . '/wp-load.php',
            dirname( __DIR__, 4 ) . '/wp-load.php',
            '/var/www/html/wp-load.php',
        ];

        $loaded = false;
        foreach ( $search_paths as $file ) {
            if ( file_exists( $file ) ) {
                @ini_set( 'display_errors', '0' );
                require_once $file;
                $loaded = true;
                break;
            }
        }

        if ( ! $loaded || ! defined( 'ABSPATH' ) ) {
            fwrite( STDERR, "\033[31m[ERROR]\033[0m Could not locate wp-load.php. Please run within a WordPress installation.\n" );
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
        if ( ! str_starts_with( $table, $wpdb->prefix ) && ! str_starts_with( $table, 'skaaa_data_' ) ) {
            $table = $wpdb->prefix . $table;
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
        if ( ! str_starts_with( $table, $wpdb->prefix ) && ! str_starts_with( $table, 'skaaa_data_' ) ) {
            $table = $wpdb->prefix . $table;
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

Options:
  --format=table|json     Output format (default: table)
  --limit=N               Number of rows for sample (default: 5, max: 100)
  --force                 Permit write/destructive statements in --query
  --help, -h              Display this help menu

Examples:
  php .agent/harness/db-tool.php --list-tables
  php .agent/harness/db-tool.php --schema=wp_skaaa_data_leads --format=json
  php .agent/harness/db-tool.php --sample=wp_skaaa_data_leads --limit=3
  php .agent/harness/db-tool.php --query="SELECT id, fullname, email FROM wp_skaaa_data_leads"

HELP;
    }
}

// Execute CLI
Skaaa_DB_Tool::run( $argv ?? [] );
