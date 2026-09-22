# QUY TRÌNH KIỂM THỬ E2E TỔNG HỢP: KHỐI SKAAA CODE
@status: 🟢 Active | @target: Skaaa Ecosystem | @update: 2026-06-24

Tài liệu này cung cấp các kịch bản kiểm thử (Test Cases) chi tiết để xác minh toàn bộ các tính năng của block **Skaaa Code** (`skaaaaa-builder/code`) từ khả năng nhúng mã trực tiếp, điều phối vị trí nạp, cơ chế khử trùng lặp, cho đến tính năng lưu nhanh (Quick Save) vào Thư viện Scripts.

---

## 🛠️ Chuẩn bị trước khi kiểm thử
1. Đảm bảo cả hai plugin **Skaaa No-Code Design** (v1.2.0) và **Skaaa Data Pro** (v1.3.0) đều đã được kích hoạt.
2. Tạo một Trang (Page) mới trong WordPress đặt tên là: `Skaaa Code E2E Playground`.

---

## 🧪 TEST CASE 1: Kiểm thử Inline Code (In tại chỗ - Body)
*Mục đích: Xác minh code HTML/CSS/JS in ra ngay tại vị trí đặt block trong body.*

1. Trong trang `Skaaa Code E2E Playground`, thêm block **Skaaa Code**.
2. Tại Inspector Sidebar bên phải:
   * **Source Mode**: Chọn `Inline Code`.
   * **Inject Location**: Chọn `Inline`.
   * Nhập mã HTML thô vào **Code Editor**:
     ```html
     <div id="tc1-box" style="padding: 20px; background: #f8fafc; border: 2px solid #cbd5e1; border-radius: 12px; text-align: center; margin: 20px 0;">
         <h4 style="margin: 0 0 8px 0; color: #1e293b;">[Test Case 1] Render Inline Body</h4>
         <button onclick="alert('Inline JS Clicked!')" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
             Click Me
         </button>
     </div>
     ```
3. Nhấn **Publish** / **Update** trang và xem ngoài Frontend.
4. **Kết quả mong muốn**: 
   * Hộp thông báo màu xám xuất hiện đúng tại vị trí đặt block.
   * Click vào nút "Click Me" hiển thị popup alert `Inline JS Clicked!`.

---

## 🧪 TEST CASE 2: Kiểm thử nạp Header & Footer (Khử trùng lặp mã Inline)
*Mục đích: Xác minh code tự động đẩy lên `<head>` và xuống cuối trang `wp_footer`, đồng thời không bị in lặp lại khi copy block.*

1. Thêm một block **Skaaa Code** mới:
   * **Source Mode**: Chọn `Inline Code`.
   * **Inject Location**: Chọn `Header (wp_head)`.
   * Nhập mã CSS tùy biến vào **Code Editor**:
     ```html
     <style>
     .tc2-custom-alert {
         background-color: #fef2f2 !important;
         border: 2px dashed #f87171 !important;
         color: #991b1b !important;
     }
     </style>
     ```
2. Thêm block **Skaaa Code** tiếp theo đặt ở vị trí khác:
   * **Source Mode**: Chọn `Inline Code`.
   * **Inject Location**: Chọn `Footer (wp_footer)`.
   * Nhập mã JS vào **Code Editor**:
     ```html
     <script>
     console.log("[Test Case 2] Footer script loaded successfully!");
     </script>
     ```
3. **Nhân bản (Duplicate)** cả hai block trên (để trang có 2 block CSS Header giống nhau và 2 block JS Footer giống nhau).
4. Sửa lại block HTML ở **Test Case 1** bằng cách bổ sung class `tc2-custom-alert` vào thẻ `div` để kiểm tra CSS Header có ăn hay không:
   `<div id="tc1-box" class="tc2-custom-alert" ...>`
5. Nhấn **Update** trang và xem ngoài Frontend.
6. **Kết quả mong muốn**:
   * Hộp thông tin ở Test Case 1 chuyển sang màu đỏ nhạt, viền đứt nét màu đỏ (CSS từ Header đã ăn thành công).
   * Mở Console của trình duyệt (F12) -> Thấy dòng log `[Test Case 2] Footer script loaded successfully!`.
   * Nhấp chuột phải chọn **View Page Source** (Xem nguồn trang):
     * Tìm kiếm đoạn CSS `<style>.tc2-custom-alert ...`: Thấy nó nằm trong thẻ `<head>` và **chỉ xuất hiện 1 lần duy nhất** (không bị in 2 lần dù đã nhân bản).
     * Tìm kiếm đoạn log JS: Thấy nó nằm ở cuối trang trước thẻ `</body>` và **chỉ xuất hiện 1 lần duy nhất**.

---

## 🧪 TEST CASE 3: Kiểm thử lưu nhanh vào thư viện (Quick Save)
*Mục đích: Xác minh tính năng lưu thẳng code từ Editor vào thư viện Scripts trung tâm.*

1. Thêm một block **Skaaa Code** mới:
   * **Source Mode**: Chọn `Inline Code`.
   * **Inject Location**: Chọn `Footer (wp_footer)`.
   * Nhập mã JS vào **Code Editor**:
     ```javascript
     console.log("[Test Case 3] Saved Library Script Executed!");
     ```
2. Click vào nút **Save to Scripts Library** ở cuối sidebar settings.
3. Trong popup modal:
   * **Script Name**: `E2E Quick Save Test`
   * **Script ID**: `e2e-quick-save`
   * **Script Type**: Chọn `JS Inline code`
   * Nhấn **Save Script**.
4. **Kết quả mong muốn**:
   * Modal đóng lại. Block tự động chuyển sang chế độ **Scripts Library** và dropdown tự chọn liên kết đến `E2E Quick Save Test (e2e-quick-save)`.
   * Vào menu **Scripts Library** ở Dashboard chính để kiểm tra -> Thấy script `E2E Quick Save Test` đã được lưu thành công vào CSDL.

---

## 🧪 TEST CASE 4: Kiểm thử nạp Script Thư viện và Khử trùng lặp
*Mục đích: Xác minh nạp script từ thư viện thông qua Action Hook decoupled và khử trùng lặp.*

1. Trong trang soạn thảo, tạo thêm 1 block **Skaaa Code** nữa.
2. Chọn **Source Mode** là **Scripts Library**, và liên kết đến script `E2E Quick Save Test` vừa tạo ở Test Case 3. (Lúc này trang đang có 2 block cùng gọi 1 script thư viện).
3. Nhấn **Update** trang và xem ngoài Frontend.
4. Mở Console trình duyệt (F12) -> Xác nhận dòng log `[Test Case 3] Saved Library Script Executed!` xuất hiện.
5. Xem nguồn trang (View Source):
   * Tìm kiếm chuỗi `e2e-quick-save` hoặc nội dung log.
   * Xác minh thẻ `<script>` tương ứng chỉ được in ra **đúng 1 lần duy nhất** ở chân trang.
