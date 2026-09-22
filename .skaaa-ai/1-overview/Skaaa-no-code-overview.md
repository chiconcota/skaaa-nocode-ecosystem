# SKAAA ECOSYSTEM - MASTER PLAN (PROJECT VISION)
@version: 3.1.0 | @stack: WP-Core 6.x, PHP 8.2+, Tailwind JIT, Flat Tables, Logic DAG, Skaaai (AI & Bridge) | @focus: Native SSR Monolith + Bidirectional Sync

## 1. TẦM NHÌN HỆ SINH THÁI (THE TRINITY + SKAAAI AI & BRIDGE)
Chúng ta định vị **SKAAA** là một **Hệ điều hành ứng dụng tự chủ (Self-hosted App OS)** chạy trực tiếp trên WordPress core dưới dạng **Native SSR Monolith** (tối ưu hóa tốc độ tải trang cực nhanh bằng Alpine.js và Tailwind v4 JIT offline, không phụ thuộc headless Next.js).

Hệ sinh thái SKAAA được xây dựng vững chắc dựa trên 3 trụ cột cốt lõi (The Trinity), 1 Theme nền tảng sạch và 1 Plugin trí tuệ nhân tạo & đồng bộ:

1. **Theme Nền Tảng (Skaaa Canvas):** Blank Canvas thuần khiết, triệt tiêu 100% CSS/JS rác của WordPress (`.wp-block-library`), tự động bù trừ thanh Admin Bar cho các layout fixed/sticky header, đem lại khung vẽ trắng tinh khiết.
2. **System Design (Skaaa No-Code Design):** Xử lý bộ khung giao diện nguyên tử (Atomic Blocks: `Container`, `Text`, `Button`, `SVG`, `Code`, `Loop`) và Design Engine (Tailwind CSS v4 JIT Compiler offline 100% Parity PHP/JS, Skaaapine Engine tương tác thời gian thực bằng Alpine.js). Tích hợp bộ chuyển đổi mã nguồn `html2tailwind`.
3. **Key Database (Skaaa Data Pro):** Hệ thống cơ sở dữ liệu bảng phẳng MySQL (`skaaa_data_*`) thay thế hoàn toàn mô hình EAV (`wp_postmeta`). Cung cấp Schema Manager, DataGrid Strategy Pattern (Inline Edit), Smart Object JSON Blueprint di động và bảo vệ schema bảng hệ thống.
4. **Logic Engine (Skaaa Logic Engine):** Bộ não điều khiển luồng hoạt động dạng đồ thị DAG kéo thả (React Flow v11). Xử lý luồng sự kiện (Events Pipeline), biểu thức nội suy dữ liệu (SkaaaFX AST), Pluggable Nodes Framework và tiến trình nền bất đồng bộ (Action Scheduler).
5. **AI & Sync Bridge (Skaaai - Plugin mới):** 
   - **Self-Documenting Context Engine:** Tự động phát sinh `ai-manifest.json` và REST API endpoint để AI Copilot ngoài môi trường hiểu thấu đáo toàn bộ hệ thống Blocks, DB Schemas mà không phụ thuộc vào thư mục `.skaaa-ai`.
   - **Bidirectional Content Sync:** Cầu nối đồng bộ bài viết và giao diện 2 chiều an toàn giữa Localhost và Webhost (Staging/Production) thông qua định danh toàn cục `skaaa_uuid`, cơ chế phát hiện xung đột và tự động sao lưu WordPress Revisions.
   - **AI Logic Nodes:** Tích hợp các Node xử lý trí tuệ nhân tạo (`AIPromptNode`, `AIParserNode`) vào đồ thị DAG kết nối LLM (Gemini/OpenAI).

---

## 2. DIRECTORY ARCHITECTURE (SKAAA ECOSYSTEM)
```text
wp-content/
├── themes/
│   └── skaaa-canvas/           # [THEME] Blank Canvas (Zero CSS/JS overhead, Native Admin Bar Offset)
└── plugins/
    ├── skaaa-no-code-design/  # [UI/UX] Atomic Blocks, Tailwind v4 JIT Compiler, Skaaapine, html2tailwind
    ├── skaaa-data-pro/        # [DATA] Flat Tables DB Engine, Smart Object JSON Blueprint, DataGrid
    ├── skaaa-logic-engine/    # [LOGIC] DAG Workflow Canvas, SkaaaFX AST, Pluggable Nodes, Webhooks
    └── skaaai/                # [AI & BRIDGE] Self-Documenting Context, Bidirectional Content Sync (Local ⟷ Host), AI Nodes
```

---

## 3. BẢN ĐỒ PHÂN CHIA TRÁCH NHIỆM (BOUNDARY ISOLATION)
Để tuân thủ triết lý **Decoupled Architecture**, các plugin tuyệt đối KHÔNG gọi trực tiếp class của nhau, mọi tương tác diễn ra độc quyền qua:
- WordPress Action/Filter hooks (`do_action`, `apply_filters`).
- Alpine.js global store (`Alpine.store`) cho các tương tác phía client.

* **Design ➔ Logic:** Gửi sự kiện form submit và tương tác người dùng lên endpoint để kích hoạt workflow.
* **Logic ➔ Data:** Các node `DBQueryNode` và `DBActionNode` gọi WP Filters để đọc/ghi dữ liệu vào các bảng phẳng của Data Pro.
* **Skaaai ➔ Logic:** Đăng ký các Node AI mới vào bộ đăng ký tập trung của Logic Engine thông qua filter `skaaa_logic_registered_nodes`.
* **Skaaai ➔ Host / Local:** Đồng bộ an toàn bài viết và cấu trúc dữ liệu qua REST API có xác thực Pairing Key bảo mật.

---

## 4. MILESTONES & ROADMAP TẦM NHÌN
* **Milestone 1: Nền tảng Monolith & Rebranding (COMPLETED):**
  - Hoàn tất Atomic Blocks, bộ đôi JIT Compiler Tailwind v4 (100% Compiler Parity).
  - Hoàn thiện Flat Tables Schema, Smart Object Blueprint và DataGrid Strategy Pattern.
  - Hoàn thành Pluggable Nodes Framework, SkaaaFX AST Evaluator và Extensions Manager.
  - Chuẩn hóa toàn bộ hệ sinh thái sang nhận diện thương hiệu thống nhất **SKAAA**.
* **Milestone 2: Skaaai Copilot & Bidirectional Sync (ACTIVE):**
  - Khởi tạo plugin `skaaai` với cơ chế xác thực kết nối Sender (Local) ⟷ Receiver (Host).
  - Xây dựng Self-Documenting Context Engine (`ai-manifest.json` & `/wp-json/skaaai/v1/context`).
  - Động cơ đồng bộ nội dung 2 chiều (Push to Live / Pull from Live) dựa trên `skaaa_uuid`.
  - Nút Push 1-Click trên Gutenberg Editor Toolbar.
  - Tích hợp các AI Logic Nodes (Gemini/OpenAI) vào DAG Canvas.

---

## 5. NGUYÊN TẮC CỐT LÕI (TECHNICAL CONSTRAINTS)
1. **No-Postmeta Rule:** Tuyệt đối không lạm dụng `wp_postmeta` để lưu trữ dữ liệu ứng dụng. Bắt buộc tạo và dùng bảng phẳng MySQL (`skaaa_data_*`).
2. **Framework-Agnostic Core Libraries:** Mã nguồn lõi của `Skaaapine`, `SkaaaFX` và `Tailwind JIT` phải viết độc lập, không phụ thuộc WordPress để sẵn sàng trích xuất thành package độc lập.
3. **Decoupled Plugins Rule:** Không có sự phụ thuộc cứng (hard dependency) giữa các plugin. Nếu một plugin tắt, các plugin khác vẫn hoạt động ổn định.
4. **i18n Compliance:** Mọi chuỗi hiển thị trên UI mặc định viết bằng tiếng Anh và bọc hàm chuẩn WordPress i18n (`__()`, `esc_html__()`). Chú thích code viết bằng tiếng Việt.
5. **Zero-Trash Policy:** Giữ tài liệu ngăn nắp theo cấu trúc 4 ngăn kéo, không tạo file rác ngoài quy chuẩn.