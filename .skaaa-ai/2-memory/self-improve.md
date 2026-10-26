# AGENT SELF-IMPROVEMENT LOG (self-improve.md)
@status: ACTIVE | @last_update: 2026-10-01

> Nhật ký tự cải thiện hành vi và sửa sai của Agent. Chứa các lỗi thao tác thực tế và quy tắc tự sửa lỗi.
> **Luật dọn dẹp:** File này không được vượt quá 80 dòng. Các lỗi đã giải quyết (Resolved) sau 3 phiên sẽ được lưu trữ.

---

## 🚨 DANH SÁCH LỖI HÀNH VI ĐANG ĐƯỢC GIÁM SÁT (ACTIVE)

### MISTAKE-001: CLI mysql tương tác trực tiếp
- **Lỗi:** Chạy CLI mysql trực tiếp qua terminal gây treo shell.
- **Sửa đổi:** Tuyệt đối không chạy lệnh `mysql` trực tiếp. Báo cáo ngay cho User để được hỗ trợ truy vấn.

### MISTAKE-003: Lạm dụng Browser MCP & Screenshot
- **Lỗi:** Tự ý chạy DevTools, Browser Subagent hoặc chụp screenshot liên tục gây tốn token.
- **Sửa đổi:** Chỉ kích hoạt browser/screenshot khi User yêu cầu rõ ràng. Ưu tiên checklist từng bước cho User test tay.

### MISTAKE-019: Markdown-Only Trap & Schema Mismatch trong Design Phase
- **Lỗi:** Chỉ viết tài liệu markdown chay mà không nạp Design Tokens / Organisms vào CSDL phẳng MySQL (`wp_skaaa_data_sys_presets`, `wp_skaaa_data_sys_organisms`). Dùng tên token tự chế không khớp với Schema của hệ thống.
- **Sửa đổi:** Luôn Database-First! Dùng `php .agent/harness/db-tool.php --set-tokens` và `--save-organism` để nạp CSDL trước, sau đó mới đồng bộ tài liệu `brand-guidelines.md`.

### MISTAKE-020: Bẫy Auto-Progression (Cầm đèn chạy trước ô tô) & Bypass Theme Builder
- **Lỗi:** (1) Tự ý nhảy cóc phase khi chưa có prompt của Giám Đốc. (2) Nhét cứng Header/Footer vào `post_content` thay vì đăng ký Theme Template vào `wp_skaaa_data_sys_theme_templates`.
- **Sửa đổi:** Dừng lượt TUYỆT ĐỐI sau mỗi giao phẩm! Header/Footer bắt buộc dùng `php .agent/harness/db-tool.php --save-organism="..." --name="HeaderBar" --category="header" --as-template=header`. Cấm nhét vào `post_content`.

### MISTAKE-021: Lạm dụng Inline Code cho UI Native, Thẻ Ảnh/Video Thô & Bỏ quên Form Engine 3 Chân Vạc
- **Lỗi:** (1) Dùng `code` block nhét mã HTML/JS cho Button, Dark Mode Toggle, SVG hay Mobile Menu thay vì dùng block native `button` (`actionType: "theme_toggle"`), `svg`. (2) Dùng `code` block nhét thẻ `<img>`/`<video>` thô do thiếu tra cứu native block. (3) Hardcode class màu cụ thể (`text-amber-400`, `bg-[#10131a]`) thay vì class Design Tokens (`text-primary`, `bg-surface`, `border-border`). (4) Tự viết `<form>` HTML thô thay vì dùng Form Engine 3 chân vạc (`isSkaaaForm: true` + `formActionId: "insert_[slug]"` + `input`/`select`/`form-rich-text`).
- **Sửa đổi:** 100% dùng block native! Hình ảnh bắt buộc dùng `skaaaaa-builder/image` (khi ảnh không vuông 1:1, BẮT BUỘC chỉ định `"aspectRatio": "aspect-auto"` hoặc `"aspect-[W/H]"` để tránh bị render engine tự động cắt vuông). Toggle Dark Mode dùng `button` (`actionType: "theme_toggle"`). Dùng 100% class token. Form dùng `container` form + `input`/`select`/`form-rich-text` tự động liên kết Data Pro & Logic Engine.

### MISTAKE-022: Thiếu khai báo namespace khi tách file View Template MVC
- **Lỗi:** Khi tách giao diện HTML ra file view riêng trong thư mục `views/*.php`, không khai báo `namespace` ở đầu file. Do PHP include không tự thừa hưởng namespace của caller, các lời gọi class tĩnh nội bộ bị tìm ở Global namespace dẫn đến Fatal error `Class not found`.
- **Sửa đổi:** BẮT BUỘC khai báo `namespace [Vendor];` (ví dụ: `namespace Skaaai;`) ngay đầu mọi file View template trong `views/` để đảm bảo cơ chế tự động phân giải namespace nội bộ hoạt động chính xác.

### MISTAKE-023: Release Notes Format Mismatch
- **Lỗi:** Xuất file Release Notes dạng bullet list trích xuất thô từ `system_map.md` thay vì định dạng chuẩn **Keep a Changelog** của `fcitx5-lilypad`.
- **Sửa đổi:** Khi đóng gói và phát hành (`/release-github`), luôn phân loại và định dạng changelog theo 3 phân mục chuẩn Keep a Changelog: `🚀 Added / Improved`, `🐛 Fixed`, `🧹 Refactored / Clean Code`.

### MISTAKE-024: Freeform HTML Comments trong cấu trúc Gutenberg Blocks gây Attempt Recovery
- **Lỗi:** Tự ý chèn các dòng comment HTML thông thường (`<!-- 1. HEADER... -->`, `<!-- Top Row... -->`, `<!-- ARTICLE CARD -->`) vào trong file template/organism để phân chia vùng code. Gutenberg Parser đọc các comment này thành Text/Comment Node lạ, gây lỗi `Block validation failed` và kích hoạt nút `Attempt recovery`.
- **Sửa đổi:** 100% dùng comment token chuẩn Gutenberg (`<!-- wp:... -->` và `<!-- /wp:... -->`), TUYỆT ĐỐI KHÔNG chèn comment HTML tự do. BẮT BUỘC chạy `php .agent/harness/block-tool.php --validate` để kiểm định tự động trước khi nạp vào CSDL.

---

## 🟢 LỊCH SỬ LỖI ĐÃ KHẮC PHỤC (RESOLVED)
- **MISTAKE-006 & 008 & 009:** Đã fix entrypoint Webpack, ArtifactMetadata đúng thư mục artifacts, và fallback icon an toàn.
- **MISTAKE-010 (fcitx5-lotus):** Đã fix trong `skaaawind.js` (chấm dứt style reflow làm mất chữ bộ gõ).
- **MISTAKE-012, 016, 017, 018:** Đã fix Editor Selector, @import CSS, zip versioning và changelog SemVer.

---

> **Quy chuẩn hóa thành Luật vĩnh viễn:**  
> Các lỗi **MISTAKE-002** (i18n UI), **MISTAKE-004** (Enqueue scripts), **MISTAKE-013** (`@click.prevent`), **MISTAKE-014** (Gutenberg comments), **MISTAKE-015** (Zero `!important`) và **MISTAKE-021** đã được quy định vĩnh viễn trong [company-rules.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/.agent/rules/company-rules.md).
