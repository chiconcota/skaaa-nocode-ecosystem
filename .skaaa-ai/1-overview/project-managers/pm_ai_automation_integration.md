# PROJECT MANAGER: SKAAAI (AI COPILOT & BIDIRECTIONAL SYNC BRIDGE)
@status: 🟡 Planning | @target_milestone: MILESTONE 2 (AI COPILOT & AUTOMATION) | @last_update: 2026-09-22

> [!NOTE]
> Tài liệu này quản lý tiến độ phát triển, thiết kế kiến trúc và quy trình triển khai plugin **`Skaaai`** (AI Addon). Plugin này đóng vai trò kép: vừa là **Hạ tầng Trí tuệ Nhân tạo (Self-Documenting Context & AI Automation Nodes)** vừa là **Cầu nối Xuất bản Hai Chiều (Bidirectional Sync Bridge)** giữa máy tính cá nhân (Localhost Dev) và Website Publish (Hosting).

---

## 1. MỤC TIÊU CỐT LÕI (CORE GOALS)
1. **Self-Documenting AI Engine:** Tự động cung cấp từ điển JSON Schema (`ai-manifest.json`) và REST endpoint `/wp-json/skaaai/v1/context` để bất kỳ AI Agent nào khi mở website đều hiểu ngay toàn bộ cú pháp Blocks, Tailwind và Alpine.js mà không cần thư mục `.skaaa-ai`.
2. **Bidirectional Content Sync Engine:** Cho phép đồng bộ bài viết hai chiều giữa Localhost và Web Publish (`Push to Live` & `Pull from Live`).
3. **Định danh Toàn cầu `skaaa_uuid`:** Triệt tiêu hoàn toàn rủi ro xung đột ID tự tăng (`AUTO_INCREMENT`) của MySQL giữa 2 máy chủ.
4. **Cơ chế Chống Đè Dữ Liệu An Toàn (Triple Shield):** Pre-flight check kiểm tra thời gian sửa đổi, hiển thị hộp thoại giải quyết xung đột (Conflict Resolver), và tự động sao lưu Revisions trước khi cập nhật.
5. **Nút Bấm "🚀 Push to Live" 1-Click:** Tích hợp trực tiếp lên Gutenberg Toolbar giúp người dùng kiểm tra xong ở Local là đẩy lên Live trong 1 giây.
6. **AI Logic Nodes:** Mở rộng **Skaaa Logic Engine** với các Node AI Prompt (Gemini/OpenAI) và AI Parser trích xuất dữ liệu phi cấu trúc vào bảng phẳng MySQL.

---

## 2. TIẾN ĐỘ THỰC HIỆN (PHASED ROADMAP)

### 🟢 Phase 1: Khởi tạo Khung xương Plugin & Ghép nối (Core & Pairing) - Hoàn thành v1.0.0
- [x] Thiết lập thư mục và tệp chính `wp-content/plugins/skaaai/skaaai.php` (SemVer `1.0.0`, text domain `skaaai`).
- [x] Xây dựng class `Skaaai\Core` và trang Cài đặt `Skaaa Bridge & Sync` trong WP Admin.
- [x] Cơ chế cấu hình vai trò:
  - **Sender (Local Dev):** Lưu Remote Site URL + Pairing Key.
  - **Receiver (Publish Hosting):** Sinh Pairing Secret Key bảo mật (`skaaai_pair://...`).
- [x] Lưu trữ cấu hình an toàn vào bảng phẳng hệ thống `wp_skaaa_data_sys_settings`.
- [x] Bổ sung module **Remote Code & Node Deployer** sử dụng `WP_Filesystem` kết hợp lá chắn kiểm tra cú pháp PHP (Syntax Validator).

### ⚪ Phase 2: Self-Documenting AI Context Engine
- [ ] Tạo file `wp-content/plugins/skaaai/ai-manifest.json` định nghĩa chuẩn JSON Schema của tất cả Skaaa Blocks (`container`, `text`, `button`, `svg`, `code`) và cú pháp Alpine.
- [ ] Xây dựng class `Skaaai_Context_Engine` đăng ký endpoint `GET /wp-json/skaaai/v1/context`.
- [ ] Tự động trích xuất các bảng phẳng `skaaa_data_*`, Organisms và Design Tokens để cung cấp ngữ cảnh thời gian thực cho AI Agent.

### ⚪ Phase 3: Động cơ Đồng bộ Hai Chiều (Bidirectional Sync Engine)
- [ ] Xây dựng class `Skaaai_Sync_Bridge`:
  - Hook `wp_insert_post` tự động gán `_skaaa_uuid` (`wp_generate_uuid4()`) khi tạo bài viết mới.
  - Endpoint tiếp nhận: `POST /wp-json/skaaai/v1/push-post` (Xác thực Pairing Key + Pre-flight timestamp check).
  - Tự động gọi `wp_save_post_revision()` tạo điểm khôi phục an toàn trước khi ghi đè.
  - Hoán đổi URL tự động (`http://...local` ➔ `https://...com`).
  - Tự động kích hoạt JIT CSS Compiler để cache style cho bài viết mới trên server.
  - Endpoint xuất bài viết: `GET /wp-json/skaaai/v1/pull-posts` phục vụ kéo bài về Localhost.

### ⚪ Phase 4: Giao diện Người dùng (Gutenberg Toolbar & Post List Badges)
- [ ] Tạo script `assets/js/skaaai-editor-toolbar.js` gắn nút **"🚀 Push to Live"** lên thanh Toolbar của Gutenberg.
- [ ] Thêm cột trạng thái **"Skaaa Sync"** vào bảng `wp-admin/edit.php` (hiển thị badge: `🟢 In Sync`, `⬆️ Local Ahead`, `⬇️ Remote Ahead`).
- [ ] Hộp thoại giải quyết xung đột (Conflict Resolution Modal) khi phát hiện Live có sửa đổi mới hơn.

### ⚪ Phase 5: Tích hợp AI Logic Nodes (Milestone 2 DAG Automation)
- [ ] Class `Skaaai_Node_Prompt`: Node gọi Gemini / OpenAI API hỗ trợ nội suy biến `{{ ... }}`.
- [ ] Class `Skaaai_Node_Parser`: Node trích xuất dữ liệu JSON từ văn bản phi cấu trúc.
- [ ] Đăng ký nodes vào filter `skaaa_logic_registered_nodes` của `Skaaa Logic Engine`.

### ⚪ Phase 6: Kiểm thử E2E & Đóng gói Phân phối
- [ ] Kiểm thử Push/Pull bài viết giữa 2 môi trường.
- [ ] Kiểm thử kịch bản xung đột và khôi phục Revision.
- [ ] Cập nhật `zip-all.js` để đóng gói tự động `skaaai-v1.0.0.zip` cùng hệ sinh thái.

---

## 3. TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA)
1. Plugin **Skaaai** hoạt động độc lập, tuân thủ nguyên tắc Decoupled và không gọi trực tiếp class của các plugin khác.
2. Endpoint `/wp-json/skaaai/v1/context` trả về đầy đủ đặc tả blocks để bất kỳ AI Agent nào cũng có thể hiểu và làm việc ngay cả khi không có thư mục `.skaaa-ai`.
3. Bài viết được tạo ở Local khi bấm "Push to Live" sẽ xuất hiện trên Web Publish trong vòng 1-2 giây với đầy đủ cấu trúc block, hình ảnh và JIT CSS.
4. Mọi bài viết đều có mã định danh toàn cầu `skaaa_uuid`, tuyệt đối không làm sai lệch hay đè nhầm ID giữa 2 database.
5. Khi có xung đột dữ liệu, hệ thống chặn đứng việc ghi đè mù quáng và có lịch sử Revision để khôi phục 100%.
