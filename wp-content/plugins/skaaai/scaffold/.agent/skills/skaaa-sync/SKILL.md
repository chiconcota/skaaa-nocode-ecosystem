---
name: skaaa-sync
description: Kỹ năng kiểm tra chất lượng, xác thực bài viết và xuất bản 1-Click từ Localhost Dev sang Live Webhost thông qua Skaaai Bridge.
---

# SKILL: SKAAA 1-CLICK SYNC & PUBLISHING

Kỹ năng này hướng dẫn AI Agent cách kiểm tra chất lượng, bảo toàn dữ liệu và xuất bản nội dung an toàn 1-Click từ môi trường phát triển Localhost lên Webhost trực tuyến qua plugin **Skaaai**.

---

## 1. NGUYÊN TẮC VÀNG VỀ XUẤT BẢN & ĐỒNG BỘ
1. **Định danh toàn cầu `_skaaa_uuid`:** Tuyệt đối không dựa vào ID số tự tăng (`AUTO_INCREMENT`) của WordPress khi đồng bộ vì ID trên Live và Local luôn bị lệch. Mọi bài viết phải được định danh bằng UUID v4 lưu tại postmeta `_skaaa_uuid`.
2. **Khóa an toàn buồng lái (Sender-Only Isolation):** Tuyệt đối KHÔNG BAO GIỜ đồng bộ 2 thư mục buồng lái `.agent/` và `.skaaa-ai/` lên máy chủ Live. Live Webhost chỉ đóng vai trò nhận nội dung hiển thị cho người dùng cuối.
3. **Bảo vệ toàn vẹn dữ liệu (Live Revision Shield):** Phía máy chủ Live sẽ tự động gọi `wp_save_post_revision()` trước khi ghi đè, cho phép người dùng khôi phục 1-click trong lịch sử WordPress nếu có sự cố.
4. **Tự động hoán đổi tên miền & Media Sideload:** Động cơ Skaaai tự động thay thế `http://localhost/...` thành tên miền Live và tải hình ảnh về Media Library của hosting.

---

## 2. BẢNG ĐỐI CHIẾU SAI ➔ ĐÚNG (TRÁNH LỖI XUNG ĐỘT LIVE)

| AI Thường Viết SAI (Thói quen cũ) | Cú pháp BẮT BUỘC ĐÚNG của Skaaa | Hậu quả nếu viết sai |
| :--- | :--- | :--- |
| Tìm bài viết trên Live bằng `ID = 45` | Đối soát bằng `_skaaa_uuid = 'e8b2...'` | Ghi đè nhầm vào bài viết khác trên Live! |
| Đẩy nguyên thư mục `.agent/` lên Live | Chỉ đồng bộ bài viết (`wp_posts`), media & JIT CSS | Rò rỉ thông tin bảo mật và làm chậm máy chủ Live. |
| Hardcode URL ảnh `http://mysite.local/img.png` | Dùng URL tương đối hoặc để Skaaai Sideload tự xử lý | Ảnh bị vỡ trang trắng (404) trên website trực tuyến. |
| Xóa bài trực tiếp trên Live | Xóa bài trên Localhost để kích hoạt Cascade Delete | Phá vỡ nguyên lý Localhost Single Source of Truth. |

---

## 3. QUY TRÌNH 4 BƯỚC THỰC HIỆN XUẤT BẢN (PRE-FLIGHT CHECKLIST)

### Bước 1: Kiểm tra Định danh UUID của Bài viết
Trước khi xuất bản, kiểm tra bài viết đã có `_skaaa_uuid` chưa:
```php
$uuid = get_post_meta( $post_id, '_skaaa_uuid', true );
if ( empty( $uuid ) ) {
    $uuid = wp_generate_uuid4();
    update_post_meta( $post_id, '_skaaa_uuid', $uuid );
}
```

### Bước 2: Kiểm tra Trạng thái Kết nối Skaaai Bridge
Xác nhận Sender đã ghép đôi (Pairing) thành công với Live Webhost qua hàm:
```php
$status = \Skaaai_Sync_Engine::check_connection();
// Đảm bảo $status['connected'] === true
```

### Bước 3: Kích hoạt Đồng bộ
- Người dùng bấm nút **"🚀 Push to Live"** trên Gutenberg Editor Toolbar.
- Hoặc AI kích hoạt thông qua endpoint REST API:
  `POST /wp-json/skaaai/v1/sync-post` kèm `post_id`.

### Bước 4: Nghiệm thu & Bàn giao
- Kiểm tra mã phản hồi HTTP `200 OK`.
- Lấy URL bài viết trên Live từ payload phản hồi: `response.data.live_url`.
- Bàn giao link bài viết trên Live cho người dùng nghiệm thu thực tế.
