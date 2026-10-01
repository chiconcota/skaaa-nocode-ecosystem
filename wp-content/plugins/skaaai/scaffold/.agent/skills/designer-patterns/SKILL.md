---
name: designer-patterns
description: Hệ thống thiết kế & thư viện cấu kiện Atomic Design (Tokens, Atoms, Molecules, Organisms, Templates), Form UI và tiền kiểm Tailwind JIT
---

# HỆ THỐNG THIẾT KẾ ATOMIC DESIGN & THƯ VIỆN CẤU KIỆN (/designer-patterns)
@phòng_ban: UI/UX Design & Frontend Architecture | @target: Designer & Block Builder
@trigger: Khi thiết kế layout, tạo component mới, tra cứu mẫu cấu kiện hoặc gõ /designer-patterns

> **THIẾT QUÂN LUẬT ATOMIC DESIGN:** Skaaa sinh ra để làm Web/App Builder, KHÔNG PHẢI công cụ sinh 1 trang HTML tĩnh dùng một lần! CẤM gom toàn bộ trang thành 1 file monolithic 100+ blocks dồn toa. Toàn bộ giao diện phải được phân rã nghiêm ngặt theo 5 tầng:
> **Tokens ➔ Atoms ➔ Molecules ➔ Organisms ➔ Templates ➔ Pages**
> Để đảm bảo: **Tái sử dụng cao nhất (Reusability) - Đồng bộ tập trung (Single Source of Truth) - Động hóa dữ liệu (Data-Binding).**

---

## TẦNG 0: DESIGN TOKENS (HỆ THỐNG GIÁ TRỊ THIẾT KẾ CỐT LÕI)

> **THIẾT QUÂN LUẬT DATABASE-FIRST & TOKEN-FIRST:**
> 1. Design Tokens KHÔNG PHẢI là bản văn markdown lý thuyết. Mọi giá trị bắt buộc nạp vào CSDL MySQL (`wp_skaaa_data_sys_presets`) qua CLI:
>    `php .agent/harness/db-tool.php --set-tokens='{"brand":{"logourl":"..."},"colors":{...},"darkColors":{...}}'`
> 2. **CẤM HARDCODE CLASS TAILWIND MÀU CỤ THỂ:** Tuyệt đối không dùng `bg-amber-500`, `text-amber-400`, `bg-[#08090c]`, `bg-[#10131a]`, `border-[#1e2433]`... Bắt buộc dùng 100% họ class Design Tokens: **`bg-background`, `bg-surface`, `border-border`, `text-text`, `bg-primary`, `text-primary`, `text-secondary`, `border-primary`**. Khi Giám Đốc đổi màu ở trang quản trị Tokens, toàn bộ website sẽ đổi màu tự động!

### 0.1. Bảng Đối Soát Design Tokens Chuẩn (Khớp 100% Schema Skaaa)

| Tên Trường CSDL (`sys_presets`) | Loại Token (`type`) | Ý Nghĩa Thị Giác | Class Tailwind Chuẩn Phải Dùng | Ý Nghĩa Khi Đổi Token |
| :--- | :--- | :--- | :--- | :--- |
| **`LogoUrl`** | `token_brand` | Đường dẫn file SVG Logo vector | Khối `svg` hoặc `<img>` | Đổi logo toàn website |
| **`Background`** | `token_color` / `token_dark_color` | Nền chính toàn trang (Canvas Base) | `bg-background dark:bg-background` | Đổi nền toàn bộ trang web |
| **`Surface`** | `token_color` / `token_dark_color` | Nền thẻ nổi, card, modal, header | `bg-surface dark:bg-surface` | Đổi nền header, card, popup |
| **`Border`** | `token_color` / `token_dark_color` | Đường viền kỹ thuật sắc nét | `border-border dark:border-border` | Đổi màu viền toàn bộ cấu kiện |
| **`Primary`** | `token_color` / `token_dark_color` | Màu hành động chính, nút CTA | `bg-primary`, `text-primary`, `border-primary` | Đổi màu nút chính, tiêu đề nhấn |
| **`Secondary`** | `token_color` / `token_dark_color` | Màu điểm nhấn trạng thái, pulse | `text-secondary`, `bg-secondary` | Đổi màu badge, tag, pulse dot |
| **`Tertiary`** | `token_color` / `token_dark_color` | Màu bổ trợ thứ cấp, link tham chiếu | `text-tertiary`, `bg-tertiary` | Đổi màu link phụ, ghi chú |
| **`Text`** | `token_color` / `token_dark_color` | Màu chữ chính (Tiêu đề, Body) | `text-text dark:text-text` | Đổi màu chữ toàn bộ trang |
| **`PrimaryFont`** | `token_font` | Font giao diện chính | `font-sans` | Đổi font toàn hệ thống |
| **`MonoFont`** | `token_font` | Font kỹ thuật (Code, Badge, KPI) | `font-mono` | Đổi font kỹ thuật |

---

## TẦNG 1: ATOMS (CÁC KHỐI NGUYÊN TỬ ĐỘC LẬP)

### 1.1. Text Atom (`<!-- wp:skaaaaa-builder/text ... /-->`)
```html
<!-- Heading H1 -->
<!-- wp:skaaaaa-builder/text {"content":"Tiêu Đề Lớn","tagName":"h1","tailwindClasses":"text-4xl md:text-5xl font-black text-text dark:text-text tracking-tight"} /-->

<!-- Paragraph Body -->
<!-- wp:skaaaaa-builder/text {"content":"Nội dung mô tả ngắn gọn súc tích.","tagName":"p","tailwindClasses":"text-base text-text/70 dark:text-text/70 leading-relaxed"} /-->
```

### 1.2. Button Atoms (100% Native - Cấm Dùng Inline Code)

```html
<!-- Primary Button CTA -->
<!-- wp:skaaaaa-builder/button {"text":"Bắt Đầu Ngay","url":"#action","tagName":"a","actionType":"link","tailwindClasses":"px-6 py-3 rounded-xl bg-primary text-black font-semibold shadow-md transition-all hover:opacity-90 active:scale-95 inline-flex items-center gap-2 cursor-pointer"} /-->

<!-- Dark / Light Mode Switch Button Native -->
<!-- wp:skaaaaa-builder/button {
  "text": "",
  "tagName": "button",
  "actionType": "theme_toggle",
  "hasIcon": true,
  "iconName": "dark_mode",
  "iconClasses": "text-lg text-text dark:text-text",
  "tailwindClasses": "w-10 h-10 rounded-xl border border-border dark:border-border bg-surface dark:bg-surface hover:scale-105 active:scale-95 transition-all flex items-center justify-center cursor-pointer shadow-sm p-0",
  "htmlAttributes": [
    {"key": "@click.prevent", "value": "$store.skaaaTheme.toggle()"},
    {"key": "x-data", "value": ""},
    {"key": "aria-label", "value": "Chuyển đổi giao diện Sáng / Tối"}
  ]
} /-->

<!-- Mobile Menu Hamburger Button Native -->
<!-- wp:skaaaaa-builder/button {
  "text": "",
  "tagName": "button",
  "actionType": "button",
  "hasIcon": true,
  "iconName": "menu",
  "iconClasses": "text-2xl text-text dark:text-text",
  "tailwindClasses": "lg:hidden w-10 h-10 rounded-xl border border-border dark:border-border bg-surface dark:bg-surface flex items-center justify-center cursor-pointer shadow-sm p-0",
  "htmlAttributes": [
    {"key": "@click.prevent", "value": "$store.nav.open = !$store.nav.open"},
    {"key": "aria-label", "value": "Mở thực đơn điều hướng"}
  ]
} /-->
```

### 1.3. SVG Icon Atom (`<!-- wp:skaaaaa-builder/svg ... /-->`)
> **QUY TẮC BẮT BUỘC:** Khối SVG phải luôn khai báo kích thước rõ ràng trong `tailwindClasses` (ví dụ `w-6 h-6`, `w-8 h-8`) để tránh lỗi SVG co lại còn 2px x 2px.
```html
<!-- Terminal Prompt Icon -->
<!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-5 h-5 text-primary shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M8 9l3 3-3 3m5 0h3\"/></svg>","tailwindClasses":"w-5 h-5 shrink-0"} /-->
```

### 1.4. Input & Select Atoms
```html
<!-- Input Text Atom -->
<!-- wp:skaaaaa-builder/input {"fieldName":"full_name","inputType":"text","placeholder":"Nguyễn Văn A...","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-surface dark:bg-surface border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->

<!-- Select Dropdown Atom -->
<!-- wp:skaaaaa-builder/select {"fieldName":"service","optionsText":"Tư Vấn:consulting\nTriển Khai:dev","displayStyle":"dropdown","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-surface dark:bg-surface border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
```

---

## TẦNG 2: MOLECULES (CÁC PHÂN TỬ CẤU THÀNH)

### 2.1. BrandLogo Molecule (Logo SVG + Tên thương hiệu + Pulse status)
```html
<!-- wp:skaaaaa-builder/container {"tagName":"a","tailwindClasses":"flex items-center gap-3 cursor-pointer group"} -->
  <!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-8 h-8 text-primary shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>","tailwindClasses":"w-8 h-8 shrink-0"} /-->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col"} -->
    <!-- wp:skaaaaa-builder/text {"content":"LÝ TẤT THÀNH","tagName":"span","tailwindClasses":"text-base font-bold text-text dark:text-text tracking-wider group-hover:text-primary transition-colors"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"HIGH LEVERAGE BUILDER","tagName":"span","tailwindClasses":"text-[10px] font-mono text-secondary uppercase tracking-widest"} /-->
  <!-- /wp:skaaaaa-builder/container -->
<!-- /wp:skaaaaa-builder/container -->
```

### 2.2. NavLink Molecule
```html
<!-- wp:skaaaaa-builder/text {"content":"Dự Án","tagName":"a","tailwindClasses":"text-sm font-medium text-text/80 dark:text-text/80 hover:text-primary dark:hover:text-primary transition-colors py-1"} /-->
```

### 2.3. FormField Molecule (Label + Input + Tự động Báo Lỗi)
```html
<!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col space-y-1.5 w-full"} -->
  <!-- wp:skaaaaa-builder/text {"content":"Địa Chỉ Email *","tagName":"label","tailwindClasses":"text-sm font-semibold text-text dark:text-text"} /-->
  <!-- wp:skaaaaa-builder/input {"fieldName":"email","inputType":"email","placeholder":"name@domain.com","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-background dark:bg-background border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
<!-- /wp:skaaaaa-builder/container -->
```

### 2.4. ProjectCard Molecule (Dành cho hiển thị Danh sách Dự án qua khối Loop)
```html
<!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"group p-6 rounded-2xl bg-surface dark:bg-surface border border-border dark:border-border hover:border-primary/40 transition-all duration-300 flex flex-col justify-between"} -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"space-y-4"} -->
    <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex items-center justify-between"} -->
      <!-- wp:skaaaaa-builder/text {"content":"LINUX / C++","tagName":"span","tailwindClasses":"px-2.5 py-1 rounded-md bg-primary/10 border border-primary/20 text-primary text-xs font-mono font-semibold"} /-->
      <!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-5 h-5 text-text/40 group-hover:text-text transition-colors shrink-0\" fill=\"currentColor\" viewBox=\"0 0 24 24\"><path d=\"M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z\"/></svg>","tailwindClasses":"w-5 h-5 shrink-0"} /-->
    <!-- /wp:skaaaaa-builder/container -->
    <!-- wp:skaaaaa-builder/text {"content":"fcitx5-lotus","tagName":"h3","tailwindClasses":"text-xl font-bold text-text dark:text-text group-hover:text-primary transition-colors"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Bộ gõ tiếng Việt hiệu năng cao cho Linux tối ưu bộ nhớ và độ trễ.","tagName":"p","tailwindClasses":"text-sm text-text/70 dark:text-text/70 line-clamp-3 leading-relaxed"} /-->
  <!-- /wp:skaaaaa-builder/container -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"pt-6 mt-6 border-t border-border dark:border-border flex items-center justify-between"} -->
    <!-- wp:skaaaaa-builder/button {"text":"Xem Wiki Docs","url":"#wiki","tagName":"a","tailwindClasses":"text-xs font-mono font-medium text-primary hover:opacity-80 transition-opacity inline-flex items-center gap-1.5"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"v1.4.0 • 280+ Stars","tagName":"span","tailwindClasses":"text-xs font-mono text-text/50 dark:text-text/50"} /-->
  <!-- /wp:skaaaaa-builder/container -->
<!-- /wp:skaaaaa-builder/container -->
```

---

## TẦNG 3: ORGANISMS (CẤU KIỆN ĐỘC LẬP TÁI SỬ DỤNG)

### 3.1. Organism `HeaderBar` (Chuẩn Token, Theme Toggle Native & Mobile Drawer)
```html
<!-- wp:skaaaaa-builder/container {"tagName":"header","tailwindClasses":"w-full bg-background/80 dark:bg-background/80 backdrop-blur-md border-b border-border dark:border-border sticky top-0 z-50"} -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"max-w-7xl mx-auto px-6 h-20 flex items-center justify-between"} -->
    
    <!-- BRAND LOGO -->
    <!-- wp:skaaaaa-builder/container {"tagName":"a","tailwindClasses":"flex items-center gap-3 cursor-pointer group"} -->
      <!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-8 h-8 text-primary shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>","tailwindClasses":"w-8 h-8 shrink-0"} /-->
      <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col"} -->
        <!-- wp:skaaaaa-builder/text {"content":"LÝ TẤT THÀNH","tagName":"span","tailwindClasses":"text-base font-bold text-text dark:text-text tracking-wider group-hover:text-primary transition-colors"} /-->
        <!-- wp:skaaaaa-builder/text {"content":"MINIMAL INPUT • MAX OUTPUT","tagName":"span","tailwindClasses":"text-[9px] font-mono text-secondary tracking-widest"} /-->
      <!-- /wp:skaaaaa-builder/container -->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- NAV ITEMS (DESKTOP) -->
    <!-- wp:skaaaaa-builder/container {"tagName":"nav","tailwindClasses":"hidden lg:flex items-center gap-8"} -->
      <!-- wp:skaaaaa-builder/text {"content":"Trang Chủ","tagName":"a","tailwindClasses":"text-sm font-medium text-text dark:text-text hover:text-primary transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Giới Thiệu","tagName":"a","tailwindClasses":"text-sm font-medium text-text/80 dark:text-text/80 hover:text-primary transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Dự Án","tagName":"a","tailwindClasses":"text-sm font-medium text-text/80 dark:text-text/80 hover:text-primary transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Tài Liệu Wiki","tagName":"a","tailwindClasses":"text-sm font-medium text-text/80 dark:text-text/80 hover:text-primary transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Khóa Học","tagName":"a","tailwindClasses":"text-sm font-medium text-text/80 dark:text-text/80 hover:text-primary transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Blog","tagName":"a","tailwindClasses":"text-sm font-medium text-text/80 dark:text-text/80 hover:text-primary transition-colors"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- ACTIONS GROUP (DARK MODE TOGGLE + CTA + MOBILE HAMBURGER) -->
    <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex items-center gap-3"} -->
      
      <!-- NÚT TOGGLE DARK MODE NATIVE -->
      <!-- wp:skaaaaa-builder/button {
        "text": "",
        "tagName": "button",
        "actionType": "theme_toggle",
        "hasIcon": true,
        "iconName": "dark_mode",
        "iconClasses": "text-lg text-text dark:text-text",
        "tailwindClasses": "w-10 h-10 rounded-xl border border-border dark:border-border bg-surface dark:bg-surface hover:scale-105 active:scale-95 transition-all flex items-center justify-center cursor-pointer shadow-sm p-0",
        "htmlAttributes": [
          {"key": "@click.prevent", "value": "$store.skaaaTheme.toggle()"},
          {"key": "x-data", "value": ""},
          {"key": "aria-label", "value": "Chuyển đổi giao diện Sáng / Tối"}
        ]
      } /-->

      <!-- NÚT CTA (DESKTOP) -->
      <!-- wp:skaaaaa-builder/button {"text":"Kết Nối Ngay","url":"#contact","tagName":"a","tailwindClasses":"hidden sm:inline-flex px-5 py-2.5 rounded-xl bg-primary text-black text-sm font-semibold shadow-md transition-all hover:opacity-90 active:scale-95 cursor-pointer"} /-->

      <!-- NÚT HAMBURGER MOBILE NATIVE (lg:hidden) -->
      <!-- wp:skaaaaa-builder/button {
        "text": "",
        "tagName": "button",
        "actionType": "button",
        "hasIcon": true,
        "iconName": "menu",
        "iconClasses": "text-2xl text-text dark:text-text",
        "tailwindClasses": "lg:hidden w-10 h-10 rounded-xl border border-border dark:border-border bg-surface dark:bg-surface flex items-center justify-center cursor-pointer shadow-sm p-0",
        "htmlAttributes": [
          {"key": "@click.prevent", "value": "$store.nav.open = !$store.nav.open"},
          {"key": "aria-label", "value": "Mở thực đơn điều hướng"}
        ]
      } /-->

    <!-- /wp:skaaaaa-builder/container -->

  <!-- /wp:skaaaaa-builder/container -->

  <!-- MOBILE DRAWER CONTAINER (x-show="$store.nav && $store.nav.open") -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"lg:hidden border-t border-border dark:border-border bg-surface/95 dark:bg-surface/95 backdrop-blur-xl px-6 py-6 space-y-4","htmlAttributes":[{"key":"x-show","value":"$store.nav && $store.nav.open"},{"key":"style","value":"display: none;"}]} -->
    <!-- wp:skaaaaa-builder/text {"content":"Trang Chủ","tagName":"a","tailwindClasses":"block text-base font-medium text-text dark:text-text hover:text-primary py-1"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Giới Thiệu","tagName":"a","tailwindClasses":"block text-base font-medium text-text/80 dark:text-text/80 hover:text-primary py-1"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Dự Án","tagName":"a","tailwindClasses":"block text-base font-medium text-text/80 dark:text-text/80 hover:text-primary py-1"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Tài Liệu Wiki","tagName":"a","tailwindClasses":"block text-base font-medium text-text/80 dark:text-text/80 hover:text-primary py-1"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Khóa Học","tagName":"a","tailwindClasses":"block text-base font-medium text-text/80 dark:text-text/80 hover:text-primary py-1"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Blog","tagName":"a","tailwindClasses":"block text-base font-medium text-text/80 dark:text-text/80 hover:text-primary py-1"} /-->
    <!-- wp:skaaaaa-builder/button {"text":"Kết Nối Ngay","url":"#contact","tagName":"a","tailwindClasses":"w-full py-3 rounded-xl bg-primary text-black text-center text-sm font-semibold shadow-md block cursor-pointer"} /-->
  <!-- /wp:skaaaaa-builder/container -->

<!-- /wp:skaaaaa-builder/container -->
```

### 3.2. Organism `ContactForm` (Form Liên Hệ Chuẩn 3 Chân Vạc)
> Tự động lưu vào bảng phẳng `wp_skaaa_data_contact_submissions` và kích hoạt DAG Logic Engine:
```html
<!-- wp:skaaaaa-builder/container {"tagName":"form","isSkaaaForm":true,"formActionId":"insert_contact_submissions","usePersist":true,"tailwindClasses":"space-y-6 bg-surface dark:bg-surface p-8 rounded-2xl border border-border dark:border-border shadow-xl max-w-xl mx-auto w-full"} -->

  <!-- TIÊU ĐỀ FORM -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"space-y-1"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Liên Hệ Hợp Tác","tagName":"h3","tailwindClasses":"text-2xl font-bold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Để lại lời nhắn, tôi sẽ phản hồi trong 24 giờ.","tagName":"p","tailwindClasses":"text-sm text-text/70 dark:text-text/70"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- BANNER THÀNH CÔNG -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"p-4 rounded-xl bg-secondary/10 border border-secondary/30 text-secondary text-sm flex items-center gap-3","htmlAttributes":[{"key":"x-show","value":"success"},{"key":"style","value":"display: none;"}]} -->
    <!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-5 h-5 text-secondary shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"/></svg>","tailwindClasses":"w-5 h-5"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Cảm ơn bạn! Thông tin đã được gửi thành công.","tagName":"span","tailwindClasses":"font-medium"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- TRƯỜNG NHẬP LIỆU HỌ TÊN -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col space-y-1.5"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Họ và Tên *","tagName":"label","tailwindClasses":"text-sm font-semibold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/input {"fieldName":"full_name","inputType":"text","placeholder":"Nguyễn Văn A...","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-background dark:bg-background border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- TRƯỜNG NHẬP LIỆU EMAIL -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col space-y-1.5"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Địa Chỉ Email *","tagName":"label","tailwindClasses":"text-sm font-semibold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/input {"fieldName":"email","inputType":"email","placeholder":"name@domain.com","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-background dark:bg-background border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- TRƯỜNG PHÂN HỆ DỊCH VỤ -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col space-y-1.5"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Nhu Cầu Hợp Tác","tagName":"label","tailwindClasses":"text-sm font-semibold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/select {"fieldName":"service_type","displayStyle":"dropdown","optionsText":"Tư Vấn Kiến Trúc:consulting\nTriển Khai No-Code:nocode_dev\nĐào Tạo Doanh Nghiệp:training","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-background dark:bg-background border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- NÚT SUBMIT -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"pt-2"} -->
    <!-- wp:skaaaaa-builder/button {
      "text": "Gửi Thông Tin Hợp Tác",
      "tagName": "button",
      "actionType": "submit",
      "hasIcon": true,
      "iconName": "send",
      "iconPosition": "right",
      "iconClasses": "text-sm",
      "tailwindClasses": "w-full py-3 px-6 rounded-xl bg-primary text-black font-semibold shadow-md transition-all hover:opacity-90 active:scale-95 cursor-pointer disabled:opacity-50",
      "htmlAttributes": [
        {"key": ":disabled", "value": "isSubmitting"}
      ]
    } /-->
  <!-- /wp:skaaaaa-builder/container -->

<!-- /wp:skaaaaa-builder/container -->
```

### 3.3. Organism `FooterBar` (Dùng chung cho toàn bộ website)
```html
<!-- wp:skaaaaa-builder/container {"tagName":"footer","tailwindClasses":"w-full bg-surface dark:bg-surface border-t border-border dark:border-border py-16 text-text/70 dark:text-text/70"} -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-10 mb-12"} -->
    
    <!-- Cột 1: Thông tin & Định vị -->
    <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"md:col-span-2 space-y-4"} -->
      <!-- wp:skaaaaa-builder/text {"content":"LÝ TẤT THÀNH","tagName":"h3","tailwindClasses":"text-xl font-bold text-text dark:text-text"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Xây dựng hệ thống công nghệ và phễu tăng trưởng quy mô một người với nguồn lực tối thiểu và hiệu quả tối đa.","tagName":"p","tailwindClasses":"text-sm text-text/70 dark:text-text/70 max-w-sm leading-relaxed"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Tây Ninh, Việt Nam • contact@domain.com","tagName":"p","tailwindClasses":"text-xs font-mono text-secondary"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- Cột 2: Điều hướng nhanh -->
    <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col gap-2.5"} -->
      <!-- wp:skaaaaa-builder/text {"content":"PHÂN HỆ","tagName":"h4","tailwindClasses":"text-xs font-mono font-semibold text-text dark:text-text uppercase tracking-wider mb-2"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Dự Án Thực Chiến","tagName":"a","tailwindClasses":"text-sm hover:text-primary transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Kho Tài Liệu Wiki","tagName":"a","tailwindClasses":"text-sm hover:text-primary transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Học Viện LMS","tagName":"a","tailwindClasses":"text-sm hover:text-primary transition-colors"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- Cột 3: Kết nối mạng xã hội -->
    <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col gap-2.5"} -->
      <!-- wp:skaaaaa-builder/text {"content":"KẾT NỐI","tagName":"h4","tailwindClasses":"text-xs font-mono font-semibold text-text dark:text-text uppercase tracking-wider mb-2"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"GitHub Profile","tagName":"a","tailwindClasses":"text-sm hover:text-primary transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"LinkedIn / X","tagName":"a","tailwindClasses":"text-sm hover:text-primary transition-colors"} /-->
    <!-- /wp:skaaaaa-builder/container -->

  <!-- /wp:skaaaaa-builder/container -->

  <!-- Dòng Bản quyền Cuối Trang -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"max-w-7xl mx-auto px-6 pt-8 border-t border-border dark:border-border text-center text-xs font-mono text-text/50 dark:text-text/50"} -->
    <!-- wp:skaaaaa-builder/text {"content":"© 2026 Lý Tất Thành. Powered by Skaaa No-Code Ecosystem.","tagName":"p","tailwindClasses":"text-xs"} /-->
  <!-- /wp:skaaaaa-builder/container -->
<!-- /wp:skaaaaa-builder/container -->
```

---

## TẦNG 4: TEMPLATES & TẦNG 5: PAGES

### 4.1. Tầng 4 (Theme Templates):
- Header và Footer được đăng ký trong `wp_skaaa_data_sys_theme_templates` (location `header` & `footer`) liên kết với `organism_id` tương ứng qua cờ `--as-template=header|footer` của `db-tool.php`.
- Kiểm tra danh sách Theme Templates trong hệ thống bằng:
  ```bash
  php .agent/harness/db-tool.php --list-templates
  ```
- Khi người dùng truy cập bất kỳ trang nào (Trang chủ, Wiki, LMS, Blog), Theme và `Skaaa_Virtual_Wrapper` tự động nạp Header và Footer từ `sys_organisms`.

### 5.1. Tầng 5 (Pages):
- **Post Content chỉ chứa nội dung riêng:** Nội dung `post_content` của một trang tuyệt đối không bọc lại Header/Footer tĩnh. Nó chỉ gồm:
  - Hero Section (USP & Headline).
  - Phân hệ tính năng / Showcase / Dynamic Grid (kết nối khối `loop` với bảng phẳng MySQL `wp_skaaa_data_*`).
  - Lead Form hoặc Call-to-action riêng của trang (sử dụng Form Engine 3 Chân Vạc).

---

## BẢNG TIỀN KIỂM CÚ PHÁP TAILWIND JIT (jit-tool.php)

Trước khi đóng gói component hoặc bàn giao trang, **BẮT BUỘC** chạy kiểm tra cú pháp:

```bash
# Quét toàn bộ component vừa ráp
php .agent/harness/jit-tool.php --scan="path/to/component.html"

# Thẩm định cú pháp khối Gutenberg
php .agent/harness/block-tool.php --validate="path/to/component.html"
```
