# SKAAA FLAT DATABASE RULES (skaaa-data.md)
@target: Skaaa Data Pro | @mode: Local Development | @architecture: Flat Tables

## 1. NO-POSTMETA DIRECTIVE
- Toàn bộ dữ liệu nghiệp vụ của ứng dụng bắt buộc lưu trong các bảng phẳng MySQL `wp_skaaa_data_*`.
- Tuyệt đối KHÔNG sử dụng `wp_postmeta`, `wp_options` hay mô hình EAV cũ của WordPress để lưu dữ liệu ứng dụng.

## 2. CLI SAFETY (CẤM LỆNH MYSQL TRỰC TIẾP)
- Tuyệt đối KHÔNG thực thi lệnh dòng lệnh `mysql -u root ...` tương tác trực tiếp (lỗi MISTAKE-001 gây treo shell).
- Mọi thao tác tra cứu schema và nạp dữ liệu mẫu phải thông qua PHP scripts helper trong `.agent/harness/` hoặc API PHP.

## 3. NATIVE MYSQL JSON CHO CỘT QUAN HỆ & MẢNG
- Mọi trường dữ liệu quan hệ (`relation`), chọn nhiều (`multi_select`) hoặc mảng đối tượng phải lưu bằng kiểu dữ liệu `JSON` gốc của MySQL.
- Định dạng chuẩn: `[{"id": 101, "label": "Title"}]`. Cấm lưu dạng chuỗi phân cách dấu phẩy CSV `"1,2,3"`.

## 4. BẢO VỆ BẢNG HỆ THỐNG CỐT LÕI (ATOMIC SYSTEM TABLES)
- Tuyệt đối KHÔNG xóa (DROP) hay tự tiện thay đổi cấu trúc 5 bảng hệ thống cốt lõi:
  - `wp_skaaa_data_sys_organisms`
  - `wp_skaaa_data_sys_theme_templates`
  - `wp_skaaa_data_sys_presets`
  - `wp_skaaa_data_sys_apps`
  - `wp_skaaa_data_sys_settings`
