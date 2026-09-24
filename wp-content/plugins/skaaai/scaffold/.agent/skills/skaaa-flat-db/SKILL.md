---
name: skaaa-flat-db
description: Kỹ năng quản trị, tra cứu schema và nạp dữ liệu an toàn vào các bảng phẳng MySQL (skaaa_data_*) của Skaaa Data Pro mà không gây treo shell.
---

# SKILL: SKAAA FLAT DATABASE MANAGER (skaaa_data_*)

Kỹ năng này hướng dẫn AI Agent cách thiết kế, tra cứu và tương tác dữ liệu an toàn với kiến trúc Bảng Phẳng (Flat Tables `skaaa_data_*`) trong hệ sinh thái SKAAA.

---

## 1. NGUYÊN TẮC VÀNG VỀ DỮ LIỆU
1. **No-Postmeta Rule:** Tuyệt đối không lưu dữ liệu ứng dụng vào `wp_postmeta`, `wp_options` hay mô hình EAV cũ của WordPress. Toàn bộ dữ liệu nằm trong các bảng phẳng `wp_skaaa_data_*`.
2. **Cấm gõ lệnh `mysql` CLI trực tiếp:** Tránh tuyệt đối việc gọi CLI `mysql -u root ...` tương tác trực tiếp vì sẽ làm treo terminal (lỗi MISTAKE-001). Mọi thao tác phải dùng script helper trong `.agent/harness/` hoặc API PHP qua `$wpdb`.
3. **Cột quan hệ dạng Native MySQL JSON:** Mọi dữ liệu quan hệ (`relation`), danh sách chọn nhiều (`multi_select`) đều được lưu trữ dưới dạng Native MySQL JSON `[{"id": 101, "label": "Title"}]`.
4. **Bảo vệ bảng hệ thống cốt lõi:** Tuyệt đối không DROP hay xóa dữ liệu trong 5 bảng hệ thống (`sys_organisms`, `sys_theme_templates`, `sys_presets`, `sys_apps`, `sys_settings`).

---

## 2. BẢNG QUY CHUẨN CÁC KIỂU CỘT DỮ LIỆU (FIELD TYPES)

| Kiểu Cột (Type) | Kiểu MySQL | Ví dụ Dữ liệu Mẫu | Ghi chú & Quy định |
| :--- | :--- | :--- | :--- |
| **`text`** | `VARCHAR(255)` / `TEXT` | `"Khóa học React nâng cao"` | Dùng cho tiêu đề, tên, văn bản ngắn. |
| **`long_text`** | `LONGTEXT` | `"<p>Nội dung chi tiết...</p>"` | Dùng cho bài viết, mô tả giàu định dạng. |
| **`number`** | `DECIMAL(12,2)` / `INT` | `1500000` hoặc `4.5` | Giá cả, số lượng, điểm đánh giá. |
| **`date`** | `DATETIME` / `DATE` | `"2026-09-24 08:00:00"` | Ngày khai giảng, ngày hết hạn. |
| **`boolean`** | `TINYINT(1)` | `1` (Active) hoặc `0` (Draft) | Trạng thái hiển thị, bật/tắt tính năng. |
| **`relation`** | `JSON` | `[{"id": 101, "label": "Nguyễn Văn A"}]` | Khóa ngoại mềm nối sang bảng khác. |
| **`multi_select`** | `JSON` | `["VIP", "Hot", "New"]` | Mảng các nhãn hoặc tag chọn nhiều. |
| **`media`** | `VARCHAR(255)` / `JSON` | `{"id": 45, "url": "https://.../img.jpg"}` | Ảnh đại diện, tệp đính kèm Media Library. |

---

## 3. BẢNG ĐỐI CHIẾU SAI ➔ ĐÚNG (TRÁNH LỖI PHÁ VỠ HỆ THỐNG)

| AI Thường Viết SAI (Thói quen cũ) | Cú pháp BẮT BUỘC ĐÚNG của Skaaa | Hậu quả nếu viết sai |
| :--- | :--- | :--- |
| `update_post_meta($id, 'price', 100);` | `$wpdb->update('wp_skaaa_data_courses', ['price' => 100], ['id' => $id]);` | Làm rác bảng `wp_postmeta`, giao diện DataGrid không đọc được. |
| `mysql -u root -p database -e "SELECT..."` | Dùng PHP script helper hoặc `$wpdb->get_results(...)` | **Treo cứng terminal (lỗi MISTAKE-001)**, Agent bị đóng băng. |
| Lưu quan hệ dạng chuỗi CSV `"101,102,103"` | Lưu JSON: `json_encode([['id' => 101, 'label' => 'A']])` | Gãy DataGrid Strategy và lỗi truy vấn Rollup. |
| Tự ý `DROP TABLE wp_skaaa_data_sys_organisms;` | Chỉ tạo/xóa bảng người dùng `wp_skaaa_data_[user_app]` | Phá hủy toàn bộ hệ thống lưu trữ Theme Templates. |

---

## 4. CODE MẪU PHP ĐỂ AI TRA CỨU & NẠP DỮ LIỆU AN TOÀN

Khi cần tạo bảng dữ liệu mới hoặc nạp dữ liệu mẫu (Mock Data):

```php
global $wpdb;

$table_name = $wpdb->prefix . 'skaaa_data_courses';

// 1. Tạo bảng phẳng an toàn bằng dbDelta
$charset_collate = $wpdb->get_charset_collate();
$sql = "CREATE TABLE {$table_name} (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    price decimal(12,2) DEFAULT 0,
    category json DEFAULT NULL,
    instructor json DEFAULT NULL,
    status tinyint(1) DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) {$charset_collate};";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta( $sql );

// 2. Chèn dữ liệu mẫu an toàn (Prepared Query)
$sample_data = [
    'title'      => 'Khóa học Thiết kế Nocode Skaaa',
    'price'      => 1250000,
    'category'   => wp_json_encode( ['Frontend', 'No-code'] ),
    'instructor' => wp_json_encode( [ [ 'id' => 1, 'label' => 'Chuyên gia Skaaa' ] ] ),
    'status'     => 1,
];

$wpdb->insert( $table_name, $sample_data );

// 3. Tra cứu an toàn không treo shell
$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE status = %d LIMIT 5", 1 ), ARRAY_A );
```
