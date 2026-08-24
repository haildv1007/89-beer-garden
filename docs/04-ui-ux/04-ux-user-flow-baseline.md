# 04. UI/UX Design — UX & User Flow Baseline

**Project:** 89 Beer Garden Website & Management System  
**Document:** UX & User Flow Baseline  
**Version:** 1.0  
**Status:** Finalized / Ready for UI Design

---

## 1. Purpose

Tài liệu này chốt **UX & User Flow Baseline** của hệ thống 89 Beer Garden.

Mục tiêu là cung cấp cho UI Designer, Developer hoặc AI coding agent một nguồn tham chiếu thống nhất về:

- nhóm người dùng và mục tiêu của từng nhóm;
- các User Journey chính;
- Core User Flow;
- Screen Inventory;
- Information Architecture;
- Navigation Rules;
- các UX constraint quan trọng;
- ranh giới giữa UX và UI Design.

Tài liệu này **không phải danh sách các task UX**. Đây là kết quả hợp nhất cuối cùng của phần UX để dùng trực tiếp cho UI Design và Development.

---

# 2. UX Scope

UX được tổ chức quanh bốn context chính:

```text
Customer
Staff / POS
Kitchen / Bar
Admin / Manager
```

Supporting concerns:

```text
Authentication
Authorization
Language Switching
AI Recommendation
Validation / Error Recovery
```

UX không quyết định:

```text
Database Schema
API Endpoint
Controller / Service Structure
Exact Visual Style
Pixel Dimensions
```

---

# 3. UX Actor Model

## 3.1 Customer

Customer sử dụng Customer Website để:

```text
Browse Menu
View Product Detail
Receive Recommendation
Add to Cart
Place Order trong valid Dining Session [if enabled]
Make Reservation
View Request / Order Result
Switch VI / EN / ZH
```

Customer không bắt buộc phải có account chỉ để browse menu hoặc gửi Reservation.

Core Customer Ordering là self-order tại bàn; Takeaway/Delivery checkout
thuộc Future Scope.

---

## 3.2 Staff / POS

Staff sử dụng operational interface để:

```text
View Table Status
Process Reservation
Open Dining Session
Create Order
Add Additional Order
Track Order
Serve Items
Open Billing
Complete Payment
Close Dining Session
```

UX Staff phải ưu tiên:

```text
Speed
Clear State
Low Click Count
Context Preservation
```

---

## 3.3 Kitchen / Bar

Kitchen/Bar sử dụng operational queue tối giản để:

```text
View Order Items
Waiting → Preparing
Preparing → Ready
```

Kitchen UI ưu tiên item-level status, note, quantity, table/order
reference và elapsed time. Kitchen không dùng Admin IA và không xác
nhận payment/table state.

---

## 3.4 Admin / Manager

Admin/Manager sử dụng Back-office để:

```text
Manage Products
Manage Categories
Manage Tables
Manage Reservations
Manage Orders
Manage Customers
Manage Employees
Manage Vouchers
Manage Inventory
View Reports
```

Admin và Manager không cần hai Information Architecture khác nhau nếu khác biệt chính nằm ở permission.

---

# 4. User Journey Baseline

| ID | User Journey | Actor | Goal |
|---|---|---|---|
| UJ-01 | Explore Menu & Choose Items | Customer | Tìm và chọn món phù hợp |
| UJ-02 | Customer Ordering | Customer | Chọn món → giỏ hàng → xác nhận đơn |
| UJ-03 | Table Reservation | Customer | Gửi yêu cầu đặt bàn và theo dõi trạng thái |
| UJ-04 | Table Service & Dining Session | Staff | Quản lý một lượt phục vụ tại bàn |
| UJ-05 | Order Processing | Staff | Tạo, bổ sung và xử lý Order trong phiên |
| UJ-06 | Billing & Payment | Staff | Tổng hợp tiêu dùng và hoàn tất thanh toán |
| UJ-07 | Back-office Management | Admin / Manager | Quản lý dữ liệu và theo dõi hoạt động |

Không áp dụng:

```text
1 Use Case = 1 User Journey
```

Các thao tác nhỏ hoặc CRUD liên quan được gom vào Journey phù hợp.

---

# 5. Customer Experience Model

## 5.1 Explore Menu

```text
Visit Website
      ↓
Explore Menu
      ↓
Browse / Search / Category
      ↓
View Product
      ↓
Choose Product
```

Recommendation là optional branch:

```text
Explore Menu
      ↓
Recommended Products
      ↓
View Product
```

Nếu Recommendation không khả dụng:

```text
Recommendation unavailable
          ↓
Normal Menu remains usable
```

AI không được trở thành dependency của Core Customer Flow.

---

## 5.2 Customer Ordering

```text
Menu
 ↓
Select Product
 ↓
Add to Cart
 ↓
Review Cart
 ↓
Validate Dining Session Context
 ↓
Confirm Order
 ↓
Order Result
```

Decision:

```text
Cart Empty?
├── Yes → Return to Menu
└── No  → Continue
```

```text
Order Valid?
├── Yes → Create Order
└── No  → Show Validation / Correct Data
```

Unavailable Product:

```text
Select Product
      ↓
Available?
├── No  → Inform Customer
└── Yes → Add to Cart
```

Recommendation chỉ thêm một đường dẫn hỗ trợ:

```text
Recommendation → Product → Cart
```

Cart là browser/session state và chỉ được clear sau khi Order commit
thành công. Nếu context/session/product validation fail, UI giữ Cart để
Customer review hoặc retry.

---

# 6. Reservation Experience

Core flow:

```text
Home
 ↓
Reservation
 ↓
Date / Time
 ↓
Party Size
 ↓
Contact Information
 ↓
Submit
 ↓
Validation
 ↓
Pending
 ↓
Reservation Result
```

Reservation lifecycle:

```text
Pending
├── Confirmed → Checked-in → Completed
├── No-show
├── Rejected
└── Cancelled
```

UX Rule:

> `Reservation Submitted` không đồng nghĩa `Reservation Confirmed`.

Nếu hệ thống mới nhận request, UI phải thể hiện:

```text
Đã gửi yêu cầu đặt bàn
Đang chờ xác nhận
```

không phải:

```text
Đặt bàn đã được xác nhận
```

---

# 7. Restaurant Operation Experience

## 7.1 Core Operational Model

```text
Restaurant Table
      ↓
Dining Session
      ↓
Multiple Orders
      ↓
Order Processing
      ↓
Billing
      ↓
Payment
      ↓
Close Dining Session
```

Critical UX rule:

```text
Table ≠ Order
```

Đúng:

```text
Table
  ↓
Dining Session
  ├── Order #1
  ├── Order #2
  └── Order #N
```

Additional Order không tạo Dining Session mới và không overwrite Order trước.

---

## 7.2 Table & Dining Session Flow

```text
Staff Login
      ↓
Table Map
      ↓
Select Table
      ↓
Table Status?
├── Available → Open Dining Session
├── Reserved  → Check Reservation → Check-in → Open Session
└── Occupied  → Open Existing Dining Session
```

Available:

```text
Available Table
      ↓
Start Service
      ↓
Open Dining Session
      ↓
Table becomes Occupied
```

Occupied:

```text
Occupied Table
      ↓
Open Current Dining Session
```

Không tạo session thứ hai.

---

# 8. Order Experience

## 8.1 Create / Add Order

```text
Dining Session
      ↓
Add Order
      ↓
POS Menu
      ↓
Select Products
      ↓
Current Order
├── Change Quantity
├── Remove Item
├── Add Note
└── Continue Adding
      ↓
Confirm Order
      ↓
Validation
      ↓
Create Order
      ↓
Return to Current Dining Session
```

Context preservation:

```text
Dining Session
      ↓
Add Order
      ↓
Confirm
      ↓
Same Dining Session
```

---

## 8.2 Order Item Processing

```text
Order Item Created
     ↓
Waiting
     ↓
Preparing
     ↓
Ready
     ↓
Served
```

Order/Item cancellation phải là explicit action:

```text
Cancel Action
     ↓
Confirmation
     ↓
Cancelled
```

Cancellation chỉ khả dụng theo status/permission; UI phải thu reason.
Order-level summary nếu hiển thị phải được derive từ Item states và
không thay thế Item state source of truth.

Không xóa âm thầm transaction đã phát sinh.

---

# 9. Billing & Payment Experience

Billing Unit:

```text
Dining Session
```

Bill summary:

```text
Dining Session
├── Order #1
├── Order #2
├── ...
├── Subtotal
├── Discount
└── Total
```

Payment flow:

```text
Dining Session
      ↓
Open Billing
      ↓
Aggregate Orders
      ↓
Review Total
      ↓
Apply Valid Discount [optional]
      ↓
Select Payment Method
      ↓
Confirm Payment
      ↓
Successful?
├── No  → Error → Retry / Change Method
└── Yes → Mark Paid → Close Session
```

Critical rule:

```text
Payment Failed
      ↓
Dining Session remains Active
```

Không được:

```text
Payment Failed → Close Dining Session
```

Order completion cũng không đồng nghĩa Payment completion.

---

# 10. Admin Experience Pattern

Admin không cần flow độc lập cho mọi CRUD module.

Baseline pattern:

```text
Login
 ↓
Dashboard
 ↓
Select Module
 ↓
List
├── Search
├── Filter
├── Pagination
├── View Detail
├── Create
└── Edit
      ↓
Form
      ↓
Validation
      ↓
Save
      ↓
Feedback
```

Destructive action:

```text
Action
 ↓
Confirmation
 ↓
Confirm?
├── No  → Return
└── Yes → Execute → Feedback
```

Các module chỉ thay:

```text
Fields
Filters
Permissions
Actions
Statuses
```

---

# 11. Global Language Flow

Supported locales:

```text
VI — Default
EN
ZH
```

Flow:

```text
Any Customer Screen
        ↓
Language Selector
        ↓
VI / EN / ZH
        ↓
Translation Available?
├── Yes → Translate
└── No  → Fallback
        ↓
Stay on Current Screen
```

Critical navigation rule:

> Switch language phải giữ nguyên screen và context hiện tại.

Ví dụ:

```text
VI Product Detail
      ↓
Switch EN
      ↓
EN Product Detail
```

Không đưa user về Home.

---

# 12. Customer Information Architecture

```text
CUSTOMER WEBSITE

Home
│
├── Menu
│   ├── Search
│   ├── Category Filter
│   ├── Recommendation
│   └── Product Detail
│       └── Add to Cart
│
├── Cart
│   └── Submit Dine-in Order
│       └── Order Result
│
├── Reservation
│   └── Reservation Result
│
└── Language Switch
```

---

# 13. Customer Screen Inventory

| ID | Screen | Core Purpose |
|---|---|---|
| C01 | Home | Entry point, featured content, navigation |
| C02 | Menu | Browse/search/filter products |
| C03 | Product Detail | View product information and add to cart |
| C04 | Cart | Review and modify selected items |
| C05 | Submit Dine-in Order | Validate session and submit Cart |
| C06 | Order Result / Status | Show result/state of order request |
| C07 | Reservation | Submit reservation request |
| C08 | Reservation Result / Status | Show reservation state |

Category filter, empty state và error state không được coi là screen độc lập nếu chỉ là variant của screen hiện tại.

---

# 14. Staff / POS Information Architecture

```text
STAFF / POS

Login
 ↓
Table Map
 │
 ├── Available Table
 │      ↓
 │   Open Session
 │
 ├── Reserved Table
 │      ↓
 │   Reservation / Check-in
 │
 └── Occupied Table
        ↓
   Dining Session
        │
        ├── Add Order
        │     ↓
        │   POS Menu
        │
        ├── Order Detail
        └── Billing / Payment

Orders
 ↓
Order Processing
```

Primary POS navigation:

```text
Tables
Orders
Account
```

POS không được biến thành Admin Dashboard thứ hai.

---

# 15. Staff / POS Screen Inventory

| ID | Screen | Core Purpose |
|---|---|---|
| P01 | Staff Login | Authenticate Staff |
| P02 | Table Map | View operational table states |
| P03 | Dining Session Detail | Manage active table/session context |
| P04 | POS Ordering | Create/add Order |
| P05 | Order Detail / Processing | View and update Order state |
| P06 | Orders Board | Monitor Order queue/status |
| P07 | Billing / Payment | Review bill and complete payment |

Billing và Payment có thể là một screen nhiều bước hoặc hai screen tùy UI implementation; UX boundary không bắt buộc route cụ thể.

---

# 15A. Kitchen / Bar Information Architecture

```text
KITCHEN / BAR

Login
 ↓
Kitchen Queue
 ├── Waiting Items
 ├── Preparing Items
 └── Ready Items
        ↓
   Item Detail / Note
```

Kitchen screen inventory:

| ID | Screen | Core Purpose |
|---|---|---|
| K01 | Kitchen Login | Authenticate Kitchen user |
| K02 | Kitchen Queue | Scan/filter Item-level work queue |
| K03 | Item Detail | View note/context and perform valid transition |

---

# 16. Admin Information Architecture

```text
ADMIN

Dashboard

Operations
├── Orders
├── Reservations
└── Tables

Menu
├── Products
└── Categories

Customers

Employees

Access Control [Admin only]
├── Roles
└── Permissions

Inventory

Vouchers

Reports

Translations [Admin only]

System Settings [Admin only]
```

Role/Permission quyết định item nào được hiển thị hoặc sử dụng.

---

# 17. Admin Screen / Pattern Inventory

Admin ưu tiên reusable patterns thay vì screen explosion.

```text
Admin Login
Dashboard
CRUD List Pattern
Detail Pattern
Create / Edit Form Pattern
Reports Pattern
Confirmation Modal
Feedback State
```

Applied modules:

```text
Products
Categories
Tables
Reservations
Orders
Customers
Employees
Inventory
Vouchers
Reports
```

---

# 18. Navigation Rules

## UX-NAV-01 — Preserve Operational Context

```text
Dining Session
 ↓
Add Order
 ↓
Confirm
 ↓
Return Current Dining Session
```

Không đưa Staff về Dashboard/Table Map sau mỗi action nếu không cần.

---

## UX-NAV-02 — Back Returns to Entry Context

Nếu Order Detail được mở từ Dining Session:

```text
Order Detail → Back → Dining Session
```

Nếu mở từ Orders Board:

```text
Order Detail → Back → Orders Board
```

---

## UX-NAV-03 — Preserve Filters

Ví dụ:

```text
Products
 ↓
Filter: Beer
 ↓
Product Detail
 ↓
Back
```

nên quay lại:

```text
Products
Filter: Beer
```

không reset context.

---

## UX-NAV-04 — Language Switch Preserves Context

Switch locale không thay đổi entity/screen mà user đang xem.

---

# 19. Screen + Pattern + State Principle

Không tạo một screen riêng cho mọi state/action.

Không:

```text
Product List
Create Product
Edit Product
Delete Confirm
Delete Success
Delete Error
```

như các screen hoàn toàn độc lập.

Ưu tiên:

```text
List Pattern
Form Pattern
Modal Pattern
Feedback Pattern
State Variants
```

Baseline:

> **Screen + Pattern + State**

---

# 20. UX State Baseline

UX phải nhận thức các state chính dù visual treatment thuộc UI Design.

Common states:

```text
Loading
Data / Success
Empty
Error
Disabled
Submitting / Processing
```

Domain states cần phản ánh đúng nghiệp vụ.

Reservation:

```text
Pending
Confirmed
Checked-in
Completed
No-show
Rejected
Cancelled
```

Order Item:

```text
Waiting
Preparing
Ready
Served
Cancelled
```

Payment:

```text
Ready
Processing
Success
Failed
```

---

# 21. Error Recovery Principles

Form submit lỗi:

```text
Error
 ↓
Keep User Input
 ↓
Allow Retry
```

POS network/request error:

```text
Error
 ↓
Keep Current Order / Current Context
 ↓
Retry
```

Không reset dữ liệu user vừa nhập nếu không bắt buộc.

---

# 22. AI Recommendation Boundary

Recommendation là supporting UX capability.

```text
Menu
 ↓
Recommendation
 ↓
Suggested Products
```

Nếu Recommendation lỗi:

```text
Normal Menu remains usable
```

AI không được trở thành bước bắt buộc để:

```text
Browse Menu
Place Order
Make Reservation
Complete Payment
```

---

# 23. Authentication & Permission UX Boundary

Authentication là entry requirement cho protected internal contexts.

Không tạo fake User Flow kiểu:

```text
Create Order <<include>> Login
```

UX chỉ cần hiểu:

```text
Protected Screen
      ↓
Authenticated?
├── No  → Login
└── Yes → Continue
```

Permission quyết định action visibility, nhưng backend authorization vẫn là technical security boundary.

---

# 24. Core UX Rules

## UX-RULE-01 — Dining Session Is POS Context

```text
Table
 ↓
Dining Session
 ↓
Orders
```

## UX-RULE-02 — Additional Order Reuses Existing Session

Không mở session mới khi khách gọi thêm món.

## UX-RULE-03 — Order ≠ Payment

Served/Completed Order không đồng nghĩa Bill đã Paid.

## UX-RULE-04 — Billing Unit Is Dining Session

Billing tổng hợp toàn bộ valid Orders trong Dining Session.

## UX-RULE-05 — Payment Failure Keeps Session Open

Failure phải cho phép retry.

## UX-RULE-06 — Reservation Request Starts Pending

Submitted không mặc định Confirmed.

## UX-RULE-07 — AI Is Optional

Recommendation failure không làm Core Customer Experience lỗi.

## UX-RULE-08 — Locale Switch Keeps Context

Không reset navigation.

## UX-RULE-09 — Preserve User Input

Error không được tự xóa form/cart/current order nếu không cần.

## UX-RULE-10 — Reuse Patterns

Không tạo screen mới chỉ vì khác state nhỏ.

---

# 25. UX Boundaries

UX Baseline quyết định:

```text
User Goals
Journeys
Flows
Screen Inventory
Information Architecture
Navigation Behavior
Context Preservation
State Semantics
Interaction Constraints
```

UX Baseline không quyết định:

```text
Colors
Typography
Spacing Tokens
Icon Style
Visual Branding
Pixel Dimensions
Exact Breakpoints
Database Tables
API Endpoints
Laravel Class Structure
```

---

# 26. UI Design Handoff

UI Design phải nhận trực tiếp:

```text
User Journey
      ↓
Core User Flow
      ↓
Screen Inventory
      ↓
Information Architecture
      ↓
Navigation Rules
      ↓
UX Constraints
```

UI không được tự thay đổi:

```text
Dining Session model
Reservation lifecycle
Payment failure behavior
Additional Order behavior
Language context preservation
AI fallback behavior
```

chỉ để layout thuận tiện hơn.

---

# 27. Final UX Decisions

## UX-FINAL-01 — Four Main UX Contexts

```text
Customer
Staff / POS
Kitchen / Bar
Admin / Manager
```

## UX-FINAL-02 — Seven Core Journeys

```text
Explore Menu
Customer Ordering
Reservation
Table Service / Dining Session
Order Processing
Billing / Payment
Back-office Management
```

## UX-FINAL-03 — Dining Session Is Central POS Context

```text
Table → DiningSession → N Orders
```

## UX-FINAL-04 — Reservation and Dining Session Stay Separate

Reservation là pre-service request; Dining Session là actual service context.

## UX-FINAL-05 — Payment Failure Does Not Complete Service

UI phải giữ session/context để retry.

## UX-FINAL-06 — Admin Uses Reusable Management Patterns

Không vẽ một interaction model riêng cho mỗi CRUD.

## UX-FINAL-07 — Language Switching Is Global but Context-Preserving

VI mặc định, EN/ZH optional locale.

## UX-FINAL-08 — Recommendation Is Supporting

Không làm Core flow phụ thuộc AI.

---

# 28. Definition of Done

UX & User Flow được coi là hoàn tất khi:

- [x] UX actors/context finalized.
- [x] Core User Journeys finalized.
- [x] Customer flow finalized.
- [x] Reservation flow finalized.
- [x] Dining Session flow finalized.
- [x] Order/additional-order flow finalized.
- [x] Billing/payment flow finalized.
- [x] Admin management pattern finalized.
- [x] Customer IA finalized.
- [x] POS IA finalized.
- [x] Kitchen/Bar IA and screen inventory finalized.
- [x] Admin IA finalized.
- [x] Screen inventory finalized.
- [x] Navigation rules finalized.
- [x] Language behavior finalized.
- [x] AI fallback behavior finalized.
- [x] Context-preservation rules finalized.
- [x] UX/UI boundary preserved.
- [x] Ready for UI Design.

---

# 29. Final Baseline

```text
UX & USER FLOW BASELINE v1.0

Customer
├── Home
├── Menu
│   └── Product Detail
├── Cart
│   └── Submit Dine-in Order
│       └── Order Result
├── Reservation
│   └── Reservation Result
└── Language Switch

Staff / POS
├── Table Map
│   └── Dining Session
│       ├── Add Order
│       ├── Order Detail
│       └── Billing / Payment
└── Orders Board

Kitchen / Bar
├── Kitchen Queue
└── Item Detail / State Transition

Admin
├── Dashboard
├── Operations
├── Menu Management
├── Customers
├── Employees
├── Inventory
├── Vouchers
└── Reports


CORE EXPERIENCE MODEL

Customer:
Browse → Choose → Cart / Order

Restaurant:
Table
  ↓
Dining Session
  ↓
Multiple Orders
  ↓
Processing / Serving
  ↓
Billing
  ↓
Payment
  ↓
Close Session

Supporting:
Language Switching
AI Recommendation
```

**Status: BASELINE v1.0 — Ready for UI Design**
