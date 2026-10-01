# HỒ SƠ NĂNG LỰC & BẢN ĐỒ CÔNG NGHỆ DOANH NGHIỆP (system-map.md)
@company: SKAAA NO-CODE ECOSYSTEM | @status: Production Ready

> Tài liệu này mô tả vũ khí công nghệ và nền tảng của Doanh Nghiệp. Toàn bộ nhân viên AI phải nắm rõ năng lực này trước khi tư vấn hoặc xây dựng sản phẩm.

---

## 1. HỆ SINH THÁI CỐT LÕI (4 PLUGINS + 1 THEME)
1. **Skaaa Canvas (Theme):** Blank Canvas theme thuần khiết, loại bỏ 100% CSS/JS mặc định của WordPress, tự động bù trừ thanh Admin Bar.
2. **Skaaa No-Code Design (Plugin):** Chịu trách nhiệm toàn bộ về giao diện, 6 khối Atomic, Tailwind CSS v4 JIT offline và Skaaapine (Alpine.js).
3. **Skaaa Data Pro (Plugin):** Chịu trách nhiệm về cơ sở dữ liệu bảng phẳng MySQL `skaaa_data_*`, Schema Manager, triệt tiêu 100% `wp_postmeta`.
4. **Skaaa Logic Engine (Plugin):** Chịu trách nhiệm về tự động hóa, đồ thị DAG sự kiện, biểu thức SkaaaFX AST.
5. **Skaaai (Plugin):** Cầu nối đồng bộ 1-Click (Local ⟷ Live Webhost) và Buồng lái AI Scaffolding.

---

## 2. KHO LINH KIỆN CÓ SẴN (6 ATOMIC BLOCKS)
- `container`: Quản lý bố cục bọc ngoài, Flexbox, Grid.
- `text`: Tiêu đề `h1`-`h4`, đoạn văn `p`, thẻ liên kết `a`.
- `button`: Nút bấm hành động, CTA, pill badges.
- `svg`: Vector icons phẳng, tối ưu tốc độ tải 0ms.
- `code`: Nhúng thẻ `<img>` ảnh hoặc script tùy biến.
- `loop`: Nạp và lặp dữ liệu từ bảng phẳng CSDL.

---

## 3. NGUYÊN TẮC BẤT BIẾN CỦA DOANH NGHIỆP
- **Flat Tables First:** Không bao giờ dùng `wp_postmeta`.
- **Offline JIT Parity:** 100% class Tailwind biên dịch offline không cần CDN ngoài.
- **Zero Inline CSS:** 100% style nằm trong class Tailwind.
