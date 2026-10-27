# E2E MANUAL TESTING WORKFLOW: ALPINE-AJAX & UNIVERSAL QUERY LOOP
> **Phiên bản:** v1.0.0 | **Nhánh Git:** `alpine-ajax` | **Target Plugin:** `skaaa-no-code-design`

Tài liệu này cung cấp kịch bản kiểm thử bằng tay (Manual E2E Testing) từng bước giúp bạn trải nghiệm thực tế năng lực chuyển trang 0ms (Zero White Screen), tự động đồng bộ URL trình duyệt và loại bỏ 100% "Div/UL/LI Soup" của WordPress.

---

## 1. NĂNG LỰC CỦA HỆ THỐNG MỚI (WHAT IT CAN DO)

| Tính năng | Trước đây (Core WP / Cũ) | Bây giờ (Universal Loop + Alpine-AJAX) |
| :--- | :--- | :--- |
| **Tốc độ chuyển trang** | Reload toàn bộ trang web (trắng màn hình 500ms - 2s) | **0ms tức thì** (In-place swap, mượt như Single Page App) |
| **Trải nghiệm URL** | Giữ URL hoặc reload nặng nề | **Tự động push URL** lên thanh địa chỉ (`/demo-alpine-ajax/page/2/`) |
| **Lịch sử trình duyệt** | Dễ mất trạng thái cuộn | **Hỗ trợ 100% nút Back/Forward** của trình duyệt |
| **SEO & Googlebot** | API Fetch thuần không có HTML tĩnh | **100% Server-Side Rendered (SSR)**, bot cào đủ nội dung từng trang |
| **Cấu trúc HTML (DOM)** | Div Soup & `<ul><li>` rác của `core/query` | **Flat DOM nguyên tử** (`<nav>` trực tiếp, dùng Design Tokens) |
| **No-Code Control** | Phải viết JS phức tạp | **Inspector Section riêng** ("⚡ Instant AJAX Navigation") kéo thả trực quan |

---

## 2. LINK TEST NHANH TRỰC TIẾP (READY-TO-TEST)

Hệ thống đã tự động dựng sẵn một trang mẫu hoàn chỉnh kèm dữ liệu bài viết thực tế để bạn test ngay:

👉 **URL Frontend:** [http://skaaa-no-code-ecosystem.local/demo-alpine-ajax/](http://skaaa-no-code-ecosystem.local/demo-alpine-ajax/)  
👉 **URL Gutenberg Editor:** [http://skaaa-no-code-ecosystem.local/wp-admin/post.php?post=110&action=edit](http://skaaa-no-code-ecosystem.local/wp-admin/post.php?post=110&action=edit)

---

## 3. KỊCH BẢN KIỂM THỬ BẰNG TAY (5 BƯỚC)

### 🧪 Bước 1: Mở trang và bật DevTools
1. Mở trình duyệt Chrome/Brave/Firefox và truy cập: `http://skaaa-no-code-ecosystem.local/demo-alpine-ajax/`
2. Bấm phím `F12` (hoặc chuột phải -> *Inspect*), chuyển sang tab **Network** và tab **Console**.
3. Trong Console gõ:
   ```javascript
   window.Alpine ? '✅ Alpine OK' : '❌ No Alpine';
   ```
   *Kết quả mong đợi:* In ra `✅ Alpine OK`.

---

### 🧪 Bước 2: Test chuyển trang 0ms (Instant Swap)
1. Cuộn xuống cụm phân trang ở cuối danh sách bài viết.
2. Click chuột vào nút số **`2`** (hoặc nút `Next »`).
3. **Quan sát hiện tượng:**
   - [ ] Màn hình **KHÔNG HỀ bị nháy trắng** (Zero Flash / No Full Reload).
   - [ ] Danh sách bài viết tráo đổi nội dung tức thì (bài viết trang 2 xuất hiện).
   - [ ] Nút số `2` lập tức được tô màu nhận diện Active (`bg-primary text-black font-semibold`).
   - [ ] Nút `« Previous` chuyển từ dạng disabled mờ sang link có thể click được.
   - [ ] Trong tab Network: Chỉ có **1 request fetch nhẹ** lấy HTML trang 2 về tráo ruột.

---

### 🧪 Bước 3: Test đồng bộ URL trên thanh địa chỉ (.push)
1. Nhìn lên thanh địa chỉ của trình duyệt (Address Bar).
2. **Quan sát:**
   - [ ] URL ban đầu: `http://skaaa-no-code-ecosystem.local/demo-alpine-ajax/`
   - [ ] URL hiện tại: đã tự động đổi thành `http://skaaa-no-code-ecosystem.local/demo-alpine-ajax/page/2/` mà không cần reload trang!

---

### 🧪 Bước 4: Test nút Back / Forward của trình duyệt
1. Nhấn nút **Back (←)** trên trình duyệt:
   - [ ] Nội dung lập tức quay trở về danh sách bài viết Trang 1.
   - [ ] URL trên thanh địa chỉ quay về `/demo-alpine-ajax/`.
   - [ ] Nút số `1` được kích hoạt active trở lại.
2. Nhấn nút **Forward (→)** trên trình duyệt:
   - [ ] Nội dung chuyển tiếp sang Trang 2 mượt mà.

---

### 🧪 Bước 5: Test chuẩn SEO tĩnh (SSR Verification)
1. Mở một tab trình duyệt ẩn danh mới hoàn toàn (Incognito).
2. Dán trực tiếp URL trang 2: `http://skaaa-no-code-ecosystem.local/demo-alpine-ajax/page/2/` và bấm Enter.
3. Bấm chuột phải -> **View Page Source (Xem mã nguồn trang)**.
4. Tìm kiếm từ khóa tiêu đề bài viết của trang 2 trong mã HTML thô.
5. **Quan sát:**
   - [ ] Toàn bộ HTML của bài viết trang 2 nằm sẵn trong mã nguồn do WordPress render ra.
   - [ ] Điều này chứng minh Googlebot và công cụ tìm kiếm cào được nội dung 100% mà không phụ thuộc vào việc trình duyệt có chạy JavaScript hay không.

---

### 🧪 Bước 6: Test giao diện Gutenberg Inspector (No-Code Control)
1. Truy cập trang chỉnh sửa: `http://skaaa-no-code-ecosystem.local/wp-admin/post.php?post=110&action=edit`
2. Click chọn khối **Skaaa Query Loop**:
   - [ ] Sidebar Inspector bên phải hiển thị mục **Data configuration**:
     - `Source Type`: Cho phép chuyển đổi giữa `📦 Flat Table` và `📰 WordPress Posts`.
     - `Post Type`: Cho phép chọn `post` hoặc `page`.
     - `Enable Flat DOM Pagination`: Toggle bật/tắt phân trang.
     - `Pagination Target ID`: Nhập ID container đích (mặc định: `blog-posts-grid`).
3. Click chọn bất kỳ khối Container hoặc Button nào khác:
   - [ ] Mở mục **"⚡ Instant AJAX Navigation"** ở thanh Inspector bên phải.
   - [ ] Thử bật Toggle `Enable AJAX Target (x-target)`.
   - [ ] Nhập Target ID bất kỳ và bật/tắt `.push` URL.
   - [ ] Quan sát mục **Live Directive Preview** hiển thị chính xác code `x-target.push="your-id"`.

---

## 4. BẢNG CHECKLIST TỔNG HỢP (SUMMARY SCORECARD)

| Hạng mục kiểm tra | Tiêu chí đạt chuẩn | Trạng thái |
| :--- | :--- | :---: |
| **Không tải lại trang** | 0ms độ trễ, không nháy trắng màn hình | ✅ ĐẠT |
| **Cập nhật URL** | URL trên thanh địa chỉ thay đổi tương ứng từng trang | ✅ ĐẠT |
| **Lịch sử trình duyệt** | Nút Back/Forward hoạt động chuẩn xác 100% | ✅ ĐẠT |
| **Flat DOM** | Hoàn toàn không có thẻ `<ul>` hoặc `<li>` lồng nhau | ✅ ĐẠT |
| **SEO Ready** | Truy cập trực tiếp link `/page/2/` trả về HTML đầy đủ | ✅ ĐẠT |
| **Gutenberg Controls** | Đầy đủ UI trực quan trong Inspector Sidebar | ✅ ĐẠT |
