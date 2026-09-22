# SKAAA DATA PRO - ARCHITECTURE DOCUMENT
@status: 🟢 Done (Core Engine) | @layer: Data Engine | @context: Flat Tables, Schema, Query Builder

## 1. BOUNDARY RULES (GIỚI HẠN TRÁCH NHIỆM)
- **Tuyệt đối không sử dụng `wp_postmeta`** cho các dữ liệu App (Nhà hàng, Bất động sản, Lịch hẹn...). Phải tự sinh bảng `skaaa_data_*`.
- **Giữ cấu trúc lai (Hybrid):** Dữ liệu xác thực người dùng (Login, Mật khẩu, Identity) vẫn NẰM ở `wp_users`. Các bảng của Skaaa liên quan đến user chỉ lưu ID (Foreign Key). Không tự làm hệ thống Auth riêng.
- **Data Providers Pattern:** Không copy dữ liệu của WooCommerce vào Skaaa. Khi cần truy vấn dữ liệu WooCommerce, sử dụng Adapter gọi hàm `wc_get_products()` của WP Core.
- **Độc Lập UI Admin:** Dashboard của Data Pro tự load Tailwind CDN tĩnh, KHÔNG hook vào CDN chung của Builder Core để tránh gãy Layout khi Core bị tắt.

## 2. WP HOOKS EXPOSED (GIAO TIẾP XUYÊN PLUGIN)
*Dự kiến triển khai trong Phase 2:*
- `apply_filters( 'skaaa_data_query', $results, $query_args )`: Hook để Logic Engine hoặc Design Engine gọi dữ liệu danh sách từ Data Pro.
- `apply_filters( 'skaaa_data_get_row', null, $table, $id )`: Hook truy xuất nhanh 1 dòng dữ liệu duy nhất bằng Khóa chính.
- `do_action( 'skaaa_data_schema_installed', $template_id )`: Hook báo hiệu một Template (vd: ecommerce) vừa được cài đặt thành công.
- `apply_filters( 'skaaa_data_get_schema_registry', $schemas )`: Hook trả về cấu trúc mảng của tất cả các Bảng đang tồn tại (để Frontend Dynamic Tag Picker liệt kê ra Dropdown).

## 3. DATA FLOW (LUỒNG QUẢN LÝ DỮ LIỆU)
1. **Schema Initialization:** User chọn Template Gallery (UI) -> Gọi AJAX -> Server biên dịch Schema Array -> Kích hoạt `dbDelta()` -> Sinh bảng `wp_skaaa_data_xyz`. Hoặc tạo thủ công Custom Table `create_custom_table()`.
2. **Ký Danh Bảng Thông Minh (Table Alias):** Bản thân DB vật lý MySQL không thay đổi Tên. Tất cả Nhãn Tiếng Việt, Icon và Group App Category được định cấu hình bằng mảng `__table_info` cắm vào JSON `skaaa_data_dictionary`.
3. **Môi Trường Quốc Tế (i18n):** Ngành mã lõi (Plugin) hiện đang Code Base = Tiếng Việt cho quá trình Fast-MVP. Tính năng Global sẽ được chuyển dịch tự động qua WordPress `__()` bằng file `.po` trong Phase Packaging cuối.
4. **Data Retrieval (Query):** Dùng `Class_Query_Builder` nhận Array conditions từ Hook, tự Generate câu lệnh raw SQL `SELECT * FROM skaaa_data_xyz WHERE...` và trả về mảng dữ liệu sạch (Clean Array).
5. **Relation Resolution (Virtual Constraints):** Tại hàm lõi `get_table_data`, Engine tự dò Cột `relation`. Gom ID (VD: `101, 102`) và thực thi 1 lệnh `WHERE IN` sang Bảng Đích. Toàn bộ ID sau đó được **Cấy (Enrich)** thẳng vào Payload dưới dạng Mảng Đối Tượng `[{id: 101, label: "Title"}]`. Nhờ vậy, `Formula Engine` hay Admin UI, API bên trên không cần tự xử lý quan hệ nữa.
6. **Data Engine Integration:** File `class-skaaa-provider.php` (Định danh Prefix `skaaa:`) làm Middleware cho phép Hệ sinh thái Skaaa Builder Core móc nối trực tiếp các Cột phẳng. Nó trả Format nguyên thủy: `Boolean` bằng `1/0`, `Media Gallery` và `Multi Select` bằng chuỗi mảng CSV.
7. **Query Builder UX (Auto-Prefix):** DB Engine tự động ghép rào bảo mật cộng phân giải tiền tố `skaaa_data_` vào các param `table` tĩnh từ Data API để hỗ trợ Coder Dev không bao giờ gặp lỗi ngớ ngẩn No Table Detected.
7. **UI Component (DataGrid):** Gạt phăng HTML form truyền thống. Mọi tương tác Input (Boolean CSS Switch, Media Uploads) đều dùng công nghệ Live AJAX 1-Click. Quản lý chặt trật tự `mousedown` để không va chạm `z-index` với Component gốc `wp.media` của WP.
8. **DataGrid View State (URL-Driven):** Quản lý trạng thái Lọc (Filter), Sắp xếp (Sort) và Gộp nhóm (Group) bằng URL Parameters. Tính năng "Gộp Nhóm (Group)" vận hành qua câu lệnh MySQL `ORDER BY` tĩnh ở Backend, vòng lặp PHP tự sinh Divider để giữ Light Weight.
9. **Extensibility Adapter:** Sẵn sàng kết nối đa nguồn dữ liệu (như `wp_users`, WooCommerce). Các Cỗ máy đọc Data (Provider Layer) phải được móc sau khi File Lõi `skaaaaa-builder-core.php` khởi tạo hoàn toàn (Hook lúc `plugins_loaded`) để né sự cố Plugin Load Order do WP Alphabetical Loading gây ra.
