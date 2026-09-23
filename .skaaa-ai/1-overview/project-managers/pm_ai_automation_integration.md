# PROJECT MANAGER: SKAAAI (LOCAL AGENT HARNESS & 1-CLICK SYNC BRIDGE)
@status: 🟡 Active Development | @target_milestone: MILESTONE 2 (AGENT HARNESS & SYNC) | @last_update: 2026-09-23

> [!NOTE]
> Tài liệu này quản lý tiến độ phát triển, thiết kế kiến trúc và quy trình triển khai plugin **`Skaaai`**. Plugin này đóng vai trò kép:
> 1. **Local Agent Harness:** Bộ công cụ, CLI và Scripts tiện ích hoạt động trực tiếp dưới môi trường **Localhost**, hỗ trợ AI Agent (như Antigravity/CLI) thao tác chuẩn xác với Skaaa (sinh block Gutenberg chuẩn, validate code, inspect cấu trúc bảng phẳng MySQL `skaaa_data_*`).
> 2. **1-Click Sync Bridge:** Cầu nối xuất bản một chiều từ **Localhost Dev** lên **Live Webhost (Hosting)**. Toàn bộ quá trình code, thiết kế và can thiệp bằng AI diễn ra 100% ở Localhost; Webhost chỉ đóng vai trò là Receiver nhận dữ liệu và đồng bộ hiển thị tương đương 100%.

---

## 1. MỤC TIÊU CỐT LÕI (CORE GOALS)
1. **Local Agent Harness:** Cung cấp bộ helper/CLI/scripts chạy trực tiếp dưới Localhost giúp AI Agent kiểm tra schema, sinh khối Atomic Blocks chuẩn cú pháp Gutenberg, và tra cứu bảng phẳng `skaaa_data_*` mà không bị ảo giác hoặc gây lỗi hệ thống.
2. **1-Click Push to Live Engine:** Chỉnh sửa, thiết kế xong ở Localhost, người dùng bấm "🚀 Push to Live" trên thanh công cụ Gutenberg hoặc danh sách bài viết là dữ liệu được đẩy lên website online ngay lập tức.
3. **Định danh Toàn cầu `skaaa_uuid`:** Gán UUID v4 cho bài viết trên Localhost, triệt tiêu hoàn toàn rủi ro xung đột ID tự tăng (`AUTO_INCREMENT`) khi đồng bộ sang Webhost.
4. **Cơ chế Bảo vệ & Hoán đổi Tự động (Data Safety):** Tự động hoán đổi URL domain (local ➔ live), sideload hình ảnh về Media Library của hosting, và tự động tạo WordPress Revision trước khi ghi đè.
5. **AI Logic Nodes:** Mở rộng **Skaaa Logic Engine** với các Node AI Prompt (Gemini/OpenAI) và AI Parser trích xuất dữ liệu phi cấu trúc vào bảng phẳng MySQL dưới môi trường Localhost.

---

## 2. TIẾN ĐỘ THỰC HIỆN (PHASED ROADMAP)

### 🟢 Phase 1: Nền tảng Ghép đôi & Triển khai Mã nguồn Bền vững (Hoàn thành v1.0.3)
- [x] Thiết lập plugin `wp-content/plugins/skaaai/skaaai.php` (SemVer `1.0.3`, text domain `skaaai`).
- [x] Giao thức ghép đôi bảo mật Pairing Protocol (`skaaai_pair://...`, token 64-char, REST API Handshake).
- [x] Lưu trữ cấu hình phẳng vào `wp_skaaa_data_sys_settings` (`skaaai_get_setting` / `skaaai_set_setting`).
- [x] Triển khai **Persistent Storage** ngoài plugin (`wp-content/skaaa-custom-nodes/`), miễn nhiễm khi update plugin bằng file zip.
- [x] **Syntax Validator Shield & Live Deletion Protection:** Chặn đứng file lỗi cú pháp, khóa xóa file trên Receiver, xóa ở Local cascade tự động dọn sạch file trên Live.

### 🟡 Phase 2: Local Agent Harness & Memory Scaffolding (Buồng Lái & Bộ Nhớ Dưới Localhost)
> **Thiết quân luật:** Cặp thư mục buồng lái `.agent/` và bộ nhớ `.skaaa-ai/` là đặc quyền **DUY NHẤT của Sender (Localhost)**. Trên **Receiver (Live Webhost)**, cấm tuyệt đối việc khởi tạo; động cơ đồng bộ Push to Live cũng không bao giờ đồng bộ `.agent/` và `.skaaa-ai/` lên Live nhằm bảo mật và tối ưu hiệu năng.

- [x] **Bộ Khởi Tạo 1-Click (Agent Harness & Memory Initializer - Sender Only) - Hoàn thành v1.1.0:**
  - Trong Admin Skaaai (`role === 'sender'`), thêm nút bấm **"⚡ Initialize Agent Harness & Memory"**.
  - Khi bấm, Skaaai tự động xuất bản (deploy) toàn bộ cấu trúc kép vào thư mục gốc của website Localhost (`app/public/`):
    - **`.agent/` (Buồng lái điều khiển):**
      - `.agent/rules/`: Bộ luật cho AI (`skaaa-blocks.md`).
      - `.agent/skills/`: Kỹ năng nghiệp vụ chuyên sâu (`skaaa-builder`, `skaaa-flat-db`, `skaaa-sync`).
      - `.agent/workflows/`: Các quy trình rút gọn (`push_to_live.md`, `start_session.md`, `end_session.md`).
      - `.agent/harness/`: Bộ công cụ dòng lệnh (CLI & PHP helpers).
    - **`.skaaa-ai/` (Bản đồ, Nhận diện Thương hiệu & Bộ nhớ Ngữ cảnh của Site):**
      - `.skaaa-ai/1-overview/`: Bản đồ cấu trúc website (`site_map.md`) & Nhận diện thương hiệu Design System (`design.md`).
      - `.skaaa-ai/2-memory/`: Bộ nhớ tiến độ (`checkpoint.md`, `decision-log.md`) ghi nhận tiến độ dở dang giữa các phiên làm việc của AI trên site này.
- [ ] **Block Synthesizer & Validator Tool (`.agent/harness/block-tool.php`):**
  - Tiện ích sinh mã Atomic Blocks chuẩn Gutenberg (`container`, `text`, `button`, `svg`, `code`, `loop`), đảm bảo Flat DOM, không sinh thẻ HTML thô thừa gây Gutenberg Invalid Content.
  - Bộ kiểm tra (Validator) cú pháp Skaaapine: bắt buộc `@click.prevent`, giao tiếp qua `Alpine.store`, ngăn chặn lỗi scope shadowing.
- [ ] **Flat Database Inspector & Safe Query Runner (`.agent/harness/db-tool.php`):**
  - Script/Helper cho phép Agent tra cứu cấu trúc các bảng phẳng `skaaa_data_*` (tên bảng, danh sách cột, kiểu dữ liệu `text`, `number`, `json`, `relation`).
  - Hỗ trợ Agent query lấy dữ liệu mẫu an toàn mà không cần gõ lệnh `mysql` trực tiếp qua CLI (triệt tiêu lỗi MISTAKE-001 làm treo shell).
- [ ] **Tailwind JIT Pre-flight Checker (`.agent/harness/jit-tool.php`):**
  - Kiểm tra tập class CSS mà Agent dự định sinh ra với từ điển `tailwind-rules.json` của JIT offline, cảnh báo sớm các class chưa được hỗ trợ.

### ⚪ Phase 3: Giao diện Người dùng 1-Click Push to Live (Gutenberg Toolbar & Post List)
- [ ] **Gutenberg Editor Toolbar Button ("🚀 Push to Live"):**
  - Tích hợp nút bấm trực tiếp trên thanh công cụ của Gutenberg Editor (`assets/js/skaaai-editor-toolbar.js`).
  - Tự động lưu bài, gán `_skaaa_uuid`, kích hoạt REST API đẩy bài viết, media và JIT CSS sang Live Webhost.
  - Hiển thị Toast thông báo trạng thái đồng bộ và link bài viết trên Live.
- [ ] **Quản lý Đồng bộ Danh sách Bài viết (`edit.php`):**
  - Thêm cột trạng thái **"Skaaa Sync"** trên danh sách All Posts / All Pages (hiển thị badge: `🟢 Synced`, `⬆️ Local Ahead`, `⚪ Not Synced`).
  - Hỗ trợ nút Push nhanh từng bài và tính năng chọn nhiều bài để Push hàng loạt (Bulk Push to Live).
- [ ] Tự động gán `_skaaa_uuid` khi tạo bài viết mới ở Localhost thông qua hook `wp_insert_post`.

### ⚪ Phase 4: Tích hợp AI Logic Nodes (Milestone 2 DAG Automation)
- [ ] Class `Skaaai_Node_Prompt`: Node gọi Gemini / OpenAI API hỗ trợ nội suy biến `{{ ... }}` trong đồ thị Logic Engine.
- [ ] Class `Skaaai_Node_Parser`: Node trích xuất dữ liệu JSON từ văn bản phi cấu trúc vào bảng phẳng MySQL `skaaa_data_*`.
- [ ] Đăng ký nodes vào filter `skaaa_logic_registered_nodes` của `Skaaa Logic Engine`.

### ⚪ Phase 5: Kiểm thử E2E & Đóng gói Hệ sinh thái
- [ ] Kiểm thử E2E toàn diện bộ công cụ Local Agent Harness (sinh block, validate, inspect DB).
- [ ] Kiểm thử E2E quy trình 1-Click Push to Live từ Localhost sang Live Webhost.
- [ ] Cập nhật `zip-all.js` đóng gói tự động `skaaai-v...zip` cùng hệ sinh thái.

---

## 3. TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA)
1. **Local Agent Harness** hoạt động trơn tru dưới Localhost, giúp AI Agent sinh đúng 100% cấu trúc block Skaaa và truy xuất schema database phẳng an toàn, không sinh lỗi shell hay lỗi Gutenberg Invalid Content.
2. Bài viết/trang được thiết kế và chỉnh sửa ở Localhost, khi bấm **"🚀 Push to Live"** từ Gutenberg hoặc bảng danh sách bài viết sẽ xuất hiện trên Webhost trong 1-2 giây với 100% giao diện, CSS và hình ảnh tương đồng.
3. Toàn bộ bài viết đồng bộ qua mã định danh `skaaa_uuid`, tuyệt đối không phụ thuộc vào ID tự tăng của MySQL.
4. Plugin Skaaai tuân thủ chuẩn Decoupled, bảo toàn nguyên tắc không can thiệp trực tiếp vào mã nguồn của các plugin khác.
