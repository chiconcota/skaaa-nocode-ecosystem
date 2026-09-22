# E2E TEST WORKFLOW: SKAAAWIND EDITOR JIT COMPILER & PHASE 6 SSOT

> [!NOTE]
> Tài liệu này chi tiết hóa các kịch bản kiểm thử E2E (End-to-End Test Scenarios) từng bước dành cho **SkaaaWind Editor JIT Compiler** và **Phase 6 Single Source of Truth (`tailwind-rules.json`)** thuộc plugin **Skaaa No-Code Design v2.2.4**.

---

## 📋 DANH SÁCH TEST CASES KIỂM THỬ (CHECKLIST)

---

### 🧪 TEST CASE 1: ZERO EXTERNAL CDN NETWORK CALL (OFFLINE 100%)
> **Mục tiêu:** Xác minh Gutenberg Editor hoạt động 100% Offline không thực hiện bất kỳ lệnh gọi HTTP nào tới `cdn.tailwindcss.com`.

**Các bước thực hiện:**
1. Mở trình duyệt Chrome / Firefox, truy cập trang Quản trị WordPress (**WP Admin** -> **Posts** / **Pages**).
2. Nhấn `F12` mở Chrome DevTools -> Chuyển sang Tab **Network**.
3. Tại ô tìm kiếm/lọc (Filter), gõ từ khóa: `tailwindcss` hoặc `cdn`.
4. Tạo mới một Bài viết (Add New Post) hoặc Mở chỉnh sửa bài viết bất kỳ với Gutenberg Editor.
5. Quan sát danh sách các yêu cầu mạng (Network Requests).

**Kết quả kỳ vọng (Acceptance Criteria):**
- [x] Không có bất kỳ request nào kết nối tới domain `https://cdn.tailwindcss.com`.
- [x] Gutenberg Editor tải nhanh mượt mà và không báo lỗi đính kèm script ngoài.

---

### 🧪 TEST CASE 2: SINGLE SOURCE OF TRUTH JSON LOADING (`tailwind-rules.json`)
> **Mục tiêu:** Xác minh Backend PHP (`Tailwind_Config`) và Frontend JS (`SkaaaWindCompiler`) nạp chung bộ quy tắc từ tệp `tailwind-rules.json`.

**Các bước thực hiện:**
1. Mở trang chỉnh sửa Gutenberg Editor bất kỳ.
2. Mở Chrome DevTools (`F12`) -> Tab **Console**.
3. Nhập câu lệnh Javascript sau và ấn Enter:
   ```javascript
   console.log(window.skaaaEditorConfig.tailwindRules);
   ```
4. Kiểm tra đối tượng JSON được in ra console.
5. Kiểm tra mã nguồn PHP bằng lệnh WP-CLI hoặc xem log:
   ```bash
   php -r "require 'wp-load.php'; var_dump(count(\Skaaa\Builder\Design\Tailwind_Config::get_rules()['palette']));"
   ```

**Kết quả kỳ vọng (Acceptance Criteria):**
- [x] `window.skaaaEditorConfig.tailwindRules` trả về đối tượng JSON đầy đủ chứa các khóa: `mediaQueries`, `basicColors`, `weights`, `shadowMap`, `sizeMap`, `layoutMap`, `palette` (22 palettes color).
- [x] PHP Backend và JS Frontend dùng chung 100% dữ liệu từ tệp `tailwind-rules.json`.

---

### 🧪 TEST CASE 3: LIVE PREVIEW & INSTANT JIT COMPILATION IN EDITOR
> **Mục tiêu:** Kiểm tra khả năng biên dịch JIT thời gian thực trong Gutenberg Editor Canvas khi gõ class Tailwind mới.

**Các bước thực hiện:**
1. Trong giao diện Gutenberg Editor, chèn một block **Skaaa Container** hoặc **Skaaa Text**.
2. Nhìn sang thanh cấu hình bên phải (**Inspector Panel** -> tab **Block**).
3. Tại ô nhập liệu **Tailwind Classes**, gõ chuỗi class sau:
   `bg-indigo-600 text-white p-6 rounded-2xl shadow-xl flex items-center justify-between`
4. Quan sát trực tiếp giao diện Block trên Editor Canvas.

**Kết quả kỳ vọng (Acceptance Criteria):**
- [x] Khối block trên Editor Canvas cập nhật style lập tức (nền màu xanh tím indigo-600, chữ trắng, bo góc lớn rounded-2xl, đổ bóng shadow-xl).
- [x] Thẻ `<style id="skaaawind-compiled-css">` bên trong Editor Canvas Iframe được cập nhật chứa mã CSS tương ứng.

---

### 🧪 TEST CASE 4: ARBITRARY COLORS & DARK MODE PARITY
> **Mục tiêu:** Kiểm tra khả năng giải mã mã màu tự chọn `bg-[#030712]` và chế độ Dark Mode `dark:bg-slate-900` đồng bộ giữa Editor và Frontend.

**Các bước thực hiện:**
1. Chọn một block Skaaa, gõ các class:
   `bg-[#030712] text-[#3b82f6] dark:bg-slate-900 dark:text-white`
2. Bấm nút **Toggle Dark Mode** (hoặc chuyển đổi class `.dark` trên document root).
3. Lưu bài viết (Save / Update Post).
4. Mở bài viết ngoài **Frontend** (Trang người dùng).
5. Dùng DevTools (Inspect Element) so sánh mã màu tính toán (Computed Style) giữa Editor Canvas và Frontend.

**Kết quả kỳ vọng (Acceptance Criteria):**
- [x] Mã màu `bg-[#030712]` xuất ra CSS exact value `background-color: rgb(3, 7, 18);` ở cả Editor và Frontend.
- [x] Chế độ Dark Mode phản hồi tức thì khi bật/tắt state, chuyển màu sang `slate-900` không bị chớp hay vỡ layout.

---

### 🧪 TEST CASE 5: LAYOUT PARITY & ZERO !IMPORTANT SPECIFICITY
> **Mục tiêu:** Đảm bảo độ rộng Grid, Flexbox và bọc Skaaapine Wrapper hiển thị khớp 100% tuyệt đối giữa Editor và Frontend.

**Các bước thực hiện:**
1. Chèn block **Skaaa Container** với cấu hình grid 12 cột: `grid grid-cols-12 gap-4`.
2. Chèn 2 block con bên trong:
   - Block 1: `col-span-7 bg-slate-100 p-4`
   - Block 2: `col-span-5 bg-slate-200 p-4`
3. Mở DevTools đo độ rộng pixel (Width) của Block 1 và Block 2 trên Editor Canvas.
4. Mở ngoài Frontend và đo độ rộng pixel tương ứng.

**Kết quả kỳ vọng (Acceptance Criteria):**
- [x] Độ rộng của phần tử 7 cột và 5 cột khớp 100% giữa Editor và Frontend (tỷ lệ chuẩn `641.5px` tương ứng với container viewport).
- [x] Thẻ bọc trung gian `.skaaapine-wrapper` có thuộc tính `display: contents;` giúp 2 block con trực tiếp làm con của `.grid`.
- [x] CSS Override không dùng cờ `!important` bừa bãi mà dùng Specificity Scope (`.editor-styles-wrapper.editor-styles-wrapper`).

---

### 🧪 TEST CASE 6: DESIGN TOKENS / THEME OPTIONS PRIORITY PROTECTION
> **Mục tiêu:** Xác minh các biến màu tùy chỉnh từ Theme Options (`tokens.json`) luôn được ưu tiên tra cứu trước từ điển `tailwind-rules.json`.

**Các bước thực hiện:**
1. Truy cập **WP Admin** -> **Skaaa Builder** -> **Design Tokens** (Theme Options).
2. Kiểm tra các mã màu Brand: `primary` (`#3B82F6`), `secondary` (`#10B981`), `surface` (`#FFFFFF`).
3. Mở bài viết Gutenberg Editor, nhập class:
   `bg-primary text-surface border-secondary`
4. Kiểm tra Computed Styles trong DevTools.

**Kết quả kỳ vọng (Acceptance Criteria):**
- [x] Class `bg-primary` phân giải chính xác mã màu `#3B82F6` lấy từ Theme Options (`tokens.json`).
- [x] Compiler gọi `resolveCustomColor()` tra cứu thành công trước khi fallback về `tailwind-rules.json`.

---

## 🛠 HƯỚNG DẪN XỬ LÝ LỖI (TROUBLESHOOTING GUIDE)

| Hiện tượng | Nguyên nhân có thể | Cách xử lý |
| :--- | :--- | :--- |
| **Gutenberg không nhận `tailwindRules`** | Trình duyệt cache file `skaaa-editor-helper.js` hoặc PHP opcache chưa reset. | Xóa cache trình duyệt (Ctrl + Shift + R) hoặc chạy `php -r "opcache_reset();"` nếu có bật Opcache. |
| **Skaaawind không biên dịch class mới** | Hash class chưa thay đổi hoặc Iframe document chưa sẵn sàng. | Gõ lại chuỗi class hoặc bấm F5 reload lại trang Editor. |
| **Layout Editor bị co chữ/dốc chữ** | Class wrapper Gutenberg bị thiếu `:where(.editor-styles-wrapper)`. | Kiểm tra file `class-tailwind-config.php` đã nạp đúng reset CSS `editorBaseSelector` chưa. |
