---
name: client-intake
description: Quy trình tiếp nhận & khảo sát định vị thương hiệu, USP, hệ sinh thái tính năng & kiến trúc Atomic Data (/client-intake)
---

# QUY TRÌNH TIẾP NHẬN & KHẢO SÁT ĐỊNH VỊ THƯƠNG HIỆU (/client-intake)
@phòng_ban: Account / Business Analyst | @người_phụ_trách: Chuyên Viên BA / Solution Architect
@trigger: Khi Giám Đốc/Khách hàng yêu cầu làm website cá nhân, doanh nghiệp, landing page, app hoặc tính năng mới, hoặc gõ /client-intake

> **TƯ TƯỞNG CỐT LÕI:** Skaaa là No-Code Ecosystem & App Builder theo chuẩn Atomic Design, KHÔNG PHẢI công cụ gõ HTML tĩnh mì ăn liền. Khảo sát phải đi sâu vào Định vị, Bản sắc (Identity), USP, Hệ sinh thái tính năng đòn bẩy (Leverage Features) và Dữ liệu động trước khi chuyển sang khâu thiết kế.

---

## BƯỚC 1: KHẢO SÁT 4 TRỤ CỘT ĐỊNH VỊ & KIẾN TRÚC (BẮT BUỘC)

🛑 **CỔNG DỪNG CỦA ACCOUNT (GATE 0):** CẤM tuyệt đối tự tiện chuyển giao việc cho phòng Dev/Design hoặc cắm đầu ráp trang khi chưa làm rõ 4 trụ cột sau:

### 1. Bản Sắc & Định Vị Độc Bản (Identity & Positioning):
* *"Giám Đốc/Anh là ai? Chuyên môn, thị trường ngách (niche) và đối tượng độc giả/khách hàng mục tiêu của anh là ai?"*
* **USP (Unique Selling Proposition):** *"Điểm độc bản khác biệt làm nên sức mạnh của anh là gì?"* (Ví dụ: Sự kết hợp hiếm có giữa Tư duy Tăng trưởng Digital Marketing và Kỹ thuật lập trình hệ thống Linux/FOSS; hoặc Kiến trúc sư giải pháp tối ưu nguồn lực).
* *"Câu tuyên ngôn cốt lõi (Manifesto) hoặc Slogan định vị thương hiệu mà anh muốn truyền tải là gì?"*

### 2. Hệ Thống Tính Năng Đòn Bẩy (Leverage & Feature Ecosystem):
* Website này cần những phân hệ nào để phục vụ chiến lược thương hiệu/kinh doanh? 
  * 🚀 **Proof of Work / Showcase:** Khu vực trưng bày dự án, công cụ thực chiến kèm thẻ tags công nghệ, link GitHub/Demo.
  * 📚 **Knowledge Hub / Wiki:** Hệ thống tài liệu kỹ thuật, cẩm nang chuyên sâu đa tầng (cần cấu trúc phân cấp).
  * 🎓 **Monetization / LMS:** Nền tảng bán khóa học, tài liệu trả phí hoặc dịch vụ cố vấn.
  * 🎯 **Lead Capture / Đòn bẩy chuyển đổi:** Form thu thập lead, newsletter capture, live demo playground.
* **Tư vấn chủ động:** Đề xuất cho khách hàng các tính năng nên có để gia tăng uy tín và đòn bẩy thương hiệu cá nhân trên môi trường số.

### 3. Bản Sắc Thị Giác & Hệ Thống Thiết Kế (Design System & Tokens):
* *"Anh thích phong cách thị giác nào?"* (Dark Tech Stealth, Minimal Editorial, Clean Modern SaaS, Futuristic Monospace, v.v.).
* *"Anh đã có bộ Design Tokens / Design System sẵn (trên Figma, Stitch project ID, hay file mẫu) chưa?"*
* **Bảng màu nhận diện:** Primary, Secondary, Canvas Background, Surface Card, Accent Neon, Structural Border.
* **Tài nguyên hình ảnh & Logo:** Logo SVG vector độc bản (hoặc Text Monogram), ảnh đại diện Avatar chân dung thật, link Media Library.

### 4. Chiến Lược CSDL Bảng Phẳng (Skaaa Data Pro Strategy):
* Xác định các thực thể dữ liệu động cần lưu trữ độc lập dưới dạng bảng phẳng MySQL (`wp_skaaa_data_*`):
  * Ví dụ: `wp_skaaa_data_projects`, `wp_skaaa_data_wiki_docs`, `wp_skaaa_data_courses`, `wp_skaaa_data_leads`.
* **Quy tắc bất biến:** Không bao giờ hardcode dữ liệu động vào trang tĩnh. Mọi danh sách phải được chuẩn bị cấu trúc bảng để kết nối khối `loop`.

---

## BƯỚC 2: GHI VÀO HỒ SƠ DỰ ÁN (CLIENT BRIEF)

Ngay sau khi có thông tin, Account cập nhật trực tiếp vào file tài liệu chuẩn:
👉 `.skaaa-ai/1-overview/client-brief.md` *(TUYỆT ĐỐI KHÔNG tạo thư mục mới ngoài 4 ngăn kéo chuẩn)*.

Nội dung ghi chép bao gồm:
1. **Brand Identity:** Tên, Danh xưng, Niche, USP, Manifesto/Slogan.
2. **Design Tokens:** Bảng mã màu HEX/Tailwind, Font chữ (Display & Mono), Logo SVG, Avatar URL.
3. **Module Architecture:** Danh sách các phân hệ (Showcase, Wiki, LMS, Blog, Lead Form).
4. **Data Pro Blueprint:** Danh sách các bảng phẳng `skaaa_data_*` và trường dữ liệu tương ứng.
5. **Atomic Components Plan:** Danh sách các Organism cần xây dựng (Header, Footer, Hero, ProjectCard, WikiSidebar).

---

## BƯỚC 3: TRÌNH GIÁM ĐỐC DUYỆT BẢN THIẾT KẾ KIẾN TRÚC TỔNG THỂ (GATE 1)

Xuất bản tóm tắt kiến trúc trực quan, rõ ràng cho Giám Đốc/Khách hàng:
```text
📋 BẢN ĐỊNH HƯỚNG KIẾN TRÚC WEBSITE (GATE 1 DUYỆT)
1. Định vị & USP: [Tóm tắt 1-2 câu điểm độc bản]
2. Phong cách Thẩm mỹ & Tokens: [Bảng màu, Font, Phong cách]
3. Các Phân Hệ Tính Năng:
   - Phân hệ 1: [Tên phân hệ + mục tiêu đòn bẩy]
   - Phân hệ 2: [Tên phân hệ + mục tiêu đòn bẩy]
4. Kế hoạch CSDL Bảng Phẳng (Data Pro): [wp_skaaa_data_*]
5. Cấu kiện Atomic tái sử dụng:
   - Organisms dùng chung: HeaderBar, FooterBar (dùng chung cho toàn bộ các trang)
   - Molecules động: ProjectCard, LessonCard (kết nối CSDL qua loop block)
```

👉 **Khi Giám Đốc duyệt "OK / Tiến hành":** Account kích hoạt quy trình thiết kế theo chuẩn Atomic Design (`/designer-patterns` và `/assembly-delivery`).
