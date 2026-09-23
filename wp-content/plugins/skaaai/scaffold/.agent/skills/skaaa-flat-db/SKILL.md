---
name: skaaa-flat-db
description: Kỹ năng quản trị, tra cứu schema và nạp dữ liệu an toàn vào các bảng phẳng MySQL (skaaa_data_*) của Skaaa Data Pro mà không gây treo shell.
---

# SKILL: SKAAA FLAT DATABASE MANAGER

Kỹ năng này hướng dẫn AI Agent cách tương tác an toàn với cơ sở dữ liệu bảng phẳng `skaaa_data_*`.

## 1. NGUYÊN TẮC VÀNG
1. **No-Postmeta Rule:** Tuyệt đối không lưu dữ liệu ứng dụng vào `wp_postmeta` hay `wp_options`. Toàn bộ dữ liệu nằm trong các bảng phẳng `wp_skaaa_data_*`.
2. **Cấm gõ lệnh `mysql` CLI trực tiếp:** Tránh tuyệt đối việc gọi CLI `mysql -u root ...` vì sẽ làm treo terminal (lỗi MISTAKE-001). Sử dụng các helper script trong `.agent/harness/` hoặc API PHP.
3. **Cột quan hệ dạng JSON gốc:** Mọi dữ liệu quan hệ (`relation`), danh sách (`multi_select`) đều được lưu trữ dưới dạng Native MySQL JSON `[{"id": 101, "label": "Title"}]`.

## 2. QUY TRÌNH TRA CỨU & THAO TÁC DỮ LIỆU
1. Kiểm tra danh sách bảng hiện có tại `.skaaa-ai/1-overview/site_map.md`.
2. Dùng script harness để kiểm tra cấu trúc cột và kiểu dữ liệu trước khi nạp dữ liệu mới.
3. Khi sinh dữ liệu mẫu (mock data), đảm bảo tính toàn vẹn của các khóa ngoại và cấu trúc JSON chuẩn.
