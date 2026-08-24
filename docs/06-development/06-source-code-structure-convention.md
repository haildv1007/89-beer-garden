# 06. Development — Source Code Structure Convention

**Project:** 89 Beer Garden Website & Management System  
**Document:** Source Code Structure Convention  
**Version:** 1.0  
**Status:** Finalized / Required for Development

---

## 1. Purpose

Tài liệu này là source of truth cho cách tổ chức source code của 89 Beer Garden.

Mục tiêu:

- bảo đảm developer và AI coding agent đặt code đúng vị trí;
- giữ cấu trúc Laravel quen thuộc và dễ tiếp quản;
- bảo vệ module boundaries khi hệ thống phát triển;
- tránh fat controller, business helper và duplication;
- tránh tạo abstraction hoặc folder rỗng không cần thiết.

Mọi implementation phải tuân theo tài liệu này. Không được đưa vào một top-level source structure khác nếu chưa cập nhật Architecture Baseline và tài liệu này.

---

## 2. Related Sources of Truth

Thứ tự đọc trước khi implementation:

```text
Requirement / Use Case
        ↓
UX / System Flow Baseline
        ↓
Database Design Baseline
        ↓
System Architecture Baseline
        ↓
Source Code Structure Convention
        ↓
Development Plan / Implementation
```

Tài liệu này quyết định **code được đặt ở đâu**. Nó không thay thế business rules, database constraints hoặc UI baseline.

---

## 3. Final Architecture Style

```text
Conventional Laravel Modular Monolith
```

Baseline:

- một Laravel application;
- một Core MySQL database;
- session-based web authentication;
- Customer, POS, Kitchen và Admin dùng chung Laravel Core;
- Controllers, Requests, Views và Feature Tests chia theo presentation context;
- Services chia theo business domain;
- Models giữ theo Laravel convention tại `app/Models`;
- external providers cô lập tại `app/Integrations`;
- không dùng `app/Modules` trong Core Scope;
- không dùng package hierarchy sâu `Domain/Application/Infrastructure`;
- không tạo Repository, DTO, Action, Event hoặc Interface cho mọi chức năng theo mặc định.

---

## 4. Core Directory Structure

```text
89-beer-garden/
├── app/
│   ├── Console/
│   │   └── Commands/
│   ├── Enums/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Customer/
│   │   │   ├── POS/
│   │   │   ├── Kitchen/
│   │   │   ├── Admin/
│   │   │   └── Webhook/              [when required]
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   │   ├── Customer/
│   │   │   ├── POS/
│   │   │   ├── Kitchen/
│   │   │   └── Admin/
│   │   └── Resources/                [when JSON API is required]
│   ├── Models/
│   ├── Policies/
│   ├── Services/                     [for non-trivial business flows]
│   ├── Integrations/                 [when external providers are required]
│   ├── Queries/                      [when complex read queries are required]
│   ├── Jobs/                         [when background processing is required]
│   ├── Notifications/                [when notifications are required]
│   ├── Exceptions/                   [when explicit business errors are required]
│   ├── Providers/
│   └── Support/                      [pure shared utilities only]
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/
│   ├── vi/
│   ├── en/
│   └── zh/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── customer/
│       ├── pos/
│       ├── kitchen/
│       ├── admin/
│       ├── components/
│       ├── layouts/
│       ├── emails/
│       └── errors/
├── routes/
│   ├── web.php
│   ├── pos.php
│   ├── kitchen.php
│   ├── admin.php
│   ├── api.php                       [when required]
│   └── console.php
├── storage/
└── tests/
    ├── Feature/
    │   ├── Customer/
    │   ├── POS/
    │   ├── Kitchen/
    │   └── Admin/
    └── Unit/
```

Không tạo tất cả optional folders ngay khi scaffold. Folder chỉ được tạo khi có file thực tế cần đặt vào.

---

## 5. Organization Principle

```text
Controller / Request / View / Feature Test → presentation context
Service                                  → business domain
Model                                    → Laravel Eloquent convention
Integration                              → external capability/provider boundary
Query                                    → complex read/report concern
```

Lý do:

- input, authorization và UI của Customer/POS/Kitchen/Admin khác nhau;
- core business flow phải dùng chung giữa nhiều context;
- cùng một `CreateOrderService` có thể được gọi bởi Customer và POS;
- provider bên ngoài không được lan vào controller hoặc model.

---

## 6. Presentation Contexts

### 6.1 Customer

```text
app/Http/Controllers/Customer/
├── HomeController.php
├── MenuController.php
├── ProductController.php
├── CartController.php
├── SubmitOrderController.php
├── OrderController.php
└── ReservationController.php
```

Customer context phục vụ website công khai, menu, session Cart,
reservation và Dine-in self-order trong valid Dining Session context.

### 6.2 POS

```text
app/Http/Controllers/POS/
├── TableController.php
├── DiningSessionController.php
├── OrderController.php
├── BillingController.php
└── PaymentController.php
```

POS context phục vụ nhân viên mở bàn, gọi món, gọi thêm món, lập bill và thanh toán.

### 6.3 Kitchen

```text
app/Http/Controllers/Kitchen/
└── KitchenOrderController.php
```

Không tách thêm controller khi Kitchen UI chưa có nhu cầu thực tế.

### 6.4 Admin

```text
app/Http/Controllers/Admin/
├── DashboardController.php
├── ProductController.php
├── CategoryController.php
├── RestaurantTableController.php
├── ReservationController.php
├── EmployeeController.php
├── VoucherController.php
├── InventoryController.php
├── ReportController.php
├── TranslationController.php
└── SystemSettingController.php
```

Admin context chủ yếu quản lý CRUD, cấu hình và báo cáo.

---

## 7. Layer Responsibilities

### 7.1 Controller

Controller chịu trách nhiệm:

- nhận HTTP request;
- nhận validated data từ Form Request;
- gọi Model trực tiếp cho CRUD đơn giản hoặc gọi Service cho flow phức tạp;
- trả View, redirect hoặc JSON response.

Controller không được:

- sở hữu multi-entity transaction;
- gọi external SDK/API trực tiếp;
- chứa state-transition logic phức tạp;
- sao chép business rule giữa các contexts;
- trở thành nơi chứa report query dài.

Preferred flow:

```text
Route → Controller → Form Request → Service/Model → Response
```

### 7.2 Form Request

Form Request chịu trách nhiệm:

- required/type/format/range validation;
- normalization đơn giản;
- authorization theo request khi phù hợp.

Form Request không thay thế business validation cần database lock, transaction hoặc state transition.

### 7.3 Model

Model có thể chứa:

- relationships;
- casts;
- scopes;
- accessors/mutators;
- hành vi nhỏ gắn trực tiếp với entity.

Model không được gọi external provider hoặc điều phối transaction trên nhiều aggregate.

### 7.4 Service

Service dùng khi flow có một hoặc nhiều đặc điểm:

- thay đổi nhiều model;
- cần database transaction;
- có concurrency hoặc idempotency;
- có state transition quan trọng;
- được reuse bởi nhiều presentation contexts;
- business rule đủ lớn để làm controller/model khó đọc.

CRUD đơn giản không bắt buộc Service.

### 7.5 Policy

Policy enforce authorization trên backend. Việc ẩn/hiện nút ở UI không phải security boundary.

### 7.6 Query

`app/Queries` dùng cho complex read/query/report logic. Không dùng Query class cho mọi lệnh Eloquent đơn giản.

### 7.7 Integration

`app/Integrations` cô lập external capability qua contract/adapter. Core business code phụ thuộc contract, không phụ thuộc provider SDK cụ thể.

### 7.8 Support

`app/Support` chỉ chứa utility hoặc value helper thuần, không sở hữu business workflow.

Không tạo:

```text
OrderHelper.php
PaymentHelper.php
ReservationHelper.php
```

Các workflow này thuộc Service.

---

## 8. Domain Service Organization

Khi service còn ít, có thể đặt trực tiếp:

```text
app/Services/
├── CreateOrderService.php
├── OpenDiningSessionService.php
└── CompletePaymentService.php
```

Khi một domain có nhiều service liên quan, nhóm theo domain:

```text
app/Services/
├── Reservation/
│   ├── CreateReservationService.php
│   ├── ConfirmReservationService.php
│   └── CancelReservationService.php
├── DiningSession/
│   ├── OpenDiningSessionService.php
│   └── CompleteDiningSessionService.php
├── Order/
│   ├── CreateOrderService.php
│   ├── CancelOrderService.php
│   └── UpdateOrderItemStatusService.php
├── Billing/
│   ├── GenerateBillService.php
│   └── ApplyVoucherService.php
├── Payment/
│   ├── CompletePaymentService.php
│   └── RecordFailedPaymentService.php
├── Inventory/
│   └── AdjustStockService.php
├── Localization/
│   └── UpdateTranslationService.php
└── Configuration/
    └── UpdateSystemSettingService.php
```

Không tạo domain folder chỉ để chứa một placeholder không dùng.

---

## 9. Critical Flow Examples

### 9.1 Open Dining Session

```text
routes/pos.php
  → POS/DiningSessionController
  → POS/OpenDiningSessionRequest
  → Services/DiningSession/OpenDiningSessionService
  → DB transaction / row lock
      ├── check RestaurantTable
      ├── check active DiningSession
      ├── create DiningSession
      └── transition table to Occupied
```

### 9.2 Create Order

```text
Customer/OrderController ─┐
                          ├─→ Services/Order/CreateOrderService
POS/OrderController ──────┘
```

`CreateOrderService` phải:

```text
DB transaction
├── validate active DiningSession
├── validate available Products
├── read current prices
├── create Order
├── create OrderItems
└── snapshot OrderItem.unit_price
```

### 9.3 Complete Payment

```text
routes/pos.php
  → POS/PaymentController
  → POS/ProcessPaymentRequest
  → Services/Payment/CompletePaymentService
  → DB transaction
      ├── validate Bill
      ├── prevent duplicate completion
      ├── create/confirm Payment
      ├── mark Bill paid
      ├── close DiningSession
      └── transition RestaurantTable
```

Payment failure không được đóng Bill hoặc Dining Session.

### 9.4 Record Stock Movement

```text
Admin/InventoryController
  → Admin/AdjustStockRequest
  → Services/Inventory/AdjustStockService
  → DB transaction
      ├── validate quantity
      ├── create StockMovement
      └── update current stock
```

---

## 10. Integrations Convention

```text
app/Integrations/
├── Translation/
│   ├── Contracts/TranslationProvider.php
│   ├── Adapters/TranslationProviderAdapter.php
│   └── TranslationResult.php
├── Weather/
│   ├── Contracts/WeatherProvider.php
│   ├── Adapters/WeatherProviderAdapter.php
│   └── WeatherData.php
├── AI/
│   ├── Contracts/RecommendationProvider.php
│   ├── Adapters/AIRecommendationAdapter.php
│   └── RecommendationResult.php
└── Payment/                       [future]
    ├── Contracts/PaymentProvider.php
    ├── Adapters/
    └── PaymentProviderResult.php
```

Provider binding được khai báo tại Service Provider/config phù hợp.

Không được:

```text
Controller → Provider SDK
Model      → External API
External provider response → mutate Core State trực tiếp
```

---

## 11. Routes Convention

```text
routes/
├── web.php       # Customer website
├── pos.php       # prefix /pos, auth + POS authorization
├── kitchen.php   # prefix /kitchen, auth + Kitchen authorization
├── admin.php     # prefix /admin, auth + Admin authorization
├── api.php       # only for actual JSON API endpoints
└── console.php   # Scheduler definitions
```

Route names:

```text
customer.menu.index
customer.reservations.store
pos.tables.index
pos.orders.store
kitchen.orders.index
admin.products.index
```

Routes cùng resource/context phải được group theo prefix, name và middleware. Không đặt business logic trong route closure.

---

## 12. Views and Components Convention

```text
resources/views/
├── customer/
│   ├── home/
│   ├── menu/
│   ├── cart/
│   ├── submit-order/
│   └── reservations/
├── pos/
│   ├── tables/
│   ├── dining-sessions/
│   ├── orders/
│   └── billing/
├── kitchen/
│   └── orders/
├── admin/
│   ├── dashboard/
│   ├── products/
│   ├── reservations/
│   ├── inventory/
│   └── reports/
├── components/
│   ├── base/
│   ├── customer/
│   ├── pos/
│   └── admin/
└── layouts/
    ├── customer.blade.php
    ├── pos.blade.php
    ├── kitchen.blade.php
    └── admin.blade.php
```

Rules:

- one Design System, multiple contexts;
- reusable foundation component đặt tại `components/base`;
- domain/context-specific component đặt tại context tương ứng;
- Blade không truy vấn database hoặc sở hữu business logic;
- page lớn phải tách layout/partial/component hợp lý;
- không hard-code random design values nếu design token đã tồn tại.

---

## 13. Enums Convention

Business states nằm tại `app/Enums`:

```text
ReservationStatus.php
RestaurantTableStatus.php
DiningSessionStatus.php
OrderItemStatus.php
BillStatus.php
PaymentStatus.php
StockMovementType.php
```

Enum name dùng singular PascalCase. Case value phải khớp database baseline. State-transition rules không được rải rác tùy ý trong UI.

---

## 14. Models and Database Convention

Models dùng singular PascalCase:

```text
RestaurantTable.php
DiningSession.php
OrderItem.php
StockMovement.php
```

Migrations, factories và seeders giữ Laravel convention:

```text
database/migrations/
database/factories/
database/seeders/
```

Không chia migration theo module package. Database constraints phải bảo vệ core integrity khi phù hợp; application services không thay thế database constraints.

---

## 15. Commands, Scheduler, Jobs and Events

Scheduled/CLI work:

```text
app/Console/Commands/
routes/console.php
```

Không tạo HTTP Cronjob Controller cho tác vụ nội bộ nếu Laravel Scheduler/Command xử lý được.

Core transactions sau phải synchronous:

```text
Open Dining Session
Create Order
Record Stock Movement
Complete Payment
```

Jobs phù hợp cho side effects:

```text
Notifications
Heavy reports
Analytics
Translation cache warming
```

Events/Listeners chỉ dùng cho side effects sau commit; không dùng để che giấu bước bắt buộc phải atomic.

---

## 16. Tests Convention

Feature Tests phản chiếu presentation context:

```text
tests/Feature/
├── Customer/
├── POS/
├── Kitchen/
└── Admin/
```

Unit Tests phản chiếu component nghiệp vụ khi cần:

```text
tests/Unit/
├── Services/
├── Models/
└── Integrations/
```

Critical test priorities:

- maximum one active Dining Session per RestaurantTable;
- Order và OrderItems được tạo atomically;
- historical price dùng `OrderItem.unit_price`;
- duplicate payment không tạo nhiều successful records;
- payment failure không đóng session;
- stock movement và current stock update nhất quán;
- AI/Weather/Translation failure không phá Core flows;
- backend authorization được enforce.

---

## 17. Naming Convention

| Type | Pattern | Example |
|---|---|---|
| Controller | `{Resource}Controller` | `OrderController` |
| Form Request | `{Action}{Resource}Request` | `CreateOrderRequest` |
| Service | `{Action}{Domain}Service` | `CompletePaymentService` |
| Query | `{Purpose}Query` | `DailyRevenueQuery` |
| Policy | `{Model}Policy` | `OrderPolicy` |
| Job | imperative business purpose | `SendReservationReminder` |
| Exception | `{Condition}Exception` | `BillAlreadyPaidException` |
| Enum | singular business concept | `PaymentStatus` |
| Blade folder | plural/kebab-case when appropriate | `dining-sessions` |
| Route name | `{context}.{resource}.{action}` | `pos.orders.store` |

PHP namespaces phải phản chiếu đường dẫn PSR-4:

```php
App\Http\Controllers\POS
App\Http\Requests\POS
App\Services\Payment
App\Integrations\Weather\Contracts
App\Queries\Reports
```

---

## 18. Decision Guide — Where Does This Code Belong?

| Concern | Location |
|---|---|
| HTTP orchestration | `Http/Controllers/{Context}` |
| Input validation | `Http/Requests/{Context}` |
| JSON representation | `Http/Resources` |
| Eloquent persistence model | `Models` |
| Business state enum | `Enums` |
| Resource authorization | `Policies` |
| Multi-step business flow | `Services/{Domain}` |
| Complex report/read query | `Queries/Reports` |
| External provider | `Integrations/{Capability}` |
| Background side effect | `Jobs` |
| Scheduled/CLI operation | `Console/Commands` |
| User notification | `Notifications` |
| Explicit business failure | `Exceptions` |
| Pure shared utility | `Support` |
| Blade UI | `resources/views/{context}` |
| Context routes | `routes/{context}.php` |

---

## 19. Structures Not Used by Default

Core Scope không mặc định sử dụng:

```text
app/Modules/
app/Domain/
app/Application/
app/Infrastructure/
Repositories/
DTOs/
Actions/
Managers/
Facades/
Traits/
```

Không mặc định tạo:

- Repository cho mỗi Model;
- Interface cho mỗi Service;
- DTO cho mỗi Request;
- Event cho mỗi state change;
- Queue cho mỗi operation;
- Action class song song với Service mà không có lý do rõ;
- folder chỉ chứa placeholder.

Một abstraction mới phải giải quyết nhu cầu thực tế, không chỉ phục vụ diagram.

---

## 20. Growth Rules

### Stage 1 — Simple feature

```text
Route → Controller → Form Request → Model → View
```

### Stage 2 — Complex business flow

```text
Route → Controller → Form Request → Service → Models
```

### Stage 3 — External provider

```text
Service → Integration Contract → Provider Adapter
```

### Stage 4 — Complex reporting

```text
ReportController → Report Query → MySQL
```

Chỉ nhóm `Services/{Domain}` khi số lượng và mức liên quan của service làm thư mục phẳng khó đọc. Không có quy tắc máy móc buộc mỗi domain phải có folder ngay từ đầu.

---

## 21. Change Control

Các thay đổi sau phải cập nhật tài liệu này và Architecture Baseline trước hoặc cùng pull request:

- thêm top-level folder mới trong `app` nhằm thể hiện một architecture layer;
- chuyển sang `app/Modules`;
- áp dụng Repository/DTO/Action pattern toàn hệ thống;
- tách Customer/POS/Kitchen/Admin thành applications riêng;
- chuyển core transaction sang queue/event-driven flow;
- tách microservice;
- thay đổi ownership của core business flow.

Thêm một class hoặc folder con phục vụ nhu cầu cụ thể không cần Architecture Decision mới nếu vẫn tuân theo convention này.

---

## 22. AI Coding Agent Rules

Trước khi tạo file, AI coding agent phải:

1. xác định use case và business rules;
2. xác định presentation context;
3. xác định domain owner;
4. phân loại CRUD đơn giản hay complex business flow;
5. kiểm tra transaction, concurrency và idempotency;
6. chọn vị trí file theo bảng Decision Guide;
7. kiểm tra code tương tự đã tồn tại để tránh duplication;
8. chỉ tạo folder cần dùng trong implementation hiện tại.

AI coding agent không được:

- tự chuyển project sang DDD/package modules;
- tạo hàng loạt placeholder folders;
- đặt business logic phức tạp trong Controller, Blade hoặc Helper;
- gọi external API trực tiếp từ Controller/Model;
- phát minh business state khác database/system baseline;
- tạo abstraction không có consumer hoặc requirement thực tế.

---

## 23. Definition of Done

Source-code organization được coi là tuân thủ khi:

- [ ] Controller/Request/View/Test đặt đúng presentation context.
- [ ] Shared business flow đặt đúng domain service.
- [ ] CRUD đơn giản không bị over-abstracted.
- [ ] Critical multi-model changes dùng explicit transaction.
- [ ] External provider được cô lập sau contract/adapter boundary.
- [ ] Model không gọi controller hoặc external provider.
- [ ] Blade không chứa database/business logic.
- [ ] Authorization được enforce ở backend.
- [ ] Không có business helper không rõ ownership.
- [ ] Không có folder/class/interface placeholder.
- [ ] Tests bao phủ business rule và failure path quan trọng.
- [ ] Không có top-level architecture mới ngoài convention.

---

## 24. Final Convention

```text
SOURCE CODE STRUCTURE CONVENTION v1.0

Architecture
└── Conventional Laravel Modular Monolith

Presentation
├── Customer
├── POS
├── Kitchen
└── Admin

Organization
├── Controllers / Requests / Views / Feature Tests → by context
├── Services                                      → by domain
├── Models                                        → app/Models
├── Integrations                                  → by external capability
└── Queries                                       → complex reads/reports only

Implementation Principle
├── Simple CRUD          → Laravel conventions
├── Complex flow         → Service + transaction
├── External integration → Contract + Adapter
└── Optional structure   → create only when required
```

**Status: FINALIZED v1.0 — REQUIRED FOR DEVELOPMENT**
