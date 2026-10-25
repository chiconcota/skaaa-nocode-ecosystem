<?php
defined('ABSPATH') || exit;

class Skaaa_Form_Receiver
{

    public static function register_routes()
    {
        register_rest_route('skaaa-logic/v1', '/submit', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'handle_submit'],
            // @todo: Tạm mở cửa cho Khách tham quan Form nhập liệu. Cần Nonce-auth ở Phase sau!
            'permission_callback' => '__return_true',
        ]);

        // Cung cấp Lược đồ Database cho hệ sinh thái Editor (TributeJS)
        register_rest_route('skaaa-logic/v1', '/schema', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'handle_schema'],
            'permission_callback' => function () {
                return current_user_can('edit_posts'); // Phân quyền Editor
            }
        ]);
    }

    public static function handle_submit(WP_REST_Request $request)
    {
        $data = $request->get_params(); // Payload gốc Frontend đẩy sang
        $form_id = $data['skaaa_form_id'] ?? 'unknown_workflow';
        if (is_array($form_id)) {
            $form_id = trim(sanitize_text_field(reset($form_id)));
        }

        // 1. Phễu Khử Độc: Chặn XSS Injection cơ bản nhất và phân loại kiểu cột
        global $wpdb;
        $table_slug = '';
        if (strpos($form_id, 'insert_') === 0) {
            $table_slug = substr($form_id, 7);
        } elseif (strpos($form_id, 'update_') === 0) {
            $table_slug = substr($form_id, 7);
        }

        $table_schema = [];
        if (!empty($table_slug)) {
            $prefix = $wpdb->prefix . get_option('skaaa_data_prefix', 'skaaa_data_');
            $table_name = $prefix . $table_slug;
            $dictionary = get_option('skaaa_data_dictionary', []);
            if (isset($dictionary[$table_name]) && is_array($dictionary[$table_name])) {
                $table_schema = $dictionary[$table_name];
            }
        }

        $clean_data = [];
        foreach ($data as $k => $v) {
            // Loại bỏ cái tham số route rác của REST API
            if ($k === 'rest_route') {
                continue;
            }

            $clean_key = sanitize_text_field($k);
            
            // Lấy kiểu cột từ lược đồ schema
            $col_type = '';
            if (isset($table_schema[$clean_key]) && is_array($table_schema[$clean_key])) {
                $col_type = $table_schema[$clean_key]['type'] ?? '';
            }

            if (is_string($v)) {
                if ($col_type === 'long_text') {
                    // long_text: Bỏ qua sanitize_text_field để bảo toàn mã HTML, CSS JIT, và block comments Gutenberg
                    $clean_data[$clean_key] = wp_unslash($v);
                } elseif (in_array($col_type, ['relation', 'multi_select', 'rollup'], true) && (str_starts_with($v, '[') || str_starts_with($v, '{'))) {
                    // relation/multi_select/rollup: Bỏ qua sanitize_text_field nếu là chuỗi JSON để không phá hủy cấu trúc mảng/đối tượng
                    $clean_data[$clean_key] = wp_unslash($v);
                } else {
                    $clean_data[$clean_key] = sanitize_text_field($v);
                }
            } else {
                $clean_data[$clean_key] = $v;
            }
        }

        // 2. Tự động sinh/đăng ký Workflow nếu là CRUD Portal tự động
        global $wpdb;
        $table_workflows = $wpdb->prefix . 'skaaa_data_sys_workflows';
        $wf_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table_workflows}` WHERE workflow_id = %s", $form_id));

        if (!$wf_exists) {
            $action_type = '';
            $table_slug = '';
            if (strpos($form_id, 'insert_') === 0) {
                $action_type = 'insert';
                $table_slug = substr($form_id, 7);
            } elseif (strpos($form_id, 'update_') === 0) {
                $action_type = 'update';
                $table_slug = substr($form_id, 7);
            } elseif (strpos($form_id, 'delete_') === 0) {
                $action_type = 'delete';
                $table_slug = substr($form_id, 7);
            }

            if (!empty($action_type) && !empty($table_slug)) {
                $prefix = $wpdb->prefix . get_option('skaaa_data_prefix', 'skaaa_data_');
                $table_name = $prefix . $table_slug;

                // Kiểm tra xem bảng vật lý có tồn tại không
                $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
                if ($table_exists) {
                    $app_id = 'skaaa_system'; // fallback
                    $dictionary = get_option('skaaa_data_dictionary', []);
                    if (isset($dictionary[$table_name]['__table_info']['app_id'])) {
                        $app_id = $dictionary[$table_name]['__table_info']['app_id'];
                    }

                    // Tạo đồ thị Workflow
                    $graph = [
                        'nodes' => [
                            [
                                'id' => 'trigger_1',
                                'type' => 'TriggerNode',
                                'class' => 'Skaaa_Logic_Trigger_Node',
                                'position' => ['x' => 50, 'y' => 50],
                                'data' => [
                                    'label' => 'Trigger Node',
                                    'workflowId' => $form_id
                                ]
                            ],
                            [
                                'id' => 'db_action_1',
                                'type' => 'DBActionNode',
                                'class' => 'Skaaa_Logic_DB_Action',
                                'position' => ['x' => 50, 'y' => 200],
                                'data' => [
                                    'label' => 'DB CRUD Action',
                                    'table' => $table_name,
                                    'actionType' => $action_type,
                                    'recordId' => '{{id}}'
                                ]
                            ],
                            [
                                'id' => 'response_1',
                                'type' => 'ClientResponseNode',
                                'class' => 'Skaaa_Logic_Client_Response',
                                'position' => ['x' => 50, 'y' => 400],
                                'data' => [
                                    'label' => 'Client Response',
                                    'response_type' => ($action_type === 'delete') ? 'remove_row' : 'toast',
                                    'message' => ($action_type === 'delete') ? __( 'Record deleted successfully!', 'skaaa-logic-engine' ) : __( 'Miracle! ', 'skaaa-logic-engine' )
                                ]
                            ]
                        ],
                        'edges' => [
                            [
                                'source' => 'trigger_1',
                                'target' => 'db_action_1',
                                'animated' => true,
                                'id' => 'edge_trigger_db'
                            ],
                            [
                                'source' => 'db_action_1',
                                'target' => 'response_1',
                                'animated' => true,
                                'id' => 'edge_db_response'
                            ]
                        ]
                    ];

                    $node_count = count($graph['nodes']);
                    $wpdb->insert(
                        $table_workflows,
                        [
                            'workflow_id' => $form_id,
                            'name'        => $form_id,
                            'app_id'      => $app_id,
                            'graph'       => wp_json_encode($graph),
                            'node_count'  => $node_count,
                            'status'      => 'active'
                        ]
                    );

                    if (class_exists('Skaaa_Logic_Core')) {
                        Skaaa_Logic_Core::sync_workflow_ids_cache();
                    }
                }
            }
        }

        // 3. Chuyển Lệnh Sang Băng Chuyền (The Runner)
        // Áp dụng Nguyên Tắc Vàng = Cấm gọi Class Runner trực tiếp!
        // Truyền Hook vào ko trung, ai hứng thì tính (Ở đây Logic Core đã đăng ký add_filter đón lõng r)
        $completed_payload = apply_filters('skaaa_logic_run_pipeline', $clean_data, $form_id);

        // 3.1 CẢNH BÁO BẢO MẬT/DEV MODE: Check xem Workflow có tồn tại thật không? Tránh fake success
        $graph_json = $wpdb->get_var($wpdb->prepare("SELECT graph FROM `{$table_workflows}` WHERE workflow_id = %s", $form_id));
        if ($graph_json === null) {
            return rest_ensure_response([
                'success' => false,
                'message' => sprintf( __( 'Wrong Password: Logic id \'%s\' not found. ', 'skaaa-logic-engine' ), $form_id )
            ]);
        }

        if ( isset($data['_debug_dump_graph']) ) {
            $decoded_graph = json_decode($graph_json, true);
            return rest_ensure_response([
                'success' => true,
                'graph'   => is_array($decoded_graph) ? $decoded_graph : []
            ]);
        }

        // 2.2 CHECK DỮ LIỆU ĐỔ VỠ TỪ NODE DATA ACTION (Bên Skaaa Data chối từ nhận Data)
        if (isset($completed_payload['_latest_insert']) && $completed_payload['_latest_insert']['result'] === false) {
            return rest_ensure_response([
                'success' => false,
                'message' => __( 'Blocking Error at Warehouse: Wrong Column Mapping or Empty Data, Table {$completed_payload[\'_latest_insert\'][\'table_name\']} refused reception!', 'skaaa-logic-engine' )
            ]);
        }

        $message = __( 'Miracle! ', 'skaaa-logic-engine' );
        
        // Trích xuất thông báo từ ClientResponseNode (nếu có)
        if (!empty($completed_payload['_skaaa_events'])) {
            foreach ($completed_payload['_skaaa_events'] as $event) {
                if (isset($event['type']) && $event['type'] === 'toast' && !empty($event['message'])) {
                    $message = $event['message'];
                    break;
                }
            }
        }

        // 3. Phun Phản Hồi Về Cho Thẻ <Form> Ở Lớp Vỏ Giao Diện
        return rest_ensure_response([
            'success' => true,
            'message' => $message,
            'data' => $completed_payload
        ]);
    }

    public static function handle_schema(WP_REST_Request $request)
    {
        $dictionary = get_option('skaaa_data_dictionary', []);
        global $wpdb;
        $global_prefix = $wpdb->prefix . 'skaaa_data_';

        // Khởi tạo nhóm biến WordPress Core chuẩn
        $tribute_data = [
            [
                'key'   => 'post.title - ' . __( 'Current Post Title', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Post', 'skaaa-logic-engine' ) . ')',
                'value' => '[post.title]'
            ],
            [
                'key'   => 'post.featured_image - ' . __( 'Featured Image URL', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Post', 'skaaa-logic-engine' ) . ')',
                'value' => '[post.featured_image]'
            ],
            [
                'key'   => 'post.author - ' . __( 'Author Display Name', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Post', 'skaaa-logic-engine' ) . ')',
                'value' => '[post.author]'
            ],
            [
                'key'   => 'post.author_avatar - ' . __( 'Author Avatar URL', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Post', 'skaaa-logic-engine' ) . ')',
                'value' => '[post.author_avatar]'
            ],
            [
                'key'   => 'post.date - ' . __( 'Post Published Date', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Post', 'skaaa-logic-engine' ) . ')',
                'value' => '[post.date]'
            ],
            [
                'key'   => 'post.excerpt - ' . __( 'Post Excerpt', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Post', 'skaaa-logic-engine' ) . ')',
                'value' => '[post.excerpt]'
            ],
            [
                'key'   => 'post.permalink - ' . __( 'Post URL / Permalink', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Post', 'skaaa-logic-engine' ) . ')',
                'value' => '[post.permalink]'
            ],
            [
                'key'   => 'post.id - ' . __( 'Current Post ID', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Post', 'skaaa-logic-engine' ) . ')',
                'value' => '[post.id]'
            ],
            [
                'key'   => 'user.name - ' . __( 'Logged-in User Name', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress User', 'skaaa-logic-engine' ) . ')',
                'value' => '[user.name]'
            ],
            [
                'key'   => 'user.email - ' . __( 'Logged-in User Email', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress User', 'skaaa-logic-engine' ) . ')',
                'value' => '[user.email]'
            ],
            [
                'key'   => 'site.title - ' . __( 'Website Title', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Site', 'skaaa-logic-engine' ) . ')',
                'value' => '[site.title]'
            ],
            [
                'key'   => 'site.url - ' . __( 'Website Home URL', 'skaaa-logic-engine' ) . ' (' . __( 'WordPress Site', 'skaaa-logic-engine' ) . ')',
                'value' => '[site.url]'
            ],
        ];

        foreach ($dictionary as $table_id => $table_config) {
            // Lấy App Slug từ __table_info
            $table_info = isset($table_config['__table_info']) ? $table_config['__table_info'] : [];
            $app_slug = isset($table_info['app_id']) && !empty($table_info['app_id']) ? $table_info['app_id'] : 'uncategorized';
            $table_label = isset($table_info['name']) ? $table_info['name'] : $table_id;

            // Xử lý tên bảng vật lý: bóc bỏ `wp_skaaa_data_` và bóc bỏ luôn `app_id` để được model_slug thuần túy
            $table_slug = str_replace($global_prefix, '', $table_id);

            // Lọc luôn cả app_id prefix ra khỏi tên bảng (nếu app_id chèn vào theo chuẩn mới)
            if ($app_slug !== 'uncategorized') {
                $app_slug_clean = sanitize_key($app_slug) . '_';
                if (strpos($table_slug, $app_slug_clean) === 0) {
                    $table_slug = substr($table_slug, strlen($app_slug_clean));
                }
            }

            // Duyệt từng Cột (Mọi key không phải __table_info đều là cột)
            foreach ($table_config as $col_slug => $col_data) {
                if ($col_slug === '__table_info' || $col_slug === 'id' || $col_slug === 'created_at') {
                    continue;
                }

                $full_notation = "{$app_slug}.{$table_slug}.{$col_slug}";
                $label = isset($col_data['label']) ? $col_data['label'] : $col_slug;

                $tribute_data[] = [
                    'key' => "{$full_notation} - {$label} ({$table_label})", // Cho user tìm bằng cả tiếng Việt
                    'value' => "[{$full_notation}]" // Giá trị thực dán vào ô Text
                ];
            }
        }

        return rest_ensure_response($tribute_data);
    }
}
