# CHECKPOINT - PHẦN BÀN GIAO TIẾN ĐỘ
*Ngày cập nhật: 2026-10-03 | Phiên làm việc: Khắc Phục Lỗi Mất Icon SVG (KSES Bypass), Lệch Database Prefix Khối Loop & Cập Nhật db-tool CLI (Skaaai v1.4.3 & Skaaa No-Code Design v2.4.7)*

---

## 1. Thông Tin Môi Trường & Nhánh Git
- **Git Branch:** `feature/skaaai-core`
- **Thư mục làm việc (Active Workspace):** `/home/chiconcota/Local Sites/skaaa-no-code-ecosystem/app/public/`
- **Website Thử Nghiệm Kết Nối (Paired Site):** `/home/chiconcota/Local Sites/lytatthanhloca/app/public/`
- **Máy Chủ Live Đích (Production):** `https://lytatthanh.com` (Database prefix: `wpxi_`)
- **Phiên bản Hệ Sinh Thái Hiện Tại:**
  - `Skaaai: v1.4.3` (🟢 Vô hiệu hóa KSES và thiết lập ngữ cảnh Administrator khi sync để bảo tồn 100% SVG, chuẩn hóa rewrite database table prefix)
  - `Skaaa Canvas Theme: v1.0.1` (🟢 Stable)
  - `Skaaa No-Code Design: v2.4.7` (🟢 Tự động trích xuất suffix và chuẩn hóa prefix CSDL cho khối Loop)
  - `Skaaa Data Pro: v1.3.3` (🟢 Stable)
  - `Skaaa Logic Engine: v1.3.0` (🟢 Stable)

---

## 2. Danh Sách Tệp Tin Đã Tạo & Chỉnh Sửa Trong Phiên (File Change Manifest)

### A. Plugin Skaaai (`wp-content/plugins/skaaai/`)
1. [inc/class-skaaai-sync-post.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai/inc/class-skaaai-sync-post.php):
   - Bổ sung `kses_remove_filters()` và thiết lập ngữ cảnh Administrator (`wp_set_current_user`) bao quanh `wp_insert_post` và `wp_update_post`, khôi phục lại qua `kses_init_filters()`. Ngăn chặn việc WordPress Core xóa trắng thuộc tính `svgCode` trong block `skaaa-svg`.
   - Bổ sung hàm tiện ích `rewrite_table_prefixes()` hoán đổi tiền tố bảng phẳng CSDL từ sender sang receiver.
2. [inc/class-skaaai-sync-ecosystem.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai/inc/class-skaaai-sync-ecosystem.php):
   - Bao bọc toàn bộ vòng lặp `apply_pages()` với cơ chế bypass KSES và ngữ cảnh Administrator.
   - Bổ sung `Sync_Post::rewrite_table_prefixes()` cho nội dung tất cả các trang khi đồng bộ hệ sinh thái.
3. [scaffold/.agent/harness/db-tool.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai/scaffold/.agent/harness/db-tool.php):
   - Nâng cấp `handle_schema()` và `handle_sample()` bằng regex `preg_match( '/(?:^|_)skaaa_data_(.+)$/', ... )` nhận diện tên bảng phẳng thông minh (chấp nhận cả `projects`, `skaaa_data_projects`, `wp_skaaa_data_projects`).
4. [skaaai.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai/skaaai.php):
   - Nâng số phiên bản SemVer lên **`v1.4.3`** (`SKAAAI_VERSION = '1.4.3'`).
5. **Gói cài đặt ZIP:** `skaaai-v1.4.3.zip` (137 KB) tại cả 2 site local.

### B. Plugin Skaaa No-Code Design (`wp-content/plugins/skaaa-no-code-design/`)
1. [build/skaaa-loop/render.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaa-no-code-design/build/skaaa-loop/render.php):
   - Sử dụng regex `preg_match( '/(?:^|_)skaaa_data_(.+)$/', ... )` để trích xuất suffix bảng thực tế, sau đó luôn ghép với `$wpdb->prefix . 'skaaa_data_'` để truy vấn chính xác bảng trên máy chủ Live (bất kể prefix là `wp_`, `wpxi_` hay prefix tùy chỉnh).
2. [skaaa-no-code-design.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaa-no-code-design/skaaa-no-code-design.php):
   - Nâng số phiên bản SemVer lên **`v2.4.7`**.
3. **Gói cài đặt ZIP:** `skaaa-no-code-design-v2.4.7.zip` (530 KB) tại cả 2 site local.

---

## 3. Danh Sách Lỗi Đã Giải Quyết Triệt Để (Resolved Issues Log)
1. **Lỗi nuốt mất ký tự gạch chéo ngược `\` và crash bộ phân tích JSON Gutenberg (`wp_slash` issue):**
   - *Hiện tượng:* Các chuỗi Unicode như `\u0026` biến thành `u0026amp;`, dấu ngoặc kép thoát chuỗi `\"` bị mất làm vỡ block attributes.
   - *Khắc phục:* Bọc `wp_slash()` cho toàn bộ nội dung bài viết trước khi nạp vào `wp_insert_post` / `wp_update_post`.
2. **Lỗi khối Skaaa Loop không hiển thị dữ liệu trên Live Webhost (`<!-- Skaaa Loop: No data found -->`):**
   - *Hiện tượng:* Khối Loop 3 project cards hoạt động trên Localhost (`wp_`), nhưng trên Live (`wpxi_`) không hiển thị do thuộc tính block lưu cứng tên bảng `wp_skaaa_data_projects`.
   - *Khắc phục:* Thiết lập cơ chế tự động nhận diện suffix bảng ở `build/skaaa-loop/render.php` kết hợp bộ `rewrite_table_prefixes()` trong tiến trình đồng bộ của Skaaai.
3. **Lỗi toàn bộ icon SVG bị mất trắng sau khi đồng bộ lên Live:**
   - *Hiện tượng:* Vùng icon bo góc tại "Bắt Đầu Dự Án Của Bạn" và "Mô Hình Solopreneur" bị rỗng ruột.
   - *Nguyên nhân:* Yêu cầu REST API chạy dưới dạng unauthenticated (`get_current_user_id() === 0`), WordPress kích hoạt bộ lọc bảo mật `wp_filter_post_kses()` tự động xóa sạch thẻ `<svg>` trong thuộc tính JSON comment Gutenberg (`"svgCode":""`).
   - *Khắc phục:* Vô hiệu hóa tạm thời KSES (`kses_remove_filters()`) và thiết lập ngữ cảnh Administrator (`wp_set_current_user`) trong suốt quá trình ghi bài của Skaaai Sync, khôi phục lại trạng thái ban đầu (`kses_init_filters()`) ngay sau khi hoàn tất.
4. **Lỗi CLI `db-tool.php` khi chạy dưới ngữ cảnh CLI:**
   - *Hiện tượng:* Người dùng hoặc AI gõ cú pháp tên bảng rút gọn (`projects`) hoặc đầy đủ (`wp_skaaa_data_projects`) không đồng nhất.
   - *Khắc phục:* Nâng cấp regex trích xuất linh hoạt tên bảng phẳng.

---

## 4. Tình Trạng Kiểm Thử E2E (E2E Test Status)
- Quản lý theo: [.skaaa-ai/1-overview/project-managers/pm_full_ecosystem_sync.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/.skaaa-ai/1-overview/project-managers/pm_full_ecosystem_sync.md)
  - **Phase 1 (Payload & Media):** 🟢 100% Hoàn thành (3/3 tasks)
  - **Phase 2 (Endpoints & Engine):** 🟢 100% Hoàn thành (4/4 tasks)
  - **Phase 3 (UI & Admin Bar):** 🟢 100% Hoàn thành (3/3 tasks)
  - **Phase 4 (Kiểm thử Live & Đóng gói):** 🟡 70% Hoàn thành (Đã đóng gói v1.4.3 & v2.4.7, test `u0026amp;` PASS, test hình ảnh chân dung PASS; đang chờ người dùng nạp zip lên live và bấm re-sync để hoàn tất nghiệm thu SVG + Loop cards).

---

## 5. Kế Hoạch Bàn Giao Phiên Kế Tiếp (Next Actions Checklist)
1. **Nghiệm thu hiển thị trên Live Webhost (`https://lytatthanh.com`):**
   - Hướng dẫn User nạp 2 file ZIP lên Live: `skaaa-no-code-design-v2.4.7.zip` và `skaaai-v1.4.3.zip`.
   - Bấm nút **⚡ 1-Click Full Ecosystem Sync** trên WordPress Admin Bar tại Localhost (`lytatthanhloca`).
   - Xác nhận khối **Skaaa Loop** (3 project cards) và toàn bộ **icon SVG** hiển thị rực rỡ, chính xác trên trang chủ Live.
2. **Khảo sát & Thiết kế Tính năng "Pull from Live" (Bidirectional Sync 2 chiều):**
   - *Yêu cầu:* Khi Online có thay đổi thì Offline nhận biết được để kéo (Pull) về.
   - *Bước 1:* Thiết kế REST API endpoint trên Live: `GET /wp-json/skaaai/v1/export-post` hoặc `export-ecosystem`.
   - *Bước 2:* Xây dựng giao diện hiển thị trạng thái phát hiện thay đổi (`⬇️ Remote Ahead`) trên danh sách bài viết (`edit.php`) khi `remote_modified > local_modified`.
   - *Bước 3:* Tích hợp nút bấm **"📥 Pull from Live"** trên Gutenberg Toolbar và danh sách bài viết để kéo nội dung trực tuyến về ghi đè an toàn xuống Localhost (tạo Revision sao lưu trước khi ghi đè).
