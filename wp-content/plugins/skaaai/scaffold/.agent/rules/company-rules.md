# QUY CHẾ & VĂN HÓA DOANH NGHIỆP (company-rules.md)
@target: Toàn bộ Nhân viên AI trong Xưởng | @authority: Giám Đốc (Người Dùng)

## 1. NGUYÊN TẮC QUẢN TRỊ TỐI THƯỢNG
- **Giám Đốc (Người Dùng):** Là chủ dự án, người ra quyết định tối cao về mọi khía cạnh thẩm mỹ, tính năng và nội dung.
- **Tác phong nhân viên:** Tuyệt đối không tự biên tự diễn. Phải báo cáo, phỏng vấn và xin phê duyệt của Giám Đốc ở các điểm chạm quan trọng trước khi hành động.

## 2. THIẾT QUÂN LUẬT BẮT BUỘC (NON-NEGOTIABLES)

### Điều 1: Lệnh Cấm Làm Mù (Anti-Blindness Directive)
- CẤM TUYỆT ĐỐI nhân viên tự ý gõ code, sinh block hoặc tạo trang khi chưa có bản Brief được Giám Đốc duyệt.
- Bắt buộc Phòng Account phải phỏng vấn Giám Đốc về: **Logo, Hình ảnh banner, Menu Header, Footer info, Copywriting và CSDL** theo đúng quy trình `1-client-intake.md`.

### Điều 2: Lệnh Kỷ Luật Ngân Sách (Anti-Token-Burn Directive)
- Ý thức bảo vệ ngân sách token của Giám Đốc là ưu tiên hàng đầu.
- CẤM các subagent chạy vòng lặp vô bổ, cấm ngồi loay hoay căn chỉnh CSS vụn vặt hàng giờ đồng hồ.
- Mọi khâu phải làm dứt khoát trong 1 nhịp (One-shot execution).

### Điều 3: Tiêu Chuẩn Kỹ Thuật Hệ Sinh Thái SKAAA
- **Giao diện (Skaaa Design):** 100% dùng comment Gutenberg thuần của 15 khối Chuẩn (`container`, `text`, `image`, `button`, `icon`, `video`, `svg`, `list`, `list-item`, `input`, `select`, `form-rich-text`, `organism-ref`, `loop`, `code`). CẤM bọc thẻ HTML thô (`<div>`, `<main>`, `<section>`) bên ngoài comment block vì sẽ gây lỗi Invalid Content. 100% style quy về class Tailwind v4 trong thuộc tính `tailwindClasses` (cấm `classes`), cấm dùng inline `style="..."`.
- **Dữ liệu (Skaaa Data Pro):** 100% dữ liệu ứng dụng lưu vào bảng phẳng MySQL `wp_skaaa_data_*`. Khai tử hoàn toàn `wp_postmeta`. CẤM gõ lệnh `mysql` trực tiếp trên terminal làm treo shell.
- **Tương tác (Skaaapine / Alpine.js):** Mọi sự kiện click bắt buộc dùng `@click.prevent`. Giao tiếp qua `Alpine.store` toàn cục, không tiêm `x-data=""` vào block con gây scope shadowing.

### Điều 4: Thiết Quân Luật Database-First (Tokens & Cấu Kiện Tái Sử Dụng)
- **CẤM TUYỆT ĐỐI** chỉ viết tài liệu markdown chay mà bỏ qua việc nạp vào CSDL phẳng MySQL (`wp_skaaa_data_sys_presets`, `wp_skaaa_data_sys_organisms`). File markdown chỉ là bản ghi chép/bàn giao, hệ thống website không thể chạy bằng file `.md`.
- **Design Tokens:** Sau khi sếp duyệt bảng màu & font chữ, bắt buộc dùng `php .agent/harness/db-tool.php --set-tokens="..."` để nạp vào `wp_skaaa_data_sys_presets` và biên dịch ra `tokens.json` trước, sau đó mới đồng bộ ghi chép lại vào `brand-guidelines.md`.
- **Chuẩn Hóa Tên Token (Schema Match 100%):** Bắt buộc dùng đúng tên trường trong CSDL:
  - `token_brand`: `LogoUrl`
  - `token_color` (Light): `Primary`, `Secondary`, `Tertiary`, `Surface`, `Background`, `Text`, `Border`, `Success`, `Warning`, `Error`, `Info`
  - `token_dark_color` (Dark): `Primary`, `Secondary`, `Tertiary`, `Surface`, `Background`, `Text`, `Border`, `Success`...
  - `token_font`: `PrimaryFont`, `SecondaryFont`, `MonoFont`
  - *Cấm tuyệt đối tự chế các tên lý thuyết trôi nổi không có trong Schema (Canvas Base, Hairline Border...).*
- **Cấu Kiện Tái Sử Dụng & Theme Builder:** Các cấu kiện dùng chung xuyên suốt toàn site (`HeaderBar`, `FooterBar`) bắt buộc lưu và kích hoạt Theme Template bằng `php .agent/harness/db-tool.php --save-organism="..." --name="HeaderBar" --category="header" --as-template=header` (hoặc `--as-template=footer`). Lệnh này vừa lưu Organism vào `wp_skaaa_data_sys_organisms`, vừa tự động đăng ký bản ghi toàn cục vào `wp_skaaa_data_sys_theme_templates` để xuất hiện trực quan trên Skaaa Theme Builder (`wp-admin/admin.php?page=skaaa-theme-builder`). CẤM TUYỆT ĐỐI nhét cứng (hardcode) Header/Footer vào `post_content` của từng trang riêng lẻ. Nội dung trang con (Page Body) chỉ chứa phần thân (Hero, Dynamic Loop Grid, Form).

### Điều 5: Thiết Quân Luật Chặn Đứng Tự Ý Nhảy Phase (Anti-Runaway Directive)
- **CẤM TUYỆT ĐỐI** nhân viên "cầm đèn chạy trước ô tô", tự ý nhảy cóc sang các phase kế tiếp (Trang Chủ, Dự Án, Wiki, LMS...) khi chưa có chỉ thị duyệt bằng văn bản từ Giám Đốc.
- **CẤM NGỘ NHẬN:** Tuyệt đối không được coi tín hiệu hệ thống, callback công cụ hay việc hoàn tất tạo file/artifact báo cáo là sự đồng ý của Giám Đốc.
- **DỪNG LƯỢT TUYỆT ĐỐI:** Sau khi xuất bản sản phẩm của một phân hệ (kèm kết quả kiểm định 3 lớp), nhân viên PHẢI DỪNG TOÀN BỘ HOẠT ĐỘNG, gửi link nghiệm thu và ĐỢI TIN NHẮN (Chat Prompt) từ Giám Đốc mới được phép thực thi phân hệ tiếp theo.

### Điều 6: Thiết Quân Luật No-Inline-Code, Native Form & Design-Tokens-First
- **Nghiêm Cấm Dùng Inline Code Cho UI Native:** Tuyệt đối CẤM dùng block `wp:skaaaaa-builder/code` để nhét mã HTML/JS thô của Button, SVG Icon/Logo, Hình ảnh (`<img>`), Video (`<video>`/`<iframe>`), Dark Mode Toggle hay Form. Hành vi này làm hỏng tính trực quan No-Code (biến thành hộp đen sì trong Gutenberg Editor) và vô hiệu hóa cơ chế quét Media Query của trình biên dịch Tailwind JIT.
- **Image & Video Native (Cấm thẻ `<img>`/`<video>` thô):** Hình ảnh bắt buộc dùng `wp:skaaaaa-builder/image` với `url`, `alt`, `aspectRatio` (`"aspect-[460/580]"`, `"aspect-video"`, `"aspect-auto"` hoặc `"aspect-square"`), `objectFit` (`"object-cover"`). Tuyệt đối không dùng thẻ `<code>` nhét `<img>`. Khi ảnh không phải tỉ lệ 1:1 vuông, bắt buộc truyền `aspectRatio: "aspect-auto"` hoặc tỉ lệ tùy biến tương ứng để tránh bị render engine tự động fallback về `aspect-square` làm méo/cắt ảnh. Video bắt buộc dùng `wp:skaaaaa-builder/video` (`videoType: "youtube"|"vimeo"|"local"`). Icon bắt buộc dùng `wp:skaaaaa-builder/icon` (`iconName`).
- **Button Native:** Chuyển đổi Dark Mode bắt buộc dùng `wp:skaaaaa-builder/button` với `actionType: "theme_toggle"`, icon `dark_mode` và directive `@click.prevent="$store.skaaaTheme.toggle()"`. Nút mở Mobile Menu bắt buộc dùng block `button` với `actionType: "button"`, icon `menu`, class `lg:hidden` và `@click.prevent="$store.nav.open = !$store.nav.open"`.
- **Form Engine 3 Chân Vạc:** Form bắt buộc dựng bằng `container` (`tagName: "form"`, `isSkaaaForm: true`, `formActionId: "insert_{table_slug}"`) kết hợp các block nguyên tử `input`, `select`, `form-rich-text` và `button` (`actionType: "submit"`). CẤM nhét thẻ `<form>` HTML thô.
- **Design Tokens First:** 100% màu sắc bắt buộc sử dụng họ class theo Design Tokens (`bg-background`, `bg-surface`, `border-border`, `text-text`, `bg-primary`, `text-primary`, `text-secondary`, `border-primary`...). Tuyệt đối CẤM hardcode class màu cụ thể (`bg-amber-500`, `text-amber-400`, `bg-[#10131a]`, `border-[#1e2433]`) làm tê liệt khả năng đổi màu nhận diện tập trung của hệ thống.
