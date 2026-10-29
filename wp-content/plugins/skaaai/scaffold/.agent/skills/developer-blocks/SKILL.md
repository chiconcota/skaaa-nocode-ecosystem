---
name: developer-blocks
description: Tra cứu cú pháp chuẩn 15 Atomic & Functional Blocks, CSDL Bảng Phẳng MySQL (Data Pro), Dynamic Loop Binding, Form Engine 3 Chân Vạc và bộ CLI Tools
---

# ĐỒ NGHỀ LẬP TRÌNH: ATOMIC BLOCKS, FORM ENGINE 3 CHÂN VẠC & CSDL BẢNG PHẲNG (/developer-blocks)
@phòng_ban: Developer & Backend Architecture | @target: Core Developer & Block Synthesizer
@trigger: Khi viết block markup, tạo form, tạo bảng CSDL, cấu hình loop binding hoặc gõ /developer-blocks

> **TRIẾT LÝ BẤT BIẾN:**
> 1. **No-Postmeta Rule:** Skaaa hoạt động trên nền tảng Bảng Phẳng (Flat Tables `skaaa_data_*`), TUYỆT ĐỐI KHÔNG dùng `wp_postmeta` để lưu trữ dữ liệu ứng dụng.
> 2. **Native Blocks First (Cấm Inline Code):** Hệ thống đã có đầy đủ 15 khối nguyên tử (`container`, `text`, `image`, `button`, `icon`, `video`, `svg`, `list`, `list-item`, `input`, `select`, `form-rich-text`, `organism-ref`, `loop`, `code`). TUYỆT ĐỐI CẤM dùng `wp:skaaaaa-builder/code` để nhét mã HTML/JS của Button, Form, SVG Logo, Hình ảnh `<img>`, Video `<video>`/`<iframe>` hoặc Dark Mode Switch.
> 3. **Form Engine 3 Chân Vạc:** Tận dụng sự hiệp đồng hoàn hảo giữa: **Skaaa No-Code Design** (Form UI + Alpine Controller `skaaaForm`) ➔ **Skaaa Logic Engine** (REST API `/submit` + Auto-DAG Workflow) ➔ **Skaaa Data Pro** (Lưu Flat Tables MySQL `wp_skaaa_data_*`).
> 4. **Clean Gutenberg Comments Only (Cấm Freeform HTML Comments):** 100% sử dụng token block chuẩn Gutenberg (`<!-- wp:... -->` và `<!-- /wp:... -->`). TUYỆT ĐỐI CẤM chèn các comment HTML tự do (`<!-- 1. HEADER... -->`, `<!-- ARTICLE CARD -->`) bên trong hoặc giữa các khối. Gutenberg Parser sẽ xem đó là Comment/Freeform Node lạ, gây lỗi 'Block validation failed: Expected end of content, instead saw Comment node' và kích hoạt nút 'Attempt recovery'.

---

## 1. CÚ PHÁP CHUẨN 15 ATOMIC & FUNCTIONAL BLOCKS (SCHEMA CHÍNH XÁC 100%)

| Khối (Block) | Loại | Cú pháp Comment | Các thuộc tính hợp lệ (Schema Chuẩn) |
| :--- | :--- | :--- | :--- |
| **`container`** | Đóng mở | `<!-- wp:skaaaaa-builder/container {...} -->...<!-- /wp:skaaaaa-builder/container -->` | `tagName`: `"div"|"section"|"header"|"footer"|"nav"|"form"|"a"`<br>`tailwindClasses`: `"..."`<br>`isSkaaaForm`: `false\|true`<br>`formActionId`: `"insert_[slug]"` (Khi `tagName: "form"`)<br>`usePersist`: `false\|true` (Lưu nháp form vào localStorage) |
| **`text`** | Tự đóng | `<!-- wp:skaaaaa-builder/text {...} /-->` | `content`: `"..."` (HTML inline an toàn)<br>`tagName`: `"h1"|"h2"|"h3"|"p"|"span"|"label"|"a"`<br>`tailwindClasses`: `"..."` |
| **`image`** | Tự đóng | `<!-- wp:skaaaaa-builder/image {...} /-->` | `url`: `"..."`, `alt`: `"..."`, `id`: `123`<br>`aspectRatio`: `"aspect-auto"|"aspect-square"|"aspect-video"|"aspect-[460/580]"`<br>`objectFit`: `"object-cover"|"object-contain"`<br>`link`: `{"url":"...","target":"_self"|"_blank"}`<br>`htmlAttributes`: `[{"key":"onerror","value":"..."},{"key":"loading","value":"lazy"}]`<br>`tailwindClasses`: `"..."`<br>*(BẮT BUỘC: Nếu ảnh không phải tỉ lệ 1:1, phải truyền `aspectRatio: "aspect-auto"` hoặc `"aspect-[W/H]"` để tránh bị render engine tự động fallback về `aspect-square` làm cắt ảnh)* |
| **`button`** | Tự đóng | `<!-- wp:skaaaaa-builder/button {...} /-->` | `text`: `"..."`, `url`: `"..."`, `tagName`: `"a"|"button"`<br>`actionType`: `"link"|"submit"|"theme_toggle"|"logic_api"`<br>`hasIcon`: `false\|true`, `iconName`: `"..."` (Material Symbols)<br>`iconPosition`: `"left"|"right"`, `iconClasses`: `"..."`<br>`htmlAttributes`: `[{"key": "...", "value": "..."}]`<br>`tailwindClasses`: `"..."` |
| **`icon`** | Tự đóng | `<!-- wp:skaaaaa-builder/icon {...} /-->` | `iconName`: `"..."` (Material Symbols: `star`, `menu`, `check_circle`, `rocket_launch`)<br>`tailwindClasses`: `"text-2xl text-primary ..."` |
| **`video`** | Tự đóng | `<!-- wp:skaaaaa-builder/video {...} /-->` | `url`: `"..."`, `videoType`: `"youtube"|"vimeo"|"local"`<br>`aspectRatio`: `"aspect-video"|"aspect-square"`<br>`controls`: `true\|false`, `autoplay`: `false\|true`, `loop`: `false\|true`, `muted`: `false\|true`<br>`posterUrl`: `"..."`, `tailwindClasses`: `"..."` |
| **`svg`** | Tự đóng | `<!-- wp:skaaaaa-builder/svg {...} /-->` | `svgCode`: `"<svg ...>...</svg>"`<br>`tailwindClasses`: `"w-6 h-6 ..."` *(BẮT BUỘC có `w-* h-*` để không bị co 2px)* |
| **`list`** | Đóng mở | `<!-- wp:skaaaaa-builder/list {...} -->...<!-- /wp:skaaaaa-builder/list -->` | `listType`: `"ul"|"ol"`<br>`tailwindClasses`: `"space-y-2 list-none ..."`<br>*(Container chỉ chứa các khối con `skaaaaa-builder/list-item`)* |
| **`list-item`** | Đóng mở | `<!-- wp:skaaaaa-builder/list-item {...} -->...<!-- /wp:skaaaaa-builder/list-item -->` | `tailwindClasses`: `"flex items-center gap-3 ..."`<br>*(Chứa inner blocks như `text`, `icon`, `image`)* |
| **`input`** | Tự đóng | `<!-- wp:skaaaaa-builder/input {...} /-->` | `fieldName`: `"..."` (Tên cột DB), `fieldId`: `"..."`<br>`inputType`: `"text"|"email"|"number"|"password"|"date"|"tel"|"checkbox"|"radio"`<br>`placeholder`: `"..."`, `fieldValue`: `"..."`<br>`isRequired`: `false\|true`, `isChecked`: `false\|true`<br>`tailwindClasses`: `"..."`<br>*(Tự động inject `x-model="fields.[name]"` và render báo lỗi)* |
| **`select`** | Tự đóng | `<!-- wp:skaaaaa-builder/select {...} /-->` | `fieldName`: `"..."` (Tên cột DB)<br>`optionsText`: `"Nhãn 1:val1\nNhãn 2:val2"`<br>`displayStyle`: `"dropdown"|"checkbox"|"radio"`<br>`isMultiple`: `false\|true`, `isRequired`: `false\|true`<br>`tailwindClasses`: `"..."` |
| **`form-rich-text`** | Tự đóng | `<!-- wp:skaaaaa-builder/form-rich-text {...} /-->` | `field`: `"noi_dung"` (Tên cột DB), `label`: `"Mô tả chi tiết"`<br>`tailwindClasses`: `"..."`<br>*(Soạn thảo WYSIWYG tích hợp Shadow Scratchpad vào Form Engine)* |
| **`organism-ref`** | Tự đóng | `<!-- wp:skaaaaa-builder/organism-ref {...} /-->` | `organismId`: `"12"` (ID Symbol trong `wp_skaaa_data_sys_organisms`)<br>`isSkaaaForm`: `false\|true`<br>*(Tham chiếu cấu kiện tái sử dụng Zero-Query rendering)* |
| **`loop`** | Đóng mở | `<!-- wp:skaaaaa-builder/loop {...} -->...<!-- /wp:skaaaaa-builder/loop -->` | `sourceTable`: `"wp_skaaa_data_[slug]"`, `limit`: `10`, `orderBy`: `"id"`, `order`: `"DESC"`, `tailwindClasses`: `"grid grid-cols-1 md:grid-cols-2 gap-6"` |
| **`code`** | Tự đóng | `<!-- wp:skaaaaa-builder/code {...} /-->` | `inlineCode`: `"..."`, `codeType`: `"inline"`<br>*(CHỈ DÙNG cho script analytics bên thứ ba hoặc tracking. CẤM TUYỆT ĐỐI dùng cho UI Native)* |

---

## 2. QUY CHUẨN CÁC KHỐI NATIVE ĐẶC THÙ (100% NO-CODE)

### 2.1. Khối Hình Ảnh Native (`skaaaaa-builder/image`)
Tuyệt đối KHÔNG dùng thẻ `<code>` nhét `<img>` thô. Block `image` đã hỗ trợ đầy đủ responsive, bo góc, aspect-ratio và link bọc:
> **Lưu ý sống còn về `aspectRatio`:**  
> `render.php` của Skaaa Image mặc định áp dụng class `aspect-square` (1:1). Do đó, nếu là ảnh chân dung (ví dụ 460x580) hoặc ảnh chữ nhật ngang (16:9), bạn **BẮT BUỘC** phải truyền thuộc tính `"aspectRatio": "aspect-[460/580]"` hoặc `"aspectRatio": "aspect-auto"` để ảnh không bị cắt méo!

```html
<!-- ẢNH CHÂN DUNG HERO CHUẨN TỈ LỆ NATIVE -->
<!-- wp:skaaaaa-builder/image {
  "url": "http://domain.local/wp-content/uploads/hero-portrait.jpg",
  "alt": "Ảnh Chân Dung Đại Diện",
  "aspectRatio": "aspect-[460/580]",
  "objectFit": "object-cover",
  "tailwindClasses": "w-full max-w-[460px] rounded-3xl shadow-2xl border border-border"
} /-->

<!-- AVATAR TRÒN CÓ LINK WRAPPER -->
<!-- wp:skaaaaa-builder/image {
  "url": "http://domain.local/wp-content/uploads/avatar.jpg",
  "alt": "Avatar Người Dùng",
  "aspectRatio": "aspect-square",
  "objectFit": "object-cover",
  "link": {"url": "/profile", "target": "_self"},
  "tailwindClasses": "w-16 h-16 rounded-full border-2 border-primary shadow-md hover:scale-105 transition-transform"
} /-->
```

### 2.2. Khối Icon Native (`skaaaaa-builder/icon`)
Hiển thị Material Symbols không cần nhúng SVG cồng kềnh:
```html
<!-- wp:skaaaaa-builder/icon {
  "iconName": "rocket_launch",
  "tailwindClasses": "text-3xl text-primary shrink-0"
} /-->
```

### 2.3. Khối Video Native (`skaaaaa-builder/video`)
Tự động chuyển đổi link YouTube/Vimeo thành iframe embed chuẩn hoặc phát video local mp4:
```html
<!-- wp:skaaaaa-builder/video {
  "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
  "videoType": "youtube",
  "aspectRatio": "aspect-video",
  "tailwindClasses": "w-full rounded-2xl shadow-xl overflow-hidden"
} /-->
```

### 2.4. Khối Danh Sách Native (`list` & `list-item`)
```html
<!-- wp:skaaaaa-builder/list {"listType":"ul","tailwindClasses":"space-y-3"} -->
  <!-- wp:skaaaaa-builder/list-item {"tailwindClasses":"flex items-center gap-3 text-text"} -->
    <!-- wp:skaaaaa-builder/icon {"iconName":"check_circle","tailwindClasses":"text-lg text-primary shrink-0"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Kiến trúc Flat DOM siêu nhẹ","tagName":"span","tailwindClasses":"text-sm font-medium"} /-->
  <!-- /wp:skaaaaa-builder/list-item -->
  <!-- wp:skaaaaa-builder/list-item {"tailwindClasses":"flex items-center gap-3 text-text"} -->
    <!-- wp:skaaaaa-builder/icon {"iconName":"check_circle","tailwindClasses":"text-lg text-primary shrink-0"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Tailwind CSS v4 JIT không tải thừa CSS","tagName":"span","tailwindClasses":"text-sm font-medium"} /-->
  <!-- /wp:skaaaaa-builder/list-item -->
<!-- /wp:skaaaaa-builder/list -->
```

### 2.5. Nút Chuyển Đổi Giao Diện Sáng / Tối (Toggle Dark Mode Native)
> Hoạt động trực tiếp với Alpine Store `Alpine.store('skaaaTheme')` của Skaaa Frontend Engine. Lật class `.dark` toàn site và lưu vào `localStorage`:
```html
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
```

### 2.6. Nút Mở Menu Mobile (Hamburger Button Native)
> Kết nối với store điều hướng toàn cục `$store.nav.open`. Ẩn hoàn toàn trên desktop bằng `lg:hidden`:
```html
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

### 2.7. Nút Gửi Form (Submit Button Native)
> Nằm trong container form, tự động kích hoạt tiến trình validate và submit của `skaaaForm`:
```html
<!-- wp:skaaaaa-builder/button {
  "text": "Gửi Thông Tin Ngay",
  "tagName": "button",
  "actionType": "submit",
  "hasIcon": true,
  "iconName": "send",
  "iconPosition": "right",
  "iconClasses": "text-sm",
  "tailwindClasses": "w-full md:w-auto px-8 py-3 rounded-xl bg-primary text-black font-semibold shadow-md transition-all hover:opacity-90 active:scale-95 cursor-pointer disabled:opacity-50"
} /-->
```

---

## 3. KIẾN TRÚC SKAAA FORM ENGINE 3 CHÂN VẠC (DESIGN + DATA PRO + LOGIC ENGINE)

### 3.1. Cơ chế Vận hành Tự động (The 3-Tier Synergy)
1. **Frontend UI (Skaaa No-Code Design):**
   - Container có `tagName: "form"`, `isSkaaaForm: true`, và `formActionId: "insert_{table_slug}"`.
   - Render engine tự động inject controller `x-data="skaaaForm('insert_{table_slug}', {persist: false})" @submit.prevent="submitForm()"`.
   - Các block `input` và `select` tự động liên kết 2 chiều qua `x-model="fields.{fieldName}"`.
   - Tự động hiển thị lỗi khi blur: `<span x-show="errors.{fieldName}">`.
2. **Logic Engine Router (Skaaa Logic Engine):**
   - Nhận payload tại REST API `POST /wp-json/skaaa-logic/v1/submit`.
   - **TỰ ĐỘNG HÓA AUTO-DAG:** Nhận diện tiền tố `insert_{table_slug}` ➔ Kiểm tra bảng phẳng `wp_skaaa_data_{table_slug}` ➔ **Tự động sinh đồ thị DAG (Trigger ➔ DBAction ➔ ClientResponse)** ghi vào `sys_workflows`. Bạn không cần cấu hình DAG thủ công cho các form CRUD cơ bản!
3. **Database Persistence (Skaaa Data Pro):**
   - Dữ liệu được làm sạch theo Schema Dictionary và ghi trực tiếp vào Bảng phẳng MySQL `wp_skaaa_data_{table_slug}`.
   - Trả về Event Bus `_skaaa_events` thông báo thành công cho client.

### 3.2. Mẫu Code Gutenberg Form Liên Hệ Hoàn Chỉnh (Chuẩn No-Code 100%)
```html
<!-- CONTAINER FORM CHÍNH: isSkaaaForm=true, formActionId kết nối bảng wp_skaaa_data_contact_submissions -->
<!-- wp:skaaaaa-builder/container {"tagName":"form","isSkaaaForm":true,"formActionId":"insert_contact_submissions","usePersist":true,"tailwindClasses":"space-y-6 bg-surface dark:bg-surface p-8 rounded-2xl border border-border dark:border-border shadow-xl max-w-xl mx-auto w-full"} -->

  <!-- TIÊU ĐỀ FORM -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"space-y-1"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Liên Hệ Hợp Tác","tagName":"h3","tailwindClasses":"text-2xl font-bold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Để lại thông tin, tôi sẽ phản hồi trong vòng 24 giờ.","tagName":"p","tailwindClasses":"text-sm text-text/70 dark:text-text/70"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- BANNER THÔNG BÁO THÀNH CÔNG (x-show="success") -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"p-4 rounded-xl bg-secondary/10 border border-secondary/30 text-secondary text-sm flex items-center gap-3","htmlAttributes":[{"key":"x-show","value":"success"},{"key":"style","value":"display: none;"}]} -->
    <!-- wp:skaaaaa-builder/svg {"svgCode":"<svg class=\"w-5 h-5 text-secondary shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"/></svg>","tailwindClasses":"w-5 h-5"} /-->
    <!-- wp:skaaaaa-builder/text {"content":"Cảm ơn bạn! Thông tin đã được gửi thành công.","tagName":"span","tailwindClasses":"font-medium"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- BANNER BÁO LỖI (x-show="status === 'error'") -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-500 text-sm flex items-center gap-3","htmlAttributes":[{"key":"x-show","value":"status === 'error'"},{"key":"style","value":"display: none;"}]} -->
    <!-- wp:skaaaaa-builder/text {"content":"Vui lòng kiểm tra lại các trường bắt buộc bên dưới.","tagName":"span","tailwindClasses":"font-medium"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- TRƯỜNG HỌ VÀ TÊN -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col space-y-1.5"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Họ và Tên *","tagName":"label","tailwindClasses":"text-sm font-semibold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/input {"fieldName":"full_name","inputType":"text","placeholder":"Nguyễn Văn A...","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-background dark:bg-background border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- TRƯỜNG EMAIL -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col space-y-1.5"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Địa Chỉ Email *","tagName":"label","tailwindClasses":"text-sm font-semibold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/input {"fieldName":"email","inputType":"email","placeholder":"name@domain.com","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-background dark:bg-background border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- TRƯỜNG PHÂN LOẠI NHU CẦU (SELECT) -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col space-y-1.5"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Nhu Cầu Hợp Tác","tagName":"label","tailwindClasses":"text-sm font-semibold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/select {"fieldName":"service_type","displayStyle":"dropdown","optionsText":"Tư Vấn Kiến Trúc Phần Mềm:consulting\nXây Dựng No-Code System:nocode_dev\nĐào Tạo Doanh Nghiệp:training","isRequired":true,"tailwindClasses":"w-full px-4 py-2.5 bg-background dark:bg-background border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- TRƯỜNG NỘI DUNG YÊU CẦU -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex flex-col space-y-1.5"} -->
    <!-- wp:skaaaaa-builder/text {"content":"Nội Dung Tin Nhắn","tagName":"label","tailwindClasses":"text-sm font-semibold text-text dark:text-text"} /-->
    <!-- wp:skaaaaa-builder/input {"fieldName":"message","inputType":"text","placeholder":"Mô tả ngắn gọn dự án của bạn...","isRequired":false,"tailwindClasses":"w-full px-4 py-2.5 bg-background dark:bg-background border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all"} /-->
  <!-- /wp:skaaaaa-builder/container -->

  <!-- NÚT GỬI KÈM TRẠNG THÁI LOADING -->
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

---

## 4. CƠ CHẾ DYNAMIC DATA BINDING (KẾT NỐI BẢNG PHẲNG QUA KHỐI LOOP)

Thay vì hardcode dữ liệu tĩnh vào Post, Developer sử dụng khối `loop` để tự động kéo dữ liệu từ bảng phẳng MySQL ra:

```html
<!-- KHỐI LOOP: Tự động lặp qua các dòng trong bảng wp_skaaa_data_projects -->
<!-- wp:skaaaaa-builder/loop {"sourceTable":"wp_skaaa_data_projects","limit":6,"orderBy":"id","order":"DESC","tailwindClasses":"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"} -->

  <!-- MOLECULE TEMPLATE: Chuẩn Design Tokens (bg-surface, border-border, text-text) -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"p-6 rounded-2xl bg-surface dark:bg-surface border border-border dark:border-border hover:border-primary/40 transition-all flex flex-col justify-between"} -->
    <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"space-y-3"} -->
      <!-- wp:skaaaaa-builder/text {"content":"{title}","tagName":"h3","tailwindClasses":"text-xl font-bold text-text dark:text-text"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"{description}","tagName":"p","tailwindClasses":"text-sm text-text/70 dark:text-text/70 line-clamp-3 leading-relaxed"} /-->
    <!-- /wp:skaaaaa-builder/container -->
    <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"pt-4 mt-4 border-t border-border dark:border-border flex items-center justify-between"} -->
      <!-- wp:skaaaaa-builder/button {"text":"Chi Tiết Dự Án","url":"{github_url}","tagName":"a","tailwindClasses":"text-xs font-mono text-primary hover:opacity-80 inline-flex items-center gap-1"} /-->
      <!-- wp:skaaaaa-builder/text {"content":"{tech_stack}","tagName":"span","tailwindClasses":"text-xs font-mono text-text/50 dark:text-text/50"} /-->
    <!-- /wp:skaaaaa-builder/container -->
  <!-- /wp:skaaaaa-builder/container -->

<!-- /wp:skaaaaa-builder/loop -->
```

---

### 4.2. CƠ CHẾ UNIVERSAL DYNAMIC BINDING (SKAAAFX) CHO TEMPLATE BÀI VIẾT (SINGLE POST & DETAIL TEMPLATES)

> **THIẾT QUÂN LUẬT NO-CORE-BLOCKS:** Khi tạo Single Post Template, Blog Detail, hay bất kỳ Template bài viết nào, **100% BẮT BUỘC dùng Atomic Blocks của Skaaa kết hợp thuộc tính `skaaaDynamicBinding`**. TUYỆT ĐỐI CẤM dùng các block mặc định của WordPress (`core/post-title`, `core/post-featured-image`, `core/post-author`, `core/post-date`) vì chúng phá vỡ hệ thống Design Tokens và trình biên dịch Tailwind JIT.

#### A. Danh Mục Biến SkaaaFX WordPress Core:
* `data = [post.title]` : Tiêu đề bài viết hiện tại.
* `data = [post.featured_image]` (hoặc `[post.thumbnail]`) : URL ảnh đại diện (Featured Image).
* `data = [post.author]` : Tên tác giả hiển thị.
* `data = [post.author_avatar]` : URL ảnh đại diện (Avatar) của tác giả.
* `data = [post.date]` : Ngày đăng bài viết.
* `data = [post.excerpt]` : Tóm tắt bài viết.
* `data = [post.permalink]` (hoặc `[post.url]`) : Đường dẫn URL của bài viết.
* `data = [post.meta.<custom_field>]` : Dữ liệu trường tùy biến từ ACF/SCF/Postmeta.
* `data = [user.name]` : Tên người dùng đang đăng nhập.
* `data = [site.title]` / `data = [site.url]` : Thông tin website.

#### B. Mẫu Code Gutenberg Chuẩn Cho Single Post Template:
```html
<!-- TIÊU ĐỀ BÀI VIẾT: Gán động data = [post.title] -->
<!-- wp:skaaaaa-builder/text {
  "tagName": "h1",
  "content": "[Tiêu đề bài viết]",
  "tailwindClasses": "text-3xl md:text-5xl font-black text-text dark:text-text tracking-tight mb-4",
  "skaaaDynamicBinding": {"script": "data = [post.title]"}
} /-->

<!-- METADATA TÁC GIẢ & NGÀY ĐĂNG -->
<!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"flex items-center gap-3 text-sm text-text/60 dark:text-text/60 mb-8"} -->
  <!-- wp:skaaaaa-builder/text {
    "tagName": "span",
    "content": "Tác giả",
    "tailwindClasses": "font-medium text-text dark:text-text",
    "skaaaDynamicBinding": {"script": "data = [post.author]"}
  } /-->
  <!-- wp:skaaaaa-builder/text {"content":"•","tagName":"span"} /-->
  <!-- wp:skaaaaa-builder/text {
    "tagName": "span",
    "content": "Ngày đăng",
    "skaaaDynamicBinding": {"script": "data = [post.date]"}
  } /-->
<!-- /wp:skaaaaa-builder/container -->

<!-- ẢNH ĐẠI DIỆN BÀI VIẾT (FEATURED IMAGE): Gán động data = [post.featured_image] -->
<!-- wp:skaaaaa-builder/image {
  "aspectRatio": "aspect-video",
  "objectFit": "object-cover",
  "tailwindClasses": "w-full rounded-2xl shadow-xl overflow-hidden mb-10 border border-border dark:border-border",
  "skaaaDynamicBinding": {"script": "data = [post.featured_image]"}
} /-->
```

---

### 4.3. HƯỚNG DẪN DỰNG TRANG BLOG / ARCHIVE FLAT DOM 0MS THEO MÔ HÌNH TƯƠNG TÁC PHẲNG A ⟷ B (ALPINE-AJAX DIRECTIVE)

> **MỤC TIÊU CỐT LÕI:** Khai tử hoàn toàn `core/query` và `core/query-pagination`. Tách rời 100% Bộ điều khiển (Part A - Search & Pagination) và Vùng hiển thị (Part B - Loop Posts Grid). Toàn bộ thao tác Tìm kiếm, Lọc và Chuyển trang diễn ra tức thì trong 0ms (SPA) nhờ thư viện siêu nhẹ `alpine-ajax` (~3.5 KB) mà vẫn bảo toàn 100% SEO, SSR và URL History (`/page/2/`, `?s=...`).

#### A. Kiến Trúc Decoupled Reactive Model (A ⟷ B):
```text
┌────────────────────────────────────────────────────────────────────────┐
│ PHẦN A1: BỘ ĐIỀU KHIỂN TÌM KIẾM & BỘ LỌC (CONTROLLERS)                 │
│ Form GET: x-target="blog-posts-grid blog-pagination" x-target.push     │
│ Input: @input.debounce.300ms="$el.form.requestSubmit()"                │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ AJAX Swap (0ms)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ PHẦN B: MỤC TIÊU HIỂN THỊ LƯỚI BÀI VIẾT (TARGET VIEW)                  │
│ id="blog-posts-grid" (wp:skaaaaa-builder/loop, sourceType="wp_posts")  │
└───────────────────────────────────▲────────────────────────────────────┘
                                    │ AJAX Swap (0ms)
┌───────────────────────────────────┴────────────────────────────────────┐
│ PHẦN A2: CỤM PHÂN TRANG ĐỘNG (PAGINATION CONTROLLER)                   │
│ id="blog-pagination" (wp:skaaaaa-builder/loop, sourceType="pagination")│
│ paginationTargetId="blog-posts-grid blog-pagination"                  │
│ Tự động sinh nút: Trang 1 (Active) ➔ Trang 2 ➔ Trang 3...              │
└────────────────────────────────────────────────────────────────────────┘
```

#### B. Quy Chuẩn Thuộc Tính Bắt Buộc:
1. **Multi-Target Swap (`x-target.push="ID1 ID2"`):**
   Luôn khai báo đồng thời 2 target ID: ID của Lưới bài viết (`blog-posts-grid`) và ID của Cụm phân trang (`blog-pagination`) để khi chuyển trang hoặc search thì cả danh sách bài viết lẫn trạng thái Active của nút trang đều được cập nhật đồng bộ!
2. **Naked ID Tokens (Cấm dấu `#`):**
   `x-target="blog-posts-grid"` (ĐÚNG) | `x-target="#blog-posts-grid"` (SAI - Lỗi đỏ Harness).
3. **Loop Tự Đóng Chuẩn Slot Conditional:**
   Khối `skaaaaa-builder/loop` dạng tự đóng `<!-- wp:skaaaaa-builder/loop {...} /-->` tự động biên dịch các Organisms mẫu:
   - Khi `sourceType: "wp_posts"`: Lặp qua danh sách bài viết WP và render thẻ Card nguyên tử.
   - Khi `sourceType: "pagination"`: Khớp AST điều kiện (`item.is_active` -> Nút cam active; `item.is_dots` -> Dấu ba chấm; `default` -> Nút link trang thường).

#### C. Mẫu Code Gutenberg Hoàn Chỉnh Cho Trang Blog Flat DOM 0ms:
```html
<!-- BỘ ĐIỀU KHIỂN A1: FORM TÌM KIẾM TỨC THÌ (INSTANT SEARCH) -->
<!-- wp:skaaaaa-builder/container {"tagName":"form","htmlAttributes":[{"key":"method","value":"get"},{"key":"action","value":""},{"key":"x-target.push","value":"blog-posts-grid blog-pagination"}],"tailwindClasses":"mb-10 flex flex-col md:flex-row gap-4 items-center justify-between"} -->
  <!-- wp:skaaaaa-builder/container {"tagName":"div","tailwindClasses":"relative w-full max-w-md"} -->
    <!-- wp:skaaaaa-builder/input {"fieldName":"s","inputType":"text","placeholder":"Tìm kiếm bài viết...","htmlAttributes":[{"key":"@input.debounce.300ms","value":"$el.form.requestSubmit()"}],"tailwindClasses":"w-full pl-11 pr-4 py-3 bg-surface dark:bg-surface border border-border dark:border-border rounded-xl text-text dark:text-text text-sm focus:outline-none focus:border-primary transition-all shadow-sm"} /-->
  <!-- /wp:skaaaaa-builder/container -->
  <!-- wp:skaaaaa-builder/text {"content":"Điều hướng 0ms tức thì qua Alpine-AJAX","tagName":"span","tailwindClasses":"text-xs font-mono text-text/50 dark:text-text/50"} /-->
<!-- /wp:skaaaaa-builder/container -->

<!-- MỤC TIÊU B: LƯỚI BÀI VIẾT NATIVE (UNIVERSAL WP_POSTS LOOP) -->
<!-- wp:skaaaaa-builder/loop {
  "sourceType": "wp_posts",
  "postsPerPage": 6,
  "targetId": "blog-posts-grid",
  "tailwindClasses": "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
} /-->

<!-- BỘ ĐIỀU KHIỂN A2: CỤM PHÂN TRANG ĐỘNG NATIVE (UNIVERSAL PAGINATION LOOP) -->
<!-- wp:skaaaaa-builder/loop {
  "sourceType": "pagination",
  "paginationTargetId": "blog-posts-grid blog-pagination",
  "paginationRange": 2,
  "includePrevNext": true,
  "tailwindClasses": "flex items-center justify-center gap-2 mt-12"
} /-->
```

---

## 5. MẪU PHP $WPDB TẠO BẢNG PHẲNG CHO FORM & DATA PRO

Tuyệt đối không gõ CLI `mysql` trực tiếp. Tạo bảng phẳng an toàn bằng `$wpdb` và `dbDelta`:

### 5.1. Bảng Tiếp Nhận Form Liên Hệ (`wp_skaaa_data_contact_submissions`)
```php
global $wpdb;
$table_name = $wpdb->prefix . 'skaaa_data_contact_submissions';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE {$table_name} (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    full_name varchar(255) NOT NULL,
    email varchar(191) NOT NULL,
    service_type varchar(100) DEFAULT '',
    message text,
    status varchar(50) DEFAULT 'new',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) {$charset_collate};";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta( $sql );
```

---

## 6. BỘ CLI TOOLS KIỂM THỬ: `db-tool.php` & `block-tool.php`

```bash
# 1. Quản trị Bảng phẳng & Theme Templates (db-tool.php)
php .agent/harness/db-tool.php --list-tables
php .agent/harness/db-tool.php --schema=wp_skaaa_data_contact_submissions
php .agent/harness/db-tool.php --list-templates

# 2. Thẩm định cú pháp Flat DOM, Input & Button (block-tool.php)
php .agent/harness/block-tool.php --validate="path/to/markup.html"
php .agent/harness/block-tool.php --render="path/to/markup.html"
php .agent/harness/block-tool.php --create-test-page="path/to/markup.html" --title="Trang Test Form"
```
