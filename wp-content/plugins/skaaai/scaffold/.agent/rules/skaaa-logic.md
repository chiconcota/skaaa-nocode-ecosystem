# SKAAA LOGIC ENGINE RULES (skaaa-logic.md)
@target: Skaaa Logic Engine | @mode: Local Development | @architecture: DAG & Event-Driven

## 1. ĐỒ THỊ DAG CÓ HƯỚNG KHÔNG CHU TRÌNH (DIRECTED ACYCLIC GRAPH)
- Mọi Workflow tự động hóa phải tuân thủ chuẩn đồ thị DAG: Luồng chạy có hướng rõ ràng từ Trigger Node ➔ Action Nodes.
- Tuyệt đối KHÔNG tạo vòng lặp khép kín (Cycle Loop / Recursive Flow) gây tràn bộ nhớ và treo worker.

## 2. CHUẨN BIỂU THỨC SKAAAFX AST (EXPRESSION DSL)
- Nội suy biến và biểu thức logic bắt buộc tuân thủ đúng cú pháp SkaaaFX AST:
  - Truy xuất biến payload: `{{ [payload.field_name] }}` hoặc `{{ [item.id] }}`.
  - Gọi hàm xử lý: `{{ UPPER([payload.email]) }}`, `{{ CONCAT([payload.first_name], ' ', [payload.last_name]) }}`.
- Cấm tự chế cú pháp Blade/Mustache không có trong từ điển AST của SkaaaFX.

## 3. PLUGGABLE NODES & LƯU TRỮ MÃ NGUỒN BỀN VỮNG
- Custom Nodes PHP phải được đặt trong thư mục bền vững `wp-content/skaaa-custom-nodes/` (Persistent Storage), không lưu trực tiếp trong lõi plugin.
- Đăng ký Node mới vào hệ thống thông qua WordPress Filter `skaaa_logic_registered_nodes`.
- Tác vụ nặng, gửi email hàng loạt hoặc gọi API bên ngoài phải đưa vào Background Worker (Action Scheduler), không chạy đồng bộ làm chậm phản hồi HTTP.
