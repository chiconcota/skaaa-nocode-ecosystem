<?php
namespace Skaaa_System_Framework;

defined( 'ABSPATH' ) || exit;

class AI_Proxy {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_skaaa_generate_blueprint', array( $this, 'handle_generate_blueprint' ) );
        add_action( 'skaaa_system_dashboard_extensions', array( $this, 'render_ai_extension_card' ) );
    }

    public function render_ai_extension_card() {
        ?>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all flex flex-col items-start h-full">
            <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center mb-4 border border-purple-100">
                <span class="material-symbols-outlined text-[22px]">auto_awesome</span>
            </div>
            <h3 class="m-0 border-0 pt-0 pb-0 text-base font-bold text-slate-800 mb-2"><?php esc_html_e( 'Skaaai AI Copilot & Automation', 'skaaa-no-code-design' ); ?></h3>
            <p class="text-sm text-slate-500 mb-5 flex-1"><?php esc_html_e( 'Configure Gemini & OpenAI API Keys, AI Automation Nodes, and Agent Harness context for your ecosystem.', 'skaaa-no-code-design' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=skaaai-settings#tab-general' ) ); ?>" class="border-0 bg-transparent cursor-pointer p-0 text-purple-600 font-medium text-sm hover:text-purple-800 transition-colors mt-auto flex items-center gap-1 no-underline">
                <?php esc_html_e( 'Configure AI & Settings', 'skaaa-no-code-design' ); ?> <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>
        <?php
    }

    public function handle_generate_blueprint() {
        check_ajax_referer( 'skaaa_ai_blueprint_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }

        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing prompt information.', 'skaaa-no-code-design' ) ) );
        }

        $api_key = get_option( 'skaaa_gemini_api_key', '' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing Gemini API Key. ', 'skaaa-no-code-design' ) ) );
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=' . $api_key;

        $system_prompt = "Bạn là Skaaa AI Overseer, kiến trúc sư trưởng của nền tảng Skaaa App Builder. 
        Người dùng sẽ cung cấp một ý tưởng hệ thống. 
        Nhiệm vụ của bạn là thiết kế kiến trúc cơ sở dữ liệu dựa trên mô hình Flat Tables (không dùng wp_postmeta).
        Mỗi bảng (table) cần có tiền tố skaaa_data_.
        Mỗi trường (field) phải thuộc các loại dữ liệu: text, longtext, number, boolean, date, image, relation_id.";

        $payload = array(
            'contents' => array(
                array( 'parts' => array( array( 'text' => $prompt ) ) )
            ),
            'systemInstruction' => array(
                'parts' => array( array( 'text' => $system_prompt ) )
            ),
            'generationConfig' => array(
                'responseMimeType' => 'application/json',
                'responseSchema' => array(
                    'type' => 'OBJECT',
                    'properties' => array(
                        'appName' => array( 'type' => 'STRING', 'description' => __( 'Professional application name', 'skaaa-no-code-design' ) ),
                        'appDescription' => array( 'type' => 'STRING', 'description' => __( 'Describe the purpose of the application', 'skaaa-no-code-design' ) ),
                        'tables' => array(
                            'type' => 'ARRAY',
                            'description' => __( 'List of Flat Tables', 'skaaa-no-code-design' ),
                            'items' => array(
                                'type' => 'OBJECT',
                                'properties' => array(
                                    'tableName' => array( 'type' => 'STRING', 'description' => __( 'Semantic slug of the table, for example: skaaa_data_spa_customers', 'skaaa-no-code-design' ) ),
                                    'label' => array( 'type' => 'STRING', 'description' => __( 'Friendly display name, for example: Spa Client', 'skaaa-no-code-design' ) ),
                                    'fields' => array(
                                        'type' => 'ARRAY',
                                        'items' => array(
                                            'type' => 'OBJECT',
                                            'properties' => array(
                                                'key' => array( 'type' => 'STRING', 'description' => __( 'School key', 'skaaa-no-code-design' ) ),
                                                'label' => array( 'type' => 'STRING', 'description' => __( 'Display name', 'skaaa-no-code-design' ) ),
                                                'type' => array( 'type' => 'STRING', 'description' => __( 'Data type (text, number, boolean, date, relation_id)', 'skaaa-no-code-design' ) ),
                                                'required' => array( 'type' => 'BOOLEAN' )
                                            ),
                                            'required' => array( 'key', 'label', 'type' )
                                        )
                                    )
                                ),
                                'required' => array( 'tableName', 'label', 'fields' )
                            )
                        )
                    ),
                    'required' => array( 'appName', 'tables' )
                )
            )
        );

        $response = wp_remote_post( $url, array(
            'body'    => wp_json_encode( $payload ),
            'headers' => array( 'Content-Type' => 'application/json' ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
            $err_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown error from API', 'skaaa-no-code-design' );
            wp_send_json_error( array( 'message' => $err_msg ) );
        }

        $text = isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ? $data['candidates'][0]['content']['parts'][0]['text'] : '';
        if ( empty( $text ) ) {
            wp_send_json_error( array( 'message' => __( 'AI does not return valid results.', 'skaaa-no-code-design' ) ) );
        }

        wp_send_json_success( array( 'blueprint' => json_decode( $text, true ) ) );
    }
}
