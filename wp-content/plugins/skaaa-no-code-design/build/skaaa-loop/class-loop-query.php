<?php
/**
 * Class Loop_Query
 * 
 * Chuyên trách toàn bộ khâu chuẩn bị và truy vấn dữ liệu cho Skaaa Loop:
 * 1. Nguồn Pagination (Dãy số phân trang động, Prev/Next, Dots).
 * 2. Nguồn WordPress Posts (Universal Query Listener cho tìm kiếm, lọc, sắp xếp).
 * 3. Nguồn Flat Table (Skaaa Data Pro bảng phẳng MySQL).
 *
 * @package Skaaa_No_Code_Design
 */

namespace Skaaa\Design\Loop;

defined( 'ABSPATH' ) || exit;

class Loop_Query {

    /**
     * Truy vấn và chuẩn bị dữ liệu hàng (rows) theo cấu hình block.
     *
     * @param array  $attributes Thuộc tính block.
     * @param string $target_id  ID container của khối Loop.
     * @return array Dữ liệu gồm rows, paged, max_pages, actual_table_name, table_suffix.
     */
    public static function fetch( array $attributes, string $target_id ): array {
        $source_type          = isset( $attributes['sourceType'] ) ? sanitize_text_field( $attributes['sourceType'] ) : 'flat_table';
        $source_table         = isset( $attributes['sourceTable'] ) ? sanitize_text_field( $attributes['sourceTable'] ) : '';
        $post_type            = isset( $attributes['postType'] ) ? sanitize_text_field( $attributes['postType'] ) : 'post';
        $category             = isset( $attributes['category'] ) ? sanitize_text_field( $attributes['category'] ) : '';
        $limit                = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 10;
        $enable_pagination    = ! empty( $attributes['enablePagination'] );
        $pagination_target_id = isset( $attributes['paginationTargetId'] ) ? sanitize_html_class( $attributes['paginationTargetId'] ) : '';
        $pagination_range     = isset( $attributes['paginationRange'] ) ? absint( $attributes['paginationRange'] ) : 2;
        $include_prev_next    = ! isset( $attributes['includePrevNext'] ) || ! empty( $attributes['includePrevNext'] );
        $inherit_query        = ! empty( $attributes['inheritQuery'] );

        // 1. Tính toán trang hiện tại từ URL Query
        $paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : ( get_query_var( 'page' ) ? absint( get_query_var( 'page' ) ) : ( isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ) );
        if ( $paged < 1 ) {
            $paged = 1;
        }

        $max_pages         = 1;
        $rows              = [];
        $actual_table_name = '';
        $table_suffix      = '';

        // 2. Phân nhánh thực thi theo nguồn dữ liệu
        if ( $source_type === 'pagination' ) {
            // ==========================================
            // 2.1. NGUỒN: PAGINATION LOOP (Phân Trang Động)
            // ==========================================
            global $skaaa_loop_registry;
            $target_ref = ! empty( $pagination_target_id ) ? $pagination_target_id : 'last_query';

            if ( isset( $skaaa_loop_registry[ $target_ref ] ) ) {
                $max_pages = (int) $skaaa_loop_registry[ $target_ref ]['max_pages'];
                $paged     = (int) $skaaa_loop_registry[ $target_ref ]['current_page'];
            } else {
                global $wp_query;
                if ( isset( $wp_query ) && ! empty( $wp_query->max_num_pages ) ) {
                    $max_pages = (int) $wp_query->max_num_pages;
                } else {
                    $count_posts = wp_count_posts( $post_type ?: 'post' );
                    $total_posts = isset( $count_posts->publish ) ? (int) $count_posts->publish : 0;
                    $max_pages   = max( 1, (int) ceil( $total_posts / ( $limit > 0 ? $limit : 4 ) ) );
                }
            }

            // Khi xem trước trong Gutenberg Editor, sinh trang mẫu nếu max_pages <= 1
            if ( ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ) ) && $max_pages <= 1 ) {
                $max_pages = 4;
                $paged     = 1;
            }

            if ( $max_pages > 1 || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
                $get_page_url = function( $page_num ) {
                    $query_params = [];
                    if ( ! empty( $_GET ) && is_array( $_GET ) ) {
                        foreach ( $_GET as $key => $val ) {
                            if ( $key !== 'paged' && $key !== 'page' && is_scalar( $val ) ) {
                                $query_params[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $val ) );
                            }
                        }
                    }
                    $base_url = get_pagenum_link( $page_num );
                    return ! empty( $query_params ) ? esc_url( add_query_arg( $query_params, $base_url ) ) : esc_url( $base_url );
                };

                $swap_targets = trim( ( ! empty( $pagination_target_id ) ? $pagination_target_id : 'blog-posts-grid' ) . ' ' . $target_id );

                // [A] Nút Trước (Previous)
                if ( $include_prev_next ) {
                    $has_prev = ( $paged > 1 );
                    $prev_url = $has_prev ? $get_page_url( $paged - 1 ) : '#';
                    $rows[]   = [
                        'type' => 'prev', 'is_prev' => true, 'is_next' => false, 'is_dots' => false,
                        'is_active' => false, 'is_disabled' => ! $has_prev,
                        'number' => '&laquo; ' . __( 'Previous', 'skaaa-no-code-design' ), 'page_number' => '&laquo;',
                        'url' => $prev_url, 'page_url' => $prev_url, 'link' => $prev_url,
                        'aria_label' => __( 'Previous page', 'skaaa-no-code-design' ), 'pagination_targets' => $swap_targets,
                    ];
                }

                // [B] Dải số trang (Sliding window)
                $pages = [];
                for ( $i = 1; $i <= $max_pages; $i++ ) {
                    if ( $i === 1 || $i === $max_pages || ( $i >= $paged - $pagination_range && $i <= $paged + $pagination_range ) ) {
                        $pages[] = $i;
                    } elseif ( end( $pages ) !== '...' ) {
                        $pages[] = '...';
                    }
                }

                foreach ( $pages as $p ) {
                    if ( $p === '...' ) {
                        $rows[] = [
                            'type' => 'dots', 'is_prev' => false, 'is_next' => false, 'is_dots' => true,
                            'is_active' => false, 'is_disabled' => true, 'number' => '&hellip;', 'page_number' => '...',
                            'url' => '#', 'page_url' => '#', 'link' => '#', 'aria_label' => '', 'pagination_targets' => $swap_targets,
                        ];
                    } else {
                        $is_current = ( $p === $paged );
                        $page_url   = $get_page_url( $p );
                        $rows[]     = [
                            'type' => 'page', 'is_prev' => false, 'is_next' => false, 'is_dots' => false,
                            'is_active' => $is_current, 'is_disabled' => $is_current,
                            'number' => (string) $p, 'page_number' => (string) $p,
                            'url' => $page_url, 'page_url' => $page_url, 'link' => $page_url,
                            'aria_label' => sprintf( __( 'Page %d', 'skaaa-no-code-design' ), $p ),
                            'pagination_targets' => $swap_targets,
                        ];
                    }
                }

                // [C] Nút Sau (Next)
                if ( $include_prev_next ) {
                    $has_next = ( $paged < $max_pages );
                    $next_url = $has_next ? $get_page_url( $paged + 1 ) : '#';
                    $rows[]   = [
                        'type' => 'next', 'is_prev' => false, 'is_next' => true, 'is_dots' => false,
                        'is_active' => false, 'is_disabled' => ! $has_next,
                        'number' => __( 'Next', 'skaaa-no-code-design' ) . ' &raquo;', 'page_number' => '&raquo;',
                        'url' => $next_url, 'page_url' => $next_url, 'link' => $next_url,
                        'aria_label' => __( 'Next page', 'skaaa-no-code-design' ), 'pagination_targets' => $swap_targets,
                    ];
                }
            }

        } elseif ( $source_type === 'wp_posts' ) {
            // ==========================================
            // 2.2. NGUỒN: WORDPRESS POSTS (WP_Query)
            // ==========================================
            $query_args = [
                'post_type'           => ! empty( $post_type ) ? sanitize_key( $post_type ) : 'post',
                'posts_per_page'      => $limit > 0 ? $limit : 10,
                'post_status'         => 'publish',
                'paged'               => $paged,
                'ignore_sticky_posts' => true,
            ];

            // [A] Universal Search: (?s=, ?search=, ?q=)
            foreach ( [ 's', 'search', 'q' ] as $sk ) {
                if ( isset( $_GET[ $sk ] ) && '' !== trim( (string) $_GET[ $sk ] ) ) {
                    $query_args['s'] = sanitize_text_field( wp_unslash( $_GET[ $sk ] ) );
                    break;
                }
            }

            // [B] Universal Filter: (?category=, ?cat=, ?category_name=)
            $active_cat = $category;
            foreach ( [ 'category', 'category_name', 'cat' ] as $ck ) {
                if ( isset( $_GET[ $ck ] ) && '' !== trim( (string) $_GET[ $ck ] ) ) {
                    $active_cat = sanitize_text_field( wp_unslash( $_GET[ $ck ] ) );
                    break;
                }
            }
            if ( ! empty( $active_cat ) ) {
                if ( is_numeric( $active_cat ) ) {
                    $query_args['cat'] = absint( $active_cat );
                } else {
                    $query_args['category_name'] = sanitize_title( $active_cat );
                }
            }

            // [C] Universal Filter: (?tag=)
            if ( isset( $_GET['tag'] ) && '' !== trim( (string) $_GET['tag'] ) ) {
                $query_args['tag'] = sanitize_title( wp_unslash( $_GET['tag'] ) );
            }

            // [D] Universal Filter: (?author= hoặc ?author_name=)
            if ( isset( $_GET['author'] ) && is_numeric( $_GET['author'] ) ) {
                $query_args['author'] = absint( $_GET['author'] );
            } elseif ( isset( $_GET['author_name'] ) && '' !== trim( (string) $_GET['author_name'] ) ) {
                $query_args['author_name'] = sanitize_title( wp_unslash( $_GET['author_name'] ) );
            }

            // [E] Universal Sort: (?orderby= và ?order=)
            if ( isset( $_GET['orderby'] ) ) {
                $valid_orderby = [ 'date', 'title', 'rand', 'modified', 'comment_count', 'ID', 'name' ];
                $req_orderby   = sanitize_key( $_GET['orderby'] );
                if ( in_array( $req_orderby, $valid_orderby, true ) ) {
                    $query_args['orderby'] = $req_orderby;
                }
            }
            if ( isset( $_GET['order'] ) ) {
                $req_order = strtoupper( sanitize_key( $_GET['order'] ) );
                if ( in_array( $req_order, [ 'ASC', 'DESC' ], true ) ) {
                    $query_args['order'] = $req_order;
                }
            }

            // [F] Kế thừa query của template nếu bật inheritQuery
            if ( $inherit_query && ! is_admin() ) {
                global $wp_query;
                if ( isset( $wp_query ) && ( $wp_query->is_archive() || $wp_query->is_search() || $wp_query->is_home() ) ) {
                    $query_args = array_merge( $wp_query->query_vars, $query_args );
                }
            }

            $query_args  = apply_filters( 'skaaa_loop_wp_query_args', $query_args, $attributes );
            $posts_query = new \WP_Query( $query_args );
            $max_pages   = $posts_query->max_num_pages ? (int) $posts_query->max_num_pages : 1;

            if ( $posts_query->have_posts() ) {
                foreach ( $posts_query->posts as $p ) {
                    $p_id       = $p->ID;
                    $thumb_url  = get_the_post_thumbnail_url( $p, 'large' ) ?: '';
                    $cats       = wp_get_post_categories( $p_id, [ 'fields' => 'names' ] );
                    $cat_string = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? implode( ', ', $cats ) : '';
                    $post_title = get_the_title( $p );
                    $post_link  = get_permalink( $p );
                    $post_date  = get_the_date( '', $p );
                    $post_exc   = wp_strip_all_tags( get_the_excerpt( $p ) );

                    $post_data = [
                        'id' => $p_id, 'ID' => $p_id,
                        'title' => $post_title, 'post_title' => $post_title,
                        'permalink' => $post_link, 'url' => $post_link, 'link' => $post_link,
                        'date' => $post_date, 'post_date' => $post_date,
                        'excerpt' => $post_exc, 'post_excerpt' => $post_exc,
                        'content' => $p->post_content, 'post_content' => $p->post_content,
                        'author' => get_the_author_meta( 'display_name', $p->post_author ),
                        'featured_image' => $thumb_url, 'featured_image_url' => $thumb_url, 'thumbnail' => $thumb_url,
                        'categories' => $cat_string, 'category' => $cat_string,
                    ];

                    $post_data['post']     = $post_data;
                    $post_data['wp_posts'] = $post_data;
                    $rows[]                = $post_data;
                }
                wp_reset_postdata();
            }

            // Đăng ký trạng thái truy vấn vào Registry toàn cục
            global $skaaa_loop_registry;
            if ( ! isset( $skaaa_loop_registry ) || ! is_array( $skaaa_loop_registry ) ) {
                $skaaa_loop_registry = [];
            }
            $skaaa_loop_registry[ $target_id ] = [
                'max_pages'    => $max_pages,
                'current_page' => $paged,
                'source_type'  => 'wp_posts',
            ];
            $skaaa_loop_registry['last_query'] = $skaaa_loop_registry[ $target_id ];

        } else {
            // ==========================================
            // 2.3. NGUỒN: FLAT TABLE (Skaaa Data Pro)
            // ==========================================
            if ( ! class_exists( '\Skaaa\Data\Core\Data_Fetcher' ) ) {
                return [ 'rows' => [], 'paged' => 1, 'max_pages' => 1, 'actual_table_name' => '', 'table_suffix' => '', 'error' => __( 'Skaaa Data Pro has not been activated.', 'skaaa-no-code-design' ) ];
            }

            global $wpdb;
            $actual_table_name = $source_table;

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

            if ( ! empty( $_GET['s'] ) ) {
                $args['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
            } elseif ( ! empty( $_GET['q'] ) ) {
                $args['search'] = sanitize_text_field( wp_unslash( $_GET['q'] ) );
            }

            if ( ! empty( $attributes['filters'] ) && is_array( $attributes['filters'] ) ) {
                $first_filter = $attributes['filters'][0];
                if ( ! empty( $first_filter['column'] ) && isset( $first_filter['value'] ) ) {
                    $filter_val = $first_filter['value'];
                    if ( preg_match( '/\{url:([a-zA-Z0-9_]+)\}/', $filter_val, $matches ) ) {
                        $p = $matches[1];
                        $filter_val = ( $p === 'id' && ( get_query_var( 'skaaa_id' ) || get_query_var( 'app_record_id' ) ) )
                            ? ( get_query_var( 'skaaa_id' ) ?: get_query_var( 'app_record_id' ) )
                            : ( isset( $_GET[ $p ] ) ? sanitize_text_field( $_GET[ $p ] ) : '' );
                    }
                    $args['filter_field'] = $first_filter['column'];
                    $args['filter_op']    = $first_filter['operator'] ?? 'eq';
                    $args['filter_val']   = $filter_val;
                }
            }

            // Quét tham số GET ?filter_[column]=value
            if ( ! empty( $_GET ) && is_array( $_GET ) ) {
                foreach ( $_GET as $k => $v ) {
                    if ( str_starts_with( $k, 'filter_' ) && is_scalar( $v ) && '' !== trim( (string) $v ) ) {
                        $col_name = sanitize_key( substr( $k, 7 ) );
                        if ( ! empty( $col_name ) ) {
                            $args['filter_field'] = $col_name;
                            $args['filter_op']    = 'eq';
                            $args['filter_val']   = sanitize_text_field( wp_unslash( $v ) );
                            break;
                        }
                    }
                }
            }

            $args = apply_filters( 'skaaa_loop_flat_table_args', $args, $actual_table_name, $attributes );
            $rows = \Skaaa\Data\Core\Data_Fetcher::get_table_rows( $actual_table_name, $args, $limit );

            if ( $enable_pagination && method_exists( '\Skaaa\Data\Core\Data_Fetcher', 'count_table_rows' ) ) {
                $total_items = \Skaaa\Data\Core\Data_Fetcher::count_table_rows( $actual_table_name, $args );
                $max_pages   = max( 1, (int) ceil( $total_items / ( $limit > 0 ? $limit : 10 ) ) );
            }

            global $skaaa_loop_registry;
            if ( ! isset( $skaaa_loop_registry ) || ! is_array( $skaaa_loop_registry ) ) {
                $skaaa_loop_registry = [];
            }
            $skaaa_loop_registry[ $target_id ] = [
                'max_pages'    => $max_pages,
                'current_page' => $paged,
                'source_type'  => 'flat_table',
            ];
            $skaaa_loop_registry['last_query'] = $skaaa_loop_registry[ $target_id ];
        }

        return [
            'rows'              => $rows,
            'paged'             => $paged,
            'max_pages'         => $max_pages,
            'actual_table_name' => $actual_table_name,
            'table_suffix'      => $table_suffix,
        ];
    }
}

