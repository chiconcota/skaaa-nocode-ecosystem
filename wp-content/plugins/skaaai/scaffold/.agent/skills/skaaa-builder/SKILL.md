---
name: skaaa-builder
description: Kỹ năng chuyên sâu xây dựng, chỉnh sửa và tối ưu giao diện No-code sử dụng các khối Atomic Blocks của Skaaa, Tailwind CSS v4 JIT offline và Skaaapine (Alpine.js).
---

# SKILL: SKAAA BUILDER (ATOMIC BLOCKS & DESIGN ENGINE)

Kỹ năng này hướng dẫn AI Agent cách tạo trang, sửa khối và xây dựng bố cục chuẩn Flat DOM trong hệ sinh thái SKAAA.

---

## 1. NGUYÊN TẮC VÀNG VỀ GIAO DIỆN
1. **Comment Gutenberg thuần:** Chỉ dùng cấu trúc comment `<!-- wp:skaaaaa-builder/... {...} -->...<!-- /wp:skaaaaa-builder/... -->` hoặc dạng tự đóng `<!-- wp:... /-->`. Tuyệt đối không bọc thẻ `<main>` hoặc `<div>` thô bên ngoài gây lỗi Gutenberg Invalid Content.
2. **Flat DOM & Zero Inline CSS:** 100% thuộc tính giao diện phải quy về class Tailwind CSS v4. Tuyệt đối không dùng thuộc tính inline `style="..."`.
3. **Cú pháp Skaaapine (Alpine.js):**
   - Click handlers bắt buộc dùng `@click.prevent` để không bị nhảy trang hoặc thêm `#` vào URL.
   - Giao tiếp chéo giữa các block độc lập thông qua `Alpine.store` toàn cục.
   - Không tiêm `x-data=""` vào block con gây scope shadowing.

---

## 2. BẢN ĐỒ NĂNG LỰC & BẢNG THUỘC TÍNH 6 ATOMIC BLOCKS

| Tên Khối (Block Name) | Cú pháp Comment | Các Thuộc Tính Hợp Lệ (Attributes Schema) | Chức năng chính |
| :--- | :--- | :--- | :--- |
| **`container`** | Đóng mở | `tag`: `"div"\|"section"\|"header"\|"footer"\|"nav"\|"main"\|"article"`<br>`classes`: `"..."` (Tailwind classes)<br>`skaaapineAttrs`: `"x-data='...' @click.prevent='...'"` | Quản lý bố cục Flexbox, CSS Grid, bọc layout section. |
| **`text`** | Tự đóng `/-->` | `content`: `"..."` (Nội dung văn bản thô hoặc HTML inline)<br>`tag`: `"h1"\|"h2"\|"h3"\|"h4"\|"p"\|"span"\|"a"`<br>`classes`: `"..."` | Hiển thị tiêu đề, đoạn văn, liên kết phẳng. |
| **`button`** | Tự đóng `/-->` | `text`: `"..."`<br>`url`: `"..."`<br>`classes`: `"..."`<br>`skaaapineAttrs`: `"@click.prevent='...'"` | Nút bấm hành động chính, nút phụ, pill badges. |
| **`svg`** | Tự đóng `/-->` | `svgCode`: `"<svg ...>...</svg>"`<br>`classes`: `"w-6 h-6 ..."` | Render trực tiếp vector icon phẳng, không dùng icon font nặng nề. |
| **`code`** | Tự đóng `/-->` | `code`: `"<script>...</script>"` hoặc mã HTML thô<br>`lang`: `"html"\|"javascript"` | Nhúng mã tùy biến an toàn. |
| **`loop`** | Đóng mở | `tableName`: `"wp_skaaa_data_[slug]"`<br>`query`: `"LIMIT 10"` hoặc JSON options | Lặp và nạp danh sách bản ghi từ bảng phẳng CSDL. |

---

## 3. BẢNG ĐỐI CHIẾU SAI ➔ ĐÚNG (TRÁNH LỖI INVALID CONTENT)

| AI Thường Viết SAI (Thói quen cũ) | Cú pháp BẮT BUỘC ĐÚNG của Skaaa | Hậu quả nếu viết sai |
| :--- | :--- | :--- |
| `<div class="hero">` bọc ngoài comment block | Dùng khối `container` với thuộc tính `{"tag":"section","classes":"hero ..."}` | Báo lỗi đỏ "This block contains unexpected or invalid content". |
| `style="background-color: #020617;"` | Dùng class Tailwind: `classes="bg-slate-950"` | Vi phạm kiến trúc Tailwind JIT offline, không thể responsive. |
| `@click="isOpen = !isOpen"` | `@click.prevent="isOpen = !isOpen"` | Trình duyệt nhảy giật lên đầu trang và đính `#` vào URL. |
| `className="flex items-center"` | `classes="flex items-center"` | Khối không nhận diện được style vì sai tên thuộc tính. |
| Tiêm `x-data="{ count: 0 }"` vào mọi block con | Đặt `x-data` ở container cha ngoài cùng hoặc dùng `Alpine.store` | Scope shadowing làm mất biến của component cha. |

---

## 4. QUY TRÌNH THỰC HIỆN KHI NHẬN LỆNH DỰNG GIAO DIỆN
1. Đọc `.skaaa-ai/1-overview/design.md` để lấy bảng màu thương hiệu (`bg-primary`, `bg-slate-950`), font chữ `font-sans` và quy chuẩn bo góc.
2. Thiết kế cây block từ ngoài vào trong: Container Section ngoài cùng ➔ Container Wrapper/Grid ➔ Atomic Blocks (Text, Button, SVG).
3. Sử dụng các class Tailwind v4 đã được định nghĩa trong JIT compiler.

---

## 5. MẪU BỐ CỤC THỰC CHIẾN (HERO SECTION HOÀN CHỈNH)

```html
<!-- wp:skaaaaa-builder/container {"tag":"section","classes":"relative py-20 md:py-32 bg-slate-950 border-b border-white/10 overflow-hidden"} -->
  <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"max-w-5xl mx-auto px-6 text-center flex flex-col items-center"} -->
    
    <!-- Huy hiệu Badge nổi bật -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-6"} -->
      <!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-4 h-4\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path d=\"M10 2a8 8 0 100 16 8 8 0 000-16zm1 11H9v-2h2v2zm0-4H9V5h2v4z\"/></svg>","classes":"w-4 h-4"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Hệ Sinh Thái No-Code Thế Hệ Mới","tag":"span","classes":"text-xs font-semibold"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- Tiêu đề Hero H1 -->
    <!-- wp:skaaaaa-builder/text {"content":"Kiến Tạo Ứng Dụng Đỉnh Cao Không Cần Viết Mã","tag":"h1","classes":"text-4xl md:text-6xl font-black text-white tracking-tight leading-tight mb-6"} /-->

    <!-- Đoạn văn mô tả -->
    <!-- wp:skaaaaa-builder/text {"content":"Xây dựng giao diện mượt mà, quản lý cơ sở dữ liệu bảng phẳng và tự động hóa quy trình nghiệp vụ với sức mạnh của Skaaa.","tag":"p","classes":"text-lg md:text-xl text-slate-400 max-w-2xl mb-10 leading-relaxed"} /-->

    <!-- Cụm nút bấm hành động (CTA Group) -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"flex flex-col sm:flex-row items-center gap-4"} -->
      <!-- wp:skaaaaa-builder/button {"text":"Khám Phá Ngay","url":"#features","classes":"w-full sm:w-auto px-8 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold shadow-lg shadow-blue-500/25 transition-all hover:scale-[1.02]"} /-->
      <!-- wp:skaaaaa-builder/button {"text":"Tài Liệu Hướng Dẫn","url":"#docs","classes":"w-full sm:w-auto px-8 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-white/10 text-slate-300 font-semibold transition-all hover:text-white"} /-->
    <!-- /wp:skaaaaa-builder/container -->

  <!-- /wp:skaaaaa-builder/container -->
<!-- /wp:skaaaaa-builder/container -->
```
