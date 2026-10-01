# SỔ TAY QUYẾT ĐỊNH KIẾN TRÚC DOANH NGHIỆP (decision-log.md)
@authority: Giám Đốc (Người Dùng) | @status: Bất Biến (Immutable Memory)

> Tài liệu này lưu trữ toàn bộ các quyết định chiến lược và kiến trúc đã được Giám Đốc phê duyệt.  
> Nhân viên AI tuyệt đối không được tự ý lật lại hay hỏi lại những vấn đề đã được chốt trong file này.

---

## CÁC QUYẾT ĐỊNH NỀN TẢNG ĐÃ CHỐT:
1. **Mô Hình Quản Trị Phẳng:** Giám Đốc (Người Dùng) trực tiếp chỉ đạo 4 chuyên viên tinh anh (Account, Designer, Developer, QC). Không dùng AI Giám Đốc trung gian để tránh bẫy tam sao thất bản.
2. **Quy Trình Human-In-The-Loop Cốt Tử:** Cấm tuyệt đối code khi chưa phỏng vấn lấy Logo, Ảnh thật, Menu, Footer và Copywriting.
3. **Kiến Trúc Flat Tables MySQL:** 100% CSDL ứng dụng dùng `wp_skaaa_data_*`, cấm đụng vào `wp_postmeta`.
4. **Tiêu Chuẩn Comment Gutenberg Thuần:** 100% không bọc thẻ HTML thô ngoài comment block.
