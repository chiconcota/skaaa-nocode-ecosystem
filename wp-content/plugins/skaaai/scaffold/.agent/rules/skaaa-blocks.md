# SKAAA ATOMIC BLOCKS & STYLING RULES
@target: Skaaa No-Code Ecosystem | @mode: Local Development

## 1. ATOMIC BLOCKS & FLAT DOM
- Chỉ sử dụng 6 khối Atomic Blocks chuẩn: `Container`, `Text`, `Button`, `SVG`, `Code`, `Loop`.
- Format comment Gutenberg thuần:
  - `Container`: `<!-- wp:skaaaaa-builder/container {"tag":"div","classes":"..."} -->...<!-- /wp:skaaaaa-builder/container -->`
  - `Text`: `<!-- wp:skaaaaa-builder/text {"content":"...","classes":"..."} /-->`
  - `Button`: `<!-- wp:skaaaaa-builder/button {"text":"...","classes":"..."} /-->`
  - `SVG`: `<!-- wp:skaaaaa-builder/svg {"svgCode":"...","classes":"..."} /-->`
  - `Code`: `<!-- wp:skaaaaa-builder/code {"code":"...","lang":"html"} /-->`
  - `Loop`: `<!-- wp:skaaaaa-builder/loop {"tableName":"...","query":"..."} -->...<!-- /wp:skaaaaa-builder/loop -->`
- Tuyệt đối KHÔNG chèn thẻ HTML tĩnh thô (`<main>`, `<div>`) lồng bên ngoài comment block khiến Gutenberg Validation báo lỗi Invalid Content.

## 2. SKAAAPINE (ALPINE.JS)
- Tất cả click handlers bắt buộc dùng `@click.prevent`.
- Giao tiếp chéo giữa các block độc lập thông qua `Alpine.store`.
- Tuyệt đối không tự ý tiêm `x-data=""` vào block con gây scope shadowing.

## 3. ZERO-CDN TAILWIND V4 JIT
- Toàn bộ styles phải quy về Tailwind CSS classes.
- Không viết inline CSS style attributes.
