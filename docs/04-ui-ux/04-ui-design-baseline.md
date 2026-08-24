# 04. UI/UX Design — UI Design Baseline

**Project:** 89 Beer Garden Website & Management System  
**Document:** UI Design Baseline  
**Version:** 1.0  
**Status:** Finalized / Ready for Development Handoff

---

## 1. Purpose

Tài liệu này chốt **UI Design Baseline** cho 89 Beer Garden dựa trên UX & User Flow Baseline.

Mục tiêu là cung cấp cho UI Designer, Developer hoặc AI coding agent một nguồn tham chiếu thống nhất về:

- UI direction;
- design system foundations;
- reusable components;
- wireframe/layout patterns;
- high-fidelity direction;
- responsive strategy;
- UI states;
- visual consistency;
- accessibility baseline;
- handoff rules cho Development.

Tài liệu này **không phải danh sách các task UI**. Đây là kết quả hợp nhất cuối cùng của phần UI Design.

---

# 2. UI Direction

Visual direction:

> **Modern Beer Garden — Warm · Modern · Casual · Social · Food-focused**

Customer Website phải tạo cảm giác:

```text
Warm
Inviting
Food / Drink Focused
Modern
Casual
Easy to Explore
```

Không hướng tới:

```text
Fine Dining Luxury
Nightclub / Very Dark Pub
Generic SaaS Dashboard
Over-decorated UI
```

POS và Admin sử dụng cùng Design System nhưng giảm decorative visual để ưu tiên usability.

---

# 3. One Design System — Four Contexts

```text
             89 DESIGN SYSTEM
                    │
    ┌───────────┬───────────┬───────────┐
    ↓           ↓           ↓           ↓
Customer       POS       Kitchen      Admin
```

Customer:

```text
Visual
Branding
Food
Clear CTA
```

POS:

```text
Speed
Status Visibility
Large Actions
Touch Friendliness
```

Kitchen:

```text
Item-level Queue
Fast State Transition
Note / Time Visibility
Touch Friendliness
```

Admin:

```text
Data Density
Search / Filter
Predictable Layout
Management Efficiency
```

Không xây ba Design System độc lập.

---

# 4. Color System

Primary direction:

> **Beer Gold / Amber**

Baseline reference:

```text
Primary 50     #FFF8E1
Primary 100    #FFECB3
Primary 500    #F59E0B
Primary 600    #D97706
Primary 700    #B45309
```

Neutral baseline:

```text
Background       #FAFAF9
Surface          #FFFFFF

Text Primary     #1C1917
Text Secondary   #78716C

Border           #E7E5E4
```

Primary dùng cho:

```text
CTA
Selected State
Active Navigation
Brand Accent
Important Highlight
```

Không phủ Primary lên toàn bộ UI.

---

# 5. Semantic Colors

Semantic baseline:

```text
Success  → Green
Warning  → Amber
Danger   → Red
Info     → Blue
Neutral  → Gray
```

Table:

```text
Available    → Success
Occupied     → Warning / Primary
Reserved     → Info
Cleaning     → Neutral
Inactive     → Muted / Disabled configuration state
```

Order Item:

```text
Waiting     → Info
Preparing   → Warning
Ready       → Success
Served      → Neutral
Cancelled   → Danger
```

Payment:

```text
Paid / Success  → Success
Pending         → Warning
Failed          → Danger
```

Semantic meaning phải nhất quán toàn hệ thống.

---

# 6. Typography

Hệ thống hỗ trợ:

```text
Vietnamese
English
Chinese
```

Font phải hỗ trợ multilingual tốt.

Baseline:

> `Inter`, `Noto Sans` hoặc sans-serif multilingual tương đương.

Hierarchy:

```text
Display
H1
H2
H3
Body Large
Body
Body Small
Caption
```

Usage:

| Style | Primary Use |
|---|---|
| Display | Customer Hero |
| H1 | Page Title |
| H2 | Section Title |
| H3 | Card / Panel Heading |
| Body | Main Content |
| Body Small | Supporting Content |
| Caption | Metadata / Time / Status |

POS không dùng text quá nhỏ.

---

# 7. Spacing System

Base spacing:

```text
4
8
12
16
24
32
48
64
```

Guideline:

```text
4px    micro spacing
8px    icon ↔ text
12px   compact component
16px   default spacing
24px   component group
32px   section
48px+  page / large section
```

Không sinh spacing ngẫu nhiên trên từng screen.

---

# 8. Radius & Shadow

Radius:

```text
Small   6px
Medium  10px
Large   16px
Round   9999px
```

Typical use:

```text
Input        → Small / Medium
Button       → Medium
Card         → Medium / Large
Product Card → Large
Badge        → Round
```

Shadow baseline:

```text
Shadow Small
Shadow Medium
Shadow Overlay
```

Admin ưu tiên border hơn shadow.

---

# 9. Core Components

Foundation components:

```text
Button
Input
Textarea
Select
Checkbox
Radio
Switch

Badge
Alert
Toast

Modal
Dropdown
Tabs
Tooltip

Pagination
Breadcrumb
```

Không tạo component chưa có nhu cầu thực tế.

---

# 10. Domain Components

## Customer

```text
ProductCard
CategoryTabs
CartItem
RecommendationCard
ReservationForm
```

## POS

```text
TableCard
DiningSessionCard
OrderCard
OrderItem
OrderItemStatusBadge
POSProductCard
BillSummary
PaymentMethod
```

## Kitchen

```text
KitchenQueueColumn
KitchenItemCard
ItemStatusBadge
ElapsedTime
ItemNote
```

## Admin

```text
DataTable
FilterBar
StatusBadge
MetricCard
FormSection
```

Same domain object phải giữ cùng semantic logic giữa các context.

---

# 11. Button Hierarchy

Primary:

```text
Đặt bàn
Thêm món
Xác nhận Order
Thanh toán
Lưu
```

Secondary:

```text
Chỉnh sửa
Xem chi tiết
Quay lại
```

Ghost:

```text
Close
More
Low-emphasis Cancel
```

Danger:

```text
Hủy Order
Xóa
Hủy Reservation
```

Rule:

> Một vùng UI không nên có nhiều Primary Actions cạnh tranh nhau.

---

# 12. Form Pattern

Baseline:

```text
Label
[ Input ]
Helper / Validation Message
```

Common states:

```text
Default
Hover
Focus
Filled
Disabled
Error
Success
```

Validation ưu tiên inline.

Error phải gắn với field/context cụ thể khi có thể.

---

# 13. Status & Feedback Pattern

Status phải dùng:

```text
Color + Text
```

không chỉ dùng màu.

Ví dụ:

```text
● Available
● Processing
● Paid
```

Action feedback:

```text
Action
 ↓
Loading / Processing
 ↓
Success / Error
```

Tools:

```text
Inline Message
Toast
Alert
Modal
Result State
```

tùy mức độ.

---

# 14. Customer UI Pattern

Customer Website ưu tiên:

```text
Food imagery
Whitespace
Clear CTA
Simple navigation
Mobile usability
```

Page structure phổ biến:

```text
Header
 ↓
Hero
 ↓
Featured / Recommendation
 ↓
Menu / Categories
 ↓
Reservation CTA
 ↓
Footer
```

Product là visual focus.

---

# 15. Customer Home Baseline

Layout:

```text
Header
├── Logo
├── Menu
├── Reservation
├── Cart
└── Language

Hero
├── Brand Message
├── View Menu
└── Make Reservation

Featured Products

Recommendation [optional]

Reservation CTA

Footer
```

Primary actions:

```text
Xem thực đơn
Đặt bàn
```

Recommendation section có thể ẩn/fallback nếu AI không khả dụng.

---

# 16. Menu Baseline

```text
Page Title
Category Tabs
Search
Product Grid
Cart Indicator
Recommendation [optional]
```

Category không tạo page riêng.

Product Grid reuse `ProductCard`.

`RecommendationCard` có thể cung cấp `Add to Cart`, nhưng chỉ sau click
của Customer và phải dùng cùng availability/Cart validation như Menu.

Desktop:

```text
3–4 columns
```

Tablet:

```text
2–3 columns
```

Mobile:

```text
1–2 columns
```

Exact columns có thể điều chỉnh theo viewport.

---

# 17. Product Card Baseline

Priority:

```text
Image
 ↓
Product Name
 ↓
Price
 ↓
Action
```

Không nhồi metadata không cần thiết.

Card phải giữ:

```text
Strong Product Image
Readable Name
Clear Price
Easy Add Action
```

---

# 18. Product Detail Baseline

Desktop:

```text
Image | Product Information
```

Information hierarchy:

```text
Product Name
Price
Description
Quantity
Primary CTA
Related / Suggested Products
```

Mobile:

```text
Image
 ↓
Name
 ↓
Price
 ↓
Description
 ↓
Quantity
 ↓
Add to Cart
```

---

# 19. Cart & Submit Dine-in Order Baseline

Cart:

```text
Item
Quantity
Price
Remove
Subtotal
Continue / Submit Order
```

Empty Cart là state:

```text
Giỏ hàng đang trống
[ Xem thực đơn ]
```

Submit desktop có thể dùng:

```text
Dining Session Context | Order Summary
```

Mobile:

```text
Dining Session Context
      ↓
Order Summary
      ↓
Total
      ↓
Confirm
```

Core không hiển thị delivery address, shipping method hoặc takeaway
pickup fields. Những field đó thuộc Future Scope.

Cart phải được giữ khi validation/submit thất bại và chỉ clear sau khi
Order commit thành công.

---

# 20. Reservation UI Baseline

Form tập trung vào:

```text
Name
Phone
Date
Time
Party Size
Note
Submit
```

Desktop có thể đặt:

```text
Date | Time
```

Mobile stack:

```text
Date
 ↓
Time
```

Sau submit phải phản ánh:

```text
Pending
```

nếu chưa được Staff confirm.

---

# 21. POS UI Direction

POS priority:

> **Fast · Clear · Touch-Friendly · Operational**

Tránh:

```text
Small Buttons
Decorative Animations
Heavy Shadows
Modal Stacking
Tiny Text
```

POS phải giúp Staff scan và thao tác nhanh.

---

# 22. Table Map Baseline

TableCard hiển thị tối thiểu:

```text
Table Number
Status
Current Session / Reservation Context
Optional Timing
```

Grid phải cho phép Staff nhìn trạng thái nhiều bàn cùng lúc.

TableCard là clickable/touchable target lớn.

---

# 23. Dining Session UI Baseline

Hierarchy bắt buộc:

```text
Table
 ↓
Session
 ↓
Orders
 ↓
Items
```

Screen phải thể hiện rõ:

```text
Table Identity
Session Status
Session Start Time
Order Groups
Order Status
Add Order
Billing
```

Primary actions:

```text
+ Gọi thêm món
Thanh toán
```

Không làm hai action này bị chìm trong secondary actions.

---

# 24. POS Ordering Baseline

Preferred desktop/tablet layout:

```text
Categories | Products | Current Order
```

Current Order phải luôn dễ truy cập.

Required behaviors:

```text
Search
Category Select
Product Add
Quantity Change
Remove Item
Note
Total
Confirm Order
```

Click Product nên ưu tiên:

```text
1 click → add
```

nếu Product không có customization bắt buộc.

---

# 25. Orders Board Baseline

Status grouping:

```text
Waiting
Preparing
Ready
Served
```

Kitchen/OrderItem Card:

```text
Order ID
Table
Product / Quantity / Note
Time
Item Status
Action
```

UI phải hỗ trợ scan nhanh.

POS Orders Board có thể hiển thị aggregate summary, nhưng state/action
phải ở Order Item level. Kitchen dùng Kitchen Queue riêng với cùng item
components. Không xây Kitchen Display System phức tạp hơn requirement.

---

# 26. Billing / Payment UI Baseline

Hierarchy:

```text
Orders
 ↓
Subtotal
 ↓
Discount
 ↓
Total
 ↓
Payment Method
 ↓
Confirm Payment
```

`TOTAL` phải là financial value nổi bật nhất.

Payment processing:

```text
Ready
 ↓
Processing
 ↓
Success / Failed
```

Failure UI phải cho phép:

```text
Retry
Change Payment Method
```

và không đóng Dining Session.

---

# 27. Admin UI Pattern

Admin priority:

```text
Data
Search
Filter
Action
Predictability
```

Base shell:

```text
Sidebar
 ↓
Page Header
 ↓
Search / Filter / Primary Action
 ↓
Content / Data Table
 ↓
Pagination
```

Không cần mỗi module một visual language khác.

---

# 28. Admin Dashboard Baseline

Dashboard gồm:

```text
Page Title
KPI Cards
Main Chart
Recent Data
Date Filter [if required]
```

Chỉ hiển thị KPI/report có giá trị trong requirement.

Không thêm chart chỉ để lấp đầy dashboard.

---

# 29. CRUD List Pattern

```text
Page Header
 ↓
Search / Filter / Primary Action
 ↓
Data Table
 ↓
Pagination
```

Applicable:

```text
Products
Categories
Tables
Reservations
Customers
Employees
Vouchers
Inventory
Roles / Permissions [Admin only]
Translations [Admin only]
System Settings [Admin only]
```

Secondary actions có thể nằm trong `More` menu khi cần tiết kiệm không gian.

---

# 30. Create / Edit Form Pattern

Có thể chia form:

```text
Basic Information
Pricing
Media
Status
```

Actions:

```text
Cancel
Save
```

Save là Primary.

Create và Edit dùng chung pattern khi cấu trúc gần giống nhau.

---

# 31. Detail Pattern

Baseline:

```text
Header
Status
Key Information
Related Data
Activity / History [if useful]
Actions
```

Không biến Detail Screen thành dashboard riêng nếu không cần.

---

# 32. Reports UI Baseline

```text
Date / Filter
 ↓
KPI Summary
 ↓
Primary Chart
 ↓
Supporting Table / Top Products
```

Report UI bám dữ liệu có thật trong requirement/database.

---

# 33. Shared Patterns

## Confirmation Modal

```text
Title
Message
Cancel
Confirm
```

Destructive action phải có warning rõ.

## Empty State

```text
No Data
 ↓
Explanation
 ↓
Primary Action [if useful]
```

## Error State

```text
Unable to load data
[ Retry ]
```

## Loading State

Ưu tiên skeleton cho content-heavy area và spinner cho action ngắn.

---

# 34. Responsive Strategy

| Context | Strategy |
|---|---|
| Customer | Mobile-first |
| POS | Tablet / Desktop-first |
| Kitchen | Tablet / Desktop-first |
| Admin | Desktop-first |

Baseline breakpoints guideline:

```text
Mobile   < 768px
Tablet   768–1023px
Desktop  ≥ 1024px
```

Không coi breakpoint là business rule cứng.

---

# 35. Customer Responsive Rules

Header desktop:

```text
Logo | Menu | Reservation | Cart | Language
```

Mobile:

```text
Logo        Cart   Menu
```

Secondary navigation có thể chuyển vào menu drawer.

Product Grid:

```text
Desktop → 3–4
Tablet  → 2–3
Mobile  → 1–2
```

Submit Order mobile phải stack thay vì ép layout hai cột.

---

# 36. POS Responsive Rules

Primary devices:

```text
Tablet
Desktop
```

Table Map phải giữ touch target đủ lớn.

Dining Session tablet có thể:

```text
Session
 ↓
Orders
 ↓
Sticky Primary Actions
```

POS Ordering trên viewport nhỏ hơn có thể chuyển Current Order thành drawer/panel nhưng không được làm Staff mất context quá lâu.

---

# 37. Admin Responsive Rules

Admin ưu tiên:

```text
Desktop
Tablet
```

Tablet có thể collapse sidebar.

Mobile chỉ cần usable cơ bản nếu requirement không yêu cầu mobile administration đầy đủ.

DataTable có thể:

```text
Horizontal Scroll
```

hoặc simplified cards khi thật sự cần.

---

# 38. Touch Target

Customer Mobile và POS Tablet phải có hit area đủ lớn cho:

```text
Primary CTA
Table Card
Category
Quantity + / -
Confirm Order
Payment
```

Không để visual icon nhỏ đồng nghĩa touch area nhỏ.

---

# 39. UI State Baseline

Common screen states:

```text
Loading
Data / Success
Empty
Error
Disabled
Submitting / Processing
```

Common component states:

Button:

```text
Default
Hover
Focus
Disabled
Loading
```

Input:

```text
Default
Focus
Filled
Error
Disabled
```

---

# 40. Product States

```text
Available
Unavailable
Loading
Error
```

Unavailable Product phải disable/remove Add action rõ ràng.

---

# 41. Table States

```text
Available
Occupied
Reserved
Cleaning
```

`Inactive` là configuration/disabled state riêng, không thay thế
`Cleaning`.

Không dựa vào màu duy nhất; luôn có text/icon/label hỗ trợ.

---

# 42. Order Item States

```text
Waiting
Preparing
Ready
Served
Cancelled
```

State styling phải nhất quán giữa:

```text
Dining Session
Orders Board
Admin Order Detail
```

---

# 43. Reservation States

```text
Form
Submitting
Pending
Confirmed
Checked-in
Completed
No-show
Rejected
Cancelled
Error
```

`Pending` phải phân biệt rõ với `Confirmed`.

---

# 44. Payment States

```text
Ready
Processing
Success
Failed
```

Flow:

```text
Confirm Payment
      ↓
Processing
      ↓
Success?
├── No  → Failed → Retry
└── Yes → Paid
```

Nếu Failed:

```text
DiningSession remains active
Table remains occupied
```

UI phải phản ánh đúng.

---

# 45. Loading Strategy

Không block toàn app nếu chỉ một phần dữ liệu đang load.

Use skeleton cho:

```text
Product Lists
Dashboard Cards
Data Tables
Order Cards
```

Use spinner/loading button cho:

```text
Submit
Confirm Order
Payment
Short Action
```

---

# 46. Empty State Strategy

Empty state cần trả lời:

```text
What is missing?
Why?
What can user do next?
```

Examples:

Cart:

```text
Giỏ hàng đang trống
[ Xem thực đơn ]
```

Products:

```text
Chưa có sản phẩm
[ + Tạo sản phẩm ]
```

Search:

```text
Không tìm thấy kết quả
[ Xóa bộ lọc ]
```

---

# 47. Error Recovery Strategy

Field error:

```text
Field
Validation Message
```

Data error:

```text
Không thể tải dữ liệu
[ Thử lại ]
```

Submit failure:

```text
Error
 ↓
Keep User Input
 ↓
Retry
```

POS failure:

```text
Error
 ↓
Keep Current Order / Current Context
 ↓
Retry
```

Không reset data người dùng vừa nhập chỉ vì request thất bại.

---

# 48. Button Processing & Double Submit

Transactional buttons phải chuyển:

```text
Default
 ↓
Loading
 ↓
Disabled until response
```

Applicable:

```text
Reservation Submit
Order Confirm
Payment Confirm
Save Form
Stock Movement Save
```

Mục tiêu là tránh accidental double-submit ở UI; backend vẫn phải có duplicate protection riêng.

---

# 49. Multilingual UI Rules

Locales:

```text
VI — Default
EN
ZH
```

Components không được hard-code width theo text tiếng Việt.

Ưu tiên:

```text
Flexible width
Padding-inline
Min-width when needed
Text wrapping rules
```

Language switch phải giữ:

```text
Current Screen
Current Entity
Current Navigation Context
```

---

# 50. Icon Strategy

Dùng một icon library/style nhất quán.

Không trộn:

```text
Outline
Filled
Emoji
Multiple unrelated icon sets
```

Action quan trọng/destructive nên có text hoặc tooltip rõ nghĩa.

---

# 51. Image Direction

Customer images:

```text
Bright
Appetizing
Consistent Tone
Appropriate Crop
Food / Drink Focus
```

POS/Admin hạn chế decorative images.

Product image vẫn dùng khi có giá trị nhận diện.

---

# 52. Accessibility Baseline

Minimum:

```text
Readable contrast
Visible focus
Form labels
Error not represented by color only
Status includes text
Reasonable font size
Touch targets large enough
Important icons have label/tooltip
```

Không biến scope thành full accessibility audit, nhưng không bỏ qua usability cơ bản.

---

# 53. Design Tokens

Tư duy:

```text
Design System
    ↓
Design Tokens
    ↓
Components
    ↓
Patterns
    ↓
Screens
```

Examples:

```text
color-primary
color-success
color-danger

text-primary
text-secondary

space-1
space-2
space-4

radius-sm
radius-md
radius-lg
```

Developer/AI không nên hard-code random visual values ở từng page nếu token đã tồn tại.

---

# 54. Figma Structure Guideline

```text
89 Beer Garden UI

00. Cover
01. Foundations
02. Components
03. Customer
04. POS
05. Admin
06. Prototype
```

Không cần một Figma Page cho từng screen.

---

# 55. Component Naming Guideline

Examples:

```text
Button/Primary/Default
Button/Primary/Hover
Button/Primary/Disabled

Badge/Success
Badge/Warning
Badge/Danger

TableCard/Available
TableCard/Occupied
TableCard/Reserved
```

Naming phải hỗ trợ handoff rõ ràng.

---

# 56. High-Fidelity Priority

Không cần thiết kế mọi possible screen thành Hi-Fi độc lập.

Core Hi-Fi targets:

Customer:

```text
Home
Menu
Product Detail
Cart
Submit Dine-in Order
Reservation
```

POS:

```text
Table Map
Dining Session
POS Ordering
Orders Board
Billing / Payment
```

Kitchen:

```text
Kitchen Queue
Item Detail / Transition
```

Admin:

```text
Dashboard
CRUD List
Create / Edit
Detail
Reports
```

Các screen khác reuse component/pattern.

---

# 57. Prototype Priority

Prototype chỉ cần tập trung flow có interaction quan trọng:

```text
Customer Ordering
Reservation
Dining Session
Add Order
Billing / Payment
```

CRUD Admin đơn giản không cần prototype đầy đủ.

---

# 58. Design Consistency Rules

## UI-RULE-01 — Same Action, Same Hierarchy

Save/Confirm/Delete/Cancel phải giữ consistent style.

## UI-RULE-02 — Same Status, Same Semantic

Paid, Processing, Cancelled... dùng semantic nhất quán.

## UI-RULE-03 — Same Domain Object, Same Meaning

OrderCard có thể khác layout giữa POS/Admin nhưng status và terminology không thay đổi tùy ý.

## UI-RULE-04 — Customer Is Visual

Food/Drink và CTA là ưu tiên.

## UI-RULE-05 — POS Is Operational

Speed và status visibility quan trọng hơn decoration.

## UI-RULE-06 — Admin Is Predictable

Search/filter/data/action được ưu tiên.

## UI-RULE-07 — Payment Failure Must Remain Recoverable

UI không đóng context sau failure.

## UI-RULE-08 — AI Failure Must Not Break Menu

Recommendation section có thể fallback/ẩn.

## UI-RULE-09 — Language Switch Keeps Context

Không đưa user về Home.

## UI-RULE-10 — Preserve Input on Error

Không reset form/current order nếu không cần.

---

# 59. UI Development Handoff

Developer/AI cần nhận được:

```text
Screen Name
Layout Pattern
Components
Primary Action
State Variants
Navigation Behavior
Responsive Intent
Semantic Status
```

UI Baseline không yêu cầu annotate từng pixel nếu Design System/tokens đã rõ.

---

# 60. Rules Development Must Not Reinterpret

Development không được tự thay đổi:

```text
Table = Dining Session context, not Order
```

Không:

```text
Table = Order
```

Không:

```text
Order Completed = Paid
```

Không:

```text
Reservation Submitted = Confirmed
```

Không:

```text
AI Recommendation required for Menu
```

Không:

```text
Payment Failed = Close Session
```

Visual implementation phải giữ nguyên business semantics từ UX/System Analysis.

---

# 61. UI Boundaries

UI Baseline quyết định:

```text
Visual Direction
Design Tokens
Component Hierarchy
Layout Patterns
Responsive Intent
UI States
Feedback Behavior
Accessibility Baseline
```

UI Baseline không quyết định:

```text
Database Tables
API Endpoint
Laravel Services
SQL Transaction
External Provider SDK
Deployment Infrastructure
```

---

# 62. Final UI Decisions

## UI-FINAL-01 — Design Direction

```text
Modern Beer Garden
Warm · Modern · Casual · Social · Food-focused
```

## UI-FINAL-02 — One Shared Design System

Customer, POS, Kitchen và Admin dùng chung foundations/components.

## UI-FINAL-03 — Customer Is Mobile-First

Customer screens phải ưu tiên mobile usability.

## UI-FINAL-04 — POS Is Tablet/Desktop Operational UI

Touch-friendly và status clarity là ưu tiên.

## UI-FINAL-05 — Admin Is Desktop-First

Predictable data-management patterns được reuse.

## UI-FINAL-06 — UI State Is Part of Design

Không chỉ thiết kế happy-path screenshots.

## UI-FINAL-07 — Semantic Status Is Shared

Status meaning không thay đổi giữa context.

## UI-FINAL-08 — Error Must Preserve Context

Form/order context được giữ khi có thể.

## UI-FINAL-09 — Multilingual Layout Must Be Flexible

Không hard-code theo độ dài text một locale.

## UI-FINAL-10 — Design System Supports Coding

Token/component naming phải đủ rõ để AI/developer triển khai nhất quán.

---

# 63. Definition of Done

UI Design được coi là hoàn tất khi:

- [x] UI direction finalized.
- [x] Color/semantic system finalized.
- [x] Typography baseline finalized.
- [x] Spacing/radius baseline finalized.
- [x] Core components finalized.
- [x] Domain components identified.
- [x] Customer patterns finalized.
- [x] POS patterns finalized.
- [x] Kitchen item-level queue patterns finalized.
- [x] Admin patterns finalized.
- [x] Core wireframe/layout patterns finalized.
- [x] High-Fidelity direction finalized.
- [x] Responsive strategy finalized.
- [x] UI state matrix finalized.
- [x] Payment failure UI behavior finalized.
- [x] Reservation status UI behavior finalized.
- [x] Multilingual behavior finalized.
- [x] Recommendation fallback UI finalized.
- [x] Accessibility baseline finalized.
- [x] Design/Development handoff rules finalized.
- [x] Ready for Development.

---

# 64. Final Baseline

```text
UI DESIGN BASELINE v1.0

Design Direction
└── Modern Beer Garden
    ├── Warm
    ├── Modern
    ├── Casual
    ├── Social
    └── Food-focused

Design System
├── Colors
├── Typography
├── Spacing
├── Radius
├── Shadows
├── Icons
├── Core Components
└── Domain Components

Customer
├── Home
├── Menu
├── Product Detail
├── Cart
├── Submit Dine-in Order
└── Reservation
Strategy: Mobile-first

POS
├── Table Map
├── Dining Session
├── POS Ordering
├── Orders Board
└── Billing / Payment
Strategy: Tablet/Desktop-first

Kitchen
├── Kitchen Queue
└── Item Detail / Transition
Strategy: Tablet/Desktop-first

Admin
├── Dashboard
├── CRUD List
├── Create/Edit
├── Detail
└── Reports
Strategy: Desktop-first


CORE UI SEMANTIC MODEL

Table
  ↓
Dining Session
  ↓
Orders
  ↓
Billing
  ↓
Payment

States and feedback must preserve this model.
```

**Status: BASELINE v1.0 — Ready for Development Handoff**
