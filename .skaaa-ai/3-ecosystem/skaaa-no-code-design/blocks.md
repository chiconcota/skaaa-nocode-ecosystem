# MODULE: BLOCKS SYSTEM
> **Path:** `skaaaaa-builder-core/blocks/`

## 1. Nhiệm vụ (Responsibility)
Hệ thống Blocks (Gutenberg) cốt lõi của Skaaa Builder. Cung cấp các atomic block để xây dựng giao diện.

## 2. Blocks đã triển khai (Cập nhật 2026-03-26)

### A. Quy tắc chung (Global Rules)
- **Single Attribute Model (2026-03-26):** `tailwindClasses` là **Single Source of Truth** duy nhất. `className` luôn được set rỗng. `TailwindPanel` callback đơn giản: `(allClasses) => setAttributes({ tailwindClasses: allClasses, className: '' })`.
- **useInnerBlocksProps (2026-03-26):** Các block có InnerBlocks (Container, List, List-Item) sử dụng `useInnerBlocksProps` thay vì `<InnerBlocks>` component. API này merge inner blocks trực tiếp vào wrapper, loại bỏ intermediate divs (`block-editor-inner-blocks`, `block-editor-block-list__layout`) gây hỏng positioning.
- **Inline Positioning (Editor, 2026-03-26):** Container block detect các positioning classes (absolute/relative/fixed/sticky, inset-*, top/right/bottom/left-*, z-*) và convert thành inline styles trong `useBlockProps`. Inline styles có highest CSS priority, không có stylesheet nào override được.
- **Flat DOM Principle:** Đối với các block cấu trúc, tuyệt đối KHÔNG thêm div bọc ngoài không cần thiết. `get_block_wrapper_attributes()` phải được áp dụng lên thẻ ROOT.
- **Robust Fallback:** Tất cả file `render.php` sử dụng logic: `! empty( $attributes['tailwindClasses'] ) ? $attributes['tailwindClasses'] : ( $attributes['className'] ?? '' )`.

### B. Danh sách Block

#### 1. Skaaa Text (`skaaaaa-builder/text`)
- **Attributes:** `content`, `tagName`, `tailwindClasses`, `customStyle`, `dynamic`, `logic`.
- **Render:** Áp dụng trực tiếp vào thẻ root (`p`, `h1`...).

#### 2. Skaaa Container (`skaaaaa-builder/container`)
- **Attributes:** `tagName`, `tailwindClasses`, `customStyle`, `logic`, `templateLock`.
- **Render:** Sử dụng `get_block_wrapper_attributes()` trên thẻ wrapper chính.
- **Editor (2026-03-26):** Sử dụng `useInnerBlocksProps` + `getPositionStyles()` helper để inject positioning inline styles. Đây là block đầu tiên và quan trọng nhất áp dụng pattern này.
- **Nesting Whitelist (2026-06-25):** Bổ sung `skaaaaa-builder/code` vào danh sách `ALLOWED_BLOCKS` cho phép người dùng kéo thả và chèn lồng block viết code/canvas trực tiếp vào bên trong block container để dựng giao diện tùy chỉnh.
- **Molecule Base (2026-04-16):** Trở thành Root Element cho hệ Skaaa Molecules. Hỗ trợ thuộc tính `templateLock` (như 'all') truyền vào `useInnerBlocksProps` để Khóa Xương Sống cấu trúc đối với các Variation phức tạp (Multi-Step Form, Tabs, Accordion) nhằm tránh gãy Layout khi người dùng thao tác. Thẻ `tagName` có thể linh hoạt chuyển hóa (vd: `form` cho Quiz/Wizard).

#### 3. Skaaa Image (`skaaaaa-builder/image`)
- **Attributes:** `url`, `alt`, `id`, `tailwindClasses`, `customStyle`, `dynamic`.
- **Isolation Fix (2026-03-21):** Di chuyển `get_block_wrapper_attributes()` từ lớp vỏ `div` vào thẳng thẻ `<img>`. Giúp cô lập hoàn toàn hiệu ứng hover/rounded lên ảnh.

#### 4. Skaaa Icon (`skaaaaa-builder/icon`)
- **Attributes:** `iconName`, `tailwindClasses`, `customStyle`.
- **Render (2026-03-27):** Áp dụng vào thẻ `<span>` với class `material-symbols-outlined`. Tích hợp thư viện 4207 icons (Modal Search UI + Quick Grid).

#### 4.1 Skaaa SVG (`skaaaaa-builder/svg`)
- **Attributes:** `svgCode`, `tailwindClasses`, `className`, `customStyle`, `logic`.
- **Render (2026-09-09 - v2.4.0):** 
  - Khối hiển thị vector SVG inline thuần Flat DOM (áp dụng `get_block_wrapper_attributes()` trực tiếp lên thẻ `<svg>`).
  - Hỗ trợ live preview và Placeholder dán code SVG nhanh trong Gutenberg Editor, thanh `TailwindPanel` điều khiển kích thước, màu sắc (`text-*`, `fill-*`, `stroke-*`), và Panel xem/sửa mã XML trực tiếp.
  - Tích hợp sâu vào `html2tailwind` (`html-to-blocks.js`): Tự động chuyển đổi các thẻ `<svg>` thành block `skaaaaa-builder/svg`, bóc tách class Tailwind và promote các phần tử cha (Button, Link, Text) thành Container để giữ nguyên vẹn cấu trúc DOM.


#### 5. Skaaa Button (`skaaaaa-builder/button`)
- **Attributes:** `text`, `url`, `tagName`, `tailwindClasses`, `customStyle`, `dynamic`, `logic`.
- **Action & Icon (2026-04-27):** Rút gọn `actionType` còn `link`, `submit`, và `logic_api` (đã hợp nhất tính năng gọi Popup vào Logic Workflow thông qua Client Response Node). Nhóm Icon vẫn giữ nguyên.
- **Alpine.js Local Event (2026-04-28):** Khi người dùng muốn kích hoạt sự kiện cục bộ thông qua Alpine.store (ví dụ: `@click="$store.appState.openModal()"`), nút bấm BẮT BUỘC phải được gắn thêm thuộc tính `x-data` để Alpine v3 nhận diện là một component và parse directives.
- **Render:** Ép kiểu Semantic Tag (`<a>` hoặc `<button>`) phụ thuộc thời gian thực vào biến `actionType`. Hỗ trợ tự động tiêm class `skaaa-action-[workflow_id]` nếu nút sử dụng hành động Logic Workflow, mở đường kết nối ngầm với Logic Engine Event Bus.
- **Isolation Fix (2026-03-21):** Di chuyển `get_block_wrapper_attributes()` từ lớp vỏ `div` vào thẻ `<a>` hoặc `<button>`. Xóa bỏ lỗi "Red Box" khi hover tràn khung.
- **CSS Specificity (2026-03-27):** Gỡ bỏ class `wp-element-button` mang nợ CSS mặc định của theme WP (như nền đen). Tự tạo file `index.css` áp dụng lệnh `a.skaaa-button-block { text-decoration: none; }` có độ ưu tiên `0,1,1` để ép tắt dòng gạch chân mặc định của WP, nhưng khéo léo dùng bộ chọn để cho phép Tailwind override hoàn hảo các class dạng `underline` và `hover:underline`.

#### 6. Skaaa Video (`skaaaaa-builder/video`)
- **Attributes:** `url`, `videoType`, `aspectRatio`, `tailwindClasses`, `customStyle`, `dynamic`, `logic`.
- **Render:** Tailwind classes được áp dụng vào video wrapper để xử lý aspect-ratio và corner clipping (`isolate`).
- **2026-03-22 Fix:** Khắc phục mismatch attribute `videoUrl`/`url` trong `render.php` giúp URL load đúng nội dung và không bật video placeholder khi có nguồn phát.

#### 7. Skaaa List (`skaaaaa-builder/list`) & Skaaa List Item (`skaaaaa-builder/list-item`)
- **Attributes:** `listType` (UL/OL), `tailwindClasses`, `customStyle`.
- **Render:** Flat DOM, hỗ trợ đầy đủ `flex`/`grid` layout trên thẻ `<ul>`/`<li>`.
- **Editor (2026-03-26):** Cả hai đều sử dụng `useInnerBlocksProps` để loại bỏ intermediate wrapper divs.

#### 8. Skaaa Form (`skaaaaa-builder/form`)
- **Attributes:** `formId`, `tailwindClasses`, `customStyle`, `actionType`...
- **Render:** Flat DOM thẻ `<form>`, sử dụng `InnerBlocks.Content` trong `save()` để render khối con, đảm bảo giao diện Frontend/Editor đồng nhất (2026-04-05).
- **Trạng thái:** Không bọc form mặc định, tuân thủ nguyên lý `ALLOW_BLOCKS` cho phép nhúng Container làm layout grid.
- **Pivot (2026-04-14):** Form được giao nhiệm vụ tự động hóa thu thập dữ liệu (payload theo thẻ `name`) và bắn AJAX về Engine thay vì ép người dùng Nocode viết mã JS bằng AlpineJS `x-on:click`.
- **Alpine Form Integration (2026-04-23):** Hoàn thiện liên kết hai chiều giữa Cấu trúc Khối (HTML) và Logic Backend (Skaaa Logic Engine) thông qua Controller `skaaaForm('workflow_id')`. Các trường State UI (`fields.*`, `status.*`) được chuẩn hóa toàn bộ, cho phép Form tiếp nhận, validation, và submit dữ liệu siêu mượt mà không cần code JS thêm. Script Dependency của `alpine.min.js` cũng được fix đảm bảo nó luôn load sau `skaaa-frontend.js` để Controller được sẵn sàng trước khi DOM quét Alpine State.
- **Form Reset Logic on Success (2026-05-22):** Nâng cấp Controller `skaaaForm` trong `skaaa-frontend.js` để tự động xác định trạng thái form là Update View (`isUpdate` đánh giá là `true`) khi `actionId` bắt đầu bằng `update_`, hoặc form fields có `id`, hoặc `Alpine.store('skaaaPortal').currentData.id` hợp lệ. Logic này ngăn việc form bị reset trắng sau khi submit update thành công (giữ nguyên dữ liệu trên Detail View của Portal), trong khi vẫn thực hiện reset form rỗng bình thường trên Create View (Insert).

#### 9. Skaaa Input (`skaaaaa-builder/input`) & Skaaa Select (`skaaaaa-builder/select`)
- **Attributes:** `fieldName`, `inputType`, `placeholder`, `isRequired`, `tailwindClasses`, `customStyle`.
- **Styling Architecture (2026-04-05):** Mọi viền, nền, outline của WP Admin bị gỡ bỏ ở Backend. Hệ thống áp dụng Global Tailwind Preflight Form Reset (`border-color: #e5e7eb;` và `appearance: none;`) trực tiếp thông qua JIT Code (Class `class-tailwind-config.php`), đảm bảo UI 100% tàng hình và giống hệt như Front-End nếu không có Tailwind classes nào.
- **Select Dynamic Binding (2026-04-23):** Tích hợp React Inspector UI cho block Select giúp thay thế việc gõ thủ công cú pháp Mustache. Dropdown tự động hiển thị các cột kiểu `select/radio/checkbox` có khai báo thuộc tính `options` từ `skaaa_data_dictionary` của `Skaaa Data Pro` (gỡ bỏ check `__table_info` nghiêm ngặt). Editor bảo vệ UX bằng cách loại bỏ các trường Số/Chữ không thể sinh danh sách tuỳ chọn.

#### 10. Skaaa Symbols & Molecules (Phase 4 Foundation)
- **Skaaa Symbols (Reusable Organisms):** Khối `skaaaaa-builder/organism-ref` hoạt động như một Ghost Block (Khối tham chiếu). Nó chỉ chứa duy nhất một thuộc tính `organismId`. Tại Editor, cơ chế Zero-Query được áp dụng cực trị bằng cách load trực tiếp `window.skaaaOrganismsCache` (qua `wp_localize_script`) để truyền dữ liệu tĩnh tức thời (0ms delay) vào bảng Inspector Controls. Quá trình render thực tế ở Editor dùng `ServerSideRender` (Đã fix 2026-04-21: Cập nhật `echo` output buffering cho `render.php` do behavior của WP 6.1+). Quá trình render ở Frontend bốc cấu trúc gốc từ bảng phẳng `skaaa_data_sys_organisms` thay vì nhân bản HTML phình to. Đảm bảo Single Source of Truth tuyệt đối. Cơ chế **Data Injection** (2026-04-22) tự động tiêm Symbol mới lưu vào `window.skaaaOrganismsCache` bằng Javascript giúp tính năng "Phân rã (Detach)" hoặc hiển thị Tên Symbol ở Dropdown hoạt động ngay lập tức (Real-time SPA) mà không cần tải lại trang.
- **Skaaa Molecules (UI Components):** Cấu trúc Khóa Xương Sống (`templateLock`). Áp dụng kết hợp Alpine.js nhằm tạo ra các UI tĩnh có tương tác mượt mà như:
  - **Tabs:** Quản lý state hiển thị qua `x-data="{ activeTab: 1 }"`.
  - **Accordion:** Quản lý collapse bằng chiều cao biến thiên (`max-height`).
  - **Logic Modal:** Modal Popup độc lập xử lý state bật / tắt với hiệu ứng `x-transition`.

#### 11. Skaaa Loop (`skaaaaa-builder/loop`)
- **Role:** Vòng lặp hiển thị dữ liệu động (Data Loop) với Zero N+1 Queries. Chuyển đổi từ kiến trúc "Ghost Block" sang "Structural Container" (2026-05-04) để trực tiếp đóng vai trò như một wrapper dàn trang Flex/Grid.
- **Backend Architecture (2026-04-22):** Sử dụng cơ chế Bulk Loading thông qua `Organisms_API::get_bulk_html` để load hàng loạt template HTML của Organism. Áp dụng Hydration Engine tốc độ cao bằng `preg_replace_callback` để đắp Data (Flat Tables) vào cặp thẻ Mustache `{{key}}`. Không được query SQL bên trong vòng lặp.
- **Condition Matching (2026-05-04):** Sử dụng `SkaaaLogicEngine::evaluate` (SkaaaFX) để quét và quyết định Slot hiển thị. Chấp nhận các giá trị Truthy (`1`, `'1'`, `true`, `'true'`) để đánh giá biểu thức logic chính xác hơn. Hỗ trợ biến hệ thống `$item` để điều kiện hóa từng bản ghi trong vòng lặp.
- **Tailwind Integration (2026-05-04):** Bổ sung thuộc tính `tailwindClasses`, tích hợp `<TailwindPanel>` vào Sidebar và ép Editor/Frontend cùng dùng chung một lớp bọc thẻ `<div class="wp-block-skaaaaa-builder-loop skaaa-loop-wrapper [TAILWIND]">` duy nhất để sửa dứt điểm lỗi mất Layout khi kéo thả ngang dọc. Đã xóa toàn bộ HTML Dump và Log rác ở Production để tối ưu hiệu năng.
- **Phân giải hiển thị trường Quan Hệ - Relation Fields (2026-05-22):** Trong tệp `render.php` của vòng lặp loop, hệ thống tự động kiểm tra và nhận diện các cột dữ liệu kiểu quan hệ được lưu trữ dưới dạng mảng JSON (sau khi giải phóng slashes `stripslashes`). Nó sẽ trích xuất tất cả các `label` của đối tượng liên kết và nối chúng bằng dấu phẩy để hiển thị chính xác tên nhãn ngoài List View thay vì in chuỗi JSON thô.
- **Phân tách Bảng Nghiệp vụ & Bảng Hệ thống (2026-08-21 - v2.3.2):** Trong Inspector Controls của khối `Skaaa Loop` và `Skaaa Select`, dropdown *Source Table* mặc định chỉ hiển thị các bảng dữ liệu nghiệp vụ (`📦 [App Data]`) do người dùng tạo. Bổ sung `ToggleControl` *"Show System Tables (Internal)"* cho phép Super Admin chủ động hiển thị các bảng hệ thống (`⚙️ [System] skaaa_data_sys_*`) khi cần thiết. Cấu hình Dynamic Block Markup được chuẩn hóa sạch thẻ tĩnh thô, đạt 100% Block Validation trong Gutenberg Editor.
- **Quy hoạch & Triển khai Universal Query Loop & Alpine-AJAX Pagination (2026-10-27 - v2.6.0):** Xem chi tiết tại [pm_alpine_ajax_integration.md](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/.skaaa-ai/1-overview/project-managers/pm_alpine_ajax_integration.md).
  - Mở rộng nguồn dữ liệu hỗ trợ `WordPress Posts` (`sourceType="wp_posts"`), tự động gọi `WP_Query` lấy đầy đủ metadata bài viết (`title`, `permalink`, `date`, `excerpt`, `content`, `author`, `featured_image`).
  - Hỗ trợ Dynamic Binding chuẩn cú pháp `[post.title]`, `[post.featured_image]` và fallback SVG sắc nét khi bài viết chưa có thumbnail.
  - Tích hợp cụm phân trang Flat DOM nguyên tử `<nav class="skaaa-pagination col-span-full ...">` mang thuộc tính `x-target.push="{target_id}"` của thư viện Alpine-AJAX offline.
  - Đạt khả năng chuyển trang tức thì 0ms (In-place Swap), không reload toàn trang, tự động đồng bộ URL trình duyệt và hỗ trợ 100% nút Back/Forward mà vẫn bảo đảm 100% SEO Server-Side Rendering. Loại bỏ hoàn toàn sự phụ thuộc vào `core/query` của WordPress Core.


## 3. Cấu trúc thư mục
- `init.php`: Đăng ký các block.
- `[block-name]/`: Thư mục chứa code của block.
  - `block.json`: Metadata (Bật `supports.className: true` để đồng bộ chuẩn Gutenberg).
  - `index.js`: Editor logic (Dùng `tailwindClasses` làm primary, Sync sang `className`).
  - `render.php`: Frontend logic (Hỗ trợ "Non-empty Fallback" & Flat DOM).

## 4. Ghi chú phát triển quan trọng
- **Inner Element Isolation:** Với các block atomic (Button, Image), luôn ưu tiên render class Tailwind vào thẻ nội dung bên trong thay vì lớp vỏ Gutenberg bên ngoài.
- **Build Sync:** Bắt buộc chạy `npm run sync` sau mỗi lần sửa logic PHP trong `src/` để cập nhật file sang `build/`.
- **TailwindPanel:** Luôn truyền `tailwindClasses` vào component `TailwindPanel`. Callback chỉ nhận 1 param (allClasses).
- **useInnerBlocksProps Pattern (2026-03-26):** Khi block cần InnerBlocks, sử dụng `useInnerBlocksProps(blockProps, { allowedBlocks, ... })` thay vì `<InnerBlocks>`. Pattern: `<Tag {...innerBlocksProps} />` thay vì `<Tag {...blockProps}><InnerBlocks .../></Tag>`.

## 5. Ghi chú triển khai
- **Attribute Migration (2026-03-21):** Chuyển đổi toàn bộ Block sang sử dụng `tailwindClasses` thay vì `className`. Điều này ngăn chặn Gutenberg tự ý can thiệp vào CSS của block thông qua thẻ wrapper.
- **Atomic Button Reset (2026-03-21):** Tự động inject CSS Reset cho thẻ `button` trong vùng `.skaaaaa-builder` để xóa border và background mặc định của trình duyệt.
- **CSS Reset Scoping (2026-03-28 Critical):** TẤT CẢ JIT resets (block-gap, button, link) phải scope bằng `[class*='wp-block-skaaaaa-builder']` — KHÔNG ĐƯỢC dùng `body.skaaaaa-builder` trực tiếp vì sẽ phá layout blog/archive page dùng theme mặc định.
- **Link Reset (2026-03-28):** `a { text-decoration: none; color: inherit; }` trong scope Skaaa blocks để chống WordPress defau- **Hybrid Source Architecture:** Sử dụng PHP Core để pre-generate CSS cho brand colors.
- **Inline Style Support:** Thuộc tính `customStyle` cho phép bảo toàn và hiển thị các style "cứng" (background-image).
- **Locale Independence:** Sử dụng `number_format( $val, 3, '.', '' )` trong PHP.
- **Style Scanning:** `Style_Manager` ưu tiên quét từ `tailwindClasses`, sau đó là `className`, `customStyle` và nội dung HTML.
- **🔧 Planned Refactor:** Tách file compiler (>500 dòng) thành: `compiler` + `config` + `color-registry`.

## 6. Kiến trúc Link Engine & Dynamic Data (2026-05-08)
- **Decoupled Link Strategy:** Loại bỏ onclick redirect, chuyển qua chuẩn `<a>` tag rendering từ Server-side. Đảm bảo SEO 100%.
- **SkaaaLinkControl (React Component):** Component lõi dùng cho Inspector Panel. Cho phép nhập Text Link tĩnh hoặc gọi dữ liệu Động từ nguồn (System, Loop).
- **Inline Dynamic Link (RichText):** Đăng ký custom format `skaaa/dynamic-link` lên thanh Toolbar của văn bản (skaaa-text, skaaa-list-item) để chèn liên kết động ở mức ký tự/từ ngữ. Sử dụng lại chung `SkaaaLinkControl` UI Popover.
- **Dynamic_Data (PHP Utility):** Xử lý chuyển đổi link attribute trên Server (trong các file `render.php`). Giải quyết 3 trường hợp tĩnh và động:
  - `static`: Xuất trực tiếp URL.
  - `system`: Xử lý thành hàm hệ thống WP (VD: `home_url()`, `get_permalink()`).
  - `loop`: Xuất ra format Mustache `{{key}}` để Engine `Skaaa Loop` tiến hành preg_replace tự động. Tương thích 100% với Hydration Pipeline.
  - **Inline Parser:** Sử dụng Regex (`resolve_inline_links`) tại SSR để quét chuỗi HTML, lấy URL/Mustache tiêm vào thuộc tính `href` của thẻ `<a>` và tự động loại bỏ các data attribute dư thừa (`data-dynamic-source`, v.v.).
- **Quy tắc Frontend Flat DOM:** Dữ liệu URL động phải được giải quyết và tiêm vào thuộc tính `href` của thẻ gốc trước khi buffer được trả về. Mọi hook liên quan phải chạy ở Phase Render.

#### 12. Skaaa Rich Text (`skaaaaa-builder/form-rich-text`)
- **Attributes:** `field`, `label`, `tailwindClasses`, `className`.
- **Render (v1.0.0 - 2026-05-21):** 
  - Khối tự trị quản lý toàn bộ cấu trúc: Tiêu đề H3 (`text-lg font-bold text-slate-800`), nút **Open Design Editor** kích hoạt AlpineJS `@click="openDesigner()"`, và trình soạn thảo TinyMCE.
  - Nhúng CSS scoped trực tiếp vào khối để tùy biến và định dạng toàn diện giao diện `.wp-editor-wrap` của WordPress TinyMCE. Thay thế thanh tabs mặc định thành Segmented Pills Controls bóng bẩy, hỗ trợ hover, active states rõ rệt, cải thiện UX/UI.
  - **Shadow Scratchpad API Routing Integration:** Sử dụng helper `getBuilderRestUrl()` để giải quyết và định tuyến chính xác URL REST API cho việc tạo/hủy Scratchpad. Thay thế hoàn toàn thuật toán `.replace('skaaa-logic', 'skaaaaa-builder')` dễ gây lỗi 404 nếu permalink của WordPress sử dụng chế độ Plain (`?rest_route=`). Helper tự động xử lý và ánh xạ:
    - Pretty URL: `/wp-json/skaaa-logic/v1/submit` -> `/wp-json/skaaaaa-builder/v1`
    - Plain URL: `?rest_route=%2Fskaaa-logic%2Fv1%2Fsubmit` -> `?rest_route=%2Fskaaaaa-builder%2Fv1`
  - **CSS Modal Scoped & Alpine Compatibility:** Thư viện CSS nội bộ sử dụng scope để tránh tràn style và loại bỏ thuộc tính `display: flex !important` khỏi lớp phủ `.skaaa-designer-modal-overlay` và `.skaaa-designer-modal-loader` nhằm đảm bảo khả năng tương thích 100% với thuộc tính hiển thị `x-show` của AlpineJS (tránh việc modal không thể ẩn đi khi `isOpen` chuyển về `false`).
  - **Beforeunload Prompt Suppression (Fix Unsaved Warning Dialog):** Tích hợp lệnh `wp.data.dispatch('core').clearEntityRecordEdits('postType', type, id)` vào cuối tiến trình đóng thiết kế (`closeDesigner`). Sau khi nội dung được lưu trữ an toàn vào Alpine field, Redux store của Gutenberg sẽ được đánh dấu sạch (clean) để giải phóng Iframe ngay lập tức mà không kích hoạt thông báo cảnh báo thay đổi chưa được lưu của trình duyệt.

#### 13. Skaaa Code (`skaaaaa-builder/code`)
- **Attributes:** `codeType` ('inline' / 'library'), `libraryScriptId`, `inlineCode`, `location` ('inline' / 'header' / 'footer').
- **Render (v1.2.3 - 2026-06-25):** 
  - Chỉ đăng ký block khi plugin **Skaaa Data Pro** đang hoạt động (quét bằng `Dependency_Manager::is_data_pro_active()`).
  - Hỗ trợ viết mã HTML/CSS/JS inline trực tiếp tại chỗ hoặc đẩy lên hàng đợi `Skaaa_Code_Block_Queue` để in không trùng lặp (dùng băm MD5 làm key) ở Head/Footer.
  - **Pre-scan & wp_head lifecycle fix**: Đăng ký hook `wp_head` độ ưu tiên 1 để tự động quét sớm toàn bộ blocks `skaaaaa-builder/code` trong `post_content` của bài viết và các active organisms (đệ quy cả inner blocks) trước khi HTML bắt đầu xuất ra, giải quyết triệt để lỗi mất CSS/JS do lỡ nhịp tải trang.
  - **Skaaa Script HTML Comment Debug**: Tự động in thêm dòng chú thích HTML debug `<!-- Skaaa Script: [script_id] -->` trước khi xuất các script thư viện, giúp dễ dàng kiểm tra tính khử trùng lặp và xác minh E2E.
  - Liên kết decoupled với **Skaaa Scripts Library** bằng cách gọi action hook `skaaa_enqueue_custom_script` thay vì gọi trực tiếp class.
  - Tích hợp giao diện dark editor monospace cho code inline và modal Quick Save trực tiếp vào thư viện trong Editor.
  - **Nesting Support (2026-06-25):** Được phép chèn lồng bên trong block `Skaaa Container` để dựng giao diện phức hợp (ví dụ: bọc thẻ canvas biểu đồ trong khung kính glassmorphism).

#### 14. Skaaa Loop & Universal Query Loop (`skaaaaa-builder/loop`)
- **Attributes:** `sourceType` ('flat_table' | 'wp_posts' | 'pagination'), `sourceTable`, `postType`, `category`, `limit`, `slots`, `paginationTargetId`, `paginationRange`, `includePrevNext`, `inheritQuery`, `"anchor": true`.
- **Render Architecture (v2.5.9 - 2026-10-29 - Modular Divide & Conquer):**
  - **Tách 3 Module Chuyên Trách (Zero File Bloat):** Bóc tách hoàn toàn tệp `render.php` khổng lồ thành 3 thành phần độc lập:
    1. `class-loop-compiler.php` (121 lines): Chuyên trách tải bulk HTML và biên dịch Organisms thành khuôn mẫu Mustache.
    2. `class-loop-query.php` (361 lines): Chuyên trách chuẩn bị dữ liệu (Universal Search/Filter cho `wp_posts`, dải số trang động cho `pagination`, và MySQL Data Fetcher cho `flat_table`).
    3. `render.php` (269 lines): Đóng vai trò Orchestrator điều phối và Hydration dữ liệu vào khuôn.
  - **Mô Hình Phân Trang Độc Lập (Decoupled Reactive Loop):** Nguồn `sourceType: "pagination"` tái sử dụng 100% cỗ máy lặp, liên kết qua `paginationTargetId`, điều phối 3 conditional slots: `PageBtnActive` (`item.is_active`), `PageBtnDots` (`item.is_dots`), và `PageBtnNormal` (`default`).
  - **Multi-Target 0ms Swap:** Tráo đổi đồng thời cả lưới bài viết lẫn thanh phân trang qua Alpine-AJAX `x-target.push="blog-posts-grid blog-pagination"`.
  - **Flat DOM Hydration:** Tự động thế chỗ sạch sẽ các token `{{post.title}}`, `[post.title]`, `{{page.number}}`, `{{page.url}}` và SVG fallback thumbnail sắc nét (0ms).
  - **Safe Empty State Container:** Luôn bảo toàn thẻ bọc mang ID đích để Alpine-AJAX không bị gãy target khi tráo đổi dữ liệu rỗng.
