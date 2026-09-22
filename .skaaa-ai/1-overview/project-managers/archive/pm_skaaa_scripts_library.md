# PROJECT MANAGER: SKAAA SCRIPTS LIBRARY
@status: 🟢 Done | @target_milestone: MILESTONE 1 (POST-MVP) | @last_update: 2026-06-23

> [!NOTE]
> Tài liệu này quản lý tiến độ, kiến trúc và kế hoạch triển khai của tính năng **Skaaa Scripts Library** - Thư viện mã nguồn tập trung trong hệ sinh thái Skaaa. Tính năng này giải quyết bài toán quản lý tập trung và nạp các đoạn mã JS/CSS tùy biến (Global hoặc Conditional) ngoài Frontend.

---

## 1. MỤC TIÊU CỐT LÕI (CORE GOALS)
- **Quản lý tập trung (Centralized CRUD):** Cung cấp giao diện quản lý toàn bộ các đoạn mã tùy biến (Custom JS/CSS) và mã liên kết (CDN/External Asset) thay vì lưu trữ phân tán.
- **Nạp có điều kiện (Conditional Loading):** Hỗ trợ cấu hình nạp script linh hoạt: Toàn cục (Global) hoặc theo điều kiện trang/App/Table View cụ thể.
- **Tối ưu hóa vị trí nạp (Location Controls):** Cho phép chỉ định script nạp ở Header (`<head>`) hoặc Footer (trước `</body>`).
- **An toàn dữ liệu:** Lưu trữ dưới dạng bảng phẳng MySQL hệ thống `skaaa_data_sys_scripts` được bảo vệ (Table Schema Protection) chống xóa hoặc thay đổi ngoài ý muốn.

---

## 2. KIẾN TRÚC & PHÂN CHIA TRÁCH NHIỆM

### A. Tầng Dữ Liệu (Skaaa Data Pro)
- Chịu trách nhiệm khởi tạo bảng phẳng MySQL `wp_skaaa_data_sys_scripts`.
- **Cấu trúc Schema dự kiến:**
  | Tên cột | Kiểu dữ liệu | Mô tả |
  | :--- | :--- | :--- |
  | `id` | `bigint(20) unsigned` | Primary Key, Auto-increment. |
  | `script_id` | `varchar(100)` | Slug định danh duy nhất (ví dụ: `google-analytics`, `alpine-global`). |
  | `name` | `varchar(255)` | Tên hiển thị gợi nhớ. |
  | `type` | `varchar(50)` | Loại script: `js_file` (URL JS), `css_file` (URL CSS), `js_inline` (JS thô), `css_inline` (CSS thô). |
  | `content` | `longtext` | Nội dung mã inline hoặc URL liên kết. |
  | `location` | `varchar(20)` | Vị trí nạp: `header` (wp_head) hoặc `footer` (wp_footer). |
  | `load_condition` | `varchar(50)` | Điều kiện nạp: `global` (tất cả trang) hoặc `conditional` (chỉ nạp khi được gọi/chỉ định). |
  | `conditions` | `longtext` | Cấu hình điều kiện chi tiết dưới dạng JSON (ví dụ: `{"apps": [1, 2], "pages": [12]}`). |
  | `status` | `tinyint(1)` | Trạng thái kích hoạt: `1` (Active), `0` (Inactive). |
  | `created_at` | `datetime` | Thời gian tạo. |
  | `updated_at` | `datetime` | Thời gian cập nhật. |

- Đăng ký bảng `wp_skaaa_data_sys_scripts` vào danh sách bảng được bảo vệ (`Database_Engine::is_table_protected()`) của **Skaaa Data Pro**.

### B. Tầng Quản Trị (Admin UI - Skaaa Data Pro)
- Xây dựng tab quản lý **Scripts Library** riêng trong trang Dashboard của Skaaa Data Pro.
- Cung cấp giao diện CRUD chuyên nghiệp (sử dụng Tailwind CSS, OutFit font, Clean UI):
  - Form Thêm/Sửa Script với code editor thô hỗ trợ căn chỉnh hoặc tích hợp thư viện hiển thị code sạch.
  - Giao diện thiết lập điều kiện nạp (Global/Conditional) trực quan.

### C. Tầng Thực Thi Ngoài Frontend (Skaaa Scripts Loader Engine)
- Viết Class `Skaaa_Scripts_Loader` độc lập nằm trong **Skaaa Data Pro** hoặc **Skaaa No-Code Design** (sẽ quyết định lúc lập implementation plan).
- Đăng ký hook vào `wp_head` và `wp_footer` ở frontend để quét cơ sở dữ liệu các script `status = 1` và in ra tương ứng.
- Viết API helper: `skaaa_enqueue_custom_script( $script_id )` để các thành phần khác (ví dụ: block `skaaa-code`) có thể gọi nạp cưỡng bức một script khi cần.

---

## 3. KẾ HOẠCH HÀNH ĐỘNG (ACTION ITEMS)

- [x] **Phase 1: Database & Protection (Tầng Dữ Liệu)**
  - [x] Viết hàm khởi tạo bảng phẳng `wp_skaaa_data_sys_scripts` trong class database setup.
  - [x] Đăng ký bảo vệ bảng trong filter `skaaa_data_protected_tables`.
  - [x] Đăng ký cấu trúc bảng vào Data Dictionary của Skaaa Data Pro để đồng bộ schema.

- [x] **Phase 2: Frontend Loader Engine (Cỗ Máy Tải Scripts)**
  - [x] Xây dựng class `Skaaa_Scripts_Loader` xử lý load danh sách script từ MySQL.
  - [x] Tích hợp logic kiểm tra điều kiện nạp (Global/Conditional) trên Frontend.
  - [x] Hook vào `wp_head` để nạp các script vị trí `header`.
  - [x] Hook vào `wp_footer` để nạp các script vị trí `footer`.
  - [x] Triển khai hàm helper static `Skaaa_Scripts_Loader::enqueue_script($script_id)` phục vụ việc gọi nạp chủ động từ bên ngoài.

- [x] **Phase 3: Admin UI Dashboard (Giao Diện Quản Trị)**
  - [x] Tạo menu/tab **Scripts Library** trong trang quản lý Skaaa.
  - [x] Thiết kế Datagrid hiển thị danh sách scripts kèm các nút Bật/Tắt (Toggle status), Sửa, Xóa nhanh qua AJAX.
  - [x] Thiết kế Form CRUD: Hỗ trợ textarea căn chỉnh font monospace cho mã inline, và các cấu hình dropdown trực quan cho Location và Load Condition.

- [x] **Phase 4: Kiểm Thử & Nghiệm Thu (Testing & Verification)**
  - [x] Viết tài liệu quy trình kiểm thử E2E thủ công.
  - [x] Test trường hợp nạp JS/CSS file (CDN) và Inline ngoài frontend ở cả Header và Footer.
  - [x] Test logic nạp có điều kiện (chỉ nạp ở trang được chọn).

---

## 4. TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA)
1. Bảng phẳng MySQL được tạo và bảo vệ cấu trúc thành công.
2. Script cấu hình `header` + `global` phải xuất hiện trong thẻ `<head>` của mọi trang ở Frontend.
3. Script cấu hình `footer` + `conditional` chỉ xuất hiện ở Footer khi và chỉ khi thỏa mãn điều kiện trang hoặc được gọi nạp chủ động.
4. Giao diện CRUD trong Admin chạy mượt mà, hỗ trợ lưu trữ mã an toàn, không bị WordPress filter làm biến dạng ký tự đặc biệt.
