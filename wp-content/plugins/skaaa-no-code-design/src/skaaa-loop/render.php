<?php
/**
 * Render Callback cho Skaaa Loop Block
 * 
 * Kiến trúc: Siêu tốc (Zero N+1)
 * 1. Thu thập tất cả các ID của Organism từ các Slots
 * 2. Tải toàn bộ HTML của chúng lên RAM bằng 1 Query (Bulk Load)
 * 3. Biên dịch HTML thô thành Khuôn Mustache (Template Compilation)
 * 4. Truy vấn dữ liệu: Flat Table (Skaaa Data Pro) HOẶC WordPress Posts (WP_Query)
 * 5. Duyệt qua từng dòng dữ liệu, tính toán SkaaaFX để chọn Khuôn
 * 6. Hydrate (bơm) dữ liệu vào Khuôn Mustache siêu nhanh bằng chuỗi tĩnh
 * 7. Tích hợp Flat DOM Pagination hỗ trợ Alpine AJAX (0ms Instant Navigation)
 *
 * @package Skaaa_No_Code_Design
 */

defined( 'ABSPATH' ) || exit;

// Lấy thuộc tính cấu hình từ người dùng
$source_type          = isset( $attributes['sourceType'] ) ? sanitize_text_field( $attributes['sourceType'] ) : 'flat_table';
$source_table         = isset( $attributes['sourceTable'] ) ? sanitize_text_field( $attributes['sourceTable'] ) : '';
$post_type            = isset( $attributes['postType'] ) ? sanitize_text_field( $attributes['postType'] ) : 'post';
$category             = isset( $attributes['category'] ) ? sanitize_text_field( $attributes['category'] ) : '';
$limit                = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 10;
$slots                = isset( $attributes['slots'] ) && is_array( $attributes['slots'] ) ? $attributes['slots'] : [];
$enable_pagination    = ! empty( $attributes['enablePagination'] );
$pagination_target_id = isset( $attributes['paginationTargetId'] ) ? sanitize_html_class( $attributes['paginationTargetId'] ) : '';
$inherit_query        = ! empty( $attributes['inheritQuery'] );
$anchor               = isset( $attributes['anchor'] ) ? sanitize_html_class( $attributes['anchor'] ) : '';

// Kiểm tra tính hợp lệ nguồn dữ liệu Flat Table
if ( $source_type === 'flat_table' && empty( $source_table ) ) {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ) ) {
        echo '<div class="components-placeholder"><div class="components-placeholder__label">Skaaa Query Loop</div><div class="components-placeholder__fieldset">' . esc_html__( 'Please select Source Table in Inspector.', 'skaaa-no-code-design' ) . '</div></div>';
        return;
    }
    return;
}

// 1. Thu thập ID và Bulk Load Organisms
$organism_ids = [];
foreach ( $slots as $slot ) {
    if ( ! empty( $slot['organismId'] ) ) {
        $organism_ids[] = absint( $slot['organismId'] );
    }
}

if ( empty( $organism_ids ) ) {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ) ) {
        echo '<div class="components-placeholder"><div class="components-placeholder__label">Skaaa Query Loop</div><div class="components-placeholder__fieldset">' . esc_html__( 'Please set up at least 1 Slot (Organism ID).', 'skaaa-no-code-design' ) . '</div></div>';
        return;
    }
    return;
}

// Lấy mảng [id => raw_html]
$bulk_html = \Skaaa\Design\Api\Organisms_API::get_bulk_html( $organism_ids );

// 2. Biên dịch (Compile) mã HTML tĩnh thành Khuôn Mustache {{key}}
$compiled_templates = [];

// Tắt bộ lọc chặn Frontend cũ để không chạy truy vấn dữ liệu thừa
if ( class_exists( '\Skaaa_Dynamic_Content' ) ) {
    remove_filter( 'render_block', [ \Skaaa_Dynamic_Content::instance(), 'parse_skaaa_blocks' ], 10 );
}

// Định nghĩa bộ lọc Compile Cục bộ
$compile_filter = function( $block_content, $block ) {
    if ( isset( $block['attrs']['skaaaDynamicBinding']['script'] ) && ! empty( $block['attrs']['skaaaDynamicBinding']['script'] ) ) {
        $script = $block['attrs']['skaaaDynamicBinding']['script'];
        
        // Trích xuất biến [table.column] hoặc [post.title]
        preg_match_all( '/\[([a-zA-Z0-9_\.]+)\]/', $script, $matches );
        if ( ! empty( $matches[1] ) ) {
            $var_name = $matches[1][0]; 
            
            // Đào lỗ vào Text Block (Hỗ trợ đa dạng block văn bản)
            $text_blocks = [ 'skaaaaa-builder/text', 'skaaaaa-builder/heading', 'core/paragraph', 'core/heading' ];
            if ( in_array( $block['blockName'], $text_blocks, true ) ) {
                $block_content = preg_replace( '/(<[a-zA-Z0-9]+[^>]*>)(.*?)(<\/[a-zA-Z0-9]+>\s*)$/s', '${1}{{' . $var_name . '}}${3}', $block_content );
            }
            
            // Đào lỗ cho Button / Link (href và label)
            $button_blocks = [ 'skaaaaa-builder/button', 'core/button' ];
            if ( in_array( $block['blockName'], $button_blocks, true ) ) {
                if ( strpos( $script, '"href"' ) !== false || strpos( $script, 'href' ) !== false || strpos( $script, 'permalink' ) !== false || strpos( $script, 'url' ) !== false ) {
                    $block_content = preg_replace( '/href="([^"]*)"/', 'href="{{' . $var_name . '}}"', $block_content );
                } else {
                    $block_content = preg_replace( '/(<[a-zA-Z0-9]+[^>]*>)(.*?)(<\/[a-zA-Z0-9]+>\s*)$/s', '${1}{{' . $var_name . '}}${3}', $block_content );
                }
            }

            // Đào lỗ cho Image (src) và loại bỏ srcset cũ
            $image_blocks = [ 'skaaaaa-builder/image', 'core/image' ];
            if ( in_array( $block['blockName'], $image_blocks, true ) ) {
                $block_content = preg_replace( '/src="([^"]*)"/', 'src="{{' . $var_name . '}}"', $block_content );
                $block_content = preg_replace( '/\s+(srcset|sizes)="([^"]*)"/', '', $block_content );
            }
        }
    }
    return $block_content;
};

// Bật bộ lọc Compile
add_filter( 'render_block', $compile_filter, 10, 2 );

global $skaaa_rendering_organisms;
if ( ! isset( $skaaa_rendering_organisms ) ) {
    $skaaa_rendering_organisms = [];
}

foreach ( $bulk_html as $org_id => $raw_html ) {
    if ( isset( $skaaa_rendering_organisms[ $org_id ] ) ) {
        $compiled_templates[ $org_id ] = '<div style="color:red; padding:10px; border:1px solid red;">' . esc_html__( 'Error: Infinite Loop detected at Symbol/Organism ID:', 'skaaa-no-code-design' ) . ' ' . esc_html( $org_id ) . '</div>';
        continue;
    }
    
    $skaaa_rendering_organisms[ $org_id ] = true;
    
    $blocks = parse_blocks( $raw_html );
    $html_output = '';
    foreach ( $blocks as $b ) {
        $html_output .= render_block( $b );
    }
    
    // Áp dụng resolve_inline_links cho toàn bộ HTML của template
    if ( class_exists( '\Skaaa\Builder\Utils\Dynamic_Data' ) ) {
        $html_output = \Skaaa\Builder\Utils\Dynamic_Data::resolve_inline_links( $html_output );
    }
    
    $compiled_templates[ $org_id ] = $html_output;
    
    unset( $skaaa_rendering_organisms[ $org_id ] );
}

if ( empty( $compiled_templates ) ) {
    return;
}

// Tắt bộ lọc Compile, bật lại bộ lọc cũ
remove_filter( 'render_block', $compile_filter, 10 );
if ( class_exists( '\Skaaa_Dynamic_Content' ) ) {
    add_filter( 'render_block', [ \Skaaa_Dynamic_Content::instance(), 'parse_skaaa_blocks' ], 10, 2 );
}

// 3. Tính toán Phân Trang Hiện Tại
$paged = 1;
if ( $enable_pagination ) {
    $paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : ( get_query_var( 'page' ) ? absint( get_query_var( 'page' ) ) : 1 );
    if ( $paged < 1 ) {
        $paged = 1;
    }
}
$max_pages = 1;
$rows      = [];
$actual_table_name = '';

// 4. Lấy Dữ Liệu Thực Tế
if ( $source_type === 'wp_posts' ) {
    // 4.1. NGUỒN: WORDPRESS POSTS (WP_Query)
    $query_args = [
        'post_type'           => ! empty( $post_type ) ? sanitize_key( $post_type ) : 'post',
        'posts_per_page'      => $limit > 0 ? $limit : 10,
        'post_status'         => 'publish',
        'paged'               => $paged,
        'ignore_sticky_posts' => true,
    ];

    if ( ! empty( $category ) ) {
        if ( is_numeric( $category ) ) {
            $query_args['cat'] = absint( $category );
        } else {
            $query_args['category_name'] = sanitize_title( $category );
        }
    }

    if ( $inherit_query && ! is_admin() ) {
        global $wp_query;
        if ( isset( $wp_query ) && ( $wp_query->is_archive() || $wp_query->is_search() || $wp_query->is_home() ) ) {
            $query_args = array_merge( $wp_query->query_vars, $query_args );
        }
    }

    $posts_query = new \WP_Query( $query_args );
    $max_pages   = $posts_query->max_num_pages ? (int) $posts_query->max_num_pages : 1;

    if ( $posts_query->have_posts() ) {
        foreach ( $posts_query->posts as $p ) {
            $p_id      = $p->ID;
            $thumb_url = get_the_post_thumbnail_url( $p, 'large' );
            if ( ! $thumb_url ) {
                $thumb_url = '';
            }

            $cats       = wp_get_post_categories( $p_id, [ 'fields' => 'names' ] );
            $cat_string = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? implode( ', ', $cats ) : '';

            $post_data = [
                'id'                 => $p_id,
                'ID'                 => $p_id,
                'title'              => get_the_title( $p ),
                'post_title'         => get_the_title( $p ),
                'permalink'          => get_permalink( $p ),
                'url'                => get_permalink( $p ),
                'link'               => get_permalink( $p ),
                'date'               => get_the_date( '', $p ),
                'post_date'          => get_the_date( '', $p ),
                'excerpt'            => wp_strip_all_tags( get_the_excerpt( $p ) ),
                'post_excerpt'       => wp_strip_all_tags( get_the_excerpt( $p ) ),
                'content'            => $p->post_content,
                'post_content'       => $p->post_content,
                'author'             => get_the_author_meta( 'display_name', $p->post_author ),
                'featured_image'     => $thumb_url,
                'featured_image_url' => $thumb_url,
                'thumbnail'          => $thumb_url,
                'categories'         => $cat_string,
                'category'           => $cat_string,
            ];

            // Cung cấp nested objects để khớp [post.title] hay [wp_posts.featured_image]
            $post_data['post']     = $post_data;
            $post_data['wp_posts'] = $post_data;

            $rows[] = $post_data;
        }
        wp_reset_postdata();
    }
} else {
    // 4.2. NGUỒN: FLAT TABLE (Skaaa Data Pro)
    if ( ! class_exists( '\Skaaa\Data\Core\Data_Fetcher' ) ) {
        echo '<div class="components-placeholder"><div class="components-placeholder__label">Skaaa Query Loop</div><div class="components-placeholder__fieldset">' . esc_html__( 'Error: Skaaa Data Pro has not been activated.', 'skaaa-no-code-design' ) . '</div></div>';
        return;
    }

    global $wpdb;
    $actual_table_name = $source_table;
    $table_suffix      = '';

    if ( preg_match( '/(?:^|_)skaaa_data_(.+)$/', $source_table, $matches ) ) {
        $table_suffix      = $matches[1];
        $actual_table_name = $wpdb->prefix . 'skaaa_data_' . $table_suffix;
    } elseif ( strpos( $actual_table_name, $wpdb->prefix . 'skaaa_data_' ) !== 0 ) {
        $clean_suffix      = preg_replace( '/^[a-zA-Z0-9]+_skaaa_data_/', '', $actual_table_name );
        $clean_suffix      = preg_replace( '/^skaaa_data_/', '', $clean_suffix );
        $table_suffix      = ltrim( $clean_suffix, '_' );
        $actual_table_name = $wpdb->prefix . 'skaaa_data_' . $table_suffix;
    } else {
        $table_suffix      = str_replace( $wpdb->prefix . 'skaaa_data_', '', $actual_table_name );
    }

    $args = [];
    if ( $enable_pagination ) {
        $args['page'] = $paged;
    }

    if ( ! empty( $attributes['filters'] ) && is_array( $attributes['filters'] ) ) {
        $first_filter = $attributes['filters'][0];
        if ( ! empty( $first_filter['column'] ) && isset( $first_filter['value'] ) ) {
            $filter_val = $first_filter['value'];
            
            // Parse {url:id} context
            if ( preg_match( '/\{url:([a-zA-Z0-9_]+)\}/', $filter_val, $matches ) ) {
                $url_param = $matches[1];
                if ( $url_param === 'id' && ( get_query_var( 'skaaa_id' ) || get_query_var( 'app_record_id' ) ) ) {
                    $filter_val = get_query_var( 'skaaa_id' ) ? get_query_var( 'skaaa_id' ) : get_query_var( 'app_record_id' );
                } else {
                    $filter_val = isset( $_GET[ $url_param ] ) ? sanitize_text_field( $_GET[ $url_param ] ) : '';
                }
            }
            
            $args['filter_field'] = $first_filter['column'];
            $args['filter_op']    = $first_filter['operator'] ?? 'eq';
            $args['filter_val']   = $filter_val;
        }
    }

    $rows = \Skaaa\Data\Core\Data_Fetcher::get_table_rows( $actual_table_name, $args, $limit );

    if ( $enable_pagination && method_exists( '\Skaaa\Data\Core\Data_Fetcher', 'count_table_rows' ) ) {
        $total_items = \Skaaa\Data\Core\Data_Fetcher::count_table_rows( $actual_table_name, $args );
        $max_pages   = max( 1, (int) ceil( $total_items / ( $limit > 0 ? $limit : 10 ) ) );
    }
}

// Xử lý khi không có dữ liệu
if ( empty( $rows ) ) {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ) ) {
        $label = ( $source_type === 'wp_posts' ) ? 'WordPress Posts' : $actual_table_name;
        echo '<div class="components-placeholder"><div class="components-placeholder__label">Skaaa Query Loop</div><div class="components-placeholder__fieldset">' . sprintf( esc_html__( 'No records found for "%s".', 'skaaa-no-code-design' ), esc_html( $label ) ) . '</div></div>';
        return;
    }
    echo '<!-- Skaaa Loop: No data found -->';
    return;
}

// 5. Vòng Lặp Phân Giải Siêu Tốc (Hydration Loop)
$final_html = '';
$total_rows = count( $rows );

foreach ( $rows as $index => $row ) {
    // Bơm các biến Hệ thống vào Context
    $context = array_merge( $row, [
        '$index' => $index,
        '$first' => $index === 0,
        '$last'  => $index === ( $total_rows - 1 ),
        '$even'  => ( $index % 2 ) === 0,
        '$odd'   => ( $index % 2 ) !== 0,
        '$item'  => $row,
    ] );

    if ( $source_type === 'wp_posts' ) {
        $context['post']     = $row;
        $context['wp_posts'] = $row;
    } else {
        $context[ $source_table ]      = $row;
        $context[ $actual_table_name ] = $row;
        if ( ! empty( $table_suffix ) ) {
            $context[ $table_suffix ]                 = $row;
            $context[ 'skaaa_data_' . $table_suffix ] = $row;
        }
    }

    // Duyệt qua các slot để Match điều kiện
    $matched_template_html = '';
    
    foreach ( $slots as $slot ) {
        if ( empty( $slot['organismId'] ) ) {
            continue;
        }
        
        $org_id = absint( $slot['organismId'] );
        if ( ! isset( $compiled_templates[ $org_id ] ) ) {
            continue;
        }

        $condition = trim( $slot['condition'] ?? '' );
        
        // Nếu không có condition, coi như Default Slot (luôn khớp)
        if ( empty( $condition ) || $condition === 'default' ) {
            $matched_template_html = $compiled_templates[ $org_id ];
            break;
        }

        // Tính toán bằng SkaaaFX
        if ( class_exists( '\Skaaa\Logic\SkaaaFX\SkaaaFX_Engine' ) ) {
            try {
                $result = \Skaaa\Logic\SkaaaFX\SkaaaFX_Engine::execute( $condition, $context );
                $is_truthy = false;
                if ( isset( $result['last_val'] ) ) {
                    $val = $result['last_val'];
                    if ( $val === true || $val === 1 || $val === '1' || strtolower( (string) $val ) === 'true' ) {
                        $is_truthy = true;
                    }
                }
                
                if ( $is_truthy ) {
                    $matched_template_html = $compiled_templates[ $org_id ];
                    break;
                }
            } catch ( \Exception $e ) {
                // Bỏ qua lỗi cú pháp để loop chạy mượt
            }
        }
    }

    // Cắm dữ liệu (Hydration) vào Khuôn
    if ( ! empty( $matched_template_html ) ) {
        $hydrated_html = preg_replace_callback( '/\{\{\s*([a-zA-Z0-9_\.\$\-]+)\s*\}\}|\[\s*([a-zA-Z0-9_\.\$\-]+)\s*\]|%7B%7B\s*([a-zA-Z0-9_\.\$\-]+)\s*%7D%7D|%5B\s*([a-zA-Z0-9_\.\$\-]+)\s*%5D/', function( $matches ) use ( $context, $actual_table_name ) {
            $raw_key = '';
            for ( $i = 1; $i <= 4; $i++ ) {
                if ( ! empty( $matches[ $i ] ) ) {
                    $raw_key = trim( $matches[ $i ] );
                    break;
                }
            }

            if ( empty( $raw_key ) ) {
                return $matches[0];
            }
            
            $parts = explode( '.', $raw_key );
            
            // Xử lý Dynamic Link cho cột Relation (vd: teacher_id.url) trong Flat Table
            if ( count( $parts ) > 1 && end( $parts ) === 'url' && ! empty( $actual_table_name ) ) {
                $field_name = $parts[ count( $parts ) - 2 ];
                if ( isset( $context[ $field_name ] ) && is_string( $context[ $field_name ] ) ) {
                    $rel_val = $context[ $field_name ];
                    $decoded = json_decode( $rel_val, true );
                    if ( is_array( $decoded ) && ! empty( $decoded[0]['id'] ) ) {
                        if ( ! empty( $decoded[0]['url'] ) ) {
                            return esc_url( $decoded[0]['url'] );
                        }
                        
                        $target_id = $decoded[0]['id'];
                        $dict      = get_option( 'skaaa_data_dictionary', [] );
                        if ( isset( $dict[ $actual_table_name ][ $field_name ]['options'] ) ) {
                            $target_table = $dict[ $actual_table_name ][ $field_name ]['options'];
                            if ( isset( $dict[ $target_table ]['__table_info']['portal_settings']['slug'] ) && ! empty( $dict[ $target_table ]['__table_info']['portal_settings']['active'] ) ) {
                                $slug = $dict[ $target_table ]['__table_info']['portal_settings']['slug'];
                                return esc_url( home_url( '/' . ltrim( $slug, '/' ) . '/' . $target_id . '/' ) );
                            }
                        }
                        return esc_url( home_url( '/?p=' . $target_id ) );
                    }
                }
            }
            
            // 2. Tìm giá trị trong context
            $val = null;
            // 2.1. Tìm trực tiếp theo $raw_key (vd: 'post.title', 'title')
            if ( array_key_exists( $raw_key, $context ) ) {
                $val = $context[ $raw_key ];
            }
            // 2.2. Nếu có dấu chấm (vd: post.title -> $context['post']['title'])
            elseif ( count( $parts ) > 1 ) {
                $curr  = $context;
                $found = true;
                foreach ( $parts as $p_key ) {
                    if ( is_array( $curr ) && array_key_exists( $p_key, $curr ) ) {
                        $curr = $curr[ $p_key ];
                    } else {
                        $found = false;
                        break;
                    }
                }
                if ( $found ) {
                    $val = $curr;
                }
            }

            // 2.3. Fallback lấy phần tử cuối (vd: [table.column] -> 'column')
            if ( $val === null ) {
                $field_name = end( $parts );
                if ( array_key_exists( $field_name, $context ) ) {
                    $val = $context[ $field_name ];
                }
            }
            
            if ( $val !== null ) {
                if ( is_string( $val ) ) {
                    $val_clean = trim( stripslashes( $val ) );
                    if ( str_starts_with( $val_clean, '[{' ) ) {
                        $decoded = json_decode( $val_clean, true );
                        if ( is_array( $decoded ) ) {
                            $labels = array_column( $decoded, 'label' );
                            if ( ! empty( $labels ) ) {
                                return esc_html( implode( ', ', $labels ) );
                            }
                        }
                    }
                    if ( str_starts_with( $val, 'http://' ) || str_starts_with( $val, 'https://' ) || str_starts_with( $val, '/' ) ) {
                        return esc_url( $val );
                    }
                }

                // Nếu là ảnh đại diện nhưng rỗng, trả về ảnh placeholder sạch để không vỡ thẻ <img>
                $last_part = strtolower( end( $parts ) );
                if ( empty( $val ) && ( $last_part === 'featured_image' || $last_part === 'featured_image_url' || $last_part === 'thumbnail' ) ) {
                    return 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 600 400\'%3E%3Crect width=\'600\' height=\'400\' fill=\'%231e293b\'/%3E%3Cpath d=\'M260 180a20 20 0 1 0 40 0 20 20 0 1 0-40 0zm-40 80l40-50 30 35 45-60 65 75H220z\' fill=\'%23475569\'/%3E%3Ctext x=\'50%25\' y=\'78%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%2394a3b8\' font-family=\'system-ui,-apple-system,sans-serif\' font-size=\'14\'%3ENo Featured Image%3C/text%3E%3C/svg%3E';
                }

                return esc_html( (string) $val );
            }
            
            return $matches[0];
        }, $matched_template_html );

        $final_html .= $hydrated_html;
    }
}

/**
 * Hàm dựng Phân Trang Flat DOM tích hợp Alpine-AJAX
 *
 * @param int    $current_page Trang hiện tại.
 * @param int    $total_pages  Tổng số trang.
 * @param string $target_dom_id ID của container đích dùng cho x-target.push.
 * @param string $source_type  Nguồn dữ liệu ('wp_posts' hoặc 'flat_table').
 * @return string HTML phân trang Flat DOM chuẩn.
 */
if ( ! function_exists( 'skaaa_render_flat_pagination' ) ) {
    function skaaa_render_flat_pagination( $current_page, $total_pages, $target_dom_id, $source_type = 'wp_posts' ) {
        if ( $total_pages <= 1 ) {
            return '';
        }

        $get_url = function( $page_num ) use ( $source_type ) {
            if ( $source_type === 'flat_table' ) {
                return esc_url( add_query_arg( 'paged', $page_num ) );
            }
            return esc_url( get_pagenum_link( $page_num ) );
        };

        $html = '<nav class="skaaa-pagination col-span-full flex items-center justify-center gap-1.5 mt-8 py-4 w-full" aria-label="' . esc_attr__( 'Pagination', 'skaaa-no-code-design' ) . '">';

        // Nút Trang Trước (Previous)
        if ( $current_page > 1 ) {
            $prev_url = $get_url( $current_page - 1 );
            $html .= '<a href="' . $prev_url . '" class="inline-flex items-center gap-1 px-3.5 py-2 text-sm font-medium rounded-lg bg-surface text-text hover:bg-primary/10 transition-colors border border-border" x-target.push="' . esc_attr( $target_dom_id ) . '" aria-label="' . esc_attr__( 'Previous page', 'skaaa-no-code-design' ) . '">';
            $html .= '&laquo; ' . esc_html__( 'Previous', 'skaaa-no-code-design' );
            $html .= '</a>';
        } else {
            $html .= '<span class="inline-flex items-center gap-1 px-3.5 py-2 text-sm font-medium rounded-lg bg-surface/50 text-text/30 cursor-not-allowed border border-border/40" aria-disabled="true">';
            $html .= '&laquo; ' . esc_html__( 'Previous', 'skaaa-no-code-design' );
            $html .= '</span>';
        }

        // Dải số trang (Sliding Window)
        $range = 2;
        $pages = [];
        for ( $i = 1; $i <= $total_pages; $i++ ) {
            if ( $i === 1 || $i === $total_pages || ( $i >= $current_page - $range && $i <= $current_page + $range ) ) {
                $pages[] = $i;
            } elseif ( end( $pages ) !== '...' ) {
                $pages[] = '...';
            }
        }

        foreach ( $pages as $p ) {
            if ( $p === '...' ) {
                $html .= '<span class="inline-flex items-center justify-center min-w-[36px] h-9 px-2 text-sm text-text/50">&hellip;</span>';
            } elseif ( $p === $current_page ) {
                $html .= '<span class="inline-flex items-center justify-center min-w-[36px] h-9 px-3 text-sm font-semibold rounded-lg bg-primary text-black pointer-events-none shadow-sm" aria-current="page">' . $p . '</span>';
            } else {
                $page_url = $get_url( $p );
                $html .= '<a href="' . $page_url . '" class="inline-flex items-center justify-center min-w-[36px] h-9 px-3 text-sm font-medium rounded-lg bg-surface text-text hover:bg-primary/10 transition-colors border border-border" x-target.push="' . esc_attr( $target_dom_id ) . '" aria-label="' . sprintf( esc_attr__( 'Page %d', 'skaaa-no-code-design' ), $p ) . '">' . $p . '</a>';
            }
        }

        // Nút Trang Sau (Next)
        if ( $current_page < $total_pages ) {
            $next_url = $get_url( $current_page + 1 );
            $html .= '<a href="' . $next_url . '" class="inline-flex items-center gap-1 px-3.5 py-2 text-sm font-medium rounded-lg bg-surface text-text hover:bg-primary/10 transition-colors border border-border" x-target.push="' . esc_attr( $target_dom_id ) . '" aria-label="' . esc_attr__( 'Next page', 'skaaa-no-code-design' ) . '">';
            $html .= esc_html__( 'Next', 'skaaa-no-code-design' ) . ' &raquo;';
            $html .= '</a>';
        } else {
            $html .= '<span class="inline-flex items-center gap-1 px-3.5 py-2 text-sm font-medium rounded-lg bg-surface/50 text-text/30 cursor-not-allowed border border-border/40" aria-disabled="true">';
            $html .= esc_html__( 'Next', 'skaaa-no-code-design' ) . ' &raquo;';
            $html .= '</span>';
        }

        $html .= '</nav>';

        return $html;
    }
}

// 6. Tính Toán Container Target ID (Chuẩn hóa tất định cho Alpine AJAX)
$target_id = ! empty( $pagination_target_id ) 
    ? $pagination_target_id 
    : ( ! empty( $anchor ) 
        ? $anchor 
        : 'skaaa-loop-' . substr( md5( wp_json_encode( [ $source_type, $source_table, $post_type, $category ] ) ), 0, 8 ) );

$pagination_html = '';
if ( $enable_pagination && $max_pages > 1 ) {
    $pagination_html = skaaa_render_flat_pagination( $paged, $max_pages, $target_id, $source_type );
}

$tailwind_classes = isset( $attributes['tailwindClasses'] ) ? esc_attr( $attributes['tailwindClasses'] ) : '';
$id_attr          = ! empty( $target_id ) ? ' id="' . esc_attr( $target_id ) . '"' : '';

$output  = '<div' . $id_attr . ' class="wp-block-skaaaaa-builder-loop skaaa-loop-wrapper ' . $tailwind_classes . '">';
$output .= $final_html;
$output .= $pagination_html;
$output .= '</div>';

echo $output;
return;
