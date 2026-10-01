---
name: assembly-delivery
description: Quy trình lắp ráp sản phẩm theo chuẩn Atomic Design, kiểm định 3 lớp chống nghiệm thu ảo & bàn giao trực quan (/assembly-delivery)
---

# QUY TRÌNH LẮP RÁP ATOMIC, KIỂM ĐỊNH 3 LỚP & BÀN GIAO (/assembly-delivery)
@phòng_ban: Design, Developer & Quality Control (QC) | @mode: Atomic Assembly & Rigorous QC
@trigger: Kích hoạt ngay sau khi Giám Đốc duyệt Brief từ /client-intake, hoặc gõ /assembly-delivery

> **THIẾT QUÂN LUẬT BÀN GIAO:** Nghiêm cấm tuyệt đối lối làm việc "bàn giao mù" (báo cáo PASSED 100% bằng miệng trong khi thực tế giao diện vỡ tan nát). Mọi sản phẩm xuất xưởng bắt buộc phải tuân thủ chuẩn lắp ráp Atomic tái sử dụng và vượt qua Cổng Kiểm Định 3 Lớp thực tế.

---

## BƯỚC 1: TIẾP NHẬN HỒ SƠ DỰ ÁN & DESIGN TOKENS
1. Mở tệp `.skaaa-ai/1-overview/client-brief.md` *(TUYỆT ĐỐI KHÔNG đọc/tạo folder lạ)* để trích xuất:
   - **Design Tokens:** Bảng màu chủ đạo, Canvas dark/light, Font sans/mono, Logo vector SVG, Avatar chân dung thật.
   - **Định vị & USP:** Manifesto, thông điệp cốt lõi, danh sách phân hệ.
   - **Data Pro Schema:** Tên các bảng phẳng `wp_skaaa_data_*`.

---

## BƯỚC 2: THỰC THI LẮP RÁP THEO CHUẨN ATOMIC & DATABASE-FIRST
1. **Nạp Design Tokens vào CSDL & Compile CSS (BẮT BUỘC TRƯỚC TIÊN):**
   - Chạy lệnh CLI để lưu bảng màu, typography và logo vào bảng phẳng `wp_skaaa_data_sys_presets`:
     ```bash
     php .agent/harness/db-tool.php --set-tokens='{"brand":{"logourl":"..."},"colors":{...},"darkColors":{...}}'
     ```
   - Lệnh tự động kích hoạt `Design_Tokens_Compiler` biên dịch cache `tokens.json` và biến CSS toàn cục.
   - Đồng bộ bảng đối soát vào `.skaaa-ai/1-overview/brand-guidelines.md`.
2. **Khởi tạo CSDL phẳng MySQL (Nếu có dữ liệu động):**
   - Developer dùng mẫu PHP `$wpdb` trong `.agent/skills/developer-blocks/SKILL.md` để khởi tạo bảng phẳng và nạp dữ liệu mẫu (Seed Data) bằng `db-tool.php`.
3. **Đóng gói Cấu Kiện Tái Sử Dụng & Kích Hoạt Theme Template Toàn Cục:**
   - **HeaderBar:** Lắp ráp Molecule Logo + Nav + CTA ➔ Lưu Organism & Kích hoạt Theme Template toàn site:
     ```bash
     php .agent/harness/db-tool.php --save-organism="path/to/header.html" --name="HeaderBar" --category="header" --as-template=header
     ```
   - **FooterBar:** Lắp ráp thông tin liên hệ, bản quyền, link mạng xã hội ➔ Lưu Organism & Kích hoạt Theme Template toàn site:
     ```bash
     php .agent/harness/db-tool.php --save-organism="path/to/footer.html" --name="FooterBar" --category="footer" --as-template=footer
     ```
   - *Kiểm tra danh sách Theme Templates toàn cục đã đăng ký bằng:*
     ```bash
     php .agent/harness/db-tool.php --list-templates
     ```
   - *Khi mở WP-Admin > Skaaa Theme Builder (`wp-admin/admin.php?page=skaaa-theme-builder`), Header và Footer sẽ xuất hiện sáng đèn (Active).*
4. **Lắp ráp Nội dung Trang Chuyên Biệt (Page Body Only):**
   - Nội dung Post chỉ chứa: Hero Section (USP/Headline) + Dynamic Content Grid (khối `loop` kết nối `wp_skaaa_data_*`) + CTA/Lead Form.
   - CẤM bọc lại Header/Footer tĩnh vào trong `post_content`.
   - Xuất bản trang bằng: `php .agent/harness/block-tool.php --create-test-page="..." --title="..."`.


---

## BƯỚC 3: CỔNG KIỂM ĐỊNH CHẤT LƯỢNG 3 LỚP (PRE-FLIGHT QC)
> **Quy định bất biến:** Thiếu bất kỳ 1 trong 3 lớp kiểm định này thì CẤM gửi link nghiệm thu cho Giám Đốc.

### 🟡 Lớp 1: Tiền kiểm Cú pháp CLI (Zero-Syntax-Error)
- [ ] **Quét Tailwind JIT:** Chạy `php .agent/harness/jit-tool.php --scan="<file_or_markup>"`.
  - Phải đạt 100% VALID classes, không có typo (`flex-center`, `text-bold`, `bg-slate900`, `w-300px`).
- [ ] **Thẩm định Block Schema:** Chạy `php .agent/harness/block-tool.php --validate="<file_or_markup>"`.
  - Phải dùng đúng `tagName` và `tailwindClasses` (cấm `tag` hoặc `classes`).
  - Khối tự đóng đúng chuẩn `<!-- wp:... /-->`.
  - Các handler sự kiện Alpine.js có `@click.prevent`.
  - 100% không có thẻ HTML thô (`<div>`, `<main>`) bọc ngoài comment Gutenberg.

### 🟠 Lớp 2: Kiểm định Render Server-side (Flat DOM Check)
- [ ] Chạy `php .agent/harness/block-tool.php --render="<file_or_markup>"`.
  - Kiểm tra kết quả đầu ra: Thẻ HTML sinh ra phẳng, đúng semantic tag (`header`, `h1`, `p`, `a`), mã SVG vector không bị lỗi escape.

### 🟢 Lớp 3: Đối soát Trực quan Frontend (Visual Verification)
- [ ] **Kiểm tra trực tiếp trang web:**
  - Dùng lệnh `curl -s http://...` hoặc công cụ Browser MCP mở trực tiếp URL trên trình duyệt.
  - **Dấu hiệu bắt buộc đạt:**
    - Không có bất kỳ dòng comment text thô nào như `<!-- wp:skaaaaa-builder/...` hiển thị ra ngoài màn hình.
    - Màu sắc, bố cục, font chữ hiển thị đúng chuẩn Design Tokens.
    - Layout không bị gãy vỡ, nút bấm có trạng thái hover mượt mà.

---

## BƯỚC 4: BÀN GIAO & DỪNG LƯỢT CHỜ DUYỆT (STOP & WAIT FOR APPROVAL)
Chỉ khi cả 3 lớp QC đã xanh (PASSED), Chuyên viên mới gửi báo cáo nghiệm thu cho Giám Đốc:
```text
Báo cáo Giám Đốc, sản phẩm đã hoàn thành lắp ráp theo đúng chuẩn Atomic Design và vượt qua 3 lớp kiểm định thực tế:
- Link xem trực tiếp (Frontend): [URL trang]
- Link Theme Builder trong WP Admin: [URL Admin Theme Builder: wp-admin/admin.php?page=skaaa-theme-builder]
- Cấu kiện đã hoàn thiện: Organism HeaderBar (Theme Template Global), Organism FooterBar (Theme Template Global).
```
- Cập nhật nhật ký bàn giao vào `.skaaa-ai/2-memory/checkpoint.md` và `.skaaa-ai/1-overview/system_map.md`.

> 🚨 **THIẾT QUÂN LUẬT DỪNG LƯỢT (ANTI-RUNAWAY):**
> - Sau khi gửi báo cáo ở Bước 4, AI Agent **BẮT BUỘC PHẢI DỪNG LƯỢT HOÀN TOÀN** (không gọi thêm tool, không sinh code).
> - **TUYỆT ĐỐI CẤM** tự ý nhảy cóc sang phase tiếp theo (Trang Chủ, Showcase, Wiki...).
> - **TUYỆT ĐỐI CẤM** coi tín hiệu hệ thống, callback hay artifact completion là sự đồng ý của Giám Đốc.
> - **CHỈ KHI GIÁM ĐỐC GỬI TIN NHẮN (CHAT PROMPT)** duyệt và chỉ đạo rõ ràng, Agent mới được phép bắt đầu phase kế tiếp!
