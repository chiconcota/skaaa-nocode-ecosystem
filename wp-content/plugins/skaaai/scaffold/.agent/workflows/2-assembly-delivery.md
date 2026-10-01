# QUY TRÌNH LẮP RÁP, KIỂM ĐỊNH & BÀN GIAO (2-assembly-delivery.md)
@phòng_ban: Design, Developer & QC | @mode: Atomic Assembly & Rigorous QC
@trigger: Kích hoạt ngay sau khi Giám Đốc duyệt Brief từ /client-intake

> **Mục tiêu:** Nạp Design Tokens vào CSDL, đóng gói Cấu kiện Tái sử dụng (Organisms), lắp ráp nội dung trang và kiểm định 3 lớp trước khi bàn giao cho Giám Đốc.

---

## BƯỚC 1: TIẾP NHẬN HỒ SƠ DỰ ÁN
1. Mở tệp `.skaaa-ai/1-overview/client-brief.md` để lấy dữ liệu thực tế:
   - URL Logo SVG thật và danh sách 1-2 ảnh chân dung chính.
   - Bảng mã màu chốt (Primary, Secondary, Background, Surface, Border cho Light & Dark Mode).
   - Danh sách mục Menu Header & thông tin liên hệ Footer.
   - Tên bảng CSDL phẳng MySQL (`wp_skaaa_data_*`) cho dữ liệu động.

---

## BƯỚC 2: THỰC THI LẮP RÁP DATABASE-FIRST
1. **Nạp Design Tokens vào CSDL & Compile CSS:**
   - Chạy lệnh CLI để lưu bảng màu, typography và logo vào bảng phẳng `wp_skaaa_data_sys_presets`:
     ```bash
     php .agent/harness/db-tool.php --set-tokens='{"brand":{"logourl":"..."},"colors":{...},"darkColors":{...}}'
     ```
   - Đồng bộ bảng đối soát vào `.skaaa-ai/1-overview/brand-guidelines.md`.
2. **Khởi tạo CSDL phẳng MySQL (Nếu có dữ liệu động):**
   - Developer dùng mẫu PHP `$wpdb` trong `.agent/skills/developer-blocks/SKILL.md` để tạo bảng và nạp dữ liệu mẫu bằng `db-tool.php`.
3. **Đóng gói Cấu Kiện Tái Sử Dụng & Kích Hoạt Theme Template Toàn Cục:**
   - Ráp HeaderBar ➔ Lưu Organism & Kích hoạt Theme Template toàn site:
     ```bash
     php .agent/harness/db-tool.php --save-organism="path/to/header.html" --name="HeaderBar" --category="header" --as-template=header
     ```
   - Ráp FooterBar ➔ Lưu Organism & Kích hoạt Theme Template toàn site:
     ```bash
     php .agent/harness/db-tool.php --save-organism="path/to/footer.html" --name="FooterBar" --category="footer" --as-template=footer
     ```
   - *Kiểm tra danh sách Theme Templates toàn cục đã đăng ký bằng:*
     ```bash
     php .agent/harness/db-tool.php --list-templates
     ```
   - *Khi mở WP-Admin > Skaaa Theme Builder (`wp-admin/admin.php?page=skaaa-theme-builder`), Header và Footer sẽ xuất hiện sáng đèn (Active).*
4. **Lắp ráp Nội dung Trang Chuyên Biệt (Page Body Only):**
   - Lắp ráp Hero Section + Dynamic Content Grid (khối `loop`) + CTA.
   - CẤM dồn Header/Footer vào trong `post_content`.
   - Xuất bản trang bằng: `php .agent/harness/block-tool.php --create-test-page="..." --title="..."`.

---

## BƯỚC 3: CỔNG KIỂM ĐỊNH CHẤT LƯỢNG 3 LỚP (PRE-FLIGHT QC)
Chuyên viên QC đối soát các tiêu chuẩn bắt buộc:
- [ ] **Lớp 1 (CLI Syntax):**
  - Chạy `php .agent/harness/jit-tool.php --scan="<file_or_markup>"` (100% VALID classes, không có typo).
  - Chạy `php .agent/harness/block-tool.php --validate="<file_or_markup>"` (100% comment Gutenberg thuần, có `@click.prevent`).
- [ ] **Lớp 2 (Server DOM):**
  - Chạy `php .agent/harness/block-tool.php --render="<file_or_markup>"` (Flat DOM phẳng sạch, SVG vector nguyên vẹn).
- [ ] **Lớp 3 (Visual Frontend):**
  - Mở trực tiếp link xem trước trên trình duyệt, xác nhận không hiển thị comment thô `<!-- wp:...`, màu sắc ăn theo đúng Design Tokens.

---

## BƯỚC 4: BÀN GIAO & DỪNG LƯỢT CHỜ DUYỆT (STOP & WAIT FOR APPROVAL)
- Báo cáo kết quả trực tiếp cho Giám Đốc:
  *"Dạ thưa sếp, cấu kiện Header và Footer đã được lắp ráp hoàn tất theo chuẩn Atomic Design, đăng ký làm Theme Template toàn cục trên Skaaa Theme Builder và vượt qua 3 lớp kiểm định thực tế. Kính mời sếp kiểm tra tại link: [URL trang] và quản lý tại: wp-admin/admin.php?page=skaaa-theme-builder"*
- Cập nhật nhật ký bàn giao vào `.skaaa-ai/2-memory/checkpoint.md` và `.skaaa-ai/2-memory/decision-log.md`.
- **THIẾT QUÂN LUẬT DỪNG LƯỢT (ANTI-RUNAWAY):** Dừng hoàn toàn tool calls và lượt làm việc. TUYỆT ĐỐI CẤM tự ý nhảy sang Phase tiếp theo (Trang Chủ, Showcase, Wiki...). CẤM coi tín hiệu hệ thống hay artifact completion là sự đồng ý của Giám Đốc. Chỉ khi Giám Đốc gửi tin nhắn chat chỉ đạo, Agent mới được phép tiếp tục!
