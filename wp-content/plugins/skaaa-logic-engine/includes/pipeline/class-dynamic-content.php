<?php

defined( 'ABSPATH' ) || exit;

/**
 * Lõi phân tích Ngữ cảnh & Dữ liệu Hiển thị Frontend (Dynamic Content Engine)
 * Đóng vai trò là Máy Hydration kết nối giữa Skaaa Data Pro và Khối thiết kế tĩnh.
 */
class Skaaa_Dynamic_Content {

    private static $instance = null;
    
    // RAM Cache để tránh gọi CSDL nhiều lần cho cùng 1 Bản ghi
    private $memory_cache = [];

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Cài đặt bộ đánh chặn khi render block/content
        $this->init_hooks();
    }

    private function init_hooks() {
        // [Legacy] Quét chữ Mustache {{...}} trong hàm in nội dung chuẩn của WordPress. 
        // Trọng số 90 để chạy sau khi Gutenberg render khối.
        add_filter( 'the_content', [ $this, 'parse_dynamic_tags' ], 90 );

        // [Skaaa V2] Đánh chặn luồng Render Block chuẩn của Universal Dynamic Binding 
        add_filter( 'render_block', [ $this, 'parse_skaaa_blocks' ], 10, 2 );
    }

    /**
     * Máy Nghiền & Gắn Dữ Liệu NoCode SkaaaFX V2
     */
    public function parse_skaaa_blocks( $block_content, $block ) {
        // Chỉ xử lý ngoài Frontend 
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $block_content;
        }

        // Bỏ qua nếu không phải Block của nhà Skaaa
        if ( empty( $block['blockName'] ) || strpos( $block['blockName'], 'skaaaaa-builder/' ) !== 0 ) {
            return $block_content;
        }

        // Hút Sinh lực từ Universal Dynamic Binding
        $script = '';
        if ( isset( $block['attrs']['skaaaDynamicBinding'] ) ) {
            if ( is_string( $block['attrs']['skaaaDynamicBinding'] ) ) {
                $script = trim( $block['attrs']['skaaaDynamicBinding'] );
            } elseif ( is_array( $block['attrs']['skaaaDynamicBinding'] ) && ! empty( $block['attrs']['skaaaDynamicBinding']['script'] ) ) {
                $script = trim( $block['attrs']['skaaaDynamicBinding']['script'] );
            }
        }

        if ( ! empty( $script ) ) {

            // Lấy con trỏ ngữ cảnh: Phổ biến nhất là $_GET['id'] cho các trang Chi tiết (Detail Portal)
            $record_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
            // Cho phép Frontend hiểu ngữ cảnh bảng hiện tại
            $table_alias = isset( $_GET['table'] ) ? sanitize_text_field( $_GET['table'] ) : '';
            
            $wp_post_id = get_the_ID();
            if ( ! $wp_post_id && isset( $GLOBALS['post']->ID ) ) {
                $wp_post_id = $GLOBALS['post']->ID;
            }

            // Cung cấp GLOBAL_ID & WP_POST_ID cho Phân giải AST
            $context = [ 
                'GLOBAL_ID'    => $record_id,
                'GLOBAL_TABLE' => $table_alias,
                'WP_POST_ID'   => $wp_post_id ? absint( $wp_post_id ) : 0,
            ]; 

            if ( class_exists( '\Skaaa\Logic\SkaaaFX\SkaaaFX_Engine' ) ) {
                $result = \Skaaa\Logic\SkaaaFX\SkaaaFX_Engine::execute( $script, $context );
                $symbols = $result['symbols'];

                // --- 1. Sát thủ Ẩn Hiện (Visibility Check) ---
                // Chấp nhận biến `visible = true` HOẶC nếu User chỉ gõ điều kiện cụt lủn `[tuoi] > 18` (trả về Boolean thuần túy)
                $is_visible = true;
                if ( strpos( $script, '{{#foreach' ) !== false ) {
                    $is_visible = true; // Bỏ qua visibility check nếu đây là template loop, tránh lỗi SkaaaFX trả về false
                } elseif ( isset( $symbols['visible'] ) ) {
                    $is_visible = (bool) $symbols['visible'];
                } elseif ( isset( $result['last_val'] ) && is_bool( $result['last_val'] ) ) {
                    $is_visible = $result['last_val'];
                }

                if ( ! $is_visible ) {
                    // Máy chém cắt đứt hoàn toàn Block ra khỏi giao diện HTML mà không cần CSS Display None!
                    return ''; 
                }

                // --- 2. Bơm Máu Giao Diện (Data Hydration) ---
                // Ưu tiên đọc biến `var data = ...` trước. Nếu User lười chỉ gõ cụt lủn `[app.model.col]` thì ta lấy kết quả tính toán cuối cùng (last_val).
                $new_data = null;
                if ( isset( $symbols['data'] ) ) {
                    $new_data = $symbols['data'];
                } elseif ( isset( $result['last_val'] ) && $result['last_val'] !== true && $result['last_val'] !== false && $result['last_val'] !== null ) {
                    $new_data = $result['last_val'];
                }

                if ( $new_data !== null ) {
                    
                    if ( $block['blockName'] === 'skaaaaa-builder/text' || $block['blockName'] === 'skaaaaa-builder/button' ) {
                        // Regex cực mạnh thay thế nội dung TEXT nằm giữa 2 thẻ Wrapper của Gutenberg
                        $block_content = preg_replace( '/(<[a-zA-Z0-9]+[^>]*>)(.*?)(<\/[a-zA-Z0-9]+>\s*)$/s', '${1}' . esc_html( $new_data ) . '${3}', $block_content );
                    }
                    else if ( $block['blockName'] === 'skaaaaa-builder/image' ) {
                        // Xử lý nạp ảnh động: Chấp nhận URL chuỗi hoặc Media Attachment ID
                        $img_url = '';
                        if ( is_numeric( $new_data ) ) {
                            $img_url = wp_get_attachment_image_url( intval( $new_data ), 'full' );
                        } elseif ( is_string( $new_data ) ) {
                            $img_url = trim( $new_data );
                        }

                        if ( ! empty( $img_url ) ) {
                            if ( strpos( $block_content, '<img' ) !== false ) {
                                // Nếu block đã có thẻ <img ...>: Thay thế thuộc tính src
                                if ( preg_match( '/<img\b[^>]*?\bsrc="[^"]*"/i', $block_content ) ) {
                                    $block_content = preg_replace( '/(<img\b[^>]*?\bsrc=")([^"]*)(")/i', '${1}' . esc_url( $img_url ) . '${3}', $block_content );
                                } else {
                                    $block_content = preg_replace( '/(<img\b)/i', '${1}src="' . esc_url( $img_url ) . '" ', $block_content );
                                }
                            } else {
                                // Nếu block đang ở dạng placeholder rỗng (div Icon Image): Thay thế div rỗng thành thẻ <img>
                                $aspect = isset( $block['attrs']['objectFit'] ) ? esc_attr( $block['attrs']['objectFit'] ) : 'object-cover';
                                $block_content = preg_replace(
                                    '/(<[a-zA-Z0-9]+[^>]*>)(.*?)(<\/[a-zA-Z0-9]+>\s*)$/s',
                                    '${1}<img class="w-full h-full ' . $aspect . '" src="' . esc_url( $img_url ) . '" alt="" />${3}',
                                    $block_content
                                );
                            }
                        }
                    }
                } else {
                    // CỘNG THÊM: Hỗ trợ vòng lặp {{#foreach schema.table_name.column_name}} để tự sinh Option Dropdown
                    if ( strpos( $script, '{{#foreach' ) !== false ) {
                        $block_content = $this->process_foreach_loop( $script, $block_content );
                    }
                }
            }
        }
        
        return $block_content;
    }

    /**
     * Bộ xử lý Vòng lặp ForEach tĩnh (Thường dùng cho Select Options)
     */
    private function process_foreach_loop( $script, $block_content ) {
        $path = '';
        $template = '';

        if ( preg_match( '/\{\{#foreach\s+([a-zA-Z0-9_\.]+)\s*\}\}(.*?)\{\{\/foreach\}\}/s', $script, $matches ) ) {
            $path = $matches[1];
            $template = $matches[2];
        } elseif ( preg_match( '/\{\{#foreach\s+([a-zA-Z0-9_\.]+)\s*\}\}/s', $script, $matches ) ) {
            // Trường hợp Auto-generate cho Select: không có thẻ đóng {{/foreach}}
            $path = $matches[1];
            if ( strpos( $block_content, '<select' ) !== false ) {
                $template = '<option value="{{value}}">{{label}}</option>';
            } else {
                if ( preg_match( '/(<label[^>]*>.*?<\/label>)/is', $block_content, $label_matches ) ) {
                    $template = $label_matches[1];
                    $template = preg_replace( '/(value=")[^"]*(")/i', '${1}{{value}}${2}', $template );
                    $template = preg_replace( '/(<span>).*?(<\/span>)/i', '${1}{{label}}${2}', $template );
                } else {
                    $template = '<option value="{{value}}">{{label}}</option>';
                }
            }
        }

        if ( $path && $template ) {
            
            // Hỗ trợ cả `schema.table_name.column_name` và `table_name.column_name`
            $parts = explode( '.', str_replace( 'schema.', '', $path ) );
            if ( count( $parts ) >= 2 ) {
                $table_name = $parts[0];
                $column_name = $parts[1];
                
                global $wpdb;
                $all_dict = get_option('skaaa_data_dictionary', array());
                
                // Chuẩn hóa tên bảng: Thêm `skaaa_data_` nếu thiếu
                $real_table_name = $table_name;
                if ( strpos( $real_table_name, 'skaaa_data_' ) !== 0 && strpos( $real_table_name, $wpdb->prefix . 'skaaa_data_' ) !== 0 ) {
                    $real_table_name = 'skaaa_data_' . $real_table_name;
                }
                // Thêm `wp_` prefix (hoặc prefix hiện tại) nếu thiếu
                if ( strpos( $real_table_name, $wpdb->prefix ) !== 0 ) {
                    $real_table_name = $wpdb->prefix . $real_table_name;
                }

                $col_config = isset( $all_dict[ $real_table_name ][ $column_name ] ) ? $all_dict[ $real_table_name ][ $column_name ] : array();
                $col_type = isset( $col_config['type'] ) ? $col_config['type'] : '';

                $target_table = '';
                if ( $col_type === 'relation' ) {
                    $target_table = isset( $col_config['options'] ) ? $col_config['options'] : '';
                } elseif ( $column_name === 'id' ) {
                    // Nếu binding là {{#foreach target_table.id}} thì target_table chính là bảng đích này
                    $target_table = $real_table_name;
                }

                if ( ! empty( $target_table ) ) {
                    $id_col = 'id';
                    $display_col = 'id';
                    
                    if ( $target_table === $wpdb->posts ) {
                        $id_col = 'ID';
                        $display_col = 'post_title';
                    } elseif ( $target_table === $wpdb->users ) {
                        $id_col = 'ID';
                        $display_col = 'display_name';
                    } else {
                        // Thử DESCRIBE bảng để tìm cột hiển thị (varchar/text đầu tiên)
                        $columns = $wpdb->get_results( "DESCRIBE `{$target_table}`" );
                        if ( $columns ) {
                            foreach ( $columns as $col ) {
                                if ( strpos( strtolower( $col->Type ), 'varchar' ) !== false || strpos( strtolower( $col->Type ), 'text' ) !== false ) {
                                    $display_col = $col->Field;
                                    break;
                                }
                            }
                        }
                    }

                    $is_core_wp_table = ( $target_table === $wpdb->posts || $target_table === $wpdb->users );
                    if ( $is_core_wp_table || strpos( $target_table, $wpdb->prefix ) === 0 ) {
                        $results = $wpdb->get_results( "SELECT `{$id_col}` as id, `{$display_col}` as label FROM `{$target_table}` LIMIT 1000", ARRAY_A );
                        
                        $generated_html = '';
                        if ( ! empty( $results ) && is_array( $results ) ) {
                            foreach ( $results as $row ) {
                                $row_html = str_replace( 
                                    array( '{{value}}', '{{label}}', '{{.}}' ), 
                                    array( esc_attr( $row['id'] ), esc_html( $row['label'] ), esc_html( $row['label'] ) ), 
                                    $template 
                                );
                                $generated_html .= $row_html;
                            }
                        }
                        
                        if ( strpos( $block_content, '<select' ) !== false ) {
                            $block_content = preg_replace( '/(<select[^>]*>)(.*?)(<\/select>)/is', '${1}' . $generated_html . '${3}', $block_content );
                        } else {
                            $block_content = preg_replace( '/(<div[^>]*>)(.*?)(<\/div>)/is', '${1}' . $generated_html . '${3}', $block_content );
                        }
                    }
                } elseif ( isset( $col_config['options'] ) ) {
                    $options_str = $col_config['options'];
                    $options_arr = array_map( 'trim', explode( ',', $options_str ) );
                    
                    $generated_html = '';
                    foreach ( $options_arr as $opt ) {
                        $row_html = str_replace( array( '{{.}}', '{{value}}', '{{label}}' ), esc_attr( $opt ), $template );
                        $generated_html .= $row_html;
                    }
                    
                    if ( strpos( $block_content, '<select' ) !== false ) {
                        $block_content = preg_replace( '/(<select[^>]*>)(.*?)(<\/select>)/is', '${1}' . $generated_html . '${3}', $block_content );
                    } else {
                        $block_content = preg_replace( '/(<div[^>]*>)(.*?)(<\/div>)/is', '${1}' . $generated_html . '${3}', $block_content );
                    }
                }
            }
        }
        return $block_content;
    }

    /**
     * Thuật toán Máy dò & Máy chém Frontend
     */
    public function parse_dynamic_tags( $content ) {
        // Tránh chạy trong khu vực wp-admin hoặc lúc truy vấn loop phụ
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $content;
        }
        
        // Thoát sớm để tối ưu hiệu năng nếu không hề tìm thấy dấu hiệu liên kết động
        if ( strpos( $content, '{{' ) === false ) {
            return $content;
        }

        // Nâng cấp Regex để hỗ trợ linh hoạt 1 phần, 2 phần, hoặc 3 phần
        // Chấp nhận: {{name}}, {{doctors.name}}, {{app.doctors.name}}
        $pattern = '/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/';
        
        // Gọi callback để tráo dòng
        $content = preg_replace_callback( $pattern, [ $this, 'hydration_callback' ], $content );

        return $content;
    }

    /**
     * Dịch từng thẻ Template Tag thành giá trị thực
     */
    private function hydration_callback( $matches ) {
        $full_tag     = $matches[0]; // Ký tự đầy đủ {{...}}
        $var_name     = trim( $matches[1] );

        // Lấy con trỏ ngữ cảnh
        $record_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $table_alias = isset( $_GET['table'] ) ? sanitize_text_field( $_GET['table'] ) : '';

        // Nếu không có mốc tham lượng, trả về chữ gốc chứ không báo lỗi làm gãy font của frontend
        if ( empty( $record_id ) ) {
            return $full_tag; 
        }

        if ( ! class_exists( '\Skaaa\Logic\SkaaaFX\SkaaaFX_Context_Resolver' ) ) {
            return $full_tag; // Chưa nạp file hoặc hệ thống chưa sẵn sàng
        }

        $context = [
            'GLOBAL_ID'    => $record_id,
            'GLOBAL_TABLE' => $table_alias
        ];

        try {
            // Mượn Cỗ máy Resolver mới để phân giải
            $resolved_context = \Skaaa\Logic\SkaaaFX\SkaaaFX_Context_Resolver::resolve( $var_name, $context );
        } catch ( \Exception $e ) {
            // NẾU CÓ XUNG ĐỘT (Collision), VÀ LÀ ADMIN ĐANG XEM THÌ BÁO CHỮ ĐỎ
            $dev_mode = get_option( 'skaaa_system_dev_mode', '1' );
            if ( current_user_can('manage_options') && $dev_mode === '1' ) {
                return '<span style="color:red; font-weight:bold; background:#fee2e2; padding:2px 4px; border-radius:4px; font-size:12px;">[' . esc_html($e->getMessage()) . ']</span>';
            }
            return ''; // Khách ngoài hoặc đã tắt Dev Mode thì nuốt lỗi giấu đi
        }

        if ( ! $resolved_context ) {
            return $full_tag; // Không giải mã được (ví dụ do thiếu _GET['table'] cho biến Mức 1)
        }

        $table_name  = $resolved_context['table_name'];
        $column_name = $resolved_context['column'];

        // Kiểm tra RAM
        $cache_key = $table_name . '_' . $record_id;
        
        if ( ! isset( $this->memory_cache[ $cache_key ] ) ) {
            // Móc sang Data Pro nếu tồn tại Cỗ máy lấy dữ liệu
            if ( class_exists( '\Skaaa\Data\Core\Data_Fetcher' ) ) {
                $args = [
                    'filter_field' => 'id',
                    'filter_val'   => $record_id,
                    'filter_op'    => 'eq'
                ];
                
                // Fetch đúng 1 row
                $rows = \Skaaa\Data\Core\Data_Fetcher::get_table_rows( $table_name, $args, 1 );
                
                if ( ! empty( $rows ) && is_array( $rows ) ) {
                    $this->memory_cache[ $cache_key ] = $rows[0]; // Lưu túi thần cất xài dần
                } else {
                    $this->memory_cache[ $cache_key ] = false; // Đánh dấu là NULL để khỏi Query DB lại
                }
            } else {
                // Skaaa Data Pro chưa kích hoạt
                return $full_tag;
            }
        }

        // Đọc RAM
        $record_data = $this->memory_cache[ $cache_key ];

        // Nếu DB không có bản ghi đó
        if ( empty( $record_data ) ) {
            return ''; 
        }

        // Trả kết quả nếu Tên cột khớp. Nếu không thì rỗng.
        return isset( $record_data[ $column_name ] ) ? esc_html( (string) $record_data[ $column_name ] ) : '';
    }
}
