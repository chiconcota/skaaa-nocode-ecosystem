---
description: Kết thúc phiên làm việc App Builder (Ghi nhớ kiến trúc & Bàn giao Checkpoint)
---

Bước 1. **Tổng hợp kiến thức (Knowledge Consolidation):**
   - Rà soát lại toàn bộ các thao tác chỉnh sửa file trong phiên (`write_to_file`, `replace_file_content`, `run_command`).
   - Xác định rõ các thay đổi kiến trúc: WP Hook (Action/Filter) mới, endpoint REST API mới, cấu trúc bảng phẳng MySQL (`skaaa_data_*`) hoặc quy tắc lưu trữ mới.

Bước 2. **Cập nhật Ecosystem Documentation (Kiến trúc cục bộ):**
   - Mở thư mục `.skaaa-ai/3-ecosystem/[Tên Plugin]/` (Ví dụ: `skaaai`, `skaaa-data-pro`).
   - Cập nhật file `architecture.md` của Plugin đó:
     - Boundary Rules (Giới hạn trách nhiệm của tính năng vừa xây).
     - WP Hooks / REST APIs được expose.
     - Luồng dữ liệu mới.

Bước 3. **Lập Biên Bản Bàn Giao Ca Trực BẮT BUỘC (`.skaaa-ai/2-memory/checkpoint.md`):**
   - **ĐÂY LÀ FILE BẮT BUỘC VÀ QUAN TRỌNG NHẤT** để bàn giao ngữ cảnh cho Agent phiên tiếp theo khi chạy `/start_session`.
   - Mở (View_file) và GHI ĐÈ toàn bộ nội dung mới vào `checkpoint.md` theo ĐỦ 5 MỤC CHUẨN:
     1. **Thông tin Môi trường & Nhánh Git:** Nhánh Git hiện tại (`git branch`), phiên bản SemVer của các module.
     2. **File Change Manifest:** Danh sách chi tiết các file đã tạo, sửa hoặc xóa trong phiên kèm link `[file.php](file:///...)`.
     3. **Resolved Issues Log:** Danh sách các lỗi/bug đã gặp phải trong phiên và cách giải quyết dứt điểm.
     4. **Tình trạng Kiểm thử E2E:** Trạng thái các ca test trong file project-managers tương ứng (bao nhiêu ca pass/fail).
     5. **Kế hoạch Bàn giao Phiên Kế Tiếp (Next Actions Checklist):** Danh sách công việc cụ thể từng bước cho Agent phiên sau đọc vào là làm được ngay mà không cần hỏi lại.

Bước 4. **Ghi Sổ Quyết Định & Cập Nhật Bản Đồ Hệ Thống:**
   - Cập nhật `.skaaa-ai/2-memory/decision-log.md`: Thêm ngày và ghi nhận Quyết Định Kiến Trúc Cốt Lõi (Core Architectural Decisions) đã triển khai.
   - Cập nhật `.skaaa-ai/1-overview/system_map.md`:
     - Nâng số phiên bản SemVer trong bảng Module Registry (🔴 Pending -> 🟡 In Progress -> 🟢 Done).
     - Thêm 1 dòng gạch đầu dòng vắn tắt tại mục `Recent Logs` ngày hôm nay.
   - Cập nhật tiến độ vào file tương ứng trong `.skaaa-ai/1-overview/project-managers/` (đánh dấu `[x]` các bước đã xong).

Bước 5. **Đánh giá phiên làm việc tương tác (Interactive Session Review & self-improve):**
   - AI dự thảo danh sách các lỗi hành vi (mistakes) hoặc quy tắc tự sửa lỗi mới phát sinh trong phiên (nếu có).
   - Trình bày danh sách này cho User để nhận phản hồi: *"Bạn có đồng ý ghi nhận các lỗi/quy tắc tự sửa đổi này vào self-improve.md không?"*.
   - Chỉ cập nhật tệp `.skaaa-ai/2-memory/self-improve.md` sau khi User đồng ý. Nếu không có lỗi mới hoặc User không đồng ý thì bỏ qua bước này.
   - Cập nhật trạng thái các lỗi đã giải quyết (Resolved). Nếu độ dài file vượt quá 80 dòng, thực hiện rút gọn, gộp nhóm và di chuyển các lỗi đã sửa lâu ngày vào `.skaaa-ai/2-memory/archive/mistake-history.md`.

Bước 6. **Cleansing Check & Xác Nhận Niêm Phong:**
   - **Cleansing Check:** Tự kiểm tra `git status` xem có bị lỡ tay tạo file `.md` nào ngoài 4 ngăn kéo không (nếu có phải xóa ngay).
   - Hỏi ý kiến User về việc commit và push Git:
     - Nhánh Git làm việc: [Tên Nhánh] (Đã ghi nhận tại `checkpoint.md`).
     - Hỏi User: *"Bạn có muốn commit toàn bộ thay đổi lên nhánh [Tên Nhánh] không?"*.
   - Thông báo rõ ràng: "Sổ bộ nhớ App Builder đã được niêm phong. Checkpoint đã sẵn sàng bàn giao cho phiên tiếp theo."