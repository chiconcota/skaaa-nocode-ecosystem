# MINI PROJECT MANAGER: 🚀 1-CLICK FULL ECOSYSTEM SYNC & CORE HOTFIX
@file: pm_full_ecosystem_sync.md | @status: IN_PROGRESS | @version: Skaaai v1.4.0 | @target: Skaaai Plugin

---

## 🎯 1. TỔNG QUAN DỰ ÁN & MỤC TIÊU CỐT LÕI
Dự án mini này giải quyết triệt để 3 vấn đề thực tế phát sinh trong quá trình đẩy dữ liệu từ Localhost lên Live Webhost (`lytatthanh.com`), đồng thời nâng cấp tính năng **Đồng Bộ Toàn Diện 1-Click (Full Ecosystem Sync)**:

1. **Triệt tiêu lỗi hiển thị `u0026amp;` & ký tự xuống dòng:** Khắc phục lỗi WordPress Core `stripslashes()` nuốt mất dấu gạch chéo ngược `\` trong JSON attribute của Gutenberg blocks.
2. **Triệt tiêu lỗi nạp Media & gãy ảnh chân dung:** Đảo thứ tự thực thi (Sideload Media trước, Rewrite Domain sau), mở rộng hỗ trợ đường dẫn ảnh tương đối `/wp-content/uploads/` và URL escape gạch chéo `\/`.
3. **Chống trùng lặp bài viết (`-2`):** Bổ sung thuật toán thông minh nhận diện theo Slug URL khi bài viết trên Live chưa có thẻ `_skaaa_uuid`.
4. **Năng lực 1-Click Full Ecosystem Sync:** Tạo 1 nút bấm duy nhất trên Admin Cockpit và Admin Bar giúp đồng bộ toàn bộ Hệ sinh thái (Design Tokens, Organisms HeaderBar/FooterBar, Theme Templates và All Pages) sang Online chỉ trong 1 thao tác.

---

## 📋 2. ROADMAP TRIỂN KHAI CHI TIẾT (4 PHASES)

```text
Phase 1: Core Hotfix (Slash, Media, Slug-Pair)
   │
   ▼
Phase 2: Backend REST API Full Ecosystem Engine
   │
   ▼
Phase 3: Frontend UI (Admin Cockpit & Admin Bar Quick Sync)
   │
   ▼
Phase 4: E2E Verification & Release Package v1.3.1
```

---

### 🟢 Phase 1: Sửa Lỗi Lõi Đồng Bộ Đơn Bài (Core Sync Hotfix)
*Mục tiêu: Đảm bảo khi đẩy bất kỳ bài viết nào, nội dung và hình ảnh đều hiển thị chuẩn 100% không tì vết.*

- [x] **Task 1.1: Khắc phục lỗi mất dấu gạch chéo ngược `\` (`u0026amp;` fix)**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-sync-post.php`
  - Bọc hàm `wp_slash()` cho `$processed_content` và `$title` trước khi gọi `wp_update_post()` và `wp_insert_post()`.
  - Bảo vệ 100% ký tự Unicode (`\u0026`), dấu xuống dòng (`\n`), và dấu ngoặc kép SVG (`\"`) trong attributes JSON của Gutenberg.
- [x] **Task 1.2: Tái cấu trúc bộ xử lý Sideload Media & đường dẫn ảnh tương đối**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-sync-post.php` & `class-skaaai-rest-api.php`
  - Bổ sung endpoint `/upload-media` nhận file nhúng trực tiếp từ Sender.
  - Tự động phát hiện ảnh local trên ổ đĩa, đẩy trực tiếp lên Live Media Library và hoán đổi URL chuẩn xác.
  - Đảo thứ tự: Thực hiện `sideload_remote_images` **TRƯỚC**, sau đó mới gọi `rewrite_domain_urls`.
  - Mở rộng regex quét ảnh: Nhận diện cả URL tuyệt đối (`http...`) và URL tương đối (`/wp-content/uploads/...`).
- [x] **Task 1.3: Cơ chế chống tạo bài trùng lặp (`-2`) qua Slug Fallback**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-sync-post.php`
  - Bổ sung `get_post_id_by_slug()`: Trong trường hợp không tìm thấy bài theo `_skaaa_uuid`, thực hiện tìm kiếm bài viết cũ theo `post_name` (slug) và `post_type`.
  - Nếu tìm thấy: Gắn thẻ `_skaaa_uuid` vào bài viết đó và cập nhật trực tiếp (In-place update), ngăn chặn hoàn toàn việc WordPress tự sinh slug `-2`.

---

### 🟢 Phase 2: Động Cơ Đồng Bộ Toàn Bộ Hệ Sinh Thái & Cấu Hình (Full Ecosystem & Setup Engine)
*Mục tiêu: Xây dựng động cơ trích xuất, đóng gói, kiểm tra đối soát (Dry-run) và tiếp nhận toàn bộ Hệ sinh thái Skaaa (Tokens, Header/Footer, Pages, Settings) sang Online an toàn 100%.*

- [x] **Task 2.1: Xây dựng Endpoint REST API `/sync-ecosystem` (Hỗ trợ 2 chế độ: Dry-run & Execute)**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-rest-api.php`
  - Đăng ký route REST API: `POST /wp-json/skaaai/v1/sync-ecosystem`.
  - Xác thực bảo mật hai đầu: Kiểm tra token `X-Skaaai-Token` qua `verify_token_permission()`.
  - Hỗ trợ cờ `dry_run = true`: Chỉ phân tích và trả về bản tóm tắt đối soát (Diff Summary), **hoàn toàn không ghi đè CSDL** để phục vụ bước duyệt trước của người dùng.
- [x] **Task 2.2: Đồng bộ Bảng Phẳng Hệ Thống (Skaaa Flat Tables: Presets, Organisms, Templates, Logic Workflows)**
  - File mới: `wp-content/plugins/skaaai/inc/class-skaaai-sync-ecosystem.php` & `class-skaaai-sync-ecosystem-sender.php`
  - **Design Tokens (`wp_skaaa_data_sys_presets`):** Đồng bộ các presets màu sắc, font chữ; tự động kích hoạt `Design_Tokens_Compiler` biên dịch lại physical cache `tokens.json` & CSS variables trên Live (0ms).
  - **Cấu kiện Organisms (`wp_skaaa_data_sys_organisms`):** Đồng bộ `HeaderBar`, `FooterBar`, cards... Tự động hoán đổi URL ảnh và domain trong mã block của Organism.
  - **Quy tắc Theme Templates (`wp_skaaa_data_sys_theme_templates`):** Ghi nhận cấu hình gán `HeaderBar` vào `location = 'header'`, `FooterBar` vào `location = 'footer'` với `is_active = 1` (kích hoạt header/footer hiển thị toàn site). Ánh xạ chính xác theo `organism_name`.
  - **Skaaa Logic Workflows (`wp_skaaa_data_sys_workflows`):** Đồng bộ các luồng DAG JSON graphs, tự động hoán đổi URL nguồn và làm mới cache `sync_workflow_ids_cache()`.
- [x] **Task 2.3: Đồng bộ Toàn Bộ Các Trang Chính & Media Đính Kèm (Full Pages Sync)**
  - Quét danh sách toàn bộ các trang đang xuất bản trên Local (Trang Chủ, Dự Án, Wiki, Khóa Học...).
  - Tự động đẩy file ảnh nhúng từ ổ cứng lên Media Library của Live host qua endpoint `/upload-media`.
  - Tự động đối soát `_skaaa_uuid` và Slug fallback để cập nhật đè bài cũ, chặn 100% sinh slug `-2`.
- [x] **Task 2.4: Bộ Động Cơ Đồng Bộ Cấu Hình & Thiết Lập Hệ Thống Toàn Diện (Full Site Setup)**
  - **General Settings:** Tiêu đề website (`blogname`), khẩu hiệu (`blogdescription`), múi giờ (`timezone_string`, `gmt_offset`), định dạng ngày/giờ (`date_format`, `time_format`), ngày đầu tuần.
  - **Reading Settings (Trang Chủ Tĩnh):** Tự động đồng bộ `show_on_front = 'page'`, đối soát UUID trang chủ local và trỏ `page_on_front` sang đúng ID bài viết tương ứng trên máy Live (vào thẳng `https://lytatthanh.com/` là thấy trang chủ).
  - **Permalink Structure:** Cấu trúc đường dẫn tĩnh chuẩn SEO `permalink_structure` (`/%postname%/`), tự động kích hoạt `flush_rewrite_rules(false)` trên máy chủ Live để triệt tiêu 100% lỗi 404 URL.
- [x] **Task 2.5: Thiết Quân Luật Bảo Mật 4 Lớp & Tự Động Xóa Cache Hosting (Safeguards)**
  - **Lớp 1 (Blacklist Tên Miền):** Tuyệt đối KHÔNG ghi đè `siteurl` và `home` (bảo vệ tên miền live `https://lytatthanh.com`).
  - **Lớp 2 (Blacklist Quản Trị & Plugin):** Tuyệt đối KHÔNG ghi đè `admin_email` (giữ email thật của chủ site) và `active_plugins` (bảo vệ các plugin production như LiteSpeed Cache, SSL, Firewall).
  - **Lớp 3 (Cách Ly Người Dùng & Dữ Liệu Form):** Tuyệt đối KHÔNG chạm vào `wp_users` (mật khẩu) và các bảng dữ liệu khách hàng gửi form trên live (`wp_skaaa_data_*_submissions`).
  - **Lớp 4 (Auto Invalidate Cache):** Tự động gọi `do_action( 'litespeed_purge_all' )` và `wp_cache_flush()` ngay sau khi đồng bộ thành công để khách vãng lai thấy ngay giao diện mới.
- [x] **Task 2.6: Cơ Chế Bảng Đối Soát Phê Duyệt Trước Khi Ghi Đè (Pre-flight Review Gate)**
  - File mới: `wp-content/plugins/skaaai/inc/class-skaaai-sync-ecosystem-diff.php`
  - Thu thập danh sách thay đổi và phân loại trực quan:
    - 🎨 Tokens thay đổi (số lượng).
    - 🧩 Organisms & Theme Templates cập nhật.
    - ⚡ Workflows logic đồng bộ.
    - 🗄️ CSDL Bảng phẳng ứng dụng và dòng dữ liệu.
    - 📄 Các trang sẽ được đồng bộ kèm ảnh.
    - ⚙️ Cấu hình Trang chủ & Permalinks sẽ áp dụng.
  - Người dùng bấm **"Phê duyệt & Đồng bộ (Approve & Execute)"** thì hệ thống mới kích hoạt ghi đè!

---

### 🟢 Phase 3: Giao Diện Người Dùng 1-Click Sync (Admin Cockpit & Admin Bar)
*Mục tiêu: Cung cấp trải nghiệm 1 nút bấm trực quan, mượt mà và có phản hồi tiến độ rõ ràng.*

- [x] **Task 3.1: Thêm bảng điều khiển "Full Ecosystem Sync" trong Admin Skaaai**
  - File: `wp-content/plugins/skaaai/inc/class-skaaai-admin.php` & `class-skaaai-ecosystem-sync-ui.php`
  - Thêm Card chuyên dụng trong tab **Bridge & Sync**: Nút bấm gradient nổi bật **`🚀 1-Click Full Sync to Live`**.
  - Checkbox tùy chọn linh hoạt:
    - `[x] Design Tokens & Presets`
    - `[x] HeaderBar & FooterBar Organisms + Theme Templates`
    - `[x] Skaaa Logic Workflows`
    - `[x] Custom Database Tables & Data`
    - `[x] All Published Pages (Trang Chủ, Dự Án, Wiki, Khóa Học...)`
    - `[x] Site Setup & Homepage Routing`
- [x] **Task 3.2: Nút tắt nhanh trên WordPress Admin Bar (Header Top Bar)**
  - Hiển thị menu tắt trên thanh đen Admin Bar: `🚀 Skaaa Sync ➔ Push All to Live`.
  - Cho phép người dùng bấm đồng bộ nhanh ngay khi đang xem trang ngoài Frontend hoặc trong dashboard.
- [x] **Task 3.3: Hộp thoại báo cáo tiến độ thời gian thực (Real-time Progress Modal)**
  - Hiển thị từng bước:
    1. Đang nạp Design Tokens & biên dịch CSS... ✔
    2. Đang nạp Header & Footer Organisms + Theme Templates... ✔
    3. Đang triển khai Workflows & Database Tables... ✔
    4. Đang tải Local Media & đồng bộ Pages... ✔
    5. Đang áp dụng Homepage Routing & Xóa sạch Cache... ✔
    6. Hoàn tất! Bắn link mở xem Live Webhost.

---

### 🟢 Phase 4: Kiểm Thử E2E, Đóng Gói & Cập Nhật Bộ Nhớ
*Mục tiêu: Đảm bảo website online khớp 100% với Localhost và đóng gói phát hành.*

- [ ] **Task 4.1: Kiểm thử bằng tay trên Live host (`lytatthanh.com`)**
  - [x] Xác nhận không còn chữ `u0026amp;` trên trang chủ online.
  - [x] Xác nhận ảnh chân dung Lý Tất Thành hiển thị sắc nét, đúng tỷ lệ.
  - [x] Xác nhận HeaderBar và FooterBar hiển thị đầy đủ, menu hoạt động trơn tru.
  - [x] Xác nhận bài viết cũ được cập nhật ghi đè, không sinh bài trùng `-2`.
- [x] **Task 4.2: Nâng phiên bản SemVer lên `v1.4.0` (Major Feature Milestone)**
  - File: `skaaai.php`, `package.json`, `architecture.md`, `system_map.md`.
- [x] **Task 4.3: Đóng gói bản cài đặt `skaaai-v1.4.0.zip`**
  - Chạy script đóng gói tự động `zip-all.js` tạo `skaaai-v1.4.0.zip` (0.13 MB).
  - Đồng bộ sang paired site `lytatthanhloca`.

---

## 📊 3. TIÊU CHUẨN NGHIỆM THU (ACCEPTANCE CRITERIA)
1. **Giao diện Online:** Trang `https://lytatthanh.com/trang-chu-ly-tat-thanh/` có đầy đủ HeaderBar, FooterBar, ảnh chân dung tải thành công, 100% sạch sẽ không còn `u0026amp;`.
2. **Thao tác người dùng:** Chỉ cần bấm 1 nút **"🚀 1-Click Full Sync to Live"**, toàn bộ cấu trúc và dữ liệu Local được nhân bản lên Online chính xác.
3. **An toàn CSDL:** Không sinh slug `-2`, luôn tạo WordPress Revision trước khi ghi đè để có thể Undo an toàn.
