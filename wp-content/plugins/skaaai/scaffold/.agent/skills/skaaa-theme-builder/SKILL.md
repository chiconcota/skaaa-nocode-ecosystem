---
name: skaaa-theme-builder
description: Kỹ năng chuyên sâu cắt và cấu trúc Theme Templates (Header, Footer, Sidebar, Single, Archive), quản lý Skaaa Organisms và thiết lập điều kiện hiển thị toàn site (Entire Site) qua Smart Virtual Wrapper.
---

# SKILL: SKAAA THEME BUILDER & ORGANISMS ARCHITECTURE

Kỹ năng này hướng dẫn AI Agent cách xây dựng cấu trúc giao diện toàn diện cho một Website: cắt Header, Footer, Sidebar thành các khối tái sử dụng (**Skaaa Organisms**) và thiết lập điều kiện hiển thị toàn site (**Theme Templates**) thông qua cỗ máy Smart Virtual Wrapper.

---

## 1. NGUYÊN TẮC VÀNG VỀ CẤU TRÚC THEME
1. **Không sửa file PHP của Theme:** Theme `Skaaa Canvas` là một Blank Slate (0 CSS/JS rác). Tuyệt đối không can thiệp vào `header.php`, `footer.php` hay `index.php` của theme.
2. **Kiến trúc Dual-Table (Bảng phẳng kép):**
   - Bảng 1: `wp_skaaa_data_sys_organisms` — Lưu trữ phần ruột mã HTML/JSON của khối giao diện (Header, Footer, Sidebar, Card...).
   - Bảng 2: `wp_skaaa_data_sys_theme_templates` — Quản lý vị trí (`location`), điều kiện hiển thị (`conditions`) và liên kết tới `organism_id`.
3. **Smart Virtual Wrapper (Cơ chế kẹp bánh mì):**
   - Khi kích hoạt Header và Footer toàn site, Virtual Wrapper sẽ tự động kẹp **Header ở đầu + Nội dung Body trang ở giữa + Footer ở cuối**.
   - Khi tạo trang con (Trang chủ, Giới thiệu, Liên hệ), AI **chỉ cần thiết kế phần thân (Body)**, không nhét lại Header/Footer vào nội dung bài viết.

---

## 2. QUY TRÌNH 4 BƯỚC XÂY DỰNG WEBSITE TỪ A-Z

### Bước 1: Cắt Global Header (Thanh điều hướng toàn site)
1. Thiết kế mã Atomic Blocks cho Header (Navbar, Logo, Menu links, CTA Button, Mobile Drawer).
2. Lưu vào bảng `wp_skaaa_data_sys_organisms` với `category = 'header'`.
3. Tạo một bản ghi trong `wp_skaaa_data_sys_theme_templates`:
   - `name`: "Main Global Header"
   - `location`: "header"
   - `organism_id`: ID của Organism vừa tạo
   - `conditions`: `{"rule": "entire_site"}`
   - `is_active`: 1

### Bước 2: Cắt Global Footer (Chân trang toàn site)
1. Thiết kế mã Atomic Blocks cho Footer (Thông tin công ty, liên kết nhanh, bản quyền, icon mạng xã hội).
2. Lưu vào bảng `wp_skaaa_data_sys_organisms` với `category = 'footer'`.
3. Tạo bản ghi trong `wp_skaaa_data_sys_theme_templates`:
   - `name`: "Main Global Footer"
   - `location`: "footer"
   - `organism_id`: ID của Organism vừa tạo
   - `conditions`: `{"rule": "entire_site"}`
   - `is_active`: 1

### Bước 3: Cắt Sidebar hoặc Sub-Organisms (Nếu có)
1. Thiết kế các khối phụ như: Sidebar Blog, Thanh tìm kiếm, Bảng giá mini.
2. Lưu vào bảng `wp_skaaa_data_sys_organisms` với `category = 'sidebar'` hoặc `'component'`.
3. Có thể tái sử dụng khối này bằng cách nhúng vào các template hoặc trang con.

### Bước 4: Thiết kế Nội dung các Trang con (Pages)
- Khi tạo Trang chủ (Home), Giới thiệu (About), Dịch vụ (Services)...:
- Cây block chỉ bắt đầu từ Section đầu tiên (Hero Section) cho tới Section cuối cùng trước Footer.
- Virtual Wrapper sẽ tự động ghép Header ở trên và Footer ở dưới khi render ra ngoài trình duyệt.

---

## 3. BẢNG MÃ ĐIỀU KIỆN HIỂN THỊ (`conditions` JSON)

| Nhu cầu hiển thị | Cấu trúc JSON cột `conditions` |
| :--- | :--- |
| **Toàn bộ website (Mặc định)** | `{"rule":"entire_site"}` |
| **Chỉ hiển thị trên các Trang (Pages)** | `{"rule":"singular","type":"page"}` |
| **Chỉ hiển thị trên Bài viết (Blog Posts)** | `{"rule":"singular","type":"post"}` |
| **Chỉ hiển thị trên Trang chỉ định** | `{"rule":"specific_page","ids":[10, 15]}` |
| **Trang danh mục / Lưu trữ (Archive)** | `{"rule":"archive"}` |
| **Trang lỗi 404** | `{"rule":"404"}` |

---

## 4. CODE MẪU PHP ĐỂ AI TỰ ĐỘNG TẠO THEME TEMPLATES & ORGANISMS

Khi AI cần tự động kích hoạt Header/Footer cho website qua script hoặc helper:

```php
global $wpdb;

$table_orgs      = $wpdb->prefix . 'skaaa_data_sys_organisms';
$table_templates = $wpdb->prefix . 'skaaa_data_sys_theme_templates';

// 1. Tạo Organism Header
$header_block_html = '<!-- wp:skaaaaa-builder/container {"tag":"header","classes":"w-full sticky top-0 z-50 bg-slate-950/80 backdrop-blur border-b border-white/10 px-6 py-4 flex items-center justify-between"} -->
  <!-- wp:skaaaaa-builder/text {"content":"SKAAA BRAND","tag":"div","classes":"text-xl font-bold text-white tracking-wider"} /-->
  <!-- wp:skaaaaa-builder/button {"text":"Bắt đầu ngay","url":"#contact","classes":"px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-medium transition-all"} /-->
<!-- /wp:skaaaaa-builder/container -->';

$wpdb->insert( $table_orgs, [
    'name'         => 'Main Global Header',
    'type'         => 'organism',
    'category'     => 'header',
    'html_content' => $header_block_html,
    'json_content' => wp_json_encode( [] ),
] );
$header_org_id = $wpdb->insert_id;

// 2. Gắn Header vào Theme Template toàn site
$wpdb->insert( $table_templates, [
    'name'        => 'Global Header Template',
    'location'    => 'header',
    'organism_id' => $header_org_id,
    'conditions'  => wp_json_encode( [ 'rule' => 'entire_site' ] ),
    'is_active'   => 1,
] );

// 3. Tạo Organism Footer
$footer_block_html = '<!-- wp:skaaaaa-builder/container {"tag":"footer","classes":"w-full bg-slate-950 border-t border-white/10 px-6 py-12 text-center text-slate-400 text-sm"} -->
  <!-- wp:skaaaaa-builder/text {"content":"© 2026 Skaaa Ecosystem. All rights reserved.","tag":"p","classes":"text-slate-500"} /-->
<!-- /wp:skaaaaa-builder/container -->';

$wpdb->insert( $table_orgs, [
    'name'         => 'Main Global Footer',
    'type'         => 'organism',
    'category'     => 'footer',
    'html_content' => $footer_block_html,
    'json_content' => wp_json_encode( [] ),
] );
$footer_org_id = $wpdb->insert_id;

// 4. Gắn Footer vào Theme Template toàn site
$wpdb->insert( $table_templates, [
    'name'        => 'Global Footer Template',
    'location'    => 'footer',
    'organism_id' => $footer_org_id,
    'conditions'  => wp_json_encode( [ 'rule' => 'entire_site' ] ),
    'is_active'   => 1,
] );
```

---

## 5. MẪU KHỐI ATOMIC CHUẨN CHO GLOBAL HEADER (CÓ MOBILE MENU VỚI SKAAAPINE)

```html
<!-- wp:skaaaaa-builder/container {"tag":"header","classes":"w-full sticky top-0 z-50 bg-slate-950/90 backdrop-blur-md border-b border-white/10 px-6 py-4","skaaapineAttrs":"x-data=\"{ openMobile: false }\""} -->
  <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"max-w-7xl mx-auto flex items-center justify-between"} -->
    
    <!-- Logo thương hiệu -->
    <!-- wp:skaaaaa-builder/text {"content":"SKAAA.AI","tag":"span","classes":"text-2xl font-black text-white tracking-wider cursor-pointer"} /-->

    <!-- Menu Links Desktop -->
    <!-- wp:skaaaaa-builder/container {"tag":"nav","classes":"hidden md:flex items-center gap-8 text-sm font-medium text-slate-300"} -->
      <!-- wp:skaaaaa-builder/text {"content":"Trang chủ","tag":"a","classes":"hover:text-blue-400 transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Khóa học","tag":"a","classes":"hover:text-blue-400 transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Tính năng","tag":"a","classes":"hover:text-blue-400 transition-colors"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"Liên hệ","tag":"a","classes":"hover:text-blue-400 transition-colors"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- Nút hành động chính Desktop -->
    <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"hidden md:flex items-center gap-4"} -->
      <!-- wp:skaaaaa-builder/button {"text":"Bắt đầu ngay","url":"#signup","classes":"px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-medium shadow-lg shadow-blue-600/25 transition-all hover:scale-[1.02]"} /-->
    <!-- /wp:skaaaaa-builder/container -->

    <!-- Nút Hamburger Mobile -->
    <!-- wp:skaaaaa-builder/container {"tag":"button","classes":"md:hidden text-white p-2 rounded-lg hover:bg-white/5","skaaapineAttrs":"@click.prevent=\"openMobile = !openMobile\""} -->
      <!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 6h16M4 12h16m-7 6h7\"></path></svg>","classes":"w-6 h-6"} /-->
    <!-- /wp:skaaaaa-builder/container -->

  <!-- /wp:skaaaaa-builder/container -->

  <!-- Drawer Menu Mobile (Alpine.js x-show) -->
  <!-- wp:skaaaaa-builder/container {"tag":"div","classes":"md:hidden pt-4 pb-2 border-t border-white/10 mt-3 flex flex-col gap-3","skaaapineAttrs":"x-show=\"openMobile\" x-transition"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Trang chủ","tag":"a","classes":"text-slate-300 hover:text-white py-1"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Khóa học","tag":"a","classes":"text-slate-300 hover:text-white py-1"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Tính năng","tag":"a","classes":"text-slate-300 hover:text-white py-1"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Liên hệ","tag":"a","classes":"text-slate-300 hover:text-white py-1"} /-->
    <!-- wp:skaaaaa-builder/button {"text":"Bắt đầu ngay","url":"#signup","classes":"mt-2 w-full text-center py-2.5 rounded-xl bg-blue-600 text-white font-medium"} /-->
  <!-- /wp:skaaaaa-builder/container -->

<!-- /wp:skaaaaa-builder/container -->
```
