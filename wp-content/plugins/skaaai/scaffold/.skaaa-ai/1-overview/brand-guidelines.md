# QUY CHUẨN NHẬN DIỆN THƯƠNG HIỆU DOANH NGHIỆP (brand-guidelines.md)
@company: SKAAA NO-CODE ECOSYSTEM | @version: 2.1 | @authority: Giám Đốc

> **TÀI LIỆU NÀY LÀ HỒ SƠ BÀN GIAO THẨM MỸ (SINGLE SOURCE OF TRUTH).**
> Toàn bộ giá trị trong bảng này bắt buộc phải được đồng bộ trực tiếp vào bảng phẳng MySQL `wp_skaaa_data_sys_presets` thông qua công cụ:
> `php .agent/harness/db-tool.php --set-tokens="..."`

---

## 1. BẢNG ĐỐI SOÁT DESIGN TOKENS CHUẨN (SCHEMA ⟷ THỊ GIÁC)

| Tên Trường CSDL (`sys_presets`) | Loại Token (`type`) | Ý Nghĩa Thị Giác (Human-Readable) | Light Mode | Dark Mode | Class Tailwind JIT Mẫu |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`LogoUrl`** | `token_brand` | Logo SVG vector chính thức | URL Logo | URL Logo | `w-8 h-8` |
| **`Background`** | `token_color` / `token_dark_color` | Nền chính toàn trang (Canvas Base) | `#f9fafb` | `#08090c` | `bg-slate-50` / `bg-[#08090c]` |
| **`Surface`** | `token_color` / `token_dark_color` | Nền thẻ nổi, card, modal (Surface Card) | `#ffffff` | `#10131a` | `bg-white` / `bg-[#10131a]` |
| **`Border`** | `token_color` / `token_dark_color` | Viền kỹ thuật sắc nét (Hairline Border) | `#e5e7eb` | `#1e2433` | `border-slate-200` / `border-[#1e2433]` |
| **`Primary`** | `token_color` / `token_dark_color` | Màu hành động chính, CTA nổi bật | `#f59e0b` | `#f59e0b` | `bg-amber-500 hover:bg-amber-400 text-black` |
| **`Secondary`** | `token_color` / `token_dark_color` | Màu điểm nhấn trạng thái, pulse, nhãn | `#10b981` | `#34d399` | `text-emerald-400 bg-emerald-500/10` |
| **`Tertiary`** | `token_color` / `token_dark_color` | Màu bổ trợ thứ cấp, link tham chiếu | `#3b82f6` | `#3b82f6` | `text-blue-500 hover:text-blue-400` |
| **`Text`** | `token_color` / `token_dark_color` | Màu chữ chính (Tiêu đề H1-H3, Headline) | `#111827` | `#f9fafb` | `text-slate-900` / `text-white` |
| **`Success`** | `token_color` / `token_dark_color` | Thông báo thành công, trạng thái online | `#10b981` | `#10b981` | `text-emerald-500 bg-emerald-500/10` |
| **`PrimaryFont`** | `token_font` | Font giao diện chính (Headings & Body) | Inter | Inter | `font-sans` |
| **`MonoFont`** | `token_font` | Font kỹ thuật (Code, CLI, Badge, KPI) | JetBrains Mono | JetBrains Mono | `font-mono` |

---

## 2. QUY CHUẨN TYPOGRAPHY & PHÂN CẤP TIÊU ĐỀ
- **Hero Title (H1):** `text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight`
- **Section Heading (H2):** `text-2xl sm:text-3xl font-bold tracking-tight text-white`
- **Card Title (H3):** `text-lg sm:text-xl font-semibold group-hover:text-amber-400 transition-colors`
- **Body Text:** `text-sm sm:text-base text-slate-400 leading-relaxed`
- **Technical & Metric:** `font-mono text-xs text-emerald-400`

---

## 3. BO GÓC & KHÔNG GIAN BỐ CỤC (SPATIAL TOKENS)
- **Container lớn / Card nổi:** `rounded-2xl` (16px - đồng bộ bo góc Logo SVG)
- **Nút bấm CTA & Input Form:** `rounded-xl` (12px)
- **Huy hiệu Tags công nghệ:** `rounded-md` (6px)
- **Avatar & Status Pulse Dot:** `rounded-full` (9999px)
- **Header cố định:** `sticky top-0 z-50 backdrop-blur-md border-b`
