<?php
/**
 * Render Callback cho Skaaa Loop Block
 *
 * Kiến trúc: Siêu tốc (Zero N+1) & Modular (Chia để trị)
 * 1. Nạp các module chuyên trách: Loop_Compiler và Loop_Query
 * 2. Xác định Target DOM ID tất định cho Alpine AJAX
 * 3. Biên dịch HTML thô thành Khuôn Mustache (Loop_Compiler)
 * 4. Truy vấn dữ liệu thực tế: Pagination, WP_Posts hoặc Flat Table (Loop_Query)
 * 5. Duyệt qua từng dòng dữ liệu, tính toán SkaaaFX để chọn Khuôn và Hydrate
 *
 * @package Skaaa_No_Code_Design
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-loop-compiler.php';
require_once __DIR__ . '/class-loop-query.php';

use Skaaa\Design\Loop\Loop_Compiler;
use Skaaa\Design\Loop\Loop_Query;

$source_type      = isset( $attributes['sourceType'] ) ? sanitize_text_field( $attributes['sourceType'] ) : 'flat_table';
$source_table     = isset( $attributes['sourceTable'] ) ? sanitize_text_field( $attributes['sourceTable'] ) : '';
$slots            = isset( $attributes['slots'] ) && is_array( $attributes['slots'] ) ? $attributes['slots'] : [];
$anchor           = isset( $attributes['anchor'] ) ? sanitize_html_class( $attributes['anchor'] ) : '';
$tailwind_classes = isset( $attributes['tailwindClasses'] ) ? esc_attr( $attributes['tailwindClasses'] ) : '';

// 1. Kiểm tra tính hợp lệ nguồn Flat Table
if ( $source_type === 'flat_table' && empty( $source_table ) ) {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ) ) {
        echo '<div class="components-placeholder"><div class="components-placeholder__label">Skaaa Query Loop</div><div class="components-placeholder__fieldset">' . esc_html__( 'Please select Source Table in Inspector.', 'skaaa-no-code-design' ) . '</div></div>';
        return;
    }
    return;
}

// 2. Kiểm tra tính hợp lệ Slots
if ( empty( $slots ) ) {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ) ) {
        echo '<div class="components-placeholder"><div class="components-placeholder__label">Skaaa Query Loop</div><div class="components-placeholder__fieldset">' . esc_html__( 'Please set up at least 1 Slot (Organism ID).', 'skaaa-no-code-design' ) . '</div></div>';
        return;
    }
    return;
}

// 3. Xác định Target DOM ID tất định cho Alpine AJAX
$target_id = ! empty( $anchor ) 
    ? $anchor 
    : ( ! empty( $attributes['targetId'] ) 
        ? sanitize_html_class( $attributes['targetId'] ) 
        : 'skaaa-loop-' . substr( md5( wp_json_encode( [ $source_type, $source_table, $attributes['postType'] ?? 'post', $attributes['category'] ?? '' ] ) ), 0, 8 ) );

// 4. Biên dịch (Compile) mã HTML tĩnh thành Khuôn Mustache {{key}}
$compiled_templates = Loop_Compiler::compile( $slots );
if ( empty( $compiled_templates ) ) {
    return;
}

// 5. Truy vấn dữ liệu thực tế theo nguồn
$query_result      = Loop_Query::fetch( $attributes, $target_id );
$rows              = $query_result['rows'];
$paged             = $query_result['paged'];
$max_pages         = $query_result['max_pages'];
$actual_table_name = $query_result['actual_table_name'];
$table_suffix      = $query_result['table_suffix'];

// 6. Xử lý khi không có dữ liệu (Safe Empty State cho Alpine AJAX)
if ( empty( $rows ) ) {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ) ) {
        $label = ( $source_type === 'wp_posts' ) ? 'WordPress Posts' : ( $source_type === 'pagination' ? 'Pagination' : $actual_table_name );
        echo '<div class="components-placeholder"><div class="components-placeholder__label">Skaaa Query Loop</div><div class="components-placeholder__fieldset">' . sprintf( esc_html__( 'No records found for "%s".', 'skaaa-no-code-design' ), esc_html( $label ) ) . '</div></div>';
        return;
    }

    $id_attr   = ! empty( $target_id ) ? ' id="' . esc_attr( $target_id ) . '"' : '';
    $empty_msg = apply_filters( 'skaaa_loop_empty_message', __( 'No records found matching your query.', 'skaaa-no-code-design' ), $source_type, $attributes );

    echo '<div' . $id_attr . ' class="wp-block-skaaaaa-builder-loop skaaa-loop-wrapper ' . $tailwind_classes . '">';
    echo '<div class="skaaa-loop-empty col-span-full py-12 px-6 text-center text-text/60 bg-surface/40 rounded-xl border border-border/40 my-4">';
    echo '<p class="text-base font-medium m-0">' . esc_html( $empty_msg ) . '</p>';
    echo '</div>';
    echo '</div>';
    return;
}

// 7. Vòng Lặp Phân Giải Siêu Tốc (Hydration Loop)
$final_html = '';
$total_rows = count( $rows );

foreach ( $rows as $index => $row ) {
    $context = array_merge( $row, [
        '$index' => $index,
        '$first' => $index === 0,
        '$last'  => $index === ( $total_rows - 1 ),
        '$even'  => ( $index % 2 ) === 0,
        '$odd'   => ( $index % 2 ) !== 0,
        '$item'  => $row,
        'item'   => $row,
        'page'   => $row,
    ] );

    if ( $source_type === 'pagination' ) {
        $context['page']       = $row;
        $context['item']       = $row;
        $context['pagination'] = [
            'current_page' => $paged,
            'max_pages'    => $max_pages,
            'target_id'    => $attributes['paginationTargetId'] ?? '',
        ];
    } elseif ( $source_type === 'wp_posts' ) {
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
        
        if ( empty( $condition ) || $condition === 'default' ) {
            $matched_template_html = $compiled_templates[ $org_id ];
            break;
        }

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
            
            // Xử lý Dynamic Link cho cột Relation trong Flat Table
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
            
            // Tìm giá trị trong context
            $val = null;
            if ( array_key_exists( $raw_key, $context ) ) {
                $val = $context[ $raw_key ];
            } elseif ( count( $parts ) > 1 ) {
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

            if ( $val === null ) {
                $field_name = end( $parts );
                if ( array_key_exists( $field_name, $context ) ) {
                    $val = $context[ $field_name ];
                }
            }
            
            if ( $val !== null ) {
                if ( is_string( $val ) ) {
                    $val_clean = trim( stripslashes( $val ) );
                    if ( str_starts_with( $val_clean, '[{' ) && ( $decoded = json_decode( $val_clean, true ) ) && is_array( $decoded ) ) {
                        $labels = array_column( $decoded, 'label' );
                        if ( ! empty( $labels ) ) {
                            return esc_html( implode( ', ', $labels ) );
                        }
                    }
                    if ( str_starts_with( $val, 'http://' ) || str_starts_with( $val, 'https://' ) || str_starts_with( $val, '/' ) ) {
                        return esc_url( $val );
                    }
                }

                $last_part = strtolower( end( $parts ) );
                if ( empty( $val ) && in_array( $last_part, [ 'featured_image', 'featured_image_url', 'thumbnail' ], true ) ) {
                    return 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 600 400\'%3E%3Crect width=\'600\' height=\'400\' fill=\'%231e293b\'/%3E%3Cpath d=\'M260 180a20 20 0 1 0 40 0 20 20 0 1 0-40 0zm-40 80l40-50 30 35 45-60 65 75H220z\' fill=\'%23475569\'/%3E%3Ctext x=\'50%25\' y=\'78%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%2394a3b8\' font-family=\'system-ui,-apple-system,sans-serif\' font-size=\'14\'%3ENo Featured Image%3C/text%3E%3C/svg%3E';
                }

                return esc_html( (string) $val );
            }
            
            return $matches[0];
        }, $matched_template_html );

        $final_html .= $hydrated_html;
    }
}

$id_attr = ! empty( $target_id ) ? ' id="' . esc_attr( $target_id ) . '"' : '';

$output  = '<div' . $id_attr . ' class="wp-block-skaaaaa-builder-loop skaaa-loop-wrapper ' . $tailwind_classes . '">';
$output .= $final_html;
$output .= '</div>';

echo $output;
return;
