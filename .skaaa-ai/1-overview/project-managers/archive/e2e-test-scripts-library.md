# E2E Test Workflow: Skaaa Scripts Library (v1.2.2)
@target: Skaaa Data Pro | @milestone: 1 (Post-MVP)

Tài liệu này hướng dẫn các bước kiểm thử E2E thủ công để nghiệm thu tính năng **Skaaa Scripts Library** (Quản lý mã nhúng tùy biến JS/CSS) và bộ biên dịch **JIT Tailwind CSS** cục bộ.

---

## 1. PHẦN 1: ĐỊNH TUYẾN ẨN (HIDDEN ROUTE VERIFICATION)
- [x] **Bước 1.1:** Truy cập vào trang Admin WordPress. Xác nhận tại Sidebar menu của WordPress không có menu con mang tên **Scripts Library** (đã ẩn thành công để tránh rác danh mục).
- [x] **Bước 1.2:** Nhấp chuột vào menu chính **Skaaa Ecosystem** để truy cập Dashboard tổng.
- [x] **Bước 1.3:** Cuộn xuống phần **Extensions**, tìm card **Scripts Library** và nhấp chọn.
- [x] **Bước 1.4:** Xác nhận trình duyệt chuyển hướng thành công đến địa chỉ URL:
  `wp-admin/admin.php?page=skaaa-data-pro-scripts`
  và hiển thị giao diện quản trị thư viện Scripts trống với thông báo: *"No scripts found. Click "Add Script" to get started."*

---

## 2. PHẦN 2: KIỂM THỬ JIT TAILWIND CSS
- [x] **Bước 2.1:** Ở giao diện danh sách, nhấn phím F12 mở DevTools.
- [x] **Bước 2.2:** Tìm trong thẻ `<head>` hoặc đầu phần body, xác nhận có sự tồn tại của thẻ style:
  `<style id="skaaa-scripts-tailwind-jit">...</style>`
- [x] **Bước 2.3:** Kiểm tra xem giao diện có hiển thị đúng font chữ Outfit, bo góc rounded-2xl, màu sắc indigo/slate chuẩn Tailwind mà hoàn toàn không cần tải thư viện từ CDN bên ngoài hay không. (Có thể test bằng cách tắt kết nối internet và tải lại trang).

---

## 3. PHẦN 3: KIỂM THỬ CRUD CHỨC NĂNG
### 3.1. Thêm mới Script (Create)
- [x] **Bước 3.1.1:** Nhấp vào nút **Add Script** (`+ Add Script`) ở góc phải header.
- [x] **Bước 3.1.2:** Xác nhận form soạn thảo 2 cột hiển thị chuyên nghiệp:
  - Cột trái: Trình soạn thảo textarea tối màu (Monospace font).
  - Cột phải: Khung cài đặt Identity và Configuration.
- [x] **Bước 3.1.3:** Điền các thông tin thử nghiệm sau:
  - *Script Name:* `E2E Console Logger`
  - *Script ID (Slug):* `e2e-console-logger`
  - *Script Type:* Chọn `JS Inline`
  - *Inject Location:* Chọn `Footer (wp_footer)`
  - *Load Condition:* Chọn `Global (All pages)`
  - *Enable immediately:* Tích chọn hộp kiểm (Active).
  - *Code Content:* Gõ đoạn mã JS:
    ```javascript
    console.log("⚡ [Skaaa E2E Test] Scripts Library is working perfectly!");
    ```
- [x] **Bước 3.1.4:** Nhấp **Save Script**. Xác nhận có thông báo *"Script saved successfully!"* và trình duyệt chuyển hướng lại về trang danh sách.

### 3.2. Đọc & Kích hoạt Frontend (Read & Load)
- [x] **Bước 3.2.1:** Tại trang danh sách, kiểm tra xem bản ghi `E2E Console Logger` có hiển thị đúng kiểu badge `JS Inline`, location `Footer` và nút Toggle Status đang được bật hay không.
- [x] **Bước 3.2.2:** Mở một tab trình duyệt mới truy cập ra ngoài Frontend (trang chủ website).
- [x] **Bước 3.2.3:** Nhấn F12 chọn tab **Console**. Xác nhận có in ra dòng log:
  `⚡ [Skaaa E2E Test] Scripts Library is working perfectly!`

### 3.3. Cập nhật & Kiểm thử Điều kiện (Update & Conditions)
- [x] **Bước 3.3.1:** Quay lại trang quản lý Scripts Library trong admin, click biểu tượng chỉnh sửa (✏️ Edit) trên dòng script `E2E Console Logger`.
- [x] **Bước 3.3.2:** Đổi cài đặt **Load Condition** thành `Conditional pages/apps`.
- [x] **Bước 3.3.3:** Tại hộp chọn Pages, nhấp chọn một trang cụ thể (Ví dụ: Trang `Sample Page` hoặc trang con bất kỳ). Lưu ý: Giữ phím Ctrl/Cmd để chọn.
- [x] **Bước 3.3.4:** Nhấp **Save Script**.
- [x] **Bước 3.3.5:** Ra ngoài Frontend trang chủ (Home page). Mở Console F12 và tải lại trang. Xác nhận **không** xuất hiện log E2E (do không khớp điều kiện).
- [x] **Bước 3.3.6:** Điều hướng trình duyệt vào đúng trang bạn vừa chọn cấu hình ở bước 3.3.3. Xác nhận log E2E xuất hiện bình thường.

### 3.4. Bật/Tắt Trạng thái Nhanh (Toggle Status)
- [x] **Bước 3.4.1:** Tại trang danh sách, click tắt nút gạt (Toggle Switch) ở cột **Status** của script `E2E Console Logger`.
- [x] **Bước 3.4.2:** Truy cập vào trang web ngoài Frontend (trang đã khớp điều kiện). Xác nhận log E2E **không còn** hiển thị nữa.
- [x] **Bước 3.4.3:** Click bật lại nút gạt Status. Xác nhận log E2E hiển thị trở lại ngoài Frontend.

### 3.5. Xóa Script khỏi Thư viện (Delete)
- [x] **Bước 3.5.1:** Tại trang danh sách, nhấp vào biểu tượng thùng rác (🗑️ Delete) ở cột **Actions**.
- [x] **Bước 3.5.2:** Xác nhận popup cảnh báo hiển thị chính xác tên script cần xóa: *"Are you sure you want to permanently delete script [E2E Console Logger]?"*. Nhấp OK/Xác nhận.
- [x] **Bước 3.5.3:** Xác nhận bản ghi biến mất khỏi bảng và có thông báo *"Script deleted successfully!"*.
- [x] **Bước 3.5.4:** Truy cập Frontend, kiểm tra Console F12 và xác nhận không còn bất kỳ dấu vết nào của script thử nghiệm nữa.
