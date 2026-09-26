# QUY TRÌNH LẮP RÁP, KIỂM ĐỊNH & BÀN GIAO (2-assembly-delivery.md)
@phòng_ban: Design, Developer & QC | @mode: One-Shot Assembly & Delivery
@trigger: Kích hoạt ngay sau khi Giám Đốc duyệt Brief từ Bước 1

> Mục tiêu: Lắp ráp sản phẩm trọn gói từ Header đến Footer trong đúng 1-2 phút, kiểm định chuẩn chỉ và bàn giao link cho Giám Đốc.

---

## BƯỚC 1: TIẾP NHẬN HỒ SƠ DỰ ÁN
1. Mở tệp `.skaaa-ai/3-project-dossier/client-brief.md` để lấy dữ liệu thực tế:
   - URL Logo thật hoặc định dạng Text Logo.
   - URL Ảnh Banner Hero thật.
   - Danh sách các mục Menu Header.
   - Dòng tiêu đề H1, mô tả phụ và Hotline chân trang.
   - Tên bảng CSDL phẳng (nếu có).

---

## BƯỚC 2: THỰC THI LẮP RÁP 1 NHỊP (ONE-SHOT ASSEMBLY)
1. **Xử lý CSDL (Nếu có yêu cầu):**
   - Developer dùng mẫu PHP `$wpdb` trong `.agent/skills/developer-blocks.md` để tạo bảng phẳng `wp_skaaa_data_*`.
2. **Lắp ráp cây Block hoàn chỉnh:**
   - Designer lấy mẫu khung bố cục từ `.agent/skills/designer-patterns.md`:
     - Ráp Header: Điền đúng Logo URL và Menu.
     - Ráp Hero Section: Điền đúng Tiêu đề, nút CTA và thẻ `<img>` ảnh banner.
     - Ráp Body Sections: Điền đúng tính năng/bảng giá/form.
     - Ráp Footer: Điền đúng thông tin liên hệ và bản quyền.
3. **Xuất bản vào WordPress:**
   - Tạo WordPress Page/Post với toàn bộ chuỗi comment Gutenberg đã lắp ráp.

---

## BƯỚC 3: KIỂM ĐỊNH CHẤT LƯỢNG (QC PRE-FLIGHT CHECK)
Chuyên viên QC đối soát 3 tiêu chuẩn bắt buộc:
- [ ] Không có thẻ HTML thô (`<div>`, `<main>`) bọc ngoài comment block (100% không bị Gutenberg Invalid Content).
- [ ] Ảnh banner và Logo hiển thị đúng, có fallback `onerror` an toàn.
- [ ] Các nút bấm Alpine.js đều có modifier `@click.prevent`.

---

## BƯỚC 4: BÀN GIAO CHO GIÁM ĐỐC NGHIỆM THU
- Báo cáo kết quả trực tiếp cho Giám Đốc:
  *"Dạ thưa sếp, sản phẩm đã được lắp ráp và xuất bản hoàn tất theo đúng Brief sếp đã duyệt. Kính mời sếp kiểm tra trực tiếp tại link: [URL trang]"*
- Cập nhật nhật ký bàn giao vào `.skaaa-ai/2-company-memory/checkpoint.md`.
