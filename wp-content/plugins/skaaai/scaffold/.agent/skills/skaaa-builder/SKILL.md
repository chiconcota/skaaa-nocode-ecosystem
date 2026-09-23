---
name: skaaa-builder
description: Kỹ năng chuyên sâu xây dựng, chỉnh sửa và tối ưu giao diện No-code sử dụng các khối Atomic Blocks của Skaaa, Tailwind CSS v4 JIT offline và Skaaapine (Alpine.js).
---

# SKILL: SKAAA BUILDER (ATOMIC BLOCKS & DESIGN ENGINE)

Kỹ năng này hướng dẫn AI Agent cách tạo trang, sửa khối và xây dựng bố cục chuẩn Flat DOM trong hệ sinh thái SKAAA.

## 1. NGUYÊN TẮC VÀNG
1. **Comment Gutenberg thuần:** Chỉ dùng cấu trúc comment `<!-- wp:skaaaaa-builder/... {...} -->...<!-- /wp:skaaaaa-builder/... -->` hoặc dạng tự đóng `<!-- wp:... /-->`. Tuyệt đối không bọc thẻ `<main>` hoặc `<div>` thô bên ngoài gây lỗi Invalid Content.
2. **6 Atomic Blocks:**
   - `container`: Khối bọc layout (flex, grid, section). Thuộc tính: `tag`, `classes`, `skaaapineAttrs`.
   - `text`: Khối văn bản phẳng. Thuộc tính: `content`, `tag` (p, h1, h2, span), `classes`.
   - `button`: Khối nút bấm. Thuộc tính: `text`, `url`, `classes`, `skaaapineAttrs`.
   - `svg`: Khối vector icon. Thuộc tính: `svgCode`, `classes`.
   - `code`: Khối mã HTML/JS tùy biến. Thuộc tính: `code`, `lang`.
   - `loop`: Khối lặp dữ liệu từ bảng phẳng. Thuộc tính: `tableName`, `query`.
3. **Cú pháp Skaaapine (Alpine.js):**
   - Click handlers bắt buộc dùng `@click.prevent` để không bị nhảy trang hoặc thêm `#` vào URL.
   - Giao tiếp chéo giữa các block thông qua `Alpine.store` toàn cục.
   - Không tiêm `x-data=""` vào block con gây scope shadowing.

## 2. QUY TRÌNH THỰC HIỆN KHI NHẬN LỆNH TẠO TRANG
1. Đọc `.skaaa-ai/1-overview/design.md` để lấy bảng màu thương hiệu, font chữ và quy chuẩn bo góc.
2. Thiết kế cây block từ ngoài vào trong: Container ngoài cùng (Section) ➔ Container con (Wrapper/Grid) ➔ Atomic Blocks (Text, Button, SVG).
3. Đảm bảo toàn bộ class Tailwind đều hợp lệ với JIT offline.
