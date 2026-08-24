# 03. System Analysis — Users & Use Cases

**Project:** 89 Beer Garden Website & Management System  
**Document:** Users & Use Cases Baseline  
**Version:** 1.0  
**Status:** Finalized / Ready for System Flow Analysis

---

## 1. Purpose

Tài liệu này tổng hợp và chốt phần **Users & Use Cases** của giai đoạn `03. System Analysis`.

Mục tiêu:

- Chốt System Actors.
- Xác định Use Case theo từng Actor.
- Xây Use Case Catalog.
- Phân tích các Critical Use Case.
- Xác định quan hệ giữa các Use Case.
- Trace Use Case về Business Rules và System Requirements.
- Tạo baseline để chuyển sang **System Flows**.

Tài liệu này không quyết định UI layout, database schema, API endpoint hay cấu trúc source code.

---

# 2. System Actors

## 2.1 Actor Classification

### Primary Human Actors

1. **Customer**
2. **Staff**
3. **Kitchen / Bar Staff**
4. **Manager / Admin**

### Supporting External Actors

1. **Translation Service**
2. **Weather Service**

### Future External Actor

1. **Payment Service / Payment Gateway**

---

## 2.2 ACT-SYS-01 — Customer

**Type:** Primary Human Actor  
**Scope:** Customer Website  
**Core status:** Core

Customer có thể:

- Xem menu và thông tin sản phẩm.
- Browse theo category, search và filter.
- Chuyển ngôn ngữ `VI / EN / ZH`.
- Đặt bàn.
- Xem recommendation.
- Gọi món nếu Customer Ordering được bật.
- Gọi thêm món trong active Dining Session.
- Nhập voucher.
- Đăng ký/đăng nhập nếu sử dụng Customer Account.
- Quản lý profile.
- Xem lịch sử order của chính mình.

Customer không bắt buộc phải đăng nhập chỉ để xem Menu hoặc được phục vụ dưới dạng Walk-in Customer.

Khi Customer có account, authentication User phải liên kết tối đa một
Customer profile để enforce own Profile/Orders/Reservations.

Customer không được truy cập các chức năng nội bộ như POS, Employee Management, Inventory Management, Reports hoặc System Configuration.

---

## 2.3 ACT-SYS-02 — Staff

**Type:** Primary Human Actor  
**Scope:** Restaurant Operations / POS  
**Core status:** Core

Staff chịu trách nhiệm vận hành chính:

- Xem và quản lý trạng thái bàn.
- Xử lý Reservation.
- Check-in và bố trí bàn.
- Mở Dining Session.
- Tạo Order và Additional Order.
- Theo dõi trạng thái món.
- Mark item Served.
- Billing.
- Voucher tại POS.
- Payment confirmation.
- Invoice và đóng Dining Session.

Không tách `Waiter`, `Cashier`, `Receptionist` thành System Actor riêng ở Core. Khác biệt nghiệp vụ được xử lý bằng `Role + Permission`.

---

## 2.4 ACT-SYS-03 — Kitchen / Bar Staff

**Type:** Primary Human Actor  
**Scope:** Kitchen / Bar Processing  
**Core status:** Core

Capability chính:

- View Kitchen Queue.
- Xem Product, Quantity, Note và Table/Order reference.
- Chuyển Order Item `Waiting → Preparing`.
- Chuyển Order Item `Preparing → Ready`.

Kitchen và Bar chưa được tách thành hai actor riêng. Nếu cần routing theo station trong tương lai, ưu tiên Category Routing, Queue Filtering và Permission.

---

## 2.5 ACT-SYS-04 — Manager / Admin

**Type:** Primary Human Actor  
**Scope:** Administration / Management  
**Core status:** Core

Quản lý:

- Categories và Products.
- Tables.
- Reservations và Orders.
- Vouchers.
- Inventory cơ bản.
- Customers.
- Employees.
- Roles & Permissions.
- Reports.
- Translations.
- System Configuration trong phạm vi requirement.

Manager và Admin chưa cần tách thành hai actor Use Case riêng; khác biệt
quyền được xử lý bằng Role/Permission. Manager quản lý nghiệp vụ nhà
hàng; chỉ Admin được gán Role/Permission, chỉnh persistent Translation
và System Configuration.

Quyền Admin không được bypass Data Integrity.

---

## 2.6 EXT-SYS-01 — Translation Service

**Type:** Supporting External Actor

Dùng cho dynamic content khi chưa có stored translation.

```text
Requested Locale
    ↓
Stored Translation?
    ├── Yes → Display
    └── No → Translation Service → Persist → Optional Cache
```

Nếu provider lỗi, hệ thống fallback về tiếng Việt. Translation failure không được làm Core Menu/Reservation lỗi.

---

## 2.7 EXT-SYS-02 — Weather Service

**Type:** Supporting External Actor

Cung cấp weather context cho Recommendation.

Weather Service không được tạo Order, sửa Product hay tự quyết định món cho Customer. Khi service lỗi, Core system vẫn phải hoạt động.

---

## 2.8 EXT-SYS-03 — Payment Service

**Type:** Future External Actor

Core hiện hỗ trợ:

- Cash.
- QR / Bank Transfer.
- Staff confirmation.

Full Payment Gateway thuộc Future Scope.

---

## 2.9 Actor Baseline

| ID | Actor | Type | Scope |
|---|---|---|---|
| ACT-SYS-01 | Customer | Primary Human | Core |
| ACT-SYS-02 | Staff | Primary Human | Core |
| ACT-SYS-03 | Kitchen / Bar Staff | Primary Human | Core |
| ACT-SYS-04 | Manager / Admin | Primary Human | Core |
| EXT-SYS-01 | Translation Service | Supporting External | Core Supporting |
| EXT-SYS-02 | Weather Service | Supporting External | Supporting |
| EXT-SYS-03 | Payment Service | External | Future |

---

# 3. Use Case Identification

## 3.1 Customer Use Cases

| ID | Use Case | Priority | Analysis Level |
|---|---|---|---|
| UC-CUS-01 | Browse Menu | Must | Standard |
| UC-CUS-02 | View Product Detail | Must | Supporting |
| UC-CUS-03 | Switch Language | Must | Standard |
| UC-CUS-04 | Make Reservation | Must | Critical |
| UC-CUS-05 | Place Order | Must* | Critical |
| UC-CUS-06 | Add Additional Order | Must* | Critical |
| UC-CUS-07 | Apply Voucher | Should | Standard |
| UC-CUS-08 | View Recommendation | Should | Standard |
| UC-CUS-09 | Register Account | Should | Supporting |
| UC-CUS-10 | Login | Should | Standard |
| UC-CUS-11 | Logout | Must* | Supporting |
| UC-CUS-12 | Manage Profile | Should | Supporting |
| UC-CUS-13 | Recover Password | Should | Supporting |
| UC-CUS-14 | View Order History | Should | Standard |
| UC-CUS-15 | Manage Cart | Must | Standard |
| UC-CUS-16 | View Current Order Status | Must* | Standard |
| UC-CUS-17 | View Own Reservations | Should | Standard |

`*` phụ thuộc capability/account context tương ứng.

---

## 3.2 Staff Use Cases

| ID | Use Case | Priority | Analysis Level |
|---|---|---|---|
| UC-STF-01 | Login | Must | Standard |
| UC-STF-02 | View Table Status | Must | Standard |
| UC-STF-03 | Assign Table | Must | Critical |
| UC-STF-04 | Update Table Status | Must | Standard |
| UC-STF-05 | View Reservations | Must | Supporting |
| UC-STF-06 | Process Reservation | Must | Critical |
| UC-STF-07 | Check-in Reservation | Must | Critical |
| UC-STF-08 | Mark Reservation No-show | Should | Supporting |
| UC-STF-09 | Open Dining Session | Must | Critical |
| UC-STF-10 | View Dining Session | Must | Standard |
| UC-STF-11 | Create Order | Must | Critical |
| UC-STF-12 | Modify Pending Order | Must | Standard |
| UC-STF-13 | Add Additional Order | Must | Critical |
| UC-STF-14 | View Order Status | Must | Standard |
| UC-STF-15 | Mark Item Served | Must | Standard |
| UC-STF-16 | Cancel Order Item | Should | Standard |
| UC-STF-17 | Open Billing | Must | Critical |
| UC-STF-18 | Apply Voucher to Bill | Should | Standard |
| UC-STF-19 | Complete Payment | Must | Critical |
| UC-STF-20 | Generate Invoice | Must | Standard |
| UC-STF-21 | Complete Dining Session | Must | Critical |

---

## 3.3 Kitchen / Bar Use Cases

| ID | Use Case | Priority | Analysis Level |
|---|---|---|---|
| UC-KIT-01 | Login | Must | Supporting |
| UC-KIT-02 | View Kitchen Queue | Must | Critical |
| UC-KIT-03 | Start Preparing Item | Must | Critical |
| UC-KIT-04 | Mark Item Ready | Must | Critical |

---

## 3.4 Manager / Admin Use Cases

| ID | Use Case | Priority | Analysis Level |
|---|---|---|---|
| UC-ADM-01 | Login | Must | Standard |
| UC-ADM-02 | Manage Categories | Must | Supporting |
| UC-ADM-03 | Manage Products | Must | Standard |
| UC-ADM-04 | Manage Tables | Must | Standard |
| UC-ADM-05 | Manage Reservations | Must | Standard |
| UC-ADM-06 | Manage Orders | Must | Standard |
| UC-ADM-07 | Manage Vouchers | Should | Supporting |
| UC-ADM-08 | Manage Inventory | Should | Standard |
| UC-ADM-09 | View Customers | Must | Supporting |
| UC-ADM-10 | Manage Employees | Must | Standard |
| UC-ADM-11 | Manage Roles & Permissions | Must | Critical |
| UC-ADM-12 | View Reports | Must | Standard |
| UC-ADM-13 | Manage Translations | Should | Supporting |
| UC-ADM-14 | Manage System Configuration | Must | Standard |

---

# 4. Use Case Catalog Strategy

Use Case được chia thành ba mức:

### Critical

Viết Full Use Case Specification vì có nhiều state transition, business rule hoặc data-integrity constraint.

### Standard

Viết specification ngắn, tập trung vào goal, precondition, main behavior và rule chính.

### Supporting

Catalog + requirement/business-rule reference thường đã đủ; không cần tài liệu dài.

Tổng baseline:

| Actor | Candidate Use Cases | Critical |
|---|---:|---:|
| Customer | 17 | 3 |
| Staff | 21 | 9 |
| Kitchen / Bar | 4 | 3 |
| Manager / Admin | 14 | 1 |
| **Total** | **56** | **16** |

Không phải 53 Use Case đều cần Full Specification.

---

# 5. Critical Use Case Specifications

## 5.1 UC-CUS-04 — Make Reservation

**Primary Actor:** Customer  
**Goal:** Gửi yêu cầu đặt bàn trước.

### Preconditions

- Website hoạt động.
- Customer truy cập được Reservation.
- Không bắt buộc đăng nhập.

### Main Flow

1. Customer mở Reservation.
2. System yêu cầu thông tin đặt bàn.
3. Customer nhập Name, Phone, Date, Time, Party Size và optional Note.
4. Customer submit.
5. System validate dữ liệu.
6. System tạo Reservation.
7. Reservation được đặt ở trạng thái `Pending`.
8. System xác nhận request đã được ghi nhận.

### Alternate / Exception

- Dữ liệu thiếu/không hợp lệ → không tạo Reservation và yêu cầu sửa.
- Customer không bắt buộc chọn Table cụ thể.

### Postcondition

```text
Reservation created
Status = Pending
```

---

## 5.2 UC-CUS-05 — Place Order

**Primary Actor:** Customer  
**Condition:** Customer Ordering được bật.

### Preconditions

- Có active Dining Session hợp lệ.
- Customer Ordering được bật.
- Customer đang có session-bound context hợp lệ cho Dining Session đó.

### Main Flow

1. Customer xem Menu hoặc Recommendation.
2. Customer chủ động thêm Product vào Cart.
3. Customer đổi Quantity/Note hoặc xóa Item khi cần.
4. Customer submit Cart.
5. System validate Customer session context và active Dining Session.
6. System validate Product, availability và quantity.
7. System lấy Unit Price hiện tại.
8. System tạo Order với source `customer` và Customer reference khi có.
9. System tạo Order Items và snapshot Unit Price.
10. Item đi vào processing flow; Cart được clear sau commit thành công.

### Exceptions

- Product unavailable → reject item.
- Không có active Dining Session → reject order.
- Customer context không thuộc Dining Session → reject order.
- Submit fail → giữ Cart để Customer sửa/retry.

### Integrity Rule

```text
OrderItem.unit_price = price at order time
```

Product Price thay đổi sau đó không làm thay đổi lịch sử Order.

---

## 5.3 UC-CUS-06 — Add Additional Order

**Primary Actor:** Customer

### Preconditions

`Dining Session = Active`

### Main Flow

1. Customer tiếp tục xem Menu.
2. Chọn món gọi thêm.
3. System validate Product.
4. Customer xác nhận.
5. System tạo Order/lần gọi thêm mới.
6. Order được gắn vào Dining Session hiện tại.
7. Order Items đi vào Kitchen/Bar processing.

### Core Rule

```text
Dining Session
├── Order #1
├── Order #2
└── Order #3
```

Không overwrite Order trước và không mở Dining Session mới chỉ vì gọi thêm.

---

## 5.3A UC-CUS-15 — Manage Cart

Customer thêm Product Available từ Menu/Product Detail/Recommendation,
đổi Quantity/Note và xóa Item. Cart là session state, không phải Order
và không giữ historical price. Backend validate lại toàn bộ dữ liệu khi
submit.

## 5.3B UC-CUS-16 — View Current Order Status

Customer chỉ xem Order Items thuộc Dining Session context hợp lệ của
mình. UI hiển thị canonical item states:

```text
waiting / preparing / ready / served / cancelled
```

## 5.3C UC-CUS-17 — View Own Reservations

Customer Account chỉ xem Reservations liên kết với Customer profile của
chính account. Guest Reservation có thể xem kết quả ngay sau submit,
nhưng không tạo quyền truy cập reservation khác.

---

## 5.4 UC-STF-03 — Assign Table

**Primary Actor:** Staff

### Preconditions

- Staff authenticated và có permission.
- Customer là Walk-in hoặc Reservation đã tới.

### Main Flow

1. Staff xác định Party Size.
2. System hiển thị Table Status.
3. Staff chọn Table phù hợp.
4. System kiểm tra trạng thái Table.
5. System kiểm tra active Dining Session.
6. Staff xác nhận.
7. Table được bố trí cho Customer.

### Exception

Table đã có active Dining Session → reject assignment.

---

## 5.5 UC-STF-06 — Process Reservation

**Primary Actor:** Staff

### Preconditions

`Reservation = Pending`

### Main Flow — Confirm

1. Staff xem Pending Reservations.
2. Chọn Reservation.
3. Xem Date, Time, Party Size và Contact.
4. Kiểm tra khả năng phục vụ.
5. Chọn Confirm.
6. System validate.
7. `Pending → Confirmed`.

### Alternate — Reject

`Pending → Rejected`

---

## 5.6 UC-STF-07 — Check-in Reservation

**Primary Actor:** Staff

### Preconditions

- `Reservation = Confirmed`.
- Customer đã tới.

### Main Flow

1. Staff tìm Reservation.
2. Xác nhận Customer arrival.
3. `Confirmed → Checked-in`.
4. Staff bố trí Table.
5. System validate Table.
6. Table được assign.
7. Có thể tiếp tục mở Dining Session.

---

## 5.7 UC-STF-09 — Open Dining Session

**Primary Actor:** Staff

### Preconditions

- Staff authenticated.
- Table hợp lệ.
- Table chưa có active Dining Session khác.

### Main Flow

1. Staff chọn Table.
2. Yêu cầu mở phiên phục vụ.
3. System kiểm tra Table.
4. System kiểm tra active session.
5. System tạo Dining Session.
6. Gắn Session với Table.
7. Ghi Start Time.
8. `Table → Occupied`.
9. `Dining Session → Active`.

### Exception

Existing active Dining Session → reject new Session.

---

## 5.8 UC-STF-11 — Create Order

**Primary Actor:** Staff

### Preconditions

- Staff authenticated.
- Active Dining Session tồn tại.

### Main Flow

1. Staff mở Dining Session.
2. Chọn Add Order.
3. Chọn Product.
4. System validate Product.
5. Nhập Quantity và optional Note.
6. Có thể thêm nhiều Product.
7. System validate Order.
8. Order phải có ít nhất một valid Item.
9. Staff xác nhận.
10. System tạo Order.
11. Snapshot Unit Price từng Item.
12. Order Items chuyển sang Kitchen/Bar.

### Exceptions

- Product unavailable → reject affected item.
- Order không có item → reject.
- Dining Session không Active → reject.

---

## 5.9 UC-STF-13 — Add Additional Order

**Primary Actor:** Staff

Additional Order sử dụng cùng active Dining Session hiện tại.

```text
Dining Session #DS01
├── Order #001
├── Order #002
└── Order #003
```

Không tạo Dining Session mới.

---

## 5.10 UC-KIT-02 — View Kitchen Queue

**Primary Actor:** Kitchen / Bar Staff

System hiển thị processable Order Items gồm:

- Product.
- Quantity.
- Note.
- Table / Order reference.
- Current status.

Kitchen Queue hoạt động ở `Order Item level`.

---

## 5.11 UC-KIT-03 — Start Preparing Item

### Preconditions

`Order Item = Waiting`

### Main Flow

1. Kitchen chọn Item.
2. System kiểm tra status.
3. Kitchen bắt đầu chế biến.
4. `Waiting → Preparing`.

---

## 5.12 UC-KIT-04 — Mark Item Ready

### Preconditions

`Order Item = Preparing`

### Main Flow

1. Kitchen chọn Item.
2. System validate status.
3. Kitchen xác nhận hoàn tất chế biến.
4. `Preparing → Ready`.

Sau đó Staff có thể chuyển `Ready → Served`.

---

## 5.13 UC-STF-17 — Open Billing

**Primary Actor:** Staff

### Preconditions

`Dining Session = Active`

### Main Flow

1. Customer yêu cầu thanh toán.
2. Staff mở Billing của Dining Session.
3. System load toàn bộ valid billable Order Items.
4. System sử dụng historical Unit Price.
5. Tính:

```text
Subtotal = Σ(Unit Price × Quantity)
```

6. Hiển thị Subtotal.
7. Voucher/Discount có thể được áp dụng.
8. Tính Total.

**Billing Unit = Dining Session**, không phải một Order đơn lẻ.

---

## 5.14 UC-STF-19 — Complete Payment

**Primary Actor:** Staff

### Preconditions

- Dining Session hợp lệ.
- Billing đã được tính.
- Payment chưa Completed.

### Main Flow

1. Staff mở Billing.
2. System tính Subtotal.
3. Validate Voucher/Discount nếu có.
4. System tính Total.
5. Chọn payment method: Cash hoặc QR/Bank Transfer.
6. Staff xác nhận đã nhận Payment.
7. System kiểm tra duplicate completion.
8. Ghi nhận `Payment = Paid`.
9. Generate/Store Invoice.
10. Khóa normal modification của paid transaction.
11. Complete Dining Session.

### Exception

Payment đã Paid → reject second completion.

QR/Bank Transfer không tự động được coi là Paid nếu Staff chưa xác nhận.

---

## 5.15 UC-STF-21 — Complete Dining Session

### Preconditions

`Payment = Paid`

### Main Flow

1. Complete Dining Session.
2. Lưu End Time.
3. `Dining Session → Completed`.
4. `Table → Cleaning`.
5. Sau khi dọn xong: `Cleaning → Available`.

---

## 5.16 UC-ADM-11 — Manage Roles & Permissions

**Primary Actor:** Manager / Admin

### Preconditions

- Admin authenticated.
- Có permission quản lý access.

### Main Flow

1. Admin chọn Employee/User.
2. Xem Role/Permission hiện tại.
3. Thay đổi quyền.
4. System validate.
5. System lưu Role/Permission.
6. Authorization mới được áp dụng.

Permission không được bypass Data Integrity. Backend phải enforce authorization; frontend button visibility không phải authorization boundary.

---

# 6. Important State Models

## 6.1 Reservation

```text
Pending
├── Confirmed
│   ├── Checked-in → Completed
│   └── No-show
├── Rejected
└── Cancelled
```

Không tự bổ sung state chưa được requirement hỗ trợ.

## 6.2 Table

```text
Available
↓
Reserved / Occupied
↓
Occupied
↓
Cleaning
↓
Available
```

`is_active = false` là configuration state riêng và không thay thế
runtime state `cleaning`.

## 6.3 Kitchen Order Item

```text
Waiting
↓
Preparing
↓
Ready
↓
Served
```

Alternate terminal state theo permission:

```text
Waiting / Preparing → Cancelled
```

## 6.4 Dining Session

Canonical state chỉ gồm `Active` và `Completed`. `Billing` là workflow/UI
context, không phải Dining Session state; không dùng `Closed`.

---

# 7. Overall Use Case Relationships

## 7.1 Relationship Types

- **Association:** Actor trực tiếp tham gia Use Case.
- **`<<include>>`:** behavior bắt buộc của Use Case khác.
- **`<<extend>>`:** behavior tùy chọn/tùy điều kiện.
- **Precondition / Dependency:** trạng thái hoặc Use Case trước tạo điều kiện cho Use Case sau nhưng không phải `include`.

---

## 7.2 Customer Relationships

### Menu

```text
Customer → Browse Menu
Customer → View Product Detail
Customer → View Recommendation

View Recommendation
    <<extend>>
Browse Menu
```

Recommendation là optional và Core Menu không phụ thuộc AI/Weather Service.

### Language

```text
Customer → Switch Language ← Translation Service
```

Không `include` Switch Language vào mọi customer-facing Use Case.

---

## 7.3 Reservation Dependencies

```text
Make Reservation
↓
Reservation Pending
↓
View Reservations
↓
Process Reservation
↓
Confirmed
↓
Check-in Reservation
↓
Assign Table
```

Đây là business dependency chain, không phải chuỗi `<<include>>`.

No-show là alternate lifecycle outcome của confirmed Reservation, không phải extension của Check-in.

---

## 7.4 Table & Dining Session Dependencies

```text
View Table Status
↓
Assign Table
↓
Open Dining Session
```

`Assign Table` tạo context cho `Open Dining Session`, nhưng không phải `<<include>>` vì Table có thể đã được assign từ trước.

---

## 7.5 Ordering Dependencies

```text
Open Dining Session
↓
Create Order
↓
Additional Order(s)
```

Active Dining Session là precondition của Order.

Các behavior như Validate Product, Snapshot Unit Price, Validate Quantity là internal behavior, không tạo fake Use Case.

Additional Order là Use Case độc lập trong cùng Dining Session, không ép thành `<<extend>> Create Order`.

---

## 7.6 Kitchen Dependencies

```text
Create Order
↓
View Kitchen Queue
↓
Start Preparing Item
↓
Mark Item Ready
↓
Mark Item Served
```

Đây là state-driven dependency chain, không phải include chain.

---

## 7.7 Voucher Relationship

```text
Apply Voucher to Bill
    <<extend>>
Open Billing
```

Voucher là optional. Billing/Payment vẫn hoạt động nếu không có Voucher.

---

## 7.8 Payment Relationships

```text
Open Billing
↓
Complete Payment
    ├── <<include>> Generate Invoice
    └── <<include>> Complete Dining Session
```

Sau successful payment:

```text
Payment = Paid
↓
Invoice
↓
Dining Session = Completed
↓
Table = Cleaning
```

---

## 7.9 Authentication & Authorization

Không dùng:

```text
Create Order <<include>> Login
```

Login là independent Use Case và authentication là precondition cho internal actions.

Permission là cross-cutting constraint:

```text
Authenticated User
↓
Role / Permission
↓
Business Rule
↓
Allowed Action
```

---

## 7.10 External Actor Relationships

| External Actor | Related Use Case | Scope |
|---|---|---|
| Translation Service | Switch Language | Supporting |
| Weather Service | View Recommendation | Supporting |
| Payment Service | Online Payment | Future |

Payment Service không được nối vào Core Complete Payment như một dependency bắt buộc.

---

# 8. Overall Core Operational Model

```text
                    CUSTOMER
                       │
        ┌──────────────┴──────────────┐
        │                             │
 Make Reservation                  Walk-in
        │                             │
        ↓                             │
Reservation Pending                   │
        ↓                             │
Process Reservation                   │
        ↓                             │
Confirmed                             │
        ↓                             │
Check-in                              │
        └──────────────┬──────────────┘
                       ↓
                  Assign Table
                       ↓
              Open Dining Session
                       ↓
                   Create Order
                       ↓
           ┌───────────┴───────────┐
           │                       │
    Additional Order         Kitchen Queue
                                   ↓
                              Preparing
                                   ↓
                                 Ready
                                   ↓
                                 Served
                                   ↓
                             Open Billing
                                   │
                    Optional Apply Voucher
                                   ↓
                            Complete Payment
                              /           \
                   Generate Invoice   Complete Session
                                         ↓
                                   Table Cleaning
                                         ↓
                                      Available
```

---

# 9. Requirement Traceability

## 9.1 Customer

| Use Case | Main Requirement Trace |
|---|---|
| UC-CUS-01 Browse Menu | FR-MENU-01..05, FR-AUTH-05, relevant Product Rules |
| UC-CUS-02 View Product Detail | FR-MENU-01, Product Rules |
| UC-CUS-03 Switch Language | FR-LANG-01..08, INT-TRANS-* |
| UC-CUS-04 Make Reservation | FR-RES-01..04, Reservation Rules |
| UC-CUS-05 Place Order | FR-ORDER-02..09, Order Rules |
| UC-CUS-06 Add Additional Order | FR-SESSION-03, FR-ORDER-10..11, Dining Session decision |
| UC-CUS-07 Apply Voucher | FR-VOUCHER-03..06, Voucher Rules |
| UC-CUS-08 View Recommendation | FR-AI-01..11, INT-WEATHER-* |
| UC-CUS-09 Register Account | FR-AUTH-01 |
| UC-CUS-10 Login | FR-AUTH-02 |
| UC-CUS-11 Logout | FR-AUTH-03 |
| UC-CUS-12 Manage Profile | FR-AUTH-06 |
| UC-CUS-13 Recover Password | FR-AUTH-07 |
| UC-CUS-14 View Order History | FR-CUSTOMER-03, customer ownership permission |
| UC-CUS-15 Manage Cart | FR-CART-01..08, BR-CART-* |
| UC-CUS-16 View Current Order Status | FR-ORDER-16, Customer session ownership |
| UC-CUS-17 View Own Reservations | FR-CUSTOMER-07, customer ownership permission |

---

## 9.2 Staff

| Use Case | Main Requirement Trace |
|---|---|
| UC-STF-02 View Table Status | FR-TABLE-01..02 |
| UC-STF-03 Assign Table | FR-TABLE-03..06, Table Rules |
| UC-STF-04 Update Table Status | FR-TABLE-02, FR-TABLE-08 |
| UC-STF-05 View Reservations | FR-RES-05 |
| UC-STF-06 Process Reservation | FR-RES-06..08 |
| UC-STF-07 Check-in Reservation | FR-RES-09..10 |
| UC-STF-08 Mark No-show | FR-RES-11..12 |
| UC-STF-09 Open Dining Session | FR-SESSION-01..05 |
| UC-STF-10 View Dining Session | FR-ORDER-12, FR-SESSION-03 |
| UC-STF-11 Create Order | FR-ORDER-01,03..09,13 |
| UC-STF-12 Modify Pending Order | FR-ORDER-06 |
| UC-STF-13 Add Additional Order | FR-ORDER-10..11, FR-SESSION-03 |
| UC-STF-14 View Order Status | FR-ORDER-12, FR-KITCHEN-06 |
| UC-STF-15 Mark Item Served | FR-KITCHEN-05..06 |
| UC-STF-16 Cancel Order Item | FR-KITCHEN-07..09 |
| UC-STF-17 Open Billing | FR-PAY-01..03 |
| UC-STF-18 Apply Voucher to Bill | FR-PAY-04, FR-VOUCHER-03..06 |
| UC-STF-19 Complete Payment | FR-PAY-03..09 |
| UC-STF-20 Generate Invoice | FR-PAY-10 |
| UC-STF-21 Complete Dining Session | FR-PAY-11, FR-SESSION-06 |

---

## 9.3 Kitchen / Bar

| Use Case | Main Requirement Trace |
|---|---|
| UC-KIT-02 View Kitchen Queue | FR-KITCHEN-01..02 |
| UC-KIT-03 Start Preparing Item | FR-KITCHEN-03,06 |
| UC-KIT-04 Mark Item Ready | FR-KITCHEN-04,06 |

---

## 9.4 Manager / Admin

| Use Case | Main Requirement Trace |
|---|---|
| UC-ADM-02 Manage Categories | FR-MENU-06 |
| UC-ADM-03 Manage Products | FR-MENU-07..10, Product Rules |
| UC-ADM-04 Manage Tables | FR-TABLE-07 |
| UC-ADM-05 Manage Reservations | Reservation management requirements |
| UC-ADM-06 Manage Orders | Order management + permission requirements |
| UC-ADM-07 Manage Vouchers | FR-VOUCHER-01..02 |
| UC-ADM-08 Manage Inventory | FR-INV-01..08 |
| UC-ADM-09 View Customers | FR-CUSTOMER-01..05 as applicable |
| UC-ADM-10 Manage Employees | FR-EMP-01,02,04,05 |
| UC-ADM-11 Manage Roles & Permissions | FR-EMP-03, PERM-*, AUTHZ-* |
| UC-ADM-12 View Reports | FR-REPORT-01..06 |
| UC-ADM-13 Manage Translations | FR-LANG-07, INT-TRANS-07 |
| UC-ADM-14 Manage System Configuration | Configuration requirements such as reservation timeout and admin config scope |

---

# 10. Requirements That Are Constraints, Not Actor Use Cases

Không tạo Use Case riêng cho các requirement kiểu:

- Store historical Unit Price.
- Prevent duplicate Payment.
- Fallback to VI.
- Prevent second active Dining Session.
- Do not recommend unavailable Product.
- Validate Product.
- Validate Voucher.
- Check Permission.
- Cache Translation.

Chúng là:

```text
Business Validation
System Constraint
Data Integrity Rule
Failure Behavior
Cross-cutting Concern
```

và được gắn vào Use Case liên quan.

---

# 11. NFR Applicability

NFR không map 1:1 thành Use Case.

Ví dụ:

- Security / Authorization → toàn bộ internal Use Cases.
- POS Performance → Staff operational Use Cases.
- Payment Reliability → Complete Payment.
- Data Integrity → Assign Table, Open Dining Session, Create/Add Order.
- Localization → Customer-facing content và Switch Language.
- Integration resilience → Translation/Weather-dependent capabilities.

---

# 12. Final Review Decisions

## UC-FINAL-01 — Actor Baseline

Giữ 4 Primary Human Actors, 2 Supporting External Actors và Payment Service ở Future Scope.

## UC-FINAL-02 — Staff Roles

Không tách Waiter/Cashier/Receptionist ở Core; dùng Role + Permission.

## UC-FINAL-03 — Dining Session

Dining Session là operational context trung tâm:

```text
Table
↓
Dining Session
↓
Multiple Orders
```

## UC-FINAL-04 — Reservation

Reservation và Table Assignment là hai concern liên quan nhưng không đồng nhất. Customer không bắt buộc chọn Table khi đặt bàn.

## UC-FINAL-05 — Historical Price

Order Item lưu Unit Price tại thời điểm Order; Product Price thay đổi không làm thay đổi lịch sử.

## UC-FINAL-06 — Additional Order

Additional Order là Use Case riêng trong existing Dining Session.

## UC-FINAL-07 — Kitchen Granularity

Kitchen processing được quản lý ở Order Item level.

## UC-FINAL-08 — Billing Unit

Billing Unit = Dining Session.

## UC-FINAL-09 — Authentication

Login là independent Use Case; authentication/authorization được xử lý như precondition và cross-cutting constraint.

## UC-FINAL-10 — Use Case Relationships

Không lạm dụng `<<include>>` / `<<extend>>` để mô tả business sequence.

Quan hệ đặc biệt chính:

```text
View Recommendation
    <<extend>> Browse Menu

Apply Voucher to Bill
    <<extend>> Open Billing

Complete Payment
    <<include>> Generate Invoice

Complete Payment
    <<include>> Complete Dining Session
```

## UC-FINAL-11 — AI Boundary

Recommendation là optional capability. AI/Weather failure không được làm Core Menu, Reservation, Order hoặc Payment lỗi. AI không tự thêm món vào Order.

## UC-FINAL-12 — Translation Boundary

Dynamic/manual translation dùng persistent store; runtime cache chỉ là
optimization. Provider chỉ được gọi khi cần và phải fallback VI khi lỗi.

## UC-FINAL-13 — Admin Boundary

Admin scope không tự mở rộng thành CRM, HRM, ERP hoặc Advanced BI.

## UC-FINAL-14 — Technical Boundary

Use Case Analysis không quyết định:

- Database tables.
- PK/FK/indexes.
- API endpoints.
- Controller/Service structure.
- Frontend components.
- Exact UI screens.

---

# 13. Requirement Coverage Review

Các nhóm requirement Core đã được bao phủ bởi Use Case hoặc System Constraint tương ứng:

```text
FR-AUTH-*
FR-MENU-*
FR-CART-*
FR-TABLE-*
FR-RES-*
FR-SESSION-*
FR-ORDER-*
FR-KITCHEN-*
FR-PAY-*
FR-VOUCHER-*
FR-INV-*
FR-CUSTOMER-*
FR-EMP-*
FR-REPORT-*
FR-LANG-*
FR-AI-*
FR-CONFIG-*
```

Không có nhóm Functional Requirement Core lớn nào bị orphan trong baseline hiện tại.

Future requirement không được kéo vào Core Use Case Catalog.

---

# 14. Handoff to System Flows

Sau Users & Use Cases, bước tiếp theo không phân tích lại actor hay danh sách chức năng mà chuyển sang **end-to-end System Flow**.

Các flow candidate chính:

```text
SF-01 Walk-in → Dining Session
SF-02 Reservation → Check-in → Dining Session
SF-03 Order → Kitchen → Served
SF-04 Additional Order
SF-05 Billing → Payment → Close Session
SF-06 Translation
SF-07 Recommendation
```

Ở System Flows sẽ phân tích thứ tự interaction, state transition, alternate/error flow và các component liên quan. Sequence Diagram chỉ nên vẽ cho các flow quan trọng sau khi System Flow đã được chốt.

---

# 15. Definition of Done

`Users & Use Cases` được coi là hoàn tất khi:

- [x] System Actors finalized.
- [x] Use Cases identified.
- [x] Use Case Catalog completed.
- [x] Critical Use Cases analyzed.
- [x] Overall Use Case Relationships reviewed.
- [x] Requirement Traceability completed.
- [x] Core/Future boundary preserved.
- [x] No major orphan Functional Requirement.
- [x] Business Rules preserved.
- [x] Technical design boundary preserved.
- [x] Ready for System Flow Analysis.

---

# 16. Final Baseline

```text
USERS & USE CASES BASELINE v1.0

Actors
├── Customer
├── Staff
├── Kitchen / Bar Staff
├── Manager / Admin
├── Translation Service [Supporting]
├── Weather Service [Supporting]
└── Payment Service [Future]

Use Cases
├── Customer: 17
├── Staff: 21
├── Kitchen / Bar: 4
└── Manager / Admin: 14

Total Candidate Use Cases: 56
Critical Use Cases: 16

Core Model
Table
  ↓
Dining Session
  ↓
Multiple Orders
  ↓
Order Items
  ↓
Kitchen / Bar
  ↓
Serving
  ↓
Billing
  ↓
Payment
  ↓
Invoice
  ↓
Complete Session
```

**Status: BASELINE v1.0 — Ready for System Flow Analysis**
