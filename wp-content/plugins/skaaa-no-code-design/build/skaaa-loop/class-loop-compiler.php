<?php
/**
 * Class Loop_Compiler
 * 
 * Chuyên trách biên dịch mã HTML thô của các Organism thành Khuôn Mustache {{key}}
 * để cỗ máy Loop có thể hydrate dữ liệu siêu tốc ở tầng RAM.
 *
 * @package Skaaa_No_Code_Design
 */

namespace Skaaa\Design\Loop;

defined( 'ABSPATH' ) || exit;

class Loop_Compiler {

    /**
     * Biên dịch danh sách Organism IDs từ các Slots thành mảng template Mustache.
     *
     * @param array $slots Danh sách slots cấu hình từ block.
     * @return array Mảng [ organism_id => html_template ]
     */
    public static function compile( array $slots ): array {
        $organism_ids = [];
        foreach ( $slots as $slot ) {
            if ( ! empty( $slot['organismId'] ) ) {
                $organism_ids[] = absint( $slot['organismId'] );
            }
        }

        if ( empty( $organism_ids ) ) {
            return [];
        }

        // 1. Tải toàn bộ HTML của Organisms lên RAM bằng 1 Query duy nhất (Bulk Load)
        $bulk_html = \Skaaa\Design\Api\Organisms_API::get_bulk_html( $organism_ids );
        if ( empty( $bulk_html ) ) {
            return [];
        }

        $compiled_templates = [];

        // 2. Tạm tắt bộ lọc Frontend cũ để không chạy truy vấn dữ liệu thừa trong lúc compile
        if ( class_exists( '\Skaaa_Dynamic_Content' ) ) {
            remove_filter( 'render_block', [ \Skaaa_Dynamic_Content::instance(), 'parse_skaaa_blocks' ], 10 );
        }

        // 3. Định nghĩa bộ lọc Compile Cục bộ (Đào lỗ {{var}} vào các block con)
        $compile_filter = function( $block_content, $block ) {
            if ( isset( $block['attrs']['skaaaDynamicBinding']['script'] ) && ! empty( $block['attrs']['skaaaDynamicBinding']['script'] ) ) {
                $script = $block['attrs']['skaaaDynamicBinding']['script'];
                
                preg_match_all( '/\[([a-zA-Z0-9_\.]+)\]/', $script, $matches );
                if ( ! empty( $matches[1] ) ) {
                    $var_name = $matches[1][0]; 
                    
                    // Đào lỗ vào Text Blocks
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
            
            $blocks      = parse_blocks( $raw_html );
            $html_output = '';
            foreach ( $blocks as $b ) {
                $html_output .= render_block( $b );
            }
            
            if ( class_exists( '\Skaaa\Builder\Utils\Dynamic_Data' ) ) {
                $html_output = \Skaaa\Builder\Utils\Dynamic_Data::resolve_inline_links( $html_output );
            }
            
            $compiled_templates[ $org_id ] = $html_output;
            unset( $skaaa_rendering_organisms[ $org_id ] );
        }

        // Tháo gỡ bộ lọc Compile, kích hoạt lại bộ lọc cũ
        remove_filter( 'render_block', $compile_filter, 10 );
        if ( class_exists( '\Skaaa_Dynamic_Content' ) ) {
            add_filter( 'render_block', [ \Skaaa_Dynamic_Content::instance(), 'parse_skaaa_blocks' ], 10, 2 );
        }

        return $compiled_templates;
    }
}

