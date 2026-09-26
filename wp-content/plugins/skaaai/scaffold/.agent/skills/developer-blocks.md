# ĐỒ NGHỀ LẬP TRÌNH: CÚ PHÁP 6 ATOMIC BLOCKS & MYSQL FLAT DB (developer-blocks.md)
@phòng_ban: Developer & Backend | @target: Core Developer

> Tài liệu này cung cấp cú pháp JSON attributes của 6 khối Atomic và mẫu code PHP $wpdb tạo bảng phẳng MySQL an toàn.

---

## 1. CÚ PHÁP CHUẨN 6 ATOMIC BLOCKS

| Khối (Block) | Cú pháp Comment | Các thuộc tính hợp lệ (Schema) |
| :--- | :--- | :--- |
| **`container`** | Đóng mở | `tag`: `"div"\|"section"\|"header"\|"footer"\|"nav"`<br>`classes`: `"..."` (Tailwind classes)<br>`skaaapineAttrs`: `"@click.prevent='...'"` |
| **`text`** | Tự đóng `/-->` | `content`: `"..."` (Hỗ trợ HTML inline `<strong>`, `<a>`)<br>`tag`: `"h1"\|"h2"\|"h3"\|"p"\|"span"\|"a"`<br>`classes`: `"..."` |
| **`button`** | Tự đóng `/-->` | `text`: `"..."`, `url`: `"..."`, `classes`: `"..."`, `skaaapineAttrs`: `"@click.prevent='...'"` |
| **`svg`** | Tự đóng `/-->` | `svgCode`: `"<svg ...>...</svg>"`, `classes`: `"w-6 h-6 ..."` |
| **`code`** | Tự đóng `/-->` | `code`: mã script/HTML hoặc thẻ `<img>`, `lang`: `"html"` |
| **`loop`** | Đóng mở | `tableName`: `"wp_skaaa_data_[slug]"`, `query`: `"LIMIT 10"` |

---

## 2. NGUYÊN TẮC CỐT TỬ CỦA DEVELOPER
1. **Comment Gutenberg thuần:** 
   - Khối đóng mở: `<!-- wp:skaaaaa-builder/container {"tag":"div","classes":"..."} -->...<!-- /wp:skaaaaa-builder/container -->`
   - Khối tự đóng: `<!-- wp:skaaaaa-builder/text {"content":"...","tag":"h1","classes":"..."} /-->`
   - CẤM bọc thẻ HTML thô ngoài comment.
2. **Zero Inline CSS:** 100% style trong `classes`. Cấm dùng `style="..."`.
3. **Skaaapine:** Click handler bắt buộc `@click.prevent`.

---

## 3. MẪU PHP $WPDB TẠO BẢNG PHẲNG MYSQL (CHỐNG TREO TERMINAL)

Tuyệt đối không gõ CLI `mysql` trực tiếp. Khi Giám Đốc yêu cầu tạo bảng lưu form/dữ liệu:

```php
global $wpdb;

$table_name = $wpdb->prefix . 'skaaa_data_leads';
$charset_collate = $wpdb->get_charset_collate();

// Tạo bảng phẳng an toàn bằng dbDelta
$sql = "CREATE TABLE {$table_name} (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    fullname varchar(255) NOT NULL,
    email varchar(191) NOT NULL,
    phone varchar(50) DEFAULT '',
    message text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id)
) {$charset_collate};";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta( $sql );
```
