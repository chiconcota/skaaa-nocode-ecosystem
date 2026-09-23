---
name: skaaa-sync
description: Kỹ năng kiểm tra chất lượng, xác thực bài viết và xuất bản 1-Click từ Localhost Dev sang Live Webhost thông qua Skaaai Bridge.
---

# SKILL: SKAAA 1-CLICK SYNC & PUBLISHING

Kỹ năng này hướng dẫn AI Agent cách thực hiện quy trình đồng bộ nội dung an toàn từ máy cá nhân lên website Live.

## 1. NGUYÊN TẮC VÀNG
1. **Định danh toàn cục `_skaaa_uuid`:** Mỗi bài viết phải có mã UUID v4 duy nhất để triệt tiêu nguy cơ xung đột ID giữa 2 database.
2. **Pre-flight Check:** Kiểm tra kỹ cấu trúc block, đảm bảo không có lỗi cú pháp hoặc link gãy trước khi đồng bộ.
3. **Bảo toàn dữ liệu Live:** Phía máy chủ Live sẽ tự động tạo WordPress Revision trước khi ghi đè, hỗ trợ khôi phục tức thì nếu cần.

## 2. QUY TRÌNH THỰC HIỆN
1. Xác nhận bài viết/trang đã được lưu hoàn chỉnh trên Localhost.
2. Đảm bảo plugin Skaaai đã kết nối thành công với Remote Webhost (Handshake OK).
3. Kích hoạt tính năng Push to Live (từ Gutenberg Toolbar hoặc REST API).
4. Kiểm tra phản hồi thành công và cung cấp URL bài viết trên Live cho User.
