# MODULE: Skaaai (AI Copilot & Bidirectional Sync Bridge)
*Plugin độc lập cung cấp tính năng AI Copilot, Context Manifest tự chủ và Cầu nối đồng bộ bài viết 2 chiều trong hệ sinh thái SKAAA.*

**Status:** 🟢 Stable (v1.2.1)  
**Role:** [BRIDGE, DEPLOYER & HARNESS] 1-Click Sync Bridge (Local ⟷ Host), Persistent Storage Remote Code Deployer via WP_Filesystem, 1-Click Local Agent Harness Initializer (Lean Rules, Skills, Workflows, Project Documents & Developer/Designer CLI Tools).  
**Dependency:** Hoạt động độc lập hoặc kết hợp với `skaaa-logic-engine`, `skaaa-data-pro`, `skaaa-no-code-design`.

---

## 1. Kiến Trúc Phân Chia Trách Nhiệm (Decoupled Integration)
Skaaai tuân thủ triệt để nguyên tắc Decoupled Architecture, giao tiếp 100% qua WordPress Hooks và REST API bảo mật:
- **Zero-Postmeta & Flat Tables:** Cấu hình hệ thống (Pairing Key, Remote URL, Secret Token, LLM API Keys) được lưu trữ tại bảng phẳng MySQL `wp_skaaa_data_sys_settings` của `skaaa-data-pro` (nếu có) hoặc fallback an toàn vào bảng phẳng cục bộ.
- **Pluggable Nodes Framework:** Đăng ký các Node AI (`AIPromptNode`, `AIParserNode`) và Custom Nodes vào đồ thị Logic Engine thông qua hook `apply_filters( 'skaaa_logic_registered_nodes', ... )`.
- **Gutenberg Editor Integration:** Nạp nút bấm 1-Click "🚀 Push to Live" trực tiếp trên Editor Toolbar mà không can thiệp sâu vào code lõi của Design Engine.

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

### Trụ cột 2: Bidirectional Content Sync Engine (Localhost ⟷ Webhost)
- **Mục tiêu:** Đồng bộ bài viết, landing page thiết kế bằng Skaaa giữa máy tính cá nhân (Local) và Webhost (Production/Staging) 2 chiều an toàn, không sợ lệch ID tự tăng (Auto Increment ID) của WordPress.
- **Định danh toàn cục (`skaaa_uuid`):**
  - Mỗi bài viết được cấp 1 mã định danh duy nhất (UUID v4) lưu tại metadata `_skaaa_uuid`.
  - Khi đồng bộ, hệ thống đối soát dựa trên `skaaa_uuid` thay vì `ID` số của WordPress.
- **Cơ chế an toàn (Data Safety):**
  - **Hoán đổi tên miền (Domain Rewriter):** Tự động hoán đổi URL giữa local và live domain.
  - **Sideload Media:** Tự động tải hình ảnh từ máy local về Media Library trên hosting.
  - **Tự động tạo Revision:** Trước khi ghi đè trên Receiver, luôn gọi `wp_save_post_revision()` để có thể Undo phục hồi 1-click trong WordPress History.

### Trụ cột 3: Mô Hình Công Ty Công Nghệ Thu Nhỏ & Bộ Công Cụ Developer/Designer CLI (v1.2.1)
- **Mục tiêu:** Tái cấu trúc toàn bộ kho AI Kit Scaffolding thành một "Công ty công nghệ thu nhỏ" gồm 4 phòng ban tinh gọn, Tủ tài liệu doanh nghiệp nội bộ và bộ 3 CLI Tools hỗ trợ Developer/Designer. Triệt tiêu hoàn toàn nguy cơ AI chạy mù quáng, đốt token vô ích, làm treo terminal khi truy vấn CSDL hoặc xuất bản class CSS sai cú pháp.
- **Quy tắc Bất Biến (Sender-Only Scaffolding):**
  - Cặp thư mục điều hành `.agent/` và tài liệu `.skaaa-ai/` là **đặc quyền độc nhất của website đóng vai trò `Sender` (Localhost)**. Cấm tuyệt đối khởi tạo hoặc đồng bộ lên `Receiver` (Live Webhost).
- **Bộ Công Cụ Harness CLI (Mã nguồn nằm trong `scaffold/.agent/harness/` của plugin Skaaai):**
  - `db-tool.php`: Tra cứu bảng phẳng `wp_skaaa_data_*`, kiểm tra schema, xem sample rows và chạy câu lệnh `SELECT` an toàn (tự động áp `LIMIT 50`, chặn câu lệnh phá hoại nếu thiếu `--force`, tích hợp preflight check DB chống lỗi treo shell terminal MISTAKE-001).
  - `block-tool.php`: Bộ kiểm định tĩnh (Static Validator) kiểm tra Flat DOM, soát cú pháp tự đóng `/-->`, kiểm tra Skaaapine `@click.prevent`, cấm `onclick` thô; hỗ trợ tạo trang WordPress thử nghiệm 1-click trả về link preview (`--create-test-page`) và xem trước HTML render (`--render`).
  - `jit-tool.php`: Bộ tiền kiểm cú pháp Tailwind CSS JIT đối soát trực tiếp với `tailwind-rules.json`, chẩn đoán lỗi typo kinh điển của Designer kèm gợi ý sửa nhanh (Hint) và xuất mã CSS biên dịch xem trước (`--compile`). Chạy Standalone 100% không phụ thuộc database.
- **Cấu trúc Thư Mục Template Scaffold trong Plugin Skaaai (`wp-content/plugins/skaaai/scaffold/`):**
  *(Khi khởi tạo trên site Localhost đích, toàn bộ sẽ được deploy tự động ra thư mục gốc `app/public/` của website đó)*
  1. **Khối Buồng Lái & Thực Thi (`scaffold/.agent/`):**
     - `rules/company-rules.md`: Công cụ quản trị của Giám Đốc (Bạn là Giám Đốc, cấm làm mù, cấm đốt token, chuẩn Skaaa).
     - `workflows/start_session.md`: Khởi động ca kíp, nạp bộ nhớ.
     - `workflows/end_session.md`: Kết thúc ca kíp, niêm phong tiến độ.
     - `workflows/1-client-intake.md`: Đồ nghề của Account/BA (kịch bản phỏng vấn Giám Đốc lấy Logo, Ảnh, Menu, Footer).
     - `workflows/2-assembly-delivery.md`: Đồ nghề của Dev & QC (ráp block 1 nhịp, soát lỗi Gutenberg và bàn giao link nghiệm thu).
     - `skills/designer-patterns.md`: Đồ nghề của Designer (mẫu khung Logo/Banner, thiết quân luật tiền kiểm JIT và bảng Sai ➔ Đúng).
     - `skills/developer-blocks.md`: Đồ nghề của Developer (cú pháp 6 Atomic blocks, PHP `$wpdb` và hướng dẫn bộ tool CLI).
     - `harness/db-tool.php`: Công cụ CLI kiểm tra CSDL phẳng an toàn.
     - `harness/block-tool.php`: Công cụ CLI kiểm định block & tạo trang test 1-nhịp.
     - `harness/jit-tool.php`: Công cụ CLI tiền kiểm cú pháp Tailwind CSS JIT.
  2. **Tủ Tài Liệu Nội Bộ Doanh Nghiệp Mẫu (`scaffold/.skaaa-ai/`):**
     - `1-company-profile/system-map.md`: Hồ sơ năng lực & bản đồ công nghệ (4 plugin + 1 theme).
     - `1-company-profile/brand-guidelines.md`: Quy chuẩn nhận diện thương hiệu & Design Tokens.
     - `2-company-memory/decision-log.md`: Sổ tay ghi nhớ quyết định kiến trúc sếp chốt.
     - `2-company-memory/checkpoint.md`: Sổ bàn giao ca kíp giữa các phiên làm việc.
     - `3-project-dossier/client-brief.md`: Hồ sơ dự án cất giữ URL Logo thật, Ảnh thật và Copywriting.
- **Thực thi:**
  - Nút bấm trên Admin Skaaai (`role === 'sender'`) deploy tự động toàn bộ cấu trúc vào `app/public/`.
  - Tối ưu token tối đa, cung cấp đầy đủ công cụ CLI để dev/agent kiểm tra tức thời tại terminal.

---

## 3. Quy Trình Ghép Đôi & Bảo Mật (Pairing Protocol)
1. **Thiết lập vai trò (Role):** User chọn chế độ trong Admin:
   - **Sender (Localhost):** Nơi thiết kế, gửi dữ liệu đi.
   - **Receiver (Webhost):** Máy chủ đích nhận dữ liệu.
2. **Khởi tạo Pairing Key:** Receiver sinh một chuỗi mã hóa:
   `skaaai_pair://[base64_encoded_remote_url_and_secret_token]`
3. **Kết nối:** Dán Pairing Key vào Sender. Hai bên bắt tay (Handshake) qua `/wp-json/skaaai/v1/handshake` với `hash_equals()` để kích hoạt trạng thái kết nối.
