# PROJECT MANAGER: TÍCH HỢP ALPINE-AJAX & UNIVERSAL QUERY LOOP
@status: 🟡 Draft / Pending Review | @owner: Antigravity AI & Giám Đốc | @target_plugins: skaaa-no-code-design | @last_update: 2026-10-27

> [!NOTE]
> Tài liệu này quản lý kế hoạch thiết kế, tích hợp và nghiệm thu thư viện **Alpine AJAX (`x-target`)** vào plugin **Skaaa No-Code Design**.
> **Mục tiêu tối thượng:** Khai tử hoàn toàn sự phụ thuộc vào các khối mặc định của WordPress (`core/query`, `core/query-pagination`), giải quyết triệt để vấn đề "Div Soup" (lồng 5-6 tầng container) và mang lại trải nghiệm điều hướng tức thì (Instant AJAX Navigation / 0ms SPA) cho các trang Blog, Tin tức, Danh mục và Lọc sản phẩm mà vẫn bảo toàn 100% SEO.

---

## 1. MỤC TIÊU CỐT LÕI (CORE OBJECTIVES)

1. **Tích hợp Runtime Siêu Nhẹ (`alpine-ajax` ~3.5 KB):**
   - Đóng gói thư viện `alpine-ajax.min.js` vào `assets/js/vendor/` của `skaaa-no-code-design`.
   - Đăng ký và nạp tự động qua WordPress Enqueue API (`wp_enqueue_script`), đảm bảo nạp liền mạch cùng hệ sinh thái `skaaa-alpine`.
2. **Giao Diện Điều Khiển No-Code Trên Gutenberg Inspector:**
   - Bổ sung panel **"⚡ Instant AJAX Navigation"** trên Inspector của các khối `Container`, `Button` và `Text (Link)`.
   - Người dùng chỉ cần nhập ID mục tiêu (ví dụ: `blog-grid`) hoặc tick chọn `Enable AJAX Pagination`, hệ thống tự động inject thuộc tính `x-target="blog-grid"` và modifier `x-target.push="blog-grid"`.
3. **Mở Rộng Skaaa Loop thành Universal Query Loop:**
   - Nâng cấp `src/skaaa-loop/render.php` để hỗ trợ nguồn dữ liệu:
     - `Skaaa Data Pro Flat Tables` (`wp_skaaa_data_*`).
     - **`WordPress Native Posts`** (`post_type = 'post'`, `'page'`, Custom Post Types).
   - Hỗ trợ kế thừa ngữ cảnh Archive tự nhiên của WordPress (`is_home()`, `is_archive()`, `is_category()`).
4. **Cấu Kiện Phân Trang Chuẩn Flat DOM (Skaaa Pagination):**
   - Tạo khuôn phân trang nguyên tử thuần HTML + Tailwind v4 (không sinh thẻ `<ul>`, `<li>` rác).
   - Tự động gắn `x-target.push` vào các nút trang (1, 2, 3... Trang sau / Trang trước).
   - Chuyển trang mượt mà không tải lại màn hình, đồng thời tự động cập nhật URL `/blog/page/2/` trên thanh địa chỉ.
5. **Cách Ly An Toàn với Skaaapine Trên Editor:**
   - Giữ nguyên `Skaaapine` trong Gutenberg Editor để chuyên trách animation cục bộ (Tabs, Accordions, Transitions).
   - Vô hiệu hóa hành vi fetch AJAX ngầm khi đang soạn thảo trong Gutenberg để bảo vệ React Fiber không bị desync trạng thái block.
6. **Chuẩn Hóa Agent Kit & Nâng Cấp Harness Validator:**
   - Nâng cấp cỗ máy CLI `block-tool.php` tự động bẫy lỗi và chặn đứng các trường hợp AI lạm dụng block mặc định `core/query` của WordPress.
   - Bổ sung tài liệu chuẩn và code mẫu vào `developer-blocks/SKILL.md` và `company-rules.md`.
   - Đồng bộ hóa 2 chiều sang cả `.agent/` và `scaffold/.agent/` của plugin `skaaai`.

---

## 2. MA TRẬN PHÂN CHIA CÔNG VIỆC (PHASED ACTION PLAN)

### 🟢 Phase 1: Tích hợp Thư viện Runtime & Đăng ký Enqueue
- [x] Tải bản phân phối tĩnh `alpine-ajax.min.js` (v0.12.7) lưu tại `assets/js/vendor/alpine-ajax.min.js` (Offline 100%, Zero-CDN).
- [x] Trong `inc/design-engine/class-core.php` & `blocks/init.php`:
  - Đăng ký handle script `'skaaa-alpine-ajax'`.
  - Thiết lập chuỗi phụ thuộc chặt chẽ: `skaaa-frontend` -> `skaaa-alpine-ajax` -> `skaaa-alpine`.
- [x] Kiểm thử nạp script ngoài Frontend: Xác nhận trình duyệt nhận diện directive `window.Alpine` có extension AJAX (`Alpine.ajax: true`, `x-target: REGISTERED`, `x-merge: REGISTERED`).

### 🟢 Phase 2: Nâng cấp Gutenberg Inspector (No-Code Controls)
- [x] Mở rộng `src/extensions/html-attributes.js`:
  - Thêm section chuyên dụng trong Sidebar Inspector: **"⚡ Instant AJAX Navigation"** với badge nhận diện Active màu xanh.
  - Các trường cấu hình:
    - **Toggle:** `Enable AJAX Target (x-target)`.
    - **Target Element ID:** Gõ ID container đích cần tráo (VD: `blog-grid` hoặc `posts-container`).
    - **Toggle Update Browser URL (`.push`):** Tự động đẩy URL mới lên thanh địa chỉ trình duyệt khi chuyển trang (lưu giữ lịch sử Back/Forward).
    - **Auto-Focus & Scroll to Target Toggle:** Tự động cuộn nhẹ và focus vào container đích (bỏ chọn sẽ gắn `.nofocus`).
- [x] Tự động đồng bộ các thuộc tính này vào mảng `htmlAttributes` của block hai chiều (Two-Way Sync), hiển thị live code directive `x-target.push="..."`.
- [x] Chạy `npm run build` biên dịch lại toàn bộ bundle `build/extensions.js` thành công 100%.

### 🟢 Phase 3: Nâng cấp Lõi Skaaa Loop (WordPress Posts Adapter)
- [x] Trong `src/skaaa-loop/render.php`:
  - Bổ sung tùy chọn `sourceType: "flat_table" | "wp_posts"`.
  - Khi `sourceType === 'wp_posts'`:
    - Tự động gọi `WP_Query` lấy danh sách bài viết theo `post_type`, `category`, `posts_per_page`, `paged`.
    - Trích xuất dữ liệu chuẩn hóa: `title`, `permalink`, `date`, `excerpt`, `author`, `featured_image_url`, `categories`.
    - Tương thích 100% với SkaaaFX Dynamic Binding (`[post.title]`, `[post.featured_image]`).
- [x] Triệt tiêu 100% thẻ `<ul>`, `<li>` rác; render trực tiếp thẻ cha Grid/Flex Flat DOM mang class Tailwind.

### 🟢 Phase 4: Thiết kế Cấu Kiện Phân Trang Nguyên Tử (Flat DOM Pagination)
- [x] Xây dựng template phân trang bản địa:
  - Nút Trang Trước (`Previous`), Danh sách số trang (`1`, `2`, `3`...), Nút Trang Kế (`Next`).
  - Style 100% bằng Design Tokens (`bg-surface`, `text-primary`, `hover:bg-primary/10`, `rounded-lg`).
  - Tự động gắn thuộc tính `x-target.push="{target_id}"` vào từng thẻ `<a>`.
- [x] Kiểm thử trạng thái Active của trang hiện tại (nút số trang đang xem được tô màu nhận diện).

### 🟢 Phase 5: Kiến Trúc Phẳng Decoupled Reactive Model (Mô Hình Tương Tác Chuẩn A ⟷ B) — [Đã Hoàn Thành Phần Cốt Lõi Alpine-AJAX]
- [x] **Mục tiêu kiến trúc:** Tách rời 100% giữa Bộ điều khiển tương tác (Phần A - Controllers: Filter, Search, Sort, Pagination) và Mục tiêu hiển thị (Phần B - Target View: Loop / Container Grid), giải phóng Loop khỏi việc ôm đồm giao diện điều khiển.
- [x] **Kế hoạch triển khai:**
  1. *Universal Query Listener cho Skaaa Loop:*
     - Loop tự động đọc toàn bộ query parameters từ URL (`?s=...`, `?category=...`, `?cat=...`, `?tag=...`, `?author=...`, `?paged=...`, `?orderby=...`, `?order=...`) mà không cần cấu hình cứng.
     - Bảo toàn toàn bộ query parameters hiện có trên các liên kết phân trang Flat DOM (`add_query_arg()`), đảm bảo khi đang search/filter mà chuyển trang thì không mất trạng thái.
     - Cung cấp hook/filter mở rộng: `skaaa_loop_wp_query_args` cho WordPress Posts và `skaaa_loop_flat_table_args` cho Flat Tables (`skaaa_data_*`).
     - Safe Empty State Container: Khi không có kết quả, vẫn render thẻ bọc có `id="{target_id}"` và class wrapper để Alpine-AJAX hoán đổi DOM an toàn.
  2. *Bộ Điều Khiển Phẳng (Part A Controllers via Native Atomic Blocks):*
     - Tận dụng Extension "⚡ Instant AJAX Navigation" trên các khối nguyên tử:
       - `Container` dạng `form (GET)` làm Khung bộ lọc (Filter Sidebar, Search Bar) mang `x-target="target-id"`.
       - Mở rộng `html-attributes.js`: Bổ sung preset tiện ích `@input.debounce.300ms` (Instant Search khi gõ phím) và `@change` (Instant Filter khi chọn Dropdown) tự động điền `$el.form.requestSubmit()`.
       - `Button` nguyên tử làm nút Phân trang hoặc Reset bộ lọc độc lập ở bất kỳ vị trí nào trên layout.
  3. *Đồng Bộ Đa Vùng (Multi-Target Sync via `x-sync`):*
     - Hỗ trợ cập nhật đồng thời nhiều thành phần trên trang qua directive `x-sync` trên Inspector.
  4. *Chuẩn Tailwind JIT Hỗ Trợ `aria-busy:` (Tạm hoãn làm sau theo chỉ đạo của Giám Đốc):*
     - Nhiệm vụ variant `aria-busy:` trong `class-tailwind-compiler.php` và `skaaawind.js` sẽ được triển khai trong phiên làm việc chuyên trách về Tailwind.

### 🟢 Phase 6: Chuẩn Hóa Agent Kit & Nâng Cấp Công Cụ Kiểm Tra (Harness CLI & Rules)
- [x] **Nâng cấp CLI Tool `block-tool.php`:**
  - Bổ sung bộ kiểm định (Validator) cho cấu trúc `skaaaaa-builder/loop` với `sourceType="wp_posts"` và `sourceType="pagination"`.
  - Đánh thẻ đỏ cảnh báo nếu phát hiện AI sử dụng khối cũ của WordPress Core (`core/query`, `core/post-template`, `core/query-pagination`, `core/post-*`).
  - Hỗ trợ cờ kiểm tra thuộc tính `x-target` và `x-target.push` trên các container/link phân trang để bảo đảm tính hợp lệ trước khi lưu vào CSDL (bắt lỗi dấu `#` thừa).
- [x] **Cập nhật Hướng Dẫn Kỹ Năng AI (`developer-blocks/SKILL.md`):**
  - Soạn thảo tài liệu và code mẫu Gutenberg chuẩn cho:
    1. Universal Loop danh sách bài viết (`sourceType="wp_posts"`).
    2. Cụm phân trang chuẩn tích hợp `x-target.push` (dùng `sourceType="pagination"` kết hợp Conditional Slots).
    3. Cụm Tìm kiếm tức thì (Instant Search) & Bộ lọc phẳng (Decoupled Filter A ⟷ B).
  - Hướng dẫn chi tiết cách dựng trang Blog / Archive Flat DOM 0ms hoàn toàn không sinh thẻ `<ul>`, `<li>` rác.
- [x] **Cập nhật Quy Chế Doanh Nghiệp (`company-rules.md`):**
  - Bổ sung Điều 8: Thiết quân luật cấm tuyệt đối nhân viên AI sử dụng `core/query` và `core/query-pagination` của WordPress.
  - Bắt buộc 100% danh sách bài viết, phân trang và bộ lọc phải dùng `skaaaaa-builder/loop` kết hợp `x-target.push` chuẩn A ⟷ B.
- [x] **Đồng bộ hóa Agent Kit Hai Chiều (Two-Way Parity):**
  - Đồng bộ các cập nhật của `block-tool.php`, `developer-blocks/SKILL.md` và `company-rules.md` vào cả `.agent/` (Root) và `scaffold/.agent/` (Plugin `skaaai`).

### 🟢 Phase 7: Đóng Gói, Nâng Cấp Phiên Bản (SemVer) & Kiểm Thử E2E Toàn Diện
- [x] Chạy `npm run build` trong `skaaa-no-code-design` để biên dịch lại toàn bộ bundle JavaScript.
- [x] Tăng phiên bản Minor theo chuẩn SemVer:
  - `Skaaa No-Code Design: v2.6.0`
  - `Skaaai: v1.7.0` (Đồng bộ bộ gen Agent Kit mới).
- [x] Kiểm thử E2E trên trang Blog & Danh mục thực tế:
  - [x] Click chuyển trang không reload màn hình (0ms).
  - [x] Gõ Instant Search lọc bài viết tức thì (0ms).
  - [x] URL trên trình duyệt cập nhật chính xác `/page/2/` và `?s=...`.
  - [x] Nút Back/Forward của trình duyệt hoạt động bình thường.
  - [x] Ảnh đại diện (Featured Image) hiển thị sắc nét, không bị icon placeholder.
  - [x] Điểm số Core Web Vitals (LCP, CLS) đạt mức tối ưu.

---

## 3. NGUYÊN TẮC KIẾN TRÚC BẤT BIẾN (ARCHITECTURAL CONSTRAINTS)
1. **Zero-API Backend:** Không viết thêm REST API endpoint cồng kềnh cho phân trang. Tận dụng 100% SSR HTML của WordPress.
2. **SEO-First Directive:** Trang HTML gốc khi mở trực tiếp bằng URL `/blog/page/2/` phải chứa đầy đủ nội dung bài viết để Google Bot đọc được 100%.
3. **Editor Safety:** Tuyệt đối không kích hoạt AJAX fetch thật trong canvas Gutenberg Editor.
4. **SemVer Rule:** Tự động tăng phiên bản lên `v2.6.0` khi triển khai nhánh tính năng mới này.
