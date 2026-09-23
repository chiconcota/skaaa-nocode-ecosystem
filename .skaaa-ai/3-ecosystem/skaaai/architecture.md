# MODULE: Skaaai (AI Copilot & Bidirectional Sync Bridge)
*Plugin độc lập cung cấp tính năng AI Copilot, Context Manifest tự chủ và Cầu nối đồng bộ bài viết 2 chiều trong hệ sinh thái SKAAA.*

**Status:** 🟢 Stable (v1.0.3)  
**Role:** [BRIDGE & DEPLOYER] 1-Click Sync Bridge (Local ⟷ Host), Persistent Storage Remote Code Deployer via WP_Filesystem, Self-Documenting Context.  
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

### Trụ cột 3: Self-Documenting Context Engine (AI Copilot Ready)
- **Mục tiêu:** Giúp AI ngoài môi trường (như Antigravity/Cursor/Windsurf) hiểu thấu đáo toàn bộ hệ thống Blocks, DB Schemas khi dev trên site thật mà không cần thư mục `.skaaa-ai`.
- **Thực thi:**
  - Cung cấp REST API Endpoint: `GET /wp-json/skaaai/v1/context` (Yêu cầu `X-Skaaai-Key` bảo mật).
  - Nội dung context: Danh mục Atomic Blocks (`Container`, `Text`, `Loop`...), từ điển class Tailwind v4 hợp lệ, danh sách bảng phẳng `skaaa_data_*` và cấu trúc các trường dữ liệu.

---

## 3. Quy Trình Ghép Đôi & Bảo Mật (Pairing Protocol)
1. **Thiết lập vai trò (Role):** User chọn chế độ trong Admin:
   - **Sender (Localhost):** Nơi thiết kế, gửi dữ liệu đi.
   - **Receiver (Webhost):** Máy chủ đích nhận dữ liệu.
2. **Khởi tạo Pairing Key:** Receiver sinh một chuỗi mã hóa:
   `skaaai_pair://[base64_encoded_remote_url_and_secret_token]`
3. **Kết nối:** Dán Pairing Key vào Sender. Hai bên bắt tay (Handshake) qua `/wp-json/skaaai/v1/handshake` với `hash_equals()` để kích hoạt trạng thái kết nối.
