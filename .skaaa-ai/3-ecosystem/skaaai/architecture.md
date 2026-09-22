# MODULE: Skaaai (AI Copilot & Bidirectional Sync Bridge)
*Plugin độc lập cung cấp tính năng AI Copilot, Context Manifest tự chủ và Cầu nối đồng bộ bài viết 2 chiều trong hệ sinh thái SKAAA.*

**Status:** 🟡 Planning (Milestone 2)  
**Role:** [AI & BRIDGE] Self-Documenting Context, Bidirectional Content Sync (Local ⟷ Host), AI Logic Nodes.  
**Dependency:** Hoạt động độc lập hoặc kết hợp với `skaaa-logic-engine`, `skaaa-data-pro`, `skaaa-no-code-design`.

---

## 1. Kiến Trúc Phân Chia Trách Nhiệm (Decoupled Integration)
Skaaai tuân thủ triệt để nguyên tắc Decoupled Architecture, giao tiếp 100% qua WordPress Hooks và REST API bảo mật:
- **Zero-Postmeta & Flat Tables:** Cấu hình hệ thống (Pairing Key, Remote URL, Secret Token, LLM API Keys) được lưu trữ tại bảng phẳng MySQL `wp_skaaa_data_sys_settings` của `skaaa-data-pro` (nếu có) hoặc fallback an toàn vào bảng phẳng cục bộ.
- **Pluggable Nodes Framework:** Đăng ký các Node AI (`AIPromptNode`, `AIParserNode`) vào đồ thị Logic Engine thông qua hook `apply_filters( 'skaaa_logic_registered_nodes', ... )`.
- **Gutenberg Editor Integration:** Nạp nút bấm 1-Click "🚀 Push to Live" trực tiếp trên Editor Toolbar mà không can thiệp sâu vào code lõi của Design Engine.

---

## 2. Ba Trụ Cột Năng Lực Cốt Lõi (The 3 Pillars)

### Trụ cột 1: Self-Documenting Context Engine (AI Copilot Ready)
- **Mục tiêu:** Giúp AI ngoài môi trường (như Antigravity/Cursor/Windsurf) hiểu thấu đáo toàn bộ hệ thống Blocks, DB Schemas khi dev trên site thật mà không cần thư mục `.skaaa-ai`.
- **Thực thi:**
  - Tự động sinh và lưu cache file tĩnh: `wp-content/uploads/skaaa-cache/ai-manifest.json`.
  - Cung cấp REST API Endpoint: `GET /wp-json/skaaai/v1/context` (Yêu cầu `X-Skaaai-Key` bảo mật).
  - Nội dung context: Danh mục Atomic Blocks (`Container`, `Text`, `Loop`...), từ điển class Tailwind v4 hợp lệ, danh sách bảng phẳng `skaaa_data_*` và cấu trúc các trường dữ liệu.

### Trụ cột 2: Bidirectional Content Sync Engine (Localhost ⟷ Webhost)
- **Mục tiêu:** Đồng bộ bài viết, landing page thiết kế bằng Skaaa giữa máy tính cá nhân (Local) và Webhost (Production/Staging) 2 chiều an toàn, không sợ lệch ID tự tăng (Auto Increment ID) của WordPress.
- **Định danh toàn cục (`skaaa_uuid`):**
  - Mỗi bài viết được cấp 1 mã định danh duy nhất (UUID v4) lưu tại bảng phẳng `wp_skaaa_data_sync_map` hoặc post metadata fallback.
  - Khi đồng bộ, hệ thống đối soát dựa trên `skaaa_uuid` thay vì `ID` số của WordPress.
- **Cơ chế an toàn (Data Safety):**
  - **Phát hiện xung đột (Conflict Detection):** Kiểm tra `last_modified` trước khi ghi đè. Nếu Webhost có người sửa mới hơn Local, cảnh báo User chọn giải pháp (Force Overwrite / Pull from Remote / Cancel).
  - **Tự động tạo Revision:** Trước khi ghi đè trên Receiver, luôn gọi `wp_save_post_revision()` để có thể Undo phục hồi 1-click trong WordPress History.

### Trụ cột 3: AI Logic Nodes (Automation Graph Integration)
- **`AIPromptNode` (`Skaaai_Node_Prompt`):**
  - Đăng ký vào Logic Engine. Nhận prompt, nội suy biến động bằng `SkaaaFX_Engine::execute()`.
  - Gửi request đến LLM (Google Gemini / OpenAI) và trả về kết quả vào pipeline payload.
- **`AIParserNode` (`Skaaai_Node_Parser`):**
  - Đọc văn bản thô phi cấu trúc và trích xuất thành JSON có cấu trúc bằng `responseSchema` (Gemini) hoặc JSON Mode (OpenAI).

---

## 3. Quy Trình Ghép Đôi & Bảo Mật (Pairing Protocol)
1. **Thiết lập vai trò (Role):** User chọn chế độ trong Admin:
   - **Sender (Localhost):** Nơi thiết kế, gửi dữ liệu đi.
   - **Receiver (Webhost):** Máy chủ đích nhận dữ liệu.
2. **Khởi tạo Pairing Key:** Receiver sinh một chuỗi mã hóa:
   `skaaai_pair://[base64_encoded_remote_url_and_secret_token]`
3. **Kết nối:** Dán Pairing Key vào Sender. Hai bên bắt tay (Handshake) qua `/wp-json/skaaai/v1/handshake` để kích hoạt trạng thái kết nối.
