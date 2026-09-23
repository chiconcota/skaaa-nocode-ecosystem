<?php
/**
 * Lớp Quản Lý Triển Khai Mã Nguồn Từ Xa (Skaaai_File_Deployer)
 *
 * Chịu trách nhiệm kiểm tra cú pháp PHP an toàn (Syntax Validator)
 * và ghi/xóa file custom nodes thông qua WP_Filesystem.
 *
 * @package Skaaai
 * @version 1.0.3
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

class File_Deployer {

    /**
     * Thư mục đích cho các Custom Nodes (Persistent Storage ngoài thư mục plugin)
     */
    public static function get_target_dir(): string {
        $dir = defined( 'SKAAAI_CUSTOM_NODES_DIR' )
            ? SKAAAI_CUSTOM_NODES_DIR
            : ( ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content' ) . '/skaaa-custom-nodes/' );
        self::ensure_target_dir( $dir );
        return $dir;
    }

    /**
     * Đảm bảo thư mục tồn tại và di chuyển tự động các file cũ từ legacy plugin dir
     */
    public static function ensure_target_dir( string $dir ): void {
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
            $index_file = $dir . 'index.php';
            if ( ! file_exists( $index_file ) ) {
                file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
            }
        }

        // Tự động di chuyển (Migrate) các file từ thư mục cũ SKAAAI_DIR . 'custom-nodes/' nếu có
        $legacy_dir = SKAAAI_DIR . 'custom-nodes/';
        if ( is_dir( $legacy_dir ) && realpath( $legacy_dir ) !== realpath( $dir ) ) {
            $legacy_files = glob( $legacy_dir . '*.php*' );
            if ( ! empty( $legacy_files ) ) {
                foreach ( $legacy_files as $lfile ) {
                    $bname = basename( $lfile );
                    if ( $bname === 'index.php' ) {
                        continue;
                    }
                    $dest = $dir . $bname;
                    if ( ! file_exists( $dest ) ) {
                        @copy( $lfile, $dest );
                    }
                }
            }
        }
    }

    /**
     * Kiểm tra cú pháp mã nguồn PHP trước khi lưu xuống đĩa
     *
     * @param string $code Đoạn mã PHP cần kiểm tra
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validate_php_syntax( string $code ): array {
        $code = trim( $code );
        if ( empty( $code ) ) {
            return [
                'valid' => false,
                'error' => __( 'Code content cannot be empty.', 'skaaai' ),
            ];
        }

        // Bắt buộc phải có thẻ mở PHP
        if ( ! str_contains( $code, '<?php' ) ) {
            return [
                'valid' => false,
                'error' => __( 'Code must contain opening PHP tag (<?php).', 'skaaai' ),
            ];
        }

        // 1. Kiểm tra Tokenizer cấp thấp
        try {
            $tokens = token_get_all( $code, TOKEN_PARSE );
            
            // Đếm dấu ngoặc để đảm bảo tính cân bằng
            $brace_count       = 0;
            $parenthesis_count = 0;
            $bracket_count     = 0;

            foreach ( $tokens as $token ) {
                if ( is_string( $token ) ) {
                    if ( $token === '{' ) {
                        $brace_count++;
                    } elseif ( $token === '}' ) {
                        $brace_count--;
                    } elseif ( $token === '(' ) {
                        $parenthesis_count++;
                    } elseif ( $token === ')' ) {
                        $parenthesis_count--;
                    } elseif ( $token === '[' ) {
                        $bracket_count++;
                    } elseif ( $token === ']' ) {
                        $bracket_count--;
                    }
                }
            }

            if ( $brace_count !== 0 ) {
                return [
                    'valid' => false,
                    'error' => __( 'Unbalanced curly braces { } detected in code.', 'skaaai' ),
                ];
            }
            if ( $parenthesis_count !== 0 ) {
                return [
                    'valid' => false,
                    'error' => __( 'Unbalanced parentheses ( ) detected in code.', 'skaaai' ),
                ];
            }
            if ( $bracket_count !== 0 ) {
                return [
                    'valid' => false,
                    'error' => __( 'Unbalanced square brackets [ ] detected in code.', 'skaaai' ),
                ];
            }
        } catch ( \ParseError $e ) {
            return [
                'valid' => false,
                'error' => sprintf( __( 'PHP Parse Error on line %d: %s', 'skaaai' ), $e->getLine(), $e->getMessage() ),
            ];
        } catch ( \Throwable $t ) {
            return [
                'valid' => false,
                'error' => $t->getMessage(),
            ];
        }

        // 2. Chạy php -l qua temp file nếu hệ điều hành cho phép
        if ( function_exists( 'proc_open' ) && ! in_array( 'proc_open', explode( ',', (string) ini_get( 'disable_functions' ) ), true ) ) {
            $temp_file = tempnam( sys_get_temp_dir(), 'skaaai_lint_' );
            if ( $temp_file ) {
                file_put_contents( $temp_file, $code );
                $cmd = 'php -l -d display_errors=1 ' . escapeshellarg( $temp_file );
                $descriptorspec = [
                    1 => [ 'pipe', 'w' ],
                    2 => [ 'pipe', 'w' ],
                ];
                $process = @proc_open( $cmd, $descriptorspec, $pipes );
                if ( is_resource( $process ) ) {
                    $stdout = stream_get_contents( $pipes[1] );
                    $stderr = stream_get_contents( $pipes[2] );
                    fclose( $pipes[1] );
                    fclose( $pipes[2] );
                    $return_val = proc_close( $process );
                    @unlink( $temp_file );

                    if ( $return_val !== 0 ) {
                        $error_output = trim( $stdout . ' ' . $stderr );
                        // Nếu lệnh php không tồn tại trong PATH của webserver (127 hoặc command not found), bỏ qua và tin cậy Tokenizer
                        if ( $return_val === 127 || str_contains( strtolower( $error_output ), 'command not found' ) || str_contains( strtolower( $error_output ), 'not recognized' ) ) {
                            // PHP CLI không khả dụng trong môi trường FPM, kết quả Tokenizer là đủ an toàn
                        } else {
                            return [
                                'valid' => false,
                                'error' => sprintf( __( 'PHP Linter Error: %s', 'skaaai' ), $error_output ),
                            ];
                        }
                    }
                } else {
                    @unlink( $temp_file );
                }
            }
        }

        return [
            'valid' => true,
            'error' => '',
        ];
    }

    /**
     * Triển khai lưu file code vào thư mục sandbox
     *
     * @param string $filename Tên file (ví dụ: class-telegram-node.php)
     * @param string $code Nội dung code PHP
     * @param array  $node_meta Thông tin cấu hình node để tự động đăng ký
     * @param bool   $overwrite Cho phép ghi đè nếu đã có
     * @return array
     */
    public static function deploy_file( string $filename, string $code, array $node_meta = [], bool $overwrite = true, bool $is_local_save = false ): array {
        // Kiểm tra quyền nhận code trên Receiver (nếu không phải là local save của chính admin)
        if ( ! $is_local_save ) {
            $allow_deploy = Core::get_setting( 'skaaai_allow_code_deploy', false );
            if ( ! $allow_deploy ) {
                return [
                    'success' => false,
                    'message' => __( 'Remote code deployment is disabled on this server. Enable it in Skaaa Bridge settings.', 'skaaai' ),
                ];
            }
        }

        // Khử trùng tên file
        $clean_name = sanitize_file_name( basename( $filename ) );
        if ( empty( $clean_name ) || ! str_ends_with( strtolower( $clean_name ), '.php' ) ) {
            return [
                'success' => false,
                'message' => __( 'Invalid file name. Only .php files are allowed.', 'skaaai' ),
            ];
        }

        if ( $clean_name === 'index.php' ) {
            return [
                'success' => false,
                'message' => __( 'Cannot overwrite index.php protection file.', 'skaaai' ),
            ];
        }

        // Kiểm tra cú pháp PHP trước khi ghi
        $lint_check = self::validate_php_syntax( $code );
        if ( ! $lint_check['valid'] ) {
            return [
                'success' => false,
                'message' => sprintf( __( 'Syntax check failed: %s', 'skaaai' ), $lint_check['error'] ),
            ];
        }

        // Khởi tạo WP_Filesystem
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        if ( ! $wp_filesystem ) {
            return [
                'success' => false,
                'message' => __( 'Unable to initialize WordPress Filesystem.', 'skaaai' ),
            ];
        }

        $target_dir = self::get_target_dir();
        if ( ! $wp_filesystem->is_dir( $target_dir ) ) {
            $wp_filesystem->mkdir( $target_dir, FS_CHMOD_DIR );
        }

        $target_file = $target_dir . $clean_name;
        $backup_made = false;

        // Nếu file đã tồn tại và cho phép ghi đè -> tạo bản sao lưu .bak
        if ( $wp_filesystem->exists( $target_file ) ) {
            if ( ! $overwrite ) {
                return [
                    'success' => false,
                    'message' => sprintf( __( 'File %s already exists and overwrite is set to false.', 'skaaai' ), $clean_name ),
                ];
            }
            $backup_file = $target_file . '.bak';
            $wp_filesystem->copy( $target_file, $backup_file, true );
            $backup_made = true;
        }

        // Ghi file mới
        $written = $wp_filesystem->put_contents( $target_file, $code, FS_CHMOD_FILE );
        if ( ! $written ) {
            return [
                'success' => false,
                'message' => sprintf( __( 'Failed to write file %s to disk.', 'skaaai' ), $clean_name ),
            ];
        }

        // Cập nhật metadata nếu có
        if ( ! empty( $node_meta['type'] ) && ! empty( $node_meta['class'] ) ) {
            $current_meta = Core::get_setting( 'skaaai_custom_nodes_meta', [] );
            if ( ! is_array( $current_meta ) ) {
                $current_meta = [];
            }
            $current_meta[ $node_meta['type'] ] = $node_meta;
            Core::set_setting( 'skaaai_custom_nodes_meta', $current_meta );
        }

        return [
            'success'     => true,
            'message'     => sprintf( __( 'File %s successfully deployed.', 'skaaai' ), $clean_name ),
            'filename'    => $clean_name,
            'backup_made' => $backup_made,
            'path'        => $target_file,
        ];
    }

    /**
     * Lưu file mã nguồn trực tiếp vào thư mục custom-nodes/ của máy cục bộ
     *
     * @param string $filename
     * @param string $code
     * @param array  $node_meta
     * @param bool   $overwrite
     * @return array
     */
    public static function save_local_file( string $filename, string $code, array $node_meta = [], bool $overwrite = true ): array {
        return self::deploy_file( $filename, $code, $node_meta, $overwrite, true );
    }

    /**
     * Lấy nội dung của một file trong custom-nodes/
     *
     * @param string $filename
     * @return string|null
     */
    public static function get_file_content( string $filename ): ?string {
        $clean_name = sanitize_file_name( basename( $filename ) );
        $path = self::get_target_dir() . $clean_name;
        if ( file_exists( $path ) ) {
            return (string) file_get_contents( $path );
        }
        return null;
    }

    /**
     * Lấy danh sách các file custom node đã triển khai
     *
     * @return array
     */
    public static function get_deployed_files(): array {
        $dir = self::get_target_dir();
        if ( ! is_dir( $dir ) ) {
            return [];
        }

        $files = glob( $dir . '*.php' );
        $result = [];

        foreach ( $files as $file ) {
            $name = basename( $file );
            if ( $name === 'index.php' ) {
                continue;
            }

            $result[] = [
                'filename' => $name,
                'size'     => filesize( $file ),
                'modified' => filemtime( $file ),
                'has_bak'  => file_exists( $file . '.bak' ),
            ];
        }

        return $result;
    }

    /**
     * Xóa một file custom node khỏi thư mục sandbox
     *
     * @param string $filename
     * @return array
     */
    public static function delete_file( string $filename ): array {
        $clean_name = sanitize_file_name( basename( $filename ) );
        if ( empty( $clean_name ) || $clean_name === 'index.php' ) {
            return [
                'success' => false,
                'message' => __( 'Invalid file name for deletion.', 'skaaai' ),
            ];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        $target_file = self::get_target_dir() . $clean_name;
        if ( ! $wp_filesystem->exists( $target_file ) ) {
            return [
                'success' => false,
                'message' => __( 'File not found on server.', 'skaaai' ),
            ];
        }

        $deleted = $wp_filesystem->delete( $target_file );
        // Xóa luôn file backup nếu có
        if ( $wp_filesystem->exists( $target_file . '.bak' ) ) {
            $wp_filesystem->delete( $target_file . '.bak' );
        }

        return [
            'success' => $deleted,
            'message' => $deleted ? sprintf( __( 'File %s deleted successfully.', 'skaaai' ), $clean_name ) : __( 'Failed to delete file.', 'skaaai' ),
        ];
    }
}
