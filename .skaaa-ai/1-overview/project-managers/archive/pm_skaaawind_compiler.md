# PROJECT MANAGER: SKAAAWIND EDITOR JIT COMPILER
@status: 🟢 Done (Phases 1-8 Complete, JIT Parity Achieved) | @target_milestone: MILESTONE 2 | @last_update: 2026-09-15


> [!NOTE]
> Tài liệu này quản lý tiến độ, kiến trúc và kế hoạch triển khai của bộ biên dịch JIT client-side **`SkaaaWind JS`** thuộc plugin **Skaaa No-Code Design**. Bộ biên dịch này thay thế hoàn toàn Tailwind CDN ngoài Editor, đem lại khả năng lập trình offline 100%, khắc phục triệt để các lỗi bất đồng bộ hiển thị (Parity) và specificity giữa Editor và Frontend.

---

## 1. MỤC TIÊU CỐT LÕI (CORE GOALS)
- **Hoạt động Offline 100% (Zero-Network JIT):** Khai tử hoàn toàn liên kết tới `https://cdn.tailwindcss.com` bên trong Gutenberg Editor, đảm bảo môi trường phát triển local hoạt động mượt mà không cần internet.
- **Đồng bộ logic JIT tuyệt đối (Absolute Compiler Parity):** Port toàn bộ cấu trúc map class, regex và quy tắc phân giải màu sắc, kích thước, bo góc, khoảng cách từ `Tailwind_Compiler` và `Tailwind_Config` bên PHP sang Javascript.
- **Độ ưu tiên cao (CSS Specificity Parity):** Sử dụng chung cơ chế bọc selector `html body.skaaaaa-builder` / `.editor-styles-wrapper` kết hợp Double Classing (`.class.class`) để đè CSS Theme WP mà không dùng `!important` bừa bãi.
- **Lắng nghe phản ứng (Reactive Editor Compilation):** Theo dõi thay đổi trạng thái block tree của Gutenberg bằng `wp.data.subscribe`, tự động biên dịch lại khi người dùng gõ class mới và cập nhật tức thời stylesheet trong editor canvas.

---

## 2. KIẾN TRÚC & PHÂN CHIA TRÁCH NHIỆM

### A. Nhân biên dịch SkaaaWind (Core Compiler Module)
- Xây dựng lớp độc lập `SkaaaWindCompiler` bằng Vanilla JS (ES6 Class) không phụ thuộc WordPress để có thể đóng gói độc lập.
- **Dữ liệu cấu hình:** Nạp bảng màu Brand Colors động từ `window.skaaaEditorConfig.brandColorsJson` làm Single Source of Truth (SSOT).
- **Trình phân tích Modifier:** Hỗ trợ tách responsive (`sm:`, `md:`, `lg:`...), trạng thái chuột/focus (`hover:`, `focus:`, `group-hover:`...) và dark mode (`dark:`).
- **Bộ tạo Selector:** Sinh CSS với hai dòng rules song song cho Frontend và Editor Preview.

### B. Bộ lắng nghe và điều phối Editor (Integration Layer)
- Tích hợp bên trong file `/assets/js/skaaa-editor-helper.js`.
- **Gutenberg Listener:** Đăng ký hàm subscribe theo dõi store `core/block-editor`. Quét đệ quy toàn bộ block tree qua `getBlocks()` thu thập thuộc tính `tailwindClasses` và `className`.
- **Iframe CSS Injector:** Quét các Editor Iframes (`iframe[name="editor-canvas"]`), tự động cập nhật thẻ `<style id="skaaawind-compiled-css">` bằng CSS mới biên dịch.
- **Skaaapine Store Hook:** Tận dụng `SkaaapineStore` để lắng nghe sự thay đổi của các biến môi trường (như Dark Mode `skaaaTheme`) để thay đổi class `.dark` trên document root của iframe đồng bộ với CSS biên dịch.

---

## 3. KẾ HOẠCH HÀNH ĐỘNG (ACTION ITEMS)

- [x] **Phase 1: Porting mã nguồn JIT Compiler sang JS**
  - [x] Khởi tạo file [skaaawind.js](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaa-no-code-design/assets/js/skaaawind.js).
  - [x] Port đầy đủ config maps từ PHP `Tailwind_Config`.
  - [x] Viết hàm `resolveClass` khớp regex màu sắc, spacing, dimension, border, transition.
  - [x] Tích hợp nạp `skaaaEditorConfig.brandColorsJson` vào bộ dịch màu tùy chỉnh.

- [x] **Phase 2: Tích hợp Gutenberg Store & Subscriber**
  - [x] Đăng ký script `skaaawind.js` làm dependency của `skaaa-editor-helper` trong [class-core.php](file:///home/chiconcota/Local%20Sites/skaaa-no-code-ecosystem/app/public/wp-content/plugins/skaaa-no-code-design/inc/design-engine/class-core.php).
  - [x] Viết bộ lọc/quét đệ quy `getBlocks()` trong helper để lấy danh sách class từ Gutenberg.
  - [x] Áp dụng cơ chế hash kiểm tra chuỗi class để tránh biên dịch lặp lại vô ích (Performance optimization).

- [x] **Phase 3: Bơm Stylesheet đa ngữ cảnh (Iframe Support)**
  - [x] Loại bỏ đoạn code tiêm script CDN tailwindcss trong `skaaa-editor-helper.js`.
  - [x] Viết hàm `updateEditorStylesheets(css)` tự động tìm và cập nhật thẻ style trong cả main document lẫn các iframe editor canvas.

- [x] **Phase 4: Tích Hợp Skaaapine & Live Preview**
  - [x] Móc nối với sự kiện thay đổi của `SkaaapineStore` để bắt được các cập nhật state cục bộ.
  - [x] Kiểm thử luồng đổi màu tối (Dark Mode) hoạt động mượt mà thời gian thực trong Iframe Editor.

- [x] **Phase 5: Kiểm thử Độ tương thích & Sửa lỗi Layout (Parity Testing & Fixes)**
  - [x] Sửa lỗi warning Gutenberg Iframe chèn sai style.
  - [x] Tối ưu hóa sync hash tránh xung đột bộ gõ tiếng Việt `fcitx5-lotus`.
  - [x] Bổ sung regex arbitrary colors `bg-[#...]` ở cả PHP và JS.
  - [x] Khắc phục bóp méo Editor Canvas Layout (chỉnh `editorBaseSelector` sang `:where(.editor-styles-wrapper)`).
  - [x] Loại bỏ `!important` bằng kỹ thuật tăng độ ưu tiên CSS tự nhiên (`.editor-styles-wrapper.editor-styles-wrapper`).
  - [x] Sửa lỗi wrapper `.skaaapine-wrapper { display: contents; }` trong Editor Canvas giúp đạt tỷ lệ 100% đồng nhất giữa Editor và Frontend (`641.5px` width).
  - [x] Thêm modifier `@click.prevent` cho file `skaaa-landing-test.html` và Post 25 để triệt tiêu việc tự thêm dấu `#` vào URL.

- [x] **Phase 6: Thiết lập Single Source of Truth qua JSON (`tailwind-rules.json`)**
  - [x] Khởi tạo file cấu hình chung `tailwind-rules.json`.
  - [x] PHP JIT và JS JIT tự động load cấu hình này để phân giải class tự động, loại bỏ nợ kỹ thuật desync.

- [x] **Phase 7: Frontend Responsive Media Query Fix & Alpine Import Integration (v2.4.1)**
  - [x] Sửa lỗi khởi tạo `Tailwind_Config::$media_queries` tự động trong constructor `Tailwind_Compiler` và fallback trong `compile_classes()`.
  - [x] Khắc phục triệt để lỗi nuốt các class responsive (`md:`, `sm:`, `lg:`, `xl:`) trên Frontend khi tải trang.
  - [x] Nâng cấp `html-to-blocks.js` trích xuất thuộc tính `x-data` từ `<body>` và bọc script inline thành block `code`.
  - [x] Khắc phục lỗi phạm vi (scope shadowing) Alpine.js bằng cách nhận diện tiền tố `:` và bỏ auto-injection `x-data=""` ở block con.

- [x] **Phase 8: Mở rộng Compiler Parity (v2.4.2 - v2.4.4)**
  - [x] Tích hợp Monospace Font & FontFamily utilities (`font-mono`, `font-sans`, `font-serif`).
  - [x] Bổ sung Font Smoothing (`antialiased`, `subpixel-antialiased`).
  - [x] Bổ sung Arbitrary Box Shadow (`shadow-[...]`) và Arbitrary Font Size (`text-[...]`).
  - [x] Bổ sung Flexbox Shrink & Grow v4 (`shrink`, `shrink-0`, `grow`, `grow-0`, arbitrary `shrink-[...]`/`grow-[...]`) và đảo chiều (`flex-col-reverse`, `flex-row-reverse`, `flex-wrap-reverse`, `flex-nowrap`).
  - [x] Bổ sung Border Width Directional & Arbitrary (`border-x`, `border-y`, `border-s`, `border-e`, arbitrary `border-y-[...]`, `border-[...]`).
  - [x] Bổ sung Gradient Middle Stop (`via-[#...]`) và chuẩn hóa đầu ra biến CSS cho bộ ba gradient stops (`from`, `via`, `to`).

---

## 4. TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA)
1. Gutenberg Editor không còn gọi ra CDN ngoài `tailwindcss.com` (Kiểm tra Tab Network trong DevTools).
2. Khi thêm/sửa class Tailwind trong Inspector, khối block tương ứng trên Editor preview cập nhật style ngay lập tức.
3. Dark Mode bật/tắt qua Alpine Action trong Editor phản hồi tức thì và chính xác màu của các class `dark:bg-*`.
4. Độ ưu tiên CSS (Specificity) của style biên dịch trên Editor đủ mạnh để đè các class mặc định của theme WordPress (sử dụng Double Classing).
