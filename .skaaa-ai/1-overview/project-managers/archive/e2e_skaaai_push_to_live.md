# E2E Test Workflow: Skaaai 1-Click Push to Live & Post List Sync (v1.3.0)

> [!NOTE]
> Tài liệu này hướng dẫn chi tiết từng bước (Step-by-step) có kèm **Checkbox [ ]** để bạn tự tay kiểm thử trên trình duyệt (Manual E2E Testing) các tính năng thuộc **Phase 3 (Giao diện 1-Click Push to Live & Quản lý Đồng bộ Danh sách Bài viết)** sau khi cài đặt bản cập nhật **`Skaaai v1.3.0`**.

---

## 🎯 1. Chuẩn Bị File Cài Đặt & Môi Trường

### A. Vị trí file ZIP cập nhật
File cài đặt bản cập nhật chính thức đã được đóng gói sẵn trong máy tính của bạn:
- Đường dẫn: `/home/chiconcota/Local Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai-v1.3.0.zip`
- (Hoặc bản alias: `wp-content/plugins/skaaai.zip`)

### B. Môi hình Kiểm thử (2 Websites):
- **Website Gửi (Sender / Localhost):** Website đang phát triển tại Local by Flywheel (ví dụ: `http://lytatthanh-localremote.local` hoặc site local hiện tại).
- **Website Nhận (Receiver / Live Webhost hoặc Local Staging):** Website đóng vai trò máy chủ đích (ví dụ: `https://lytatthanh.com` hoặc một site local thứ hai đóng vai trò Receiver).

---

## 🧪 2. Quy Trình Kiểm Thử Bằng Tay Chi Tiết (8 Test Cases)

---

### Ca 1: Cập Nhật Plugin Skaaai Lên v1.3.0
*Mục tiêu: Đưa mã nguồn Phase 3 chính thức vào website Localhost.*

- [ ] **Bước 1.1:** Mở trang quản trị WordPress Admin của website Localhost (`/wp-admin/plugins.php`).
- [ ] **Bước 1.2:** Bấm **Add New Plugin** (Thêm plugin mới) ➔ **Upload Plugin** (Tải plugin lên).
- [ ] **Bước 1.3:** Chọn file `wp-content/plugins/skaaai-v1.3.0.zip` và bấm **Install Now** (Cài đặt ngay).
- [ ] **Bước 1.4:** Bấm **Replace current with uploaded** (Ghi đè phiên bản hiện tại).
- [ ] **Bước 1.5:** Kiểm tra danh sách Plugins: xác nhận phiên bản hiển thị là **`Skaaai: 1.3.0`**.
- [ ] **Bước 1.6:** Vào menu **Skaaa Ecosystem ➔ Bridge & Sync** (hoặc **Skaaa Bridge**):
  - Đảm bảo **Site Role** đang chọn là **`Sender (Localhost Dev / PC)`**.
  - Kiểm tra trạng thái kết nối với Remote Host đã báo xanh (Connected). Nếu chưa kết nối, thực hiện dán Pairing Key từ site Receiver và bấm **Save Configuration**.

> **Kết quả kỳ vọng:** Plugin `Skaaai 1.3.0` hoạt động bình thường, không phát sinh lỗi PHP WSoD hay lỗi JavaScript console.

---

### Ca 2: Kiểm Thử Tự Động Cấp Phát `_skaaa_uuid` Khi Tạo Bài Mới
*Mục tiêu: Đảm bảo mọi bài viết hoặc trang tạo mới ở Localhost đều tự động sở hữu UUID v4 duy nhất mà không phụ thuộc vào ID tự tăng của MySQL.*

- [ ] **Bước 2.1:** Mở **Posts ➔ Add New Post** (hoặc **Pages ➔ Add New Page**).
- [ ] **Bước 2.2:** Đặt tiêu đề bài viết: `Test Sync UUID Post 01`.
- [ ] **Bước 2.3:** Bấm **Save Draft** (Lưu nháp).
- [ ] **Bước 2.4:** Kiểm tra trong database hoặc chạy lệnh kiểm tra nhanh qua terminal:
  ```bash
  wp post meta list [ID_BÀI_VIẾT] --path="."
  ```
  *(Hoặc quan sát trong panel Document Settings của Gutenberg - xem Ca 4)*.

> **Kết quả kỳ vọng:** Metadata `_skaaa_uuid` được tự động sinh ra dưới dạng chuỗi UUID v4 chuẩn (ví dụ: `c8a2b3e4-5678-4901-abcd-ef0123456789`).

---

### Ca 3: Kiểm Thử Cột Trạng Thái "Skaaa Sync" trên Danh Sách Bài Viết (`edit.php`)
*Mục tiêu: Đảm bảo bảng danh sách All Posts và All Pages hiển thị trực quan trạng thái đồng bộ của từng bài.*

- [ ] **Bước 3.1:** Mở trang **Posts ➔ All Posts** (`/wp-admin/edit.php`).
- [ ] **Bước 3.2:** Quan sát tiêu đề các cột của bảng:
  - Cột mới **"Skaaa Sync"** xuất hiện ngay cạnh cột Title (Tiêu đề).
- [ ] **Bước 3.3:** Quan sát các dòng bài viết:
  - Với bài viết chưa từng đồng bộ: Hiển thị badge xám `⚪ Not Synced`. Rà chuột vào badge thấy tooltip *"Never synchronized to Live webhost"*.
  - Bên dưới badge có nút bấm xám nhỏ: **`[Cloud] Push`**.
- [ ] **Bước 3.4:** Mở trang **Pages ➔ All Pages** (`/wp-admin/edit.php?post_type=page`):
  - Xác nhận cột **"Skaaa Sync"** cũng hiển thị đồng nhất trên danh sách Trang.

> **Kết quả kỳ vọng:** Cột "Skaaa Sync" hiển thị đẹp mắt, căn chỉnh gọn gàng, huy hiệu rõ ràng và không làm vỡ layout bảng WordPress.

---

### Ca 4: Kiểm Thử Nút Push Nhanh Từng Dòng Qua AJAX trên `edit.php`
*Mục tiêu: Đẩy một bài viết lên Live Webhost trực tiếp từ danh sách bài viết mà không cần mở trình soạn thảo.*

- [ ] **Bước 4.1:** Tại trang **All Posts**, tìm bài viết `Test Sync UUID Post 01` (đang có badge `⚪ Not Synced`).
- [ ] **Bước 4.2:** Bấm nút **`Push`** tại dòng của bài viết đó.
- [ ] **Bước 4.3:** Quan sát nút bấm:
  - Nút chuyển sang trạng thái disabled, icon đám mây xoay tròn (spinner), chữ đổi thành **`Pushing...`**.
- [ ] **Bước 4.4:** Sau 1 - 2 giây khi nhận phản hồi thành công từ Live Webhost:
  - Nút bấm trở lại trạng thái bình thường.
  - Huy hiệu tức thì đổi từ `⚪ Not Synced` sang **`🟢 Synced`** (màu xanh lá tươi sáng).
  - Xuất hiện thêm biểu tượng mũi tên mở link ngoài **`↗`** cạnh nút Push.
- [ ] **Bước 4.5:** Click vào biểu tượng **`↗`**:
  - Trình duyệt mở tab mới dẫn thẳng đến link xem trước bài viết trên Live Webhost.
- [ ] **Bước 4.6:** Rà chuột vào huy hiệu `🟢 Synced`:
  - Tooltip hiển thị chính xác ngày giờ vừa đồng bộ (ví dụ: `Last synced: 2026-10-02 02:40:00`).

> **Kết quả kỳ vọng:** Bài viết được đồng bộ tức thì, giao diện cập nhật Real-time không cần tải lại (F5) trang.

---

### Ca 5: Kiểm Thử Nhận Diện Sửa Đổi Cục Bộ (`⬆️ Local Ahead`)
*Mục tiêu: Đảm bảo hệ thống phát hiện chính xác khi bài viết trên Local có sửa đổi mới hơn bản đã đẩy lên Live.*

- [ ] **Bước 5.1:** Bấm chỉnh sửa bài viết `Test Sync UUID Post 01`.
- [ ] **Bước 5.2:** Thêm một đoạn văn bản mới vào nội dung bài viết và bấm **Save Draft** (hoặc **Update**).
- [ ] **Bước 5.3:** Quay trở lại trang **Posts ➔ All Posts**:
- [ ] **Bước 5.4:** Quan sát cột "Skaaa Sync" của bài viết đó:
  - Huy hiệu tự động đổi sang màu cam hổ phách: **`⬆️ Local Ahead`**.
  - Báo hiệu cho biên tập viên biết bản Local đang có nội dung mới hơn bản trên Live host.
- [ ] **Bước 5.5:** Bấm lại nút **`Push`**:
  - Hệ thống đẩy bản mới nhất lên Live host và huy hiệu chuyển lại thành **`🟢 Synced`**.

> **Kết quả kỳ vọng:** Hệ thống phân biệt chính xác 3 trạng thái: `⚪ Not Synced` ➔ `🟢 Synced` ➔ `⬆️ Local Ahead` ➔ `🟢 Synced`.

---

### Ca 6: Kiểm Thử Thao Tác Đẩy Hàng Loạt (Bulk Push to Live)
*Mục tiêu: Đẩy cùng lúc nhiều bài viết từ Localhost lên Live Webhost chỉ bằng 1 thao tác.*

- [ ] **Bước 6.1:** Tại trang **All Posts**, tích chọn checkbox ở đầu 2 hoặc 3 bài viết khác nhau.
- [ ] **Bước 6.2:** Mở dropdown **Bulk actions** (Hành động hàng loạt) ở phía trên góc trái bảng.
- [ ] **Bước 6.3:** Chọn hành động: **`🚀 Push to Live (Skaaa)`**.
- [ ] **Bước 6.4:** Bấm nút **Apply** (Áp dụng).
- [ ] **Bước 6.5:** Chờ trang xử lý và chuyển hướng lại:
  - Trên đầu trang xuất hiện thông báo màu xanh của WordPress (Admin Notice):  
    `✔ 3 posts pushed to Live webhost successfully.`
  - Toàn bộ các bài viết vừa chọn đều chuyển huy hiệu sang **`🟢 Synced`**.

> **Kết quả kỳ vọng:** Tính năng Bulk Push xử lý mượt mà toàn bộ các bài viết được chọn và báo cáo số lượng thành công/thất bại chính xác.

---

### Ca 7: Kiểm Thử Nút "🚀 Push to Live" trên Gutenberg Header Toolbar
*Mục tiêu: Đẩy bài viết lên Live Webhost ngay trong lúc soạn thảo giao diện trên Gutenberg Editor.*

- [ ] **Bước 7.1:** Mở chỉnh sửa một bài viết bất kỳ bằng Gutenberg Block Editor.
- [ ] **Bước 7.2:** Quan sát thanh công cụ trên cùng (Header Toolbar) bên góc phải:
  - Nút bấm gradient tím/indigo nổi bật xuất hiện: **`🚀 Push to Live`** (nằm ngay cạnh nút Save draft / Publish).
- [ ] **Bước 7.3:** Thử chỉnh sửa một khối text nhưng **chưa bấm Lưu**:
- [ ] **Bước 7.4:** Bấm trực tiếp nút **`🚀 Push to Live`**:
  - Hệ thống tự động kích hoạt tiến trình lưu bài viết trước (`Saving post changes before pushing...`).
  - Nút bấm đổi sang icon tia sét xoay tròn: `⚡ Pushing to Live...`.
- [ ] **Bước 7.5:** Quan sát thông báo nổi (Snackbar / Toast Notice) ở góc dưới màn hình:
  - Thông báo màu xanh bật lên:  
    `✔ Post pushed to Live webhost successfully! [View on Live]`
- [ ] **Bước 7.6:** Click vào nút **View on Live** trên thông báo Toast:
  - Trang web bài viết trên Live host mở ra trong tab mới với nội dung khớp 100%.

> **Kết quả kỳ vọng:** Nút Header Toolbar hoạt động độc lập, tự động lưu bài trước khi push và hiển thị thông báo trực quan hiện đại.

---

### Ca 8: Kiểm Thử Panel "Status & Visibility" Trong Document Sidebar
*Mục tiêu: Quản lý chi tiết trạng thái đồng bộ ngay bên trong bảng cài đặt bài viết của Gutenberg.*

- [ ] **Bước 8.1:** Trong màn hình Gutenberg Editor, mở thanh Sidebar bên phải (bấm icon Bánh răng Settings).
- [ ] **Bước 8.2:** Chọn tab **Post** (hoặc **Page**), mở mục **Summary** (hoặc **Status & visibility**).
- [ ] **Bước 8.3:** Cuộn xuống bên dưới mục Visibility và Publish:
  - Khối **Skaaa Sync** xuất hiện với giao diện thẻ gọn gàng:
    - Tiêu đề: **Skaaa Sync** kèm badge `🟢 Synced` (hoặc `⬆️ Local Ahead`).
    - Dòng thời gian: `Last Synced: 2026-10-02 ...` (hoặc `Never`).
    - Nút bấm xanh: **`Re-sync to Live`** (hoặc `Push to Live`).
    - Nút phụ mở link ngoài: **`↗`**.
- [ ] **Bước 8.4:** Bấm nút **Re-sync to Live**:
  - Trạng thái push diễn ra tương tự và cập nhật lại thời gian đồng bộ mới nhất.

> **Kết quả kỳ vọng:** Khối thông tin tích hợp tự nhiên vào hệ sinh thái Gutenberg API (`PluginPostStatusInfo`), không gây vỡ giao diện Sidebar.

---

### Ca 9: Kiểm Thử Xử Lý Xung Đột (409 Conflict Resolution & Force Overwrite)
*Mục tiêu: Đảm bảo Live Webhost phát hiện khi bản thân nó có sửa đổi mới hơn máy Local, cảnh báo người dùng và hỗ trợ ghi đè an toàn khi được xác nhận.*

- [ ] **Bước 9.1:** Mở trang quản trị của **Website Nhận (Live Webhost)**.
- [ ] **Bước 9.2:** Sửa trực tiếp bài viết đó trên Live Webhost và bấm Update (khiến thời gian sửa đổi trên Live mới hơn Local).
- [ ] **Bước 9.3:** Quay lại **Website Gửi (Localhost)**, mở bài viết đó trên Gutenberg và bấm **`🚀 Push to Live`**.
- [ ] **Bước 9.4:** Quan sát thông báo:
  - Live Webhost từ chối với mã HTTP 409 Conflict.
  - Trên Gutenberg xuất hiện hộp thoại/thông báo cảnh báo màu vàng:  
    `⚠ Conflict Detected: The live website has a newer revision of this post.`  
    Kèm theo nút hành động: **`[Force Overwrite Live]`**.
- [ ] **Bước 9.5:** Bấm **`Force Overwrite Live`**:
  - Hệ thống gửi cờ `force: 1`. Live Webhost tự động tạo WordPress Revision để sao lưu bản cũ, sau đó cho phép ghi đè bản Local lên.
  - Thông báo thành công màu xanh xuất hiện.

> **Kết quả kỳ vọng:** Cơ chế chống ghi đè vô ý (Conflict Prevention) bảo vệ toàn vẹn dữ liệu của Live Webhost và cho phép cưỡng chế an toàn khi cần thiết.

---

## 🛠️ Checklist Tổng Kết Nghiệm Thu (Acceptance Summary)

| STT | Hạng mục kiểm thử | Kết quả | Ghi chú |
| :---: | :--- | :---: | :--- |
| 1 | Cập nhật `skaaai-v1.3.0.zip` không lỗi WSoD | [ ] Pass | |
| 2 | Tự động sinh `_skaaa_uuid` khi tạo bài | [ ] Pass | |
| 3 | Cột "Skaaa Sync" trên All Posts / All Pages | [ ] Pass | Badge 🟢 / ⬆️ / ⚪ |
| 4 | Nút Push nhanh AJAX từng dòng | [ ] Pass | Cập nhật tức thì không cần F5 |
| 5 | Đẩy hàng loạt (Bulk Push to Live) | [ ] Pass | Báo cáo số lượng thành công |
| 6 | Nút "🚀 Push to Live" trên Gutenberg Header | [ ] Pass | Tự động lưu trước khi push |
| 7 | Panel "Skaaa Sync" trong Document Sidebar | [ ] Pass | Tích hợp PluginPostStatusInfo |
| 8 | Bắt xung đột HTTP 409 & Force Overwrite | [ ] Pass | Bảo vệ dữ liệu 2 chiều an toàn |
