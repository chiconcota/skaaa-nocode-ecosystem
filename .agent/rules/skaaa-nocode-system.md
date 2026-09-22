---
trigger: always_on
---

# SKAAA APP BUILDER RULES (v2.0.0)
@target: Skaaa Micro-Ecosystem | @architecture: 4 Plugins + 1 Theme | @ai_token_saving: Maximum (Decoupled Focus)

## 0. KHẨU QUYẾT TỐI THƯỢNG (THE PRIME DIRECTIVES)
1. **No-Postmeta Rule:** Skaaa Data Pro sử dụng bảng phẳng (Flat Tables `skaaa_data_*`). Tuyệt đối không xài lại `wp_postmeta` cho việc xây App. Mọi model mới phải sinh table mới.
2. **Framework-Agnostic WP:** WordPress từ nay bị xem là một nền tảng cung cấp Authentication & Admin UI tạm thời, KHÔNG phải môi trường rễ. Tránh phụ thuộc các class nội địa cũ nếu rủi ro hiệu năng cao.
3. **Decoupled Plugins Rule (Microservices):** Tuyệt đối KHÔNG ĐƯỢC để Plugin A gọi trực tiếp một class của Plugin B. Sự giao tiếp giữa 4 Plugins bắt buộc phải truyền qua WordPress Hooks (`do_action`, `apply_filters`). 
4. **Single Source Of Truth (Global State):** Tuân thủ tuyệt đối chuẩn Nocode Molecule. Không tách vỡ State, Skaaapine Engine phải sử dụng độc quyền hệ sinh thái `Alpine.store` để giao tiếp chéo giữa các Block độc lập.
5. **Clean & Agnostic Core Libraries (SkaaaFX, Skaaapine, Tailwind JIT):** Trong quá trình code hiện tại, bắt buộc viết mã nguồn của SkaaaFX, Skaaapine, và Tailwind PHP JIT cực kỳ sạch, hoàn toàn không phụ thuộc vào WordPress (ví dụ: không gọi trực tiếp các hàm WP như `get_option`, `wp_send_json` bên trong class lõi/parser, mà phải nhận dữ liệu qua tham số truyền vào). Điều này phục vụ việc tách các module này thành package độc lập (Composer/npm) đưa lên open source trong tương lai.


## 1. PHÂN CÁCH TRÁCH NHIỆM (BOUNDARY ISOLATION)
Trước khi Code, Agent BẮT BUỘC nhận diện code mình viết sẽ rơi vào Plugin nào để không lẫn lộn:
- **Skaaa Canvas Theme:** Loại bỏ hoàn toàn `.wp-block-library` và CSS rác của WP. Cung cấp một Blank Canvas thuần khiết, tự động bù trừ thanh Admin Bar cho Header cố định.
- **Skaaa No-Code Design:** Nhốt toàn bộ trách nhiệm builder: Render mã HTML cực sạch (Atomic Blocks), bảng điều khiển Inspector, Skaaapine Engine (Alpine.js) tương tác thời gian thực, cỗ máy JIT Tailwind v4 offline, và bộ chuyển đổi `html2tailwind`. Cấm chỉ định Hardcode CSS Inline.
- **Skaaa Data Pro:** Đọc/Ghi dữ liệu dưới Database (Bảng phẳng `skaaa_data_*`). Trọng tâm vào Schema Manager, DataGrid Strategy và xuất nhập Smart Object Blueprint JSON Native.
- **Skaaa Logic Engine:** Tổ hợp Event-Driven. Khai thác ngôn ngữ biểu thức SkaaaFX DSL (AST Evaluator), đồ thị DAG kéo thả (React Flow), Pluggable Nodes Framework và tiến trình nền bất đồng bộ.
- **Skaaai (AI & Sync Bridge):** Tự động xuất bản Context Manifest (`ai-manifest.json` & `/wp-json/skaaai/v1/context`), Cầu nối đồng bộ bài viết 2 chiều (Local ⟷ Host qua `skaaa_uuid`) và các AI Automation Logic Nodes.

## 2. AI WORKFLOW PROTOCOL BẮT BUỘC
- **Step 1 (Context):** Quét `/1-overview/` để nắm giới hạn ranh giới (System Map).
- **Step 2 (Isolate Memory):** Thay vì đọc toàn bộ, hãy hỏi user xem đang làm việc với Plugin nào? Rồi chỉ quét tài liệu trong `/3-ecosystem/[Tên Plugin]/`. (Ví dụ: Đang dev Theme thì cấm AI đọc doc của Logic Engine).
- **Step 3 (Schema First):** Lên thiết kế Interface & WP Hooks giữa các khối TRƯỚC khi gõ Code vào File. Cấm vội vã.
- **Step 4 (Documentation Loop):** Code xong, cập nhật tài liệu ở `/3-ecosystem/[Plugin]/` và ném Log lịch sử vào `/2-memory/decision-log.md`.

## 3. FILE SIZE LIMIT (DIVIDE & CONQUER)
- Cấm để file to vượt mốc 700 lines. 
- Giữ logic ở các Hook callback, thay vì ném code dồn toa vào constructor `__construct`.

## 4. STYLING & FRONTEND RULE
- Nếu có Tailwind, chỉ xài Design Engine.
- Ở ngoài Editor (Backend JSX), hạn chế hardcode CSS, bắt mọi thuộc tính phải quy về class Tailwind (Nguồn gốc: Single Source Of Truth). Không được tự ý nhúng mã `<style>` nội tuyến nếu không có sự phê duyệt.
- Tránh ghi đè global nếu không có phạm vi cách ly (scoped). Dùng `.skaaaaa-builder [class*='wp-block-skaaaaa-builder']`. Tránh làm gãy Theme khác.
- **Hash-less Event Handler Protocol (Alpine.js):** Tất cả handler sự kiện click trong Alpine.js bắt buộc dùng modifier `@click.prevent` để ngăn chặn hành vi đính `#` vào URL và tự ý nhảy cuộn trang lên đầu.
- **Clean Dynamic Block Comment Protocol:** Dynamic Blocks bắt buộc lưu đúng chuẩn comment Gutenberg thuần (`Container` chỉ chứa inner blocks, `Text`/`Loop` dùng dạng tự đóng `<!-- wp:... {...} /-->`). Tuyệt đối KHÔNG chèn thẻ HTML tĩnh thô (`<main>`, `<div>`) lồng bên trong comment block khiến Gutenberg Validation báo lỗi Invalid Content.

## 5. VERSIONING RULES (QUY TẮC ĐÁNH DẤU PHIÊN BẢN)
- **Chuẩn Semantic Versioning (SemVer):** Tất cả Plugin/Theme trong hệ sinh thái Skaaa bắt buộc tuân thủ định dạng `MAJOR.MINOR.PATCH` (Ví dụ: `1.0.0`).
- **Tự động tăng phiên bản (Auto-Increment):** Mỗi khi Agent sửa đổi/cập nhật tệp nguồn của Plugin/Theme nào, **bắt buộc** phải nâng số phiên bản tương ứng trong file định nghĩa chính (như comment Header của file PHP chính, `style.css` của theme, hoặc `block.json`, `package.json`):
  - **Tăng PATCH (Số thứ 3, ví dụ `1.0.0` -> `1.0.1`):** Đối với các sửa đổi nhỏ, sửa lỗi (bug fixes, hotfixes) tương thích ngược.
  - **Tăng MINOR (Số thứ 2, ví dụ `1.0.0` -> `1.1.0`):** Khi triển khai nhánh tính năng (`feature/`) mới, thêm component, hoặc mở rộng API tương thích ngược.
  - **Tăng MAJOR (Số thứ 1, ví dụ `1.0.0` -> `2.0.0`):** Khi tái cấu trúc hoặc thay đổi lõi làm mất tương thích ngược (breaking changes).
- **Ghi nhận lịch sử (Changelog):** Ghi chú rõ ràng số phiên bản mới và các thay đổi cốt lõi tại `Recent Logs` của `system_map.md` và `decision-log.md` sau khi hoàn thành.