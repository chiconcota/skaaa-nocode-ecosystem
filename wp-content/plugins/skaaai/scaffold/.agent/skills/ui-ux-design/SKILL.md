---
name: ui-ux-design
description: Kỹ năng chuyên sâu về tư duy thẩm mỹ UI/UX hiện đại, phân cấp thị giác (Visual Hierarchy), quy tắc phối màu 60-30-10, nhịp điệu khoảng cách (8px Grid), và chuyển động vi mô (Micro-interactions) cho giao diện web đẳng cấp.
---

# SKILL: MODERN UI/UX DESIGN & VISUAL EXCELLENCE

Kỹ năng này cung cấp các nguyên lý, quy tắc vàng và tiêu chuẩn thẩm mỹ quốc tế để AI Agent thiết kế ra những giao diện web hiện đại, sang trọng, có chiều sâu và mang lại trải nghiệm người dùng (UX) xuất sắc.

---

## 1. TRIẾT LÝ THIẾT KẾ ĐẲNG CẤP (PREMIUM AESTHETICS)
1. **Tránh sự nhạt nhòa đơn điệu (Anti-Basic Rule):** Tuyệt đối không dùng các màu cơ bản thô kệch (đỏ thuần `#ff0000`, xanh thuần `#00ff00`). Luôn sử dụng bảng màu HSL tinh chỉnh, chế độ Dark Mode bóng bẩy (`bg-slate-950`) hoặc Light Mode thanh lịch (`bg-slate-50`).
2. **Chiều sâu không gian (Elevation & Depth):** Kết hợp hiệu ứng kính mờ (Glassmorphism `backdrop-blur-md`), viền sáng mảnh bán trong suốt (`border border-white/10`) và đổ bóng đa tầng (`shadow-2xl shadow-blue-500/10`).
3. **Giao diện sống động (Living Interface):** Mọi phần tử tương tác (Nút bấm, thẻ Card, liên kết) đều phải có phản hồi thị giác tức thì khi người dùng tương tác.

---

## 2. QUY TẮC PHỐI MÀU VÀNG 60-30-10

Khi xây dựng giao diện cho bất kỳ trang nào, phân bổ tỷ lệ màu sắc theo tỷ lệ 60-30-10:

```text
┌─────────────────────────────────────────────────────────────────┐
│ 60% MÀU NỀN TỔNG THỂ (Dominant Canvas)                          │
│ Dark Mode: bg-slate-950 (#020617) | Light Mode: bg-slate-50     │
├─────────────────────────────────────────────────────────────────┤
│ 30% MÀU BỀ MẶT THỨ CẤP (Surface & Cards)                        │
│ Nền thẻ Card, Navbar, Panel: bg-slate-900/80, bg-white, v.v.     │
├─────────────────────────────────────────────────────────────────┤
│ 10% MÀU ĐIỂM NHẤN (Accent / Brand CTA)                          │
│ CHỈ DÀNH RIÊNG cho: Nút bấm chính, Badge nổi bật, icon active   │
│ Tailwind: bg-blue-600, text-indigo-400, border-blue-500/30      │
└─────────────────────────────────────────────────────────────────┘
```
> ⚠️ **Lỗi cần tránh:** Lạm dụng màu Primary tràn lan (như tô màu nền cả section bằng màu xanh đậm) làm mất đi điểm nhấn thị giác và gây mỏi mắt.

---

## 3. PHÂN CẤP THỊ GIÁC (VISUAL HIERARCHY & TYPOGRAPHY)

Người dùng không đọc toàn bộ trang web, họ "quét" (scan) mắt theo hình chữ F hoặc chữ Z. Bố cục phải dẫn dắt tầm nhìn qua 3 nấc:

1. **Nấc 1 — Điểm dừng đầu tiên (Primary Focal Point):**
   - Tiêu đề Hero H1 cực lớn: `text-4xl md:text-6xl font-black text-white tracking-tight leading-tight`.
   - Nút hành động chính (Primary CTA) với độ tương phản cao nhất.
2. **Nấc 2 — Thông tin bổ trợ (Secondary Information):**
   - Tiêu đề phụ, Section Heading H2: `text-2xl md:text-3xl font-bold text-white tracking-tight`.
   - Tiêu đề thẻ Card H3: `text-lg md:text-xl font-semibold text-white`.
3. **Nấc 3 — Chi tiết nội dung (Body & Metadata):**
   - Đoạn văn mô tả: `text-base text-slate-300 leading-relaxed max-w-2xl`.
   - Thông tin phụ, ngày tháng: `text-xs md:text-sm text-slate-500`.

---

## 4. NHỊP ĐIỆU KHOẢNG CÁCH (SPACING CADENCE - 8PX GRID)

Toàn bộ padding, margin và gap phải tuân theo hệ số của 8px (hoặc 4px cho khoảng cách hẹp):

| Khoảng cách | Utility Tailwind | Ứng dụng thực tế |
| :--- | :--- | :--- |
| **4px / 8px** | `p-1`, `gap-2` | Khoảng cách giữa icon và chữ trong badge. |
| **12px / 16px** | `p-3`, `gap-4`, `p-4` | Padding nút bấm nhỏ, khoảng cách giữa các phần tử form. |
| **24px / 32px** | `p-6`, `p-8`, `gap-6` | Padding bên trong các thẻ Card, khoảng cách giữa các cột Grid. |
| **64px / 80px** | `py-16`, `py-20` | Padding trên/dưới của các Section nhỏ hoặc Header. |
| **96px / 128px**| `py-24`, `py-32` | Padding trên/dưới của Section chính (Hero, Pricing, CTA lớn). |

---

## 5. CHUYỂN ĐỘNG VI MÔ & TRẠNG THÁI GIAO DIỆN (MICRO-INTERACTIONS)

Mọi component phải hỗ trợ đầy đủ 4 trạng thái thị giác:

### A. Nút Bấm Chính (Primary Button)
```html
classes="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-[0.98] text-white font-semibold shadow-lg shadow-blue-500/25 transition-all duration-200 hover:scale-[1.02] cursor-pointer"
```
* **Hover:** Sáng màu hơn (`hover:bg-blue-500`), phóng nhẹ 1.02x (`hover:scale-[1.02]`).
* **Active (Khi click):** Thu nhỏ nhẹ (`active:scale-[0.98]`) tạo cảm giác nhấn phím cơ học.

### B. Thẻ Nội Dung (Interactive Card)
```html
classes="p-8 rounded-2xl bg-slate-900/80 border border-white/10 backdrop-blur-sm shadow-xl transition-all duration-300 hover:border-blue-500/40 hover:scale-[1.01] hover:shadow-2xl hover:shadow-blue-500/10"
```
* Khi hover: Viền sáng lên màu xanh, nổi bóng mờ nhẹ, tạo chiều sâu 3D tinh tế.

### C. Trạng Thái Rỗng (Empty State) & Tải Trang (Loading)
* Khi danh sách không có dữ liệu, không để trang trắng trơn: Luôn hiển thị một khối thông báo thân thiện kèm icon minh họa và nút hành động *"Tạo mới ngay"*.

---

## 6. KHẢ NĂNG TIẾP CẬN & THÍCH ỨNG MOBILE (MOBILE-FIRST & A11Y)
1. **Vùng chạm tối thiểu (Tap Target Size):** Mọi nút bấm và link trên điện thoại phải đạt chiều cao tối thiểu 44px (`min-h-[44px]` hoặc `py-3`) để ngón tay chạm dễ dàng.
2. **Độ tương phản chữ (Contrast Ratio):** Chữ trên nền tối phải dùng màu trắng sáng `text-white` hoặc xám sáng `text-slate-200`, không dùng xám tối `text-slate-600` làm chữ bị chìm.
3. **Cột thích ứng (Responsive Grid):**
   - Mobile: 1 cột `grid-cols-1`.
   - Tablet: 2 cột `md:grid-cols-2`.
   - Desktop: 3 hoặc 4 cột `lg:grid-cols-3` hoặc `xl:grid-cols-4`.
