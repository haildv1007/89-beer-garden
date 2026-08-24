# 05. Database & Architecture — System Architecture Baseline

**Project:** 89 Beer Garden Website & Management System  
**Document:** System Architecture Baseline  
**Version:** 1.0  
**Status:** Finalized / Ready for Development Planning

---

## 1. Purpose

Tài liệu này chốt **System Architecture Baseline** cho hệ thống 89 Beer Garden.

Mục tiêu là cung cấp cho developer hoặc AI coding agent một kiến trúc đủ rõ để triển khai Laravel mà không phải tự suy diễn lại:

- architecture style;
- system components;
- presentation contexts;
- business module boundaries;
- application layers;
- controller/service/model responsibilities;
- module dependencies;
- transaction boundaries;
- concurrency rules;
- external integrations;
- cache/storage/logging strategy;
- authentication/authorization boundaries;
- failure/fallback strategy;
- các giới hạn để tránh over-engineering.

Tài liệu này **không phải danh sách B1/B2/B3/B4**. Đây là kết quả hợp nhất cuối cùng dùng làm **source of truth** cho Development.

---

# 2. Architecture Style

Architecture chính:

> **Laravel Modular Monolith**

Toàn bộ Core System chạy trong một Laravel Application và một Core MySQL Database.

```text
Customer / Staff / Kitchen / Admin
              ↓
      Laravel Application
              ↓
       Business Modules
              ↓
             MySQL
```

Modular Monolith ở đây có nghĩa:

```text
One Deployable Application
+
Clear Business Module Boundaries
+
Shared Transaction Boundary
```

Không có nghĩa toàn bộ business logic được viết chung trong Controller/Model.

---

# 3. Architecture Scope

Core architecture bao gồm:

```text
Presentation
Application / Business Logic
Domain / Data
Infrastructure
External Integrations
```

Hệ thống không sử dụng trong Core Scope:

```text
Microservices
API Gateway
Service Discovery
Distributed Transactions
Message Broker Cluster
Event Sourcing
CQRS
Separate Database per Module
```

Các pattern này chỉ được xem xét nếu requirement hoặc scale tương lai thực sự yêu cầu.

---

# 4. High-Level System Map

```text
┌──────────────────────────────────────────────────────┐
│                       USERS                          │
│                                                      │
│ Customer       Staff       Kitchen       Admin       │
└─────────────────────────┬────────────────────────────┘
                          │
                          ▼
┌──────────────────────────────────────────────────────┐
│                PRESENTATION LAYER                    │
│                                                      │
│ Customer Web │ POS │ Kitchen UI │ Admin UI           │
└─────────────────────────┬────────────────────────────┘
                          │
                          ▼
┌──────────────────────────────────────────────────────┐
│              LARAVEL APPLICATION                     │
│                                                      │
│ Authentication / Authorization                       │
│ Validation / Exception Handling                      │
│                                                      │
│ Menu / Customer / Reservation / Table                │
│ DiningSession / Order / Kitchen                      │
│ Billing / Payment / Voucher                          │
│ Inventory / Reporting                                │
│ Localization / Configuration / Recommendation        │
└─────────────────────────┬────────────────────────────┘
                          │
                ┌─────────┴──────────┐
                ▼                    ▼
┌──────────────────────────┐  ┌─────────────────────────┐
│          MySQL           │  │ External Integrations   │
│                          │  │                         │
│ Core Source of Truth     │  │ Translation Provider    │
│                          │  │ Weather Provider        │
└──────────────────────────┘  │ AI Provider             │
                              │ Payment Gateway [Future]│
                              └─────────────────────────┘

Supporting Infrastructure:
- Laravel Storage
- Laravel Cache
- Laravel Logging
- Environment Configuration
```

---

# 5. Presentation Contexts

Hệ thống có 4 presentation contexts chính.

## 5.1 Customer Website

Capabilities:

```text
Browse Menu
View Product Detail
Reservation
Customer Ordering [if enabled]
Recommendation
Language Switch
Customer Account / History
Session Cart / Dine-in Self-order
```

Flow:

```text
Customer
   ↓
Customer UI
   ↓
Laravel
   ↓
Business Modules
   ↓
MySQL
```

Customer UI không được truy cập database trực tiếp và không được chứa provider secrets.

---

## 5.2 Staff / POS

POS chịu trách nhiệm các restaurant operations chính:

```text
Table Status
Reservation Processing
Check-in
Open Dining Session
Create Order
Additional Order
Order Tracking
Serving
Billing
Payment
Invoice
Close Dining Session
```

Core operational flow:

```text
Table
 ↓
Dining Session
 ↓
Order(s)
 ↓
Serving
 ↓
Billing
 ↓
Payment
 ↓
Close Session
```

POS là transaction-critical context.

Backend phải enforce mọi business rule quan trọng.

---

## 5.3 Kitchen / Bar

Kitchen/Bar xử lý ở **Order Item level**.

```text
Waiting
 ↓
Preparing
 ↓
Ready
 ↓
Served
```

Kitchen Queue là query/view của các Order Items đang cần xử lý.

Không tạo Kitchen Order entity/table riêng chỉ vì có Kitchen UI.

---

## 5.4 Admin / Manager

Capabilities:

```text
Menu Management
Table Management
Reservation Management
Order Monitoring
Voucher Management
Inventory
Customer Management
Employee Management
Role / Permission
Reports
System Configuration
```

Admin có quyền rộng nhưng không được bypass Data Integrity.

---

# 6. Core Operational Architecture

Backbone quan trọng nhất:

```text
RestaurantTable
      ↓
DiningSession
      ↓
Multiple Orders
      ↓
OrderItems
```

Financial backbone:

```text
DiningSession
      ↓
Bill
      ↓
Payment Attempts
```

Các module, service và UI phải giữ nguyên hai backbone này.

Không được tự rút gọn thành:

```text
Table = Order
```

hoặc:

```text
Bill = Payment
```

---

# 7. Logical Business Modules

Baseline modules:

```text
Auth & Access

Menu
Customer
Reservation
Table
Dining Session
Order
Kitchen

Billing
Payment
Voucher

Inventory

Reporting

Localization
Configuration
Recommendation
```

Đây là **logical module boundaries**, không phải yêu cầu mỗi module là package hoặc service riêng.

---

# 8. Auth & Access Module

Ownership:

```text
Authentication
Authorization
Users
Employees
Roles
Permissions
Policies
```

Data:

```text
users
employees
roles
permissions
role_permissions
```

Flow:

```text
Request
 ↓
Authentication
 ↓
Authorization
 ↓
Business Action
```

Các module khác không tự viết một permission system riêng.

---

# 9. Menu Module

Ownership:

```text
Categories
Products
Current Price
Availability
Menu Browsing
```

Data:

```text
categories
products
```

Consumers:

```text
Customer
POS
Order
Recommendation
Admin
```

Order Module đọc current Product data khi tạo Order nhưng phải snapshot transaction values vào OrderItem.

---

# 10. Customer Module

Ownership:

```text
Customer Profile
Customer Lookup
Customer History Context
Customer Account Ownership
Session Cart
```

Data:

```text
customers
```

Customer business data không đồng nghĩa với authentication identity.

```text
Customer ≠ User
```

Khi Customer có account, `customers.user_id` là unique nullable link tới
authentication User. Cart là session/application state và không phải
MySQL transaction source of truth.

---

# 11. Reservation Module

Ownership:

```text
Create Reservation
Validate Reservation
Confirm
Reject
Cancel
Reservation Lookup
Check-in Context
```

Data:

```text
reservations
```

Reservation không tự mở Dining Session.

```text
Reservation
 ↓
Confirmed
 ↓
Customer Arrival
 ↓
Table Assignment
 ↓
DiningSession
```

---

# 12. Table Module

Ownership:

```text
Restaurant Tables
Capacity
Availability
Operational Table Context
```

Data:

```text
restaurant_tables
```

Table state phải phối hợp với Reservation/DiningSession.

`restaurant_tables.runtime_status` là source of truth cho
`available/reserved/occupied/cleaning`; `is_active` là configuration
flag riêng. Service cập nhật runtime status cùng transaction với
Reservation/DiningSession và re-check relationship để chống drift.

---

# 13. Dining Session Module

Dining Session là **central operational context**.

Ownership:

```text
Open Session
Attach Table
Attach Customer
Attach Reservation
Track Active Session
Complete Session
```

Data:

```text
dining_sessions
```

Critical rule:

```text
1 RestaurantTable
      ↓
max 1 active DiningSession
```

Relationship:

```text
Table
 ↓
DiningSession
 ├── Orders
 └── Bill
```

Canonical Dining Session states:

```text
active → completed
```

`billing` là workflow/UI context; không dùng `billing` hoặc `closed` làm
Dining Session state.

---

# 14. Order Module

Ownership:

```text
Create Order
Additional Order
Add Order Items
Product Validation
Historical Price Snapshot
Order Status
Cancellation
Customer Self-order Context Validation
```

Data:

```text
orders
order_items
```

Dependencies:

```text
DiningSession
Menu
Employee
Customer [when self-order]
```

Order không trực tiếp sở hữu Table state.

---

# 15. Kitchen Module

Ownership:

```text
Kitchen Queue
Order Item Processing
Order Item State Transition
```

Data chính vẫn là:

```text
order_items
```

State transition:

```text
waiting
 ↓
preparing
 ↓
ready
 ↓
served
```

Valid cancellation:

```text
waiting / preparing → cancelled
```

Service phải enforce permission và lưu actor/time/reason.

Backend phải validate transition.

---

# 16. Billing Module

Ownership:

```text
Collect Billable Items
Calculate Subtotal
Apply Discount
Calculate Total
Create / Update Bill
```

Data:

```text
bills
```

Billing Unit:

```text
DiningSession
```

Billing phải sử dụng historical transaction values:

```text
OrderItem.unit_price
```

Không dùng current `Product.price` để tính lại lịch sử.

Bill là financial record. Khi Bill `paid`, Invoice là read-only/printable
representation của paid Bill; Core không có Invoice entity riêng.

---

# 17. Voucher Module

Ownership:

```text
Voucher Lookup
Validity
Time Range
Minimum Amount
Usage Limit
Discount Calculation
```

Data:

```text
vouchers
```

Billing có thể sử dụng Voucher Module.

Voucher vẫn là optional extension của Billing.

---

# 18. Payment Module

Ownership:

```text
Payment Attempt
Payment Method
Payment Confirmation
Payment Status
Duplicate Protection
```

Data:

```text
payments
```

Relationship:

```text
Bill
 ↓
Payment Attempt(s)
```

Successful payment mới cho phép normal completion:

```text
Payment Success
      ↓
Bill = Paid
      ↓
DiningSession = Completed
```

Payment failure:

```text
Payment Failed
      ↓
Bill remains Unpaid
      ↓
DiningSession remains Active
```

---

# 19. Inventory Module

Ownership:

```text
Inventory Items
Current Stock
Stock Movements
Stock Adjustment
Low Stock
```

Data:

```text
inventory_items
stock_movements
```

Core scope không quản lý Recipe/BOM phức tạp.

---

# 20. Configuration Module

Ownership:

```text
Admin-managed Runtime Settings
Typed Validation
Configuration Audit Metadata
```

Data:

```text
system_settings
```

Core settings:

```text
no_show_timeout_minutes
```

Secrets/API credentials không thuộc module này và tiếp tục nằm trong
environment configuration. Supplier/Purchasing thuộc Future Scope;
Core stock import/export/adjustment tạo Stock Movement trực tiếp.

---

# 21. Reporting Module

Reporting là read-oriented capability.

Inputs:

```text
Orders
OrderItems
Bills
Payments
Reservations
Products
```

Outputs:

```text
Revenue
Order Count
Top Products
Reservation Statistics
```

Không sở hữu Core transaction tables riêng.

Nếu query phức tạp có thể dùng Query Objects nhưng không bắt buộc Repository Pattern toàn hệ thống.

---

# 22. Localization Module

Supported locales:

```text
VI — Default
EN
ZH
```

Ownership:

```text
Locale Resolution
Translation Resolution
Translation Cache
Fallback
Translation Provider
Persistent Translation Editing
```

Flow:

```text
Requested Locale
      ↓
Persistent Translation?
    /                    \
  Yes                    No
   ↓                      ↓
Return              Translation Provider
                          ↓
                        Persist
                          ↓
                Optional Runtime Cache
                          ↓
                        Return
```

Failure:

```text
Translation Provider Error
           ↓
Fallback Vietnamese
```

Translation không được làm Menu/Reservation/Order fail.

MySQL `translations` là source of truth cho dynamic/manual translation.
Manual translation được ưu tiên hơn provider-generated translation;
runtime cache có thể tái tạo.

---

# 23. Recommendation Module

Inputs có thể gồm:

```text
Available Products
Current Time
Weather [optional]
Customer History [optional]
Popular Products
```

Output:

```text
Recommended Product IDs
```

Boundary:

```text
Recommendation ≠ Ordering
```

AI chỉ gợi ý.

AI không được:

```text
Create Order
Modify Order
Change Price
Apply Voucher
Confirm Payment
Modify Stock
```

Provider result phải được backend validate trước khi trả cho user.

---

# 24. Application Layers

Architecture layers:

```text
Presentation
     ↓
Application / Business
     ↓
Domain / Data
     ↓
Infrastructure
```

Laravel mapping:

```text
Routes / Controllers / Requests
              ↓
           Services
              ↓
 Models / Eloquent / Queries
              ↓
            MySQL
```

External integrations:

```text
Application Service
       ↓
Integration Contract
       ↓
Provider Adapter
       ↓
External API
```

---

# 25. Presentation Layer Responsibility

Presentation gồm:

```text
Routes
Controllers
Form Requests
Resources / Responses
Views / Frontend
```

Responsibilities:

- receive request;
- basic/input validation;
- use authentication context;
- authorization entry;
- call business operation;
- return response.

Controllers phải **thin**.

Không đặt multi-entity transaction vào Controller.

---

# 26. Application / Business Layer

Business layer sở hữu:

```text
Business Validation
State Transitions
Transaction Boundaries
Coordination Across Models/Modules
External Integration Calls
```

Critical service candidates:

```text
OpenDiningSessionService
CreateOrderService
BillingService
CompletePaymentService
AdjustStockService
VoucherService
RecommendationService
TranslationService
```

Không bắt buộc Service cho mọi CRUD.

---

# 27. Model / Data Layer

Eloquent Models dùng cho:

```text
Persistence Representation
Relationships
Casts
Scopes
Simple Helpers / State Checks
```

Ví dụ:

```text
DiningSession
├── table()
├── customer()
├── reservation()
├── orders()
└── bill()
```

Không nhét toàn bộ workflow phức tạp vào Model.

---

# 28. Infrastructure Layer

Infrastructure concerns:

```text
MySQL
External APIs
Storage
Cache
Logging
Environment Configuration
```

Business logic không được phụ thuộc trực tiếp provider-specific implementation ở nhiều nơi.

---

# 29. Source Code Organization Decision

Source code organization bắt buộc theo:

> **Conventional Laravel Modular Monolith**

Project giữ Laravel conventions và không sử dụng `app/Modules` hoặc package hierarchy sâu kiểu `Domain/Application/Infrastructure` trong Core Scope.

Quy tắc phân chia:

```text
Presentation code  → chia theo context: Customer / POS / Kitchen / Admin
Business services  → chia theo domain
Models             → giữ tập trung tại app/Models
External providers → cô lập tại app/Integrations
Reports            → complex read queries tại app/Queries/Reports
```

Core structure:

```text
app/
├── Enums/
│
├── Http/
│   ├── Controllers/
│   │   ├── Customer/
│   │   ├── POS/
│   │   ├── Kitchen/
│   │   ├── Admin/
│   │   └── Webhook/               [when required]
│   ├── Middleware/
│   ├── Requests/
│   │   ├── Customer/
│   │   ├── POS/
│   │   ├── Kitchen/
│   │   └── Admin/
│   └── Resources/                 [when JSON API is required]
│
├── Models/
│
├── Policies/
│
├── Services/                      [only for non-trivial business flows]
│   ├── Reservation/
│   ├── DiningSession/
│   ├── Order/
│   ├── Billing/
│   ├── Payment/
│   ├── Inventory/
│   ├── Localization/
│   ├── Configuration/
│   └── Recommendation/
│
├── Integrations/                  [when external providers are required]
│   ├── Translation/
│   ├── Weather/
│   ├── AI/
│   └── Payment/
│
├── Queries/                       [when complex read queries are required]
│   └── Reports/
│
├── Jobs/                          [when background processing is required]
├── Notifications/                 [when notifications are required]
├── Exceptions/                    [when explicit business exceptions are required]
├── Providers/
└── Support/                       [pure shared utilities only]
```

Supporting Laravel structure:

```text
resources/views/
├── customer/
├── pos/
├── kitchen/
├── admin/
├── components/
└── layouts/

routes/
├── web.php                         # Customer website
├── pos.php
├── kitchen.php
├── admin.php
├── api.php                         # only when an API is required
└── console.php

tests/
├── Feature/
│   ├── Customer/
│   ├── POS/
│   ├── Kitchen/
│   └── Admin/
└── Unit/
```

Đây là quyết định kiến trúc cấp cao và là source of truth cho bố cục source code.

Quy ước triển khai chi tiết nằm tại:

```text
docs/06-development/06-source-code-structure-convention.md
```

Không tạo folder/class rỗng chỉ để đúng diagram.

Optional folder chỉ được tạo khi có implementation thực tế cần sử dụng. Developer và AI coding agent không được tự đưa vào một top-level source structure khác nếu chưa cập nhật Architecture Decision này.

---

# 30. Controller Organization

Presentation controllers bắt buộc chia theo context:

```text
Customer/
POS/
Kitchen/
Admin/
```

Examples:

```text
Customer/
    MenuController
    ReservationController

POS/
    TableController
    DiningSessionController
    OrderController
    BillingController
    PaymentController

Kitchen/
    KitchenOrderController

Admin/
    ProductController
    CategoryController
    EmployeeController
    VoucherController
    InventoryController
    ReportController
    TranslationController
    SystemSettingController
```

Business logic dùng chung phải được reuse qua Service/Application Layer.

---

# 31. Input Validation vs Business Validation

## Input Validation

Form Request có thể validate:

```text
required
string
integer
date
format
max length
```

Examples:

```text
CreateOrderRequest
StoreReservationRequest
ProcessPaymentRequest
AdjustStockRequest
```

## Business Validation

Service/backend validate:

```text
Table currently available?
Existing active DiningSession?
Product available?
Bill already paid?
Voucher valid?
Valid state transition?
Enough stock?
```

Frontend validation không phải integrity boundary.

---

# 32. Authorization Boundary

Backend authorization flow:

```text
Request
 ↓
Authentication
 ↓
Role / Permission
 ↓
Policy / Gate
 ↓
Business Action
```

Frontend chỉ hide/show action cho UX.

```text
Hidden Button ≠ Authorization
```

Admin cũng không được bypass business constraints.

---

# 33. PHP Enums & State Transitions

Baseline enums:

```text
ReservationStatus
RestaurantTableStatus
DiningSessionStatus
OrderItemStatus
BillStatus
PaymentStatus
StockMovementType
```

Database lưu string; application dùng PHP Enum để giữ consistency.

Không chỉ validate status value.

Phải validate transition.

Example:

```text
waiting
 ↓
preparing
 ↓
ready
 ↓
served
```

Không cho arbitrary transition ngược nếu business rule không hỗ trợ.

---

# 34. Repository Pattern Decision

Không bắt buộc Repository Pattern toàn project.

Không tạo boilerplate:

```text
ProductRepositoryInterface
ProductRepository

CategoryRepositoryInterface
CategoryRepository
...
```

chỉ để wrap Eloquent.

Service có thể sử dụng Eloquent trực tiếp.

Repository/Query Object chỉ được thêm khi:

- query phức tạp;
- reuse cao;
- persistence abstraction thực sự có giá trị.

---

# 35. DTO Decision

Không bắt buộc DTO cho CRUD đơn giản.

Form Request validated data có thể đủ.

DTO hợp lý khi:

```text
Complex Service Input
External Integration
Typed Shared Context
```

Possible examples:

```text
RecommendationContext
TranslationRequest
PaymentProviderResult
```

nếu implementation cần.

---

# 36. Events Decision

Critical transaction path phải explicit.

Không dùng event/listener để che giấu các bước bắt buộc phải atomic.

Không:

```text
PaymentSucceeded
 ↓
Listener marks Bill
 ↓
Another Listener closes Session
```

nếu các bước này phải cùng transaction.

Đúng:

```text
CompletePaymentService
├── Payment
├── Bill
└── DiningSession
```

Events phù hợp cho side effects sau commit:

```text
Notification
Analytics
Logging
```

nếu cần.

---

# 37. Queue Strategy

Queue không phải dependency của Core Transaction.

Không queue:

```text
Open Dining Session
Create Order
Complete Payment
```

Queue phù hợp cho:

```text
Notifications
Heavy Reports
Analytics
Translation Cache Warming
```

nếu sau này cần.

Core baseline không bắt buộc queue worker.

---

# 38. Database Architecture

Core persistence:

> **MySQL**

```text
Laravel
   ↓
Eloquent / Query Builder
   ↓
MySQL
```

MySQL là source of truth của:

```text
Users
Products
Reservations
Dining Sessions
Orders
Bills
Payments
Inventory
Permissions
```

Cache hoặc external APIs không được thay thế Core State.

---

# 39. Core Transaction Boundaries

## 39.1 Open Dining Session

```text
Check Table
 ↓
Lock / Re-check State
 ↓
Check Active Session
 ↓
Create DiningSession
 ↓
Table runtime_status = Occupied
 ↓
Commit
```

Concurrency requirement:

```text
1 Table
↓
max 1 Active DiningSession
```

---

## 39.2 Create Order

```text
Validate DiningSession
        ↓
Validate Staff or Customer Session Context
        ↓
Validate Products
        ↓
Read Current Prices
        ↓
Create Order
        ↓
Record Order source/creator
        ↓
Create OrderItems
        ↓
Snapshot Transaction Values
        ↓
Commit
```

Order và OrderItems phải được tạo atomically.

---

## 39.3 Record Stock Movement

```text
Validate Inventory Item and Quantity
       ↓
Create Stock Movement
       ↓
Update Current Stock
       ↓
Commit
```

Partial inventory update không được phép tồn tại.

---

## 39.4 Complete Payment

```text
Validate Bill
      ↓
Prevent Duplicate Completion
      ↓
Create / Confirm Payment
      ↓
Bill = Paid
      ↓
DiningSession = Completed
      ↓
Table runtime_status = Cleaning
      ↓
Commit
```

Nếu một bước fail:

```text
Rollback
```

---

# 40. Concurrency Strategy

Backend phải bảo vệ race conditions.

Critical example:

```text
Staff A ──┐
          ├── Open Table 05
Staff B ──┘
```

System phải đảm bảo:

```text
Maximum 1 Active DiningSession
```

Có thể sử dụng:

```text
Database Transaction
State Re-check
Row Locking where required
```

Không dựa vào UI state.

---

# 41. Payment Idempotency

Payment phải chống duplicate:

```text
Double Click
Network Retry
Repeated Request
```

Checks có thể gồm:

```text
Bill already paid?
Existing successful payment?
Duplicate transaction reference?
```

Mục tiêu:

```text
One intended payment completion
≠
multiple successful records
```

---

# 42. External Integration Architecture

Supporting integrations:

```text
Translation Service
Weather Service
AI Recommendation Provider
```

Future:

```text
Payment Gateway
```

Boundary:

```text
Business/Application
       ↓
Integration Contract
       ↓
Provider Adapter
       ↓
External API
```

Không gọi provider SDK trực tiếp từ Controller/Model ở nhiều nơi.

---

# 43. Translation Integration

Concept:

```text
LocalizationService
      ↓
TranslationProvider
      ↓
Translation Adapter
      ↓
External API
```

Cache candidate:

```text
translation:{locale}:{content_hash}
```

Flow phải kiểm tra MySQL `translations` trước provider. Provider success
được persist rồi mới cache; manual translation luôn có precedence.

Failure:

```text
Translation API Failed
        ↓
Fallback VI
```

Translation là supporting capability.

---

# 44. Weather Integration

Weather chỉ cung cấp context cho Recommendation.

```text
RecommendationService
        ↓
WeatherProvider [optional]
```

Weather có thể cache theo location/time window.

Nếu lỗi:

```text
Weather unavailable
      ↓
Recommendation without weather
```

Không ảnh hưởng Menu/Reservation/Order.

---

# 45. AI Recommendation Integration

Flow:

```text
Recommendation Request
        ↓
Build Context
 ├── Available Products
 ├── Current Time
 ├── Weather [optional]
 └── Customer History [optional]
        ↓
Recommendation Provider
        ↓
Validate Product IDs
        ↓
Return Suggestions
```

Provider output không phải source of truth.

AI chỉ được recommend.

Không được mutate Core Transaction.

Failure:

```text
AI unavailable
   ↓
Popular / Featured / Rule-based / Normal Menu
```

---

# 46. Future Payment Gateway

Future architecture:

```text
Payment Module
      ↓
PaymentProvider
      ↓
Gateway Adapter
```

Gateway-specific implementation không được thay Core Bill/Payment domain model.

Cash/manual payment methods có thể tiếp tục hoạt động độc lập nếu business hỗ trợ.

---

# 47. External Failure Isolation

| Dependency | Failure Behavior |
|---|---|
| MySQL | Core system unavailable |
| Translation | Fallback VI |
| Weather | Recommendation bỏ weather |
| AI | Fallback/default recommendation |
| Storage | Media/upload affected |
| Payment Gateway [Future] | Chỉ online method bị ảnh hưởng |

External timeout không được giữ request vô hạn.

Baseline resilience:

```text
Timeout
Limited Retry
Fallback
Logging
```

Không cần Circuit Breaker framework riêng trong Core Scope.

---

# 48. Cache Strategy

Cache chỉ dành cho data có thể regenerate:

```text
Menu
Categories
Translations
Weather
Recommendation Context
Report Summaries
```

Không dùng cache làm source of truth cho:

```text
Payments
Active DiningSessions
Orders
Inventory Movements
```

Redis không phải Core Requirement.

Laravel file/database cache có thể đủ ở deployment đơn giản.

---

# 49. Storage Strategy

Laravel Storage abstraction được dùng cho:

```text
Product Images
Optional Invoice / Export Files
Other Media
```

Baseline:

```text
local / public disk
```

Future:

```text
S3-compatible storage
```

Database chỉ lưu:

```text
path / URL / reference
```

Không lưu image binary trực tiếp trong MySQL.

---

# 50. Logging Strategy

Baseline dùng Laravel Logging.

Log critical failures với context:

```text
bill_id
payment_id
dining_session_id
order_id
employee_id
provider
failure_reason
```

Không log:

```text
password
API key
secret credentials
```

---

# 51. Exception Handling

Technical exception:

```text
Exception
 ↓
Central Handler
 ↓
Technical Log
 ↓
Safe User Response
```

End user không được thấy SQL/internal stack errors.

Critical business errors có thể được biểu diễn rõ:

```text
TableAlreadyOccupied
DiningSessionNotActive
ProductUnavailable
BillAlreadyPaid
InvalidVoucher
InvalidStateTransition
```

Không cần exception class cho mọi validation nhỏ.

---

# 52. Authentication Technical Strategy

Baseline:

> **Laravel Session-Based Authentication**

phù hợp nếu Customer/POS/Kitchen/Admin cùng web application/server.

Không bắt buộc JWT.

Nếu sau này tách SPA/mobile/API:

```text
Laravel Sanctum
```

có thể được bổ sung.

Không xây OAuth server riêng ở Core Scope.

---

# 53. Security Baseline

Architecture phải giữ:

- password hashing;
- backend authorization;
- CSRF protection cho session-based web;
- secure session/cookie config;
- input validation;
- safe database access qua Eloquent/Query Builder;
- upload validation;
- environment secrets;
- rate limiting cho sensitive endpoints khi cần;
- không tự xây custom crypto/authentication.

Frontend không phải security boundary.

---

# 54. Secrets & Configuration

Secrets nằm trong:

```text
.env / environment configuration
```

Examples:

```text
DB_PASSWORD
TRANSLATION_API_KEY
WEATHER_API_KEY
AI_API_KEY
PAYMENT_GATEWAY_KEY [Future]
```

Không:

```text
hard-code
commit .env
expose API keys to frontend
log secrets
```

Environment tối thiểu:

```text
local
production
```

Testing/staging có thể thêm khi cần.

---

# 55. Upload Security

Uploaded Product images phải validate:

```text
MIME type
File size
Allowed extension
Generated safe filename
```

Không tin filename từ user.

Không cho executable uploads tùy ý trong public path.

---

# 56. Rate Limiting

Có thể áp dụng cho:

```text
Login
Password Recovery
Reservation Submit
External/Public API
```

Không cần rate limit phức tạp cho mọi internal operation.

---

# 57. Reporting Technical Strategy

Baseline:

```text
ReportService / Query
        ↓
MySQL
```

Nếu performance sau này không đủ:

```text
Cache
Aggregate Table
Scheduled Summary
```

mới được xem xét.

Không premature-optimize reporting.

---

# 58. Health & Operational Concerns

Simple health check có thể kiểm tra:

```text
Application running
Database reachable
```

Supporting service như AI/Translation down không đồng nghĩa toàn Core App unhealthy.

Production cần:

```text
Periodic MySQL Backup
Storage Backup if local files matter
```

Exact schedule thuộc Deployment.

---

# 59. Technical Source of Truth

| Concern | Source of Truth |
|---|---|
| Users / Roles / Permissions | MySQL |
| Products | MySQL |
| Current Price | `products` |
| Historical Price | `order_items` |
| Reservations | MySQL |
| Dining Sessions | MySQL |
| Orders | MySQL |
| Bills | MySQL |
| Payments | MySQL |
| Inventory | MySQL |
| Dynamic/manual Translations | MySQL `translations` |
| Translation Runtime Cache | Cache, regenerable |
| Admin Runtime Settings | MySQL `system_settings` |
| Weather | External / cache |
| Recommendation | Derived |
| Uploaded Files | Storage |
| Secrets | Environment |

---

# 60. Module Dependency Baseline

```text
CUSTOMER ───→ RESERVATION ───→ TABLE
    │               │            │
    │               └────┐       │
    │                    ▼       ▼
    └──────────────→ DINING SESSION
                           │
                           ▼
MENU ───────────────────→ ORDER
                           │
                           ▼
                        KITCHEN
                           │
                           ▼
                        BILLING ←── VOUCHER
                           │
                           ▼
                        PAYMENT


ADMIN ────────────────→ CONFIGURATION


MENU ───────┐
CUSTOMER ───┼───────────→ RECOMMENDATION
WEATHER ────┘


MULTIPLE MODULES ───────→ REPORTING
```

`AUTH / Authorization` là cross-cutting concern.

---

# 61. Dependencies to Avoid

Không tạo:

```text
Menu → Order
```

Menu không cần hiểu transaction đang sử dụng nó.

Không tạo:

```text
Order → AI Provider
```

Order phải độc lập Recommendation.

Không tạo:

```text
Table → Payment
```

Table không sở hữu financial logic.

Không tạo:

```text
Model → Controller
```

Không để:

```text
External API → Core Business State
```

---

# 62. Over-Engineering Boundaries

Core Architecture **không bắt buộc**:

```text
Repository for every Model
DTO for every Request
Event for every State Change
Queue for every Operation
Redis
Microservices
Message Broker
CQRS
DDD package hierarchy
```

Nguyên tắc:

> **Simple CRUD → Laravel conventions.**

> **Complex business flow → explicit Service + transaction.**

> **External integration → Adapter/Contract.**

> **Optimization infrastructure → chỉ thêm khi có nhu cầu thực tế.**

---

# 63. Critical Architecture Rules

## ARCH-RULE-01 — Modular Monolith

Một Laravel application với module boundaries rõ.

## ARCH-RULE-02 — Backend Owns Integrity

Authorization, state transition, transaction integrity phải được enforce ở backend.

## ARCH-RULE-03 — Thin Controllers

Controller không sở hữu complex transaction logic.

## ARCH-RULE-04 — Selective Service Layer

Service chỉ bắt buộc khi business flow đủ phức tạp.

## ARCH-RULE-05 — MySQL Is Core Source of Truth

Cache, frontend state và external provider không thay thế Core State.

## ARCH-RULE-06 — One Active Dining Session per Table

Concurrency không được phá rule này.

## ARCH-RULE-07 — Additional Orders Remain in Existing Session

```text
DiningSession
├── Order #1
├── Order #2
└── Order #N
```

## ARCH-RULE-08 — Historical Price Snapshot

Order history sử dụng `OrderItem.unit_price`.

## ARCH-RULE-09 — Billing Unit Is Dining Session

Bill tổng hợp transaction của Dining Session.

## ARCH-RULE-10 — Payment Is Atomic

Successful Payment, Bill state và Dining Session completion phải nhất quán.

## ARCH-RULE-11 — Payment Failure Does Not Close Session

Failure phải cho phép retry.

## ARCH-RULE-12 — Reservation Is Separate from Dining Session

Reservation có thể không tạo Session; walk-in có thể có Session không Reservation.

## ARCH-RULE-13 — AI Cannot Mutate Core Transactions

AI chỉ recommend.

## ARCH-RULE-14 — External Services Degrade Gracefully

Translation/Weather/AI failure không được phá Core flows.

## ARCH-RULE-15 — Critical Flow Must Stay Explicit

Không dùng Event/Queue để che giấu các bước bắt buộc phải atomic.

## ARCH-RULE-16 — Conventional Laravel Source Structure

Core source code tuân theo Laravel conventions. Project không dùng `app/Modules` hoặc package hierarchy sâu `Domain/Application/Infrastructure` trong Core Scope.

## ARCH-RULE-17 — Context-Oriented Presentation

Controllers, Form Requests, Views và Feature Tests được tổ chức theo presentation context:

```text
Customer
POS
Kitchen
Admin
```

## ARCH-RULE-18 — Domain-Oriented Services

Services được tổ chức theo business domain và phải có khả năng reuse giữa các presentation contexts khi cùng business flow.

## ARCH-RULE-19 — Create Structure on Demand

Optional folders và abstractions chỉ được tạo khi có implementation thực tế. Không tạo folder/class/interface rỗng chỉ để khớp architecture diagram.

## ARCH-RULE-20 — No Unapproved Top-Level Structure

Developer và AI coding agent không được đưa vào một top-level source organization khác nếu chưa cập nhật Architecture Baseline và Source Code Structure Convention.

---

# 64. AI Coding Guidance

AI coding agent phải đọc architecture theo thứ tự:

```text
Requirement / Use Case
        ↓
System Flow
        ↓
Database Baseline
        ↓
Architecture Baseline
        ↓
Identify Module Owner
        ↓
Identify Business Rules
        ↓
Simple or Complex?
     /              \
 Simple            Complex
   ↓                  ↓
Laravel          Service
Convention       + Transaction
     \              /
      └──────┬─────┘
             ↓
      Controller / UI
```

AI không được bắt đầu từ Controller rồi tự phát minh business rule.

---

# 65. Implementation Guidance for Critical Flows

## Open Dining Session

```text
POS
 ↓
DiningSessionController
 ↓
Authorization
 ↓
OpenDiningSessionService
 ↓
DB Transaction / Lock
 ↓
Check Existing Active Session
 ↓
Create DiningSession
 ↓
Commit
```

---

## Create Order

```text
POS / Customer
 ↓
OrderController
 ↓
CreateOrderRequest
 ↓
Authorization / Context Validation
 ↓
CreateOrderService
 ↓
Validate DiningSession
 ↓
Validate Products
 ↓
Snapshot Prices
 ↓
DB Transaction
 ├── Create Order
 └── Create OrderItems
 ↓
Commit
```

---

## Complete Payment

```text
POS
 ↓
PaymentController
 ↓
ProcessPaymentRequest
 ↓
Authorization
 ↓
CompletePaymentService
 ↓
DB Transaction
 ├── Validate Bill
 ├── Prevent Duplicate Payment
 ├── Create / Confirm Payment
 ├── Bill → Paid
 ├── DiningSession → Completed
 └── Table runtime_status → Cleaning
 ↓
Commit
```

Failure:

```text
Payment → Failed
Bill → Unpaid
DiningSession → Active
```

---

## Record Stock Movement

```text
Admin
 ↓
InventoryController
 ↓
AdjustStockService
 ↓
DB Transaction
 ├── Validate Quantity
 ├── Create Stock Movement
 └── Update Stock
 ↓
Commit
```

---

# 66. Final Architecture Decisions

## ARCH-FINAL-01 — Architecture Style

```text
Laravel Modular Monolith
```

## ARCH-FINAL-02 — Single Core Application

Customer, POS, Kitchen và Admin sử dụng cùng Laravel Core.

## ARCH-FINAL-03 — Single Core Database

MySQL là authoritative store cho Core business state.

## ARCH-FINAL-04 — Business Module Boundaries

Modules được tổ chức theo domain thay vì nhét logic theo technical folders một cách không có ownership.

## ARCH-FINAL-05 — Selective Services

Critical business transactions phải nằm ở Service/Application Layer; CRUD đơn giản không bị ép qua quá nhiều abstraction.

## ARCH-FINAL-06 — External Integration Isolation

Translation, Weather, AI và future Payment Gateway đi qua adapter/provider boundary.

## ARCH-FINAL-07 — Supporting Integrations Are Replaceable

Provider cụ thể không được lan vào core business code.

## ARCH-FINAL-08 — Graceful Degradation

Translation → fallback VI.  
Weather → recommendation without weather.  
AI → fallback/default menu recommendations.

## ARCH-FINAL-09 — Core Transactions Stay Synchronous

Dining Session, Order và Payment không phụ thuộc queue.

## ARCH-FINAL-10 — Explicit Transaction Integrity

Multi-entity state changes phải atomic và rollback khi fail.

## ARCH-FINAL-11 — Authorization Is Backend-Enforced

Frontend permission visibility chỉ là UX convenience.

## ARCH-FINAL-12 — Avoid Premature Complexity

Microservices, Redis, Queue, Repository/DTO/Event patterns chỉ được thêm khi có nhu cầu thực tế.

## ARCH-FINAL-13 — Source Code Organization

Implementation sử dụng Conventional Laravel Modular Monolith:

```text
Presentation code  → organized by context
Business services  → organized by domain
Models             → centralized in app/Models
External providers → isolated in app/Integrations
```

Chi tiết bắt buộc tuân theo `docs/06-development/06-source-code-structure-convention.md`.

## ARCH-FINAL-14 — Canonical State Model

Architecture sử dụng canonical states từ System Requirements; đặc biệt
Dining Session dùng `active/completed`, Table runtime có `cleaning`, và
Kitchen dùng Order Item states.

## ARCH-FINAL-15 — Customer Self-order Boundary

Session Cart không phải transaction source of truth. Customer Order chỉ
được tạo sau khi backend validate session-bound Dining Session context.

## ARCH-FINAL-16 — Persistent Translation and Settings

Dynamic/manual Translation và Admin runtime settings lưu trong MySQL;
cache chỉ là optimization.

## ARCH-FINAL-17 — Purchasing Future Boundary

Supplier/Purchasing không thuộc Core module. Inventory import/export/
adjustment dùng Stock Movement trực tiếp.

---

# 67. Development Handoff

Architecture Baseline này là đầu vào cho Development.

Development có thể bắt đầu từ:

```text
Database Migrations
      ↓
Eloquent Models
      ↓
Enums
      ↓
Policies / Authorization
      ↓
Core Services
      ↓
Controllers / Requests
      ↓
Customer / POS / Kitchen / Admin UI
```

Suggested implementation priority:

```text
1. Auth / Roles / Permissions

2. Menu

3. Customer / Table / Reservation

4. Dining Session

5. Orders / Kitchen

6. Billing / Payment

7. Voucher

8. Inventory / Stock Movement

9. Reporting

10. Localization / Configuration / Recommendation Integration
```

Exact Sprint/Development Plan được quyết định ở giai đoạn Development Planning.

---

# 68. Definition of Done

System Architecture được coi là hoàn tất khi:

- [x] Architecture style finalized.
- [x] Presentation contexts finalized.
- [x] Logical modules finalized.
- [x] Core operational architecture finalized.
- [x] Application layers finalized.
- [x] Controller/Service/Model responsibilities finalized.
- [x] Validation boundaries finalized.
- [x] Authorization boundary finalized.
- [x] Module dependency direction finalized.
- [x] Critical business services identified.
- [x] Database source-of-truth rule finalized.
- [x] Transaction boundaries identified.
- [x] Concurrency risk identified.
- [x] Payment idempotency requirement identified.
- [x] Integration boundaries finalized.
- [x] Translation fallback finalized.
- [x] Weather fallback finalized.
- [x] AI boundary finalized.
- [x] Cache/storage/logging strategy finalized.
- [x] Authentication technical strategy finalized.
- [x] Security/config baseline finalized.
- [x] Over-engineering boundaries finalized.
- [x] Ready for Development Planning.

---

# 69. Final Baseline

```text
SYSTEM ARCHITECTURE BASELINE v1.0

Users
├── Customer
├── Staff / POS
├── Kitchen / Bar
└── Admin / Manager

Presentation
├── Customer Web
├── POS
├── Kitchen UI
└── Admin UI

Laravel Modular Monolith
├── Auth & Access
├── Menu
├── Customer
├── Reservation
├── Table
├── DiningSession
├── Order
├── Kitchen
├── Billing
├── Payment
├── Voucher
├── Inventory
├── Reporting
├── Localization
├── Configuration
└── Recommendation

Core Persistence
└── MySQL

Supporting Infrastructure
├── Laravel Storage
├── Laravel Cache
├── Laravel Logging
└── Environment Config

External Integrations
├── Translation Provider
├── Weather Provider
├── AI Recommendation Provider
└── Payment Gateway [Future]


CORE OPERATIONAL FLOW

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
Close DiningSession
```

**Status: BASELINE v1.0 — Ready for Development Planning**
