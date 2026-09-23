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

### Trụ cột 3: Local Agent Harness & Memory Scaffolding
- **Mục tiêu:** Cung cấp bộ công cụ, CLI, Scripts tiện ích và Hệ thống Bộ nhớ dài hạn chạy trực tiếp dưới máy Localhost để AI Agent (như Antigravity/CLI) thao tác chuẩn xác, ghi nhớ ngữ cảnh và an toàn tuyệt đối với website.
- **Quy tắc Bất Biến (Sender-Only Scaffolding):**
  - Cặp thư mục buồng lái `.agent/` và bộ nhớ `.skaaa-ai/` là **đặc quyền độc nhất của website đóng vai trò `Sender` (Localhost)**.
  - Trên `Receiver` (Live Webhost), cấm tuyệt đối sinh ra `.agent/` và `.skaaa-ai/`. Động cơ đồng bộ Push to Live tuyệt đối không đồng bộ 2 thư mục này lên Live Hosting nhằm triệt tiêu nguy cơ lộ bảo mật và tối ưu hiệu năng.
- **Cấu trúc Buồng lái Kép tại Website Sender:**
  1. **Thư mục `.agent/` (Quy tắc & Công cụ điều khiển):**
     - `.agent/rules/`: Luật lệ phát triển (Atomic Blocks, Flat DB, Tailwind JIT).
     - `.agent/workflows/`: Quy trình hành động (/push-to-live, /start_session, /end_session).
     - `.agent/harness/`: Scripts CLI (tra cứu DB, validate block, kiểm tra CSS).
  2. **Thư mục `.skaaa-ai/` (Bản đồ & Bộ nhớ Ngữ cảnh của Site):**
     - `.skaaa-ai/1-overview/`: Bản đồ cấu trúc website (`site_map.md`), danh sách Pages, Apps và bảng dữ liệu hiện có trên site.
     - `.skaaa-ai/2-memory/`: Bộ nhớ tiến độ (`checkpoint.md`, `decision-log.md`) giúp AI ghi nhớ trạng thái dở dang giữa các phiên làm việc.
- **Thực thi:**
  - **1-Click Init:** Nút bấm trên giao diện Admin Skaaai (chỉ hiện khi `role === 'sender'`) tự động xuất bản (deploy) toàn bộ cấu trúc `.agent/` và `.skaaa-ai/` vào thư mục gốc `app/public/`.
  - **Block Synthesizer & Validator:** Helper sinh mã và kiểm thử cú pháp Atomic Blocks (`container`, `text`, `loop`...) chuẩn comment Gutenberg thuần, bắt buộc `@click.prevent` Alpine.js, loại bỏ triệt để nguy cơ Gutenberg Invalid Content.
  - **Flat Database Inspector:** Module an toàn giúp AI Agent tra cứu schema, danh sách cột và trích xuất mẫu dữ liệu các bảng phẳng `skaaa_data_*` dưới Localhost mà không cần gõ lệnh `mysql` trực tiếp qua CLI (triệt tiêu lỗi treo shell MISTAKE-001).
  - **Tailwind JIT Pre-flight Checker:** Đối soát class dự kiến sinh ra với từ điển `tailwind-rules.json` của JIT offline, đảm bảo 100% Compiler Parity.

---

## 3. Quy Trình Ghép Đôi & Bảo Mật (Pairing Protocol)
1. **Thiết lập vai trò (Role):** User chọn chế độ trong Admin:
   - **Sender (Localhost):** Nơi thiết kế, gửi dữ liệu đi.
   - **Receiver (Webhost):** Máy chủ đích nhận dữ liệu.
2. **Khởi tạo Pairing Key:** Receiver sinh một chuỗi mã hóa:
   `skaaai_pair://[base64_encoded_remote_url_and_secret_token]`
3. **Kết nối:** Dán Pairing Key vào Sender. Hai bên bắt tay (Handshake) qua `/wp-json/skaaai/v1/handshake` với `hash_equals()` để kích hoạt trạng thái kết nối.
