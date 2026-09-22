# PROJECT MANAGER: SKAAA CODE BLOCK
@status: 🟢 Done | @target_milestone: MILESTONE 1 (POST-MVP) | @last_update: 2026-06-24

> [!NOTE]
> Tài liệu này quản lý tiến độ, kiến trúc và kế hoạch triển khai của khối Gutenberg **`skaaa-code`** (Custom Code Block) thuộc plugin **Skaaa No-Code Design**. Khối này thay thế Custom HTML mặc định của WordPress, cho phép nhúng mã CSS/JS/HTML tùy biến cục bộ hoặc liên kết với *Skaaa Scripts Library*, hỗ trợ tối ưu hóa và chống nạp trùng lặp.

---

## 1. MỤC TIÊU CỐT LÕI (CORE GOALS)
- **Nhúng mã tùy biến (Inline Code Injection):** Cho phép viết trực tiếp JS, CSS, hoặc HTML tùy biến ngay trong Inspector Panel hoặc block Canvas của Gutenberg Editor.
- **Liên kết Thư viện (Library Connection):** Cho phép chọn nhanh một Script đã đăng ký từ *Skaaa Scripts Library* thông qua một ô chọn Dropdown trực quan.
- **Khử trùng lặp Server-side (Deduplication):** Tự động phát hiện và loại bỏ các script/style trùng lặp khi người dùng kéo thả nhiều block `skaaa-code` giống nhau hoặc tham chiếu chung một script trên cùng một trang.
- **Đồng bộ hóa vị trí hiển thị:** Hỗ trợ render code inline tại vị trí block, hoặc đưa lên Header (`wp_head` qua cơ chế Pre-parsing), hoặc đưa xuống Footer (`wp_footer`).

---

## 2. KIẾN TRÚC & PHÂN CHIA TRÁCH NHIỆM

### A. Giao diện Editor Gutenberg (Skaaa No-Code Design)
- Đăng ký một block Gutenberg mới có tên `skaaaaa-builder/code` (Skaaa Code Block) để tự động hỗ trợ bộ lọc thuộc tính động.
- **Attributes của block:**
  - `codeType` (string): `inline` (tự viết mã) hoặc `library` (chọn từ thư viện).
  - `libraryScriptId` (string): ID của script liên kết từ Scripts Library.
  - `inlineCode` (string): Đoạn mã JS/CSS/HTML viết trực tiếp.
  - `location` (string): `inline` (render tại chỗ), `header` (lên `<head>`), `footer` (xuống cuối trang).
- **Inspector Controls (Cài đặt Sidebar):**
  - Toggle chọn chế độ: Viết mã trực tiếp hay Chọn từ thư viện.
  - Dropdown lấy danh sách script từ REST API của Scripts Library (GET `/wp-json/skaaa-data/v1/scripts`).
  - Nút bấm và Modal Quick Save to Library cho phép lưu thẳng code inline vào thư viện Scripts.
  - Dropdown chọn vị trí nạp: `Inline`, `Header`, `Footer`.

### B. Xử lý Render Backend & Khử Trùng Lặp (Skaaa No-Code Design)
- Viết class `Skaaa_Code_Block_Queue` xử lý hàng đợi nạp script của block:
  - **Inline Render:** Nếu `location === 'inline'`, in trực tiếp mã ra ngoài frontend ngay tại vị trí block.
  - **Footer Render:** Nếu `location === 'footer'`, đẩy mã hoặc asset ID vào static queue. Hook `wp_footer` sẽ in ra và tự động loại bỏ trùng lặp (Deduplication) qua MD5 hash hoặc backend Scripts Loader.
  - **Header Render:** Đẩy mã vào hàng đợi `wp_head` để in ra trong hook `wp_head`.
  - **Deduplication Logic:** 
    - Nếu nhiều block `skaaa-code` cùng liên kết đến một `libraryScriptId`, hệ thống chỉ nạp script đó một lần duy nhất qua Action Hook.
    - Nếu nạp inline code, hệ thống băm (hash) MD5 nội dung code để chống in trùng lặp ở Header/Footer nếu người dùng sao chép y nguyên block ở nhiều nơi trên trang.

---

## 3. KẾ HOẠCH HÀNH ĐỘNG (ACTION ITEMS)

- [x] **Phase 1: Khởi Tạo Block Gutenberg (Giao Diện Editor)**
  - [x] Đăng ký block `skaaaaa-builder/code` với đầy đủ file cấu hình `block.json` và assets JS/CSS.
  - [x] Thiết kế giao diện Edit trong `edit.js` với các panels Inspector.
  - [x] Viết API endpoint GET `/skaaa-data/v1/scripts` để Gutenberg fetches danh sách script từ library làm dữ liệu cho dropdown.
  - [x] Thiết kế hiển thị preview của block trong Editor (hiển thị biểu tượng code kèm nhãn script đang liên kết hoặc dòng code preview ngắn).

- [x] **Phase 2: Render Engine & Deduplication (Xử lý Backend)**
  - [x] Thiết lập file `render.php` xử lý `render_callback` cho block.
  - [x] Viết hàng đợi static `Skaaa_Code_Block_Queue` để in code Header/Footer.
  - [x] Triển khai cơ chế khử trùng lặp (Deduplication) cho cả script liên kết thư viện và mã inline dựa trên hash MD5 nội dung.

- [x] **Phase 3: Tối Ưu Hóa Trải Nghiệm Editor (Code Formatting & UX)**
  - [x] Tinh chỉnh stylesheet trong Editor để textarea nhập code inline có màu nền tối, font chữ monospace rõ ràng.
  - [x] Tích hợp Modal Quick Save to Library cho phép lưu thẳng code JS/CSS inline vào thư viện Scripts.

- [x] **Phase 4: Kiểm Thử & Nghiệm Thu (Testing & Verification)**
  - [x] Viết tài liệu quy trình kiểm thử E2E thủ công tại `walkthrough.md`.
  - [x] Test kéo thả nhiều block `skaaa-code` dùng chung thư viện CDN để xác nhận chỉ tải 1 lần duy nhất ngoài frontend.
  - [x] Test chức năng nạp Inline, Header (Pre-parsing), và Footer hoạt động đúng vị trí DOM.

---

## 4. TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA)
1. Block Gutenberg `skaaa-code` hiển thị chính xác trong Inserter (+) của editor.
2. Inspector Sidebar hiển thị đầy đủ các tùy chọn cấu hình và lấy được danh sách script động từ Scripts Library.
3. Cơ chế khử trùng lặp hoạt động hoàn hảo: Không có bất kỳ thẻ script/style liên kết thư viện nào bị nạp 2 lần ở frontend trên cùng 1 trang.
4. Cơ chế nạp Header quét chính xác block cấu hình `header` và đưa thành công lên phần `<head>` của trang web.
