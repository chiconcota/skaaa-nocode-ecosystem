---
description: Kết thúc phiên làm việc App Builder (Ghi nhớ kiến trúc & Decision Log)
---

Bước 1. **Tổng hợp kiến thức (Knowledge Consolidation):**
   - Rà soát lại toàn bộ các tool `write_to_file`, `replace_file_content` đã thực hiện trong phiên hiện tại.
   - Xác định các thay đổi kiến trúc: Có thêm WP Hook (Action/Filter) nào mới để giao tiếp xuyên Plugin không? Tên bảng Flat Tables (`skaaa_data_*`) nào được tạo?

Bước 2. **Cập nhật Ecosystem Documentation (Đường sinh mệnh của App Builder):**
   - Mở thư mục `.skaaa-ai/3-ecosystem/` và tìm Plugin tương ứng mà bạn vừa thao tác (Vd: `skaaa-data-pro`).
   - Tạo hoặc Cập nhật file markdown kiến trúc của Plugin đó với nội dung:
     - Boundary Rules (Giới hạn trách nhiệm của đoạn code vừa viết).
     - WP Hooks được Expose để Plugin khác móc vào.
     - Luồng dữ liệu mới.

Bước 3. **Ghi sổ quyết định (Decision Log, System Map, self-improve & Update):**
   - Đọc và cập nhật file `.skaaa-ai/2-memory/decision-log.md` bằng cách thêm ngày hôm nay và các Quyết Định Kỹ Thuật Lõi (Core Technical Decisions) đã được chốt/triển khai.
   - Đọc và cập nhật file `.skaaa-ai/1-overview/system_map.md`:
     - **Tình trạng Plugin/Theme:** Chuyển trạng thái từ 🔴 Pending -> 🟡 In Progress -> 🟢 Done trong bảng Module Registry.
     - **Change Log:** Thêm dòng gạch đầu dòng ngắn ngọn cho ngày cập nhật.
   - Mở và GHI ĐÈ dữ liệu vào file tương ứng với module tương ứng `.skaaa-ai/1-overview/project-managers/` để lưu lại tiến độ dự án.
   - Mở và GHI ĐÈ dữ liệu vào file `.skaaa-ai/2-memory/checkpoint.md` để lưu lại tiến độ đang code dở, danh sách file, các lỗi hiện tại, và đặc biệt phải ghi rõ tên nhánh Git hiện tại đang làm việc để bàn giao cho Agent phiên sau.
   - **Cleansing Check & Cập nhật Git:** Trước khi commit, tự kiểm tra xem có lỡ tạo file `.md` nào ngoài 4 ngăn kéo không (nếu có phải xóa ngay). Sau đó hỏi ý kiến User xem có nên commit và push lên GitHub không? Nếu đang ở nhánh feature, có cần tạo Pull Request hoặc merge vào `main` luôn không? Thực hiện theo quyết định của User.
Bước 4. **Đánh giá phiên làm việc tương tác (Interactive Session Review & self-improve):**
   - AI dự thảo danh sách các lỗi hành vi (mistakes) hoặc quy tắc tự sửa lỗi mới phát sinh trong phiên.
   - Trình bày danh sách này cho User để nhận phản hồi: *"Bạn có đồng ý ghi nhận các lỗi/quy tắc tự sửa đổi này vào self-improve.md không?"*.
   - Chỉ cập nhật tệp `.skaaa-ai/2-memory/self-improve.md` sau khi User đồng ý hoặc đưa ra chỉnh sửa.
   - Cập nhật trạng thái các lỗi đã giải quyết (Resolved). Nếu độ dài file vượt quá 80 dòng, thực hiện rút gọn, gộp nhóm và di chuyển các lỗi đã sửa lâu ngày vào `.skaaa-ai/2-memory/archive/mistake-history.md`.
Bước 5. **Xác nhận kết thúc:**
   - Thông báo rõ ràng: "Sổ bộ nhớ App Builder đã được niêm phong. 
     - Nhánh Git làm việc: [Tên Nhánh] (Đã ghi nhận tại checkpoint.md)
     - Trạng thái Push/Merge: [Quyết định và kết quả đã thực hiện]
     - File bàn giao (Checkpoint) đã được lưu mốc an toàn.
     - Danh sách công việc (project manager) đã được cập nhật.
     - Docs lưu tại: `.skaaa-ai/3-ecosystem/`
     - Lịch sử thiết kế được dán vào Decision Log.
     Phiên làm việc kết thúc an toàn, hệ sinh thái sẵn sàng cho phiên kế tiếp không mất ngữ cảnh."