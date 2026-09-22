# AGENT SELF-IMPROVEMENT LOG (self-improve.md)
@status: ACTIVE | @last_update: 2026-09-22

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

### MISTAKE-006: Thiếu đăng ký Webpack entry point
- **Lỗi:** Tạo block mới trong `src/` nhưng quên khai báo trong `webpack.config.js`.
- **Sửa đổi:** Luôn kiểm tra và đăng ký entry point webpack trước khi chạy build.

### MISTAKE-008: ArtifactMetadata sai chỗ
- **Lỗi:** Dùng ArtifactMetadata cho file code nguồn dự án ngoài thư mục artifacts.
- **Sửa đổi:** Chỉ dùng cho file markdown nằm trong thư mục artifacts của conversation.

### MISTAKE-009: Thiếu Fail-Safe Fallback cho Dynamic UI
- **Lỗi:** Import thiếu component hoặc thiếu map icon từ backend gây crash trang trắng.
- **Sửa đổi:** Luôn thiết lập fail-safe fallback an toàn (VD: icon mặc định `ServerCog` khi tên icon lạ).

---

## 🟢 LỊCH SỬ LỖI ĐÃ KHẮC PHỤC (RESOLVED)
- **MISTAKE-010 (Xung đột bộ gõ tiếng Việt fcitx5-lotus):** Đã fix triệt để trong `skaaawind.js` (lưu vết `activeIframeDoc`, chỉ reset hash 1 lần khi đổi document, chấm dứt style reflow làm mất chữ bộ gõ).
- **MISTAKE-012 (Editor Selector bị CSV trượt):** Đã fix chuyển sang selector an toàn `:where(.editor-styles-wrapper)` trong `skaaa-editor-helper.js`.
- **MISTAKE-016 (Đặt @import CSS sau các rule khác):** Đã fix chuẩn hóa đưa `@import` lên dòng đầu tiên tuyệt đối trong cả PHP và JS.
- **MISTAKE-017 (Xuất file ZIP đóng gói thiếu số phiên bản):** Đã fix trong `zip-all.js` và `release.js` (tự động trích xuất version PHP xuất `${pluginFolder}-v${version}.zip`).
- **MISTAKE-018 (Xuất Release Notes đơn điệu):** Đã fix trong `release.js` và quy trình `/release-github` (phân loại 3 nhóm Added, Improved, Fixed).

---

> **Quy chuẩn hóa thành Luật vĩnh viễn:**  
> Các lỗi **MISTAKE-002** (i18n tiếng Anh UI), **MISTAKE-004** (Enqueue script dependencies), **MISTAKE-013** (`@click.prevent` Alpine), **MISTAKE-014** (Comment Gutenberg thuần), và **MISTAKE-015** (Zero `!important`) đã được chuyển thành **Luật kỹ thuật vĩnh viễn** trong [wp-architect.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/.agent/rules/wp-architect.md) và [skaaa-nocode-system.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/.agent/rules/skaaa-nocode-system.md).
