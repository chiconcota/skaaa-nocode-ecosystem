# E2E Test Workflow: Skaaai 1-Click Sync & Deployment Bridge (v1.0.1)

> [!NOTE]
> Tài liệu này hướng dẫn chi tiết từng bước (Step-by-step) có kèm **Checkbox [ ]** để bạn tự tay kiểm thử toàn diện plugin **`Skaaai v1.0.1`** và đánh dấu tiến độ hoàn thành.

---

## 🎯 Chuẩn Bị Trước Khi Test

### 1. Vị trí các gói cài đặt (ZIP Files)
Toàn bộ các plugin và theme của hệ sinh thái đã được đóng gói sẵn trong thư mục máy tính của bạn:
- Thư mục chứa file ZIP: `/home/chiconcota/Local Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/`
- Danh sách 5 gói cần thiết:
  1. `skaaa-canvas.zip` (Theme Blank Canvas v1.0.1)
  2. `skaaa-no-code-design.zip` (Design Engine v2.4.4)
  3. `skaaa-data-pro.zip` (Database Flat Tables v1.3.3)
  4. `skaaa-logic-engine.zip` (Logic Automation v1.3.0)
  5. `skaaai-v1.0.1.zip` (Bridge & Code Deployer v1.0.1)

### 2. Mô hình Bạn Đang Kiểm Thử:
- **Site Gửi (Sender / Local Dev):** `http://lytatthanh-localremote.local`
- **Site Nhận (Receiver / Online Webhost):** `https://lytatthanh.com`

---

## 🧪 Các Ca Kiểm Thử Chi Tiết (Test Cases)

---

### Ca 1: Thiết Lập Website Nhận & Lấy Pairing Key (Receiver Setup)
*Mục tiêu: Cấu hình website đóng vai trò Receiver trên lytatthanh.com, sinh mã khóa bí mật và sao chép chuỗi skaaai_pair://...*

- [x] **Bước 1.1:** Đăng nhập vào Admin của **Website Nhận** (`https://lytatthanh.com/wp-admin/`).
- [x] **Bước 1.2:** Cài đặt và Kích hoạt plugin `skaaai-v1.0.0.zip`.
- [x] **Bước 1.3:** Truy cập menu bên trái: **Skaaa Ecosystem ➔ Bridge & Sync** (hoặc menu **Skaaa Bridge**).
- [x] **Bước 1.4:** Tại mục **Site Role Configuration**, chọn: **Receiver (Live Webhost / Production)**.
- [x] **Bước 1.5:** Chuyển sang Tab **Code & Node Deployer**, tích chọn: **`Allow Remote Code & Pluggable Node Deployment`** *(để cho phép nhận file code PHP)*.
- [x] **Bước 1.6:** Quay lại Tab **Pairing & Connection**, bấm nút **Save Configuration**.
- [x] **Bước 1.7:** Tại ô **Universal Pairing Key**, bấm nút xanh: **`📋 Copy Key`**.

> **Kết quả kỳ vọng:** Nút bấm đổi sang chữ *"Copied to clipboard!"*. Bạn đã lưu trong clipboard một chuỗi có định dạng `skaaai_pair://ey...`.

---

### Ca 2: Bắt Tay Ghép Đôi 1-Click (Sender Handshake)
*Mục tiêu: Tự động điền cấu hình và bắt tay kết nối hai chiều từ Localhost sang Live Webhost qua REST API.*

- [x] **Bước 2.1:** Mở Admin của **Website Gửi (Localhost)** (`http://lytatthanh-localremote.local/wp-admin/`).
- [x] **Bước 2.2:** Truy cập menu: **Skaaa Ecosystem ➔ Bridge & Sync**.
- [x] **Bước 2.3:** Tại mục **Site Role Configuration**, chọn: **Sender (Localhost Dev / PC)**.
- [x] **Bước 2.4:** Tại ô **Paste Pairing Key**, dán chuỗi `skaaai_pair://...` vừa copy ở Ca 1.
- [x] **Bước 2.5:** Bấm nút: **`Auto-fill Connection Details`** (Kiểm tra ô Remote URL điền `https://lytatthanh.com`).
- [x] **Bước 2.6:** Bấm nút: **`🔍 Test Connection (Handshake)`**.
- [x] **Bước 2.7:** Bấm nút: **`Save Configuration`** màu xanh ở góc dưới để lưu cấu hình.

> **Kết quả kỳ vọng:** Dòng chữ xanh bật lên:
> `✔ Connection established successfully! (Lý Tất Thành) | ⚡ Code Push: Enabled`

---

### Ca 3: Kiểm Thử Lá Chắn Cú Pháp PHP (Syntax Validator Shield)
*Mục tiêu: Chứng minh website nhận có khả năng ngăn chặn 100% các đoạn code PHP lỗi, không bao giờ để xảy ra lỗi sập web (White Screen of Death).*

- [x] **Bước 3.1:** Tại **Website Gửi (Localhost)**, chuyển sang tab **Code & Node Deployer**.
- [x] **Bước 3.2:** Tại ô **File Name**, nhập: `class-broken-node.php`.
- [x] **Bước 3.3:** Tại ô **PHP Source Code**, cố ý nhập một đoạn code PHP **bị lỗi cú pháp** (thiếu dấu ngoặc nhọn `{`):
  ```php
  <?php
  defined( 'ABSPATH' ) || exit;

  class Broken_Node {
      public function run() {
          echo "This code has unclosed curly brace!";
  ```
- [x] **Bước 3.4:** Bấm nút: **`🚀 Deploy to Remote Webhost`**.

> **Kết quả kỳ vọng:** Khung console màu đen hiển thị thông báo lỗi màu đỏ ngay lập tức:
> `✖ DEPLOY FAILED: Syntax check failed: PHP Parse Error on line 7: Unclosed '{' on line 4`  
> Phía website `lytatthanh.com` hoàn toàn an toàn, không có file lỗi nào được ghi.

---

### Ca 4: Triển Khai Custom Node Mới 1-Click (1-Click Code Push & Persistent Storage)
*Mục tiêu: Đẩy một file Node PHP hợp lệ từ máy Local lên thư mục bền vững wp-content/skaaa-custom-nodes/ của lytatthanh.com.*

- [x] **Bước 4.1:** Tại tab **Code & Node Deployer** trên Website Gửi, nhập lại thông tin:
  - **File Name:** `class-alert-box-node.php`
  - **PHP Source Code:** Dán đoạn code chuẩn sau:
    ```php
    <?php
    defined( 'ABSPATH' ) || exit;

    class Skaaa_Alert_Box_Node implements Skaaa_Logic_Node {
        public function execute( $payload, $config ) {
            $payload['alert_status'] = 'Active from Remote Node!';
            return [ 'payload' => $payload, 'port' => 'main' ];
        }
    }
    ```
- [x] **Bước 4.2:** Đảm bảo tích chọn: *Overwrite if file exists*.
- [x] **Bước 4.3:** Bấm nút: **`🚀 Deploy to Remote Webhost`**.
- [x] **Bước 4.4:** Chờ 1 giây và quan sát phản hồi trong console (`✔ DEPLOY SUCCESS! Target File: class-alert-box-node.php`).
- [x] **Bước 4.5:** Mở Admin của `https://lytatthanh.com`, vào tab **Code & Node Deployer** ➔ Kiểm tra bảng **Active Custom Nodes on this Server** xem file đã xuất hiện chưa.
- [x] **Bước 4.6:** Thử bấm Deploy lại lần nữa trên Local ➔ Kiểm tra hệ thống tự động sinh bản sao lưu `class-alert-box-node.php.bak`.

---

### Ca 5: Xóa File Custom Node An Toàn & Bảo Vệ Live (Local SSoT Deletion)
*Mục tiêu: Đảm bảo Live Webhost KHÔNG được phép xóa trực tiếp file PHP (Live Protected), mọi thao tác xóa xuất phát từ Localhost (Single Source of Truth) và tự động đồng bộ dọn sạch trên Live Webhost.*

- [x] **Bước 5.1:** Trên Website Nhận (`lytatthanh.com`), kiểm tra hàng có file `class-alert-box-node.php` trong bảng ➔ Nút Delete được bảo vệ (Live Protected).
- [x] **Bước 5.2:** Trên Website Gửi Local, bấm nút đỏ: **`Delete`**.
- [x] **Bước 5.3:** Khi hộp thoại xác nhận hiện lên, bấm **OK** ➔ Hệ thống tự động xóa sạch file cả trên Local và Live Webhost qua REST API.

> **Kết quả kỳ vọng:** Hàng chứa file `class-alert-box-node.php` mờ dần và biến mất khỏi bảng trên cả 2 site. File và cả bản sao lưu `.bak` được xóa sạch an toàn khỏi server.

---

## 🛠️ Xử Lý Sự Cố Thường Gặp (Troubleshooting)

| Triệu chứng | Nguyên nhân | Cách khắc phục nhanh |
| :--- | :--- | :--- |
| **Báo lỗi `Error 403: Invalid security token`** | Token giữa 2 máy không khớp (do bấm Regenerate Token nhưng chưa copy lại). | Bấm nút **Copy Key** bên Web Nhận và dán lại vào Web Gửi, bấm *Auto-fill* và *Save*. |
| **Báo lỗi `Remote code deployment is disabled`** | Website Nhận chưa bật tính năng cho phép nhận file PHP. | Vào Web Nhận ➔ Tab *Code & Node Deployer* ➔ Tích chọn `Allow Remote Code Deployment` ➔ Bấm Save. |
| **Báo lỗi `Network Error connecting to remote site`** | Sai URL website nhận hoặc website nhận chưa bật server. | Kiểm tra lại URL (đảm bảo gõ đúng `http://` hoặc `https://`), kiểm tra web nhận có đang mở được trên trình duyệt không. |
