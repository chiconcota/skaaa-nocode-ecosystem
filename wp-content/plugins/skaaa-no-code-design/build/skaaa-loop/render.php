<?php
/**
 * Render Callback cho Skaaa Loop Block
 * 
 * Kiến trúc: Siêu tốc (Zero N+1)
 * 1. Thu thập tất cả các ID của Organism từ các Slots
 * 2. Tải toàn bộ HTML của chúng lên RAM bằng 1 Query (Bulk Load)
 * 3. Biên dịch HTML thô thành Khuôn Mustache (Template Compilation)
 * 4. Truy vấn bảng phẳng lấy dữ liệu.
 * 5. Duyệt qua từng dòng dữ liệu, tính toán SkaaaFX để chọn Khuôn.
 * 6. Hydrate (bơm) dữ liệu vào Khuôn Mustache siêu nhanh bằng chuỗi tĩnh.
 */

defined( 'ABSPATH' ) || exit;

// Lấy thuộc tính cấu hình từ người dùng
$source_table = isset( $attributes['sourceTable'] ) ? sanitize_text_field( $attributes['sourceTable'] ) : '';
$limit        = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 10;
$slots        = isset( $attributes['slots'] ) && is_array( $attributes['slots'] ) ? $attributes['slots'] : [];

if ( empty( $source_table ) ) {
    if ( is_admin() || ( defined('REST_REQUEST') && REST_REQUEST && isset($_REQUEST['context']) && $_REQUEST['context'] === 'edit' ) ) {
        echo __( '<div class=\"components-placeholder\"><div class=\"components-placeholder__label\">Skaaa Query Loop</div><div class=\"components-placeholder__fieldset\">Please select Source Table in Inspector.</div></div>', 'skaaa-no-code-design' );
        return;
    }
    return;
}

// 1. Thu thập ID và Bulk Load
$organism_ids = [];
foreach ( $slots as $slot ) {
    if ( ! empty( $slot['organismId'] ) ) {
        $organism_ids[] = absint( $slot['organismId'] );
    }
}

if ( empty( $organism_ids ) ) {
    if ( is_admin() || ( defined('REST_REQUEST') && REST_REQUEST && isset($_REQUEST['context']) && $_REQUEST['context'] === 'edit' ) ) {
        echo __( '<div class=\"components-placeholder\"><div class=\"components-placeholder__label\">Skaaa Query Loop</div><div class=\"components-placeholder__fieldset\">Please set up at least 1 Slot (Organism ID).</div></div>', 'skaaa-no-code-design' );
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
        
        // Trích xuất biến [table.column]
        preg_match_all( '/\[([a-zA-Z0-9_\.]+)\]/', $script, $matches );
        if ( ! empty( $matches[1] ) ) {
            $var_name = $matches[1][0]; 
            
            // Đào lỗ vào Text Block (Hỗ trợ đa dạng block văn bản)
            $text_blocks = ['skaaaaa-builder/text', 'skaaaaa-builder/heading', 'core/paragraph', 'core/heading'];
            if ( in_array( $block['blockName'], $text_blocks ) ) {
                 $block_content = preg_replace( '/(<[a-zA-Z0-9]+[^>]*>)(.*?)(<\/[a-zA-Z0-9]+>\s*)$/s', '${1}{{' . $var_name . '}}${3}', $block_content );
            }
            
            // Mở rộng: Đào lỗ cho Link (href) đối với nút
            $button_blocks = ['skaaaaa-builder/button', 'core/button'];
            if ( in_array( $block['blockName'], $button_blocks ) ) {
                if ( strpos( $script, '"href"' ) !== false ) {
                    $block_content = preg_replace( '/href="([^"]*)"/', 'href="{{' . $var_name . '}}"', $block_content );
                }
            }
            // Mở rộng sau: Đào lỗ cho Image (src)
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
        $compiled_templates[ $org_id ] = '<div style="color:red; padding:10px; border:1px solid red;">' . esc_html__( 'Error: Infinite Loop detected at Symbol/Organism ID:', 'skaaa-no-code-design' ) . ' ' . esc_html($org_id) . '</div>';
        continue;
    }
    
    $skaaa_rendering_organisms[ $org_id ] = true;
    
    $blocks = parse_blocks( $raw_html );
    $html_output = '';
    foreach ( $blocks as $b ) {
        $html_output .= render_block( $b );
    }
    
    // Áp dụng resolve_inline_links cho toàn bộ HTML của template (Hỗ trợ block native của WP)
    if ( class_exists( '\Skaaa\Builder\Utils\Dynamic_Data' ) ) {
        $html_output = \Skaaa\Builder\Utils\Dynamic_Data::resolve_inline_links( $html_output );
    }
    
    $compiled_templates[ $org_id ] = $html_output;
    
    unset( $skaaa_rendering_organisms[ $org_id ] );
}

if ( empty( $compiled_templates ) ) {
    error_log('Skaaa Loop: Return early 5 - Empty compiled_templates');
    return;
}

// Tắt bộ lọc Compile, bật lại bộ lọc cũ
remove_filter( 'render_block', $compile_filter, 10 );
if ( class_exists( '\Skaaa_Dynamic_Content' ) ) {
    add_filter( 'render_block', [ \Skaaa_Dynamic_Content::instance(), 'parse_skaaa_blocks' ], 10, 2 );
}


// 3. Lấy dữ liệu thực từ Data Pro (Sử dụng bảng phẳng)
if ( ! class_exists( '\Skaaa\Data\Core\Data_Fetcher' ) ) {
    echo __( 'Error: Skaaa Data Pro has not been activated.', 'skaaa-no-code-design' );
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
if ( ! empty( $attributes['filters'] ) && is_array( $attributes['filters'] ) ) {
    $first_filter = $attributes['filters'][0];
    if ( ! empty( $first_filter['column'] ) && isset( $first_filter['value'] ) ) {
        $filter_val = $first_filter['value'];
        
        // Parse {url:id} context
        if ( preg_match('/\{url:([a-zA-Z0-9_]+)\}/', $filter_val, $matches) ) {
            $url_param = $matches[1];
            // Get from Router Context or $_GET
            if ( $url_param === 'id' && (get_query_var('skaaa_id') || get_query_var('app_record_id')) ) {
                $filter_val = get_query_var('skaaa_id') ? get_query_var('skaaa_id') : get_query_var('app_record_id');
            } else {
                $filter_val = isset($_GET[$url_param]) ? sanitize_text_field($_GET[$url_param]) : '';
            }
        }
        
        $args['filter_field'] = $first_filter['column'];
        $args['filter_op']    = $first_filter['operator'] ?? 'eq';
        $args['filter_val']   = $filter_val;
    }
}

$rows = \Skaaa\Data\Core\Data_Fetcher::get_table_rows( $actual_table_name, $args, $limit );
if ( empty( $rows ) ) {
    if ( is_admin() || ( defined('REST_REQUEST') && REST_REQUEST && isset($_REQUEST['context']) && $_REQUEST['context'] === 'edit' ) ) {
        echo __( '<div class=\"components-placeholder\"><div class=\"components-placeholder__label\">Skaaa Query Loop</div><div class=\"components-placeholder__fieldset\">Data table \"', 'skaaa-no-code-design' ) . esc_html($actual_table_name) . __( '\" There is currently no data. (No rows found).</div></div>', 'skaaa-no-code-design' );
        return;
    }
    echo '<!-- Skaaa Loop: No data found -->';
    return;
}

// 4. Vòng Lặp Phân Giải Siêu Tốc
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
        // Hỗ trợ cả prefix của bảng: [doctors.name], [skaaa_data_projects.name]
        $source_table      => $row,
        $actual_table_name => $row,
        // Cung cấp biến $item chuẩn để dùng trong SkaaaFX Condition
        '$item'            => $row,
    ] );

    if ( ! empty( $table_suffix ) ) {
        $context[ $table_suffix ] = $row;
        $context[ 'skaaa_data_' . $table_suffix ] = $row;
    }

    // Duyệt qua các slot để Match điều kiện
    $matched_template_html = '';
    
    foreach ( $slots as $slot ) {
        if ( empty( $slot['organismId'] ) ) continue;
        
        $org_id = absint( $slot['organismId'] );
        if ( ! isset( $compiled_templates[ $org_id ] ) ) continue;

        $condition = trim( $slot['condition'] ?? '' );
        
        // Nếu không có condition, coi như Default Slot (luôn khớp)
        if ( empty( trim( $condition ) ) || trim( $condition ) === 'default' ) {
            $matched_template_html = $compiled_templates[ $org_id ];
            break;
        }

        // Tính toán bằng SkaaaFX
        if ( class_exists( '\Skaaa\Logic\SkaaaFX\SkaaaFX_Engine' ) ) {
            try {
                $result = \Skaaa\Logic\SkaaaFX\SkaaaFX_Engine::execute( $condition, $context );
                
                // Nếu phép tính trả về giá trị truthy (true, 1, "1", "true"), khớp Slot này
                $is_truthy = false;
                if ( isset( $result['last_val'] ) ) {
                    $val = $result['last_val'];
                    if ( $val === true || $val === 1 || $val === '1' || strtolower((string)$val) === 'true' ) {
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
        // Hỗ trợ cả {{ variable }}, [variable] và dạng URL-encoded (%7B%7B variable %7D%7D, %5B variable %5D)
        $hydrated_html = preg_replace_callback( '/\{\{\s*([a-zA-Z0-9_\.\$\-]+)\s*\}\}|\[\s*([a-zA-Z0-9_\.\$\-]+)\s*\]|%7B%7B\s*([a-zA-Z0-9_\.\$\-]+)\s*%7D%7D|%5B\s*([a-zA-Z0-9_\.\$\-]+)\s*%5D/', function( $matches ) use ( $context, $actual_table_name ) {
            // Biến nằm ở group 1, 2, 3 hoặc 4 tùy theo cú pháp khớp
            $raw_key = '';
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($matches[$i])) {
                    $raw_key = trim($matches[$i]);
                    break;
                }
            }
            
            $parts = explode( '.', $raw_key );
            
            // Xử lý Dynamic Link cho cột Relation (vd: teacher_id.url)
            if ( count( $parts ) > 1 && end( $parts ) === 'url' ) {
                $field_name = $parts[ count($parts) - 2 ];
                if ( isset( $context[ $field_name ] ) && is_string( $context[ $field_name ] ) ) {
                    $rel_val = $context[ $field_name ];
                    // Dữ liệu Relation đã được enrich thành JSON Array: [{"id":123,"label":"..."}]
                    $decoded = json_decode($rel_val, true);
                    if ( is_array($decoded) && !empty($decoded[0]['id']) ) {
                        if ( !empty($decoded[0]['url']) ) {
                            return esc_url( $decoded[0]['url'] );
                        }
                        
                        $target_id = $decoded[0]['id'];
                        // Tìm bảng đích và portal slug
                        $dict = get_option('skaaa_data_dictionary', []);
                        if ( isset($dict[$actual_table_name][$field_name]['options']) ) {
                            $target_table = $dict[$actual_table_name][$field_name]['options'];
                            if ( isset($dict[$target_table]['__table_info']['portal_settings']['slug']) && !empty($dict[$target_table]['__table_info']['portal_settings']['active']) ) {
                                $slug = $dict[$target_table]['__table_info']['portal_settings']['slug'];
                                return esc_url( home_url( '/' . ltrim($slug, '/') . '/' . $target_id . '/' ) );
                            }
                        }
                        // Fallback URL nếu không tìm thấy portal
                        return esc_url( home_url( '/?p=' . $target_id ) );
                    }
                }
            }
            
            // Tách mảng theo dấu chấm. Data Binding có thể trả về: app_test_loop_block.bang_bac_si.name
            // Cột trong bảng phẳng chỉ lưu là: name. Nên ta sẽ lấy phần tử cuối cùng.
            $field_name = end( $parts );
            
            if ( isset( $context[ $field_name ] ) ) {
                $val = $context[ $field_name ];
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
                }
                return esc_html( (string) $val );
            }
            
            // Nếu không tìm thấy dữ liệu, trả về lại nguyên gốc để User biết lỗi
            return $matches[0];
        }, $matched_template_html );

        $final_html .= $hydrated_html;
    }
}

$tailwind_classes = isset( $attributes['tailwindClasses'] ) ? esc_attr( $attributes['tailwindClasses'] ) : '';

$output = '<div class="wp-block-skaaaaa-builder-loop skaaa-loop-wrapper ' . $tailwind_classes . '">';
$output .= $final_html;
$output .= '</div>';

echo $output;
return;
