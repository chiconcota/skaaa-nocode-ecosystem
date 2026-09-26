# ĐỒ NGHỀ THIẾT KẾ: CÁC MẪU BỐ CỤC CHUẨN (designer-patterns.md)
@phòng_ban: UI/UX Design | @target: Designer & Block Builder

> Tài liệu này cung cấp các mẫu khung bố cục hoàn chỉnh có sẵn vị trí cho Logo và Hình ảnh. Designer chỉ việc bốc khung ra và điền dữ liệu thật từ `client-brief.md`.

---

## 1. MẪU HEADER HOÀN CHỈNH (CÓ LOGO & MENU)

```html
<!-- wp:skaaaaa-builder/container {"tag":"header","classes":"w-full bg-slate-950/80 backdrop-blur-md border-b border-white/10 sticky top-0 z-50"} -->
  <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"max-w-7xl mx-auto px-6 h-20 flex items-center justify-between"} -->
    
    <!-- KHUNG LOGO: Thay URL ảnh từ client-brief.md, nếu là text dùng khối text -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"flex items-center gap-3 cursor-pointer"} -->
      <!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-8 h-8 text-blue-500\" fill=\"currentColor\" viewBox=\"0 0 24 24\"><path d=\"M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5\"/></svg>","classes":"w-8 h-8"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"BRAND LOGO","tag":"span","classes":"text-xl font-bold text-white tracking-wider"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- KHUNG MENU ĐIỀU HƯỚNG -->
    <!-- wp:skaaaaa-builder/container {"tag":"nav","classes":"hidden md:flex items-center gap-8"} -->
      <!-- wp:skaaaaa-builder/text {"content":"Trang Chủ","tag":"a","classes":"text-sm font-medium text-white hover:text-blue-400 transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Tính Năng","tag":"a","classes":"text-sm font-medium text-slate-300 hover:text-blue-400 transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Bảng Giá","tag":"a","classes":"text-sm font-medium text-slate-300 hover:text-blue-400 transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Liên Hệ","tag":"a","classes":"text-sm font-medium text-slate-300 hover:text-blue-400 transition-colors"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- NÚT BẤM CTA HEADER -->
    <!-- wp:skaaaaa-builder/button {"text":"Bắt Đầu Ngay","url":"#contact","classes":"px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-md shadow-blue-500/20 transition-all hover:scale-105"} /-->

  <!-- /wp:skaaaaa-builder/container -->
<!-- /wp:skaaaaa-builder/container -->
```

---

## 2. MẪU HERO SECTION (CÓ NỘI DUNG & ẢNH BANNER)

```html
<!-- wp:skaaaaa-builder/container {"tag":"section","classes":"relative py-20 md:py-28 bg-slate-950 overflow-hidden border-b border-white/10"} -->
  <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center"} -->
    
    <!-- CỘT NỘI DUNG CHỮ -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"flex flex-col items-start"} -->
      <!-- wp:skaaaaa-builder/text {"content":"Giải Pháp Thế Hệ Mới","tag":"span","classes":"px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-6"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Tiêu Đề Lớn Giải Quyết Nỗi Đau Khách Hàng","tag":"h1","classes":"text-4xl md:text-5xl font-black text-white tracking-tight leading-tight mb-6"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Đoạn mô tả ngắn gọn súc tích 2-3 câu làm nổi bật giá trị cốt lõi của sản phẩm hoặc dịch vụ.","tag":"p","classes":"text-lg text-slate-400 mb-8 leading-relaxed"} /-->
      <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"flex items-center gap-4"} -->
        <!-- wp:skaaaaa-builder/button {"text":"Dùng Thử Miễn Phí","url":"#pricing","classes":"px-7 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold shadow-lg shadow-blue-500/25 transition-all"} /-->
        <!-- wp:skaaaaa-builder/button {"text":"Xem Demo","url":"#features","classes":"px-7 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-white/10 text-slate-300 font-semibold"} /-->
      <!-- /wp:skaaaaa-builder/container -->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- CỘT HÌNH ẢNH BANNER: Lấy URL từ client-brief.md -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"relative w-full aspect-video md:aspect-[4/3] rounded-2xl overflow-hidden border border-white/10 shadow-2xl bg-slate-900"} -->
      <!-- wp:skaaaaa-builder/code {"code":"<img src=\"[URL_ANH_BANNER]\" alt=\"Hero Banner\" class=\"w-full h-full object-cover\" onerror=\"this.src='https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=80'\" />","lang":"html"} /-->
    <!-- /wp:skaaaaa-builder/container -->

  <!-- /wp:skaaaaa-builder/container -->
<!-- /wp:skaaaaa-builder/container -->
```

---

## 3. MẪU FOOTER HOÀN CHỈNH (CÓ THÔNG TIN CÔNG TY & LIÊN HỆ)

```html
<!-- wp:skaaaaa-builder/container {"tag":"footer","classes":"w-full bg-slate-950 border-t border-white/10 py-16 text-slate-400"} -->
  <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-10 mb-12"} -->
    
    <!-- Cột 1: Thông tin công ty & Hotline -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"md:col-span-2"} -->
      <!-- wp:skaaaaa-builder/text {"content":"TÊN DOANH NGHIỆP","tag":"h3","classes":"text-xl font-bold text-white mb-4"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Mô tả ngắn gọn về sứ mệnh hoặc năng lực cốt lõi của doanh nghiệp.","tag":"p","classes":"text-sm text-slate-400 max-w-sm leading-relaxed mb-4"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Hotline: 0900.xxx.xxx | Email: contact@domain.com","tag":"p","classes":"text-sm text-slate-300 font-medium"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- Cột 2: Liên kết nhanh -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"flex flex-col gap-2.5"} -->
      <!-- wp:skaaaaa-builder/text {"content":"Liên Kết","tag":"h4","classes":"text-sm font-semibold text-white uppercase tracking-wider mb-2"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Trang Chủ","tag":"a","classes":"text-sm hover:text-white transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Tính Năng","tag":"a","classes":"text-sm hover:text-white transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Bảng Giá","tag":"a","classes":"text-sm hover:text-white transition-colors"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- Cột 3: Chính sách -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"flex flex-col gap-2.5"} -->
      <!-- wp:skaaaaa-builder/text {"content":"Chính Sách","tag":"h4","classes":"text-sm font-semibold text-white uppercase tracking-wider mb-2"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Điều Khoản Dịch Vụ","tag":"a","classes":"text-sm hover:text-white transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Bảo Mật Thông Tin","tag":"a","classes":"text-sm hover:text-white transition-colors"} /-->
    <!-- /wp:skaaaaa-builder/container -->

  <!-- /wp:skaaaaa-builder/container -->

  <!-- Dòng Bản Quyền Cuối Trang -->
  <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"max-w-7xl mx-auto px-6 pt-8 border-t border-white/5 text-center text-xs text-slate-500"} -->
    <!-- wp:skaaaaa-builder/text {"content":"© 2026 Tên Doanh Nghiệp. All rights reserved.","tag":"p","classes":"text-xs"} /-->
  <!-- /wp:skaaaaa-builder/container -->

<!-- /wp:skaaaaa-builder/container -->
```

---

## 4. BẢNG ĐỐI CHIẾU SAI ➔ ĐÚNG CỦA DESIGNER

| Thói Quen Cũ Sai Lầm | Cách Viết BẮT BUỘC ĐÚNG Trong Skaaa | Hậu Quả Nếu Viết Sai |
| :--- | :--- | :--- |
| `<div class="hero">` bọc ngoài comment block | Dùng khối `container` với thuộc tính `{"tag":"section","classes":"hero ..."}` | Báo lỗi đỏ Invalid Content trên Gutenberg. |
| Để trống logo hoặc không hỏi logo | Lấy Logo từ `client-brief.md` (ảnh hoặc text + svg) | Header bị mù logo, Giám Đốc không duyệt. |
| Quên fallback ảnh banner | Luôn thêm thuộc tính `onerror="this.src='...'"` | Ảnh bị vỡ icon xám xịt khi link hỏng. |

---

## 5. CÔNG CỤ TIỀN KIỂM CÚ PHÁP TAILWIND JIT (jit-tool.php)

> **Thiết quân luật cho Designer:** Tuyệt đối không giao block chứa class rác hoặc sai cú pháp (như `flex-center`, `text-bold`, `bg-slate900`, `w-300px`). Trước khi bàn giao giao diện, **BẮT BUỘC** chạy công cụ kiểm tra cú pháp:

### 1. Kiểm tra nhanh một danh sách class:
```bash
php .agent/harness/jit-tool.php --check="flex items-center text-sm font-semibold bg-slate-900 text-white p-4 rounded-xl"
```

### 2. Quét toàn bộ file mẫu hoặc đoạn mã HTML / Gutenberg Block:
```bash
# Quét trực tiếp file layout vừa thiết kế
php .agent/harness/jit-tool.php --scan="path/to/my-block.html"

# Quét kèm xem trước mã CSS được sinh ra
php .agent/harness/jit-tool.php --scan="path/to/my-block.html" --compile
```

### 3. Tra cứu từ điển quy tắc JIT đang được nạp:
```bash
php .agent/harness/jit-tool.php --rules
```

### 4. Bảng Lỗi Phổ Biến & Cách Sửa Nhanh (Gợi ý tự động từ Tool):
| Lỗi Designer Thường Mắc | Nguyên Nhân | Sửa Lại Cho Đúng |
| :--- | :--- | :--- |
| `flex-center` | Không có utility này | `items-center justify-center` |
| `text-bold` | Nhầm chữ với độ dày font | `font-bold` |
| `bg-slate900` | Thiếu dấu gạch nối giữa màu và độ đậm | `bg-slate-900` |
| `w-300px` | Giá trị tuỳ biến thiếu ngoặc vuông | `w-[300px]` |
| `cursor-hand` | Tên thuộc tính CSS sai | `cursor-pointer` |
| `shadow-box` | Tên shadow không chuẩn | `shadow` hoặc `shadow-md` |

