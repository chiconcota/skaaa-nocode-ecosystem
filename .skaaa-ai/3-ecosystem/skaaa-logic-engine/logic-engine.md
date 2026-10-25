# Kiến trúc Skaaa Logic Engine (Skaaa-xi măng)
@version: 1.3.3
@focus: Lớp Nhân (Dự án The Trinity Architecture)

Skaaa Logic Engine là bộ óc kết nối (Node Engine định tuyến) giúp "Vỏ" (Skaaa Design / Form Frontend) nối với "Kho" (Skaaa Data Pro / Flat Tables MySQL).

## 1. Tôn Chỉ Hoạt Động (Event-Driven Backbone)
Giao tiếp hoàn toàn vô hình thông qua WP Hook. Cách ly Microservices triệt để.
- REST API `POST /skaaa-logic/v1/submit` đón nhận dữ liệu thô. (API Receiver).
- **Tự động sinh/đăng ký Workflow cho CRUD Portal (2026-05-22):** Nếu ID của Form/Action gửi lên có tiền tố `insert_`, `update_` hoặc `delete_` và chưa có workflow cấu hình sẵn, hệ thống tự động kiểm tra sự tồn tại của bảng phẳng dữ liệu tương ứng. Nếu bảng tồn tại, hệ thống tự động sinh một đồ thị workflow CRUD tương ứng (Insert/Update/Delete) gồm: Trigger -> DB Action -> Client Response (trả về kết quả hoặc chỉ thị giao diện như `remove_row` cho delete) và lưu thẳng vào bảng phẳng MySQL `wp_skaaa_data_sys_workflows` (Hoàn thành refactor CSDL v1.1.0 ngày 2026-05-28).
- **Cải tiến cơ chế Xóa dòng trên Frontend (2026-05-22):** Sửa lỗi không xóa dòng ở UI bằng cách hoàn thiện `processEventBus` và `submitForm` trong `skaaa-core.js` and `skaaa-frontend.js`. Hệ thống chuyển sang truyền trực tiếp phần tử click (`initiatorEl` hoặc `$el` của Form trong Alpine) thay vì biến global dễ bị race condition. Đồng thời, cải tiến selector định vị hàng cha (`rowEl`) để tránh bị dừng ở nút click hoặc cột (do có cùng lớp CSS `.skaaa-container-block` hoặc `.skaaa-organism-row`), sử dụng thuật toán tìm kiếm đi lên thông qua `targetEl.closest('tr')`, `.skaaa-organism-row`, v.v. kết hợp fallback `targetEl.parentElement` đảm bảo xóa chính xác hàng DOM tương ứng với hiệu ứng chuyển động mượt mà.
- Bơm dữ liệu lên phễu bằng Filter `apply_filters( 'skaaa_logic_run_pipeline', $clean_data, $form_id )`.
- Class `Skaaa_Workflow_Runner` khởi động, lặp qua đồ thị Graph và cho điện chạy qua các cục `Nodes`.
- Ở cuối bằng chuyền, một Action Node sẽ gồng mình lên phát lệnh `apply_filters('skaaa_data_insert_record')` to tống cổ cục hàng qua sang vương quốc `Skaaa Data Pro` đúc MySQL.

### 1.1. Quy tắc Lọc Dữ liệu An Toàn (Sanitization Bypass Rules)
Để hỗ trợ lưu trữ hoàn hảo các khối Gutenberg blocks (`<!-- wp:... -->`), custom raw HTML, các mã CSS Tailwind tùy biến và các kiểu dữ liệu JSON đặc biệt (`relation`, `multi_select`, `rollup`) được tạo ra từ Design Editor (Shadow Scratchpad) hoặc Frontend Portal:
- Hệ thống đón nhận dữ liệu API (`Skaaa_Logic_Form_Receiver`) tự động tra cứu lược đồ bảng (`skaaa_data_dictionary`) dựa trên Form ID.
- Tự động bỏ qua hàm lọc `sanitize_text_field()` đối với các cột được định nghĩa kiểu `long_text` hoặc kiểu dữ liệu JSON, thay vào đó áp dụng `wp_unslash()` để lưu trữ dữ liệu thô nguyên bản vào CSDL phẳng `wp_skaaa_data_*`.
- Phòng chống tấn công SQL Injection vẫn được bảo đảm tuyệt đối 100% nhờ việc sử dụng cơ chế prepared statements (`$wpdb->prepare`, `$wpdb->insert`, `$wpdb->update`) ở tầng xử lý DB Action Node.



## 2. Tiêu Chuẩn Giao Diện Node
Tất cả các cục Xử lý mềm (Date, Slug) hoặc Cục Hành Động chốt (Gửi Email) BẮT BUỘC IMPLEMENTS `Skaaa_Logic_Node`.
Chúng bắt buộc chứa tham số `public function execute( $payload, $config )`. 
Mọi thứ vào là `$payload` và bắt buộc trả về mảng `$payload` cho Node kế nhiệm kế thừa xử lý. (Polymorphism thuần túy).

## 3. Kiến trúc Automation Platform (DAG & Canvas)
Định hướng của Skaaa Logic Engine đã chính thức **Vượt ra ngoài giới hạn Linear Array (Tuần tự)** để tiến lên nền tảng Automation toàn diện (tương tự n8n/Blender).
- **DAG (Directed Acyclic Graph):** Chuyển đổi bộ máy Backend (`Skaaa_Workflow_Runner`) để hỗ trợ duyệt cây đồ thị.
- **Node Vạn Năng (Success/Error Ports):** Các Node (đặc biệt là API) hỗ trợ đa ngõ ra. Một Node có thể rẽ nhánh khi lỗi (Try/Catch) giúp bảo vệ luồng dữ liệu an toàn tuyệt đối.
- **Trigger Node First:** Mọi Workflow bắt buộc bắt đầu bằng 1 Trigger (Form Submit, Webhook In, Cron Schedule).
- **Async Process (Chạy Nền):** Hỗ trợ cờ chạy nền cho các đường nối (Edges), đẩy tác vụ nặng xuống hàng đợi Action Scheduler của WordPress.
- **Canvas UI (React Flow v11+):** Giao diện Builder đã chuyển đổi hoàn toàn sang React Flow (Full-Screen). Hỗ trợ Sidebar Drag & Drop kéo thả các Node trực tiếp vào Canvas. `ReactFlowProvider` bọc toàn cục để quản lý State chung (screenToFlowPosition).
- **Settings Panel (Inspector):** Click vào Node trên Canvas sẽ mở ra bảng Setting bên phải để cấu hình Node. Thay đổi cấu hình (Data Binding 2 chiều) cập nhật label và data trên Node Canvas theo thời gian thực, đồng thời sync xuống ô input ẩn của WP.
- **BaseNode Architecture:** Cung cấp generic `BaseNode` bọc ngoài các Node logic, chuẩn hóa UI hiển thị cho Icon, Label, và các cổng In/Out (Handles) giúp việc mở rộng 10 Atomic Nodes được đồng nhất.
- **Pluggable Nodes Framework & Dynamic Sidebar (v1.3.0):** Triển khai lớp `Skaaa_Node_Registry` quản lý tập trung việc đăng ký node và filter `skaaa_logic_registered_nodes` cho phép bên thứ ba tự đăng ký custom nodes từ các plugin addon độc lập. Sidebar React nạp động danh sách nodes từ JS context, tự động mapping chuỗi tên icon (như `Zap`, `Database`, `Mail`...) thành React Lucide Icon tương ứng với fallback an toàn về `ServerCog` chống crash.
- **Dynamic settings form (v1.3.0):** Xây dựng component `DynamicNodeSettings` tự động giải phân cấu trúc mảng cấu hình JSON Schema (`settings_schema`) từ PHP registry để tự động vẽ Form settings phù hợp (input, password, textarea, select, toggle) cho các node ngoài core, đồng bộ reactive về state nodes của Canvas.

## 4. UI Tương Tác & Quản Trị Băng Chuyền
- **Hệ Thống Dual-View & Dynamic Submenu:** Lớp giao diện quản trị wp-admin tách thành `Manager UI` (Dashboard List View) và `Builder UI` (Không gian kéo thả). Đặc biệt, hệ thống hỗ trợ Dynamic Submenu, tự động đăng ký các workflows đang hoạt động thành các menu con trên thanh sidebar của WordPress, cho phép người dùng Nocode truy cập nhanh trực tiếp vào builder mà không cần qua Dashboard trung gian.
- **Settings Panel & Tối ưu Responsive (UX/UI):** Bảng Settings Panel bên phải được gia cố CSS (`flex-shrink-0`, `word-break`) để chống tình trạng vỡ layout hoặc tràn màn hình khi thực hiện Mapping nhiều trường dữ liệu phức tạp. Hỗ trợ sự kiện phím tắt (VD: bấm `ESC` để đóng Modal nhanh gọn).
- **Skaaa System Dashboard & Module Cards:** Áp dụng thiết kế thống nhất (Unified Card UI) cho toàn bộ hệ sinh thái Skaaa thông qua `System Framework`. Các Module Cards (bao gồm của Logic Engine) sử dụng chung một `module-card` component Tailwind với hiệu ứng hover, blur-backdrop cao cấp, tạo cảm giác Premium và đồng bộ tuyệt đối giữa các Plugins.
- **Explicit Target Mapping:** Hỗ trợ tính năng `Mapping DB Database` trực quan cho Node Hành động Cuối. Bấm nút OK sẽ kích hoạt ngầm AJAX lôi Lược đồ từ `Skaaa Data Pro` (dùng khóa an ninh `skaaa_data_nonce`) giúp người dùng khớp dữ liệu tự do mà KHÔNG THỰC HIỆN Re-render phá hủy DOM tree (ngăn lỗi Extension Chrome dính kèm).
- **Hệ Cơ Chế Tự Phục Hồi HTTP POST (Data Healing):** Độc quyền hệ điều hành Nocode mới có. Khi Client nhập biến tự thân có chứa khoảng trắng (Vd: `ngày sinh`), máy chủ PHP sinh ra biến cục bộ theo Post (`ngày_sinh`). Logic Engine Processor và Logic Insert tự động tìm dò quy hồi bằng cách replace ` ` -> `_` để thỏa dụng đầu vào. Tôn trọng 100% người dùng Nocode.
- **Logic Database Picker (UI Nhúng Cao Cấp):** Modal DB Picker (Glassmorphism design) loại bỏ hoàn toàn thẻ `datalist` cũ. Phân nhóm (Group) tự động tuyệt đối bằng cách khai thác khóa ngoại tĩnh `__table_info['app_id']` đồng bộ trực tiếp mảng `skaaa_data_apps` từ Data Pro. Giải quyết vĩnh viễn rác rưởi Regex tên App, và Trigger tự động Load Lược đồ Database (Mapping Scheme) khi Người dùng Nocode chọn Bảng Đích.
- **AI JSON Blueprint Import/Export (v1.1.3):** Tích hợp công cụ Giao tiếp Máy-Máy (Machine-to-Machine) trên UI Manager. Cho phép người dùng kết xuất (Export) đồ thị đồ sộ (DAG) ra định dạng file `.json` thuần và nhập lại (Import Blueprint) bất cứ lúc nào qua Upload Modal. Đặc biệt hữu ích khi dùng AI Agents tạo trước cấu trúc Automation Workflow phức tạp rồi đúc trực tiếp vào nền tảng. Hỗ trợ ghi đè (`overwrite`) an toàn. Endpoint REST: `POST /skaaa-logic/v1/import-blueprint` và `GET /skaaa-logic/v1/export-blueprint`.
- **JSON Blueprint Editor & Graph Switcher (v1.1.11):** Tích hợp nút chuyển đổi nhanh (Graph View / JSON View) bằng thanh công cụ Glassmorphism. Cho phép người dùng hoặc AI chỉnh sửa trực tiếp sơ đồ qua JSON thô, tự động bắt lỗi cú pháp và validate cấu trúc trước khi cập nhật canvas React Flow để ngăn chặn tuyệt đối rủi ro crash ứng dụng (Syntax Validation & Safe Update).

## 5. Universal Dynamic Binding (Data Hydration Engine)
Cỗ máy không chỉ đón dữ liệu đẩy xuống, mà còn bơm dữ liệu ngược lên Giao diện Frontend.
- **Đánh chặn Tầng Trình Bày:** Sử dụng Filter `the_content` (Priority `90`) qua class `Skaaa_Dynamic_Content`.
- **Cơ chế Tiền tố "Ngầm" (Smart Prefixing):** Lễ Tân không bắt buộc phải gõ đầy đủ `skaaa_data_doctors`. Thuật toán Regex tự hiểu `{{doctors.ho_va_ten}}` và bồi đắp tiền tố chuẩn để giao tiếp trơn tru bằng hàm lõi `\Skaaa\Data\Core\Data_Fetcher`.
- **Memory Cache (Singleton RAM):** Lỗ hổng Database được vá kín mít. Cơ chế Singleton `$memory_cache` lưu lại Query theo chỉ mục `?id=`. Nó mở liên kết với MariaDB/MySQL đúng 1 lần duy nhất cho toàn bộ trang web kể cả khi trang có in tới 50 cột dữ liệu khác nhau. Response rate duy trì ảo diệu ở mức `0ms`.
- **Skaaa Select Auto-Generation (2026-04-23):** Cỗ máy hỗ trợ xử lý linh hoạt chuỗi (string) và đối tượng (object) từ `skaaaDynamicBinding`. Tự động nhận diện trường hợp cú pháp loop `{{#foreach schema.table.col}}` bị thiếu template body và thẻ đóng `{{/foreach}}` (từ React Inspector của thẻ Select) để tự động bơm vào chuỗi `<option value="{{value}}">{{label}}</option>` mặc định. Đảm bảo Editor UI cấu hình tối giản nhưng Frontend render chính xác.
- **SkaaaFX Context Resolver (Đoán ngữ cảnh thông minh):** Ngăn ngừa triệt để lỗi "nhân đôi tiền tố" và xung đột (Collision) giữa các App:
  1. **Smart Context Guessing:** Ưu tiên đọc tham số `GLOBAL_TABLE` (như `?table=app_kiem_thu_moi`) kết hợp với Hậu tố truy vấn của người dùng để sinh ra Full Tên đúng mà KHÔNG cần dùng Suffix Scan. Loại bỏ hoàn toàn ngoại lệ Xung đột.
  2. **Memory Array Traversal:** Nạp nguyên từ điển Schema lên bộ nhớ tĩnh (Static Cache RAM PHP). Dù mảng có hơn 5,000 DB flat tables, tốc độ cắt chuỗi truy vết (`substr`) vẫn đạt dưới 1ms. Bảo toàn 100% Hiệu năng Nocode Frontend.
- **Gutenberg Editor Flat DOM & Zero-Wrapper Protocol (v1.3.3):**
  - HOC `withUniversalDynamicBinding` (`assets/js/skaaafx-editor/src/index.jsx`) tuyệt đối không sinh thẻ `<div>` bọc thừa nếu block không chứa dynamic binding script (`!bindingData.script`), trả về trực tiếp `<BlockEdit {...props} />` thuần khiết.
  - Khi block chứa dynamic script, thẻ bọc bắt buộc áp dụng `style={{ display: 'contents' }}` (`.skaaa-dynamic-binding-wrapper`), giúp thẻ bọc hoàn toàn "vô hình" trước bộ máy tính toán Grid/Flex layout của CSS, bảo toàn 100% kích thước và bố cục responsive trong Editor mà không cần can thiệp cờ `!important`.

## 6. SkaaaFX Evaluator (Engine Thông Dịch Cú Pháp Lõi)
SkaaaFX cấu tạo nên Trái Tim Giải Trí (Logic & Calculation) của toàn bộ Micro-Ecosystem Skaaa. Nó là ngôn ngữ chung dùng cho tất cả Plugins thay thế Blockly truyền thống.
- **Vai Trò Đa Năng:** Phục vụ Data Hydration Frontend, Conditional Block Display (Logic hiển thị), và Backend Formula Data Grid (Cột Ảo Skaaa Data Pro).
- **Cú Pháp Excel/Airtable Chuẩn Hóa:** Biến dữ liệu được bọc trong Ngoặc Vuông `[table.field]`. Hàm được bao bằng Ngoặc Tròn `IF(...)`. Tính tương thích rộng nhưng sức mạnh Lập trình cao.
- **Hoạt Động Dựa Trên AST (Abstract Syntax Tree PHP):** 
  - (1) **Lexer:** Băm chuỗi người dùng gõ.
  - (2) **Parser (Statements):** Biến thành luồng xử lý và lập bản đồ Cây phân cấp (Top-Down Precedence). Hỗ trợ Statement variables `var x = 1;`.
  - (3) **Evaluator:** Đối chiếu Symbol Table Local Row đầu tiên, nếu không thấy mới phân tách Scope App/Table tìm ngoài DB. Nuốt 100% Syntax Error không bao giờ Fatal Website.

## 7. Kiến trúc Primitive & Composite Nodes (Phase 4.2)
Triết lý thiết kế Node của Skaaa Logic Engine thay đổi từ "Specialized Nodes" (các node chức năng đóng hộp to bản) sang mô hình **Core Primitives** (các khối cơ sở) kết hợp **Composite Nodes** (Macro đóng gói).

- **Các Hạt Cơ Bản (True Primitives):** *(Chi tiết xem tại `primitive-nodes.md`)*
  1. **Nhóm Trigger:** Kích hoạt luồng (Logic Trigger, Webhook, Schedule).
  2. **Nhóm Logic:** Điều hướng logic (If/Else, Switch Router).
  3. **Nhóm Data & Giao thức:** 
     - **DB Action:** Lưu/Sửa/Xóa (Mutate) CSDL bảng phẳng.
     - **DB Query:** Lọc, Tìm kiếm, Đọc (Fetch) dữ liệu từ CSDL.
     - **Set Data / Context:** Gán biến, thay đổi Payload cục bộ.
     - **HTTP Request:** Giao tiếp API ngoại vi (GET/POST/PUT...).
  4. **Nhóm Trình diễn (Response):** 
     - **Render Template:** Nội suy dữ liệu vào giao diện HTML tĩnh.
     - **Client Response:** Trả lệnh điều khiển về Frontend (Toast, Modal, Redirect).

- **Cơ chế Payload Mutability (Sự thay đổi mảng toàn cục):** Hệ thống hoạt động theo nguyên lý "thùng hàng" `$payload` truyền qua dạng Pass-by-Reference ở mức Runner. Các Primitive Node (Như `Set Payload`) sẽ thực thi tính toán SkaaaFX và chèn thẳng (hoặc ghi đè) biến vào `$payload`, giúp các Node phía sau có thể trực tiếp lấy dữ liệu thông qua ngữ cảnh `{{ ... }}` mà không cần phải truy vết ngược.
- **Composite Nodes (Post-MVP):** Người dùng có thể nhóm nhiều Primitive Nodes lại thành một Sub-flow (Macro) và lưu vào `skaaa_data_sys_logic_macros` để tái sử dụng như một Custom Node độc lập (Ví dụ: Nhóm `Render Template` + `Raw HTTP Request` thành Custom Node `Send Email Marketing`).

### 7.1. Cơ Chế An Toàn (Performance & Safety)
Do bản chất của việc xâu chuỗi nhiều Primitive Nodes và chạy vòng lặp, hệ thống lõi (`Workflow_Runner`) áp dụng 3 quy tắc thép để chống quá tải (Overhead) và lặp vô hạn (Infinite Loop):
1. **Circuit Breaker (Ngắt mạch):** Bộ đếm `$step_count` đếm số lượng Node đã thực thi trong 1 phiên. Nếu vượt quá ngưỡng (VD: 1000), lập tức `throw CircuitBreakerException` để dừng hệ thống chống treo. Có giới hạn độ sâu đệ quy (`$call_stack_depth`) và thời gian chạy tối đa (Execution Timeout).
2. **State Pruning (Dọn rác bộ nhớ):** Ở môi trường Production, biến `$context` của luồng chỉ giữ lại các Output cần thiết cho các node sau. Output của các Node trung gian không còn tác dụng sẽ bị `unset()` để tiết kiệm RAM. Async Worker (Action Scheduler) được dùng để chặt nhỏ (chunking) xử lý cho vòng lặp lớn.
3. **Data Vector / Batch Processing (Chống N+1):** Các Action Nodes (như DB Action) được thiết kế nhận diện đầu vào dạng Mảng (Array). Thay vì gọi lặp `insert/update` 1000 lần sinh ra lỗi N+1 Queries, Node sẽ tự động gom thành 1 câu lệnh Bulk SQL duy nhất để xử lý với Skaaa Data Pro.

### 7.2. Quản Lý Bật/Tắt Mềm Node (Node Soft-Toggle) & Bảng Phẳng System Settings (v1.3.0)
Để đảm bảo tính độc lập và độ ổn định cho hệ sinh thái, việc tắt mở các trạm logic mở rộng được thực hiện ở mức độ logic mềm mà không cần gỡ kích hoạt plugin vật lý của WordPress:
1. **Bảng phẳng `wp_skaaa_data_sys_settings`:** Cung cấp hạ tầng cơ sở dữ liệu phẳng để lưu trữ các thông số cấu hình hệ thống bằng JSON.
2. **Khóa `skaaa_disabled_nodes`:** Lưu trữ mảng chứa danh sách các Node Type bị vô hiệu hóa (Blacklist).
3. **Cơ chế hoạt động:** Khi nạp danh sách Node cho Canvas React Flow, Registry sẽ lọc qua danh sách bị tắt này và đánh dấu thuộc tính `disabled => true` cho các Node tương ứng, làm cho các Node đó bị mờ đi (grayscale) và vô hiệu hóa khả năng kéo thả trên UI.
