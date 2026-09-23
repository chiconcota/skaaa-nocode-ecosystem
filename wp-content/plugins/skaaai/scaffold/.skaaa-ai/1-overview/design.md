# DESIGN SYSTEM & BRAND IDENTITY GUIDELINES (design.md)
@site_name: Local Development Site | @target: AI Agent & Designer | @version: 1.0.0

Tài liệu này là **Nguồn Chân Lý Duy Nhất (Single Source of Truth)** về nhận diện thương hiệu, bảng màu, font chữ và các thành phần giao diện để AI Agent tuân thủ 100% khi sinh mã giao diện cho website.

---

## 1. TRIẾT LÝ THIẾT KẾ (DESIGN PHILOSOPHY)
- **Thẩm mỹ Cao cấp (Vibrant & Rich Aesthetics):** Tránh sử dụng màu đơn sắc thô kệch. Ưu tiên tông màu hiện đại, Dark Mode sang trọng kết hợp hiệu ứng viền mờ (glassmorphism) và gradient mượt mà.
- **Tương tác Sống động (Dynamic Micro-Animations):** Mọi nút bấm, thẻ bài viết đều phải có phản hồi thị giác khi hover (`transition-all duration-200 hover:scale-[1.02]`).
- **Khối phẳng Flat DOM:** Không sinh các thẻ `<div>` lồng nhau vô nghĩa. Khối `Container` quản lý inner blocks thuần khiết; `Text` và `Button` render phẳng.

---

## 2. BẢNG MÀU THƯƠNG HIỆU (DESIGN TOKENS - COLOR PALETTE)

| Token Name | Class Tailwind | Mã Hex / HSL | Mục đích sử dụng |
| :--- | :--- | :--- | :--- |
| **Primary** | `bg-primary`, `text-primary` | `#3b82f6` (Blue) | Nút bấm chính, điểm nhấn thương hiệu, link quan trọng |
| **Primary Hover** | `hover:bg-blue-600` | `#2563eb` | Trạng thái hover của nút bấm chính |
| **Secondary / Accent** | `bg-indigo-500`, `text-indigo-400` | `#6366f1` (Indigo) | Huy hiệu nổi bật, dải gradient, nút phụ |
| **Canvas / Background** | `bg-slate-950` | `#020617` | Màu nền tổng thể toàn trang (Sleek Dark Mode) |
| **Surface / Card** | `bg-slate-900/80` | `#0f172a` (mờ 80%) | Nền các thẻ bài viết, panel, modal, pricing card |
| **Border Muted** | `border-white/10` | `rgba(255,255,255,0.1)` | Đường viền tinh tế cho thẻ bài viết và thanh menu |
| **Text Heading** | `text-slate-50` | `#f8fafc` | Tiêu đề H1, H2, H3 (độ tương phản cao) |
| **Text Body** | `text-slate-300` | `#cbd5e1` | Văn bản đoạn văn, mô tả nội dung |
| **Text Muted** | `text-slate-500` | `#64748b` | Chú thích phụ, ngày tháng, thông tin tác giả |

---

## 3. QUY CHUẨN TYPOGRAPHY & FONT CHỮ
- **Font Heading & Body:** `font-sans` (Ưu tiên: Inter, Plus Jakarta Sans, Outfit).
  - Tiêu đề H1 Hero: `text-4xl md:text-6xl font-extrabold tracking-tight`
  - Tiêu đề H2 Section: `text-2xl md:text-3xl font-bold tracking-tight`
  - Tiêu đề H3 Card: `text-lg font-semibold`
  - Body Text: `text-base text-slate-300 leading-relaxed`
- **Font Code / Monospace:** `font-mono` (JetBrains Mono, Courier Prime). Dùng cho mã code, JSON, hoặc badge kỹ thuật.

---

## 4. QUY CHUẨN BO GÓC & KHOẢNG CÁCH (RADIUS & SPACING)
- **Bo góc Nút bấm (Button Radius):** `rounded-lg` (8px) hoặc `rounded-full` (cho pill badge).
- **Bo góc Thẻ Card (Card Radius):** `rounded-2xl` (16px) hoặc `rounded-xl` (12px).
- **Padding Thẻ Card:** `p-6` hoặc `p-8` trên desktop.
- **Section Spacing:** Khoảng cách giữa các section lớn luôn là `py-16 md:py-24`.

---

## 5. MẪU THÀNH PHẦN GIAO DIỆN CHUẨN (ATOMIC COMPONENT RECIPES)

### A. Primary Action Button (Nút bấm chính)
```html
<!-- wp:skaaaaa-builder/button {"text":"Bắt đầu ngay","classes":"inline-flex items-center justify-center px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-medium shadow-lg shadow-blue-500/25 transition-all duration-200 hover:scale-[1.02]"} /-->
```

### B. Feature / Pricing Card (Thẻ tính năng)
```html
<!-- wp:skaaaaa-builder/container {"tag":"div","classes":"p-8 rounded-2xl bg-slate-900/80 border border-white/10 backdrop-blur-sm shadow-xl transition-all duration-300 hover:border-blue-500/30 hover:scale-[1.01]"} -->
  <!-- wp:skaaaaa-builder/text {"content":"Tính Năng Cao Cấp","classes":"text-xl font-bold text-white mb-2"} /-->
  <!-- wp:skaaaaa-builder/text {"content":"Giải pháp toàn diện tối ưu hiệu suất cho doanh nghiệp của bạn.","classes":"text-slate-400 text-sm leading-relaxed"} /-->
<!-- /wp:skaaaaa-builder/container -->
```
