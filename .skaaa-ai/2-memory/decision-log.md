# DECISION LOG & ARCHITECTURAL HANDOVER
@status: ACTIVE | @milestone: 2 (IN PROGRESS) | @last_update: 2026-09-22

---

## BÀN GIAO KIẾN TRÚC TOÀN DIỆN MILESTONE 1 (PHASE 1, 2, 3 & 4)
> Phần đúc kết này tổng hợp toàn bộ các quyết định kiến trúc nền tảng bất biến (Architectural Pillars) được kiểm chứng qua 4 Phase phát triển của Milestone 1. Đây là kim chỉ nam giúp toàn bộ AI Agents và lập trình viên giữ vững tính toàn vẹn của hệ thống khi bước vào Milestone 2.

### 1. Phase 1: Nền tảng Giao diện & Atomic Blocks (Skaaa No-Code Design & Skaaa Canvas)
- **The Blank Canvas Theme (Skaaa Canvas):** Loại bỏ 100% CSS/JS mặc định của WordPress (`.wp-block-library`), thiết lập một môi trường vẽ sạch (Clean Slate). Tự động bù trừ thanh WordPress Admin Bar (`top: 32px` desktop, `46px` mobile) bằng CSS selector ưu tiên tự nhiên, triệt tiêu 100% cờ `!important` và hoàn toàn zero-overhead cho khách vãng lai.
- **Atomic Blocks & Flat DOM:** Xây dựng hệ thống khối giao diện nguyên tử phẳng (`Container`, `Text`, `Button`, `SVG`, `Code`, `Loop`), không sinh `<div>` bọc thừa. Khối `Container` quản lý inner blocks thuần khiết; khối `SVG` render vector trực tiếp Flat DOM.
- **Single Source of Truth (SSoT) & Tailwind JIT Parity:** Tập trung toàn bộ quy tắc biên dịch CSS vào file tĩnh `tailwind-rules.json`. Cả bộ biên dịch PHP JIT (Frontend) và SkaaaWind JS JIT (Gutenberg Editor) đều nạp động từ file này, đạt **100% Compiler Parity (0 unresolved classes)**, hỗ trợ đầy đủ arbitrary values (`text-[...]`, `shadow-[...]`, `border-[...]`, arbitrary colors `#hex`), flexbox v4 (`shrink`, `grow`) và gradient middle stop (`via-[#...]`). Không phụ thuộc bất kỳ CDN ngoài nào.
- **Skaaapine Engine (Alpine.js Scope Isolation):** Sử dụng độc quyền `Alpine.store` toàn cầu để giao tiếp chéo giữa các block độc lập. Nhận diện chuẩn cú pháp rút gọn `:`, loại bỏ hiện tượng scope shadowing (không tự ý tiêm `x-data=""` vào block con), đảm bảo tương tác động thời gian thực mượt mà.
- **Bộ Chuyển Đổi `html2tailwind` (`html-to-blocks.js`):** Chuyển đổi mã HTML thô từ Stitch/Figma thành cây block Skaaa Gutenberg, tự động bóc tách `x-data` trên `<body>` và chuyển đổi thẻ `<script>` inline thành block `skaaaaa-builder/code`.

### 2. Phase 2: Hệ Thống Dữ Liệu Bảng Phẳng (Skaaa Data Pro)
- **No-Postmeta Rule:** Khai tử hoàn toàn mô hình EAV (`wp_postmeta`) lề mề. Mọi ứng dụng và cấu trúc dữ liệu mới phải tự sinh bảng phẳng MySQL (`skaaa_data_*`).
- **Native MySQL JSON Storage:** Thay thế hoàn toàn cách lưu trữ CSV chuỗi text cũ. Mọi trường quan hệ `relation`, `multi_select` và mảng đều được lưu bằng kiểu dữ liệu **`JSON` gốc của MySQL**, tự động Enrich payload thành mảng đối tượng `[{id: 101, label: "Title"}]`.
- **Atomic System Tables Protection:** 4 bảng hệ thống cốt lõi (`wp_skaaa_data_sys_organisms`, `sys_theme_templates`, `sys_presets`, `sys_apps`, `sys_settings`) được khởi tạo và đồng bộ bằng `dbDelta()` nguyên tử (v1.3.3) kết hợp cơ chế self-healing column checks. Bảo vệ cấu trúc bảng bất biến qua `Database_Engine::is_table_protected()`.
- **DataGrid Strategy Pattern (ES6 + Vite):** Phân chia kiến trúc modular với `CellRegistry` và các lớp Strategy kế thừa `BaseCell`: `BooleanCell` (1-click toggle), `MediaCell`, `GalleryCell`, `SelectCell`, `TextCell`. Cập nhật dữ liệu inline qua AJAX.
- **Smart Object Blueprint (JSON Portable):** Đóng gói schema và metadata bảng thành file JSON di động. Cơ chế **Dynamic Slug Resolution** tự động xử lý đụng độ tên bảng (Table Collision) khi Import sang server khác và tái kết nối (re-wire) các quan hệ bảng tự động.
- **Rollup Virtualization & Heuristic Meta Filter:** Truy xuất dữ liệu Rollup ảo O(1) ở tầng PHP RAM, không nhân bản dữ liệu trên MySQL (`NULL`), kết hợp bộ lọc Heuristic loại bỏ rác metadata ngầm của WordPress (`_wp_%`, `session_%`).

### 3. Phase 3: Bộ Não Luồng Sự Kiện & Pluggable Nodes (Skaaa Logic Engine)
- **DAG Workflow Canvas:** Đồ thị trực quan kéo thả dựa trên React Flow v11, hỗ trợ chuyển đổi linh hoạt giữa chế độ đồ thị (Graph View) và chế độ JSON (JSON View) an toàn cú pháp.
- **Pluggable Nodes Framework:** Cơ chế mở rộng cắm rút phi tập trung. Class `Skaaa_Node_Registry` và filter `skaaa_logic_registered_nodes` cho phép các plugin addon (như `skaaai`) đăng ký Node mới kèm `settings_schema` (JSON Schema). Frontend tự động vẽ Settings Panel mà không cần nạp React/Webpack của bên thứ ba.
- **SkaaaFX DSL & Context-Aware Autocomplete:** Ngôn ngữ biểu thức nội suy AST chuyên dụng. Hỗ trợ dropdown gợi ý biến nổi (Floating Autocomplete) theo ngữ cảnh khi gõ `[`, `{` hoặc tên hàm; tự động nhận diện biến mock payload và biến vòng lặp `[$item]`, `[$index]`.
- **Pure Render Template Node:** Bộ nội suy 2 bước tuần tự (Two-Pass Interpolation): giải quyết biến HTML động trước, sau đó nội suy dữ liệu cá nhân hóa bên trong. Tích hợp `do_blocks()` biên dịch block markup Gutenberg thành HTML thật.
- **Asynchronous Worker:** Tích hợp Action Scheduler để thực thi các tác vụ nền nặng và nhận diện sự kiện qua Webhooks.

### 4. Phase 4: Tái Cấu Trúc Thương Hiệu & Quy Hoạch Monolith (Milestone 1 Completion)
- **Thương Hiệu Thống Nhất SKAAA:** Đổi tên toàn bộ codebase từ `Ska` sang `SKAAA` (System Design, Key Database, Action, AI, Automation), refactor hơn 270 file nguồn, di cư 13 bảng phẳng MySQL và cập nhật 873 bài viết mẫu.
- **Chiến Lược Native SSR Monolith:** Từ bỏ định hướng headless React/Next.js bên ngoài. Toàn bộ hệ sinh thái chạy trực tiếp trên WordPress core dưới dạng SSR kết hợp Alpine.js và Tailwind JIT offline, đạt tốc độ tải 0ms và chi phí vận hành tối thiểu.
- **Phân Rã Bridge Cũ & Kiến Trúc Decoupled Microservices:** Khai tử plugin `skaaa-bridge`. Phân rã tính năng về đúng nơi tự nhiên: `html2tailwind` về Design, `Integration REST APIs` về Data Pro, `Webhooks` về Logic Engine. Toàn bộ 4 plugins giao tiếp độc quyền qua WordPress Action/Filter hooks, tuyệt đối không gọi class chéo.
- **Định Vị Plugin Thứ 4 - Skaaai (AI Copilot & Sync Bridge):** Quy hoạch plugin độc lập phụ trách: Self-Documenting Context Engine (`ai-manifest.json`), Bidirectional Content Sync (Localhost ⟷ Webhost qua `skaaa_uuid`), và AI Logic Nodes.
- **Chuẩn Hóa Quản Lý Phiên Bản & Tài Liệu:** Bắt buộc tuân thủ SemVer (tự động tăng version header khi sửa code) và Thiết quân luật Không rác (Zero-Trash Policy) với 4 ngăn kéo tài liệu.

---

## NHẬT KÝ QUYẾT ĐỊNH MỚI NHẤT (ACTIVE LOGS - THÁNG 09/2026)

## 2026-09-23 - 🟢 Hoàn thành: Khởi tạo & Nâng cấp Plugin Skaaai v1.0.3 (1-Click Sync, Persistent Storage & Live Deletion Protection)
- **Decision (Skaaai 1-Click Sync & Remote Code Deployer via Persistent Storage WP_Filesystem):**
  - **Mục tiêu:** Xây dựng plugin `Skaaai` độc lập đóng vai trò là Cầu nối đồng bộ 1-Click giữa máy tính cá nhân (Localhost Dev) và Hosting trực tuyến (Live Webhost). Mọi tác vụ nặng (thiết kế, code, AI) thực hiện trên PC; Webhost là trang WordPress bình thường nhận nội dung hiển thị y chang 100%.
  - **Quyết định Kiến trúc:**
    1. **Khung sườn Plugin & Cấu hình Phẳng (No-Postmeta):** Tạo plugin `wp-content/plugins/skaaai/` (SemVer 1.0.3, PHP 8.2+). Lưu trữ toàn bộ cấu hình vào bảng phẳng MySQL `wp_skaaa_data_sys_settings` (`skaaai_get_setting` / `skaaai_set_setting`).
    2. **Cơ chế Ghép đôi (Pairing Protocol):** Receiver sinh chuỗi `skaaai_pair://...` (URL + Token 64-char ngẫu nhiên) hỗ trợ 1-click Copy & Paste. Bắt tay Handshake an toàn qua REST API `GET /wp-json/skaaai/v1/handshake` với `hash_equals()`.
    3. **Lưu Trữ Bền Vững Ngoài Plugin (Persistent Storage `wp-content/skaaa-custom-nodes/`):**
       - **Vấn đề:** Khi cập nhật plugin bằng file `.zip` (hoặc giải nén ghi đè), WordPress mặc định xóa sạch thư mục plugin cũ `wp-content/plugins/skaaai/`, làm mất toàn bộ file custom nodes nếu lưu bên trong plugin.
       - **Giải pháp:** Di dời thư mục lưu trữ custom nodes ra vị trí bền vững `wp-content/skaaa-custom-nodes/` (ngang hàng với `wp-content/uploads/`). Khi cập nhật plugin lên bất kỳ phiên bản nào, WordPress chỉ ghi đè thư mục plugin, tuyệt đối không động chạm đến `wp-content/skaaa-custom-nodes/`.
       - **Tự động Khởi tạo & Di cư (Auto-Migration):** Hàm `File_Deployer::ensure_target_dir()` tự động sinh thư mục kèm `index.php` bảo vệ, đồng thời tự động quét và copy toàn bộ file từ thư mục cũ `SKAAAI_DIR . 'custom-nodes/'` nếu phát hiện có file tồn tại.
       - **Hiệu năng Tối đa (Zero-Latency OPcache):** Tiếp tục thực thi mã nguồn bằng file vật lý nạp qua `require_once` để tận dụng PHP OPcache (0ms), tuyệt đối không lưu và chạy code qua `eval()` từ Database vì sẽ làm chậm và mất bảo mật.
    4. **Lá chắn Triển khai Code, Bảo vệ Live & Đồng bộ 2 Chiều (Code Deployer & Live SSoT Protection):**
       - Khi bấm Deploy từ máy Dev: hệ thống tự động lưu 1 bản sao vào `wp-content/skaaa-custom-nodes/` của Localhost Dev trước (`File_Deployer::save_local_file()`), sau đó gửi qua REST API `POST /wp-json/skaaai/v1/deploy-file` lên Live Webhost.
       - Tích hợp **Syntax Validator Shield** chạy Tokenizer (`token_get_all`) và Linter trước khi ghi, chặn 100% nguy cơ lỗi cú pháp làm sập web.
       - Ghi file qua `WP_Filesystem` chuẩn WordPress vào thư mục cách ly `wp-content/skaaa-custom-nodes/`.
       - Tự động tạo bản sao lưu `.bak` trước khi ghi đè, hiển thị huy hiệu `📦 .bak` trên giao diện bảng danh sách.
       - **Live Deletion Protection & Local SSoT:** Khóa quyền xóa file trực tiếp trên Live Webhost (`role === 'receiver'`). Nút Delete trên Live được ẩn và thay bằng huy hiệu `🔒 Live Protected`. Thao tác xóa bắt buộc xuất phát từ Localhost (Sender), khi xóa trên Local sẽ tự động kích hoạt REST API `POST /delete-file` dọn sạch file trên Live Webhost, bảo vệ triệt để tính toàn vẹn của mã nguồn.
       - Tự động nạp (autoload) và đăng ký Node mới vào hook `skaaa_logic_registered_nodes` của Logic Engine.
       - Giao diện Admin: Bổ sung nút **Edit** (nạp ngược file vào editor để sửa) và **Push** (đẩy 1-click lên hosting) cho các custom node có sẵn, bảng danh sách tự cập nhật thời gian thực bằng `wp.template`. Tự động lưu thiết lập `allow_code_deploy` khi chuyển trạng thái checkbox.
    5. **Động cơ Đồng bộ Bài viết (Post Sync Engine):** Định danh bài viết bằng `_skaaa_uuid`, tự động hoán đổi URL domain cục bộ sang live domain, tự động tải ảnh (Sideload Media) về Media Library của hosting và tạo điểm khôi phục `wp_save_post_revision()`.
    6. **Tự động Đóng gói Phân phối:** Bổ sung `skaaai` vào `zip-all.js` tạo `skaaai-v1.0.3.zip` tự động.

## 2026-09-22 - 🟢 Hoàn thành: Hệ thống hóa Tài liệu Hệ sinh thái & Định nghĩa Kiến trúc Skaaai (AI & Sync Bridge)
- **Decision (Ecosystem Documentation Systemization & Zero-Trash Compliance):**
  - **Mục tiêu:** Dọn sạch tài liệu, quy chuẩn lại cấu trúc 4 ngăn kéo theo thiết quân luật `skaaa-docs-management.md`, chuẩn bị nền tảng rõ ràng cho phiên kế tiếp.
  - **Hành động:**
    1. Lưu trữ 9 file PM và E2E đã hoàn thành từ Phase trước vào `.skaaa-ai/1-overview/project-managers/archive/`.
    2. Tinh gọn `system_map.md` từ 32KB xuống 9KB (72 dòng), cập nhật trạng thái Milestone 2.
    3. Di chuyển bản thảo cũ `1-overview/architecture.md` vào `.skaaa-ai/2-memory/archive/architecture-data-pro-phase1-draft.md` (giữ `3-ecosystem/skaaa-data-pro/architecture.md` là nguồn chân lý duy nhất 15KB).
    4. Di chuyển `1-overview/release-workflow.md` vào `.skaaa-ai/2-memory/archive/release-workflow.md` (sử dụng workflow agent chuẩn `.agent/workflows/release-github.md`).
    5. Cập nhật `Skaaa-no-code-overview.md` thành Master Plan Tầm nhìn dự án (không lưu checkpoint ngắn hạn), đồng bộ 100% tầm nhìn với `system_map.md`.
    6. Hoàn thiện tài liệu kiến trúc cục bộ `3-ecosystem/skaaai/architecture.md` bao quát 3 trụ cột: Self-Documenting Context Engine, Bidirectional Content Sync (Local ⟷ Host) và AI Logic Nodes.

## 2026-09-22 - 🟢 Hoàn thành: Bù khoảng cách Admin Bar cho Header Cố định (Skaaa Canvas Theme v1.0.1)
- **Decision (Admin Bar Offset for Fixed/Sticky Headers in Blank Theme):**
  - **Vấn đề:** Khi quản trị viên đăng nhập, thanh WordPress Admin Bar (`#wpadminbar`, cao 32px desktop / 46px mobile) xuất hiện ở `position: fixed; top: 0; z-index: 99999` che khuất phần đầu của các khối Header có class `fixed top-0` / `sticky top-0`.
  - **Quyết định:**
    1. Bổ sung hàm `skaaa_canvas_admin_bar_fix()` vào hook `wp_head` của theme `Skaaa Canvas` với điều kiện kiểm tra `is_admin_bar_showing()`.
    2. Sử dụng selector ưu tiên tự nhiên `html body.admin-bar.skaaaaa-builder header.fixed, html body.admin-bar.skaaaaa-builder .fixed.top-0, html body.admin-bar.skaaaaa-builder .sticky.top-0` để gán `top: 32px` (desktop) và `top: 46px` (mobile `<= 782px`), hoàn toàn không sử dụng `!important`.
    3. Đối với khách truy cập chưa đăng nhập (`is_admin_bar_showing() === false`), đoạn style không được in ra HTML, đảm bảo zero-overhead và giữ nguyên thiết kế Clean Slate. Nâng phiên bản theme `Skaaa Canvas` lên `v1.0.1`.

## 2026-09-15 - 🟢 Hoàn thành: Bổ sung Gradient Middle Stop via-[#...] & Chuẩn hóa Gradient Stops Parity (v2.4.4)
- **Decision (Tailwind Gradient Stops Arbitrary Resolution: via-[#...], from-[#...], to-[#...]):**
  - **Vấn đề:** Khi người dùng sử dụng điểm dừng màu trung gian `via-[#171c26]` trong dải màu Gradient, class bị báo đỏ (unresolved) do regex arbitrary hex `^(text|bg|border|ring|from|to)-\[#...\]` trước đây chỉ chứa `from` và `to`, thiếu `via`. Đồng thời `from` và `via` chưa xuất chuỗi biến CSS `--tw-gradient-stops` chuẩn xác.
  - **Quyết định:**
    1. Bổ sung `via` vào regex `^(text|bg|border|ring|from|via|to)-\[#...\]` và `resolveCustomColor` trên cả `class-tailwind-compiler.php`, `class-tailwind-color-registry.php` (PHP) và `skaaawind.js` (JS).
    2. Chuẩn hóa đầu ra CSS cho bộ ba gradient stops: `from` (`--tw-gradient-from`, `--tw-gradient-stops`), `via` (`--tw-gradient-stops`), và `to` (`--tw-gradient-to`), hỗ trợ cả nấc opacity `/N`.
    3. Biên dịch và đồng bộ Webpack sang `build/`, kiểm thử 100% Compiler Parity (0 unresolved classes). Nâng phiên bản `Skaaa No-Code Design` lên `v2.4.4`.

## 2026-09-15 - 🟢 Hoàn thành: Bổ sung Border Width Directional & Arbitrary JIT Compiler Parity (v2.4.3)
- **Decision (Tailwind Border Width Directional & Arbitrary Resolution: border-x, border-y, border-s, border-e, border-[...]):**
  - **Vấn đề:** Khi người dùng nhập class `border-y` (hoặc `border-x`), Inspector hiển thị viền đỏ và tooltip cảnh báo "This class is not supported offline. Please report it to support." do regex `^border-([trbl])` trước đây chỉ xử lý 4 cạnh đơn lẻ (`top`, `right`, `bottom`, `left`), thiếu 2 trục tọa độ `x` (ngang) và `y` (dọc), cùng 2 cạnh logic `s` (start), `e` (end), và cú pháp arbitrary `border-[...]`.
  - **Quyết định:**
    1. Mở rộng regex nhận diện `^border-([trblxyse])(?:-([0-9]+))?$` trên cả `class-tailwind-compiler.php` (PHP) và `skaaawind.js` (JS). Tự động phân giải `x` thành `border-left-width` + `border-right-width`, `y` thành `border-top-width` + `border-bottom-width`, `s` thành `border-inline-start-width`, `e` thành `border-inline-end-width`.
    2. Bổ sung regex hỗ trợ arbitrary `border-[...]` (cho độ dày tùy biến) và `border-([trblxyse])-[...]` (cho độ dày theo hướng tùy biến) với cơ chế tự động phân biệt giá trị màu sắc (`#`, `rgb`, `hsl`).
    3. Cập nhật `tailwind-dictionary.js` bổ sung `border-x`, `border-y` vào nhóm *Borders & Radius*.
    4. Biên dịch và đồng bộ Webpack sang `build/`, kiểm thử đạt 100% Compiler Parity (0 unresolved classes). Nâng phiên bản `Skaaa No-Code Design` lên `v2.4.3`.

## 2026-09-14 - 🟢 Hoàn thành: Bổ sung Cấu hình Monospace Font & Hỗ trợ FontFamily JIT Compiler (v2.4.2)
- **Decision (Monospace Font Settings UI & Tokens Default):**
  - **Vấn đề:** Giao diện Theme Options (Design Tokens - tab Typography) chỉ có trường Primary Font và Secondary Font, thiếu ô nhập Monospace Font cho các thành phần code block, thẻ tag, badges.
  - **Quyết định:** Bổ sung Card nhập liệu *Monospace Font (Code)* trên `design-tokens-app.php` liên kết với `formData.typography.mono`. Bổ sung giá trị mặc định `'mono' => 'IBM Plex Mono, monospace'` vào `Tailwind_Color_Registry::get_typography_config()`.
- **Decision (Global --font-mono Variable & Code Preflight Reset):**
  - Khai báo `--font-mono: {$mono_font};` trong `:root` qua `Tailwind_Config::get_core_reset_css()`. Bổ sung reset font mặc định cho các thẻ `code, kbd, samp, pre` trong cả Frontend và Gutenberg Canvas Editor.
- **Decision (Tailwind FontFamily JIT Resolution):**
  - Bổ sung nhóm utility classes `fontFamily` (`font-mono`, `font-sans`, `font-serif`) vào `tailwind-rules.json`, `class-tailwind-compiler.php` (PHP) và `skaaawind.js` (JS Editor) đảm bảo 100% Compiler Parity. Cập nhật `tailwind-dictionary.js` phục vụ auto-suggestion.
- **Decision (Font Smoothing Utilities Parity: antialiased & subpixel-antialiased):**
  - **Vấn đề:** Các class chuẩn của Tailwind về làm mịn font chữ (`antialiased` và `subpixel-antialiased`) bị đánh dấu unresolved màu đỏ trên Gutenberg Inspector do chưa nằm trong `textMiscMap`.
  - **Quyết định:** Bổ sung định nghĩa `antialiased` (`-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;`) và `subpixel-antialiased` (`-webkit-font-smoothing: auto; -moz-osx-font-smoothing: auto;`) vào `tailwind-rules.json`, `skaaawind.js` và `tailwind-dictionary.js`.
- **Decision (Arbitrary Box Shadow Utilities Parity: shadow-[...]):**
  - **Vấn đề:** Các class đổ bóng tùy biến tự do (arbitrary value) theo cú pháp chuẩn của Tailwind (ví dụ `shadow-[0_4px_20px_-2px_rgba(0,0,0,0.5)]`) bị đánh dấu unresolved màu đỏ do compiler chỉ hỗ trợ các bóng dựng sẵn và bóng màu.
  - **Quyết định:** Bổ sung regex nhận diện `shadow-[...]` vào cả `class-tailwind-compiler.php` và `skaaawind.js`, tự động chuyển đổi dấu `_` thành khoảng trắng và xuất ra `box-shadow: <value>;` đạt 100% Compiler Parity.
- **Decision (Arbitrary Font Size Utilities Parity: text-[...]):**
  - **Vấn đề:** Các class kích thước chữ tùy biến tự do (ví dụ `text-[10px]`, `text-[12px]/[16px]`, `text-[0.875rem]`) bị đánh dấu unresolved màu đỏ do Typography Maps chỉ hỗ trợ các nấc font tĩnh chuẩn (`xs`, `sm`, `base`, `lg`...).
  - **Quyết định:** Bổ sung regex nhận diện `text-[...]` (và tuỳ chọn line-height dạng `text-[...]/[...]`) vào cả `class-tailwind-compiler.php` và `skaaawind.js`, tự động phân biệt giá trị màu sắc (`rgb/rgba/hsl/#`) và kích thước font chữ để xuất ra `font-size: <size>;` hoặc `color: <color>;` chuẩn xác, đạt 100% Compiler Parity.
- **Decision (Tailwind v4 Flexbox Shrink & Grow Utilities Parity: shrink-0, shrink, grow, grow-0):**
  - **Vấn đề:** Class chuẩn Tailwind v4 `shrink-0` bị đánh dấu unresolved màu đỏ trên Gutenberg Inspector do bảng `flexExtra` trước đây chỉ ánh xạ cú pháp legacy `flex-shrink-0` của Tailwind v2.
  - **Quyết định:** Bổ sung các class chuẩn Tailwind v4 `shrink` (`flex-shrink: 1;`), `shrink-0` (`flex-shrink: 0;`), `grow` (`flex-grow: 1;`), `grow-0` (`flex-grow: 0;`) cùng regex hỗ trợ arbitrary `^(shrink|grow)-\[(.+)\]$` vào `tailwind-rules.json`, `skaaawind.js` và `class-tailwind-compiler.php`. Đồng thời cập nhật `tailwind-dictionary.js` đưa vào nhóm Layout & Display để hiển thị trên panel Skaaa Layout.
- **Decision (Flexbox Reverse Direction & Wrap Utilities Parity: flex-col-reverse, flex-row-reverse, flex-wrap-reverse, flex-nowrap):**
  - **Vấn đề:** Class đảo chiều flexbox `flex-col-reverse` bị đánh dấu unresolved màu đỏ do `layoutMap` trước đây mới chỉ đăng ký 2 hướng xuôi (`flex-col`, `flex-row`).
  - **Quyết định:** Bổ sung `flex-col-reverse` (`flex-direction: column-reverse;`), `flex-row-reverse` (`flex-direction: row-reverse;`), `flex-wrap-reverse` (`flex-wrap: wrap-reverse;`) và `flex-nowrap` (`flex-wrap: nowrap;`) vào `layoutMap` của `tailwind-rules.json`, `skaaawind.js` và `class-tailwind-compiler.php`. Cập nhật `tailwind-dictionary.js`. Giữ nguyên không can thiệp token màu nền (`bg-canvas`) chờ định hướng tiếp theo từ người dùng. Nâng phiên bản `Skaaa No-Code Design` lên `v2.4.2`.

## 2026-09-14 - 🟢 Hoàn thành: Khắc phục Lỗi Biên Dịch Media Query Tailwind JIT, Alpine Scope & Chuyển đổi Stitch HTML (v2.4.1)
- **Decision (Tailwind Compiler Media Query Auto-Initialization - Skaaa No-Code Design v2.4.1):**
  - **Vấn đề:** Khi render trang ngoài frontend, các tiền tố Responsive (`md:flex`, `sm:inline-flex`, `lg:grid-cols-3`...) bị bỏ qua, dẫn đến menu desktop và badge trạng thái bị biến mất.
  - **Nguyên nhân:** Biến tĩnh `Tailwind_Config::$media_queries` không được tự động khởi tạo trên frontend nếu `Tailwind_Config::init()` chưa được gọi trước `compile_classes()`.
  - **Quyết định:** Bổ sung gọi `Tailwind_Config::init()` ngay trong constructor của `Tailwind_Compiler` và kiểm tra fallback trong `compile_classes()`.
- **Decision (Alpine.js Scope Isolation & `:` Prefix Recognition):**
  - **Vấn đề:** Các thuộc tính viết tắt của Alpine như `:class`, `:key` không được coi là thuộc tính Alpine, đồng thời `blocks/init.php` tự ý tiêm `x-data=""` vào mọi block con có thuộc tính Alpine làm gãy phạm vi dữ liệu (scope shadowing) của component cha.
  - **Quyết định:** Bổ sung tiền tố `:` vào bộ lọc Alpine; loại bỏ việc tự động tiêm `x-data=""` vào các block con để component con kế thừa trọn vẹn context dữ liệu cha (`portfolioApp()`).
- **Decision (html-to-blocks Script & Body Attributes Preservation):**
  - **Vấn đề:** Công cụ chuyển đổi `html2tailwind` (`html-to-blocks.js`) khi import code từ Stitch loại bỏ sạch thẻ `<script>` và bỏ quên `x-data` trên thẻ `<body>`, làm mất hoàn toàn hàm logic JavaScript client-side.
  - **Quyết định:** Nâng cấp `html-to-blocks.js` trích xuất `x-data` từ `<body>` vào thuộc tính của Container gốc; đồng thời trích xuất các thẻ script inline tạo thành block `skaaaaa-builder/code` đính kèm theo trang.
- **Decision (Semantic Button & Image Attribute Forwarding):**
  - Khối `skaaa-button` tự động render thẻ `<button type="button">` khi `url` là `#` hoặc `tagName` là `button` để tránh nhảy trang. Khối `skaaa-image` chuyển tiếp các thuộc tính `onerror`, `onload`, `loading` vào thẻ `<img>` thay vì bọc ngoài wrapper. Nâng phiên bản `Skaaa No-Code Design` lên `v2.4.1`.

## 2026-09-09 - 🟢 Hoàn thành: Khối Skaaa SVG (Flat DOM), Khắc phục Font Icon Editor Canvas & Zero !important Directive (v2.4.0)
- **Decision (Native Skaaa SVG Block - Skaaa No-Code Design v2.4.0):**
  - **Vấn đề:** Khi convert mã HTML bằng công cụ `html-to-blocks` (`html2tailwind`), các thẻ vector `<svg>` bị nuốt hoặc bị biến dạng thành thẻ code thô / text thô trong Gutenberg Editor, không cho phép chỉnh sửa kích thước hoặc kết hợp linh hoạt cùng biểu tượng Google Material Symbols.
  - **Quyết định:** Tạo khối Native Block `skaaaaa-builder/svg` (`src/skaaa-svg`) chuẩn Flat DOM (không bọc `<div>` dư thừa quanh thẻ `<svg>`). Cho phép paste/edit trực tiếp chuỗi XML `<svg>`, nhúng đầy đủ Inspector Controls tùy biến class Tailwind, và tích hợp sâu vào `ALLOWED_BLOCKS` của Container / List Item và luồng dịch thuật tự động của `html-to-blocks.js`.
- **Decision (Editor Canvas Google Material Symbols Font Rendering Fix):**
  - **Vấn đề:** Trong Gutenberg Editor, khối `Skaaa Icon` hiển thị thành chữ thô (như `verified`, `rocket_launch`) thay vì hình vẽ, mặc dù ở ngoài Frontend và modal chọn icon ở sidebar vẫn hiển thị bình thường.
  - **Nguyên nhân:** Gutenberg Editor Canvas chạy trong một `<iframe>` cô lập. Thẻ `<link>` nạp Google Font ở trang cha không lọt vào Iframe. Đồng thời, trong `skaaa-editor-helper.js`, khai báo `@import url(...)` bị đặt sau chuỗi `brandColorsCss` (vi phạm thứ tự W3C CSS spec khiến trình duyệt bỏ qua toàn bộ `@import`).
  - **Quyết định:** Khắc phục triệt để bằng 3 tầng phòng thủ:
    1. Đăng ký `add_editor_style('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined...')` trong `inc/design-engine/class-core.php` qua hook `admin_init`.
    2. Đưa khai báo `@import` font lên dòng đầu tiên tuyệt đối của `unifiedCss` trong `assets/js/skaaa-editor-helper.js`.
    3. Thêm hàm `ensureFontLink(doc)` tự động chèn trực tiếp thẻ `<link id="skaaa-material-symbols-font">` vào `<head>` của từng Iframe Canvas và trang cha.
- **Decision (Zero !important Directive Enforcement in Editor Helper):**
  - **Vấn đề:** `assets/js/skaaa-editor-helper.js` còn sót các cờ `!important` trong override layout và giao diện WP Admin.
  - **Quyết định:** Loại bỏ 100% cờ `!important`, thay thế hoàn toàn bằng cơ chế tăng độ ưu tiên bộ chọn tự nhiên (CSS Specificity Scope: `.editor-styles-wrapper.editor-styles-wrapper` và `body.wp-admin.wp-admin`). Tuân thủ tuyệt đối quy tắc Clean Slate của hệ sinh thái Skaaa. Nâng phiên bản `Skaaa No-Code Design` lên `v2.4.0`.

## 2026-09-08 - 🟢 Hoàn thành: Loại bỏ hoàn toàn Demo Content Generator & Đóng gói Theme Skaaa Canvas (v2.3.3)
- **Decision (Zero Demo Polluting - Skaaa No-Code Design v2.3.3):**
  - **Vấn đề:** Khi cài đặt plugin lên website mới, hàm `admin_init` tự ý tạo trang `Skaaa Logic Demo` và 2 bài post mẫu `Related A`, `Related B` qua `demo-content.php`, vi phạm nguyên tắc Clean Slate và gây rác cơ sở dữ liệu của người dùng.
  - **Quyết định:** Xóa bỏ hoàn toàn tệp `inc/demo-content.php` và gỡ bỏ lệnh nạp tệp này khỏi `skaaa-no-code-design.php`. Đảm bảo hệ sinh thái khi kích hoạt đạt chuẩn Blank Slate 100%. Nâng phiên bản `Skaaa No-Code Design` lên `v2.3.3`.
  - **Đóng gói hệ sinh thái:** Cập nhật script `zip-all.js` hỗ trợ đóng gói tự động cả 3 plugins và Theme `Skaaa Canvas` thành các file ZIP độc lập.

---

> **Lưu trữ Lịch sử:** Toàn bộ nhật ký chi tiết các quyết định kiến trúc từ Phase 1, 2, 3 và 4 của Milestone 1 (từ `2026-05-24` đến `2026-08-21`) đã được nén và lưu trữ an toàn tại:  
> [decision-log-milestone-1.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/.skaaa-ai/2-memory/archive/decision-log-milestone-1.md)
