---
name: skaaa-logic
description: Kỹ năng chuyên sâu xây dựng đồ thị DAG Workflows, kết nối các Node sự kiện, sử dụng ngôn ngữ biểu thức SkaaaFX AST và phát triển Pluggable Custom Nodes trong Skaaa Logic Engine.
---

# SKILL: SKAAA LOGIC WORKFLOW ENGINE & DAG AUTOMATION

Kỹ năng này hướng dẫn AI Agent cách thiết lập đồ thị luồng sự kiện (DAG Workflows), viết biểu thức nội suy SkaaaFX AST và phát triển các trạm xử lý (Nodes) mở rộng trong hệ sinh thái SKAAA.

---

## 1. NGUYÊN TẮC VÀNG VỀ KIẾN TRÚC LOGIC
1. **Đồ thị DAG có hướng (Directed Acyclic Graph):**
   - Mọi quy trình bắt buộc xuất phát từ một **Trigger Node** (Sự kiện Form submit, Webhook, Click, Timer).
   - Dòng chảy dữ liệu đi một chiều từ đầu vào (Input) sang đầu ra (Output). Tuyệt đối không tạo vòng lặp khép kín gây treo worker.
2. **Biểu thức SkaaaFX AST thuần chủng:**
   - Truy xuất biến payload: `[payload.field_name]` hoặc `[item.id]`.
   - Không tự chế cú pháp template ngoài từ điển (cấm dùng Blade, Twig hay Mustache).
3. **Pluggable & Persistent Storage:**
   - Custom Node PHP phải lưu tại thư mục độc lập `wp-content/skaaa-custom-nodes/` để không bị xóa khi update plugin.
   - Đăng ký Node mới vào hệ thống thông qua filter `skaaa_logic_registered_nodes`.

---

## 2. BẢNG 10 TRẠM LOGIC CỐT LÕI (CORE NODES REGISTRY)

| Mã Node (Type) | Nhóm (Category) | Tên trạm | Chức năng chính |
| :--- | :--- | :--- | :--- |
| `TriggerNode` | `trigger` | **Event Trigger** | Điểm khởi đầu luồng (Form Submit, Button Click, Webhook, Schedule). |
| `SetDataNode` | `data` | **Set Data** | Khai báo biến tạm, tính toán trước khi đẩy sang bước sau (`var total = price * qty`). |
| `DBActionNode` | `data` | **DB CRUD Action** | Thực hiện thao tác Ghi (`insert`, `update`, `delete`) vào bảng phẳng `wp_skaaa_data_*`. |
| `DBQueryNode` | `data` | **DB Query** | Truy vấn lấy danh sách bản ghi từ bảng phẳng theo bộ lọc và sắp xếp. |
| `ConditionNode` | `logic` | **If / Else** | Rẽ 2 nhánh dựa trên kết quả biểu thức SkaaaFX (`true` / `false`). |
| `SwitchNode` | `logic` | **Switch Router** | Rẽ nhiều nhánh phụ thuộc vào giá trị của biến đầu vào. |
| `IteratorNode` | `logic` | **Iterator / Loop** | Lặp qua danh sách mảng dữ liệu, cung cấp biến `[$item]` và `[$index]`. |
| `ApiNode` | `data` | **HTTP Request** | Bắn request REST API ra ngoài (Webhook, CRM, Zalo, Telegram). |
| `ClientResponseNode` | `presentation` | **Client Response** | Bắn lệnh tương tác về trình duyệt người dùng (hiện Toast, mở Modal, redirect). |
| `RenderTemplateNode` | `presentation` | **Render Template** | Nội suy dữ liệu 2 bước vào HTML template (`do_blocks()`). |

---

## 3. CẨM NANG NGÔN NGỮ BIỂU THỨC SKAAAFX AST

Cỗ máy SkaaaFX Evaluator biên dịch chuỗi theo cây AST an toàn:

### A. Truy xuất Biến (Variable Resolution)
* **Dữ liệu Form/Payload:** `[payload.email]`, `[payload.full_name]`, `[payload.phone]` (SkaaaFX tự động rút gọn `payload.` khi đánh giá).
* **Trường dữ liệu bảng phẳng:** `[table_name.column_name]` hoặc `[column_name]`.
* **Biến lặp:** `[item.title]`, `[item.price]`, `[index]`.

### B. Ngân Hàng Hàm Nội Bộ (Built-in Functions)
* **`IF(condition, true_val, false_val)`**: Rẽ nhánh điều kiện có short-circuit (tiết kiệm RAM).  
  *Ví dụ:* `IF([payload.age] >= 18, 'Người lớn', 'Trẻ em')`
* **`CONCAT(arg1, arg2, ...)`**: Ghép chuỗi văn bản.  
  *Ví dụ:* `CONCAT([payload.first_name], ' ', [payload.last_name])`
* **`UPPER(str)` / `LOWER(str)`**: Chuyển đổi chữ hoa / chữ thường.
* **`ROUND(number, precision)`**: Làm tròn số.  
  *Ví dụ:* `ROUND([payload.price] * 1.1, 2)`
* **`IS_NULL(val)`**: Kiểm tra giá trị rỗng/null.
* **`LIST_COL(array_data, 'col_name', ', ')`**: Bóc một cột trong mảng đối tượng thành chuỗi phân cách.

### C. Toán tử Hỗ trợ
* **Toán học:** `+`, `-`, `*`, `/` (tự động chặn lỗi chia cho 0).
* **So sánh:** `==`, `!=`, `>`, `<`, `>=`, `<=`.
* **Logic:** `AND`, `OR`.

### D. BẢNG ĐỐI CHIẾU SAI ➔ ĐÚNG (TRÁNH ẢO GIÁC CÚ PHÁP)

| AI Thường Viết SAI (Thói quen cũ) | Cú pháp BẮT BUỘC ĐÚNG của SkaaaFX | Giải thích lý do |
| :--- | :--- | :--- |
| `{{ $payload->email }}` *(PHP)* | `{{ [payload.email] }}` | SkaaaFX dùng dấu ngoặc vuông `[...]` để bóc tách biến. |
| `{{ payload.email.toUpperCase() }}` *(JS)* | `{{ UPPER([payload.email]) }}` | SkaaaFX là biểu thức hàm lồng nhau (Functional AST). |
| `{{ $age >= 18 ? 'Lớn' : 'Nhỏ' }}` *(Ternary)* | `{{ IF([payload.age] >= 18, 'Lớn', 'Nhỏ') }}` | SkaaaFX dùng hàm `IF(cond, true, false)` có short-circuit. |
| `{{ user.first + " " + user.last }}` | `{{ CONCAT([payload.first], ' ', [payload.last]) }}` | SkaaaFX ghép chuỗi qua hàm `CONCAT`. |
| `{{ [table.created_at].substring(0, 10) }}` | `{{ [table.created_at] }}` | Không có hàm `.substring()`. Cấm gọi method kiểu JS. |

> ⛔ **Thiết quân luật Từ điển Đóng (Closed-World Whitelist):**
> Chỉ được sử dụng đúng 7 hàm chính thức: `IF`, `CONCAT`, `UPPER`, `LOWER`, `ROUND`, `IS_NULL`, `LIST_COL`. Tuyệt đối KHÔNG tự chế hàm lạ ngoài danh sách này.

---

## 4. KỊCH BẢN MẪU: FORM ĐĂNG KÝ ➔ LƯU BẢNG PHẲNG ➔ BÁO TOAST UI

Khi người dùng yêu cầu dựng luồng xử lý Form, AI thiết kế đồ thị DAG gồm 3 bước:

```text
[TriggerNode: form_submit]
          │ (payload: name, email, phone)
          ▼
[DBActionNode: insert into wp_skaaa_data_leads]
  - table: "wp_skaaa_data_leads"
  - action: "insert"
  - fields: {
      "full_name": "[payload.name]",
      "email": "[payload.email]",
      "phone": "[payload.phone]",
      "status": "new"
    }
          │ (record_id)
          ▼
[ClientResponseNode: show_toast]
  - type: "toast"
  - message: "CONCAT('Cảm ơn ', [payload.name], '! Đăng ký thành công.')"
  - toast_type: "success"
  - redirect_url: ""
```

---

## 5. HƯỚNG DẪN VIẾT PLUGGABLE CUSTOM NODE (PHP)

Khi cần tính năng đặc thù (như tích hợp AI, gửi tin nhắn SMS ZNS):
1. **Vị trí tệp:** Đặt tệp PHP tại `wp-content/skaaa-custom-nodes/class-node-[ten-node].php`.
2. **Đăng ký Node qua Filter:**
```php
add_filter( 'skaaa_logic_registered_nodes', function( $nodes ) {
    $nodes['MyCustomNode'] = [
        'type'            => 'MyCustomNode',
        'class'           => 'Skaaa_Node_My_Custom',
        'label'           => 'Gửi SMS OTP',
        'icon'            => 'MessageSquare',
        'description'     => 'Gửi mã xác thực OTP qua Zalo/SMS',
        'color'           => 'bg-blue-50 text-blue-700 border-blue-200',
        'category'        => 'data',
        'settings_schema' => [
            'phone'   => [ 'type' => 'string', 'title' => 'Số điện thoại nhận' ],
            'message' => [ 'type' => 'string', 'title' => 'Nội dung tin nhắn' ],
        ],
    ];
    return $nodes;
} );
```
3. **Thực thi:** Kế thừa hàm `execute( $payload, $settings )` nhận dữ liệu và trả về `$output_payload`.
