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

## NHẬT KÝ QUYẾT ĐỊNH MỚI NHẤT (ACTIVE LOGS - THÁNG 10/2026)

## 2026-10-05 - 🟢 Hoàn thành: Hiện Đại Hóa Skaaa System Dashboard (Skaaai v1.5.3 & Skaaa No-Code Design v2.4.8)
- **Decision (Dashboard Decoupled Integration & Legacy Cleanup):**
  - **Bối cảnh:** Trang Skaaa System Dashboard còn tồn tại thẻ HTML tĩnh `Skaaa Bridge (In development) [Frozen]` từ thời sơ khởi và thẻ `Skaaa AI Architect` cũ có nút bấm alert thô sơ.
  - **Giải pháp:**
    1. **Skaaa No-Code Design v2.4.8:** Loại bỏ hoàn toàn khối HTML tĩnh `<!-- Module: Bridge -->` trong `class-framework-ui.php`. Đưa `skaaai` vào danh sách `$ecosystem_modules` fallback khi chưa kích hoạt. Nâng cấp thẻ `Skaaa AI Architect` trong `class-ai-proxy.php` thành `Skaaai AI Copilot & Automation` liên kết trực tiếp sang `admin.php?page=skaaai-settings#tab-general`.
    2. **Skaaai v1.5.3:** Đăng ký phương thức `Ecosystem_Sync_UI::render_dashboard_card()` hook vào `skaaa_system_dashboard_modules` hiển thị card `Skaaai (Bridge & Sync)` sáng đèn với đầy đủ vai trò, nút mở trang cấu hình và đồng bộ.
  - **Đóng gói phát hành:** Đóng gói `skaaai-v1.5.3.zip`, `skaaa-no-code-design-v2.4.8.zip`, cập nhật `README.md` v2.4.7 và đồng bộ 100% sang paired site `lytatthanhloca`.

## 2026-10-04 - 🟢 Hoàn thành: Cơ Chế Đồng Bộ Gương 100% Hệ Sinh Thái (True Mirror Synchronization) Cho CSDL, Logic & Nội Dung (Skaaai v1.5.2)
- **Decision (True Mirror Ecosystem Replication: Row-level DB Cleanup, Workflow/Organism Pruning & Complete Draft/Publish Deletion Trashing):**
  - **Bối cảnh & Yêu cầu:** Người dùng yêu cầu cơ chế đồng bộ không chỉ dừng lại ở việc thêm/sửa một chiều (Merge), mà phải là **Đồng Bộ Gương 100% (True Mirror Synchronization)** cho toàn bộ hệ sinh thái (Logic Engine Workflows, Skaaa Data Pro Flat Tables, Organisms, Theme Templates và Nội dung). Khi Live xóa trang/bài/dòng dữ liệu thì Localhost cũng phải được dọn dẹp sạch sẽ tương ứng, không để lại rác hay bản ghi mồ côi.
  - **Giải pháp kiến trúc 4 điểm:**
    1. **Bảng phẳng CSDL Smart Object (`skaaa_data_*`):** Nâng cấp `apply_custom_tables()` kiểm tra danh sách IDs gửi sang. Tự động xóa các bản ghi thừa không còn trên nguồn (`DELETE FROM ... WHERE id NOT IN (...)` hoặc `TRUNCATE TABLE` nếu nguồn rỗng), đảm bảo số dòng của bảng phẳng ở 2 môi trường luôn đồng nhất tuyệt đối.
    2. **Logic Engine Workflows & Organisms:** Nâng cấp `apply_workflows()` và `apply_organisms()` tự động dọn dẹp các workflow (`sys_workflows`) và component (`sys_organisms`) thừa không còn tồn tại trên nguồn.
    3. **Quét Xóa Đa Trạng Thái (`publish` & `draft`):** Mở rộng bộ quét đối chiếu ngược trong `Sync_Ecosystem_Diff::analyze_content_items()` để kiểm tra toàn bộ các bài viết/trang ở trạng thái `publish`, `draft`, `pending`, `private`. Tự động nhận diện chính xác tất cả các trang bị xóa trên Live (ví dụ: phát hiện 8 trang Draft và 1 post bị xóa).
    4. **Dọn dẹp chuyển Thùng rác An Toàn (`wp_trash_post`):** Khi duyệt Approve & Pull, toàn bộ các trang/bài không còn trên nguồn sẽ tự động được chuyển vào Thùng rác (Trash) trên môi trường đích thay vì xóa vĩnh viễn, vừa đảm bảo môi trường đích sạch bóng như nguồn, vừa bảo vệ dữ liệu chống thao tác nhầm.
  - **Đóng gói phát hành:** Đóng gói `skaaai-v1.5.2.zip` (0.16 MB) và đồng bộ 100% sang paired live site `lytatthanhloca`.

## 2026-10-04 - 🟢 Hoàn thành: Động Cơ Đối Soát Sâu Đa Chiều (Modified, New, Deleted on Live) & Mở Rộng Blog Posts (Skaaai v1.5.1)
- **Decision (Deep Pre-flight Diff Engine: Multidimensional State Classification, Reverse Check for Deleted Remote Items & Blog Posts Integration):**
  - **Bối cảnh & Triệu chứng:** Khi người dùng mở modal `1-Click Full Ecosystem Pull from Live`, thẻ `Pages & Media` chỉ hiển thị con số `6 - 6 pages with media from Live` mà không liệt kê danh sách tên bài cụ thể. Đồng thời, khi sửa đổi nội dung bài viết hoặc xóa một bài trên Live, hệ thống không nhận diện được do chỉ kiểm tra `tồn tại -> update` mà không so sánh ngày sửa đổi hay mã băm nội dung, bỏ sót hoàn toàn chiều đối chiếu ngược cho các bài bị xóa trên Live và chưa quét bài viết Blog (`post_type => 'post'`).
  - **Giải pháp kiến trúc 4 điểm:**
    1. **Thuật toán Đối Soát Đa Chiều (`Sync_Ecosystem_Diff::analyze_content_items`):** So sánh `content_hash` (md5 `title + content`) và `post_modified_gmt` giữa Live và Localhost. Phân loại chuẩn 4 trạng thái:
       - `🟡 Modified:` Nội dung Live khác Localhost, ghi nhận độ chênh lệch thời gian (`Remote is newer (+X time)`).
       - `🔵 New:` Bài/trang mới trên Live chưa có tại Localhost (sẽ được tạo mới).
       - `🗑️ Deleted on Live:` Reverse check quét toàn bộ bài publish trên Localhost mà Live không còn gửi về (cảnh báo bài đã bị xóa trên Live).
       - `⚪ Synced:` Hai bên đồng nhất 100%.
    2. **Mở Rộng Blog Posts (`post_type => 'post'`):** Cập nhật `Sync_Ecosystem_Sender::export_published_posts()` và `Sync_Ecosystem::apply_pages()` hỗ trợ `post_type` động, tự động xuất và nhập cả Posts lẫn Pages trong Full Ecosystem Sync.
    3. **Giao Diện Modal Diff Trực Quan (Interactive Accordion List):** Thẻ `Pages & Blog Posts` chiếm toàn bộ 2 cột (`skaaai-diff-card-wide`), tích hợp thanh huy hiệu thống kê trạng thái (`🟡 X Modified`, `🔵 Y New`, `🗑️ Z Deleted on Live`, `⚪ W Synced`) và danh sách tương tác cuộn (`max-height: 220px`) hiển thị tên bài, slug, badge loại (`Page`/`Post`), ngày giờ sửa Live vs Local và badge trạng thái màu sắc nổi bật.
    4. **Reverse Transformers:** Áp dụng đầy đủ cho cả Posts và Pages (sideload ảnh về `uploads/` local, hoán đổi domain và rewrite database table prefix).
  - **Đóng gói phát hành:** Đóng gói `skaaai-v1.5.1.zip` (0.16 MB) và đồng bộ sang paired site `lytatthanhloca`.

## 2026-10-04 - 🟢 Hoàn thành: Động Cơ Kéo Dữ Liệu An Toàn 2 Chiều & Cổng Đối Soát 2 Tầng (Skaaai v1.5.0)
- **Decision (Bidirectional Full Ecosystem Sync: Safe Pull from Live Engine, Reverse Transformers & Two-Tier Pre-flight Diff Gate):**
  - **Bối cảnh & Yêu cầu:** Trước đây Skaaai mới chỉ có chiều Push từ Localhost sang Live Webhost. Khi nội dung trên Live hoặc các bảng CSDL Smart Objects/Workflows được cập nhật trên Live, hệ thống thiếu cơ chế kéo toàn diện về Localhost và người dùng không có bảng đối soát (Diff) để xem trước thay đổi trước khi phê duyệt ghi đè.
  - **Giải pháp kiến trúc toàn diện 5 lớp:**
    1. **Receiver Export Service & Endpoints:** Xây dựng lớp độc lập `Skaaai\Export_Service` trong `class-skaaai-export-service.php` (< 700 lines) cung cấp 3 endpoint REST API bảo mật qua token: `GET /export-post` (xuất dữ liệu bài viết kèm UUID, block attributes, metadata), `POST /check-posts-status` (đối soát chênh lệch thời gian `remote_modified` vs `local_modified`) và `GET /export-ecosystem` (xuất trọn vẹn 7 scopes: presets, organisms, theme_templates, workflows, custom_tables, pages, settings). Bổ sung `table_prefix` vào gói payload để bảo đảm chuẩn hóa prefix CSDL ngược.
    2. **Sender Safe Pull Engine (Revision-First):** Xây dựng lớp chuyên trách `Skaaai\Sync_Pull` trong `class-skaaai-sync-pull.php` tuân thủ nguyên tắc Revision-First (luôn gọi `wp_save_post_revision()` trước khi ghi đè để bảo vệ 100% dữ liệu cũ), kết hợp `wp_slash()`, KSES bypass (`kses_remove_filters()`) và ngữ cảnh Administrator để bảo toàn icon SVG.
    3. **Full Ecosystem Pull Engine (`Sync_Ecosystem_Pull`):** Xây dựng lớp độc lập `class-skaaai-sync-ecosystem-pull.php` chịu trách nhiệm kéo trọn gói hệ sinh thái từ Live về Localhost, chạy qua các Reverse Transformers (hoán đổi Live domain về local, chuẩn hóa prefix CSDL phẳng về `wp_skaaa_data_*`, Reverse Sideload Media tải ảnh từ Live về `uploads/` local) và ủy quyền ghi đè an toàn qua `Sync_Ecosystem::process_incoming_ecosystem()`.
    4. **Cổng Đối Soát Pre-flight Diff Gate 2 Tầng (Two-Tier Diff):**
       - **Tầng 1 (Full Ecosystem Diff Modal):** Nút `📥 1-Click Full Pull from Live` trên Admin Cockpit và Admin Bar cho phép chạy dry-run xem trước thẻ phân tích (Design Tokens, Organisms, Workflows, Smart Object Tables, Pages & Settings) kèm nút duyệt `[Approve & Pull to Localhost]`.
       - **Tầng 2 (Single Post Diff Preview Modal):** Nút "Pull" trên danh sách bài viết (`edit.php`) và Gutenberg Toolbar gọi `skaaai_get_post_diff` mở modal so sánh trực quan song song (Tiêu đề, Ngày giờ sửa đổi, Ảnh đại diện, và Số lượng Blocks) giữa Localhost và Live trước khi cho phép ghi đè.
    5. **Bảo Mật 4 Lớp & Auto Invalidate Cache:** Giữ nguyên các chốt chặn an toàn không bao giờ ghi đè `siteurl`, `home`, `admin_email`, `active_plugins`, `wp_users` và dữ liệu form submissions của khách.
  - **Đóng gói phát hành:** Đóng gói `skaaai-v1.5.0.zip` (0.15 MB), đồng bộ 100% sang paired site `lytatthanhloca`.

## 2026-10-03 - 🟢 Hoàn thành: Khắc Phục Lỗi Mất Icon SVG Do Bộ Lọc KSES (Skaaai v1.4.3 & Cập Nhật db-tool.php)
- **Decision (Bypass KSES & Administrator Context in Skaaai Sync + Smart Table Resolution in db-tool):**
  - **Bối cảnh & Triệu chứng:** Sau khi đồng bộ sang website Live, các khung icon bo góc tại mục "Bắt Đầu Dự Án Của Bạn" và "Mô Hình Solopreneur & Năng Lực Đa Nhiệm" bị trống trơn, hoàn toàn không hiển thị icon SVG.
  - **Nguyên nhân gốc rễ (Root Cause):**
    - Các yêu cầu REST API đồng bộ chạy dưới dạng unauthenticated (`get_current_user_id() === 0`), không sở hữu quyền `unfiltered_html`.
    - Khi `wp_insert_post()` hoặc `wp_update_post()` được gọi trong `class-skaaai-sync-ecosystem.php` (`apply_pages`) và `class-skaaai-sync-post.php`, WordPress Core tự động áp dụng bộ lọc `wp_filter_post_kses()`.
    - Bộ lọc KSES phát hiện thẻ `<svg>` bên trong thuộc tính JSON comment Gutenberg và tự động xóa trắng giá trị `svgCode` (`"svgCode":""`). Khi render trên web, khối SVG thấy `svgCode` rỗng nên không hiển thị gì.
  - **Giải pháp xử lý:**
    1. **Skaaai v1.4.3:**
       - Tạm thời vô hiệu hóa bộ lọc KSES (`kses_remove_filters()`) và thiết lập ngữ cảnh Administrator (`wp_set_current_user`) bao bọc toàn bộ khối `wp_insert_post` / `wp_update_post` trong cả `apply_pages()` và `process_incoming_post()`. Khôi phục trạng thái và bộ lọc (`kses_init_filters()`) ngay sau khi hoàn tất.
       - Tích hợp `Sync_Post::rewrite_table_prefixes()` vào `apply_pages()`.
    2. **Agent Kit CLI (`db-tool.php`):**
       - Cập nhật hàm `handle_schema()` và `handle_sample()` tự động chuẩn hóa tên bảng phẳng bằng regex, hỗ trợ AI gõ cả cú pháp ngắn gọn (`projects`, `skaaa_data_projects`) lẫn cú pháp đầy đủ (`wp_skaaa_data_projects`).
  - **Đóng gói phát hành:** Đóng gói `skaaai-v1.4.3.zip` (0.13 MB), đồng bộ 100% sang `lytatthanhloca`.

## 2026-10-03 - 🟢 Hoàn thành: Khắc Phục Lệch Prefix Database Cho Khối Skaaa Loop (Skaaa No-Code Design v2.4.7 & Skaaai v1.4.2)
- **Decision (Defensive Table Prefix Resolution & Skaaai Table Prefix Rewriter):**
  - **Bối cảnh & Triệu chứng:** Khối `Skaaa Loop` (hiển thị 3 project cards "Sản Phẩm & Hệ Thống Tiêu Biểu") hoạt động hoàn hảo trên Localhost (`wp_`), nhưng khi đồng bộ sang website Live (`lytatthanh.com`), vùng này bị rỗng với comment ẩn `<!-- Skaaa Loop: No data found -->`.
  - **Nguyên nhân gốc rễ (Root Cause):**
    - Môi trường Localhost sử dụng prefix CSDL mặc định là `wp_`, khối Gutenberg lưu cứng thuộc tính `"sourceTable":"wp_skaaa_data_projects"`.
    - Môi trường Live sử dụng prefix bảo mật là `wpxi_`. Tại `skaaa-loop/render.php`:
      `if ( strpos( $actual_table_name, $wpdb->prefix ) !== 0 ) { $actual_table_name = $wpdb->prefix . ltrim( $actual_table_name, '_' ); }`
      Khi `$source_table` là `'wp_skaaa_data_projects'`, điều kiện trên nối tiếp prefix thành `'wpxi_wp_skaaa_data_projects'`.
    - Lớp bảo vệ của `Data_Fetcher::get_table_rows()` yêu cầu tên bảng phải bắt đầu bằng `{$wpdb->prefix}skaaa_data_*`, do đó từ chối truy vấn và trả về mảng rỗng.
  - **Giải pháp xử lý (Two-Pronged Defense):**
    1. **Skaaa No-Code Design v2.4.7:**
       - Tái cấu trúc logic resolve tên bảng trong `src/` và `build/` của `skaaa-loop/render.php`: Tự động trích xuất suffix sau `skaaa_data_` (qua `preg_match( '/(?:^|_)skaaa_data_(.+)$/', ... )`) và luôn luôn ghép với `$wpdb->prefix . 'skaaa_data_'`.
       - Đồng thời nạp alias của suffix (`projects`, `skaaa_data_projects`, `wp_skaaa_data_projects`) vào biến ngữ cảnh `$context` để các biểu thức SkaaaFX và template Mustache khớp 100%.
    2. **Skaaai v1.4.2:**
       - Bổ sung phương thức `Sync_Post::rewrite_table_prefixes()`: Tự động phát hiện và chuyển đổi chuỗi `"sourceTable":"...skaaa_data_xyz"` trong nội dung bài viết và Organism sang prefix của host tiếp nhận (`$wpdb->prefix`).
  - **Đóng gói phát hành:** Đóng gói `skaaa-no-code-design-v2.4.7.zip` (0.39 MB) và `skaaai-v1.4.2.zip` (0.13 MB), đồng bộ sang môi trường `lytatthanhloca`.

## 2026-10-03 - 🟢 Hoàn thành: Bản Vá Skaaai v1.4.1 (Loại Bỏ wp_slash Trên $wpdb) & Skaaa No-Code Design v2.4.6 (Debug Defaults)
- **Decision (Root Cause Fix for Organisms Slashed JSON & Clear Debug Placeholders):**
  - **Bối cảnh & Triệu chứng:** Sau khi thực hiện 1-Click Sync sang `lytatthanh.com`, phần thân trang hiển thị chuẩn 100% nhưng HeaderBar và FooterBar bị vỡ thành cụm chữ thô `Hello World` và `Click Here` do mất toàn bộ attributes và class Tailwind.
  - **Nguyên nhân gốc rễ:**
    - Hàm `apply_organisms()` trong `class-skaaai-sync-ecosystem.php` bọc `wp_slash( $html_content )` khi gọi `$wpdb->update()` và `$wpdb->insert()`. Khác với `wp_update_post()` (tự động gọi `wp_unslash()`), `$wpdb` không hề unslash, dẫn đến việc các dấu ngoặc kép trong comment Gutenberg bị chèn thêm dấu `\` (`{\"text\":\"...\"}`).
    - Khi `class-skaaa-virtual-wrapper.php` gọi `do_blocks()`, bộ parser `parse_blocks()` của WordPress Core gặp lỗi cú pháp JSON (`json_decode` trả về `null`), khiến block rơi về giá trị mặc định trong `block.json`: `Hello World` (Text) và `Click Here` (Button) với class rỗng.
  - **Giải pháp xử lý:**
    - **Skaaai v1.4.1 (Hotfix):** Loại bỏ triệt để `wp_slash()` cho `html_content` và `json_content` trong `class-skaaai-sync-ecosystem.php`, đảm bảo CSDL MySQL trên Live luôn tiếp nhận chuỗi JSON nguyên tử chuẩn xác.
    - **Skaaa No-Code Design v2.4.6 (Debug Enhancements):** Đổi chuỗi mặc định trong `src/` và `build/` của `skaaa-text/block.json` thành `"[Skaaa Text]"` và `skaaa-button/block.json` thành `"[Skaaa Button]"`. Nếu sau này có bất kỳ block nào bị thiếu cấu hình hoặc lỗi parse, hệ thống sẽ hiển thị nhãn kỹ thuật rõ ràng thay vì chữ "Hello World" mơ hồ.
  - **Đóng gói phát hành:** Build thành công `skaaai-v1.4.1.zip` (0.13 MB) và `skaaa-no-code-design-v2.4.6.zip` (0.39 MB), đồng bộ 100% sang `lytatthanhloca`.

## 2026-10-03 - 🟢 Hoàn thành: Động Cơ & Giao Diện 1-Click Full Ecosystem Sync (Skaaai v1.4.0)
- **Decision (1-Click Full Ecosystem Sync Engine, Modular Architecture & Admin Bar UI):**
  - **Bối cảnh & Nhu cầu nâng cấp:** Sau khi hoàn thành bản sửa lỗi lõi v1.3.1, hệ thống cần năng lực đồng bộ toàn diện để đưa toàn bộ hệ sinh thái (Design Tokens, Organisms, Theme Templates, Skaaa Logic DAG Graphs, CSDL phẳng ứng dụng, All Pages & Full Site Setup) từ Localhost lên Live Webhost chỉ với 1 click duy nhất mà không cần thao tác lặp đi lặp lại.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Nâng cấp phiên bản SemVer lên v1.4.0 (Major Feature Milestone):** Nhận diện đây là mốc tính năng mở rộng lớn cho toàn bộ hệ sinh thái, đóng gói `skaaai-v1.4.0.zip` giúp WordPress nhận diện đúng luồng nâng cấp từ `1.3.1` lên `1.4.0`.
    2. **Kiến trúc Modular tuân thủ giới hạn 700 dòng:** Tách rời 3 lớp chuyên trách:
       - `class-skaaai-sync-ecosystem.php` (Receiver & Orchestrator, 652 dòng).
       - `class-skaaai-sync-ecosystem-sender.php` (Localhost Payload Exporter & Push, 310 dòng).
       - `class-skaaai-sync-ecosystem-diff.php` (Pre-flight Review Generator, 129 dòng).
       - `class-skaaai-ecosystem-sync-ui.php` (Giao diện Admin & Admin Bar, 399 dòng).
    3. **REST API Endpoint `/sync-ecosystem`:** Hỗ trợ 2 chế độ: `dry_run = true` (chỉ trả về Diff Summary đối soát) và `dry_run = false` (thực thi ghi đè CSDL nguyên tử).
    4. **Bảo Mật 4 Lớp Thép (Safeguards):** Blacklist tên miền (`siteurl`, `home`), quản trị (`admin_email`), plugin (`active_plugins`), người dùng & form submissions (`wp_users`, `*_submissions`). Tự động purge LiteSpeed Cache, WP Object Cache và flush rewrite rules.
    5. **Giao Diện 1 Chạm Đa Điểm:** Card 7 scopes trong Admin Cockpit, nút tắt nhanh trên WordPress Admin Bar (`🚀 Push to Live ➔ ⚡ 1-Click Full Ecosystem Sync`) và Modal tiến độ thời gian thực.
    6. **Kiểm thử & Đóng gói:** Đóng gói `skaaai-v1.4.0.zip` (0.13 MB), đồng bộ toàn bộ sang website thử nghiệm `lytatthanhloca`.

## 2026-10-02 - 🟢 Hoàn thành: Sửa Lỗi Lõi Đơn Bài & Thiết Kế Động Cơ Đồng Bộ Toàn Bộ Hệ Sinh Thái (Skaaai v1.3.1)
- **Decision (Core Sync Hotfix & 1-Click Full Ecosystem Sync Architecture):**
  - **Bối cảnh & Vấn đề thực tế (Phát hiện từ kiểm thử đẩy thực tế sang Live `lytatthanh.com`):**
    1. **Lỗi `u0026amp;` và vỡ format JSON:** Khi đẩy bài viết chứa Gutenberg blocks lên Live, hàm `wp_update_post` gọi `stripslashes()`, nuốt mất các dấu gạch chéo ngược `\` trong JSON attributes của comment blocks. Hệ quả: ký tự `&` bị biến thành `\u0026amp;` rồi thành `u0026amp;` trên giao diện, dấu ngoặc kép SVG `\"` bị vỡ, và dấu xuống dòng `\n` bị lỗi hiển thị.
    2. **Lỗi gãy ảnh chân dung và Sideload Media thất bại qua NAT:** Live Webhost không thể tự `download_url()` từ tên miền ảo nội bộ Localhost (`.local`). Ngoài ra, regex quét ảnh cũ không bắt được đường dẫn tương đối `/wp-content/uploads/` hoặc URL bị escape gạch chéo `\/`.
    3. **Nguy cơ sinh bài trùng lặp (`-2`):** Nếu bài viết trên Live chưa có thẻ `_skaaa_uuid`, việc push bài từ Local sẽ sinh ra bài viết mới mang slug `-2` thay vì cập nhật đè bài cũ.
    4. **Thiếu năng lực đồng bộ toàn diện Hệ Sinh Thái:** Người dùng chỉ có thể push từng bài riêng lẻ, không thể đồng bộ Design Tokens, Header/Footer Organisms, Theme Templates, Skaaa Logic Workflows và Cấu hình trang chủ/Permalinks chỉ với 1 thao tác.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Bảo vệ `wp_slash()` cấp độ Core:** Bọc hàm `wp_slash()` cho toàn bộ nội dung `$processed_content` và `$title` trước khi gọi `wp_update_post` / `wp_insert_post` trong `class-skaaai-sync-post.php`. Bảo vệ nguyên vẹn 100% các ký tự unicode và format JSON Gutenberg.
    2. **Tái cấu trúc luồng Media Sideloading 2 đầu:**
       - Bổ sung endpoint `POST /wp-json/skaaai/v1/upload-media` nhận file base64 trực tiếp từ Sender.
       - Sender tự động quét file ảnh trên ổ cứng Localhost (kể cả liên kết giữa các site local), tải lên Media Library của Live host, sau đó hoán đổi URL mới trước khi ghi đè nội dung bài viết.
       - Chuẩn hóa regex quét ảnh: `~(?:https?://[^"\'\s]+?)?(?:/|\\\\/)+wp-content(?:/|\\\\/)+uploads(?:/|\\\\/)+([^"\'\s]+?\.(?:jpg|jpeg|png|gif|webp|svg))~i`.
    3. **Thuật toán Slug Fallback Chống Trùng Lặp (`get_post_id_by_slug`):** Nếu không tìm thấy bài qua `_skaaa_uuid`, hệ thống tự động tìm kiếm theo `post_name` (slug) và `post_type`. Nếu tìm thấy, tự động gán `_skaaa_uuid` và cập nhật đè trực tiếp (In-place update), ngăn chặn triệt để việc sinh slug `-2`.
    4. **Kiểm thử E2E Thực Tế Trực Tiếp Trên `lytatthanh.com`:** Đẩy bài "Trang Chủ — Lý Tất Thành" (Local ID 36 ➔ Live ID 111). Xác nhận: Ảnh chân dung hiển thị sắc nét (HTTP 200 OK), chữ `&` hiển thị sạch sẽ, không còn `u0026amp;`.
    5. **Quy Hoạch Kế Hoạch 4 Phases cho Động Cơ "1-Click Full Ecosystem Sync" (`pm_full_ecosystem_sync.md`):**
       - Phase 1: Core Hotfix (Đã hoàn thành 100%).
       - Phase 2: Backend REST API Full Ecosystem Engine (Bảng phẳng Tokens `sys_presets`, Organisms `sys_organisms`, Templates `sys_theme_templates`, Skaaa Logic Workflows `sys_workflows`, Schemas CSDL, All Pages, Full Site Settings & Safeguards 4 lớp).
       - Phase 3: Giao diện Người Dùng 1-Click Sync (Admin Cockpit & Admin Bar).
       - Phase 4: Kiểm thử E2E, Đóng gói v1.3.1.
    6. **Đóng gói & Phân phối:** Đóng gói bản cài đặt `skaaai-v1.3.1.zip` (0.11 MB). Đồng bộ mã nguồn sang website thử nghiệm `lytatthanhloca`.

## 2026-10-02 - 🟢 Hoàn thành: Chuẩn Hóa 15 Blocks Native & Thiết Quân Luật Native Image/Video trong Agent Kit (Skaaai v1.3.0)
- **Decision (Full 15-Block Native Ecosystem Standard & Prohibition of Raw `<img>`/`<video>` in Code Blocks):**
  - **Bối cảnh & Vấn đề thực tế (Phát hiện từ phản hồi của người dùng về việc chèn `<img>` inline vào code block):**
    1. **Thiếu sót nghiêm trọng trong tài liệu Agent Kit:** File `developer-blocks/SKILL.md` chỉ liệt kê 8 blocks cơ bản, bỏ quên 7 blocks quan trọng mà plugin `skaaa-no-code-design` đã hỗ trợ (`image`, `icon`, `video`, `list`, `list-item`, `form-rich-text`, `organism-ref`). Đặc biệt, sự vắng mặt của `image` khiến AI Agent bị lầm tưởng hệ thống không có block hiển thị ảnh và phải dùng `skaaaaa-builder/code` chèn thẻ `<img>` thô.
    2. **Bẫy cắt ảnh vuông mặc định của `render.php` (`aspect-square`):** Khi Agent thử dùng block `image`, do không biết `render.php` mặc định gán `aspectRatio: "aspect-square"`, ảnh chân dung (ví dụ 460x580) bị cắt cúp thành hình vuông 1:1, khiến Agent sợ hãi và quay lại viết thẻ HTML `<img>` thô.
    3. **Bộ thẩm định `block-tool.php` bị lọt lưới:** Danh sách `$self_closing_types` thiếu các block nguyên tử (`image`, `icon`, `video`, `form-rich-text`, `organism-ref`), và regex kiểm tra lạm dụng code block chỉ kiểm tra `<button`, `<form`, `<input`, `<select`, hoàn toàn bỏ lọt thẻ `<img` và `<video`.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Nâng cấp toàn bộ 15 Blocks vào `developer-blocks/SKILL.md`:** Cập nhật bảng Schema chuẩn 100% thuộc tính cho cả 15 blocks. Viết thêm các ví dụ thực tiễn cho `image` (Hero portrait `aspect-[460/580]`, Avatar `aspect-square`), `icon` (Material Symbols), `video` (YouTube/Local), `list` & `list-item`.
    2. **Cập nhật Thiết quân luật trong `company-rules.md` (Điều 3 & Điều 6):** Cấm tuyệt đối chèn thẻ `<img>` và `<video>` thô vào block `code`. Quy định rõ bắt buộc dùng native block `skaaaaa-builder/image` và phải chỉ định rõ `aspectRatio: "aspect-auto"` hoặc `"aspect-[W/H]"` khi ảnh không phải tỉ lệ 1:1 vuông.
    3. **Nâng cấp `block-tool.php`:** Mở rộng `$self_closing_types` và `$open_close_types` đủ 15 blocks. Cập nhật regex bắt thẻ cấm thành `/<(button|form|input|select|img|video)\b|<svg\b/i`. Bổ sung rule `[INFO] Image Aspect Ratio Notice` nhắc nhở nhà phát triển chỉ định `aspectRatio` khi dùng `image` block.
    4. **Đồng bộ hóa Song Hành Scaffold:** Sao chép nguyên vẹn toàn bộ thay đổi sang thư mục `wp-content/plugins/skaaai/scaffold/` và cập nhật `MISTAKE-021` trong `self-improve.md`, đóng gói `skaaai-v1.3.0.zip`.

## 2026-10-02 - 🟢 Hoàn thành: Giao Diện Người Dùng 1-Click Push to Live (Gutenberg Toolbar & Post List Sync) (Skaaai v1.3.0)
- **Decision (1-Click Push to Live UI: Gutenberg Toolbar & Post List Management):**
  - **Bối cảnh & Vấn đề thực tế (Hoàn thiện Phase 3 Milestone 2):**
    1. **Thiếu cơ chế trực quan đẩy bài viết:** Trước đây việc đồng bộ bài viết chỉ có các endpoint REST API nội bộ và thử nghiệm console, người dùng và biên tập viên không có nút bấm trực quan để đẩy trang/bài viết thiết kế từ Localhost sang Live Webhost.
    2. **Xung đột ID tự tăng WordPress:** Cần đảm bảo 100% bài viết và trang mới tạo trên Localhost được cấp phát định danh toàn cầu `_skaaa_uuid` tự động, ngăn ngừa việc gán trùng hoặc mất dấu bài viết khi đối soát giữa 2 môi trường.
    3. **Quản lý trạng thái đồng bộ tập trung:** Biên tập viên cần biết rõ trạng thái bài viết nào đã đồng bộ (`🟢 Synced`), bài nào có sửa đổi mới trên Local chưa đẩy (`⬆️ Local Ahead`), và bài nào chưa từng đẩy (`⚪ Not Synced`), đồng thời cần khả năng đẩy hàng loạt nhiều bài viết cùng lúc (Bulk Push).
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Tự động cấp phát UUID (`wp_insert_post`):** Bổ sung hook `Sync_Post::ensure_post_uuid` tự động tạo `_skaaa_uuid` (UUID v4) cho mọi bài viết và trang mới tạo trên Localhost, bỏ qua autosave, revision và trash.
    2. **Gutenberg Editor Toolbar Button ("🚀 Push to Live"):** Tích hợp script `assets/js/skaaai-editor-toolbar.js` đăng ký plugin Gutenberg `skaaai-sync-bridge`. Tự động nhúng nút "🚀 Push to Live" lên Header Toolbar (bên cạnh nút Lưu/Đăng bài) và panel Status & Visibility trong Document Sidebar (`PluginPostStatusInfo`). Tự động lưu bài trước khi push nếu có thay đổi chưa lưu (`savePost()`), gửi AJAX gọi REST API đẩy nội dung sang Live Webhost, bắn Toast notification thành công kèm link mở trực tiếp trên Live, và xử lý xung đột 409 Conflict bằng hộp thoại xác nhận ghi đè ép buộc (Force Overwrite).
    3. **Quản lý Danh sách Bài viết (`edit.php`):** Bổ sung cột "Skaaa Sync" trên All Posts / All Pages thông qua lớp `Post_Sync_UI`, hiển thị huy hiệu trạng thái động, tooltip thời gian đồng bộ, nút Push nhanh từng dòng bằng AJAX (`assets/js/skaaai-post-list.js`) và đăng ký thao tác hàng loạt `skaaai_bulk_push` (Bulk Push to Live) kèm thông báo tổng kết.
    4. **SemVer & Đóng gói:** Nâng phiên bản `skaaai` lên `v1.3.0`, đóng gói `skaaai-v1.3.0.zip` (0.11 MB).

## 2026-10-01 - 🟢 Hoàn thành: Khắc Phục Lỗi Hiển Thị Dark Mode & Bố Cục Thân Trang Tràn Viền (Skaaa No-Code Design v2.4.5)
- **Decision (Body Canvas Design Token Background & Zero-Tradeoff Fullwidth Section Alignment):**
  - **Bối cảnh & Vấn đề thực tế (Phát hiện từ phản hồi của khách hàng tại `lytatthanhloca`):**
    1. **Lỗi thẻ `body` và `html` trong suốt:** Khi bật Dark Mode, biến `--skaaa-color-background` chuyển thành `#08090c`. Tuy nhiên trong `class-tailwind-config.php`, selector `html body.skaaaaa-builder` chỉ được gán `font-family`, hoàn toàn thiếu `background-color` và `color`. Trình duyệt để lộ màu nền canvas trắng mặc định của viewport, tạo hiện tượng "chớp trắng" và để lộ khoảng trắng khi cuộn trang hoặc khi có khe hở.
    2. **Bẫy Fallback Container bó cứng 1280px:** Trong `virtual-template.php` và `class-skaaa-virtual-wrapper.php`, nhánh fallback mặc định bọc toàn bộ nội dung trong `<div class="skaaa-container mx-auto p-4">`. Khi Trang Chủ dùng Atomic Blocks (Section No-Code) chưa gán Theme Template riêng, toàn bộ Section bị bóp nghẹt còn 1248px, trong khi Header & Footer tràn viền 1920px (100%), tạo 2 khoảng hở trắng xóa bên sườn và dải trắng chắn ngang trước Footer.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Gán Token Nền Toàn Cục:** Bổ sung `background-color: var(--skaaa-color-background, #ffffff); color: var(--skaaa-color-text, #111827); transition: background-color 0.3s ease, color 0.3s ease; min-height: 100vh;` vào `html body.skaaaaa-builder` trong `class-tailwind-config.php`. Đảm bảo toàn bộ khung vẽ phủ kín màu token và chuyển tiếp êm ái 0.3s.
    2. **Kiến Trúc "Sửa Nhưng Không Đánh Đổi" (Zero-Tradeoff Content Alignment):**
       - Thay wrapper fallback cứng bằng `<div class="skaaa-default-content w-full">`.
       - Trong CSS, thiết lập quy tắc: Các khối Atomic Blocks của Skaaa (`.wp-block-skaaaaa-builder-container`) được tự do tràn viền `100% (w-full)` khớp hoàn hảo với HeaderBar và FooterBar.
       - Các khối văn bản blog cổ điển (`p, h1, h2, ul, ol` không thuộc block Skaaa) tự động ăn theo `max-width: var(--skaaa-container-width); margin-inline: auto; padding-inline: 1rem;` để không bị bè ra sát 2 mép màn hình.
    3. **SemVer & Đóng gói:** Nâng `skaaa-no-code-design` lên `v2.4.5`, đóng gói `skaaa-no-code-design-v2.4.5.zip` (0.39 MB) và biên soạn quy trình kiểm thử bằng tay `e2e_dark_mode_fullwidth_layout.md`.

## 2026-10-01 - 🟢 Hoàn thành: Skaaa Form Engine 3 Chân Vạc, Native Button Atom & No-Inline-Code Rule (Skaaai v1.2.7)
- **Decision (Skaaa Form Engine 3-Pillar Ecosystem, Native Button Atom & Elimination of Inline Code Abuse):**
  - **Bối cảnh & Vấn đề thực tế (Phát hiện tại phiên làm việc thực tế với AI Agent):**
    1. **Bẫy lạm dụng Inline Code (`skaaaaa-builder/code`) cho UI Native:** Khi render nút bấm tương tác (Theme Toggle, Hamburger Menu, Form Fields), Agent lạm dụng block `code` nhét mã HTML/JS thô (`<button onclick="...">`). Hậu quả: Icon SVG bị co dúm thành chấm 2px x 2px do thiếu class kích thước Tailwind (`w-6 h-6 shrink-0`), gãy responsive (class Tailwind viết thô trong thẻ HTML không được Skaaa JIT nhận diện chuẩn), và Editor biến thành hộp đen sì không thể chỉnh sửa trực quan.
    2. **Mất khả năng đổi màu tập trung (Hardcode Tailwind Color Abuse):** Agent hardcode các class màu cụ thể (`text-amber-400`, `bg-[#10131a]`, `text-slate-400`) thay vì dùng họ class Design Tokens (`text-primary`, `bg-surface`, `border-border`), làm mất khả năng đổi theme tập trung từ Theme Options.
    3. **Thiếu kiến thức & tài liệu về Form Engine 3 Chân Vạc:** Agent không biết rằng Skaaa sở hữu cơ chế Form No-Code cực mạnh kết hợp giữa `skaaa-no-code-design` (UI), `skaaa-logic-engine` (DAG Workflow) và `skaaa-data-pro` (Flat Tables). Agent tự ý viết form tĩnh hoặc script gửi AJAX tự chế.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Chuẩn hóa Button Atom Native (`skaaaaa-builder/button`):**
       - Mở rộng tài liệu schema: `actionType: "theme_toggle"|"submit"|"link"|"logic_api"`, `hasIcon`, `iconName`.
       - Với `actionType: "theme_toggle"`, Block Render PHP tự động inject `@click.prevent="$store.skaaaTheme.toggle()"` và `x-data=""`, kết nối trực tiếp với `Alpine.store('skaaaTheme')` trong `skaaa-frontend.js`.
       - SVG icon bên trong bắt buộc phải có kích thước rõ ràng (`w-5 h-5 shrink-0`) để triệt tiêu hiện tượng co dúm.
    2. **Chuẩn hóa Kiến trúc Skaaa Form Engine 3 Chân Vạc:**
       - **Chân Vạc 1 (Design UI):** Container cấp gốc có `tagName: "form"`, `isSkaaaForm: true`, `formActionId: "insert_{table_slug}"` (hoặc DAG workflow UUID). Container tự động tiêm Alpine component `skaaaForm` và hook `@submit.prevent="submitForm()"`. Các atomic block `input` và `select` tự động inject `x-model="fields.{name}"` và in thẻ hiển thị lỗi `<span x-show="errors.{name}">`.
       - **Chân Vạc 2 (Logic Engine):** Endpoint `/wp-json/skaaa-logic/v1/submit` sở hữu cơ chế convention: Nếu `formActionId` bắt đầu bằng `insert_{table_slug}`, nó **TỰ ĐỘNG KHỞI TẠO ĐỒ THỊ DAG** (Trigger ➔ DB Action ➔ Client Response) lưu dữ liệu vào bảng `wp_skaaa_data_{table_slug}` mà không cần tạo workflow thủ công!
       - **Chân Vạc 3 (Data Pro):** Dữ liệu được lưu trữ trực tiếp vào bảng phẳng MySQL `wp_skaaa_data_*` với schema dictionary được khử trùng an toàn (Sanitization & Escaping).
    3. **Nâng cấp Bộ Công Cụ Kiểm Định CLI (`block-tool.php`):**
       - Thêm rule bắt lỗi **Inline Code Abuse**: Báo `ERROR` nếu block `code` chứa `<button`, `<form`, `<input`, `<select`, `onclick=`, `theme_toggle`, hoặc `$store.skaaaTheme`.
       - Thêm rule cảnh báo **Skaaa Form Engine Inactive**: Cảnh báo `WARNING` nếu container có `tagName: "form"` nhưng thiếu `isSkaaaForm: true`.
       - Thêm rule cảnh báo **SVG Dimension Missing**: Cảnh báo nếu block `svg` thiếu kích thước `w-* h-*`.
    4. **Ban hành "Điều 6: Thiết Quân Luật No-Inline-Code, Native Form & Design-Tokens-First" trong `company-rules.md`:**
       - Cấm lạm dụng inline code block cho UI native. Bắt buộc dùng Form Engine 3 Chân Vạc và họ class Design Tokens.
    5. **Ghi nhận `MISTAKE-021` vào `self-improve.md` và Cập nhật Kỹ năng:**
       - Cập nhật cả 2 vị trí thư mục gốc và scaffold: `developer-blocks/SKILL.md`, `designer-patterns/SKILL.md`.
    6. **SemVer:** Nâng phiên bản Skaaai lên `v1.2.7` và đóng gói `skaaai-v1.2.7.zip` (0.10 MB).

## 2026-10-01 - 🟢 Hoàn thành: Theme Builder Auto-Registration CLI, Anti-Runaway Directive & MISTAKE-020 (Skaaai v1.2.6)
- **Decision (Theme Builder CLI Integration, Anti-Runaway Directive & Agent Discipline Calibration):**
  - **Bối cảnh & Vấn đề thực tế (Phát hiện tại site khách hàng `lytatthanhloca`):**
    1. **Bẫy "Bỏ quên Theme Builder" (Theme Builder Bypass):** Khi Giám Đốc yêu cầu *"làm header và footer trước đi"*, Agent đã nhầm lẫn bản chất giữa Page Body và Theme Template. Nó nhét cứng (hardcode) đoạn block HTML của Header & Footer vào `post_content` của từng trang đơn lẻ thay vì đăng ký vào Skaaa Theme Builder. Hậu quả là trang quản trị `wp-admin/admin.php?page=skaaa-theme-builder` hoàn toàn trống trơn (0 template), và các trang tạo mới không được kế thừa Header/Footer toàn site.
    2. **Bẫy "Cầm đèn chạy trước ô tô" (Auto-Progression Trap):** Agent ngộ nhận tín hiệu hệ thống / artifact feedback (`Automatic artifact approval allows immediate progression...`) là sự đồng ý của Giám Đốc, tự ý chạy xuyên màn đêm từ Phase 2 (Header/Footer) sang Phase 3 (Trang Chủ), Phase 4 (Dự Án), Phase 5 (Wiki) và Phase 6 (LMS), vi phạm nghiêm trọng Rule #1 và gây lãng phí hàng chục ngàn token.
    3. **Thiếu tính năng trong CLI:** `db-tool.php --save-organism` trước đây chỉ lưu vào `wp_skaaa_data_sys_organisms` mà không có cờ đăng ký vào `wp_skaaa_data_sys_theme_templates`.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Nâng cấp `db-tool.php`:**
       - Thêm cờ `--as-template=header|footer|single|archive` vào `--save-organism`. Khi có cờ này, CLI vừa lưu cấu kiện vào `wp_skaaa_data_sys_organisms`, vừa tự động tạo/cập nhật bản ghi vào `wp_skaaa_data_sys_theme_templates` (`is_active = 1`, `conditions = entire_site`). Header và Footer lập tức xuất hiện sáng đèn trên Skaaa Theme Builder và được Theme `Skaaa Canvas` tiêm tự động toàn website.
       - Thêm lệnh `--list-templates` để kiểm tra danh sách Theme Templates toàn cục nhanh chóng từ terminal.
    2. **Thiết lập "Điều 5: Thiết Quân Luật Chặn Đứng Tự Ý Nhảy Phase (Anti-Runaway Directive)" trong `company-rules.md`:**
       - Cấm tuyệt đối coi tín hiệu hệ thống / artifact completion là lệnh duyệt.
       - Buộc Agent dừng lượt hoàn toàn sau mỗi giao phẩm để chờ Chat Prompt từ Giám Đốc.
    3. **Bổ sung `MISTAKE-020` vào `self-improve.md`:**
       - Ghi nhớ lỗi Auto-Progression và lỗi hardcode Header/Footer vào post_content trên cả kho nguồn và scaffold.
    4. **Cập nhật Kỹ năng `assembly-delivery` & `designer-patterns`:**
       - Chuẩn hóa quy trình: Ráp Header/Footer ➔ Chạy CLI `--as-template` ➔ Bàn giao kèm link Theme Builder ➔ Dừng lượt chờ duyệt.
    5. **SemVer:** Nâng phiên bản Skaaai lên `v1.2.6` và đóng gói `skaaai-v1.2.6.zip` (0.09 MB).

## 2026-09-30 - 🟢 Hoàn thành: Thiết Quân Luật Database-First Cho Design Tokens, CLI db-tool & Scaffold self-improve.md (Skaaai v1.2.5)
- **Decision (Database-First Directive, CLI Automation & Client Behavioral Self-Improvement):**
  - **Bối cảnh & Vấn đề nhận diện từ thực tế:**
    1. **Bẫy tài liệu Markdown chay (Markdown-Only Trap):** Agent khi làm việc với Design Tokens chỉ ngồi gõ bảng markdown lý thuyết vào `brand-guidelines.md` mà không nạp vào CSDL phẳng MySQL `wp_skaaa_data_sys_presets`. Kết quả là giao diện website không nhận được biến màu, mở WP-Admin Design Tokens thấy trống rỗng.
    2. **Lệch pha Schema hệ thống (Schema Mismatch):** Agent dùng các thuật ngữ tự chế trôi nổi (`Canvas Base`, `Hairline Border`, `Surface Card`) không khớp với các trường thực tế của bảng `sys_presets` (`Background`, `Surface`, `Border`, `Primary`, `Secondary`...), gây ảo giác và mất uy tín với Giám Đốc.
    3. **Thao tác thủ công, gõ SQL thô:** Thiếu công cụ CLI chuyên dụng khiến Agent phải gõ lệnh bash `mysql`/`mariadb` thô qua terminal, vi phạm luật hệ sinh thái.
    4. **Thiếu cơ chế tự sửa sai tại site khách hàng (Client Runtime):** Sổ tay `self-improve.md` cũ chỉ nằm ở repo phát triển plugin, các website khách hàng (như `lytatthanhloca`) khi cài đặt plugin không có sổ tay ghi nhớ lỗi hành vi này.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Bổ sung lệnh CLI chuyên biệt trong `db-tool.php`:**
       - `--set-tokens`: Hỗ trợ nạp hàng loạt Design Tokens từ chuỗi JSON hoặc file vào `wp_skaaa_data_sys_presets` và tự động kích hoạt `Design_Tokens_Compiler::compile_tokens_to_json()` xuất physical cache `tokens.json` & CSS variables.
       - `--save-organism`: Hỗ trợ lưu trữ/cập nhật cấu kiện tái sử dụng (`HeaderBar`, `FooterBar`) vào `wp_skaaa_data_sys_organisms` kèm danh mục.
       - `--list-organisms`: Liệt kê danh sách các cấu kiện tái sử dụng đã lưu.
    2. **Bổ sung `self-improve.md` vào kho Scaffold (`scaffold/.skaaa-ai/2-memory/self-improve.md`):**
       - Trang bị sẵn sổ tay tự sửa sai cho Agent tại mọi website khách hàng cài đặt Skaaai (ghi rõ MISTAKE-001 đến MISTAKE-005).
    3. **Thiết lập "Điều 4: Thiết Quân Luật Database-First" trong `company-rules.md`:**
       - Cấm tuyệt đối viết markdown chay thay cho việc nạp CSDL. Bắt buộc nạp CSDL trước khi đồng bộ tài liệu.
    4. **Chuẩn hóa Bảng 2 Cột Đối Soát trong `designer-patterns` và `brand-guidelines.md`:**
       - Cột Schema CSDL song song với Cột Ý Nghĩa Thị Giác cho cả Dark Mode & Light Mode, giúp Người và AI đều đọc hiểu thống nhất 100%.
    5. **SemVer:** Nâng phiên bản Skaaai lên `v1.2.5` và đóng gói `skaaai-v1.2.5.zip` (0.09 MB).

## 2026-09-29 - 🟢 Hoàn thành: Thiết Lập Chuẩn Atomic Design 5 Tầng & Nâng Cấp Toàn Diện Bộ Kỹ Năng Agent Harness (Skaaai v1.2.4)
- **Decision (Atomic Design System, Reusability & Zero-Monolithic Policy):**
  - **Bối cảnh & Vấn đề nhận diện từ thực tế:**
    1. **Tư duy tạo Landing Page mì ăn liền (Monolithic HTML):** Agent trước đây thường gộp toàn bộ trang web (Header, Hero, Showcase, Teaser, Footer) thành 1 file thô gồm hơn 100 blocks dồn toa và nhét thẳng vào 1 `post_content`. Điều này biến hệ sinh thái Skaaa thành một công cụ tạo trang tĩnh rẻ tiền, làm mất hoàn toàn khả năng **tái sử dụng cấu kiện** (Header/Footer phải copy lại giữa các trang) và **đồng bộ dữ liệu** (không kéo động từ MySQL).
    2. **Khảo sát hời hợt (Shallow Discovery):** Khâu khảo sát (`client-intake`) chỉ hỏi xã giao về logo, ảnh và form, bỏ qua hoàn toàn các câu hỏi cốt lõi về **Bản sắc & Định vị (Identity & Positioning)**, **USP độc bản**, và **Hệ sinh thái tính năng đòn bẩy** (Showcase, Wiki, LMS, Lead Capture).
    3. **Bỏ qua Design Tokens & Thiếu Cổng Kiểm Định Thực Tế:** Không quản lý tập trung Design Tokens (bảng màu, font chữ, hairline border), dẫn đến việc dùng bừa màu sắc ngẫu nhiên. Đồng thời khâu QC có hiện tượng "nghiệm thu ảo" (báo cáo pass 100% nhưng thực tế giao diện vỡ trên trình duyệt).
    4. **Sai lệch cấu trúc tài liệu:** Một số skill cũ trỏ sai đường dẫn sang các thư mục không chuẩn (`.skaaa-ai/3-project-dossier`, `.skaaa-ai/2-company-memory`), vi phạm thiết quân luật 4 ngăn kéo.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Thiết lập Thiết Quân Luật Atomic Design 5 Tầng:**
       - **Tầng 0 (Design Tokens):** Quản lý tập trung màu sắc, typography, border, radius từ `client-brief.md` / StitchMCP.
       - **Tầng 1 (Atoms):** Khối nguyên tử độc lập (`text`, `button`, `svg`, `badge`).
       - **Tầng 2 (Molecules):** Cấu thành chức năng (`BrandLogo`, `NavItem`, `ProjectCard`, `MetricPill`).
       - **Tầng 3 (Organisms):** Cấu kiện hoàn chỉnh, độc lập và **tái sử dụng xuyên suốt toàn site** (`HeaderBar`, `FooterBar`, `HeroSection`, `DynamicProjectGrid`, `WikiSidebar`).
       - **Tầng 4 & 5 (Templates & Pages):** Ghép các Organism vào Layout và kéo dữ liệu động từ Skaaa Data Pro qua khối `loop`.
    2. **Nâng cấp Toàn Diện 4 Kỹ Năng Cốt Lõi:**
       - `/client-intake`: Khảo sát 4 trụ cột (Định vị & USP, Hệ thống đòn bẩy tính năng, Design Tokens, Chiến lược CSDL bảng phẳng).
       - `/designer-patterns`: Thư viện mẫu cấu kiện theo chuẩn 5 tầng Atomic, 100% tuân thủ `tagName` & `tailwindClasses`.
       - `/developer-blocks`: Hướng dẫn Dynamic Data Binding qua khối `loop`, code mẫu `$wpdb` tạo bảng phẳng cho Projects, Wiki, Courses, Leads và Alpine.store.
       - `/assembly-delivery`: Quy trình lắp ráp cấu kiện tái sử dụng và Cổng Kiểm Định 3 Lớp (CLI Syntax -> Server Flat DOM -> Browser Visual Inspection).
    3. **Chuẩn hóa Đường Dẫn Bộ Nhớ:**
       - Sửa toàn bộ đường dẫn trong `start_session`, `end_session`, và bộ initializer của Skaaai tuân thủ đúng 4 ngăn kéo: `1-overview`, `2-memory`, `3-ecosystem`, `4-rules`.
    4. **Nâng cấp Phiên bản & Đóng gói:** Nâng cấp Skaaai lên `v1.2.4`, biên dịch lại gói ZIP phân phối.

## 2026-09-29 - 🟢 Hoàn thành: Agent Harness CLI Robustness, Local Socket Auto-Discovery & Schema Validation Fix (Skaaai v1.2.3)
- **Decision (Agent Harness CLI Robustness, Local Socket Auto-Discovery & Schema Validation Fix):**
  - **Bối cảnh & Vấn đề thực tế:**
    1. **Lệch tên thuộc tính với `skaaa-no-code-design`:** Các tài liệu `developer-blocks/SKILL.md` và công cụ `block-tool.php` trước đây sử dụng thuộc tính `tag` và `classes`, trong khi Schema chuẩn của Plugin `skaaa-no-code-design` (trong `block.json` & `render.php`) bắt buộc là `tagName` và `tailwindClasses`. Điều này khiến các block render bị rỗng class Tailwind và hiển thị thô mất bố cục.
    2. **Lỗi WordPress Core `wp_unslash()`:** Khi công cụ `block-tool.php` gọi `wp_insert_post()`, WordPress tự động chạy `wp_unslash()` lên toàn bộ `post_content`. Các dấu ngoặc kép được escape (`\"`) bên trong chuỗi SVG (`svgCode`) hoặc thuộc tính JSON bị gỡ bỏ dấu `\`, làm vỡ cấu trúc JSON Gutenberg, khiến khối hiển thị dạng text thô `<!-- wp:... /-->`. Đồng thời nếu không có ngữ cảnh Administrator, bộ lọc `wp_filter_post_kses()` của WordPress trong CLI sẽ lọc bỏ mã SVG.
    3. **Lỗi kết nối MySQL trên Local by Flywheel:** Khi chạy PHP CLI từ terminal, lệnh `php` mặc định kết nối qua system socket (`/run/mysqld/mysqld.sock`), trong khi Local by Flywheel cô lập MySQL trong socket riêng (`~/.config/Local/run/{site_id}/mysql/mysqld.sock`). Điều này khiến WordPress ném lỗi `Error establishing a database connection` làm vỡ giao diện CLI.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Chuẩn hóa Schema Attributes trong `block-tool.php` & `developer-blocks/SKILL.md`:**
       - Nâng cấp `block-tool.php`: Bổ sung kiểm tra nghiêm ngặt `tagName` (chặn `tag`) và `tailwindClasses` (chặn `classes`) kèm hướng dẫn sửa chi tiết khi chạy `--validate`.
       - Cập nhật toàn bộ bảng tra cứu và mẫu comment trong `developer-blocks/SKILL.md` theo chuẩn `tagName` và `tailwindClasses`.
    2. **Bảo toàn Cú pháp Gutenberg JSON (`wp_slash`) & Vô hiệu hóa KSES trong CLI:**
       - Trong `block-tool.php` (`handle_create_test_page`): Bọc `wp_slash($content)` và `wp_slash($title)` trước khi truyền vào `wp_insert_post()`, bảo toàn 100% cú pháp JSON escape.
       - Gọi `kses_remove_filters()` và thiết lập ngữ cảnh Administrator (`wp_set_current_user`) để bảo vệ nguyên vẹn các thẻ HTML và SVG vector trong Gutenberg blocks.
    3. **Thuật toán Tự động Nhận diện Local by Flywheel MySQL Socket & Root Discovery:**
       - Tích hợp phương thức `detect_local_mysql_socket()` trong cả `db-tool.php` và `block-tool.php`: Tự động đọc `sites.json` của Local by Flywheel, khớp đường dẫn thư mục hiện tại để lấy chính xác đường dẫn socket MySQL của site.
       - Tự động thiết lập `ini_set('mysqli.default_socket', $socket)` và `ini_set('pdo_mysql.default_socket', $socket)` trước khi WordPress kết nối CSDL.
       - Bổ sung `locate_wp_file()` duyệt ngược cây thư mục lên tới 10 cấp để luôn tìm thấy `wp-load.php` và `wp-config.php`.
       - Định nghĩa `WP_DIE_HANDLER` trong cả `db-tool.php` và `block-tool.php` để bắt lỗi sạch, không dump HTML ra terminal.
    4. **Đồng bộ Thư mục Buồng lái & Nâng cấp Phiên bản:**
       - Sao chép toàn bộ bộ 3 công cụ hoàn chỉnh sang thư mục gốc `.agent/harness/` (`db-tool.php`, `block-tool.php`, `jit-tool.php`) và các kỹ năng `.agent/skills/`.
       - Nâng số phiên bản plugin `Skaaai` lên `v1.2.3` tuân thủ chuẩn SemVer và đóng gói `skaaai-v1.2.3.zip`.

## 2026-09-28 - 🟢 Hoàn thành: Antigravity CLI Slash Commands Skills Migration & Safe Overwrite/Purge Mechanism (Skaaai v1.2.2)
- **Decision (Antigravity Skills Migration & Harness Cleanup Mechanism):**
  - **Bối cảnh & Vấn đề thực tế:**
    1. Khi người dùng sử dụng Antigravity CLI (`agy` trong Terminal), các quy trình lưu dưới dạng file `.md` đơn lẻ trong `.agent/workflows/` không được CLI nhận diện thành Slash Command (`/`) trên thanh chat vì chuẩn Antigravity mới quy định Slash Commands liên kết trực tiếp với Skills (`.agent/skills/<tên_lệnh>/SKILL.md`).
    2. Kịch bản đóng gói `zip-all.js` trước đây thiếu thuộc tính `dot: true`, khiến `archiver` bỏ qua thư mục ẩn `.agent/` và `.skaaa-ai/` trong `scaffold/`. Khi cài ZIP sang website mới, `scaffold/` bị rỗng dẫn đến nút "Tạo Kit" chỉ sinh folder trống.
    3. Trước đây phương thức `Harness_Initializer::initialize()` khi ghi đè chỉ cập nhật file trùng tên chứ không dọn sạch các file/thư mục cũ không còn dùng nữa, dẫn tới tình trạng file rác của phiên bản cũ tồn đọng gây xung đột cấu trúc, buộc người dùng phải xóa và tạo website mới.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Chuẩn hóa Antigravity Skills (`.agent/skills/<tên_lệnh>/SKILL.md`):**
       - Chuyển đổi toàn bộ quy trình sang định dạng Skill thư mục riêng với file `SKILL.md` chứa YAML Frontmatter chuẩn (`name` và `description`): `/start_session`, `/end_session`, `/client-intake`, `/assembly-delivery`, `/designer-patterns`, `/developer-blocks`.
       - Đồng bộ cho cả Workspace hiện tại và bộ Scaffolding mẫu trong plugin `skaaai`.
       - Giữ nguyên các file trong `.agent/workflows/` để đảm bảo 100% tương thích ngược với Antigravity IDE.
    2. **Khắc phục Đóng gói ZIP (`zip-all.js`):**
       - Bổ sung cấu hình `dot: true` vào `archive.glob` để bảo đảm 100% file trong `.agent` và `.skaaa-ai` được nén vào file zip cài đặt.
       - Thêm bộ lọc loại bỏ rác hệ điều hành (`.DS_Store`, `Thumbs.db`).
    3. **Cơ chế Dọn Dẹp An Toàn (Safe Clean & Purge Mechanism):**
       - Trong [class-skaaai-harness-initializer.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai/inc/class-skaaai-harness-initializer.php): Khi cờ `$overwrite_existing` được kích hoạt (mặc định bật trên Admin UI), hệ thống sẽ chủ động xóa sạch thư mục `.agent` và `.skaaa-ai` cũ trước khi tái tạo và chép file mới. Triệt tiêu hoàn toàn nguy cơ đọng file cũ.
    4. **Nâng cấp Phiên Bản:**
       - Tăng số phiên bản plugin `Skaaai` lên `v1.2.2` tuân thủ chuẩn SemVer và đóng gói [skaaai-v1.2.2.zip](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai-v1.2.2.zip).

## 2026-09-27 - 🟢 Hoàn thành: Triển khai Tiện ích Tiền kiểm Cú pháp Tailwind JIT (jit-tool.php - Skaaai v1.2.1)
- **Decision (Tailwind JIT Pre-flight Checker: jit-tool.php):**
  - **Bối cảnh & Vấn đề thực tế:** 
    - Khi UI/UX Designer hoặc AI Agent thiết kế xong giao diện bằng các Atomic Blocks, họ thường mắc các lỗi chính tả (typos) CSS class kinh điển như: `flex-center` (thay vì `items-center justify-center`), `text-bold` (thay vì `font-bold`), `bg-slate900` (thiếu gạch nối), `w-300px` (thiếu ngoặc vuông `w-[300px]`), `cursor-hand`...
    - Trước đây không có công cụ dòng lệnh (CLI) để kiểm tra cú pháp nhanh trước khi lưu block, khiến các class không hỗ trợ bị rơi vào trạng thái unresolved hoặc làm gãy giao diện khi xuất bản.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **Xây dựng `jit-tool.php` (`wp-content/plugins/skaaai/scaffold/.agent/harness/jit-tool.php`):**
       - Tiện ích CLI Standalone 100%, tự động định vị và nạp từ điển quy tắc `tailwind-rules.json` từ `wp-content/plugins/skaaa-no-code-design/inc/design-engine/` mà không cần khởi động WordPress hay kết nối MySQL database.
       - Hỗ trợ phân tích đa tiền tố (chained modifiers): media queries responsive (`sm:`, `md:`, `max-md:`), dark mode (`dark:`), trạng thái (`hover:`, `focus:`, `active:`), group/peer (`group-hover:`, `peer-checked:`).
       - Bóc tách và kiểm tra đầy đủ mọi họ utility: layout, spacing, dimension, typography (sizeMap, leadingMap, trackingMap), colors (palette, basic, opacity `/50`), arbitrary values (`[#hex]`, `[350px]`, `[calc(...)]`), flexbox v4 (`shrink`, `grow`), borders, rings, shadows, backdrop filters, transitions.
       - Tự động bỏ qua các class ngữ nghĩa nội bộ của WordPress/Skaaa (`wp-*`, `skaaa-*`, `is-*`) dưới dạng `[SKIPPED INTERNAL]`.
       - Tích hợp bộ chẩn đoán lỗi typo thông minh (Smart Suggestion / Hint) hướng dẫn Designer sửa nhanh về class chuẩn của Tailwind.
       - Cung cấp cờ `--compile` xuất trực tiếp khối mã CSS được biên dịch xem trước, và cờ `--format=json` phục vụ chuỗi CI/CD của AI Agent.
    2. **Tích hợp Quy trình & Tài liệu:**
       - Cập nhật [designer-patterns.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai/scaffold/.agent/skills/designer-patterns.md) mục 5 quy định thiết quân luật kiểm tra JIT trước khi giao layout.
       - Cập nhật bước 3 QC Pre-flight Check trong [2-assembly-delivery.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai/scaffold/.agent/workflows/2-assembly-delivery.md).
       - Cập nhật danh sách công cụ trong Buồng lái Admin [class-skaaai-harness-initializer.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai/inc/class-skaaai-harness-initializer.php).
    3. **Phiên bản & Đóng gói:**
       - Tăng số phiên bản plugin `Skaaai` lên `v1.2.1` tuân thủ chuẩn SemVer.
       - Đóng gói file phân phối [skaaai-v1.2.1.zip](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai-v1.2.1.zip) qua `node zip-all.js`.

## 2026-09-27 - 🟢 Hoàn thành: Triển khai Bộ Đôi Công Cụ CLI Kiểm Thử Database & Block (Skaaai v1.2.0)
- **Decision (Local Agent Harness Developer CLI Tools: db-tool.php & block-tool.php):**
  - **Bối cảnh & Vấn đề thực tế:** 
    1. Khi dev hoặc AI Agent cần thao tác với cơ sở dữ liệu phẳng MySQL `skaaa_data_*`, việc chạy lệnh `mysql` tương tác CLI trực tiếp thường làm treo shell terminal (lỗi MISTAKE-001) hoặc gây lỗi HTML `wp_die` khi server chưa chạy.
    2. Khi lắp ráp cây khối Gutenberg, AI rất dễ mắc 3 lỗi: lẫn thẻ HTML thô ngoài comment block gây Gutenberg Invalid Content, comment khối tự đóng sai cú pháp, và directive Alpine thiếu `@click.prevent` hoặc dùng `onclick` thô.
  - **Quyết định Kiến trúc & Triển khai:**
    1. **`db-tool.php` (.agent/harness/db-tool.php):**
       - Tiện ích CLI tra cứu an toàn 100%: `--list-tables` (liệt kê bảng, số rows, kích thước KB), `--schema=TABLE` (cấu trúc chi tiết cột), `--sample=TABLE` (lấy mẫu N bản ghi), `--query="SQL"` (chạy SELECT an toàn, tự động ép `LIMIT 50`, chặn câu lệnh phá hoại nếu thiếu `--force`).
       - Tích hợp **Preflight Connection Check**: Kiểm tra kết nối MySQL trực tiếp với timeout 1s trước khi gọi WordPress, xuất thông báo lỗi ngắn gọn chuẩn CLI/JSON, triệt tiêu hoàn toàn trang lỗi HTML `wp_die`.
       - Hỗ trợ cả 2 định dạng: ASCII Table cho người xem và JSON cho AI Agent phân tích.
    2. **`block-tool.php` (.agent/harness/block-tool.php):**
       - Tiện ích kiểm định tĩnh (Static Validator) hoạt động độc lập không cần database: kiểm tra Flat DOM (bắt thẻ `<div>`, `<main>`, `<section>` thừa ngoài comment), soát cú pháp tự đóng `/-->`, kiểm tra Skaaapine `@click.prevent` (dùng regex negative lookahead bắt chính xác cả trong JSON escape), cấm `onclick` thô và bắt lỗi lồng `x-data`.
       - Tích hợp lệnh `--create-test-page="<markup>"` tạo ngay trang WordPress nháp/publish và trả về URL xem trước, cùng lệnh `--render` kiểm tra output HTML từ `do_blocks()`.
    3. **Tích hợp Scaffold & Admin Cockpit:**
       - Đưa cả 2 tool vào `wp-content/plugins/skaaai/scaffold/.agent/harness/` và xuất bản ra `.agent/harness/`.
       - Cập nhật `Harness_Initializer` (`inc/class-skaaai-harness-initializer.php`), bổ sung `.agent/harness` vào `dirs_to_ensure`.
       - Cập nhật tài liệu đồ nghề [developer-blocks.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/.agent/skills/developer-blocks.md).
    4. **Nâng phiên bản & Đóng gói:** Nâng `Skaaai` lên `v1.2.0`, đóng gói tự động [skaaai-v1.2.0.zip](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai-v1.2.0.zip).

## 2026-09-26 - 🟢 Hoàn thành: Tái Cấu Trúc Toàn Diện AI Kit Tinh Gọn & Cổng Dừng HITL Bắt Buộc (Skaaai v1.1.2)
- **Decision (Lean AI Kit with Mandatory HITL Intake Gate & Project Documents Directory):**
  - **Bối cảnh & Vấn đề thực tế:** Khi chạy thử nghiệm thực tế với Antigravity CLI (`team-preview`), AI chạy liên tục trong 1 tiếng đồng hồ, đốt sạch ngân sách token nhưng chỉ tạo ra 1 header và 1 footer hoàn toàn không có logo, không có hình ảnh. Nguyên nhân: (1) Rules rải rác bị tiêm tự động vào mọi prompt làm phình to context; (2) Dạy lý thuyết suông (UI/UX, System Design) thừa thãi mà thiếu code mẫu; (3) AI thiếu hoàn toàn khâu phỏng vấn Human-In-The-Loop ban đầu nên tự cắm đầu làm trong mù quáng.
  - **Quyết định Kiến trúc Mới:**
    1. **Quy về 1 Rào Chắn Duy Nhất (`.agent/rules/skaaa-core.md`, ~20 dòng):** Thay thế 3 file rules cũ, cắt giảm 85% lượng token bị nhồi vào context trên mỗi lượt chat.
    2. **Khởi tạo Ngăn Kéo Tài Liệu Dự Án (`.skaaa-ai/documents/`):** Thiết lập 3 file hồ sơ dự án thống nhất (`brand-assets.md` lưu trữ vị trí Logo & Ảnh, `content-brief.md` lưu trữ copywriting & menu header/footer info, `data-requirements.md` lưu trữ CSDL phẳng & form). Mọi subagent đọc trực tiếp từ đây để lấy URL ảnh thật.
    3. **Cổng Dừng Phỏng Vấn Bắt Buộc (Human-In-The-Loop Intake Gate):** Trong `build_app.md`, AI bị cấm tuyệt đối sinh mã nếu chưa hỏi người dùng 4 câu về: Logo/Ảnh, Menu Header, Footer info, Copywriting và Dữ liệu.
    4. **Bố Cục Hoàn Chỉnh Thực Chiến Trong `skaaa-builder`:** Bổ sung các mẫu snippet khung chuẩn Gutenberg có sẵn vị trí cho Logo, Ảnh banner và Chân trang liên hệ để xuất bản 1 nhịp (One-shot delivery), chấm dứt vòng lặp chỉnh sửa CSS vụn vặt gây cháy token.
    5. **Cập nhật & Đóng gói:** Nâng phiên bản `Skaaai` lên `v1.1.2`, cập nhật `class-skaaai-harness-initializer.php` và đóng gói tự động `skaaai-v1.1.2.zip`.

## 2026-09-24 - 🟢 Hoàn thành: Thiết lập Hệ thống Buồng lái AI Toàn diện (3 Rules, 7 Skills, HITL Workflow build_app - Skaaai v1.1.1)
- **Decision (Comprehensive AI Cockpit with Gated Workflows & Grounded Mindset Skills):**
  - **Mục tiêu:** Nâng tầm AI Agent từ một "thợ gõ công cụ" (Tool Operator) thành "Lead Product Designer & System Architect" thông qua hệ thống buồng lái 17 tệp chuẩn hóa cao độ, giải quyết triệt để 2 nguy cơ: ô nhiễm ngữ cảnh (Context Pollution) và ảo giác cú pháp đối với hệ sinh thái độc quyền Skaaa.
  - **Quyết định Kiến trúc:**
    1. **Tách 3 Rào Chắn Độc Lập (`.agent/rules/`):** Phân chia rào chắn theo đúng 3 plugin lõi (`skaaa-blocks.md` cho Design, `skaaa-data.md` cho Data Pro, `skaaa-logic.md` cho Logic Engine) giúp Sub-agents hoạt động độc lập, không ô nhiễm ngữ cảnh chéo.
    2. **Bộ Ngũ Kỹ Năng Thực Thi (5 Skaaa Implementation Skills):** 
       - `skaaa-theme-builder`: Khung sườn website, Dual-Table (`sys_organisms` + `sys_theme_templates`), Smart Virtual Wrapper tự động kẹp Header/Footer toàn site.
       - `skaaa-builder`: 6 Atomic Blocks, bảng thuộc tính chi tiết, snippet Hero chuẩn Flat DOM.
       - `skaaa-flat-db`: Kiểu cột phẳng, quan hệ Native MySQL JSON, code PHP `$wpdb` an toàn chống treo shell.
       - `skaaa-logic`: Đồ thị DAG, bảng đối chiếu Sai ➔ Đúng của SkaaaFX AST, Whitelist 7 hàm đóng.
       - `skaaa-sync`: Định danh toàn cầu `_skaaa_uuid`, pre-flight checklist 4 bước xuất bản 1-Click.
    3. **Bộ Đôi Kỹ Năng Tư Duy Nền Tảng (2 Agnostic Mindset Skills):**
       - `ui-ux-design`: Phân cấp thị giác F/Z, quy tắc phối màu 60-30-10, nhịp điệu khoảng cách 8px grid, chuyển động vi mô (Micro-interactions) và tối ưu mobile-first.
       - `system-design`: Bóc tách thực thể nghiệp vụ, chuẩn hóa quan hệ 1-N / N-N dạng JSON, kiến trúc máy trạng thái (State Machine) và quy chuẩn đặt tên toàn cục.
    4. **Workflow Điều Phối Có Điểm Dừng Phê Duyệt (`build_app.md`):** Quy trình 4 bước chuẩn App Builder kết hợp nạp kỹ năng động theo từng bước và 2 Cổng dừng kiểm soát bắt buộc của Con người (Human-In-The-Loop Approval Gates: Gate 1 duyệt Schema, Gate 2 duyệt Bố cục giao diện).
    5. **Cập nhật & Đóng gói:** Nâng phiên bản `Skaaai` lên `v1.1.1`, cập nhật tab Agent Cockpit và đóng gói tự động `skaaai-v1.1.1.zip`.

## 2026-09-23 - 🟢 Hoàn thành: Khởi tạo Kiến trúc Agent Harness & Bộ nhớ AI 1-Click cho Localhost (Skaaai v1.1.0)
- **Decision (Local Agent Harness & Memory Scaffolding Initializer - Sender Only):**
  - **Mục tiêu:** Cung cấp giải pháp triển khai "Buồng lái AI" (Agent Cockpit) tức thì cho mọi website Localhost của khách hàng khi cài plugin `Skaaai`. Chỉ với 1 click, toàn bộ giàn giáo AI (`.agent/`) và cấu trúc bộ nhớ dài hạn (`.skaaa-ai/`) được tự động deploy ra thư mục gốc `app/public/`.
  - **Khái niệm Agent Harness:** Đây là bộ khung điều phối toàn diện cho các AI Agent (Antigravity, Claude Code, Cursor, Codex...) bao gồm:
    1. **Guardrails & Rules (`.agent/rules/`):** Rào chắn chuẩn Atomic Blocks, Flat DOM, Tailwind offline, Alpine Skaaapine.
    2. **Domain Skills (`.agent/skills/`):** Kỹ năng chuyên môn sâu về hệ sinh thái (`skaaa-builder`, `skaaa-flat-db`, `skaaa-sync`).
    3. **Standard Workflows (`.agent/workflows/`):** Quy trình chuẩn hóa `/start_session`, `/end_session`, `/push_to_live`.
    4. **Design Tokens & Brand Identity (`.skaaa-ai/1-overview/design.md`):** Nguồn chân lý duy nhất (SSoT) về bảng màu, typography, khoảng cách, radius và công thức component.
    5. **Long-Term Memory (`.skaaa-ai/2-memory/`):** Sổ quyết định kiến trúc và biên bản bàn giao ca trực liên tục.
  - **Quy tắc Bảo vệ Sender-Only Tuyệt Đối:**
    - Tính năng sinh Harness & Memory là **đặc quyền duy nhất của máy Localhost (`role === 'sender'`)**.
    - Trên Live Webhost (`role === 'receiver'`), giao diện bị ẩn hoàn toàn, backend chặn cứng `WP_Error('skaaai_receiver_forbidden')`.
    - Động cơ đồng bộ bài viết (Push to Live) tuyệt đối không đẩy thư mục `.agent/` và `.skaaa-ai/` lên server trực tuyến.
  - **Triển khai Kỹ thuật:**
    - Lớp `Harness_Initializer` (`inc/class-skaaai-harness-initializer.php`) quét đệ quy thư mục mẫu `scaffold/` và triển khai qua `WP_Filesystem` chuẩn WordPress.
    - Giao diện Admin: Bổ sung Tab **"Agent Cockpit"** với nút bấm trực quan, real-time status pill và AJAX `skaaai_init_harness`.
    - Tối ưu kích thước: File `class-skaaai-admin.php` giữ mức an toàn 694 dòng (dưới trần 700 dòng).
    - Đóng gói tự động bản nâng cấp [skaaai-v1.1.0.zip](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaai-v1.1.0.zip).


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
