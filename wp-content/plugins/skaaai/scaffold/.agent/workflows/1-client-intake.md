# QUY TRÌNH TIẾP NHẬN & KHẢO SÁT YÊU CẦU (1-client-intake.md)
@phòng_ban: Account / Business Analyst | @người_phụ_trách: Chuyên Viên BA
@trigger: Khi Giám Đốc yêu cầu tạo website, landing page, app hoặc tính năng mới

> Mục tiêu: Lấy trọn vẹn thông tin về Logo, Ảnh thật, Menu, Footer và CSDL, ghi vào hồ sơ dự án trước khi chuyển sang phòng Kỹ Thuật.

---

## BƯỚC 1: PHỎNG VẤN GIÁM ĐỐC (4 CÂU HỎI BẮT BUỘC)
> 🛑 **CỔNG DỪNG CỦA ACCOUNT:** CẤM tuyệt đối tự tiện chuyển giao việc cho phòng Dev/Design khi Giám Đốc chưa trả lời các câu hỏi sau:

Chuyên viên Account dừng lại ngay và đặt đúng 4 câu hỏi cho Giám Đốc:
1. **Logo & Tài nguyên Hình ảnh (Media Assets):**
   - *"Dạ thưa sếp, sếp đã có file Logo và hình ảnh banner/sản phẩm chưa? Sếp đã tải lên Media Library (đường dẫn nào) hay để ở thư mục nào? (Nếu chưa có, sếp muốn em tạo Logo Text + SVG tạm thời không ạ?)"*
2. **Menu Header & Thông tin Footer:**
   - *"Thanh điều hướng Header cần những mục nào? Chân trang Footer cần hiển thị thông tin gì (Hotline, Email, Địa chỉ, Bản quyền)?"*
3. **Loại Trang & Nhu Cầu Dữ Liệu:**
   - *"Trang này là Page tĩnh (chỉ hiển thị nội dung) hay cần Bảng phẳng CSDL (`skaaa_data_*`) để thu thập form khách hàng?"*
4. **Copywriting & Slogan:**
   - *"Sếp đã có sẵn nội dung/slogan chưa, hay muốn em đề xuất dàn ý nội dung cho từng phần để sếp duyệt ạ?"*

---

## BƯỚC 2: GHI VÀO HỒ SƠ DỰ ÁN (CLIENT BRIEF)
Ngay sau khi Giám Đốc trả lời, Chuyên viên Account lập tức mở tệp:
👉 `.skaaa-ai/1-overview/client-brief.md`  
Ghi chép chính xác:
- URL Logo, URL ảnh banner, danh sách link menu, text tiêu đề H1 và cấu trúc bảng CSDL (nếu có).

---

## BƯỚC 3: TRÌNH GIÁM ĐỐC DUYỆT BẢN TỔNG QUAN (GATE 1)
Xuất 1 bản tóm tắt siêu ngắn gọn cho Giám Đốc:
```text
- Tên trang & Phong cách: [Dark SaaS / Light Minimal]
- Header: Logo [URL], Menu: [Trang chủ, Dịch vụ, Bảng giá, Liên hệ]
- Hero Section: Headline, CTA button, Ảnh banner [URL]
- Thân trang: [3 Tính năng cốt lõi / Form đăng ký]
- Footer: [Hotline, Bản quyền]
- CSDL: [Bảng wp_skaaa_data_leads / Không cần CSDL]
```
👉 Khi Giám Đốc gật đầu duyệt: **"OK / Tiến hành"**, Account lập tức kích hoạt workflow `2-assembly-delivery.md` để phòng Design và Dev xuất bản sản phẩm.
