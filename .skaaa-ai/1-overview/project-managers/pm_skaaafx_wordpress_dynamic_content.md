# PROJECT MANAGER: SKAAAFX WORDPRESS DYNAMIC CONTENT & UNIVERSAL BINDING
@status: 🟢 Completed | @owner: Antigravity AI & System Architect | @target_plugins: skaaa-logic-engine, skaaai | @last_update: 2026-10-25

> [!NOTE]
> Tài liệu này quản lý tiến độ thiết kế, lập trình và kiểm định tính năng **Universal WordPress Dynamic Content** cho cỗ máy **SkaaaFX (Skaaa Logic Engine)** và bộ tài liệu hướng dẫn AI (**Skaaai Agent Kit**).
> **Mục tiêu tối thượng:** Cho phép người dùng và AI Agent xây dựng toàn bộ giao diện bài viết (Single Post Template, Archive, Page Detail) 100% bằng Atomic Blocks của Skaaa (Text, Image, Button, Container) mà **không cần sử dụng bất kỳ block mặc định nào của WordPress**, đồng thời bảo toàn khả năng style qua Tailwind CSS v4 JIT và Design Tokens.

---

## 1. MỤC TIÊU CỐT LÕI (CORE OBJECTIVES)
1. **Kích hoạt Hydration cho Image Block (`skaaaaa-builder/image`):** Xử lý luồng `data` trong `class-dynamic-content.php` để bơm URL ảnh động vào thẻ `<img>` hoặc thay thế placeholder rỗng một cách mượt mà.
2. **WordPress Context Provider trong AST Evaluator:** Mở rộng `SkaaaFX_Evaluator` tự động nhận diện ngữ cảnh Post ID và các biến WordPress core (`[post.title]`, `[post.featured_image]`, `[post.author]`, `[post.date]`, `[post.excerpt]`, `[post.permalink]`, `[post.meta.*]`, `[user.*]`, `[site.*]`).
3. **Floating Autocomplete Schema:** Bổ sung nhóm gợi ý WordPress Core vào REST API `/skaaa-logic/v1/schema` để khi người dùng gõ `[` trong CodeMirror, các biến WordPress hiển thị trực quan kèm mô tả.
4. **Chuẩn hóa Agent Kit (`skaaai`):** Bổ sung hướng dẫn chi tiết và mẫu markup Gutenberg có thuộc tính `skaaaDynamicBinding` vào `developer-blocks/SKILL.md` và `company-rules.md`, chặn đứng việc AI sử dụng block mặc định WordPress.
5. **Đồng bộ & Kiểm thử Thực tế:** Nâng phiên bản SemVer Patch (`skaaa-logic-engine v1.3.2`, `skaaai v1.6.4`), đồng bộ sang site `lytatthanh localhost` và kiểm thử nghiệm thu.

---

## 2. KẾ HOẠCH TRIỂN KHAI CHI TIẾT (ACTION PLAN)

### 🟢 Bước 1: Kích hoạt bơm dữ liệu Image & Button trong `class-dynamic-content.php`
- [x] Mở rộng phương thức `parse_skaaa_blocks`:
  - **`skaaaaa-builder/image`:**
    - Tiếp nhận `$new_data` (URL ảnh hoặc Media ID số).
    - Tự động chuyển đổi Media ID sang URL kích thước `'full'` qua `wp_get_attachment_image_url()`.
    - Nếu block đã chứa thẻ `<img ...>`: Dùng Regex thay thế chính xác thuộc tính `src="..."` thành `$new_data`.
    - Nếu block đang ở dạng placeholder rỗng (div `Icon Image`): Dùng Regex thay thế phần lõi placeholder thành thẻ `<img class="w-full h-full object-cover" src="..." alt="" />`.
  - **`skaaaaa-builder/button`:**
    - Tiếp nhận `$new_data` để thay thế nội dung nhãn text hiển thị của button.

### 🟢 Bước 2: Tích hợp Context WordPress vào Cỗ máy `SkaaaFX_Evaluator`
- [x] Trong `class-skaaafx-evaluator.php` (nhánh xử lý `Node_Variable`):
  - Tự động trích xuất Post ID hiện tại từ: `$this->row_context['WP_POST_ID']` ➔ `get_the_ID()` ➔ `$GLOBALS['post']->ID` ➔ `$_GET['id']`.
  - Phân giải danh mục biến WordPress:
    - `[post.title]` / `post.title`: `get_the_title( $post_id )`
    - `[post.featured_image]` / `[post.thumbnail]` / `[post.image]`: `get_the_post_thumbnail_url( $post_id, 'full' ) ?: ''`
    - `[post.author]`: `get_the_author_meta( 'display_name', $post->post_author )` hoặc `get_the_author()`
    - `[post.author_avatar]`: `get_avatar_url( $post->post_author )`
    - `[post.date]`: `get_the_date( '', $post_id )`
    - `[post.modified_date]`: `get_the_modified_date( '', $post_id )`
    - `[post.excerpt]`: `get_the_excerpt( $post_id )`
    - `[post.permalink]` / `[post.url]`: `get_permalink( $post_id )`
    - `[post.id]`: `$post_id`
    - `[post.meta.<key>]`: `get_post_meta( $post_id, <key>, true )`
    - `[user.name]` / `[user.display_name]`: `wp_get_current_user()->display_name`
    - `[user.email]`: `wp_get_current_user()->user_email`
    - `[user.avatar]`: `get_avatar_url( get_current_user_id() )`
    - `[site.title]`: `get_bloginfo( 'name' )`
    - `[site.url]`: `home_url()`
  - Nâng cấp `class-skaaafx-parser.php` hỗ trợ gán trực tiếp không cần `var`: `data = [post.title]` và `visible = true`.

### 🟢 Bước 3: Thêm gợi ý Autocomplete vào REST API Schema
- [x] Trong `class-form-receiver.php` (`handle_schema`):
  - Bổ sung nhóm biến `WordPress Core Data` vào mảng `$tribute_data` trả về.
  - Cung cấp nhãn hiển thị và mô tả rõ ràng để khi gõ `[` trong CodeMirror modal, danh sách biến lập tức hiện ra.

### 🟢 Bước 4: Cập nhật bổ sung vào Agent Kit của `skaaai`
- [x] Cập nhật `wp-content/plugins/skaaai/scaffold/.agent/skills/developer-blocks/SKILL.md`:
  - Bổ sung mục **Cơ chế Universal Dynamic Binding (SkaaaFX) cho Single Post & Templates**.
  - Cung cấp mẫu comment Gutenberg hoàn chỉnh cho Tiêu đề bài viết (`skaaaaa-builder/text`), Ảnh đại diện (`skaaaaa-builder/image`), Tác giả & Ngày đăng.
- [x] Cập nhật `wp-content/plugins/skaaai/scaffold/.agent/rules/company-rules.md`:
  - Khẳng định nguyên tắc: Tuyệt đối dùng Atomic Blocks kết hợp `skaaaDynamicBinding` khi tạo Template bài viết, cấm dùng block mặc định `core/post-*`.
- [x] Đồng bộ nội dung vào thư mục `.agent/` của site `lytatthanh`.

### 🟢 Bước 5: Nâng SemVer, Đóng gói, Đồng bộ sang `lytatthanh` & Kiểm thử
- [x] Nâng SemVer Patch:
  - `skaaa-logic-engine`: `1.3.1` ➔ `1.3.2` (trong `skaaa-logic-engine.php` và `package.json`).
  - `skaaai`: `1.6.3` ➔ `1.6.4` (trong `skaaai.php`).
- [x] Chạy `node wp-content/plugins/zip-all.js` tạo các file zip phát hành mới:
  - `skaaa-logic-engine-v1.3.2.zip` (0.37 MB)
  - `skaaai-v1.6.4.zip` (0.16 MB)
- [x] Sao chép tệp đã cập nhật sang `/home/chiconcota/Local Sites/l-tt-thnh-siu-c-nhn/app/public/wp-content/plugins/`.
- [x] Kiểm tra thực tế: Chạy test suite `test_skaaafx_wp.php` xác nhận 100% assertions PASSED thành công trên Local database.
- [x] Cập nhật hồ sơ tài liệu: `system_map.md`, `decision-log.md`, `checkpoint.md`.
