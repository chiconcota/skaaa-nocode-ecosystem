---
name: system-design
description: Kỹ năng chuyên sâu về phân tích nghiệp vụ, mô hình hóa thực thể (Entity-Relationship Modeling), thiết kế quan hệ 1-N / N-N trên bảng phẳng MySQL, và kiến trúc máy trạng thái (State Machine) cho ứng dụng mở rộng.
---

# SKILL: SYSTEM DESIGN & FLAT DATA ARCHITECTURE

Kỹ năng này cung cấp các nguyên lý, tư duy thiết kế hệ thống và phương pháp bóc tách bài toán thực tế thành cấu trúc cơ sở dữ liệu bảng phẳng (Flat Tables `skaaa_data_*`) khoa học, chuẩn hóa và có khả năng mở rộng cao.

---

## 1. TRIẾT LÝ THIẾT KẾ HỆ THỐNG TRÊN BẢNG PHẲNG (FLAT ARCHITECTURE)
1. **Một Bảng Cho Một Thực Thể (Single Responsibility Table):** Mỗi bảng phẳng chỉ đại diện cho một danh từ nghiệp vụ duy nhất (Khóa học, Học viên, Đơn hàng, Bài viết). Không nhét chung nhiều nghiệp vụ khác nhau vào cùng một bảng.
2. **Khắc cốt ghi tâm No-Postmeta:** Tuyệt đối không lưu dữ liệu ứng dụng vào `wp_postmeta`. Toàn bộ dữ liệu nằm trong bảng riêng `wp_skaaa_data_[table_name]`.
3. **Mềm dẻo hóa với Native MySQL JSON:** Tận dụng kiểu cột `JSON` gốc để lưu trữ các quan hệ động (`relation`), danh sách nhãn (`multi_select`) mà không cần tạo quá nhiều bảng nối phức tạp làm chậm truy vấn.

---

## 2. QUY TRÌNH BÓC TÁCH THỰC THỂ (ENTITY EXTRACTION)

Khi nhận một đề bài từ người dùng (ví dụ: *"Tôi muốn làm web bán khóa học"*):
1. **Tìm các Danh từ Cốt lõi (Core Nouns):**
   - Ai dạy? ➔ Giảng viên (`instructors`)
   - Bán cái gì? ➔ Khóa học (`courses`)
   - Ai mua? ➔ Học viên / Đăng ký (`enrollments`)
2. **Xác định Thuộc tính Nội tại (Attributes):**
   - Khóa học gồm: Tiêu đề (`title`), Giá (`price`), Nội dung (`content`), Trạng thái (`status`).
3. **Xác định Quan hệ giữa các Thực thể (Relationships):**
   - 1 Giảng viên dạy nhiều Khóa học (Quan hệ 1-N).
   - 1 Học viên đăng ký nhiều Khóa học (Quan hệ N-N).

---

## 3. MÔ HÌNH HÓA QUAN HỆ TRÊN BẢNG PHẲNG SKAAA

### A. Quan hệ 1 - Nhiều (One-to-Many / 1-N)
* **Quy tắc:** Đặt khóa ngoại mềm ở bảng "Nhiều".
* **Cách lưu trữ:** Cột kiểu `JSON` chứa mảng đối tượng:
  ```json
  [{"id": 5, "label": "Thạc sĩ Nguyễn Văn A"}]
  ```
* *Lợi ích:* Frontend DataGrid hiển thị ngay được tên giảng viên mà không cần chạy câu lệnh SQL JOIN nặng nề.

### B. Quan hệ Nhiều - Nhiều (Many-to-Many / N-N)
Có 2 phương pháp lựa chọn tùy quy mô:
* **Cách 1 (Nhẹ — Dùng cột JSON):** Lưu danh sách ID các đối tượng liên quan trực tiếp vào cột `JSON`:
  ```json
  [{"id": 10, "label": "Khóa Học React"}, {"id": 12, "label": "Khóa Học Node.js"}]
  ```
* **Cách 2 (Lớn — Tạo Bảng Nối Trung Gian):** Tạo một bảng phẳng độc lập `wp_skaaa_data_course_enrollments` chứa:
  - `course_id` (INT)
  - `student_id` (INT)
  - `enrolled_at` (DATETIME)
  - `payment_status` (VARCHAR)

---

## 4. THIẾT KẾ MÁY TRẠNG THÁI (STATE MACHINE DESIGN)

Mọi quy trình kinh doanh đều phải có dòng chảy trạng thái (Lifecycle) rõ ràng. Cột `status` bắt buộc phải được thiết kế trước:

```text
[Khách Gửi Yêu Cầu] ──► new (Mới)
                            │
                            ▼
                    contacted (Đã liên hệ)
                            │
                            ▼
                    qualified (Đạt điều kiện)
                            │
              ┌─────────────┴─────────────┐
              ▼                           ▼
      won (Thành công/Chốt)       lost (Thất bại/Hủy)
```

> 💡 **Quy tắc vàng:** Sử dụng chuỗi ngắn dạng `snake_case` cho trạng thái (`new`, `pending`, `in_progress`, `completed`, `cancelled`). Tránh dùng số 1, 2, 3 gây khó hiểu khi đọc code.

---

## 5. QUY CHUẨN ĐẶT TÊN TOÀN CỤC (NAMING CONVENTIONS)

1. **Tên Bảng Phẳng:**
   - Tiền tố bắt buộc: `wp_skaaa_data_`
   - Danh từ số nhiều bằng tiếng Anh viết thường (`snake_case`):
     - ✅ `wp_skaaa_data_courses`
     - ✅ `wp_skaaa_data_candidates`
     - ❌ `wp_skaaa_data_Course` (Tránh viết hoa)
     - ❌ `wp_skaaa_data_danh_sach_hoc_vien` (Tránh tiếng Việt)
2. **Tên Cột Bắt Buộc Có Mặt:**
   - `id`: Khóa chính `BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY`.
   - `status`: Trạng thái nghiệp vụ (`VARCHAR(50)` hoặc `TINYINT(1)`).
   - `created_at`: Thời gian tạo (`DATETIME DEFAULT CURRENT_TIMESTAMP`).
   - `updated_at`: Thời gian cập nhật cuối (`DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`).

---

## 6. VÍ DỤ THỰC CHIẾN: HỆ THỐNG TUYỂN DỤNG & ỨNG VIÊN

Khi nhận yêu cầu: *"Xây dựng app tuyển dụng cho công ty"*, AI phân tích mô hình gồm 2 bảng:

### Bảng 1: `wp_skaaa_data_jobs` (Vị trí tuyển dụng)
- `id`: Khóa chính
- `title`: Tên vị trí (VD: Senior PHP Developer)
- `department`: Phòng ban (VD: Kỹ thuật)
- `salary_range`: Mức lương (VD: 20tr - 35tr)
- `description`: Nội dung mô tả công việc (Kiểu `long_text`)
- `status`: Trạng thái (`open`, `paused`, `closed`)

### Bảng 2: `wp_skaaa_data_candidates` (Hồ sơ ứng viên)
- `id`: Khóa chính
- `job_id`: Quan hệ tới bảng jobs dạng JSON: `[{"id": 1, "label": "Senior PHP Developer"}]`
- `full_name`: Họ tên ứng viên
- `email`: Địa chỉ email
- `phone`: Số điện thoại
- `resume_file`: Đường dẫn CV đính kèm (Kiểu `media`)
- `stage`: Vòng phỏng vấn (`applied` ➔ `screening` ➔ `interview` ➔ `offer` ➔ `hired` / `rejected`)
