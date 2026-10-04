# MINI PROJECT MANAGER: 📥 SKAAAI BIDIRECTIONAL SYNC (PULL FROM LIVE)
@file: pm_skaaai_pull_from_live.md | @status: COMPLETED | @version: Skaaai v1.5.1 | @target: Skaaai Plugin

---

## 🎯 1. TỔNG QUAN DỰ ÁN & MỤC TIÊU CỐT LÕI
Dự án này hoàn thiện mảnh ghép thứ hai của **Cầu nối Đồng bộ 2 Chiều (Bidirectional Sync Bridge)** giữa Localhost và Live Webhost: **Tính năng Kéo Dữ Liệu từ Live về Máy Cục Bộ (Pull from Live)**.

Sau khi tính năng **Push to Live (v1.3.0 - v1.4.3)** đã hoạt động ổn định và tin cậy, tính năng **Pull from Live** giúp giải quyết bài toán:
1. **Biết được khi nào Live có thay đổi:** Tự động phát hiện khi bài viết/trang trên máy chủ Live được chỉnh sửa trực tiếp (`remote_modified > local_modified`), hiển thị huy hiệu trực quan `⬇️ Remote Ahead`.
2. **Kéo nội dung an toàn về Localhost (Safe Pull):** Cho phép người dùng bấm 1 nút để kéo toàn bộ nội dung, khối Gutenberg và metadata từ Live về ghi đè lên Localhost.
3. **Bảo vệ dữ liệu bằng WordPress Revision (Zero Data Loss):** Luôn tự động tạo bản sao lưu Revision trên Localhost trước khi ghi đè, cho phép Undo khôi phục trạng thái cũ chỉ với 1 cú click.
4. **Đảo chiều hoàn hảo quy trình biến đổi dữ liệu (Reverse Transformers):**
   - Đảo tên miền: Hoán đổi URL Live (`https://lytatthanh.com`) về Local URL (`http://lytatthanhloca.local` hoặc `http://...`).
   - Đảo tiền tố CSDL phẳng: Hoán đổi prefix Live (`wpxi_skaaa_data_*`) về prefix Localhost (`wp_skaaa_data_*`).
   - Tải ngược hình ảnh (Reverse Sideload Media): Tải các hình ảnh mới tải lên Live về thư mục `uploads/` của Localhost và đăng ký vào Local Media Library.

---

## 📋 2. ROADMAP TRIỂN KHAI CHI TIẾT (4 PHASES)

```text
Phase 1: Backend REST API Export & Diff Checker (Receiver/Live)
   │
   ▼
Phase 2: Local Pull Engine & Reverse Data Transformers (Sender/Local)
   │
   ▼
Phase 3: Frontend UI (Gutenberg Toolbar & Post List Column)
   │
   ▼
Phase 4: E2E Verification, SemVer v1.5.0 & Release Packaging
```

---

### 🟢 Phase 1: Backend REST API Export & Diff Checker (Phía Receiver/Live)
*Mục tiêu: Xây dựng các cổng API an toàn trên máy chủ Live để Sender có thể truy vấn trạng thái và trích xuất dữ liệu bài viết/hệ thống.*

- [x] **Task 1.1: Endpoint REST API xuất bài viết đơn lẻ (`GET /export-post`)**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-rest-api.php` & `class-skaaai-export-service.php`
  - Đăng ký route: `GET /wp-json/skaaai/v1/export-post`.
  - Tham số tiếp nhận: `uuid` (chuẩn) hoặc `slug` hoặc `post_id`.
  - Quyền truy cập: Bắt buộc xác thực token bảo mật `X-Skaaai-Token` qua `verify_token_permission()`.
  - Dữ liệu trả về (Payload):
    - `uuid`: Định danh toàn cục `_skaaa_uuid`.
    - `title`: Tiêu đề bài viết.
    - `content`: Toàn bộ mã khối Gutenberg (đã bọc `wp_slash()` để bảo toàn Unicode/SVG).
    - `slug`: Đường dẫn URL (`post_name`).
    - `post_type`: Loại bài viết (`post`, `page`, hoặc custom post types).
    - `post_status`: Trạng thái (`publish`, `draft`...).
    - `last_modified`: Unix timestamp thời điểm chỉnh sửa cuối cùng trên Live.
    - `permalink`: URL xem trực tiếp trên Live.
    - `featured_media_url`: URL ảnh đại diện (nếu có).
    - `meta`: Các metadata tùy biến của Skaaa.
- [x] **Task 1.2: Endpoint đối soát trạng thái hàng loạt (`POST /check-posts-status`)**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-rest-api.php` & `class-skaaai-export-service.php`
  - Đăng ký route: `POST /wp-json/skaaai/v1/check-posts-status`.
  - Tiếp nhận danh sách đối soát từ Sender: Mảng các cặp `[ { "uuid": "...", "local_modified": 1712345678 }, ... ]`.
  - Phân tích & Trả về trạng thái từng bài:
    - `synced`: Cả 2 bên khớp thời gian (sai số <= 2s).
    - `remote_ahead`: Live mới hơn Local (`remote_modified > local_modified + 2s`).
    - `local_ahead`: Local mới hơn Live (`local_modified > remote_modified + 2s`).
    - `not_found`: Bài viết chưa tồn tại trên Live.
- [x] **Task 1.3: Endpoint xuất cấu hình Hệ sinh thái tổng thể (`GET /export-ecosystem`)**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-rest-api.php`, `class-skaaai-export-service.php` & `class-skaaai-sync-ecosystem-sender.php`
  - Đăng ký route: `GET /wp-json/skaaai/v1/export-ecosystem`.
  - Xuất dữ liệu bảng phẳng hệ thống: Design Tokens (`sys_presets`), Organisms (`sys_organisms`), Theme Templates (`sys_theme_templates`), và Logic Workflows (`sys_workflows`).

---

### 🟢 Phase 2: Local Pull Engine & Reverse Data Transformers (Phía Sender/Localhost)
*Mục tiêu: Xây dựng động cơ kéo nội dung từ xa về máy cục bộ, biến đổi địa chỉ và prefix ngược lại chuẩn xác, tạo Revision an toàn.*

- [x] **Task 2.1: Bộ Động Cơ Kéo Bài Viết `Sync_Post::pull_post_from_remote()`**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-sync-pull.php` & `class-skaaai-sync-post.php`
  - Gửi yêu cầu `GET` có kèm token xác thực đến endpoint `/wp-json/skaaai/v1/export-post` của Live host.
  - Kiểm tra tính toàn vẹn của payload nhận về.
- [x] **Task 2.2: Cơ chế Lưu Trữ An Toàn & Tạo Revision (Revision-First Safe Overwrite)**
  - Tự động gọi `wp_save_post_revision( $local_post_id )` trước khi cập nhật dữ liệu mới.
  - Bọc hàm `wp_slash()` cho tiêu đề và nội dung để bảo vệ ký tự JSON Gutenberg.
  - Sử dụng `kses_remove_filters()` và thiết lập ngữ cảnh Administrator (`wp_set_current_user`) trong suốt quá trình ghi bài để bảo toàn 100% SVG code.
  - Cập nhật `_skaaa_last_synced` bằng timestamp hiện tại và lưu `_skaaa_remote_permalink`.
- [x] **Task 2.3: Bộ Hoán Đổi Ngược (Reverse Transformers: Domain & Table Prefix)**
  - **Reverse Domain Rewriter:** Thay thế toàn bộ URL domain máy chủ Live (`$remote_url`) thành domain máy chủ Localhost (`site_url()`).
  - **Reverse Table Prefix Rewriter:** Quét các thuộc tính `sourceTable` của block `Skaaa Loop` và tự động hoán đổi prefix Live (`wpxi_skaaa_data_*`) về prefix Localhost (`wp_skaaa_data_*`).
- [x] **Task 2.4: Bộ Tải Ảnh Ngược (Reverse Sideload Media)**
  - Quét tìm các URL ảnh thuộc domain Live nằm trong nội dung bài viết.
  - Tải file ảnh về thư mục `uploads/` của Localhost, chèn vào WordPress Media Library qua `wp_insert_attachment()`.
  - Hoán đổi URL ảnh trong nội dung bài viết sang URL mới của Localhost.
  - Kích hoạt hook `skaaa_after_post_synced` để biên dịch lại CSS Tailwind JIT trên Localhost.

---

### 🟢 Phase 3: Giao Diện Người Dùng (Gutenberg Toolbar & Post List UI)
*Mục tiêu: Cung cấp trải nghiệm người dùng trực quan, rõ ràng, dễ phân biệt giữa Push và Pull.*

- [x] **Task 3.1: Tích hợp nút "📥 Pull from Live" trong Gutenberg Block Editor**
  - File: `wp-content/plugins/skaaai/assets/js/skaaai-editor-toolbar.js` & `assets/css/skaaai-editor-toolbar.css`
  - Thêm nút **"📥 Pull from Live"** trên thanh Header Toolbar (bên cạnh nút `🚀 Push to Live`) và panel Status & Visibility trong Document Sidebar.
  - Hộp thoại xác nhận an toàn (Safety Confirmation Modal):
    - Cảnh báo: *"Hành động này sẽ tải nội dung mới nhất từ Live về ghi đè lên bài viết hiện tại trên Localhost. Một bản sao lưu (Revision) sẽ được tự động tạo trước khi ghi đè. Bạn có chắc chắn muốn tiếp tục?"*
    - Nút: `[Hủy bỏ]` và `[Xác nhận Kéo về (Pull & Overwrite)]`.
  - Tự động nạp lại bài viết trong editor sau khi pull thành công mà không làm mất trạng thái.
- [x] **Task 3.2: Cập nhật Cột "Skaaa Sync" trên Danh sách Bài viết (`edit.php`)**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-post-sync-ui.php` & `assets/css/skaaai-editor-toolbar.css`
  - Bổ sung huy hiệu:
    - `⬇️ Remote Ahead` (Màu tím nhạt): Khi Live có nội dung mới hơn Localhost.
    - `🟢 Synced` (Màu xanh): Nội dung 2 bên đồng nhất.
    - `⬆️ Local Ahead` (Màu vàng/cam): Localhost có sửa đổi mới chưa đẩy lên Live.
    - `⚪ Not Synced` (Màu xám): Bài viết chưa từng đồng bộ.
  - Bổ sung nút bấm **"Pull"** (`dashicons-cloud-download`) ngay cạnh nút **"Push"** trên từng dòng.
- [x] **Task 3.3: Thao tác Kéo hàng loạt & Tự động đối soát Remote Status**
  - Thêm bulk action: `📥 Pull from Live (Skaaa)` trong dropdown bulk actions của `edit.php`.
  - Tự động chạy tiến trình ngầm `checkRemoteStatuses()` gửi yêu cầu đối soát tới `/check-posts-status` và tự động gắn huy hiệu `⬇️ Remote Ahead`.

---

### 🟢 Phase 4: Kiểm Thử E2E, Đóng Gói SemVer & Cập Nhật Bộ Nhớ
*Mục tiêu: Kiểm thử thực tế quy trình kéo dữ liệu 2 chiều, đảm bảo không mất mát dữ liệu và đóng gói bản phát hành v1.5.0.*

- [ ] **Task 4.1: Kiểm thử Kéo bài viết đơn lẻ (Single Post Pull E2E)**
  - Sửa đổi nội dung một bài viết trên website Live thử nghiệm (`lytatthanhloca` đóng vai trò Live hoặc trên chính host Live).
  - Kiểm tra xem danh sách bài viết trên Sender có hiển thị đúng huy hiệu `⬇️ Remote Ahead` hay không.
  - Bấm nút **"Pull"**: Xác nhận nội dung mới được kéo về Localhost, icon SVG nguyên vẹn, khối Skaaa Loop nhận diện đúng prefix `wp_`, URL ảnh chuyển về local.
  - Kiểm tra mục Revisions của WordPress: Xác nhận bản sao lưu cũ vẫn tồn tại nguyên vẹn.
- [ ] **Task 4.2: Kiểm thử Kéo bài viết từ Gutenberg Editor Toolbar**
  - Mở bài viết trong Gutenberg trên Localhost, bấm **"📥 Pull from Live"**.
  - Kiểm tra hộp thoại xác nhận, thanh tiến trình và thông báo kết quả.
- [x] **Task 4.3: Nâng số phiên bản SemVer lên `v1.5.0` (Major Feature Milestone)**
  - File: `skaaai.php`, `architecture.md`, `system_map.md`, `decision-log.md`.
  - Cập nhật tài liệu kiến trúc và bộ nhớ 4 ngăn kéo.
- [x] **Task 4.4: Đóng gói bản cài đặt `skaaai-v1.5.0.zip`**
  - Chạy script đóng gói tự động `zip-all.js` tạo `skaaai-v1.5.0.zip` (0.15 MB).
  - Đồng bộ và giải nén sang paired site `lytatthanhloca`.

---

### 🟢 Phase 5: Mở Rộng Kéo Toàn Diện Hệ Sinh Thái & Cổng Đối Soát 2 Tầng (Full Ecosystem Pull & Two-Tier Diff Gate)
*Mục tiêu: Đưa tính năng Pull đạt mức độ cân xứng 100% với Push — kéo trọn vẹn cả 7 thành phần hệ sinh thái và minh bạch hóa chênh lệch bằng bản Diff đối soát trước khi duyệt ghi đè.*

- [x] **Task 5.1: Hoàn thiện Backend REST API Export trọn vẹn 7 thành phần trên Live**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-export-service.php` & `class-skaaai-sync-ecosystem-sender.php`
  - Endpoint `GET /wp-json/skaaai/v1/export-ecosystem` mặc định xuất đủ 7 scopes: `presets`, `organisms`, `theme_templates`, `workflows`, `custom_tables`, `pages`, `settings`.
  - Bổ sung `table_prefix` vào gói payload để chuẩn hóa quá trình hoán đổi CSDL phẳng ngược.
- [x] **Task 5.2: Xây dựng Động cơ Kéo Hệ Sinh Thái Cục Bộ (Local Pull Engine & Reverse Transformers)**
  - File mới: `wp-content/plugins/skaaai/inc/class-skaaai-sync-ecosystem-pull.php`
  - Tự động gọi Live lấy gói dữ liệu, chạy các bộ chuyển đổi ngược (Reverse Transformers):
    - Đổi prefix CSDL ngược: từ Live (vd: `wpxi_skaaa_data_*`) về Localhost (`wp_skaaa_data_*`).
    - Đổi domain ngược: từ URL Live về `site_url()` Localhost.
    - Reverse Sideload Media: Tự động tải ảnh nhúng từ Live về thư mục `uploads/` trên Localhost.
    - Áp dụng vào CSDL Localhost qua `Sync_Ecosystem::process_incoming_ecosystem()`.
- [x] **Task 5.3: Cổng Đối Soát Pre-flight Diff Gate cho Chiều Kéo (Dry-run Diff Review)**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-ecosystem-sync-ui.php` & `assets/js/skaaai-ecosystem-sync.js`
  - Đăng ký AJAX `skaaai_ecosystem_pull_diff` và `skaaai_ecosystem_pull_execute`.
  - Tích hợp nút bấm **`📥 1-Click Full Pull from Live`** trên Admin Cockpit và menu Admin Bar.
  - Hộp thoại Diff Modal hiển thị chi tiết số lượng Tokens, Organisms, Workflows, Smart Object Tables và Pages sẽ được cập nhật.
- [x] **Task 5.4: Modal Xem Trước Diff Đơn Bài Trước Khi Ghi Đè (Single Post Diff Preview Modal)**
  - File: `class-skaaai-sync-pull.php`, `class-skaaai-post-sync-ui.php`, `assets/js/skaaai-post-list.js`, `assets/js/skaaai-editor-toolbar.js`
  - Bổ sung endpoint AJAX `skaaai_get_post_diff`: so sánh Tiêu đề, Ngày giờ sửa đổi, Ảnh đại diện, và Số lượng Blocks giữa Live và Local.
  - Trên danh sách bài viết: Mở modal trực quan `#skaaai-post-diff-modal` so sánh song song Local vs Remote kèm nút `[Approve & Overwrite Local]`.
  - Trên Gutenberg Editor Toolbar: Nút "📥 Pull from Live" tự động quét Diff và hiển thị cảnh báo chi tiết trước khi xác nhận.

---

## 🛡️ 3. NGUYÊN TẮC BẢO MẬT & AN TOÀN DỮ LIỆU (THE PULL SAFEGUARDS)
1. **Revision-First Policy:** Tuyệt đối không bao giờ ghi đè trực tiếp lên bài viết Localhost mà không tạo Revision trước. Người dùng luôn có đường lùi (Undo).
2. **Reverse Domain Isolation:** Cấm tuyệt đối để sót URL domain Live (`lytatthanh.com`) trong nội dung bài viết trên Localhost sau khi Pull. Mọi link nội bộ và ảnh bắt buộc phải trỏ về Localhost.
3. **Prefix Decoupling:** Block attributes chứa tên bảng CSDL (`sourceTable`) phải luôn được chuyển về prefix của máy đang thực thi (`$wpdb->prefix . 'skaaa_data_'`).
4. **Token Authentication:** Mọi request kéo dữ liệu từ Live đều phải được bảo vệ bởi Token bí mật `X-Skaaai-Token` 64 ký tự. Không mở công khai nội dung bài nháp hoặc dữ liệu nhạy cảm ra ngoài internet.

---

## 📊 4. TIÊU CHUẨN NGHIỆM THU (ACCEPTANCE CRITERIA)
1. **Phát hiện chênh lệch:** Khi một bài viết trên Live có `post_modified` mới hơn Localhost, hệ thống gắn huy hiệu `⬇️ Remote Ahead` chính xác.
2. **Thao tác 1-Click Pull:** Bấm nút **"Pull"** (ở danh sách hoặc trong Gutenberg), toàn bộ bài viết từ Live được đồng bộ về Localhost thành công trong vòng dưới 3 giây.
3. **Hiển thị chuẩn xác:** Sau khi Pull, các khối SVG, Skaaa Loop, hình ảnh và bố cục hiển thị chính xác 100% trên Localhost như trên Live.
4. **Có đường lùi:** Trong phần lịch sử chỉnh sửa (Revisions) của WordPress trên Localhost, luôn xuất hiện bản sao lưu của phiên bản ngay trước khi Pull.
