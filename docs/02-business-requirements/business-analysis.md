# 89 Beer Garden --- Business Analysis

> **Document:** Business Analysis\
> **Project:** 89 Beer Garden Website & Management System\
> **Version:** 1.0\
> **Status:** Final Baseline\
> **Date:** 2026-08-17\
> **Next document:** `system-requirements.md`

------------------------------------------------------------------------

## 1. Purpose

Tài liệu này là **single source of truth cho Business Analysis** của dự
án 89 Beer Garden.

Nó hợp nhất đầy đủ kết quả của các task:

1.  Xác định Actor & Stakeholder.
2.  Phân tích nghiệp vụ hiện tại.
3.  Xác định các quy trình nghiệp vụ chính.
4.  Xác định Business Rules.
5.  Xác định vấn đề & nhu cầu cải tiến.
6.  Review & chốt Business Analysis.

Tài liệu được dùng làm context cho con người và AI coding agents trước
khi phân tích System Requirements, Use Case, Database, UI/UX và
Development.

### 1.1 Business goal

Số hóa quá trình phục vụ và quản lý bán hàng của 89 Beer Garden, giảm
sai sót vận hành, tập trung dữ liệu và cải thiện trải nghiệm chọn món
của khách hàng.

### 1.2 Business model

89 Beer Garden được phân tích theo mô hình **restaurant / beer garden**,
trong đó hoạt động phục vụ khách tại quán và POS là trung tâm.

Website khách hàng, đặt bàn, đa ngôn ngữ và AI Recommendation hỗ trợ
xung quanh nghiệp vụ trung tâm này.

### 1.3 Primary business flow

`Reservation / Walk-in → Table → Dining Session → Ordering → Kitchen / Bar → Serving → Payment → Complete`

------------------------------------------------------------------------

# 2. Actors & Stakeholders

## 2.1 Stakeholders

  -----------------------------------------------------------------------
  Stakeholder             Vai trò                 Nhu cầu chính
  ----------------------- ----------------------- -----------------------
  Chủ quán / Quản lý      Quản lý hoạt động kinh  Theo dõi doanh thu, đơn
                          doanh                   hàng, sản phẩm, bàn,
                                                  kho và nhân viên

  Nhân viên               Vận hành hoạt động tại  Tiếp nhận order, quản
                          quán                    lý bàn, xử lý đơn và
                                                  thanh toán

  Khách hàng              Người sử dụng dịch vụ   Xem menu, đặt bàn, gọi
                                                  món và thanh toán thuận
                                                  tiện

  Nhân viên bếp / quầy    Chuẩn bị món            Nhận món cần chuẩn bị
                                                  và cập nhật tiến độ

  Quản trị hệ thống       Quản lý website         Quản lý tài khoản,
                                                  quyền truy cập và cấu
                                                  hình
  -----------------------------------------------------------------------

Stakeholder không nhất thiết tương ứng 1:1 với một role phần mềm.

## 2.2 Human Actors

### ACT-01 --- Customer

Khách hàng sử dụng giao diện website.

Nhu cầu:

-   Xem menu.
-   Xem chi tiết món.
-   Tìm kiếm và lọc món.
-   Chuyển ngôn ngữ `VI / EN / ZH`.
-   Nhận gợi ý món.
-   Chủ động thêm món từ Menu/Recommendation vào giỏ.
-   Đặt món trong Dining Session hợp lệ khi Customer self-order được bật.
-   Đặt bàn.
-   Theo dõi đơn hàng nếu chức năng được triển khai.
-   Áp dụng voucher.
-   Thanh toán.
-   Quản lý tài khoản.
-   Xem lịch sử đơn nếu có tài khoản.

**Business decision:** Không bắt buộc Customer đăng nhập chỉ để xem
menu.

### ACT-02 --- Staff

Nhân viên trực tiếp vận hành tại quán.

Nhu cầu:

-   Đăng nhập hệ thống.
-   Xem trạng thái bàn.
-   Tiếp nhận khách.
-   Tạo order tại POS.
-   Chọn bàn cho order.
-   Thêm/xóa/thay đổi số lượng món khi nghiệp vụ cho phép.
-   Ghi chú món.
-   Xem và cập nhật order.
-   Xử lý yêu cầu đặt bàn.
-   Xác nhận thanh toán.
-   Xem hóa đơn.

Staff là actor sử dụng POS nhiều nhất.

### ACT-03 --- Kitchen / Bar Staff

Nhân viên bếp hoặc quầy nhận yêu cầu chế biến.

Nhu cầu:

-   Xem danh sách món cần chuẩn bị.
-   Xem số lượng.
-   Xem ghi chú món.
-   Xem bàn/order liên quan.
-   Cập nhật trạng thái chuẩn bị.

Trạng thái cơ bản:

`Waiting → Preparing → Ready`

Sau khi phục vụ:

`Ready → Served`

**Scope decision:** Kitchen Display chuyên biệt không bắt buộc trong
Core. Core chỉ cần trạng thái xử lý cơ bản.

### ACT-04 --- Manager / Admin

Người quản lý hệ thống.

Nhu cầu:

-   Quản lý sản phẩm.
-   Quản lý danh mục.
-   Quản lý giá.
-   Quản lý bàn.
-   Quản lý order.
-   Quản lý reservation.
-   Quản lý voucher.
-   Quản lý kho.
-   Quản lý khách hàng.
-   Quản lý nhân viên.
-   Quản lý tài khoản.
-   Phân quyền.
-   Xem báo cáo.
-   Theo dõi doanh thu.
-   Theo dõi món bán chạy.
-   Quản lý cấu hình hệ thống.

**Business decision:** Ở Business Analysis, `Manager / Admin` được coi
là một actor cấp cao. Có thể tách role chi tiết hơn ở Role & Permission.

## 2.3 External Actors / External Systems

### EXT-01 --- Translation Service

Dùng để hỗ trợ nội dung động đa ngôn ngữ.

Luồng khái niệm:

`Website → Translation Service → EN/ZH translation → persist → optional cache → Customer`

Không nên gọi Translation API lại mỗi lần tải trang.

### EXT-02 --- Weather Service

Cung cấp dữ liệu thời tiết làm context cho AI Recommendation.

Ví dụ:

`Weather Service → weather context → Recommendation Logic → Suggested Products`

Weather Service chỉ cung cấp dữ liệu đầu vào, không quyết định món.

### EXT-03 --- Payment Service

Có thể được tích hợp nếu triển khai payment gateway online.

**Core:** Cash + QR/Bank Transfer confirmation.\
**Future/Optional:** Payment Gateway đầy đủ.

## 2.4 Actor baseline

Human actors chính:

`Customer · Staff · Kitchen/Bar Staff · Manager/Admin`

Không chia nhỏ Waiter, Cashier, Warehouse Staff, Receptionist... ở
Business Analysis. Khi cần sẽ giải quyết bằng Role & Permission.

------------------------------------------------------------------------

# 3. Current Business Analysis

> "Current" trong tài liệu này được hiểu là mô hình vận hành nghiệp vụ
> giả định/chuẩn hóa của 89 Beer Garden để thiết kế hệ thống.

## 3.1 Overall operation

`Customer`

→ Walk-in hoặc Reservation

→ Tiếp nhận & xếp bàn

→ Xem Menu

→ Gọi món

→ Kitchen / Bar xử lý

→ Phục vụ

→ Có thể gọi thêm

→ Thanh toán

→ Hoàn tất

Các nghiệp vụ hỗ trợ:

`Menu · Inventory · Voucher · Customer · Employee · Reporting`

Các capability hỗ trợ:

`Multilingual · AI Recommendation`

------------------------------------------------------------------------

# 4. Business Domains

  Business Domain            Scope
  -------------------------- ----------------
  Table & Guest Service      Core
  Reservation                Core
  Ordering                   Core
  Kitchen / Bar Processing   Core --- Basic
  Payment & Billing          Core
  Menu Management            Core
  Inventory Management       Core --- Basic
  Management & Reporting     Core

Voucher là nghiệp vụ hỗ trợ.

AI Recommendation là capability hỗ trợ chọn món/cross-sell.

Multilingual là system capability, không phải business process độc lập.

------------------------------------------------------------------------

# 5. Detailed Business Processes

## 5.1 Walk-in & Guest Reception

Khách đến quán và cung cấp:

-   Số lượng người.
-   Nhu cầu bàn.
-   Thông tin reservation nếu đã đặt trước.

Staff kiểm tra bàn dựa trên:

-   Trạng thái bàn.
-   Sức chứa.
-   Reservation sắp tới.

Nếu có bàn phù hợp:

`Available → Occupied`

Khách bắt đầu phiên phục vụ.

Nếu không có bàn phù hợp:

-   Thông báo tình trạng.
-   Đề xuất khách chờ hoặc phương án khác.

------------------------------------------------------------------------

## 5.2 Table Reservation

Khách có thể đặt bàn trước qua website.

Thông tin cơ bản:

-   Họ tên.
-   Số điện thoại.
-   Ngày.
-   Giờ.
-   Số người.
-   Ghi chú.

Luồng:

`Customer → Reservation Request → Staff Review → Confirm / Reject`

Nếu xác nhận:

`Pending → Confirmed`

Khi khách đến:

`Confirmed → Checked-in`

Sau khi bố trí bàn:

`Table → Occupied`

**Business decision:** Core không bắt buộc Customer tự chọn chính xác
bàn số nào. Customer chọn thời gian và số người; hệ thống/quán bố trí
bàn.

------------------------------------------------------------------------

## 5.3 Dine-in Ordering

Khách xem menu và lựa chọn món.

Staff hoặc giao diện khách ghi nhận:

-   Product.
-   Quantity.
-   Unit price tại thời điểm order.
-   Note.
-   Table / Dining Session.
-   Thời gian.
-   Staff liên quan nếu có.

Luồng:

`Customer Request → Validate Product → Confirm → Create Order → Kitchen/Bar`

### Additional orders

Trong quá trình sử dụng bàn, khách có thể gọi thêm nhiều lần.

Ví dụ:

Lần đầu:

-   6 bia.
-   1 gà nướng.

Sau 30 phút:

-   4 bia.
-   1 món chiên.

Các lần gọi thêm thuộc cùng một phiên phục vụ.

------------------------------------------------------------------------

# 6. Dining Session

## 6.1 Definition

**Dining Session** là một phiên phục vụ của một bàn, bắt đầu khi khách
nhận bàn và kết thúc khi khách hoàn tất thanh toán/rời bàn.

Ví dụ:

`19:00 Check-in → Orders → Additional Orders → 21:10 Payment → Complete`

Toàn bộ khoảng thời gian này là một Dining Session.

## 6.2 Conceptual relationship

`Table → Dining Session → Order(s) → Order Items`

## 6.3 Important decision

Không thiết kế nghiệp vụ theo giả định:

`1 Table = 1 Order`

vì khách có thể gọi thêm nhiều lần trong cùng một phiên.

Dining Session là **business concept**. Việc tạo entity/table
`dining_sessions` hay dùng mô hình khác sẽ được quyết định ở Database
Design.

------------------------------------------------------------------------

# 7. Kitchen / Bar Processing

Sau khi order được xác nhận, món được gửi tới bộ phận phù hợp.

Ví dụ:

-   Bia / nước → Bar.
-   Món ăn → Kitchen.

Mỗi Order Item có thể có trạng thái riêng:

`Waiting → Preparing → Ready → Served`

Ví dụ trong cùng một order:

  Product           Status
  ----------------- -----------
  Beer              Served
  French Fries      Ready
  Grilled Chicken   Preparing
  Hot Pot           Waiting

Điều này cho phép từng món hoàn thành ở thời điểm khác nhau.

Core không yêu cầu Kitchen Display System phức tạp.

------------------------------------------------------------------------

# 8. Payment & Table Closing

Khi Customer yêu cầu thanh toán:

`Open Dining Session`

→ Tổng hợp toàn bộ món

→ Tính Subtotal

→ Validate Voucher/Discount

→ Tính Total

→ Chọn phương thức thanh toán

→ Staff xác nhận

→ Tạo Invoice

→ Complete Dining Session

→ Table Cleaning

→ Table Available

Công thức Core:

`Subtotal = Σ(Unit Price × Quantity)`

`Total = Subtotal − Discount`

Core hỗ trợ:

-   Cash.
-   QR / Bank Transfer confirmation.

Tax, Service Fee hoặc payment gateway có thể mở rộng sau.

------------------------------------------------------------------------

# 9. Menu Management

Manager/Admin quản lý:

-   Category.
-   Product.
-   Price.
-   Image.
-   Description.
-   Availability.
-   Active status.

Khi món tạm hết:

`Available → Unavailable`

Ưu tiên chuyển trạng thái thay vì xóa sản phẩm để giữ lịch sử.

------------------------------------------------------------------------

# 10. Inventory Management

## 10.1 Import

`Receive Goods → Create Stock Import Record → Increase Stock`

## 10.2 Export / Adjustment

`Stock Change → Record Quantity → Decrease/Adjust Stock → Save History`

Core hỗ trợ:

-   Current stock.
-   Import.
-   Export/Adjustment.
-   Stock history.
-   Minimum stock.
-   Low-stock warning.

Core **không yêu cầu**:

`Dish → Recipe → Ingredient quantities → automatic ingredient deduction`

Recipe/BOM thuộc Future Scope.

------------------------------------------------------------------------

# 11. Voucher Management

Manager/Admin có thể cấu hình:

-   Voucher code.
-   Discount type/value.
-   Valid period.
-   Minimum order value.
-   Usage limit.
-   Active status.

Khi áp dụng:

`Voucher → Validate → Apply Discount`

Core giới hạn một voucher trên một hóa đơn.

------------------------------------------------------------------------

# 12. Reporting

Manager cần trả lời được các câu hỏi cơ bản:

-   Doanh thu hôm nay/tháng này?
-   Có bao nhiêu đơn?
-   Giá trị đơn trung bình?
-   Món nào bán chạy?
-   Doanh thu theo khoảng thời gian?

Core report:

-   Revenue.
-   Order count.
-   Average Order Value.
-   Best-selling Products.
-   Revenue by Date/Period.

Không yêu cầu BI nâng cao.

------------------------------------------------------------------------

# 13. AI Recommendation

AI Recommendation hỗ trợ nghiệp vụ chọn món, không phải business process
độc lập.

## 13.1 Business purpose

-   Hỗ trợ khách chọn món.
-   Gợi ý món phù hợp.
-   Cross-sell.
-   Upsell hợp lý.
-   Cải thiện trải nghiệm menu.

## 13.2 Possible context

Recommendation có thể sử dụng:

-   Time of day.
-   Weather.
-   Popular Products.
-   Customer Order History.
-   Current Cart.

Ví dụ:

`Customer chooses Beer → Recommend suitable side dishes`

hoặc:

`Cold weather → prioritize suitable hot dishes`

## 13.3 Core approach

Core có thể dùng:

`Rules + Context + Product Data`

Không bắt buộc Machine Learning.

## 13.4 AI boundary

AI:

-   Chỉ recommendation.
-   Không tự động thêm món vào cart/order.
-   Không gợi ý món unavailable.
-   Không được trở thành dependency của Core Business.

Customer có thể chủ động chọn `Add to Cart` từ kết quả Recommendation.
Đây là user action; AI không được tự mutate Cart/Order.

Nếu AI/Recommendation Service lỗi:

`Menu + Reservation + Order + Payment` vẫn phải hoạt động.

------------------------------------------------------------------------

# 14. Multilingual

Ngôn ngữ:

-   `VI` --- mặc định.
-   `EN`.
-   `ZH`.

Customer có thể switch language.

Conceptual flow:

`Select Language → Find Translation → Display`

Nếu thiếu translation:

`Requested Language Missing → Fallback VI`

Nội dung động chưa có translation có thể sử dụng Translation Service.

**Technical direction đã thống nhất ở mức business:** bản dịch nên được
persist và cache để tránh dịch lại ở mọi request; nội dung quan trọng có thể
chỉnh thủ công.

Chi tiết schema/API sẽ được quyết định ở System
Requirements/Architecture/Database.

------------------------------------------------------------------------

# 15. Business Flows

## BF-01 --- Walk-in & Table Assignment

**Actors:** Customer, Staff

**Start:** Customer đến trực tiếp.

Flow:

1.  Staff tiếp nhận Customer.
2.  Xác định số lượng khách.
3.  Kiểm tra bàn phù hợp.
4.  Nếu có bàn:
    -   Chọn bàn.
    -   Gán khách vào bàn.
    -   `Available → Occupied`.
5.  Nếu không có:
    -   Thông báo.
    -   Đề xuất chờ/phương án khác.
6.  Bắt đầu Dining Session.

**Result:** Customer có bàn và có thể gọi món.

------------------------------------------------------------------------

## BF-02 --- Table Reservation

**Actors:** Customer, Staff

Flow:

1.  Customer mở chức năng đặt bàn.
2.  Nhập ngày, giờ, số người, họ tên, số điện thoại, ghi chú.
3.  Gửi request.
4.  Reservation ở `Pending`.
5.  Staff kiểm tra khả năng phục vụ.
6.  Nếu chấp nhận:
    -   `Pending → Confirmed`.
7.  Nếu không:
    -   `Pending → Rejected/Cancelled`.
8.  Khi khách đến:
    -   `Confirmed → Checked-in`.
9.  Bố trí bàn.
10. `Table → Occupied`.
11. Bắt đầu Dining Session.

**Result:** Reservation được xử lý và liên kết với quá trình phục vụ.

------------------------------------------------------------------------

## BF-03 --- Dine-in Ordering

**Actors:** Customer, Staff

Flow:

1.  Customer xem menu.
2.  Chọn món.
3.  Staff/Customer gửi yêu cầu.
4.  Kiểm tra Product availability.
5.  Xác nhận Product, Quantity, Unit Price, Note.
6.  Tạo Order/Order Items.
7.  Gửi item đến Kitchen/Bar.
8.  Trong Dining Session, Customer có thể gọi thêm.
9.  Các lần gọi thêm tiếp tục gắn với Dining Session hiện tại.

**Result:** Các yêu cầu món được ghi nhận chính xác trong phiên phục vụ.

------------------------------------------------------------------------

## BF-04 --- Kitchen / Bar Processing

**Actors:** Kitchen/Bar Staff, Staff

Flow cho từng Order Item:

1.  Item được gửi đến bộ phận xử lý.
2.  `Waiting`.
3.  Kitchen/Bar nhận.
4.  `Preparing`.
5.  Chuẩn bị xong.
6.  `Ready`.
7.  Staff mang món đến bàn.
8.  `Served`.

**Result:** Tiến độ từng món được theo dõi độc lập.

------------------------------------------------------------------------

## BF-05 --- Payment & Table Closing

**Actors:** Customer, Staff

Flow:

1.  Customer yêu cầu thanh toán.
2.  Staff mở Dining Session.
3.  Tổng hợp toàn bộ món.
4.  Tính Subtotal.
5.  Validate voucher/discount.
6.  Tính Total.
7.  Customer chọn Cash hoặc QR/Transfer.
8.  Staff xác nhận payment.
9.  Tạo invoice.
10. `Dining Session → Completed`.
11. `Table → Cleaning`.
12. Sau khi dọn: `Cleaning → Available`.

**Result:** Phiên phục vụ kết thúc và bàn sẵn sàng cho khách mới.

------------------------------------------------------------------------

## BF-06 --- Menu Management

**Actor:** Manager/Admin

Flow:

1.  Đăng nhập.
2.  Truy cập Menu Management.
3.  Quản lý Category/Product.
4.  Cập nhật giá, hình ảnh, mô tả, availability.
5.  Lưu thay đổi.
6.  Menu khách hàng phản ánh dữ liệu mới.

**Result:** Menu được quản lý tập trung.

------------------------------------------------------------------------

## BF-07 --- Inventory Management

**Actor:** Manager/Admin

Flow nhập:

`Receive Goods → Create Import → Quantity → Increase Stock → Save History`

Flow xuất/điều chỉnh:

`Select Item → Quantity → Export/Adjustment → Update Stock → Save History`

Nếu:

`Stock ≤ Minimum Stock`

→ Low Stock Warning.

**Result:** Tồn kho cơ bản và lịch sử biến động được kiểm soát.

------------------------------------------------------------------------

# 16. Business Rules

## BR-TABLE --- Table Rules

### BR-TABLE-01

Mỗi bàn tại một thời điểm chỉ có một trạng thái chính:

`Available / Reserved / Occupied / Cleaning`

### BR-TABLE-02

Chỉ bàn `Available` mới được xếp trực tiếp cho khách walk-in.

### BR-TABLE-03

Ưu tiên bàn có sức chứa phù hợp với số khách.

Staff/Manager có thể override nếu nghiệp vụ thực tế yêu cầu.

### BR-TABLE-04

Một bàn không được có hai Dining Session đang hoạt động cùng lúc.

------------------------------------------------------------------------

## BR-RES --- Reservation Rules

### BR-RES-01

Reservation tối thiểu phải có:

-   Họ tên.
-   Số điện thoại.
-   Ngày.
-   Giờ.
-   Số người.

### BR-RES-02

Trạng thái reservation:

`Pending → Confirmed → Checked-in → Completed`

Các trạng thái khác:

`Cancelled · Rejected · No-show`

### BR-RES-03

Không xác nhận reservation nếu không còn khả năng bố trí bàn phù hợp
trong thời gian đó.

### BR-RES-04

Khi khách đến:

`Confirmed → Checked-in`

và sau khi bố trí bàn:

`Table → Occupied`

### BR-RES-05

Staff có thể đánh dấu `No-show` nếu khách không đến sau thời gian cho
phép.

Thời gian này phải là configuration, không hard-code trong business
logic.

------------------------------------------------------------------------

## BR-ORDER --- Ordering Rules

### BR-ORDER-01

Một order phải có ít nhất một món hợp lệ.

### BR-ORDER-02

Không được thêm Product `Unavailable` vào order mới.

### BR-ORDER-03

Unit Price phải được lưu tại **thời điểm order**.

Nếu Product thay đổi giá sau đó, order cũ không thay đổi.

### BR-ORDER-04

Customer có thể gọi thêm món trong khi Dining Session đang active.

### BR-ORDER-05

Order Item có thể có note riêng.

------------------------------------------------------------------------

## BR-CART --- Customer Cart Rules

### BR-CART-01

Cart là dữ liệu tạm của Customer self-order và không phải Order đã xác
nhận.

### BR-CART-02

Customer chỉ được submit Cart vào một Dining Session hợp lệ được xác
định cho Customer/table context hiện tại.

### BR-CART-03

Giá và availability phải được validate lại khi submit; dữ liệu Cart
không phải transaction price snapshot.

### BR-CART-04

Recommendation chỉ được thêm món vào Cart sau hành động rõ ràng của
Customer.

------------------------------------------------------------------------

## BR-KITCHEN --- Kitchen / Bar Rules

### BR-KITCHEN-01

Chỉ món thuộc order đã được xác nhận mới đi vào quá trình chuẩn bị.

### BR-KITCHEN-02

Trạng thái cơ bản:

`Waiting → Preparing → Ready → Served`

### BR-KITCHEN-03

Item `Served` được coi là đã phục vụ Customer.

### BR-KITCHEN-04

Hủy món phụ thuộc trạng thái:

-   `Waiting`: có thể hủy theo quyền.
-   `Preparing`: cần quyền phù hợp/Manager approval.
-   `Served`: không xóa trực tiếp; sai sót xử lý bằng adjustment/refund
    workflow nếu được triển khai.

------------------------------------------------------------------------

## BR-PAY --- Payment Rules

### BR-PAY-01

Chỉ thanh toán cho Dining Session/order hợp lệ đang hoạt động.

### BR-PAY-02

`Subtotal = Σ(Unit Price × Quantity)`

`Total = Subtotal − Discount`

Tax/Service Fee có thể mở rộng sau.

### BR-PAY-03

Một giao dịch thành công không được ghi nhận thành công hai lần.

### BR-PAY-04

Sau khi Payment = `Paid`, order không được chỉnh sửa theo luồng thông
thường.

### BR-PAY-05

Sau thanh toán:

`Dining Session → Completed`

`Table → Cleaning`

------------------------------------------------------------------------

## BR-VOUCHER --- Voucher Rules

### BR-VOUCHER-01

Voucher chỉ hợp lệ khi:

-   Active.
-   Còn thời gian hiệu lực.
-   Đạt minimum order.
-   Chưa vượt usage limit.
-   Thỏa các điều kiện khác nếu có.

### BR-VOUCHER-02

Voucher phải được validate trước khi hoàn tất payment.

### BR-VOUCHER-03

Core: một invoice chỉ áp dụng tối đa một voucher.

### BR-VOUCHER-04

Discount không được làm `Total < 0`.

------------------------------------------------------------------------

## BR-INV --- Inventory Rules

### BR-INV-01

Mọi thay đổi tồn kho phải có lịch sử:

`Import / Export / Adjustment`

### BR-INV-02

Không xóa trực tiếp lịch sử nhập/xuất đã hoàn tất. Sai lệch được xử lý
bằng Adjustment.

### BR-INV-03

Core không cho tồn kho âm.

### BR-INV-04

Nếu:

`Stock ≤ Minimum Stock`

hệ thống phải có cảnh báo tồn thấp.

### BR-INV-05

Core chưa tự động trừ nguyên liệu theo recipe/BOM.

------------------------------------------------------------------------

## BR-PRODUCT --- Product Rules

### BR-PRODUCT-01

Product thuộc ít nhất một Category chính.

### BR-PRODUCT-02

Product tối thiểu có:

-   Name.
-   Price.
-   Category.
-   Status.

### BR-PRODUCT-03

Không xóa vật lý Product đã xuất hiện trong lịch sử order.

Dùng:

`Active → Inactive`

### BR-PRODUCT-04

Product `Inactive/Unavailable` không được tạo Order Item mới.

------------------------------------------------------------------------

## BR-USER --- User & Permission Rules

### BR-USER-01

Mỗi tài khoản nội bộ phải có Role/Permission.

### BR-USER-02

Staff chỉ truy cập chức năng được cấp quyền.

### BR-USER-03

Manager/Admin có quyền quản trị cao hơn Staff.

### BR-USER-04

Tài khoản nhân viên không còn làm việc nên:

`Active → Disabled`

thay vì xóa để giữ audit/history.

------------------------------------------------------------------------

## BR-LANG --- Language Rules

### BR-LANG-01

Ngôn ngữ mặc định là `VI`.

### BR-LANG-02

Nếu translation của `EN/ZH` không tồn tại:

`Requested Language → Missing → VI fallback`

### BR-LANG-03

Translation đã tạo phải được persist; runtime cache là optional.

Không gọi Translation API ở mọi page request.

### BR-LANG-04

Admin có thể chỉnh thủ công bản dịch quan trọng.

------------------------------------------------------------------------

## BR-AI --- AI Recommendation Rules

### BR-AI-01

AI chỉ Recommendation.

AI không tự thêm món vào Cart/Order.

### BR-AI-02

Không gợi ý Product `Unavailable`.

### BR-AI-03

Context có thể gồm:

`Time · Weather · Popularity · Order History · Current Cart`

### BR-AI-04

AI failure không được làm gián đoạn:

`Menu · Reservation · Order · Payment`

------------------------------------------------------------------------

# 17. Business Problems & Needs

## 17.1 Table Management

**Problem**

Quản lý bàn thủ công khiến Staff khó biết nhanh bàn nào:

-   Available.
-   Reserved.
-   Occupied.
-   Cleaning.

**Impact**

-   Khách phải chờ.
-   Xếp nhầm bàn.
-   Trùng reservation.
-   Staff phối hợp khó.

**Need**

Một hệ thống trạng thái bàn tập trung và dễ cập nhật.

------------------------------------------------------------------------

## 17.2 Reservation

**Problem**

Reservation qua điện thoại, tin nhắn hoặc ghi chú có thể phân tán.

**Impact**

-   Quên reservation.
-   Trùng lịch.
-   Sai số người.
-   Thiếu thông tin.
-   Khó kiểm tra lịch.

**Need**

Reservation Management tập trung với request, confirm/reject/cancel,
check-in và lịch đặt.

------------------------------------------------------------------------

## 17.3 Ordering

**Problem**

Order thủ công dễ:

-   Ghi nhầm món.
-   Nhầm quantity.
-   Bỏ sót.
-   Sai note.
-   Khó tổng hợp khi gọi thêm.

**Need**

Digital Ordering/POS với Order và Order Item rõ ràng.

------------------------------------------------------------------------

## 17.4 Kitchen / Bar Coordination

**Problem**

Staff khó biết món đang ở bước nào; Kitchen/Bar khó phối hợp khi đông.

**Impact**

-   Bỏ sót món.
-   Phục vụ chậm.
-   Staff phải hỏi bếp liên tục.

**Need**

Theo dõi trạng thái từng Order Item.

------------------------------------------------------------------------

## 17.5 Payment

**Problem**

Một Dining Session có thể có nhiều lần gọi món, làm tổng hợp thủ công dễ
sai.

**Need**

Billing tự tổng hợp toàn bộ món, discount và total trước payment.

------------------------------------------------------------------------

## 17.6 Menu

**Problem**

Menu truyền thống khó cập nhật khi đổi giá, thêm món, hết món hoặc thay
nội dung.

**Need**

Digital Menu được quản lý tập trung.

------------------------------------------------------------------------

## 17.7 Multilingual

**Problem**

Khách nước ngoài có thể khó đọc tên/mô tả món và đặt bàn. Duy trì nhiều
menu thủ công khó đồng bộ.

**Need**

Website hỗ trợ `VI / EN / ZH` với cơ chế translation và fallback.

------------------------------------------------------------------------

## 17.8 Inventory

**Problem**

Theo dõi kho thủ công gây khó khăn trong kiểm soát tồn và lịch sử biến
động.

**Need**

Inventory Management cơ bản gồm stock, import, export/adjustment,
history và low-stock warning.

------------------------------------------------------------------------

## 17.9 Reporting

**Problem**

Dữ liệu phân tán khiến Manager khó biết doanh thu, số đơn và sản phẩm
bán chạy.

**Need**

Dashboard/Report cơ bản dựa trên Order + Payment data.

------------------------------------------------------------------------

## 17.10 Recommendation / Selling

**Problem**

Khách có nhiều lựa chọn nhưng không luôn biết món phù hợp hoặc món nên
gọi kèm. Recommendation phụ thuộc Staff.

**Need**

Recommendation dựa trên context để hỗ trợ lựa chọn và cross-sell/upsell.

------------------------------------------------------------------------

## 17.11 User & Permission

**Problem**

Không thể để mọi nhân viên đều có quyền sửa giá, quản lý nhân viên, điều
chỉnh kho hoặc xem mọi dữ liệu.

**Need**

Role & Permission.

------------------------------------------------------------------------

# 18. Problem → Need Summary

  Business Problem                 Business Need
  -------------------------------- ------------------------
  Khó theo dõi bàn                 Table Management
  Reservation phân tán             Reservation Management
  Order dễ sai                     Digital Ordering / POS
  Khó theo dõi Kitchen/Bar         Order Item Status
  Billing thủ công                 Billing & Payment
  Menu khó cập nhật                Digital Menu
  Rào cản ngôn ngữ                 Multilingual
  Khó kiểm soát tồn                Inventory Management
  Dữ liệu phân tán                 Reporting
  Recommendation phụ thuộc Staff   AI Recommendation
  Quyền truy cập không rõ          Role & Permission

------------------------------------------------------------------------

# 19. Scope Baseline

## 19.1 Core Business Scope

-   Authentication cơ bản cho các actor cần tài khoản.
-   Menu & Product.
-   Table Management.
-   Reservation.
-   Dining Session.
-   Ordering / POS.
-   Kitchen / Bar status cơ bản.
-   Billing.
-   Cash.
-   QR / Bank Transfer confirmation.
-   Customer Management.
-   Employee Management.
-   Role & Permission.
-   Reporting cơ bản.
-   Multilingual `VI / EN / ZH`.

## 19.2 Simplified Core

Các chức năng vẫn thuộc phiên bản triển khai nhưng được giữ ở mức đơn
giản:

### Inventory

-   Stock.
-   Import.
-   Export/Adjustment.
-   History.
-   Low-stock warning.

### Voucher

-   Code.
-   Discount.
-   Validity.
-   Basic conditions.
-   Usage limit.

### AI Recommendation

-   Rule-based/context-based trước.
-   Weather/time/product/cart context.
-   Không bắt buộc ML.

## 19.3 Future Scope

-   Advanced Inventory.
-   Recipe/BOM.
-   Automatic ingredient deduction.
-   Advanced Voucher.
-   Loyalty / Membership.
-   Advanced CRM.
-   Full Online Payment Gateway.
-   Advanced Refund workflow.
-   Machine Learning Recommendation.
-   Advanced Personalization.
-   Dedicated Kitchen Display System.
-   Mobile Application.
-   Takeaway ordering.
-   Delivery ordering/integration.
-   News/CMS/WordPress.
-   Product gallery/video.
-   AI Analytics.

------------------------------------------------------------------------

# 20. Review Decisions

## DEC-01 --- Restaurant/POS first

Hệ thống được thiết kế theo hướng **restaurant/POS system**, không phải
chỉ là website bán đồ ăn online.

Core flow:

`Table → Dining Session → Order → Kitchen/Bar → Payment`

## DEC-02 --- Dining Session

Dining Session được chốt là khái niệm nghiệp vụ trung tâm để hỗ trợ
nhiều lần gọi món trong một phiên bàn.

## DEC-03 --- Human roles

Giữ 4 Human Actor chính. Không chia role quá chi tiết ở Business
Analysis.

## DEC-04 --- Reservation table selection

Customer không bắt buộc tự chọn bàn cụ thể. Core ưu tiên chọn
ngày/giờ/số người và để quán/hệ thống bố trí.

## DEC-05 --- Product history

Không xóa vật lý Product đã xuất hiện trong Order history.

## DEC-06 --- Historical price

Order Item phải giữ Unit Price tại thời điểm gọi món.

## DEC-07 --- Kitchen granularity

Trạng thái được quản lý ở mức Order Item để các món có thể hoàn thành
độc lập.

## DEC-08 --- Inventory boundary

Core không triển khai Recipe/BOM hoặc tự động trừ từng nguyên liệu theo
món.

## DEC-09 --- Payment boundary

Core ưu tiên Cash + QR/Bank Transfer confirmation; payment gateway đầy
đủ thuộc Future/Optional.

## DEC-10 --- Multilingual

VI là mặc định; EN và ZH được hỗ trợ; thiếu translation thì fallback VI.

## DEC-11 --- Translation strategy

Không gọi Translation API lại ở mọi request. Translation động nên được
persist, có thể cache và cho phép chỉnh thủ công khi cần.

## DEC-12 --- AI boundary

AI Recommendation là capability hỗ trợ bán hàng, không phải dependency
của Core Business.

Nếu AI lỗi, nghiệp vụ chính vẫn hoạt động.

## DEC-13 --- Core-first scope control

Các nghiệp vụ nâng cao được giữ ở Future Scope thay vì mở rộng Core
không cần thiết.

## DEC-14 --- Dine-in only Core

Core hiện tại chỉ triển khai Dine-in. Takeaway và Delivery không có
Core flow/entity và thuộc Future Scope.

## DEC-15 --- Customer self-order boundary

Customer self-order chỉ hoạt động trong Dining Session hợp lệ khi
capability được bật. Cart là dữ liệu tạm; Order chỉ được tạo sau khi
backend validate lại session, Product, availability và price.

## DEC-16 --- Customer account ownership

Customer Account là capability tùy chọn. Khi có account, hệ thống phải
có relationship rõ giữa authentication User và Customer business
profile để enforce own profile/order/reservation.

## DEC-17 --- Inventory boundary

Core Inventory chỉ gồm Inventory Item, Stock Movement, import/export/
adjustment, history và low-stock warning. Supplier/Purchasing thuộc
Future Scope.

## DEC-18 --- Content and media boundary

Core dùng trang thông tin nhà hàng và một ảnh đại diện Product.
News/CMS/WordPress và gallery/video thuộc Future Scope.

------------------------------------------------------------------------

# 21. Business Analysis Baseline v1.0

## Business Model

89 Beer Garden vận hành theo mô hình phục vụ khách tại quán, kết hợp đặt
bàn trực tuyến và hệ thống quản lý bán hàng/POS.

## Primary Actors

`Customer · Staff · Kitchen/Bar Staff · Manager/Admin`

## External Systems

`Translation Service · Weather Service · Payment Service (optional/future)`

## Primary Business Flow

`Reservation/Walk-in → Table → Dining Session → Ordering → Kitchen/Bar → Serving → Payment → Complete`

## Supporting Capabilities

`Inventory · Voucher · Reporting · Multilingual · AI Recommendation`

## Primary Goal

Số hóa quá trình phục vụ và quản lý bán hàng của 89 Beer Garden, giảm
sai sót vận hành, tập trung dữ liệu và cải thiện trải nghiệm chọn món
của khách hàng.

------------------------------------------------------------------------

# 22. Handoff to System Requirements

Tài liệu này là **Business Analysis Baseline v1.0**.

System Requirements phải dựa trên các Business Flow, Business Rules,
Problem → Need và Scope đã chốt tại đây.

Ở bước tiếp theo cần chuyển Business Need thành:

-   Functional Requirements (`FR-*`).
-   Non-functional Requirements (`NFR-*`).
-   Integration Requirements.
-   Role/Permission Requirements.
-   Data requirements ở mức hệ thống.
-   Requirement priority.
-   Core/Future mapping.
-   Acceptance criteria khi phù hợp.

Ví dụ:

`Business Need: Table Management`

→ `FR-TABLE-*`

`Business Need: Reservation Management`

→ `FR-RES-*`

`Business Need: Digital Ordering / POS`

→ `FR-ORDER-*`

`Business Rule: BR-ORDER-03`

→ System Requirement phải đảm bảo giá lịch sử của Order Item không thay
đổi khi Product Price được cập nhật.

------------------------------------------------------------------------

# 23. Guidance for AI Coding Agents

Khi AI đọc tài liệu này:

1.  Không tự ý mở rộng Future Scope thành Core.
2.  Không bỏ qua Dining Session khi thiết kế order/table flow.
3.  Không giả định `1 Table = 1 Order`.
4.  Không lấy giá hiện tại của Product để tính lại Order lịch sử.
5.  Không hard-delete Product/Employee nếu cần giữ lịch sử.
6.  Không để AI Recommendation trở thành dependency của Core.
7.  Không gọi Translation API ở mọi request.
8.  Luôn giữ VI làm fallback language.
9.  Kitchen status cần hỗ trợ ở mức Order Item.
10. Nếu implementation đề xuất mâu thuẫn Business Rule trong tài liệu
    này, phải ưu tiên rule đã chốt hoặc đánh dấu cần Change Request.
11. Database schema, framework implementation và API contract chưa được
    chốt bởi tài liệu Business Analysis; không suy diễn quyết định kỹ
    thuật nếu chưa có tài liệu tương ứng.
12. Mọi thay đổi nghiệp vụ lớn sau baseline phải được coi là thay đổi
    scope và cần review.

------------------------------------------------------------------------

**Status:** `FINAL BASELINE v1.0`
