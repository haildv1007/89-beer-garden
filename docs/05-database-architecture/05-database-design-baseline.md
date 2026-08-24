# 05. Database & Architecture — Database Design Baseline

**Project:** 89 Beer Garden Website & Management System  
**Document:** Database Design Baseline  
**Version:** 1.0  
**Status:** Finalized / Ready for System Architecture

---

## 1. Purpose

Tài liệu này chốt **Database Design Baseline** cho hệ thống 89 Beer Garden.

Mục tiêu của tài liệu là cung cấp cho developer hoặc AI coding agent một mô hình dữ liệu thống nhất để triển khai MySQL/Laravel mà không phải tự suy diễn lại nghiệp vụ.

Tài liệu chốt:

- Core data domains.
- Core entities và database tables.
- Relationships và cardinality.
- Transaction model của Restaurant Operation.
- Reservation, Dining Session, Order, Billing và Payment boundaries.
- Historical data rules.
- Inventory/Stock Movement model cơ bản.
- Authentication/Authorization data model.
- Naming, money, status, soft-delete và indexing strategy.
- Transaction/data-integrity rules.
- Những phần chủ động không đưa vào Core Schema.

Tài liệu này **không phải danh sách các task Database Design** và không mô tả lại quá trình phân tích. Đây là **baseline cuối cùng** dùng làm nguồn tham chiếu cho System Architecture và Development.

---

# 2. Database Scope

Core Database được chia thành các domain:

```text
Identity & Access
Menu
Customer & Reservation
Restaurant Operation
Ordering
Billing & Payment
Inventory
Promotion
Localization & Configuration
```

Các capability sau **không cần Core Table riêng ở baseline hiện tại**:

```text
Reports
AI Recommendation
Translation cache
```

Reports được derive từ transaction data.

AI Recommendation không cần Core table. Dynamic/manual Translation và
runtime System Settings cần persistent tables; runtime cache vẫn là
technical optimization có thể tái tạo.

---

# 3. Core Database Model

Core operational model của hệ thống:

```text
Customer
   │
   ├──────── Reservation
   │
   ▼
RestaurantTable
   │
   ▼
DiningSession
   │
   ├──────── Order
   │            │
   │            └──── OrderItem ─── Product ─── Category
   │
   └──────── Bill
                │
                ├──── Payment
                └──── Voucher
```

Inventory:

```text
InventoryItem
   │
   ▼
StockMovement
```

Identity & Access:

```text
Employee
   │
   ▼
User
   │
   ▼
Role
   │
   ▼
Permission
```

Backbone quan trọng nhất của toàn hệ thống:

```text
RestaurantTable
      ↓
DiningSession
      ↓
Multiple Orders
      ↓
OrderItems
```

và:

```text
DiningSession
      ↓
Bill
      ↓
Payment Attempts
```

---

# 4. Core Tables

## 4.1 Identity & Access

```text
users
roles
permissions
role_permissions
employees
```

## 4.2 Menu

```text
categories
products
```

## 4.3 Customer & Reservation

```text
customers
reservations
```

## 4.4 Restaurant Operation

```text
restaurant_tables
dining_sessions
```

## 4.5 Ordering

```text
orders
order_items
```

## 4.6 Billing & Promotion

```text
vouchers
bills
payments
```

## 4.7 Inventory

```text
inventory_items
stock_movements
```

## 4.8 Localization & Configuration

```text
translations
system_settings
```

Core baseline gồm **20 tables**.

---

# 5. Identity & Access Model

## 5.1 User

`users` đại diện cho account dùng để:

- Authentication.
- Authorization.
- Account status.
- Login tracking.

User không phải hồ sơ nghiệp vụ của nhân viên.

Baseline fields:

```text
users
├── id
├── email
├── password
├── role_id
├── status
├── last_login_at
├── created_at
└── updated_at
```

Rules:

```text
email UNIQUE
role_id → roles.id
```

---

## 5.2 Employee

`employees` lưu thông tin nghiệp vụ của nhân viên.

```text
employees
├── id
├── user_id
├── employee_code
├── name
├── phone
├── position
├── status
├── created_at
├── updated_at
└── deleted_at
```

Relationship:

```text
Employee 1 ─── 0..1 User
```

`user_id` nullable vì Employee có thể tồn tại mà chưa có account.

Rules:

```text
employee_code UNIQUE
user_id UNIQUE nullable
```

---

## 5.3 Role & Permission

Baseline sử dụng:

```text
Role 1 ─── N User
```

Một User có một Role chính trong Core Scope.

Role examples:

```text
admin
manager
staff
kitchen
customer
```

Role và Permission:

```text
Role N ─── N Permission
```

qua:

```text
role_permissions
```

Baseline:

```text
roles
├── id
├── name
├── code
├── description
├── created_at
└── updated_at

permissions
├── id
├── name
├── code
├── description
├── created_at
└── updated_at

role_permissions
├── role_id
└── permission_id
```

Permission code có thể theo pattern:

```text
product.view
product.create
order.update
payment.create
report.view
```

`role_permissions` dùng composite unique:

```text
(role_id, permission_id)
```

Không cần `user_roles` trong Core Scope hiện tại.

---

# 6. Menu & Product Model

## 6.1 Category

Relationship:

```text
Category 1 ─── N Product
```

Baseline:

```text
categories
├── id
├── name
├── slug
├── description
├── status
├── sort_order
├── created_at
├── updated_at
└── deleted_at
```

Rules:

```text
slug UNIQUE
```

---

## 6.2 Product

```text
products
├── id
├── category_id
├── name
├── slug
├── description
├── price
├── image_url
├── status
├── is_available
├── created_at
├── updated_at
└── deleted_at
```

Relationship:

```text
products.category_id → categories.id
```

Rules:

```text
slug UNIQUE
price >= 0
```

`products.price` là **giá hiện tại**.

Giá lịch sử của transaction không được tính lại từ field này.

---

## 6.3 Product Images Decision

Core Scope chỉ cần một ảnh đại diện cho Product:

```text
products.image_url
```

Không tạo `product_images` ở baseline.

Nếu sau này cần gallery/multiple images mới mở rộng:

```text
Product 1 ─── N ProductImage
```

---

# 7. Customer Model

`Customer` và `User` là hai concept khác nhau.

```text
Customer ≠ User
```

Customer không bắt buộc phải đăng ký account để:

- Browse Menu.
- Make Reservation.
- Được phục vụ dạng Walk-in.
- Có Dining Session.

Baseline:

```text
customers
├── id
├── user_id
├── name
├── phone
├── email
├── note
├── created_at
├── updated_at
└── deleted_at
```

Indexes:

```text
phone
email
```

Rules:

```text
user_id → users.id UNIQUE nullable
```

Relationship:

```text
Customer 1 ─── 0..1 User
```

`user_id` nullable vì guest/walk-in Customer không bắt buộc có account.
Một Customer Account chỉ được liên kết tối đa một Customer business
profile để enforce ownership.

Không enforce `phone UNIQUE` ở baseline để tránh gắn cứng business assumption chưa cần thiết.

Relationships:

```text
Customer 1 ─── N Reservation
Customer 1 ─── N DiningSession
```

`DiningSession.customer_id` có thể nullable cho anonymous/walk-in context.

---

# 8. Reservation Model

Reservation đại diện cho **yêu cầu/kế hoạch sử dụng bàn**, không phải phiên phục vụ thực tế.

```text
reservations
├── id
├── customer_id
├── table_id
├── reservation_code
├── reservation_date
├── reservation_time
├── party_size
├── status
├── note
├── confirmed_by_employee_id
├── confirmed_at
├── checked_in_at
├── completed_at
├── no_show_at
├── cancelled_at
├── created_at
└── updated_at
```

Relationships:

```text
Customer 1 ─── N Reservation
RestaurantTable 1 ─── N Reservation
Employee 1 ─── N confirmed Reservations
```

FK:

```text
customer_id → customers.id
table_id → restaurant_tables.id nullable
confirmed_by_employee_id → employees.id nullable
```

`table_id` nullable vì Customer không bắt buộc chọn bàn cụ thể khi gửi Reservation.

Rules:

```text
reservation_code UNIQUE
party_size > 0
```

Baseline lifecycle:

```text
pending
confirmed
checked-in
no-show
rejected
cancelled
completed
```

Reservation Submitted phải bắt đầu ở:

```text
status = pending
```

Không tự coi Reservation mới tạo là Confirmed.

`no_show_timeout_minutes` được đọc từ `system_settings`.

---

# 9. Reservation vs Dining Session

Hai entity phải được giữ tách biệt.

Reservation:

> kế hoạch/yêu cầu sử dụng bàn trước khi khách được phục vụ.

Dining Session:

> phiên phục vụ thực tế khi khách sử dụng bàn.

Flow:

```text
Reservation
    ↓
Confirmed
    ↓
Customer Arrives
    ↓
DiningSession
```

Walk-in flow:

```text
No Reservation
      ↓
DiningSession
```

Relationship:

```text
Reservation 1 ─── 0..1 DiningSession
```

Implementation:

```text
dining_sessions.reservation_id UNIQUE nullable
```

Một Reservation không bắt buộc phải tạo Dining Session nếu bị rejected/cancelled/no-show.

---

# 10. Restaurant Table Model

Table vật lý được lưu ở:

```text
restaurant_tables
```

Không dùng tên `tables` để tránh nhầm với database table.

Baseline:

```text
restaurant_tables
├── id
├── code
├── name
├── capacity
├── runtime_status
├── is_active
├── location
├── created_at
├── updated_at
└── deleted_at
```

Rules:

```text
code UNIQUE
capacity > 0
```

Canonical runtime status:

```text
available
reserved
occupied
cleaning
```

Configuration availability được lưu riêng:

```text
is_active = true / false
```

`runtime_status` là source of truth vận hành và phải được cập nhật trong
cùng transaction với Reservation/Dining Session liên quan. System vẫn
phải re-check relationship để chống state drift. `is_active = false`
loại Table khỏi assignment nhưng không thay thế `cleaning`.

---

# 11. Dining Session Model

`DiningSession` là operational context trung tâm của Restaurant Operation.

```text
RestaurantTable
      ↓
DiningSession
      ↓
Orders
```

Baseline:

```text
dining_sessions
├── id
├── session_code
├── table_id
├── customer_id
├── reservation_id
├── opened_by_employee_id
├── completed_by_employee_id
├── status
├── started_at
├── ended_at
├── guest_count
├── note
├── created_at
└── updated_at
```

Relationships:

```text
RestaurantTable 1 ─── N DiningSession
Customer 1 ─── N DiningSession
Reservation 1 ─── 0..1 DiningSession
Employee → opened_by / completed_by
```

FK:

```text
table_id → restaurant_tables.id
customer_id → customers.id nullable
reservation_id → reservations.id nullable
opened_by_employee_id → employees.id
completed_by_employee_id → employees.id nullable
```

Rules:

```text
session_code UNIQUE
reservation_id UNIQUE nullable
guest_count > 0
ended_at >= started_at
```

Baseline status:

```text
active
completed
```

`billing` là workflow/UI context, không phải Dining Session status.
Không dùng `closed`; canonical terminal status là `completed`.

## Critical Integrity Rule

Một Restaurant Table chỉ được có tối đa:

```text
1 active DiningSession
```

tại cùng thời điểm.

Với MySQL/Laravel, rule này phải được bảo vệ ở application/service layer bằng transaction/locking phù hợp, không chỉ dựa vào UI.

---

# 12. Table ≠ Order

Không thiết kế relationship chính:

```text
RestaurantTable
      ↓
Order
```

Không cần:

```text
orders.table_id
```

Relationship đúng:

```text
Order
 ↓
DiningSession
 ↓
RestaurantTable
```

Điều này cho phép:

```text
Table 05
   ↓
DiningSession #DS01
   ├── Order #001
   ├── Order #002
   └── Order #003
```

Additional Order tạo **Order mới trong cùng Dining Session**, không overwrite Order cũ và không mở Dining Session mới.

---

# 13. Order Model

```text
orders
├── id
├── order_code
├── dining_session_id
├── created_by_employee_id
├── created_by_customer_id
├── source
├── note
├── ordered_at
├── created_at
└── updated_at
```

Relationships:

```text
DiningSession 1 ─── N Order
Employee 1 ─── N Order
Customer 1 ─── N self-service Order
```

FK:

```text
dining_session_id → dining_sessions.id
created_by_employee_id → employees.id nullable
created_by_customer_id → customers.id nullable
```

Rules:

```text
order_code UNIQUE
source IN (staff, customer)
```

Rules:

- `source = staff` yêu cầu `created_by_employee_id`.
- `source = customer` chỉ hợp lệ khi request có session-bound Dining
  Session context; `created_by_customer_id` được lưu khi Dining Session/
  account có Customer profile.
- Một creator FK có thể nullable để hỗ trợ guest self-order, nhưng
  `source`, request context và audit timestamp luôn bắt buộc.

Order-level status nếu cần hiển thị là derived summary từ Order Item
states; `order_items.status` mới là processing source of truth. Core
không lưu `orders.status` để tránh state drift.

Order phải thuộc một active/valid Dining Session theo business rule.

---

# 14. Order Item Model

```text
order_items
├── id
├── order_id
├── product_id
├── product_name
├── quantity
├── unit_price
├── line_total
├── status
├── note
├── cancelled_by_employee_id
├── cancelled_at
├── cancellation_reason
├── created_at
└── updated_at
```

Relationships:

```text
Order 1 ─── N OrderItem
Product 1 ─── N OrderItem
```

FK:

```text
order_id → orders.id
product_id → products.id
cancelled_by_employee_id → employees.id nullable
```

Rules:

```text
quantity > 0
unit_price >= 0
line_total >= 0
```

```text
line_total = unit_price × quantity
```

`line_total` là stored transaction snapshot được tính trong cùng
CreateOrder transaction; Billing không tự lấy lại current Product price.

Kitchen/Bar processing có thể sử dụng `order_items.status`.

Typical item lifecycle:

```text
waiting
↓
preparing
↓
ready
↓
served
```

Alternate terminal transition:

```text
waiting / preparing → cancelled
```

Cancellation phải giữ transaction history, actor, timestamp và reason
thay vì xóa item. Waiting có thể do Staff/Manager/Admin hủy; Preparing
chỉ Manager/Admin trong Core; Served không được chuyển Cancelled theo
normal flow.

---

# 15. Historical Price Snapshot

Order Item phải lưu snapshot transaction:

```text
product_name
unit_price
quantity
line_total
```

Ví dụ:

```text
Product.price tại thời điểm bán = 50,000

OrderItem.unit_price = 50,000
```

Nếu sau này:

```text
Product.price = 55,000
```

Order cũ vẫn phải giữ:

```text
OrderItem.unit_price = 50,000
```

Không được tính lại historical Order từ `products.price`.

Đây là Data Integrity Rule bắt buộc.

---

# 16. Billing Model

**Billing Unit = Dining Session.**

Không bill riêng từng Order nếu toàn bộ các Order thuộc cùng một phiên phục vụ.

```text
DiningSession
├── Order #1
├── Order #2
└── Order #N
        ↓
       Bill
```

Baseline:

```text
bills
├── id
├── bill_code
├── dining_session_id
├── voucher_id
├── subtotal
├── discount_amount
├── total_amount
├── status
├── issued_at
├── created_at
└── updated_at
```

Relationship:

```text
DiningSession 1 ─── 0..1 Bill
```

Implementation:

```text
bills.dining_session_id UNIQUE
```

Rules:

```text
bill_code UNIQUE
subtotal >= 0
discount_amount >= 0
total_amount >= 0
```

Baseline status:

```text
draft
unpaid
paid
cancelled
```

Bill sử dụng historical values từ Order Items.

Không cần `bill_items` ở Core Scope vì Order/OrderItem đã giữ transaction snapshot cần thiết.

**Invoice decision:** Core không có `invoices` table. Bill tồn tại trước
payment; khi `bills.status = paid`, Invoice là printable/read-only
representation của paid Bill và các Order Items liên quan.

---

# 17. Voucher Model

Core Scope cho phép:

```text
Bill → 0..1 Voucher
Voucher → N Bills
```

Không hỗ trợ voucher stacking trong baseline.

```text
vouchers
├── id
├── code
├── name
├── discount_type
├── discount_value
├── min_order_amount
├── max_discount_amount
├── start_at
├── end_at
├── usage_limit
├── used_count
├── status
├── created_at
├── updated_at
└── deleted_at
```

Types:

```text
fixed
percentage
```

Rules:

```text
code UNIQUE
discount_value >= 0
usage_limit >= 0 nullable
```

Không cần `bill_vouchers` hoặc `voucher_usages` ở Core Scope hiện tại.

`used_count` chỉ tăng khi Bill dùng Voucher được thanh toán thành công
và phải được lock/re-check trong Complete Payment transaction. Apply vào
Bill draft hoặc Payment failed không tiêu thụ usage.

---

# 18. Bill ≠ Payment

Bill và Payment là hai entity khác nhau.

Bill:

> số tiền cần thanh toán.

Payment:

> một lần thực hiện/xác nhận thanh toán.

Relationship:

```text
Bill 1 ─── N Payment
```

Lý do:

```text
Bill
 ├── Payment #1 → Failed
 └── Payment #2 → Success
```

Không thiết kế:

```text
Bill 1 ─── 1 Payment
```

vì sẽ không biểu diễn tốt payment retry/history.

---

# 19. Payment Model

```text
payments
├── id
├── payment_code
├── bill_id
├── processed_by_employee_id
├── method
├── amount
├── status
├── transaction_reference
├── paid_at
├── failed_at
├── failure_reason
├── created_at
└── updated_at
```

Relationships:

```text
Bill 1 ─── N Payment
Employee 1 ─── N Payment
```

FK:

```text
bill_id → bills.id
processed_by_employee_id → employees.id
```

Rules:

```text
payment_code UNIQUE
amount > 0
```

Baseline methods:

```text
cash
bank_transfer
other
```

Baseline status:

```text
pending
success
failed
cancelled
```

QR/Bank Transfer không tự được coi là success nếu Staff chưa xác nhận trong Core payment flow.

---

# 20. Payment & Session Integrity

Normal completion flow:

```text
DiningSession
      ↓
Bill
      ↓
Payment
      ↓
Success
      ↓
Bill = Paid
      ↓
DiningSession = Completed
      ↓
Table becomes available after operational cleanup
```

Failure flow:

```text
Payment Failed
      ↓
Bill remains unpaid
      ↓
DiningSession remains active
      ↓
Table remains occupied
      ↓
Retry Payment
```

Critical rule:

> **Payment failure không được đóng Dining Session.**

Duplicate payment completion phải được backend ngăn chặn.

---

# 21. Inventory Model

Inventory baseline chỉ quản lý kho cơ bản.

Không triển khai Recipe/BOM/ingredient consumption phức tạp trong Core Scope.

```text
inventory_items
├── id
├── product_id
├── sku
├── name
├── unit
├── current_stock
├── minimum_stock
├── status
├── created_at
├── updated_at
└── deleted_at
```

Relationship:

```text
Product 1 ─── 0..1 InventoryItem
```

Implementation:

```text
inventory_items.product_id UNIQUE nullable
```

Một Inventory Item có thể gắn với Product bán trực tiếp.

Không bắt buộc mọi Product phải có Inventory Item.

Rules:

```text
sku UNIQUE
current_stock >= 0
minimum_stock >= 0
```

`current_stock` phục vụ truy vấn nhanh nhưng không thay thế Stock Movement history.

---

# 22. Stock Movement Model

Không chỉ lưu:

```text
current_stock
```

mà phải giữ lịch sử thay đổi kho.

```text
stock_movements
├── id
├── inventory_item_id
├── type
├── quantity
├── stock_before
├── stock_after
├── note
├── created_by_employee_id
└── created_at
```

Relationship:

```text
InventoryItem 1 ─── N StockMovement
```

Types baseline:

```text
import
export
adjustment_in
adjustment_out
damaged
return
```

Rules:

```text
quantity > 0
```

Negative stock không được cho phép ở Core Scope hiện tại.

---

# 23. Translation & System Settings Model

Persistent dynamic/manual translations:

```text
translations
├── id
├── translatable_type
├── translatable_id
├── field
├── locale
├── source_text
├── translated_text
├── source_hash
├── source
├── updated_by_employee_id
├── created_at
└── updated_at
```

Rules:

```text
locale IN (en, zh)
source IN (provider, manual)
UNIQUE(translatable_type, translatable_id, field, locale)
```

VI là source/fallback và không cần duplicate thành translation row.
Manual translation được ưu tiên hơn provider translation. Cache có thể
được tạo từ table này nhưng không phải source of truth.

Persistent runtime settings:

```text
system_settings
├── id
├── key
├── value
├── type
├── updated_by_employee_id
├── created_at
└── updated_at
```

Rules:

```text
key UNIQUE
type IN (integer, boolean, string)
```

Core keys ban đầu:

```text
no_show_timeout_minutes
```

API credentials/secrets không được lưu trong `system_settings`; chúng
thuộc environment configuration.

---

# 24. Reporting Data Source

Không tạo:

```text
reports
revenue_reports
order_reports
top_product_reports
```

trong Core Database.

Reports được derive từ transaction data.

Examples:

```text
Revenue
   ← successful payments / paid bills

Order Count
   ← orders

Top Products
   ← order_items + products

Reservation Metrics
   ← reservations
```

Nếu performance sau này yêu cầu, aggregate/cache/report snapshots có thể được bổ sung như optimization layer.

---

# 25. AI Recommendation Boundary

Recommendation không phải Core Database dependency.

Baseline:

```text
Context
  ↓
Recommendation Service
  ↓
Recommended Products
```

Không cần:

```text
recommendations
recommendation_logs
```

trong Core Schema.

Nếu recommendation service lỗi:

```text
Menu / Product / Order
```

vẫn phải hoạt động bình thường.

AI không được tự tạo hoặc thêm Product vào Order.

---

# 26. Translation Boundary

Customer UI hỗ trợ:

```text
VI — Default
EN
ZH
```

Dynamic/manual translation được lưu tại:

```text
translations
```

Runtime cache có thể được bổ sung nhưng luôn tái tạo được từ persistent
translation rows và VI source content.

Translation failure phải fallback VI và không làm Core Menu/Reservation lỗi.

---

# 27. Relationship Baseline

| Parent | Child | Cardinality |
|---|---|---|
| Role | User | 1 → N |
| Role | Permission | N ↔ N |
| Employee | User | 1 → 0..1 |
| User | Customer | 1 → 0..1 |
| Category | Product | 1 → N |
| Customer | Reservation | 1 → N |
| RestaurantTable | Reservation | 1 → N |
| Customer | DiningSession | 1 → N |
| RestaurantTable | DiningSession | 1 → N |
| Reservation | DiningSession | 1 → 0..1 |
| DiningSession | Order | 1 → N |
| Order | OrderItem | 1 → N |
| Product | OrderItem | 1 → N |
| DiningSession | Bill | 1 → 0..1 |
| Bill | Payment | 1 → N |
| Voucher | Bill | 1 → N |
| Product | InventoryItem | 1 → 0..1 |
| InventoryItem | StockMovement | 1 → N |
| Employee | Translation | 1 → N updated rows |
| Employee | SystemSetting | 1 → N updated rows |

---

# 28. Foreign Key Baseline

FK đặt ở phía `many` trong quan hệ 1-N.

Examples:

```text
users.role_id
products.category_id

reservations.customer_id
reservations.table_id

dining_sessions.table_id
dining_sessions.customer_id
dining_sessions.reservation_id

orders.dining_session_id
orders.created_by_employee_id
orders.created_by_customer_id
order_items.order_id
order_items.product_id
order_items.cancelled_by_employee_id

bills.dining_session_id
bills.voucher_id
payments.bill_id

stock_movements.inventory_item_id

translations.updated_by_employee_id
system_settings.updated_by_employee_id
```

1 → 0..1 relationships sử dụng FK + UNIQUE khi phù hợp:

```text
employees.user_id UNIQUE nullable
customers.user_id UNIQUE nullable
dining_sessions.reservation_id UNIQUE nullable
bills.dining_session_id UNIQUE
inventory_items.product_id UNIQUE nullable
```

---

# 29. Business Identifier Baseline

Các business identifiers sau phải unique:

```text
users.email

employees.employee_code

categories.slug
products.slug

restaurant_tables.code

reservations.reservation_code
dining_sessions.session_code
orders.order_code

bills.bill_code
payments.payment_code

vouchers.code

inventory_items.sku

system_settings.key
```

Database PK vẫn sử dụng:

```text
id
```

Business code không thay thế primary key nội bộ.

---

# 30. Naming Convention

Table:

```text
snake_case
plural
```

Examples:

```text
users
restaurant_tables
dining_sessions
order_items
stock_movements
```

Primary key:

```text
id
```

Foreign key:

```text
<entity>_id
```

Timestamps:

```text
created_at
updated_at
```

Soft delete:

```text
deleted_at
```

Không sử dụng naming kiểu:

```text
tbl_product
TableOrder
ProductData
```

---

# 31. Money Strategy

Hệ thống chủ yếu sử dụng VND.

Baseline:

> **Lưu tiền bằng integer/BIGINT đại diện số VNĐ.**

Examples:

```text
50000
89000
230000
```

Áp dụng cho:

```text
products.price
order_items.unit_price
order_items.line_total

bills.subtotal
bills.discount_amount
bills.total_amount

payments.amount
```

Không dùng:

```text
FLOAT
DOUBLE
```

cho monetary values.

---

# 32. Status Strategy

Không hard-code business status bằng DB ENUM nếu không cần.

Baseline:

> **VARCHAR + PHP Enum**

Examples:

```text
ReservationStatus
DiningSessionStatus
OrderItemStatus
BillStatus
PaymentStatus
StockMovementType
```

Database giữ string value; Laravel/application layer định nghĩa allowed values và transition rules.

---

# 33. Soft Delete Strategy

Soft delete phù hợp với master data:

```text
employees
categories
products
customers
restaurant_tables
vouchers
inventory_items
```

Transaction data không nên bị xóa vật lý:

```text
dining_sessions
orders
order_items
bills
payments
stock_movements
translations
system_settings
```

Transaction bị hủy phải giữ record và sử dụng status như:

```text
cancelled
failed
```

để giữ audit/history.

---

# 34. Referential Integrity

Không cascade-delete transaction history chỉ vì master data bị deactivate hoặc soft-delete.

Examples:

```text
Product
  ↓
OrderItem
```

Product bị xóa mềm không được xóa OrderItem cũ.

Tương tự:

```text
Employee → Order / Payment
Customer → Reservation / DiningSession
RestaurantTable → DiningSession
Voucher → Bill
```

Historical transaction phải tiếp tục truy xuất được.

---

# 35. Indexing Baseline

Không index mọi column.

Ưu tiên fields thường dùng cho:

- Join.
- Filter.
- Search.
- Sort.
- Operational queue.

Baseline indexes:

```text
products(category_id, status)

reservations(reservation_date, reservation_time, status)
reservations(table_id, reservation_date, reservation_time, status)

restaurant_tables(runtime_status, is_active)
dining_sessions(table_id, status)

orders(dining_session_id, ordered_at)
order_items(order_id, status)

payments(bill_id, status)

stock_movements(inventory_item_id, created_at)

translations(translatable_type, translatable_id, field, locale)
```

Các simple indexes như business codes/unique fields được tạo thông qua UNIQUE constraint.

Query optimization sâu chỉ thực hiện sau khi có query pattern/performance data thực tế.

---

# 36. Transaction Boundaries

Các operation sau phải được xử lý atomically.

## 36.1 Open Dining Session

```text
Check Table
    ↓
Check Existing Active Session
    ↓
Create DiningSession
    ↓
Table runtime_status = occupied
```

Nếu một bước fail:

```text
Rollback
```

Không được tạo hai active sessions do concurrent requests.

---

## 36.2 Create Order

```text
Validate DiningSession
        ↓
Validate Actor/Customer Session Context
        ↓
Create Order
        ↓
Create OrderItems
        ↓
Snapshot Price
```

Order không được tồn tại ở trạng thái transaction không hoàn chỉnh do chỉ insert được một phần items.

---

## 36.3 Record Stock Movement

```text
Validate Inventory Item and Quantity
       ↓
Create StockMovement
       ↓
Update Current Stock
```

Nếu Stock Movement/update stock fail:

```text
Rollback
```

---

## 36.4 Complete Payment

```text
Validate Bill
      ↓
Record Payment Success
      ↓
Bill = Paid
      ↓
DiningSession = Completed
      ↓
Table runtime_status = Cleaning
```

Các bước phải được bảo vệ khỏi duplicate execution.

Nếu transaction fail giữa chừng:

```text
Rollback
```

để tránh trạng thái:

```text
Payment = Success
Bill = Unpaid
```

hoặc:

```text
Bill = Paid
DiningSession = Active
```

do partial update.

---

# 37. Critical Data Integrity Rules

## DB-RULE-01 — One Active Session per Table

```text
RestaurantTable
      ↓
max 1 active DiningSession
```

Backend phải enforce.

---

## DB-RULE-02 — Order Requires Dining Session

Mọi Order trong restaurant operation phải reference Dining Session hợp lệ.

```text
Order → DiningSession
```

Không tạo orphan Order.

---

## DB-RULE-03 — Additional Order Uses Existing Session

```text
DiningSession
├── Order #1
├── Order #2
└── Order #N
```

Không overwrite Order trước.

---

## DB-RULE-04 — Historical Price Is Immutable

```text
OrderItem.unit_price
```

là transaction snapshot.

Product price thay đổi không làm thay đổi Order lịch sử.

---

## DB-RULE-05 — Billing Unit Is Dining Session

Bill tổng hợp valid billable Order Items thuộc Dining Session.

Không mặc định mỗi Order có một Bill.

---

## DB-RULE-06 — Bill Supports Multiple Payment Attempts

```text
Bill 1 → N Payments
```

để hỗ trợ failed/retry/success.

---

## DB-RULE-07 — Payment Failure Does Not Close Session

```text
Payment Failed
      ↓
Bill Unpaid
      ↓
DiningSession remains open
```

---

## DB-RULE-08 — Reservation Is Not Dining Session

Reservation có thể tồn tại mà không có Dining Session.

Walk-in Dining Session có thể tồn tại mà không có Reservation.

---

## DB-RULE-09 — Transaction History Must Survive Master Changes

Không hard-delete hoặc cascade-delete transaction history do Product/Customer/Employee/Table thay đổi.

---

## DB-RULE-10 — Report Is Derived Data

Không tạo report tables nếu chưa có requirement lưu report snapshot.

---

## DB-RULE-11 — AI Is Not Core Data Dependency

Recommendation failure không được ảnh hưởng Menu/Order transaction.

---

## DB-RULE-12 — Translation Is Not Core Data Dependency

Translation failure không được làm Core customer flow lỗi; fallback về VI.

## DB-RULE-13 — Canonical Runtime Table State

`restaurant_tables.runtime_status` dùng
`available/reserved/occupied/cleaning`; `is_active` là configuration
flag riêng. State và related Reservation/Dining Session update phải
atomic.

## DB-RULE-14 — Customer Ownership

`customers.user_id` là unique nullable relationship để Customer Account
chỉ truy cập own Profile/Orders/Reservations.

## DB-RULE-15 — Customer Order Source

Order lưu `source` và creator reference phù hợp. Customer self-order
phải validate session-bound Dining Session context trước transaction.

## DB-RULE-16 — Persistent Translation and Settings

Manual/dynamic translation và Admin-managed runtime settings phải tồn
tại trong MySQL; cache không phải source of truth.

## DB-RULE-17 — Purchasing Is Future

Core schema không có Supplier/Purchase/PurchaseItem. Nhập/xuất kho được
biểu diễn trực tiếp bằng Stock Movement có actor và history.

---

# 38. Data Validation Baseline

Important constraints:

```text
restaurant_tables.capacity > 0

reservations.party_size > 0

dining_sessions.guest_count > 0

order_items.quantity > 0
order_items.unit_price >= 0
order_items.line_total >= 0

bills.subtotal >= 0
bills.discount_amount >= 0
bills.total_amount >= 0

payments.amount > 0

inventory_items.current_stock >= 0
inventory_items.minimum_stock >= 0
```

Temporal constraint:

```text
dining_sessions.ended_at >= dining_sessions.started_at
```

Business validation phải được enforce ở backend ngay cả khi UI đã validate.

---

# 39. Audit Baseline

Không xây full Audit Log subsystem trong Core Scope.

Các transaction quan trọng vẫn cần biết actor thực hiện thông qua fields như:

```text
dining_sessions.opened_by_employee_id
dining_sessions.completed_by_employee_id

orders.created_by_employee_id
orders.created_by_customer_id

order_items.cancelled_by_employee_id

reservations.confirmed_by_employee_id

payments.processed_by_employee_id

stock_movements.created_by_employee_id

translations.updated_by_employee_id
system_settings.updated_by_employee_id
```

Nếu sau này cần compliance/audit nâng cao mới bổ sung `audit_logs`.

---

# 40. Tables Intentionally Excluded from Core

Để tránh over-design, baseline **không thêm**:

```text
product_images

reservation_tables

user_roles

bill_vouchers
voucher_usages

bill_items

recipes
ingredients
recipe_items

recommendations
recommendation_logs

translation_cache

reports
report_snapshots

audit_logs

suppliers
purchases
purchase_items
invoices
```

Các table này không bị coi là sai về mặt kỹ thuật; chúng chỉ chưa cần thiết với Core Requirement hiện tại.

Chỉ bổ sung khi requirement tương ứng thực sự xuất hiện.

---

# 41. Migration / Development Order

Khi implementation, schema nên được triển khai theo dependency thay vì tạo ngẫu nhiên.

## Phase 1 — Identity

```text
roles
permissions
role_permissions
users
employees
```

## Phase 2 — Menu

```text
categories
products
```

## Phase 3 — Restaurant Operation

```text
customers
restaurant_tables
reservations
dining_sessions
orders
order_items
```

## Phase 4 — Billing

```text
vouchers
bills
payments
```

## Phase 5 — Inventory

```text
inventory_items
stock_movements
```

## Phase 6 — Localization & Configuration

```text
translations
system_settings
```

Sau migrations mới triển khai:

```text
Eloquent Models
      ↓
Relationships
      ↓
Enums
      ↓
Factories / Seeders
      ↓
Business Services
```

---

# 42. Implementation Boundaries

Database Baseline quyết định:

- Entities.
- Tables.
- Relationships.
- Core fields.
- Data integrity.
- Historical transaction model.
- FK/unique/index strategy.
- Status/money/soft-delete conventions.

Database Baseline **không quyết định**:

- Controller structure.
- API routes.
- Service classes.
- Repository pattern.
- Frontend component tree.
- Exact Figma layout.
- Deployment infrastructure.
- Queue/cache technology.
- External API implementation.
- Exact SQL optimization.

Các quyết định đó thuộc System Architecture hoặc Development.

---

# 43. Final Database Decisions

## DB-FINAL-01 — Core Transaction Backbone

```text
RestaurantTable
      ↓
DiningSession
      ↓
Multiple Orders
      ↓
OrderItems
```

---

## DB-FINAL-02 — Dining Session Is the Operational Context

Dining Session kết nối:

```text
Table
Customer [optional]
Reservation [optional]
Orders
Bill
Employees
```

---

## DB-FINAL-03 — Reservation Is Independent

Customer Reservation không đồng nghĩa với Dining Session và không bắt buộc assign Table ngay khi tạo.

---

## DB-FINAL-04 — Historical Price Snapshot

Order Item giữ `product_name`, `unit_price`, `quantity`, `line_total` tại thời điểm transaction.

---

## DB-FINAL-05 — Billing Unit

```text
Billing Unit = Dining Session
```

---

## DB-FINAL-06 — Payment Attempts

```text
Bill 1 → N Payments
```

Payment success mới cho phép normal completion của Bill/Dining Session.

---

## DB-FINAL-07 — User and Employee Are Separate

```text
User = authentication identity
Employee = business profile
```

---

## DB-FINAL-08 — Customer Does Not Require Account

Customer business record không phụ thuộc `users`.

---

## DB-FINAL-09 — Inventory Uses Movement History

`current_stock` không phải nguồn audit duy nhất.

```text
InventoryItem
      ↓
StockMovements
```

---

## DB-FINAL-10 — Reports Are Derived

Core reporting sử dụng transaction tables thay vì report tables riêng.

---

## DB-FINAL-11 — Supporting Services Stay Decoupled

AI Recommendation và Translation không được trở thành dependency bắt buộc của Core Database/transaction flows.

---

## DB-FINAL-12 — Preserve History

Master data có thể deactivate/soft-delete; transaction history phải được giữ.

## DB-FINAL-13 — Canonical State Storage

Reservation, Table runtime, Dining Session, Order Item, Bill và Payment
phải dùng canonical state vocabulary từ System Requirements.

## DB-FINAL-14 — Customer Identity Link

`customers.user_id` là unique nullable link cho Customer Account
ownership; guest Customer không bắt buộc có User.

## DB-FINAL-15 — Persistent Localization and Settings

`translations` và `system_settings` thuộc Core schema. Cache chỉ là
optimization.

## DB-FINAL-16 — Purchasing Future Boundary

Core Inventory không chứa Supplier/Purchase/PurchaseItem.

---

# 44. Definition of Done

Database Design được coi là hoàn tất khi:

- [x] Core domains finalized.
- [x] Core entities/tables finalized.
- [x] Relationships và cardinality finalized.
- [x] Dining Session model finalized.
- [x] Reservation boundary finalized.
- [x] Order/OrderItem model finalized.
- [x] Historical price snapshot finalized.
- [x] Billing model finalized.
- [x] Bill/Payment separation finalized.
- [x] Inventory/Stock Movement baseline finalized; Purchasing moved to Future.
- [x] Role/Permission model finalized.
- [x] Customer Account ownership relationship finalized.
- [x] Persistent Translation/System Settings finalized.
- [x] Money strategy finalized.
- [x] Status strategy finalized.
- [x] Soft-delete/history strategy finalized.
- [x] FK/unique/index baseline finalized.
- [x] Transaction boundaries identified.
- [x] Core integrity rules documented.
- [x] Over-designed tables removed from Core.
- [x] Ready for System Architecture.

---

# 45. Final Baseline

```text
DATABASE DESIGN BASELINE v1.0

Identity
├── roles
├── permissions
├── role_permissions
├── users
└── employees

Menu
├── categories
└── products

Customer
├── customers
└── reservations

Restaurant Operation
├── restaurant_tables
└── dining_sessions

Ordering
├── orders
└── order_items

Billing
├── vouchers
├── bills
└── payments

Inventory
├── inventory_items
└── stock_movements

Localization & Configuration
├── translations
└── system_settings


CORE TRANSACTION MODEL

RestaurantTable
      ↓
DiningSession
      ↓
Multiple Orders
      ↓
OrderItems
      ↓
Billing
      ↓
Bill
      ↓
Payment Attempt(s)
      ↓
Successful Payment
      ↓
Complete DiningSession
```

**Status: BASELINE v1.0 — Ready for System Architecture**
