# 03. System Analysis — System Flows

**Project:** 89 Beer Garden Website & Management System  
**Document:** System Flows Baseline  
**Version:** 1.0  
**Status:** Finalized  

---

## 1. Purpose

Tài liệu này tổng hợp phần **System Flows** thuộc `03. System Analysis`.

Mục tiêu:

- Xác định các luồng end-to-end quan trọng cần mô hình hóa.
- Chuyển các Use Case đã chốt thành luồng xử lý hệ thống.
- Xác định state transition, exception và output quan trọng.
- Chỉ giữ các diagram thực sự cần thiết.
- Tránh phân tích lại Business Analysis, System Requirements và Users & Use Cases.
- Tạo baseline để handoff sang UI/UX Design và Database & Architecture.

Nguyên tắc:

> Thông tin đã được chốt ở giai đoạn trước được tham chiếu và kế thừa, không phân tích lại nếu không có mâu thuẫn hoặc thay đổi requirement.

---

# Task 1 — Identify Core System Flows

## 1.1 Nguyên tắc chọn Core Flow

Một flow được ưu tiên mô hình hóa khi có một hoặc nhiều đặc điểm:

- Nhiều actor/module cùng tham gia.
- Có state transition quan trọng.
- Có business rule hoặc data integrity cần bảo vệ.
- Ảnh hưởng trực tiếp tới nghiệp vụ chính.
- Kết nối nhiều Use Case.
- Nếu hiểu sai có khả năng dẫn đến implementation sai.

Không tạo Core System Flow riêng cho mọi Use Case.

Các thao tác đơn giản như Login, Logout, Manage Profile, CRUD Category hoặc View Customers không cần flow end-to-end riêng nếu Use Case và Requirement đã đủ rõ.

---

## 1.2 Core System Flow Set

### SF-01 — Walk-in → Table → Dining Session

**Mục tiêu:** Khách đến trực tiếp, được bố trí bàn và bắt đầu phiên phục vụ.

```text
Customer Walk-in
        ↓
Staff checks Table
        ↓
Assign Table
        ↓
Validate Table availability
        ↓
Open Dining Session
        ↓
Table = Occupied
        ↓
Dining Session = Active
```

**Related Use Cases**

- UC-STF-02 — View Table Status
- UC-STF-03 — Assign Table
- UC-STF-09 — Open Dining Session

**Key Rule**

```text
1 Table
→ Maximum 1 Active Dining Session
```

---

### SF-02 — Reservation → Confirm → Check-in → Dining Session

**Mục tiêu:** Xử lý vòng đời đặt bàn từ lúc Customer gửi yêu cầu đến khi bắt đầu Dining Session.

```text
Customer
↓
Make Reservation
↓
Pending
↓
Staff Review
├── Reject
└── Confirm
       ↓
   Customer arrives
       ↓
    Check-in
       ↓
   Assign Table
       ↓
Open Dining Session
```

**Related Use Cases**

- UC-CUS-04 — Make Reservation
- UC-STF-05 — View Reservations
- UC-STF-06 — Process Reservation
- UC-STF-07 — Check-in Reservation
- UC-STF-03 — Assign Table
- UC-STF-09 — Open Dining Session

**Key Decision**

Customer không bắt buộc chọn chính xác một Table khi tạo Reservation.

---

### SF-03 — Ordering → Kitchen / Bar → Served

**Mục tiêu:** Xử lý Order từ lúc tạo món đến khi món được phục vụ.

```text
Active Dining Session
        ↓
Staff / Customer creates Order
        ↓
Validate Product
        ↓
Snapshot Unit Price
        ↓
Create Order Items
        ↓
Kitchen / Bar Queue
        ↓
Waiting
        ↓
Preparing
        ↓
Ready
        ↓
Served
```

**Related Use Cases**

- UC-CUS-05 — Place Order
- UC-STF-11 — Create Order
- UC-STF-14 — View Order Status
- UC-STF-15 — Mark Item Served
- UC-KIT-02 — View Kitchen Queue
- UC-KIT-03 — Start Preparing Item
- UC-KIT-04 — Mark Item Ready

**Key Rules**

```text
OrderItem.unit_price
= Product price at order time
```

```text
Kitchen processing status
= Order Item level
```

---

### SF-04 — Additional Order in Active Dining Session

**Mục tiêu:** Cho phép gọi thêm món mà vẫn giữ cùng Dining Session.

```text
Dining Session = Active
        ↓
Customer requests more items
        ↓
Create Additional Order
        ↓
Link to SAME Dining Session
        ↓
Create Order Items
        ↓
Kitchen Processing
```

**Related Use Cases**

- UC-CUS-06 — Add Additional Order
- UC-STF-13 — Add Additional Order

**Key Model**

```text
Dining Session DS-001
├── Order #001
├── Order #002
└── Order #003
```

Additional Order không tạo Dining Session mới.

---

### SF-04A — Customer Cart → Self-order

**Mục tiêu:** Customer chủ động tạo Order tại bàn mà không làm mất
Dining Session/ownership boundary.

```text
Valid Customer Session Context
        ↓
Menu / Recommendation
        ↓
Customer Add-to-Cart
        ↓
Review Cart
        ↓
Submit
        ↓
Validate Context + Active Dining Session
        ↓
Validate Products + Current Prices
        ↓
Create Order + Snapshot Order Items
        ↓
Clear Cart after Commit
```

**Related Use Cases**

- UC-CUS-15 — Manage Cart
- UC-CUS-05 — Place Order
- UC-CUS-16 — View Current Order Status

**Key Rule:** Cart là session state; Order mới là transaction state.
Recommendation chỉ thêm món sau action của Customer.

---

### SF-05 — Billing → Voucher → Payment → Close Session

**Mục tiêu:** Tổng hợp toàn bộ chi phí của Dining Session, thanh toán và đóng phiên phục vụ.

```text
Customer requests payment
        ↓
Staff opens Billing
        ↓
Load all billable Order Items
        ↓
Calculate Subtotal
        ↓
Optional Voucher
        ↓
Calculate Total
        ↓
Select Payment Method
        ↓
Staff confirms Payment
        ↓
Payment = Paid
        ↓
Generate Invoice
        ↓
Dining Session = Completed
        ↓
Table = Cleaning
        ↓
Table = Available
```

**Related Use Cases**

- UC-STF-17 — Open Billing
- UC-STF-18 — Apply Voucher to Bill
- UC-STF-19 — Complete Payment
- UC-STF-20 — Generate Invoice
- UC-STF-21 — Complete Dining Session
- UC-STF-04 — Update Table Status

**Key Rules**

```text
Billing Unit = Dining Session
```

```text
No duplicate successful Payment
```

---

### SF-06 — Language Switch → Translation → Fallback

**Mục tiêu:** Hỗ trợ VI / EN / ZH mà Translation Service không trở thành dependency bắt buộc của Core.

```text
Customer selects EN / ZH
        ↓
Check stored translation
        ↓
Translation exists?
├── Yes → Display
└── No
     ↓
Translation Service
     ↓
Success?
├── Yes → Persist → Optional Cache → Display
└── No  → Fallback VI
```

**Related Use Cases**

- UC-CUS-03 — Switch Language
- UC-ADM-13 — Manage Translations
- Translation Service

---

### SF-07 — Recommendation → Context → Suggested Products

**Mục tiêu:** Tạo gợi ý món dựa trên context nhưng không ảnh hưởng Core Menu khi recommendation thất bại.

```text
Customer views Menu
        ↓
Recommendation requested
        ↓
Load Product Data
        ↓
Load Context
├── Time
├── Popularity
├── Current Cart / Order
├── Optional Order History
└── Optional Weather
        ↓
Filter unavailable Products
        ↓
Recommendation Logic
        ↓
Suggested Products
```

**Related Use Cases**

- UC-CUS-08 — View Recommendation
- Weather Service

**Failure Rule**

```text
Weather failure
→ Continue without Weather
```

```text
Recommendation failure
→ Menu continues normally
```

---

## 1.3 Flow Classification

| ID | Flow | Classification |
|---|---|---|
| SF-01 | Walk-in → Table → Dining Session | Core |
| SF-02 | Reservation → Check-in → Dining Session | Core |
| SF-03 | Ordering → Kitchen/Bar → Served | Critical Core |
| SF-04 | Additional Order | Critical Core |
| SF-04A | Customer Cart → Self-order | Core when enabled |
| SF-05 | Billing → Payment → Close | Critical Core |
| SF-06 | Language → Translation → Fallback | Supporting Core |
| SF-07 | Recommendation → Suggested Products | Project Feature |

### Restaurant Core

```text
SF-01
SF-02
SF-03
SF-04
SF-04A
SF-05
```

### Platform Capability

```text
SF-06
```

### Project Feature

```text
SF-07
```

---

## 1.4 Flows intentionally not separated

Không tạo Core System Flow riêng cho:

- Login / Logout
- Register / Recover Password
- Manage Profile
- Manage Category
- Manage Product
- Manage Table Configuration
- Manage Employees
- Manage Roles & Permissions
- Manage Voucher
- View Reports
- View Customers

Inventory hiện chưa cần Core Flow riêng. Nếu cần trong tương lai có thể bổ sung supporting flow cho Inventory Adjustment.

Authentication/Authorization được xử lý như cross-cutting constraint:

```text
Authentication
↓
Authorization
↓
Business Rule
↓
Data Integrity
↓
Execute
```

---

# Task 2 — Analyze Core System Flows

## 2.1 SF-01 — Walk-in → Table → Dining Session

### Preconditions

- Staff đã authenticated.
- Staff có permission phù hợp.
- Customer đến trực tiếp.
- Table data khả dụng.

### Input

- Party size.
- Current Table status.

### System Processing

```text
Customer arrives
        ↓
Staff checks Table Status
        ↓
System loads Available Tables
        ↓
Staff selects Table
        ↓
System validates Table
        ↓
System checks no Active Dining Session exists
        ↓
Assign Table
        ↓
Open Dining Session
```

### State Changes

```text
Table:
Available → Occupied
```

```text
Dining Session:
Create record with status Active
```

### Exceptions

- Không có Table phù hợp → không mở Dining Session.
- Table vừa được actor khác sử dụng → reject assignment.
- Table đã có Active Dining Session → reject second session.

### Output

```text
Customer seated
+
Dining Session = Active
```

---

## 2.2 SF-02 — Reservation → Confirm → Check-in → Dining Session

### Preconditions

- Reservation feature khả dụng.
- Staff có permission quản lý Reservation.

### Input

- Customer Name
- Phone
- Date
- Time
- Party Size
- Optional Note

### System Processing

```text
Customer submits Reservation
        ↓
Validate Input
        ↓
Create Reservation
        ↓
Pending
        ↓
Staff reviews
        ↓
Confirm OR Reject
```

Nếu Confirm:

```text
Confirmed
        ↓
Customer arrives
        ↓
Check-in
        ↓
Checked-in
        ↓
Assign Table
        ↓
Open Dining Session
```

### State Changes

```text
Reservation:
Pending → Confirmed → Checked-in
```

Possible alternate states:

```text
Rejected
Cancelled
No-show
```

Table:

```text
Available → Occupied
```

Dining Session:

```text
Create record with status Active
```

### Exceptions

- Invalid reservation input → không tạo Reservation.
- Không đủ khả năng phục vụ → Rejected.
- Customer không đến sau configured timeout → No-show.
- Table không còn available khi check-in → chọn Table khác.

### Output

```text
Reservation = Checked-in
Table assigned
Dining Session = Active
```

---

## 2.3 SF-03 — Ordering → Kitchen / Bar → Served

### Preconditions

```text
Dining Session = Active
```

Actor tạo Order phải có context/permission hợp lệ.

### Input

- Product
- Quantity
- Optional Note
- Dining Session
- Actor

### System Processing

```text
Select Product
        ↓
Validate Product
        ↓
Check Active + Available
        ↓
Validate Quantity
        ↓
Read current Product Price
        ↓
Create Order
        ↓
Create Order Items
        ↓
Snapshot Unit Price
        ↓
Send processable Items to Kitchen / Bar Queue
```

Kitchen processing:

```text
Waiting
↓
Preparing
↓
Ready
↓
Served
```

### State Changes

Order Item:

```text
Waiting → Preparing → Ready → Served
```

### Data Rule

```text
OrderItem.unit_price
= Product price at Order time
```

### Exceptions

- Product unavailable → reject affected Item.
- Dining Session not Active → reject Order.
- Zero valid items → không tạo/confirm valid Order.
- Cancellation phụ thuộc Order Item status và permission.
- Served Item không hard-delete theo normal flow.

### Output

```text
Order saved
+
Order Items processed
+
Items eventually Served
```

---

## 2.4 SF-04 — Additional Order in Active Dining Session

### Preconditions

```text
Dining Session = Active
```

### Input

- New Product(s)
- Quantity
- Optional Note
- Current Dining Session

### System Processing

```text
Customer requests additional items
        ↓
Validate Dining Session
        ↓
Validate Products
        ↓
Snapshot Unit Price
        ↓
Create NEW Order
        ↓
Link to EXISTING Dining Session
        ↓
Create Order Items
        ↓
Kitchen / Bar Processing
```

### State Changes

Dining Session:

```text
Active → Active
```

New Order Items:

```text
Waiting → Preparing → Ready → Served
```

### Exceptions

- Dining Session Completed → reject.
- Product unavailable → reject affected Item.
- Không còn valid Item → không tạo valid Additional Order.

### Output

```text
Additional Order created
+
Existing Dining Session preserved
```

---

## 2.4A SF-04A — Customer Cart → Self-order

### Preconditions

- Customer Ordering được bật.
- Customer có signed/session-bound context hợp lệ cho Dining Session.
- `Dining Session = Active`.

### System Processing

```text
Add Product from Menu/Recommendation
        ↓
Store/update session Cart
        ↓
Customer submits Cart
        ↓
Validate Customer context and Active Dining Session
        ↓
Validate Product + availability + quantity + current price
        ↓
DB transaction
├── Create Order(source = customer)
└── Create Order Items + snapshot price
        ↓
Commit
        ↓
Clear Cart
```

### Exceptions

- Invalid/expired session context → reject; không tạo Order.
- Dining Session không Active → reject; giữ Cart để thông báo/sửa.
- Product unavailable → yêu cầu Customer review lại affected Item.
- Transaction failure → rollback Order/Items và giữ Cart.

### Output

```text
Order linked to existing Dining Session
+
Customer can view own current Item status
```

---

## 2.5 SF-05 — Billing → Voucher → Payment → Close Session

### Preconditions

- Dining Session hợp lệ.
- Có billable Order Items.
- Staff có payment permission.

### Input

- Dining Session
- Billable Order Items
- Optional Voucher
- Payment Method

### System Processing

```text
Customer requests payment
        ↓
Open Billing
        ↓
Load all valid Order Items
        ↓
Calculate Subtotal
        ↓
Voucher?
├── No
└── Yes
     ↓
Validate Voucher
     ↓
Apply Discount
        ↓
Calculate Total
        ↓
Choose Payment Method
├── Cash
└── QR / Bank Transfer
        ↓
Staff confirms money received
        ↓
Check not already Paid
        ↓
Payment = Paid
        ↓
Generate Invoice
        ↓
Complete Dining Session
        ↓
Table = Cleaning
```

After cleaning:

```text
Cleaning → Available
```

### Calculation

```text
Subtotal = Σ(Unit Price × Quantity)

Total = Subtotal - Discount
```

### State Changes

```text
Payment:
Not Paid → Paid
```

```text
Dining Session:
Active → Completed
```

```text
Table:
Occupied → Cleaning → Available
```

### Exceptions

- Invalid Voucher → reject Voucher, Billing remains valid.
- Duplicate Payment → reject duplicate completion.
- QR/Transfer chưa được Staff xác nhận → không mark Paid.
- Successful Payment không được chỉnh sửa theo normal operational flow.

### Output

```text
Payment = Paid
Invoice stored
Dining Session = Completed
Table = Cleaning
```

---

## 2.6 SF-06 — Language Switch → Translation → Fallback

### Preconditions

- Supported locales: VI / EN / ZH.
- VI là default locale.

### Input

- Requested Locale
- Static UI content
- Dynamic content

### System Processing

Static UI:

```text
Requested Locale
↓
Load Localization Resource
↓
Display
```

Dynamic content:

```text
Requested Locale
        ↓
Check Stored Translation
        ↓
Exists?
├── Yes → Display
└── No
     ↓
Translation Service available?
├── Yes
│    ↓
│ Translate
│    ↓
│ Persist / Update Translation
│ Optional Cache
│    ↓
│ Display
└── No
     ↓
Fallback VI
```

### Data Changes

Translation thành công phải được lưu persistent theo source entity/field
và target locale; runtime cache chỉ là optimization có thể tái tạo.

### Exceptions

- Translation API timeout/error → VI fallback.
- Missing EN/ZH translation → VI fallback.
- Provider unavailable → Core feature vẫn tiếp tục.

### Output

```text
Requested Locale
OR
VI Fallback
```

---

## 2.7 SF-07 — Recommendation → Context → Suggested Products

### Preconditions

- Menu/Product data khả dụng.
- Recommendation feature enabled.
- Recommendation không phải Core dependency.

### Input

Có thể bao gồm:

- Current Time
- Product Data
- Popularity
- Current Cart / Order Context
- Optional Customer Order History
- Optional Weather Data

### System Processing

```text
Recommendation requested
        ↓
Load valid Product Data
        ↓
Load internal context
        ↓
Try Weather Context
        ↓
Filter Active + Available Products
        ↓
Apply Recommendation Logic
        ↓
Rank / Select
        ↓
Return Suggested Products
```

### State Changes

Không có mandatory business state transition.

### Exceptions

- Weather Service failure → continue without Weather.
- Recommendation logic failure → Menu continues normally.
- Unavailable Product → không được recommend.

### Output

```text
Suggested Products
OR
No Recommendation
```

Recommendation không tự động thêm Product vào Order.

---

# Task 3 — Sequence / Activity Diagrams

Không vẽ diagram cho mọi Use Case hoặc mọi System Flow.

Diagram baseline gồm **1 Overall Activity Diagram + 3 Key Sequence Diagrams**.

---

## 3.1 DGM-SF-01 — Overall Restaurant Operation

**Type:** Activity Diagram  
**Coverage:** SF-01 → SF-05

Diagram cần thể hiện:

```text
START
  ↓
Walk-in OR Reservation
  ↓
Reservation processing (if applicable)
  ↓
Assign Table
  ↓
Open Dining Session
  ↓
Create Order
  ↓
Kitchen / Bar Processing
  ↓
Served
  ↓
More items?
├── Yes → Additional Order → Kitchen
└── No
     ↓
Open Billing
     ↓
Optional Voucher
     ↓
Complete Payment
     ↓
Generate Invoice
     ↓
Complete Dining Session
     ↓
Table Cleaning
     ↓
Table Available
     ↓
END
```

Mục tiêu của diagram là cung cấp **overall operational map**, không mô tả implementation chi tiết.

---

## 3.2 DGM-SF-02 — Order → Kitchen → Served

**Type:** Sequence Diagram  
**Coverage:** SF-03 + SF-04

Suggested participants:

```text
Staff / Customer
System
Order Logic
Kitchen / Bar
Staff
```

Sequence chính:

```text
Create Order
↓
Validate Dining Session
↓
Validate Product
↓
Snapshot Unit Price
↓
Create Order + Order Items
↓
Send Items to Kitchen / Bar
↓
Waiting → Preparing
↓
Preparing → Ready
↓
Notify / expose Ready status
↓
Staff serves Item
↓
Ready → Served
```

Additional Order được thể hiện bằng `alt`:

```text
alt Additional Order
    Validate existing Active Dining Session
    Create NEW Order
    Link to SAME Dining Session
    Continue normal Kitchen processing
end
```

---

## 3.3 DGM-SF-03 — Billing → Payment → Close Session

**Type:** Sequence Diagram  
**Coverage:** SF-05

Suggested participants:

```text
Customer
Staff
System
Billing Logic
Payment
Invoice
Table
```

Sequence:

```text
Customer requests Bill
↓
Staff opens Billing
↓
System loads Dining Session items
↓
Calculate Subtotal
↓
Optional Voucher validation
↓
Calculate Total
↓
Customer pays
↓
Staff confirms Payment
↓
Prevent duplicate successful Payment
↓
Payment = Paid
↓
Generate Invoice
↓
Complete Dining Session
↓
Table = Cleaning
```

Recommended alternate blocks:

```text
alt Invalid Voucher
    Reject Voucher
    Continue Billing without Discount
end
```

```text
alt Payment already Paid
    Reject duplicate completion
end
```

---

## 3.4 DGM-SF-04 — Language → Translation → Fallback

**Type:** Sequence Diagram  
**Coverage:** SF-06

Suggested participants:

```text
Customer
Website
Persistent Translation Store / Runtime Cache
Translation Service
```

Sequence:

```text
Customer selects EN/ZH
↓
Website checks stored translation
↓
alt Translation exists
    Display translated content
else Translation missing
    Request Translation Service
    alt Translation success
        Persist / Update → Optional Cache
        Display translated content
    else Translation failure
        Display VI fallback
    end
end
```

---

## 3.5 Diagram Scope Decision

Không yêu cầu diagram riêng cho:

- SF-07 Recommendation
- Login
- Admin CRUD
- Reports
- Basic Inventory

Nếu implementation sau này phát hiện một flow phức tạp hơn dự kiến thì có thể bổ sung diagram khi cần.

### Reusable Diagram Guideline

Cho các project tương lai:

```text
System Flow Diagrams

1 Overall Activity Diagram
        +
2–5 Key Sequence Diagrams
```

Sequence Diagram chỉ nên ưu tiên khi flow có:

- Nhiều component.
- Transaction quan trọng.
- External integration.
- State transition phức tạp.
- Data integrity quan trọng.

Không áp dụng quy tắc `1 Use Case = 1 Sequence Diagram`.

---

# Task 4 — Review & Finalize System Flow Baseline

## 4.1 Scope Review

System Flow baseline:

```text
Restaurant Core
├── SF-01 Walk-in → Dining Session
├── SF-02 Reservation → Dining Session
├── SF-03 Ordering → Kitchen → Served
├── SF-04 Additional Order
├── SF-04A Customer Cart → Self-order
└── SF-05 Billing → Payment → Close

Supporting Core
└── SF-06 Multilingual / Translation

Project Feature
└── SF-07 Recommendation
```

Không cần mở rộng thêm flow nếu requirement hiện tại không thay đổi.

---

## 4.2 Entry Flow Decision

```text
Walk-in ───────┐
               ↓
          Assign Table
               ↓
      Open Dining Session

Reservation
    ↓
Confirm
    ↓
Check-in ──────┘
```

**SF-FINAL-01**

Walk-in và Reservation là hai entry khác nhau nhưng hội tụ vào cùng Dining Session flow sau khi Customer được bố trí Table.

---

## 4.3 Dining Session Decision

```text
Table
  ↓
Dining Session
  ↓
Order #1
Order #2
Order #3
...
```

**SF-FINAL-02**

Dining Session là operational context trung tâm cho Table, Order, Billing và Payment.

Một Table không được có hai Active Dining Sessions đồng thời.

---

## 4.4 Reservation Decision

Normal lifecycle:

```text
Pending
→ Confirmed
→ Checked-in
```

Possible alternate states:

```text
Rejected
Cancelled
No-show
```

**SF-FINAL-03**

Reservation không đồng nghĩa với việc Customer phải chọn hoặc khóa cứng một Table cụ thể ngay khi request được tạo.

---

## 4.5 Ordering Decision

```text
Active Dining Session
↓
Create Order
↓
Validate Product
↓
Snapshot Unit Price
↓
Create Order Items
↓
Kitchen / Bar
```

**SF-FINAL-04**

Historical Order calculation phải sử dụng `Order Item Unit Price`, không sử dụng Product Price hiện tại.

---

## 4.6 Additional Order Decision

```text
Existing Active Dining Session
↓
Create NEW Order
↓
Link SAME Dining Session
```

**SF-FINAL-05**

Additional Order không mở Dining Session mới.

---

## 4.7 Kitchen / Bar Decision

```text
Waiting
↓
Preparing
↓
Ready
↓
Served
```

**SF-FINAL-06**

Kitchen processing granularity = **Order Item**.

Một Order có thể chứa nhiều Items ở các trạng thái xử lý khác nhau.

---

## 4.8 Billing Decision

```text
Dining Session
↓
All valid Order Items
↓
Subtotal
↓
Optional Voucher
↓
Total
```

**SF-FINAL-07**

Billing Unit = **Dining Session**.

---

## 4.9 Payment Decision

Core payment methods:

```text
Cash
OR
QR / Bank Transfer
+
Staff Confirmation
```

Successful path:

```text
Payment = Paid
↓
Generate Invoice
↓
Dining Session = Completed
↓
Table = Cleaning
```

**SF-FINAL-08**

Core Payment không phụ thuộc external Payment Gateway. Full online gateway remains Future Scope.

---

## 4.10 Table Closing Decision

```text
Occupied
↓
Cleaning
↓
Available
```

**SF-FINAL-09**

Cleaning được giữ như một trạng thái vận hành riêng trước khi Table trở lại Available.

---

## 4.11 Multilingual Decision

```text
Static UI
→ Localization Resources
```

```text
Dynamic Content
→ Stored Translation
→ Translation Service if missing
→ VI fallback on failure
```

**SF-FINAL-10**

Translation Provider là supporting dependency, không phải Core dependency.

---

## 4.12 Recommendation Decision

Recommendation có thể sử dụng:

- Time
- Weather
- Popularity
- Current Cart / Order
- Optional Order History

Nhưng:

```text
Weather failure
→ Continue
```

```text
Recommendation failure
→ Menu continues
```

**SF-FINAL-11**

Recommendation là optional supporting capability và không tự động thêm Product vào Order.

---

## 4.13 Authentication & Authorization Decision

```text
Authentication
↓
Role / Permission
↓
Business Rule
↓
Data Integrity
↓
Execute
```

**SF-FINAL-12**

Authentication và Authorization là cross-cutting system constraints, không cần System Flow riêng.

---

## 4.14 Diagram Baseline Decision

```text
DGM-SF-01 — Overall Restaurant Operation
Activity Diagram

DGM-SF-02 — Order → Kitchen → Served
Sequence Diagram

DGM-SF-03 — Billing → Payment → Close Session
Sequence Diagram

DGM-SF-04 — Language → Translation → Fallback
Sequence Diagram
```

**SF-FINAL-13**

`1 Overall Activity Diagram + 3 Key Sequence Diagrams` là đủ cho baseline hiện tại.

---

# State Model Summary

## Reservation

```text
Pending
├── Confirmed
│   ├── Checked-in → Completed
│   └── No-show
├── Rejected
└── Cancelled
```

## Table

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

## Order Item

```text
Waiting
↓
Preparing
↓
Ready
↓
Served
```

Alternate terminal transition:

```text
Waiting / Preparing → Cancelled
```

## Dining Session

Baseline hiện tại chỉ cần:

```text
Active
↓
Completed
```

Không dùng `Billing`, `Closed`, `Pending`, `Closing` hoặc `Paid` làm
Dining Session state. Billing là workflow context; successful payment
chuyển trực tiếp `Active → Completed` trong transaction.

---

# Cross-cutting Rules

## Internal operations

```text
Authentication
↓
Authorization
↓
Business Rule
↓
Data Integrity
↓
Execute
```

## External service resilience

```text
External Service Failure
≠
Core Business Failure
```

Đặc biệt áp dụng cho:

- Translation Service
- Weather Service
- Recommendation capability

---

# System Flow Traceability Summary

| System Flow | Main Use Case Area |
|---|---|
| SF-01 | Table + Dining Session |
| SF-02 | Reservation + Table + Dining Session |
| SF-03 | Order + Kitchen / Bar + Serving |
| SF-04 | Additional Order + Dining Session |
| SF-04A | Session Cart + Customer Self-order + Dining Session ownership |
| SF-05 | Billing + Voucher + Payment + Invoice |
| SF-06 | Language + Translation |
| SF-07 | Recommendation |

System Flows kế thừa Requirements và Use Cases đã baseline; không tạo capability mới ngoài scope.

---

# Out of Scope for System Flow Design

System Flow Analysis **không quyết định**:

- Database table names.
- Primary / Foreign Keys.
- Indexes.
- API endpoints.
- Controller structure.
- Service classes.
- Queue implementation.
- Frontend pages/components.
- Exact UI navigation.
- Framework folder structure.

Ví dụ:

```text
Open Dining Session
```

là system/business behavior, nhưng chưa bắt buộc implementation phải có database table tên chính xác là `dining_sessions`.

Các quyết định kỹ thuật này được handoff sang Database & Architecture.

---

# Final Decisions

| ID | Decision |
|---|---|
| SF-FINAL-01 | Walk-in và Reservation hội tụ vào Dining Session flow |
| SF-FINAL-02 | Dining Session là operational context trung tâm |
| SF-FINAL-03 | Reservation không khóa cứng Table cụ thể khi request được tạo |
| SF-FINAL-04 | Historical Order sử dụng Order Item Unit Price |
| SF-FINAL-05 | Additional Order dùng cùng Active Dining Session |
| SF-FINAL-06 | Kitchen processing ở Order Item level |
| SF-FINAL-07 | Billing Unit = Dining Session |
| SF-FINAL-08 | Core Payment không phụ thuộc external Payment Gateway |
| SF-FINAL-09 | Table có Cleaning state trước Available |
| SF-FINAL-10 | Translation Provider là supporting dependency |
| SF-FINAL-11 | Recommendation là optional capability |
| SF-FINAL-12 | Authentication/Authorization là cross-cutting constraints |
| SF-FINAL-13 | Baseline dùng 1 Activity + 3 Sequence Diagrams |
| SF-FINAL-14 | Customer Cart là session state; self-order chỉ tạo trong valid Dining Session context |
| SF-FINAL-15 | Canonical states dùng `completed` cho Dining Session và có `cancelled` cho Order Item |

---

# Definition of Done

System Flows được coi là hoàn tất khi:

- [x] Core end-to-end flows identified.
- [x] Detailed flows analyzed.
- [x] Important state changes identified.
- [x] Important exceptions identified.
- [x] Use Case consistency checked.
- [x] Requirement consistency checked.
- [x] Dining Session model preserved.
- [x] Historical price rule preserved.
- [x] Kitchen Item-level status preserved.
- [x] Billing by Dining Session preserved.
- [x] External-service fallback preserved.
- [x] Core/Future boundary preserved.
- [x] Diagram baseline identified.
- [x] Ready for UI/UX and Database/Architecture.

---

# System Analysis Baseline

```text
03. System Analysis
│
├── Users & Use Cases
│   └── BASELINE v1.0
│
└── System Flows
    └── BASELINE v1.0
```

`03. System Analysis` hiện đủ điều kiện handoff sang các giai đoạn thiết kế tiếp theo.

## Next Handoff

System Flows cung cấp đầu vào cho:

```text
System Analysis
      ↓
UI/UX Design
      ↓
Database & Architecture
```

UI/UX tập trung vào:

> Người dùng đi qua màn hình và thao tác như thế nào?

Database & Architecture tập trung vào:

> Dữ liệu, entity và technical structure nào cần thiết để hiện thực hóa các flow đã baseline?
