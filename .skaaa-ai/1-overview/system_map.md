# SYSTEM MAP: SKAAA NO-CODE (v2.4.0)
@status: MILESTONE 2 (DEVELOPMENT) | @git_branch: main | @last_update: 2026-09-09

## 1. TECH STACK (APP BUILDER ARCHITECTURE)
- **Backend:** WP Core 6.x + PHP 8.2+ (Host & API)
- **Data (Skaaa Data Pro):** Flat Tables MySQL (`skaaa_data_*`), Schema Manager, Custom Query Builder.
- **Design (Skaaa Design Engine):** Tailwind CSS v4, Local JIT Compiler, React + Gutenberg API, Alpine.js (Skaaa Molecule).
- **Logic (Skaaa Logic Engine):** DAG Canvas Graph (React Flow v11), Expression Evaluator (SkaaaFX AST), Background Worker (Action Scheduler).
- **Skaaai (AI Addon & Sync Bridge):** Tích hợp AI Copilot (Self-Documenting Context), Automation Nodes (Gemini/OpenAI) và Cầu nối đồng bộ hai chiều (Bidirectional Sync Bridge) giữa Localhost và Webhost.

---

## 2. PROJECT STRUCTURE (MICRO-ECOSYSTEM)
```text
wp-content/
├── themes/
│   └── skaaa-canvas/           # [THEME] Blank Canvas (Zero CSS/JS overhead)
└── plugins/
    ├── skaaa-no-code-design/  # [UI/UX] Atomic Blocks, Tailwind JIT, Inspector, Skaaapine, Molecules
    ├── skaaa-data-pro/        # [DATA] Flat Tables DB Engine, Smart Object JSON Blueprint
    ├── skaaa-logic-engine/    # [LOGIC] DAG Workflow Builder, SkaaaFX AST, Async Worker
    └── skaaai/                # [AI & BRIDGE] Self-Documenting Context, Bidirectional Content Sync (Local ⟷ Host), AI Nodes
```
*Giao tiếp chéo:* Độc lập tuyệt đối (Decoupled). Không gọi class chéo, chỉ truyền nhận qua WP Action/Filter hooks và Alpine.js global store (`Alpine.store`).

---

## 3. MODULE REGISTRY & STATUS
| Module Name | Path | Core Function | Status |
| :--- | :--- | :--- | :--- |
| **Skaaa Canvas (Theme)** | `themes/skaaa-canvas/` | Loại bỏ CSS/JS rác của WP, tạo khung canvas sạch. | 🟢 Stable (v1.0.1) |
| **Skaaa No-Code Design** | `plugins/skaaa-no-code-design/` | Custom Blocks, Tailwind JIT, Skaaapine, Molecules. | 🟢 Stable (v2.4.4) |
| **Skaaa Data Pro** | `plugins/skaaa-data-pro/` | Quản lý bảng phẳng MySQL, Schema, Smart Objects. | 🟢 Stable (v1.3.3) |
| **Skaaai (Bridge, Deployer & Harness)** | `plugins/skaaai/` | Cầu nối đồng bộ 1-Click (Local ⟷ Host), Remote Code Deployer & 1-Click Local Agent Harness Initializer. | 🟢 Stable (v1.1.0) |

---

## 4. 🟢 CORE PLATFORM CAPABILITIES (v2.4.x)
- **Flat Tables Architecture:** Lưu trữ toàn bộ dữ liệu ứng dụng và hệ thống (`sys_organisms`, `sys_theme_templates`, `sys_presets`, `sys_apps`, `sys_settings`) qua bảng phẳng MySQL `skaaa_data_*`, triệt tiêu 100% `wp_postmeta`.
- **Zero-CDN Tailwind v4 JIT:** Bộ biên dịch JIT kép (PHP backend & SkaaaWind JS editor) chạy offline 100%, chuẩn hóa Single Source of Truth qua `tailwind-rules.json` với 100% Compiler Parity.
- **Atomic Blocks & Skaaapine:** Hệ thống khối phẳng (`Container`, `Text`, `Button`, `SVG`, `Code`, `Loop`) kết hợp tương tác thời gian thực bằng Skaaapine (Alpine.js) không bị xung đột với WordPress.
- **Decoupled Logic Workflows:** Đồ thị DAG kéo thả (React Flow v11) xử lý sự kiện qua biểu thức SkaaaFX AST, Pluggable Nodes Framework và Action Scheduler.
- **Blank Canvas Host:** Theme `Skaaa Canvas (v1.0.1)` loại bỏ CSS rác của WordPress, tự động bù vị trí Admin Bar cho header cố định.

---

## 5. GLOBAL CONSTRAINTS (FOR AI)
1. **Decoupled Architecture:** Plugins KHÔNG được gọi trực tiếp class của nhau. Mọi giao tiếp bắt buộc qua WP Hooks (`do_action`, `apply_filters`).
2. **Flat Tables First:** Mọi cấu trúc dữ liệu mới phải sử dụng bảng phẳng MySQL (`skaaa_data_*`), không lạm dụng `wp_options` hay `wp_postmeta`.
3. **SemVer Rule:** Tự động tăng số phiên bản (PATCH/MINOR/MAJOR) trong file header khi chỉnh sửa code nguồn của Plugin/Theme.
4. **Zero-Trash Policy:** Nghiêm cấm tạo file `.md` tự do ngoài 4 thư mục chính. Mọi tài liệu cập nhật phải ghi đè trực tiếp (replace) lên file cũ.

---

- **2026-09-23 - 🟢 Done (Skaaai Plugin v1.1.0 - 1-Click Local Agent Harness & Memory Scaffolding Initializer):** Triển khai tính năng khởi tạo buồng lái AI 1-click cho website Localhost (`role === 'sender'`). Sử dụng `WP_Filesystem` xuất bản tự động toàn bộ cấu trúc kép `.agent/` (`rules/`, `skills/` với 3 kỹ năng `skaaa-builder`, `skaaa-flat-db`, `skaaa-sync`, `workflows/` và `harness/`) cùng `.skaaa-ai/` (`1-overview/design.md` nhận diện thương hiệu Design Tokens, `site_map.md` và `2-memory/` checkpoint, decision-log). Khóa bảo vệ nghiêm ngặt: cấm tuyệt đối khởi tạo trên máy chủ Live (`Receiver`), không bao giờ đồng bộ thư mục này lên web hosting. Đóng gói tự động `skaaai-v1.1.0.zip` qua `zip-all.js`.
- **2026-09-23 - 🟢 Done (Skaaai Plugin v1.0.3 - Persistent Custom Nodes Storage outside Plugin Directory):** Di dời toàn bộ thư mục lưu trữ mã nguồn tùy biến từ `wp-content/plugins/skaaai/custom-nodes/` ra thư mục bền vững `wp-content/skaaa-custom-nodes/` (Persistent Storage). Khắc phục triệt để lỗi mất toàn bộ file custom nodes khi người dùng update plugin bằng file zip (do cơ chế WordPress xóa trắng thư mục plugin khi cập nhật). Tích hợp cơ chế tự động khởi tạo và di cư (auto-migration) các file cũ từ legacy plugin dir. Giữ trọn vẹn 100% tốc độ thực thi qua PHP OPcache (0ms) mà không phụ thuộc vào `eval()` hay query Database. Đóng gói tự động `skaaai-v1.0.3.zip` qua `zip-all.js`.
- **2026-09-23 - 🟢 Done (Skaaai Plugin v1.0.2 - 1-Click Sync & Deployment Bridge + Live Deletion Protection):** Khởi tạo và nâng cấp plugin `Skaaai v1.0.2` độc lập. Triển khai cơ chế ghép đôi bảo mật Pairing Protocol (`skaaai_pair://...` 1-click copy/paste, token 64-char, REST API Handshake). Tích hợp module Remote Code Deployer hỗ trợ đồng bộ mã nguồn 2 chiều (tự động lưu bản sao vào `custom-nodes/` của Localhost đồng thời đẩy qua REST API lên Live Webhost an toàn qua `WP_Filesystem` kết hợp lá chắn Syntax Validator ngăn chặn WSoD). Khóa quyền xóa file trực tiếp trên Live Webhost (Receiver Read-only Protection) tuân thủ triệt để nguyên tắc Localhost Single Source of Truth; xóa file trên Local tự động xóa sạch trên Live Webhost. Bổ sung huy hiệu `.bak` và huy hiệu Live Protected trực quan trên bảng quản trị. Xây dựng Post Sync Engine tự động hoán đổi domain URL, nạp media về hosting và tạo WordPress Revisions bảo vệ dữ liệu. Đã tích hợp và đóng gói tự động `skaaai-v1.0.2.zip` qua `zip-all.js`.
- **2026-09-22 - 🟢 Done (Skaaa Canvas Theme v1.0.1 - Admin Bar Offset for Fixed/Sticky Headers):** Bổ sung hàm `skaaa_canvas_admin_bar_fix` hook vào `wp_head` của theme `Skaaa Canvas` khi `is_admin_bar_showing()`. Sử dụng selector ưu tiên tự nhiên `html body.admin-bar.skaaaaa-builder header.fixed, .fixed.top-0, .sticky.top-0` tự động bù `top: 32px` trên Desktop và `top: 46px` trên Mobile (<= 782px). Đảm bảo thanh điều hướng không bị thanh Admin Bar của WordPress che khuất nội dung mà không dùng cờ `!important` và hoàn toàn zero-overhead cho khách vãng lai.
- **2026-09-15 - 🟢 Done (Skaaa No-Code Design v2.4.4 - Gradient Middle Stop via-[#...] & Gradient Stops JIT Parity):** Bổ sung nhận diện `via` vào regex phân giải màu arbitrary hex và `resolveCustomColor` trên cả PHP JIT và JS JIT Compiler (`skaaawind.js`). Chuẩn hóa định dạng biến CSS xuất ra cho bộ 3 gradient stops (`from`, `via`, `to`) hỗ trợ cả nấc độ mờ opacity, đạt 100% Compiler Parity (0 unresolved classes).
- **2026-09-15 - 🟢 Done (Skaaa No-Code Design v2.4.3 - Border Width Directional & Arbitrary JIT Parity):** Bổ sung nhận diện 2 trục `border-x`, `border-y`, các cạnh logic `border-s`, `border-e`, cùng các biến thể kích thước `border-x/y-0/2/4/8` và cú pháp arbitrary `border-x-[...]`, `border-y-[...]`, `border-[...]` vào PHP JIT và JS JIT Compiler (`skaaawind.js`). Cập nhật `tailwind-dictionary.js` phục vụ auto-suggestion trong Gutenberg Inspector. Đạt 100% Compiler Parity (0 unresolved classes).
- **2026-09-14 - 🟢 Done (Skaaa No-Code Design v2.4.2 - Monospace Font, FontFamily, Font Smoothing, Arbitrary Shadow, Font Size & v4 Flex Layout JIT Parity):** Tích hợp cấu hình Monospace Font (Code) trên giao diện Theme Options (Design Tokens). Khai báo biến CSS toàn cục `--font-mono` và CSS reset cho các thẻ `code, kbd, samp, pre`. Bổ sung nhóm utility classes `fontFamily` (`font-mono`, `font-sans`, `font-serif`), Font Smoothing (`antialiased`, `subpixel-antialiased`), Arbitrary Box Shadow (`shadow-[...]`), Arbitrary Font Size (`text-[...]`), các tiện ích Flexbox v4 (`shrink`, `shrink-0`, `grow`, `grow-0`, arbitrary `shrink-[...]`/`grow-[...]`), cùng các hướng đảo ngược Flexbox (`flex-col-reverse`, `flex-row-reverse`, `flex-wrap-reverse`, `flex-nowrap`) vào `tailwind-rules.json`, PHP JIT Compiler và SkaaaWind JS Compiler đảm bảo 100% Compiler Parity. Cập nhật `tailwind-dictionary.js` phục vụ auto-suggestion trong Editor. Giữ nguyên không can thiệp token màu nền `bg-canvas`.
- **2026-09-14 - 🟢 Done (Skaaa No-Code Design v2.4.1 - Frontend Responsive Media Queries & Stitch HTML Import):** Khắc phục triệt để lỗi biên dịch Tailwind Responsive (mất menu desktop navbar và status pill), lỗi Alpine.js scope (`x-data` nesting), chuyển tiếp thuộc tính ảnh (`onerror`) và trích xuất inline script / body attributes trong công cụ `html2tailwind` (`html-to-blocks.js`), giúp trang thiết kế Stitch (`index-lytatthanh.html`) hiển thị 100% nguyên vẹn giao diện và tương tác động client-side (lọc tab bài viết, tìm kiếm thời gian thực).
- **2026-09-09 - 🟢 Done (Skaaa No-Code Design v2.4.0 - Native Skaaa SVG Block, Editor Font Fix & Zero !important Directive):** Triển khai khối mới `Skaaa SVG` (`skaaaaa-builder/svg`) chuẩn Flat DOM, hỗ trợ bóc tách chuyển đổi mã HTML từ `html-to-blocks` (`html2tailwind`), tích hợp song song cả Icon và SVG; sửa triệt để lỗi Google Material Symbols hiển thị chữ trong Gutenberg Canvas qua 3 tầng (`add_editor_style`, top-level `@import`, `ensureFontLink`); loại bỏ 100% cờ `!important` trong `skaaa-editor-helper.js` bằng CSS Specificity Scope.
*(Các logs cũ hơn từ giai đoạn v1.0 - v2.3 đã được lưu trữ đầy đủ tại `.skaaa-ai/2-memory/decision-log.md`)*

---

## 7. ACTIVE ROADMAP (MILESTONE 2 - LOCAL AGENT HARNESS & 1-CLICK SYNC)
- [x] **Phase 1: Plugin Bootstrap, Pairing & Persistent Deployer:** Khởi tạo `wp-content/plugins/skaaai/` (v1.0.3), cài đặt Sender ⟷ Receiver, Remote Code Deployer qua Persistent Storage `wp-content/skaaa-custom-nodes/` và Live Deletion Lock.
- [ ] **Phase 2: Local Agent Harness:** Bộ công cụ/CLI/Scripts hỗ trợ AI dưới Localhost (Block Synthesizer & Validator, Flat DB Inspector, Tailwind JIT Pre-flight Checker).
- [ ] **Phase 3: Giao diện 1-Click Push to Live:** Nút "🚀 Push to Live" trên Gutenberg Editor Toolbar và cột trạng thái "Skaaa Sync" trong danh sách bài viết (`edit.php`).
- [ ] **Phase 4: AI Logic Nodes:** Triển khai các node AI Automation (Prompt & Parser) tích hợp vào Skaaa Logic Engine trên Localhost.
- [ ] **Phase 5: Kiểm thử E2E & Đóng gói Hệ sinh thái:** Kiểm thử Local Harness, Push to Live và đóng gói tự động qua `zip-all.js`.