# 01. PROJECT PLANNING

## Website 89 Beer Garden tích hợp AI gợi ý thực đơn

> Tài liệu chốt toàn bộ giai đoạn Project Planning, làm baseline để
> triển khai Requirement Analysis, System Analysis, Database, UI/UX,
> Development, Testing và Deployment.

------------------------------------------------------------------------

# 01. Xác định mục tiêu & phạm vi dự án

## Tổng quan

89 Beer Garden Website là hệ thống web hỗ trợ bán hàng, đặt bàn, gọi món
và quản lý cơ bản cho nhà hàng. Hệ thống chuyển một phần quy trình phục
vụ truyền thống sang môi trường số, cho phép khách chủ động xem món,
giá, media, tình trạng món, đặt bàn và đặt món trên Mobile, Tablet hoặc
Desktop.

Hình thức phục vụ trong **Core Scope**:

-   **Ăn tại quán:** xem menu, đặt bàn, tự gọi món tại bàn khi capability
    được bật hoặc nhờ Staff gọi món, và gọi thêm món trong cùng Dining
    Session.

Các hình thức **Takeaway / Delivery** được ghi nhận là Future Scope và
không thuộc baseline triển khai Core hiện tại.

Phạm vi dự án ở mức **cơ bản đến tiệm cận nâng cao**: hoàn thiện nghiệp
vụ cốt lõi của website nhà hàng và bổ sung AI Recommendation/AI
Analytics, không phát triển thành ERP/POS chuyên sâu.

## Bối cảnh & vấn đề

Quy trình truyền thống:

`Khách đến quán → Xem menu → Chọn bàn → Gọi món → Nhân viên → Bếp → Phục vụ → Thanh toán`

Các vấn đề cần giải quyết:

-   Khách khó xem đầy đủ menu trước khi đến quán.
-   Khó biết trước giá, hình ảnh, mô tả và tình trạng món.
-   Chưa hỗ trợ tốt đặt bàn và gọi món tại bàn.
-   Chưa có luồng số thống nhất cho phục vụ tại quán.
-   Menu nhiều món khiến khách khó lựa chọn.
-   Một số món cần đặt trước hoặc có thể hết.
-   Nội dung món Core cần được truyền tải bằng text và ảnh đại diện;
    gallery/video thuộc Future Scope.
-   Bán hàng và quản lý chưa tập trung trên một hệ thống.
-   Dữ liệu kinh doanh chưa được khai thác tốt.

Định hướng chuyển đổi số tập trung vào:

**Bán hàng → Quản lý → Truyền thông**

## Mục tiêu

### Customer

-   Trang chủ và thông tin nhà hàng.
-   Trang chủ và thông tin nhà hàng ở mức nội dung tĩnh/quản trị tối
    thiểu; News/CMS/WordPress thuộc Future Scope.
-   Menu theo danh mục.
-   Chi tiết món: text, ảnh đại diện, giá, mô tả, trạng thái.
-   Search/Filter cơ bản.
-   Cart.
-   Reservation.
-   Customer self-order tại bàn khi capability được bật.
-   Dine-in.
-   Customer Account cơ bản.
-   AI Recommendation.
-   Customer chủ động Add-to-Cart từ kết quả AI; AI không tự thêm món.
-   Responsive Mobile / Tablet / Desktop.

### Multi-language

Customer Website hỗ trợ:

-   `vi` --- Tiếng Việt, **mặc định**.
-   `en` --- English.
-   `zh` --- 中文.

Khách có thể switch ngôn ngữ. Định hướng:

-   **Laravel Localization** cho text giao diện cố định.
-   Nội dung động như tên/mô tả món có thể dịch qua **Translation API**.
-   Ưu tiên Google Cloud Translation API hoặc dịch vụ tương đương.
-   Bản dịch động nên được lưu/cache để tái sử dụng.
-   Tiếng Việt là ngôn ngữ gốc và fallback.

### Operation

-   Staff tiếp nhận/xử lý Order.
-   Quản lý trạng thái Order.
-   Table/Reservation.
-   Kitchen cơ bản.
-   Cashier.
-   Payment/Invoice.
-   Theo dõi Dine-in.

### Admin

-   Menu/Category/Price/Status.
-   Table.
-   Order/Invoice.
-   Customer/Employee.
-   Voucher cơ bản.
-   Inventory/Ingredient cơ bản.
-   Dashboard/Report.
-   AI Analytics thuộc Future Scope.

## Người dùng & bên liên quan

  Nhóm                   Vai trò
  ---------------------- ----------------------------------------
  Customer               Xem menu, đặt bàn, đặt món, sử dụng AI
  Staff                  Tiếp nhận và xử lý Order
  Kitchen                Tiếp nhận/cập nhật trạng thái món
  Cashier                Thanh toán và hóa đơn
  Admin / Manager        Quản trị và theo dõi hoạt động
  Chủ nhà hàng           Theo dõi kết quả kinh doanh
  Đối tác giao hàng      Future Scope
  Third-party Services   AI, Translation API, Payment...

Phân quyền chi tiết được xử lý ở Requirement/Use Case.

## Phạm vi chức năng cấp cao

`Website → Localization → Menu → AI Recommendation → Cart → Reservation → Table → Dining Session → Order → Kitchen/Staff → Bill/Payment/Invoice → Customer → Voucher → Inventory → Employee → Reporting → Administration`

Business Flow chính:

**Dine-in**

`Menu + AI → Reservation/Table → Dining Session → Cart/Order → Kitchen/Staff → Bill → Payment → Invoice`

**Takeaway / Delivery:** Future Scope, chưa có Core Flow trong baseline
hiện tại.

## AI Scope

### AI Recommendation

-   Gợi ý món/combo/thực đơn.
-   Theo số người, ngân sách, sở thích, nhu cầu.
-   Chỉ đề xuất món thực tế có trong hệ thống.
-   AI chỉ tư vấn, không tự quyết định Order.
-   Có Add-to-Cart từ kết quả AI.

### AI Analytics (Future Scope)

-   Tổng hợp doanh thu.
-   Phân tích Order.
-   Món bán tốt/bán kém.
-   So sánh khoảng thời gian.
-   Giải thích dữ liệu bằng ngôn ngữ tự nhiên.

## Out of Scope

-   Native Mobile App.
-   Multi-branch chuyên sâu.
-   Shipper riêng.
-   ERP/Accounting chuyên sâu.
-   HRM chuyên sâu.
-   Supply Chain/Warehouse chuyên sâu.
-   CRM/Loyalty chuyên sâu.
-   AI Forecasting phức tạp.
-   BI/Data Warehouse quy mô lớn.

------------------------------------------------------------------------

# 02. Người dùng & bên liên quan

Nội dung này đã được **gộp vào Task 01** để tránh lặp. Requirement
Analysis sẽ phân tích sâu Actor, nhu cầu, quyền hạn, User Flow và Use
Case.

------------------------------------------------------------------------

# 03. Xác định công nghệ & công cụ

  Thành phần            Công nghệ / Công cụ
  --------------------- -------------------------------------------------
  Backend               PHP 8.4 + Laravel 12
  Frontend              Blade + HTML5 + CSS3 + JavaScript + Bootstrap 5
  Database              MySQL 8.x + Eloquent ORM
  AI                    Google Gemini API
  Localization          Laravel Localization
  Dynamic Translation   Translation API
  UI/UX                 Figma + AI Design
  System Design         Draw.io
  Project Management    ClickUp + Google Drive
  Version Control       Git
  Repository            GitHub

## Backend

**PHP 8.4 + Laravel 12** xử lý Authentication, Authorization, Business
Logic, Menu, Cart, Reservation, Order, Payment, Admin, API, Gemini và
Translation Integration.

## Frontend

**Blade + HTML5 + CSS3 + JavaScript + Bootstrap 5**.

Không tách React/Vue trong phạm vi hiện tại. JavaScript/AJAX/Fetch xử lý
tương tác động. Responsive theo:

`Mobile → Tablet → Desktop`

Node.js/npm/Vite có thể dùng như **build tools** khi setup Development
Environment.

## Database

**MySQL 8.x + Eloquent ORM**, sử dụng Migration, Seeder và Factory.
Schema chi tiết được thiết kế ở Database Design.

## AI

**Google Gemini API**

`Laravel → Prepare Data → Gemini API → Validate/Process → Website`

Không huấn luyện LLM riêng.

## Localization

-   Laravel Localization: UI cố định.
-   `vi` mặc định, `en`, `zh`.
-   Translation API: nội dung động.
-   Cache/store translation.
-   Vietnamese fallback.

## Kiến trúc tổng quát

`Browser → Blade/Bootstrap/JavaScript → Laravel 12/PHP 8.4 → Eloquent → MySQL`

Laravel kết nối thêm:

`Gemini API / Translation API / External Services`

------------------------------------------------------------------------

# 04. Thiết lập môi trường quản lý dự án

## Công cụ

  Công cụ             Mục đích
  ------------------- ----------------------
  ClickUp             Task, tiến độ, phase
  Google Drive        Tài liệu/tài nguyên
  GitHub / Local      Source code/version
  Figma / AI Design   UI/UX/prototype
  Draw.io             Diagram

## ClickUp

-   Phase:
    `Planning → Requirements → Analysis & Design → Development → Testing → Deployment`
-   Status: `TO DO → IN PROGRESS → REVIEW → DONE`
-   Thêm `BLOCKED` khi cần.
-   Tên task tiếng Việt, ngắn gọn, bắt đầu bằng hành động.

## Google Drive

``` text
01_Project_Planning
02_Requirements
03_Analysis_Design
04_Development
05_Testing
06_Deployment
07_Project_Documentation
```

Tên tài liệu rõ nghĩa:

`Project_Scope_Objectives`, `Functional_Requirements`,
`Use_Case_Diagram`, `Database_ERD`, `Test_Cases`, `Deployment_Guide`

Version: `Database_ERD_v1 → Database_ERD_v2`.

## Source Convention

**PascalCase:** `Order`, `MenuItem`, `OrderController`

**camelCase:** `createOrder()`, `customerName`

**snake_case:** `order_items`, `table_reservations`

**kebab-case URL:** `/menu-items`, `/table-reservations`

Commit:

``` text
feat: add table reservation
fix: fix order total calculation
docs: update database ERD
refactor: update recommendation service
```

Không commit `.env` hoặc dữ liệu nhạy cảm.

## Design Convention

Figma chia theo Customer / Staff / Kitchen / Admin. AI Design dùng làm
concept/tham khảo. Draw.io dùng cho Use Case, ERD, Flow, Activity và
Sequence.

**ClickUp là trung tâm quản lý**, liên kết tới Drive, Figma, Draw.io và
GitHub.

------------------------------------------------------------------------

# 05. Lập các giai đoạn và mốc dự án

  Phase                       Milestone
  --------------------------- ----------------------------------
  Project Planning            M1 -- Project Planning Completed
  Requirement Analysis        M2 -- Requirements Completed
  System & Database Design    M3 -- System Design Completed
  UI/UX Design                M4 -- UI/UX Completed
  Development                 M5 -- Feature Complete
  Testing & QA                M6 -- Testing Completed
  Deployment & Finalization   M7 -- Production Ready

Roadmap:

`Planning → Requirements → System & Database Design → UI/UX → Development → Testing → Deployment`

UI/UX, Development và Testing có thể overlap hợp lý.

------------------------------------------------------------------------

# 06. Xác định rủi ro chính

  -------------------------------------------------------------------------
  Rủi ro                  Mức độ                  Hướng xử lý
  ----------------------- ----------------------- -------------------------
  Scope quá lớn           Cao                     Core / Advanced / Future
                                                  Scope

  Thời gian giới hạn      Cao                     Module hóa, ưu tiên Core
                                                  Flow

  Requirement thay đổi    Trung bình              Baseline + Impact
                                                  Analysis

  Nghiệp vụ nhà hàng phức Trung bình              Phân tích Core Flow trước
  tạp                                             Database

  Database chưa phù hợp   Cao                     Chốt
                                                  Entity/ERD/Relationship
                                                  trước code phụ thuộc

  AI Recommendation sai   Trung bình              Chỉ dùng món thực tế +
                                                  validate output

  Gemini API lỗi/quota    Trung bình              AI không là dependency
                                                  của Core

  Dịch tự động chưa chính Trung bình              Store/cache và kiểm soát
  xác                                             bản dịch

  Translation API         Thấp/Trung bình         Vietnamese fallback
  lỗi/quota                                       

  Lỗi tích hợp module     Trung bình              End-to-end Flow Testing

  Responsive kém          Trung bình              Test
                                                  Mobile/Tablet/Desktop

  Security/Permission     Cao                     Auth, Authorization,
                                                  Validation, Role Test

  Mất source/data         Thấp                    Git/GitHub + Backup

  Deployment lỗi          Trung bình              Production Checklist
  -------------------------------------------------------------------------

## Core Scope

**Customer:** Home, Menu, Menu Detail, Search/Filter, session Cart,
Reservation, Dine-in self-order khi được bật, Account cơ bản tùy chọn,
Multi-language cơ bản.

**Operation:** Order, Order Status, Table/Reservation, Staff, Kitchen,
Cashier, Payment/Invoice.

**Admin:** Dashboard cơ bản, Menu, Category, Table, Order, Customer,
Employee, Role/Permission, Report cơ bản.

**System:** Authentication, Database, Responsive, Validation, Security
cơ bản, Deployment.

## Advanced Scope

-   Gemini AI Recommendation.
-   Customer-initiated AI Add-to-Cart.
-   Promotion/Voucher.
-   Inventory cơ bản.
-   Media gallery/video nâng cao.
-   Dashboard/Chart nâng cao.
-   Translation automation/cache nâng cao.

## Future Scope

-   Mobile App.
-   Multi-branch.
-   Shipper riêng / Real-time tracking.
-   Loyalty/CRM nâng cao.
-   ERP/Accounting/HRM.
-   Warehouse/Supply Chain chuyên sâu.
-   AI Forecasting/Personalization nâng cao.
-   BI/Data Warehouse.
-   Restaurant Chain Management.
-   Takeaway / Delivery ordering và fulfillment.
-   News/CMS/WordPress.
-   AI Analytics.

Nguyên tắc:

**Core Business Flow → Stability → AI/Advanced → Future**

Nếu chậm tiến độ: giữ Core → giảm Advanced → loại chức năng phụ → chuyển
Future.

------------------------------------------------------------------------

# 07. Review & chốt kế hoạch dự án

## Review Checklist

-   [x] Project Objectives & Scope
-   [x] User & Stakeholder
-   [x] Core / Advanced / Future Scope
-   [x] Technology Stack
-   [x] Multi-language & Translation Direction
-   [x] Project Management Environment
-   [x] Roadmap & Milestones
-   [x] Risk Strategy

## Project Baseline

Các nội dung sau được chốt làm baseline:

-   Project Objectives.
-   Project Scope.
-   User/Stakeholder Groups.
-   Core / Advanced / Future Scope.
-   Technology Stack.
-   Multi-language: `vi` default / `en` / `zh`.
-   Laravel Localization + Translation API direction.
-   Project Management Environment.
-   Roadmap & Milestones.
-   Risk Strategy.

Thay đổi lớn ảnh hưởng tới **Scope, Business Flow, Database,
Architecture hoặc Timeline** phải được đánh giá impact trước khi áp
dụng.

## Kết luận

**PROJECT PLANNING: APPROVED / COMPLETED**

**Milestone:** `M1 – Project Planning Completed`

**Next Phase:** `02. Requirement Analysis`

------------------------------------------------------------------------

# Project Planning Baseline Summary

``` text
PROJECT: 89 Beer Garden Website
BACKEND: PHP 8.4 + Laravel 12
FRONTEND: Blade + Bootstrap 5 + JavaScript
DATABASE: MySQL 8.x
AI: Google Gemini API
LANGUAGES: Vietnamese (Default) / English / Chinese
LOCALIZATION: Laravel Localization + Translation API
MANAGEMENT: ClickUp + Google Drive
DESIGN: Figma + AI Design + Draw.io
VERSION CONTROL: Git + GitHub

PRIORITY:
Core Business Flow
→ Stability
→ AI / Advanced Features
→ Future Scope
```

> **Planning Rule:** Project Planning xác định làm gì, tại sao làm, dùng
> công nghệ gì và triển khai theo hướng nào. Chi tiết nghiệp vụ, Use
> Case, Database Schema, UI và implementation sẽ được xác định trong các
> tài liệu tiếp theo.
