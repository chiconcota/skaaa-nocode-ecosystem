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

- [x] **Mô Hình Công Ty Công Nghệ Thu Nhỏ & Tủ Tài Liệu Doanh Nghiệp (Sender Only) - Hoàn thành v1.1.2:**
  - Trong Admin Skaaai (`role === 'sender'`), tab **Agent Cockpit** tự động xuất bản (deploy) toàn bộ cấu trúc 10 tệp nguyên tử tinh gọn vào thư mục gốc `app/public/`:
    - **`.agent/` (Bộ máy điều hành & thực thi):**
      - `.agent/rules/company-rules.md`: Công cụ quản trị của Giám Đốc (Bạn là Giám Đốc, cấm làm mù, cấm đốt token, chuẩn Skaaa).
      - `.agent/workflows/start_session.md`: Bắt đầu ca làm việc (Nạp hồ sơ công ty & Sổ bàn giao ca trước).
      - `.agent/workflows/end_session.md`: Kết thúc ca làm việc (Niêm phong bàn giao vào checkpoint & ghi sổ quyết định).
      - `.agent/workflows/1-client-intake.md`: Đồ nghề của Account/BA (Kịch bản phỏng vấn Giám Đốc lấy Logo, Ảnh, Menu, Footer).
      - `.agent/workflows/2-assembly-delivery.md`: Đồ nghề của Dev & QC (Lắp ráp block 1 nhịp, kiểm định và bàn giao).
      - `.agent/skills/designer-patterns.md`: Đồ nghề của Designer (Mẫu khung Header có ô chứa Logo, Hero có ô chứa Banner Image, Footer liên hệ và bảng Sai ➔ Đúng).
      - `.agent/skills/developer-blocks.md`: Đồ nghề của Developer (Cú pháp 6 Atomic blocks, PHP `$wpdb` và hướng dẫn bộ tool CLI).
      - `.agent/harness/db-tool.php`: Công cụ CLI kiểm tra CSDL phẳng an toàn.
      - `.agent/harness/block-tool.php`: Công cụ CLI kiểm định block & tạo trang test 1-nhịp.
    - **`.skaaa-ai/` (Tủ tài liệu nội bộ doanh nghiệp & Hồ sơ dự án):**
      - `.skaaa-ai/1-company-profile/system-map.md`: Hồ sơ năng lực & bản đồ công nghệ doanh nghiệp.
      - `.skaaa-ai/1-company-profile/brand-guidelines.md`: Quy chuẩn nhận diện thương hiệu & Design Tokens.
      - `.skaaa-ai/2-company-memory/decision-log.md`: Sổ tay ghi nhớ quyết định kiến trúc sếp chốt.
      - `.skaaa-ai/2-company-memory/checkpoint.md`: Sổ bàn giao ca kíp giữa các phiên làm việc.
      - `.skaaa-ai/3-project-dossier/client-brief.md`: Hồ sơ dự án cất giữ URL Logo thật, Ảnh thật và Copywriting sếp duyệt.
- [x] **Block Synthesizer & Validator Tool (`.agent/harness/block-tool.php` - Hoàn thành v1.2.0):**
  - Tiện ích kiểm định cú pháp Atomic Blocks chuẩn Gutenberg (`container`, `text`, `button`, `svg`, `code`, `loop`), bắt lỗi Flat DOM vi phạm thẻ HTML thô gây Gutenberg Invalid Content.
  - Bộ kiểm tra cú pháp Skaaapine / Alpine.js: bắt buộc `@click.prevent`, cấm sự kiện `onclick` thô, cảnh báo lồng `x-data` gây scope shadowing.
  - Hỗ trợ tạo WordPress page thử nghiệm 1-click trả về link preview (`--create-test-page`) và xem trước HTML qua `do_blocks()` (`--render`).
- [x] **Flat Database Inspector & Safe Query Runner (`.agent/harness/db-tool.php` - Hoàn thành v1.2.0):**
  - Script/Helper cho phép Agent tra cứu cấu trúc các bảng phẳng `skaaa_data_*` (tên bảng, danh sách cột, kiểu dữ liệu `text`, `number`, `json`, `relation`).
  - Hỗ trợ Agent query lấy dữ liệu mẫu an toàn mà không cần gõ lệnh `mysql` trực tiếp qua CLI (triệt tiêu lỗi MISTAKE-001 làm treo shell), tự động ép `LIMIT 50` và chặn các câu lệnh phá hoại nếu thiếu cờ `--force`.
  - Cơ chế Preflight check kết nối database thông minh, triệt tiêu trang lỗi HTML `wp_die` khi server chưa chạy.
- [x] **Tailwind JIT Pre-flight Checker (`.agent/harness/jit-tool.php` - Hoàn thành v1.2.1):**
  - Tiện ích kiểm định cú pháp Tailwind CSS JIT thời gian thực đối chiếu trực tiếp với từ điển `tailwind-rules.json` và cấu hình token offline.
  - Hỗ trợ kiểm tra chuỗi class rời (`--check="..."`), quét toàn bộ tệp giao diện / comment block Gutenberg (`--scan="..."`), xuất báo cáo dạng ANSI table hoặc JSON chuẩn (`--format=table|json`).
  - Tự động nhận diện và chẩn đoán các lỗi typo kinh điển của UX/UI Designer (`flex-center`, `text-bold`, `bg-slate900`, `w-300px`, `cursor-hand`) kèm gợi ý sửa nhanh (Hint).
  - Tích hợp cờ `--compile` hỗ trợ biên dịch và xem trước khối mã CSS chuẩn theo quy chuẩn Skaaa JIT Engine.
  - Chạy độc lập hoàn toàn (Standalone CLI), không phụ thuộc vào kết nối MySQL database của WordPress.

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
