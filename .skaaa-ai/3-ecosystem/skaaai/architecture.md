# MODULE: Skaaai (AI Copilot & Bidirectional Sync Bridge)
*Plugin độc lập cung cấp tính năng AI Copilot, Context Manifest tự chủ và Cầu nối đồng bộ bài viết 2 chiều trong hệ sinh thái SKAAA.*

**Status:** 🟢 Stable (v1.5.2)  
**Role:** [BRIDGE, DEPLOYER & HARNESS] Bidirectional Sync Bridge (Local ⟷ Host: Push & Safe Pull, True Mirror Synchronization), Persistent Storage Remote Code Deployer via WP_Filesystem, 1-Click Local Agent Harness Initializer (Lean Rules, Skills, Workflows, 4-Drawer Architecture & Developer/Designer CLI Tools), 1-Click Push & Pull UI (Gutenberg Header Toolbar, Document Status Panel & Post List Management with Deep Pre-flight Diff Checker & Mirror Parity).  
**Dependency:** Hoạt động độc lập hoặc kết hợp với `skaaa-logic-engine`, `skaaa-data-pro`, `skaaa-no-code-design`.

---

## 1. Kiến Trúc Phân Chia Trách Nhiệm (Decoupled Integration)
Skaaai tuân thủ triệt để nguyên tắc Decoupled Architecture, giao tiếp 100% qua WordPress Hooks và REST API bảo mật:
- **Zero-Postmeta & Flat Tables:** Cấu hình hệ thống (Pairing Key, Remote URL, Secret Token, LLM API Keys) được lưu trữ tại bảng phẳng MySQL `wp_skaaa_data_sys_settings` của `skaaa-data-pro` (nếu có) hoặc fallback an toàn vào bảng phẳng cục bộ.
- **Pluggable Nodes Framework:** Đăng ký các Node AI (`AIPromptNode`, `AIParserNode`) và Custom Nodes vào đồ thị Logic Engine thông qua hook `apply_filters( 'skaaa_logic_registered_nodes', ... )`.
- **Gutenberg Editor Integration:** Nạp nút bấm 1-Click "🚀 Push to Live" và "📥 Pull from Live" trực tiếp trên Header Toolbar và Sidebar Document mà không can thiệp sâu vào code lõi của Design Engine.

---

## 2. Các Trụ Cột Năng Lực Cốt Lõi

### Trụ cột 1: Persistent Storage & Remote Code Deployer (v1.0.3)
- **Lưu trữ bền vững ngoài plugin (`wp-content/skaaa-custom-nodes/`):**
  - Khắc phục triệt để cơ chế xóa sạch thư mục plugin mặc định của WordPress khi cập nhật bằng file `.zip`.
  - Toàn bộ custom nodes PHP được lưu tại thư mục độc lập `wp-content/skaaa-custom-nodes/` (ngang hàng với `uploads/`), miễn nhiễm 100% khi update plugin Skaaai.
  - Tự động sinh file bảo mật `index.php` và tự động di cư (auto-migration) các file cũ từ legacy plugin dir.
  - Thực thi mã nguồn trực tiếp qua `require_once` để tận dụng PHP OPcache (0ms), không dùng `eval()` và không lưu code trong Database.
- **Syntax Validator Shield & WP_Filesystem:**
  - Linter chạy Tokenizer (`token_get_all`) và kiểm tra cú pháp trước khi ghi, ngăn chặn 100% nguy cơ White Screen of Death (WSoD).
  - Tự động tạo bản sao lưu `.bak` trước khi ghi đè file có sẵn.
- **Live Deletion Lock & Local SSoT:**
  - Khóa quyền xóa file trực tiếp trên Live Webhost (`role === 'receiver'`), thay nút Delete bằng huy hiệu `🔒 Live Protected`.
  - Xóa file trên Localhost (Sender) tự động kích hoạt cuộc gọi REST API `POST /wp-json/skaaai/v1/delete-file` dọn sạch file và bản sao lưu trên Live.

### Trụ cột 2: Bidirectional Content Sync Engine & Push/Pull UI (v1.5.0)
- **Mục tiêu:** Đồng bộ bài viết, landing page thiết kế bằng Skaaa giữa máy tính cá nhân (Local) và Webhost (Production/Staging) 2 chiều hoàn chỉnh (Push ➔ Live & Pull ➔ Local) an toàn, không sợ lệch ID tự tăng (Auto Increment ID) của WordPress.
- **Động Cơ Kéo Dữ Liệu An Toàn (Safe Pull from Live - v1.5.0):**
  - **Endpoint Trích Xuất & Diff Check (Live Host):** `GET /export-post` xuất dữ liệu bài viết (UUID, thuộc tính Gutenberg, metadata), `POST /check-posts-status` nhận diện chênh lệch thời gian (`remote_ahead`) và `GET /export-ecosystem` xuất trọn vẹn 7 scopes hệ sinh thái.
  - **Động Cơ Kéo Hệ Sinh Thái Toàn Diện (Full Ecosystem Pull - `Sync_Ecosystem_Pull`):** Kéo toàn bộ 7 thành phần từ Live về Local (Tokens, Organisms Header/Footer, Theme Templates, Logic Workflows, Smart Object Flat Tables `skaaa_data_*`, All Pages & Settings).
  - **Quy Chế Revision-First (Zero Data Loss):** Luôn tự động tạo WordPress Revision cục bộ trước khi ghi đè, cho phép Undo khôi phục 100% phiên bản trước.
  - **Bộ Hoán Đổi Ngược (Reverse Transformers):** Hoán đổi Live Domain ➔ Localhost Domain (`Sync_Post::rewrite_domain_urls`), hoán đổi Live Table Prefix ➔ Localhost Prefix `wp_skaaa_data_*` (`Sync_Post::rewrite_table_prefixes`), và tải ngược media nhúng (`Reverse Sideload Media`) vào thư mục `uploads/` của Localhost.
  - **Bảo Vệ Khối & KSES Bypass:** Bọc `wp_slash()`, tạm ngắt KSES (`kses_remove_filters()`) và thiết lập ngữ cảnh Administrator (`wp_set_current_user`) khi ghi đè để bảo tồn 100% icon SVG.
- **Cổng Đối Soát 2 Tầng (Two-Tier Pre-flight Diff Gate):**
  - **Tầng 1 - Full Ecosystem Diff Modal:** Trên Admin Cockpit & Admin Bar, nút `📥 1-Click Full Pull from Live` tự động chạy dry-run phân tích đối soát hiển thị chi tiết số Tokens, Organisms, Workflows, Smart Object Tables và Pages sẽ được kéo về trước khi yêu cầu người dùng phê duyệt ghi đè.
  - **Tầng 2 - Single Post Diff Preview:** Trên danh sách bài viết (`edit.php`) và Gutenberg Toolbar, nút "Pull" gọi `skaaai_get_post_diff` mở modal so sánh trực quan song song (Tiêu đề, Ngày giờ sửa đổi, Ảnh đại diện, và Số lượng Blocks) giữa Localhost và Live trước khi thực thi.
- **Giao diện Người dùng 1-Click Push & Pull:**
  - Gutenberg Header Toolbar: Tích hợp song song 2 nút "🚀 Push to Live" và "📥 Pull from Live" kèm modal Diff Preview cảnh báo xác nhận ghi đè.
  - Bảng quản lý `edit.php`: Bổ sung nút "Pull" cạnh nút "Push", thao tác hàng loạt "📥 Pull from Live (Skaaa)" và tự động đối soát hiển thị huy hiệu `⬇️ Remote Ahead` khi Live có sửa đổi mới hơn.
  - Admin Cockpit: Hai nút hành động lớn đặt song song `🚀 1-Click Full Push to Live` và `📥 1-Click Full Pull from Live`.
  - Admin Bar: Menu "Skaaa Sync" phân nhánh `🚀 Push All to Live` và `📥 Pull All from Live`.
  - **KSES Bypass & Administrator Context (v1.4.3):** Tạm thời gọi `kses_remove_filters()` và thiết lập ngữ cảnh Administrator (`wp_set_current_user`) khi xử lý ghi bài qua REST API để ngăn WordPress Core xóa sạch thẻ `<svg>` trong thuộc tính JSON comment Gutenberg (`svgCode`), khôi phục bộ lọc qua `kses_init_filters()` ngay sau khi hoàn tất.
- **Chuẩn Hóa Database Table Prefix Cho Khối Động (v1.4.2):**
  - Tự động quét và hoán đổi prefix bảng phẳng `skaaa_data_*` từ sender sang receiver (`Sync_Post::rewrite_table_prefixes()`) cho toàn bộ nội dung bài viết và các trang đồng bộ, đảm bảo các khối `loop` trỏ chính xác vào CSDL Live (ví dụ: `wpxi_skaaa_data_*`).
- **Cơ chế nạp Media 2 Đầu (Base64 Sideload Media over NAT - v1.3.1):**
  - Mở rộng endpoint tiếp nhận Media `POST /wp-json/skaaai/v1/upload-media` nhận file base64 trực tiếp từ Sender.
  - Quét regex thông minh hỗ trợ cả URL tuyệt đối (`http...`) và URL tương đối (`/wp-content/uploads/...`) cùng escape gạch chéo `\/`.
  - Sender tự động đọc file ảnh từ ổ cứng Localhost, đẩy trực tiếp lên Media Library của Live host, sau đó hoán đổi URL mới trước khi ghi nội dung bài viết.
- **Giao diện Người dùng 1-Click Push to Live (Gutenberg Toolbar & Document Panel):**
  - Tích hợp nút bấm trực tiếp "🚀 Push to Live" trên thanh Header Toolbar (bên cạnh nút Lưu/Đăng bài) và panel Status & Visibility trong Document Sidebar (`assets/js/skaaai-editor-toolbar.js`).
  - Tự động lưu bài trước khi push (`savePost()`), gửi payload bài viết qua REST API, tự động hoán đổi URL domain và tải ảnh về media library của Live host.
  - Hiển thị Toast thông báo thành công kèm link mở xem trực tiếp trên Live, phát hiện và hỗ trợ giải quyết xung đột 409 Conflict (Force Overwrite).
- **Quản lý Đồng bộ Danh sách Bài viết (`edit.php`):**
  - Bổ sung cột **"Skaaa Sync"** trên danh sách All Posts / All Pages (`class-skaaai-post-sync-ui.php`), hiển thị huy hiệu động (`🟢 Synced`, `⬆️ Local Ahead`, `⚪ Not Synced`).
  - Hỗ trợ nút Push nhanh từng bài qua AJAX với spinner (`assets/js/skaaai-post-list.js`) và thao tác đẩy hàng loạt (Bulk Push to Live `skaaai_bulk_push`) kèm thông báo tổng kết.
- **Động cơ Đồng Bộ Toàn Bộ Hệ Sinh Thái (1-Click Full Ecosystem Sync - v1.4.3):**
  - Đồng bộ trọn gói: `sys_presets` (Design Tokens), `sys_organisms` (HeaderBar/FooterBar), `sys_theme_templates` (Theme Locations), `sys_workflows` (Skaaa Logic Engine DAG Graphs) và All Pages kèm Media.
  - Thiết quân luật bảo vệ Live: Blacklist tên miền (`siteurl`, `home`), tài khoản (`admin_email`), plugin (`active_plugins`) và dữ liệu thực của khách (`*_submissions`).
  - Tự động Purge LiteSpeed Cache & WordPress Object Cache ngay sau khi hoàn tất.

### Trụ cột 3: Kiến Trúc Atomic Design 5 Tầng, Theme Builder Integration & Anti-Runaway Harness (v1.2.6)
- **Mục tiêu:** Thiết lập chuẩn mực kiến trúc Atomic Design 5 tầng (Design Tokens ➔ Atoms ➔ Molecules ➔ Organisms ➔ Templates ➔ Pages). Triệt tiêu hoàn toàn lối làm việc tạo trang tĩnh mì ăn liền (Monolithic HTML 100+ blocks), phân rã cấu kiện độc lập tái sử dụng (`HeaderBar`, `FooterBar`), tự động kích hoạt Theme Template toàn site trên Skaaa Theme Builder qua CSDL phẳng MySQL `skaaa_data_sys_theme_templates`, và kết nối dữ liệu động qua khối `loop` với bảng phẳng `skaaa_data_*` (Skaaa Data Pro).
- **Quy tắc Bất Biến (Sender-Only Scaffolding):**
  - Cặp thư mục điều hành `.agent/` và tài liệu `.skaaa-ai/` là **đặc quyền độc nhất của website đóng vai trò `Sender` (Localhost)**. Cấm tuyệt đối khởi tạo hoặc đồng bộ lên `Receiver` (Live Webhost).
- **Bộ Công Cụ Harness CLI (Mã nguồn nằm trong `scaffold/.agent/harness/` của plugin Skaaai):**
  - `db-tool.php`: Tra cứu bảng phẳng `wp_skaaa_data_*`, nạp Design Tokens (`--set-tokens`), lưu cấu kiện Organism (`--save-organism`) tích hợp đăng ký Theme Template toàn cục (`--as-template=header|footer`), xem danh sách Theme Templates (`--list-templates`) và query an toàn. Tự động nhận diện socket MySQL của Local by Flywheel.
  - `block-tool.php`: Bộ kiểm định tĩnh (Static Validator) kiểm tra Flat DOM, chuẩn hóa `tagName` & `tailwindClasses`, kiểm tra Skaaapine `@click.prevent`; hỗ trợ tạo trang WordPress thử nghiệm 1-click trả về link preview (`--create-test-page`) có `wp_slash()` bảo vệ và xem trước HTML render (`--render`).
  - `jit-tool.php`: Bộ tiền kiểm cú pháp Tailwind CSS JIT đối soát trực tiếp với `tailwind-rules.json`, chẩn đoán lỗi typo kèm gợi ý sửa nhanh (Hint) và xuất mã CSS biên dịch xem trước (`--compile`).
- **Cấu trúc Thư Mục Template Scaffold trong Plugin Skaaai (`wp-content/plugins/skaaai/scaffold/`):**
  *(Khi khởi tạo trên site Localhost đích, toàn bộ sẽ được deploy tự động ra thư mục gốc `app/public/` của website đó)*
  1. **Khối Buồng Lái & Thực Thi (`scaffold/.agent/`):**
     - `rules/company-rules.md`: Quy định quản trị của Giám Đốc (Điều 1: Cấm làm mù, Điều 2: Kỷ luật token, Điều 3: Chuẩn kỹ thuật, Điều 4: Database-First & Theme Builder, Điều 5: Anti-Runaway Directive).
     - `workflows/`: `start_session.md`, `end_session.md`, `1-client-intake.md`, `2-assembly-delivery.md`.
     - `skills/`:
       - `client-intake/`: Khảo sát định vị, USP, hệ sinh thái tính năng đòn bẩy.
       - `designer-patterns/`: Mẫu cấu kiện theo chuẩn 5 tầng Atomic Design & Theme Templates.
       - `developer-blocks/`: 15 Atomic & Functional Blocks, CSDL bảng phẳng MySQL và Dynamic Loop Binding.
       - `assembly-delivery/`: Lắp ráp cấu kiện tái sử dụng, đăng ký Theme Template & Cổng kiểm định 3 lớp thực tế kèm thiết quân luật dừng lượt.
       - `start_session/` & `end_session/`: Khởi động và niêm phong tiến độ ca kíp.
     - `harness/`: `db-tool.php`, `block-tool.php`, `jit-tool.php`.
  2. **Bộ Nhớ Hệ Thống Chuẩn 4 Ngăn Kéo (`scaffold/.skaaa-ai/`):**
     - `1-overview/`: `system_map.md` (Bản đồ kiến trúc), `brand-guidelines.md` (Design Tokens), `client-brief.md` (Hồ sơ dự án, USP & Brief).
     - `2-memory/`: `decision-log.md` (Sổ tay quyết định kiến trúc), `checkpoint.md` (Sổ bàn giao ca kíp), `self-improve.md` (Sổ tay tự sửa sai hành vi cho AI Agent trên site khách hàng).
     - `3-ecosystem/`: Thư mục kiến trúc các plugin độc lập.
     - `4-rules/`: Thư mục quy định hệ sinh thái.
- **Thực thi:**
  - Nút bấm trên Admin Skaaai (`role === 'sender'`) deploy tự động toàn bộ cấu trúc vào `app/public/`.
  - Tích hợp checkbox `Clean and overwrite existing template files & folders` cho phép làm mới triệt để hoặc bảo toàn dữ liệu hiện có.

---

## 3. Quy Trình Ghép Đôi & Bảo Mật (Pairing Protocol)
1. **Thiết lập vai trò (Role):** User chọn chế độ trong Admin:
   - **Sender (Localhost):** Nơi thiết kế, gửi dữ liệu đi.
   - **Receiver (Webhost):** Máy chủ đích nhận dữ liệu.
2. **Khởi tạo Pairing Key:** Receiver sinh một chuỗi mã hóa:
   `skaaai_pair://[base64_encoded_remote_url_and_secret_token]`
3. **Kết nối:** Dán Pairing Key vào Sender. Hai bên bắt tay (Handshake) qua `/wp-json/skaaai/v1/handshake` với `hash_equals()` để kích hoạt trạng thái kết nối.
