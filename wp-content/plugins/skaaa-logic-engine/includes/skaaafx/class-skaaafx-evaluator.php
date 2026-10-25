<?php
namespace Skaaa\Logic\SkaaaFX;

defined( 'ABSPATH' ) || exit;

/**
 * Trạm 3: The Evaluator (Cỗ Máy Phán Xét & Tính Toán)
 * Duyệt cây AST và thực thi kết quả thực tế.
 */
class SkaaaFX_Evaluator {
    
    // Kho chứa biến tạm khi Lễ tân xài `var x = 1`
    private $symbol_table = []; 
    
    // Kho chứa dữ liệu Bảng Phẳng / Context truyền vào (VD: ['rating' => 4])
    private $row_context = [];  

    public function __construct( array $row_context = [] ) {
        $this->row_context = $row_context;
    }

    /**
     * Chạy luồng Kịch bản (Nhiều statements)
     */
    public function evaluate_script( $ast_statements ) {
        $last_value = null;
        
        foreach ( $ast_statements as $node ) {
            $last_value = $this->evaluate( $node );
        }
        
        return $last_value;
    }

    /**
     * Tính toán Đệ quy trên từng Nhánh cây
     */
    private function evaluate( SkaaaFX_AST_Node $node ) {
        
        // 1. Phân loại Giá trị Đơn (Nguyên thủy)
        if ( $node instanceof Node_Number ) {
            return $node->value;
        }

        if ( $node instanceof Node_String ) {
            return $node->value;
        }

        // 2. Chế độ Lưu Biến Tạm (Memory Assignment)
        if ( $node instanceof Node_Assign ) {
            $val = $this->evaluate( $node->expression );
            $this->symbol_table[ $node->var_name ] = $val;
            return $val;
        }

        // 3. Chế độ Móc Biến / Móc Context
        if ( $node instanceof Node_Variable ) {
            $var_name = $node->name;

            // Bắt Literals (Hằng số Boolean/Null)
            $lower_name = strtolower( $var_name );
            if ( $lower_name === 'true' ) return true;
            if ( $lower_name === 'false' ) return false;
            if ( $lower_name === 'null' ) return null;

            // Ưu tiên 0: Phân giải biến ngữ cảnh WordPress Core (post.*, wp.*, user.*, site.*)
            $wp_val = $this->resolve_wp_variable( $var_name );
            if ( $wp_val !== null ) {
                return $wp_val;
            }

            // UX Cải tiến: Nếu user gõ `payload.xyz`, ta tự động cắt bỏ chữ `payload.` đi
            // vì $this->row_context vốn dĩ chính là payload rồi.
            if ( strpos( $var_name, 'payload.' ) === 0 ) {
                $var_name = substr( $var_name, 8 );
            }

            // Thiết lập danh sách các tên biến cần thử tìm kiếm (bao gồm cả fallback của render_template)
            $var_names_to_try = [ $var_name ];
            if ( $var_name === 'render_template' ) {
                $var_names_to_try[] = 'rendered_template';
            } elseif ( $var_name === 'rendered_template' ) {
                $var_names_to_try[] = 'render_template';
            }

            foreach ( $var_names_to_try as $try_name ) {
                // Ưu tiên 1: Quét biến ảo trong RAM (VD: Lễ tân vừa gán `var tuoi = 18`)
                if ( array_key_exists( $try_name, $this->symbol_table ) ) {
                    return $this->symbol_table[ $try_name ];
                }
                foreach ( $this->symbol_table as $k => $v ) {
                    if ( strtolower( $k ) === strtolower( $try_name ) ) return $v;
                }

                // Ưu tiên 2: Quét mảng dữ liệu Dòng nội khu (Tự nhận diện `[nam_kinh_nghiem]`)
                if ( array_key_exists( $try_name, $this->row_context ) ) {
                    return $this->row_context[ $try_name ];
                }
                foreach ( $this->row_context as $k => $v ) {
                    if ( strtolower( $k ) === strtolower( $try_name ) ) return $v;
                }

                // Hỗ trợ quét trong sub-key 'payload' nếu đang test bằng mock payload bọc ngoài
                if ( isset( $this->row_context['payload'] ) && is_array( $this->row_context['payload'] ) ) {
                    if ( array_key_exists( $try_name, $this->row_context['payload'] ) ) {
                        return $this->row_context['payload'][ $try_name ];
                    }
                    foreach ( $this->row_context['payload'] as $k => $v ) {
                        if ( strtolower( $k ) === strtolower( $try_name ) ) return $v;
                    }
                }
            }

            // Ưu tiên 2.5: Quét mảng dữ liệu Dòng nội khu hỗ trợ Dot Notation (VD: `trigger.nam_sinh` hoặc `form.ten`)
            if ( strpos( $var_name, '.' ) !== false ) {
                $keys = explode( '.', $var_name );
                $current = $this->row_context;
                if ( ! array_key_exists( $keys[0], $current ) && isset( $current['payload'] ) && is_array( $current['payload'] ) ) {
                    if ( $keys[0] !== 'payload' ) {
                        $current = $current['payload'];
                    }
                }
                $found = true;
                foreach ( $keys as $key ) {
                    // Cải tiến: Nếu là chuỗi JSON, tự động decode để truy cập sâu
                    if ( is_string( $current ) && (strpos($current, '{') === 0 || strpos($current, '[') === 0) ) {
                        $decoded = json_decode( $current, true );
                        if ( json_last_error() === JSON_ERROR_NONE ) {
                            $current = $decoded;
                        }
                    }

                    if ( is_array( $current ) ) {
                        if ( array_key_exists( $key, $current ) ) {
                            $current = $current[ $key ];
                        } else {
                            // Case-insensitive fallback
                            $found_key = false;
                            foreach ( $current as $k => $v ) {
                                if ( strtolower( $k ) === strtolower( $key ) ) {
                                    $current = $v;
                                    $found_key = true;
                                    break;
                                }
                            }
                            if ( ! $found_key ) {
                                if ( $key === 'length' ) {
                                    $current = count( $current );
                                } else {
                                    $found = false;
                                    break;
                                }
                            }
                        }
                    } elseif ( $key === 'length' && (is_array( $current ) || $current instanceof \Countable) ) {
                        $current = count( $current );
                    } elseif ( $key === 'length' && is_string( $current ) ) {
                        $current = mb_strlen( $current );
                    } else {
                        $found = false;
                        break;
                    }
                }
                if ( $found ) {
                    return $current;
                }
            }

            // Ưu tiên 3: Quét chéo Object (Smart Object / App Context) bằng Context_Resolver
            // Hỗ trợ cả 3 dạng: [app.model.col], [model.col], và [col]
            $resolved_context = null;
            try {
                $resolved_context = SkaaaFX_Context_Resolver::resolve( $var_name, $this->row_context );
            } catch ( \Exception $e ) {
                throw new SkaaaFX_Runtime_Error( $e->getMessage() );
            }

            if ( $resolved_context ) {
                $table_name = $resolved_context['table_name'];
                $col        = $resolved_context['column'];
                $record_id  = $resolved_context['record_id'];

                // Static Cache dùng chung cho cả vòng đời Render 1 trang
                static $smart_object_cache = [];
                $cache_key = $table_name . '_' . $record_id;

                if ( ! isset( $smart_object_cache[ $cache_key ] ) ) {
                    if ( class_exists( '\Skaaa\Data\Core\Data_Fetcher' ) ) {
                        $rows = \Skaaa\Data\Core\Data_Fetcher::get_table_rows( $table_name, [
                            'filter_field' => 'id',
                            'filter_val'   => $record_id,
                            'filter_op'    => 'eq'
                        ], 1 );
                        
                        if ( ! empty( $rows ) && is_array( $rows ) ) {
                            $smart_object_cache[ $cache_key ] = $rows[0];
                        } else {
                            $smart_object_cache[ $cache_key ] = false;
                        }
                    } else {
                        $smart_object_cache[ $cache_key ] = false;
                    }
                }

                $record_data = $smart_object_cache[ $cache_key ];
                if ( $record_data !== false && isset( $record_data[ $col ] ) ) {
                    return $record_data[ $col ];
                }
            }

            // Nuốt lỗi an toàn: Trả NULL thay vì ném Lỗi để tránh Error 500 nếu cột bay màu.
            return null; 
        }

        // 4. Lõi Tính Toán Toán Học & Logic
        if ( $node instanceof Node_BinaryOp ) {
            $left = $this->evaluate( $node->left );
            $right = $this->evaluate( $node->right );

            switch ( $node->operator ) {
                case '+':  return (float)$left + (float)$right;
                case '-':  return (float)$left - (float)$right;
                case '*':  return (float)$left * (float)$right;
                // Chặn lỗi chia cho số 0
                case '/':  
                    $r = (float)$right;
                    return $r != 0 ? (float)$left / $r : 0; 
                // Cú pháp gõ 1 dấu = vẫn coi là phép so sánh theo chuẩn Nocode
                case '=':  
                case '==': return $left == $right; 
                case '!=': return $left != $right;
                case '>':  return $left > $right;
                case '<':  return $left < $right;
                case '>=': return $left >= $right;
                case '<=': return $left <= $right;
                case 'AND': return $left && $right;
                case 'OR':  return $left || $right;
            }
        }

        // 5. Ngân Hàng Hàm Nội Bộ (Built-in Functions)
        if ( $node instanceof Node_Function ) {
            $fn_name = $node->name;

            // Xử lý Short-circuit (Chặn sớm nhánh sai) cho riêng hàm IF để tránh lãng phí RAM
            if ( $fn_name === 'IF' ) {
                if ( count($node->args) < 3 ) throw new SkaaaFX_Runtime_Error(__( 'The IF function needs 3 parameters: IF(Condition, True, False)', 'skaaa-logic-engine' ));
                $condition = $this->evaluate( $node->args[0] );
                if ( $condition ) {
                    return $this->evaluate( $node->args[1] );
                } else {
                    return $this->evaluate( $node->args[2] );
                }
            }

            // Với các hàm khác, bóc vỏ Evaluate toàn bộ Biến
            $arg_vals = [];
            foreach ( $node->args as $arg ) {
                $arg_vals[] = $this->evaluate( $arg );
            }

            switch ( $fn_name ) {
                case 'CONCAT':
                    return implode( '', $arg_vals );
                case 'LOWER':
                    return strtolower( $arg_vals[0] );
                case 'UPPER':
                    return strtoupper( $arg_vals[0] );
                case 'ROUND':
                    $precision = isset( $arg_vals[1] ) ? $arg_vals[1] : 0;
                    return round( $arg_vals[0], $precision );
                case 'IS_NULL':
                    return is_null( $arg_vals[0] );
                case 'LIST_COL':
                    // Cú pháp: LIST_COL( array_data, 'column_name', 'separator' )
                    $arr = $arg_vals[0];
                    if ( ! is_array( $arr ) ) return '';
                    $col_name = isset( $arg_vals[1] ) ? $arg_vals[1] : 'id';
                    $separator = isset( $arg_vals[2] ) ? $arg_vals[2] : ', ';
                    $plucked = array_column( $arr, $col_name );
                    return implode( $separator, $plucked );
                default:
                    throw new SkaaaFX_Runtime_Error("Hàm không tồn tại trong từ điển SkaaaFX: " . $fn_name);
            }
        }

        throw new SkaaaFX_Runtime_Error(__( 'The machine cannot recognize the Node type: Breaking the AST architecture.', 'skaaa-logic-engine' ));
    }
    /**
     * Phân giải biến ngữ cảnh WordPress Core (post.*, wp.*, user.*, site.*)
     * An toàn framework-agnostic: chỉ kích hoạt khi có môi trường WordPress.
     */
    private function resolve_wp_variable( $var_name ) {
        if ( ! function_exists( 'get_the_ID' ) && ! function_exists( 'get_bloginfo' ) ) {
            return null;
        }

        $lower = strtolower( $var_name );

        // Chuẩn hóa tiền tố wp. thành post. (ví dụ: wp.title -> post.title)
        if ( strpos( $lower, 'wp.' ) === 0 ) {
            $lower = 'post.' . substr( $lower, 3 );
        }

        // 1. Ngữ cảnh Website (site.title, site.url, site.description)
        if ( $lower === 'site.title' && function_exists( 'get_bloginfo' ) ) {
            return get_bloginfo( 'name' );
        }
        if ( $lower === 'site.description' && function_exists( 'get_bloginfo' ) ) {
            return get_bloginfo( 'description' );
        }
        if ( $lower === 'site.url' && function_exists( 'home_url' ) ) {
            return home_url();
        }

        // 2. Ngữ cảnh Người dùng hiện tại (user.name, user.email, user.avatar, user.id)
        if ( strpos( $lower, 'user.' ) === 0 && function_exists( 'wp_get_current_user' ) ) {
            $current_user = wp_get_current_user();
            if ( $lower === 'user.name' || $lower === 'user.display_name' ) {
                return $current_user && $current_user->exists() ? $current_user->display_name : '';
            }
            if ( $lower === 'user.email' ) {
                return $current_user && $current_user->exists() ? $current_user->user_email : '';
            }
            if ( $lower === 'user.id' ) {
                return $current_user && $current_user->exists() ? $current_user->ID : 0;
            }
            if ( $lower === 'user.avatar' && function_exists( 'get_avatar_url' ) ) {
                return $current_user && $current_user->exists() ? get_avatar_url( $current_user->ID ) : '';
            }
        }

        // 3. Ngữ cảnh Bài viết WordPress (post.title, post.featured_image, post.author...)
        if ( strpos( $lower, 'post.' ) === 0 ) {
            $post_id = 0;
            if ( ! empty( $this->row_context['WP_POST_ID'] ) ) {
                $post_id = absint( $this->row_context['WP_POST_ID'] );
            } elseif ( function_exists( 'get_the_ID' ) && get_the_ID() ) {
                $post_id = get_the_ID();
            } elseif ( isset( $GLOBALS['post']->ID ) ) {
                $post_id = $GLOBALS['post']->ID;
            } elseif ( ! empty( $this->row_context['GLOBAL_ID'] ) ) {
                $post_id = absint( $this->row_context['GLOBAL_ID'] );
            } elseif ( isset( $_GET['id'] ) ) {
                $post_id = absint( $_GET['id'] );
            }

            if ( ! $post_id ) {
                return '';
            }

            $post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;

            switch ( $lower ) {
                case 'post.title':
                    return function_exists( 'get_the_title' ) ? get_the_title( $post_id ) : ( $post ? $post->post_title : '' );

                case 'post.featured_image':
                case 'post.thumbnail':
                case 'post.image':
                    return function_exists( 'get_the_post_thumbnail_url' ) ? ( get_the_post_thumbnail_url( $post_id, 'full' ) ?: '' ) : '';

                case 'post.author':
                    if ( $post && ! empty( $post->post_author ) && function_exists( 'get_the_author_meta' ) ) {
                        return get_the_author_meta( 'display_name', $post->post_author );
                    }
                    return function_exists( 'get_the_author' ) ? get_the_author() : '';

                case 'post.author_avatar':
                    if ( $post && ! empty( $post->post_author ) && function_exists( 'get_avatar_url' ) ) {
                        return get_avatar_url( $post->post_author );
                    }
                    return '';

                case 'post.date':
                    return function_exists( 'get_the_date' ) ? get_the_date( '', $post_id ) : ( $post ? $post->post_date : '' );

                case 'post.modified_date':
                    return function_exists( 'get_the_modified_date' ) ? get_the_modified_date( '', $post_id ) : ( $post ? $post->post_modified : '' );

                case 'post.excerpt':
                    return function_exists( 'get_the_excerpt' ) ? get_the_excerpt( $post_id ) : ( $post ? $post->post_excerpt : '' );

                case 'post.permalink':
                case 'post.url':
                    return function_exists( 'get_permalink' ) ? get_permalink( $post_id ) : '';

                case 'post.id':
                    return $post_id;

                case 'post.content':
                    return $post ? $post->post_content : '';

                default:
                    // Custom fields: post.meta.<key>
                    if ( strpos( $lower, 'post.meta.' ) === 0 && function_exists( 'get_post_meta' ) ) {
                        $meta_key = substr( $var_name, 10 );
                        return get_post_meta( $post_id, $meta_key, true );
                    }
                    // Fallback trực tiếp nếu gõ post.<meta_key>
                    if ( function_exists( 'get_post_meta' ) ) {
                        $meta_key = substr( $var_name, 5 );
                        $val = get_post_meta( $post_id, $meta_key, true );
                        if ( $val !== '' && $val !== false && $val !== null ) {
                            return $val;
                        }
                    }
                    break;
            }
        }

        return null;
    }

    /**
     * Lấy toàn bộ biến bộ nhớ ra ngoài (Ví dụ: `data`, `visible`)
     */
    public function get_symbols() {
        return $this->symbol_table;
    }
}

/**
 * Controller Đại Diện: Tàu Cảng public đón Lệnh
 */
class SkaaaFX_Engine {
    
    /**
     * API Phóng tên lửa tính toán
     * @param string $script_string Chuỗi SkaaaFX người dùng gõ
     * @param array $context Mảng dữ liệu bơm vào vòng lặp
     * @return mixed Kết quả biểu thức hoặc Mảng Trạng Thái
     */
    public static function execute( $script_string, $context = [] ) {
        // Tắt early return khi chuỗi rỗng
        if ( trim( $script_string ) === '' ) {
            return [ 'last_val' => true, 'symbols' => [] ]; // Mặc định chuỗi rỗng mang ý nghĩa True
        }

        try {
            // Vận hành Dòng chuyền
            $lexer = new SkaaaFX_Lexer( $script_string );
            $tokens = $lexer->tokenize();
            
            $parser = new SkaaaFX_Parser( $tokens );
            $statements = $parser->parse();
            
            $evaluator = new SkaaaFX_Evaluator( $context );
            $last_val = $evaluator->evaluate_script( $statements );
            
            // Trả về Nguyên bộ Mảng Mạch (Scripting Mode)
            return [
                'last_val' => $last_val,
                'symbols'  => $evaluator->get_symbols()
            ];
            
        } catch ( SkaaaFX_Syntax_Error | SkaaaFX_Runtime_Error $e ) {
             // Đạt tiêu chuẩn Nuốt Lỗi: Ghi log, Không là sập web frontend.
             error_log("[SkaaaFX Engine Failure]: " . $e->getMessage());
             return [ 'last_val' => false, 'symbols' => [], 'error' => $e->getMessage() ];
        }
    }
}
