# WORKFLOW: SKAAA APP BUILDER PIPELINE (build_app.md)
@trigger: /build_app hoặc lệnh yêu cầu xây dựng ứng dụng / bảng dữ liệu / tính năng từ A-Z
@mode: Human-In-The-Loop (HITL) Gated Orchestration

Quy trình này hướng dẫn AI Agent từng bước kiến tạo một Ứng Dụng (App) hoặc Tính Năng hoàn chỉnh trong hệ sinh thái SKAAA, tuân thủ nguyên tắc **Nạp Skill theo nhu cầu (On-Demand Loading)** và **Dừng lại xin phê duyệt ở các khâu trọng yếu (Human Gating)**.

---

## BƯỚC 1: THIẾT KẾ SCHEMA BẢNG PHẲNG (HUMAN-IN-THE-LOOP)
> 🎯 **Chỉ nạp tài liệu:** `.agent/skills/system-design/SKILL.md` (Tư duy thực thể & quan hệ), `.agent/skills/skaaa-flat-db/SKILL.md` (Thực thi bảng phẳng) và `.skaaa-ai/1-overview/site_map.md`.

1. **Phân tích nghiệp vụ:** Xác định các thực thể dữ liệu cần thiết cho ứng dụng (ví dụ: Học viên, Khóa học, Đơn hàng).
2. **Thiết kế Schema phẳng:**
   - Tên bảng phẳng: `wp_skaaa_data_[app_slug]`.
   - Danh sách cột: Tên cột, kiểu dữ liệu (`text`, `number`, `date`, `json`, `relation`, `multi_select`).
   - Cột quan hệ dạng Native MySQL JSON: `[{"id": 101, "label": "Title"}]`.
3. 🛑 **CỔNG KIỂM SOÁT 1 (HUMAN APPROVAL GATE - BẮT BUỘC DỪNG LẠI):**
   - Trình bày bảng thiết kế Schema rõ ràng cho Người Dùng.
   - **Bắt buộc hỏi ý kiến người dùng:** *"Bạn xem cấu trúc bảng [tên_bảng] này đã đúng ý chưa? Có cần thêm, bớt hoặc điều chỉnh cột nào trước khi tôi khởi tạo vào CSDL không?"*
   - **DỪNG LẠI CHỜ PHẢN HỒI:** Tuyệt đối KHÔNG tự ý chạy lệnh tạo bảng khi người dùng chưa xác nhận phê duyệt (Approved).

---

## BƯỚC 2: RẼ NHÁNH SMART OBJECT BLUEPRINT (ĐIỀU KIỆN LOGIC)
> 🎯 **Thực hiện sau khi Bước 1 đã được duyệt và khởi tạo bảng thành công.**

1. **Đánh giá nhu cầu đóng gói:**
   - Nếu bảng chỉ dùng nội bộ cục bộ cho trang hiện tại ➔ Bỏ qua bước này, tiến thẳng tới Bước 3.
   - Nếu bảng là cấu trúc dùng chung (như Danh mục CRM, LMS, E-commerce) cần đóng gói để di chuyển sang website khác:
     - Xuất bản tệp Smart Object Blueprint JSON lưu trữ tại CSDL hệ thống (`wp_skaaa_data_sys_presets`).
     - Tự động ghi chú vào `.skaaa-ai/1-overview/site_map.md`.

---

## BƯỚC 3: THIẾT KẾ GIAO DIỆN NHÁP (HUMAN-IN-THE-LOOP)
> 🎯 **Chỉ nạp tài liệu:** `.agent/skills/ui-ux-design/SKILL.md` (Tư duy phối màu 60-30-10 & Visual Hierarchy), `.agent/skills/skaaa-builder/SKILL.md` (Thực thi Atomic Blocks) và `.skaaa-ai/1-overview/design.md`.

1. **Phác thảo Wireframe:**
   - Xác định dạng hiển thị: List View (Dạng Thẻ Grid 3 cột hay Bảng DataGrid?), Detail View (Form xem chi tiết).
   - Áp dụng các Design Tokens từ `design.md`: Màu sắc thương hiệu, font chữ `font-sans`, bo góc `rounded-2xl`, hiệu ứng hover.
   - Lựa chọn các khối Atomic chuẩn: `Container`, `Text`, `Button`, `SVG`, `Loop`.
2. 🛑 **CỔNG KIỂM SOÁT 2 (HUMAN APPROVAL GATE - BẮT BUỘC DỪNG LẠI):**
   - Trình bày bản phác thảo bố cục (Layout Structure) và phong cách thẩm mỹ cho Người Dùng.
   - **Bắt buộc hỏi ý kiến người dùng:** *"Tôi đề xuất bố cục giao diện dạng [Grid/Table/Detail] với phong cách trên. Bạn duyệt giao diện này chưa để tôi sinh mã chính thức?"*
   - **DỪNG LẠI CHỜ PHẢN HỒI:** Chờ người dùng xác nhận hoặc yêu cầu chỉnh sửa trước khi sinh mã.

---

## BƯỚC 4: TỰ ĐỘNG SINH TRANG & HOÀN TẤT (AUTONOMOUS EXECUTION)
> 🎯 **Chỉ nạp tài liệu:** `.agent/skills/skaaa-theme-builder/SKILL.md` (nếu cần gắn template) hoặc `.agent/skills/skaaa-builder/SKILL.md`.

1. **Sinh mã Gutenberg Atomic Blocks chuẩn:**
   - Tạo trang mới hoặc template tương ứng với cây block đã được duyệt ở Bước 3.
   - Khối `skaaaaa-builder/loop`: Chỉ định chính xác `sourceTable` là tên bảng đầy đủ (`wp_skaaa_data_...`).
   - Đảm bảo 100% cú pháp comment Gutenberg thuần, bắt buộc `@click.prevent` nếu có tương tác Alpine.js.
2. **Kiểm tra chất lượng (Verification):**
   - Kiểm tra các class Tailwind đã được biên dịch đúng bởi JIT offline.
   - Đảm bảo không sinh thẻ HTML thô bên ngoài comment block.
3. **Cập nhật bộ nhớ:**
   - Cập nhật trang mới và bảng dữ liệu mới vào `.skaaa-ai/1-overview/site_map.md`.
   - Bàn giao URL hoặc ID bài viết cho người dùng nghiệm thu.
