---
description: Bắt đầu phiên làm việc chuẩn chuẩn Vibecoding App Builder (Load Context & Memory)
---

1. **Khởi động bộ nhớ (Context Loading - Mới):**
   - Đọc file `.skaaa-ai/2-memory/checkpoint.md` LÀ BƯỚC ĐẦU TIÊN để nhận bàn giao trạng thái công việc, Git branch, và bug đang làm dở từ phiên trước.
   - Dựa trên file checkpoint, đọc file project manager đang làm, trong thưc mục '.skaaa-ai/1-overview/project-managers' để biết công việc đang ở giai đoạn nào 
   - Đọc file `.skaaa-ai/1-overview/system_map.md`. Đây là bước BẮT BUỘC để nắm cấu trúc App Builder
   - Đọc file `.skaaa-ai/2-memory/decision-log.md` (Chỉ đọc phần Bàn giao kiến trúc và các Quyết định gần nhất ở đầu file để tiết kiệm token, không đọc ngược về lịch sử cũ).
   - Đọc file `.skaaa-ai/2-memory/self-improve.md` để nạp danh sách lỗi hành vi cần tránh và quy tắc tự sửa lỗi của Agent.

2. **Xác định tiêu điểm & Nhánh làm việc (Focus & Git Branch Decision):**
   - Nếu người dùng chưa chỉ định rõ Plugin mục tiêu, hãy hỏi để cô lập vùng làm việc.
   - Kiểm tra nhánh Git hiện tại (dùng `git branch` hoặc `git status`).
   - Cùng người dùng quyết định: **Nên làm việc trực tiếp trên nhánh `main` hay cần khởi tạo/chuyển sang một nhánh tính năng mới (`feature/ten-tinh-nang`)?** 
   - Ghi nhận thông tin nhánh này để cập nhật vào `checkpoint.md` khi kết thúc phiên.

3. **Load Plugin Memory (Context-Switching):**
   - Dựa vào Plugin mục tiêu, đọc TOÀN BỘ tài liệu liên đới trong thư mục `.skaaa-ai/3-ecosystem/[Tên Plugin]/`.
   - Lưu ý: Tuyệt đối KHÔNG đọc lộn tài liệu của Plugin không liên quan để tránh Hallucination/Tràn RAM.

4. **Sẵn sàng (Ready Check):**
   - Phản hồi ngắn gọn: "Đã nạp kiến trúc App Builder (system_map.md) và Nhật ký tự cải thiện (self-improve.md). Đang làm việc trên nhánh Git: [Tên Nhánh]. Đang cô lập vùng làm việc vào Plugin: [Tên Plugin]. Sẵn sàng nhận lệnh."