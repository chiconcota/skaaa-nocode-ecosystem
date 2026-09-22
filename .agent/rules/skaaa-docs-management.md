---
trigger: always_on
---

# SKAAA AI DOCUMENTATION MANAGEMENT RULE (NO-MESS POLICY)
@target: All AI Agents | @trigger: /end_session or any documentation update

## 1. THIẾT QUÂN LUẬT KHÔNG RÁC (ZERO-TRASH DIRECTIVE)
- Mọi tài liệu bắt buộc phải nằm gọn bên trong 4 ngăn kéo. **Dưới đây là Cấu trúc Hệ thống (Directory Tree) BẮT BUỘC tuân thủ:**

```text
.skaaa-ai/
├── 1-overview/
│   ├── system_map.md           (Bản đồ kiến trúc tổng thể, Trạng thái Dự án, Nhật ký ngắn)
│   └── project-managers/       (Thư mục chứa các Roadmap, Kế hoạch cho từng Phase)
├── 2-memory/
│   ├── decision-log.md         (Quy định Kiến trúc lớn, Lịch sử thay đổi cốt lõi)
│   ├── checkpoint.md           (Bàn giao tiến độ dở dang và nhánh Git hiện tại cho phiên sau)
│   └── archive/                (Lưu trữ các quyết định và log cũ của các Phase trước)
├── 3-ecosystem/
│   ├── skaaa-canvas/             (Tài liệu kiến trúc Theme Canvas, Blank Layout)
│   ├── skaaa-no-code-design/     (Tài liệu kiến trúc Design, UI/UX, Blocks, JIT)
│   ├── skaaa-data-pro/           (Tài liệu kiến trúc Database, Smart Object Blueprint)
│   ├── skaaa-logic-engine/       (Tài liệu kiến trúc Logic, Event Pipeline, Form)
│   └── skaaai/                   (Tài liệu kiến trúc AI Copilot, Context Manifest & Sync Bridge)
└── 4-rules/
    └── (Tầng chứa các quy chuẩn/luật lệ AI cũ - Thường đã được di chuyển sang thư mục .agent/rules/)
```

## 2. QUY TRÌNH GHI ĐÈ CHỐNG LỖI (ANTI-DUPLICATION PROTOCOL)
- Trước khi cập nhật bất kỳ tài liệu nào (`system_map.md`, `decision-log.md`...), AI BẮT BUỘC phải dùng Lệnh `Hỏi Hệ Thống (List_dir)` hoặc `View_file` để xem file đó đang nằm ở cấu trúc nào, nội dung hiện tại ra sao.
- **Nghiêm Cấm:** Tự ý dùng lệnh tạo file mới (Write_to_file) đối với các file trung tâm nếu chưa kiểm tra sự tồn tại của file cũ. Nếu không tìm thấy, AI phải thông báo cho User thay vì im lặng tạo bản sao rác.

## 3. LUẬT NGUYÊN TẮC /END_SESSION BẮT BUỘC
- Khi gọi luồng `/end_session`, AI phải Mở (View_file) và Sửa nội dung TRỰC TIẾP lên các file cũ.
- Chỉ cập nhật **Tối đa 5 điểm chạm:**
  1. `1-overview/system_map.md` (Sửa trạng thái Status & Recent Logs).
  2. `2-memory/decision-log.md` (Chèn Note lên đầu file, di chuyển log quá hạn vào `archive/` nếu dài).
  3. `2-memory/checkpoint.md` (Bàn giao tiến độ dở dang và nhánh Git hiện tại cho phiên sau).
  4. `3-ecosystem/[tên-plugin]/tên-file.md` (Chỉnh sửa Kiến trúc cục bộ vừa Code xong).
  5. `1-overview/project-managers/` Cập nhật tiến độ làm việc tương ứng với file phase đang làm việc.

## 4. CLEANSING CHECK (BƯỚC CHUẨN BỊ COMMIT GIT)
- Trước khi push code lên Git, AI phải tự phán xét: "Mình có bị lỡ tay đẻ ra cái file .md nào nằm ngoài hệ thống 4 folder không?". Nếu có thì tự động dùng Lệnh Xóa (Remove) hoặc hỏi ý kiến User ngay lập tức.

## 5. QUY LUẬT TẠO TÀI LIỆU (ANTI-CLUTTER DIRECTIVE)
- **Hạn chế tạo rác:** Yêu cầu các Agent Tuyệt đối KHÔNG ĐƯỢC tự ý tạo mới bất kỳ file Markdown (`.md`) nào ở thư mục gốc (Root) của project hoặc thư mục gốc của `.skaaa-ai` nếu chưa xin phép.
- **Bắt buộc hỏi Ý kiến:** Nếu trong lúc thực hiện luồng /end_session hoặc theo lệnh từ Workflow mà nhận thấy CẦN PHẢI TẠO file mới nằm ngoài 4 thư mục lớn (`1-overview`, `2-memory`, `3-ecosystem`, `4-rules`) thì **BẮT BUỘC phải đặt câu hỏi xin phép User trước**. Nếu User trả lời OK/Yes thì mới được phép khởi tạo.
- Khuyến nghị: Ưu tiên update trực tiếp (Replace_content) trên các tài liệu đã có sẵn trong 4 ngăn kéo để hạn chế sinh file rác.