# Phase 4 Closeout and Phase 5 Handoff

## 1. Purpose

This checkpoint records the implemented Phase 4 backend and functional UI, the
remaining configuration and deferred scope, and the constraints for Phase 5.
It was produced from the routes, controllers, services, models, migrations and
tests in the worktree, not from README status alone.

## 2. Phase 4 completion status

**Decision:** Phase 4 Core Development is eligible for closeout. No Core P0
blocker or conflict with the approved baseline was found during this audit.

- Core completed: the dine-in customer, POS, Kitchen and Admin workflows below.
- Complete but requires configuration: real Google Cloud Translation calls.
- Deferred optional/future scope: Recommendation/Weather, delivery, takeaway,
  online payment gateway, Recipe/BOM and advanced inventory.
- Phase 5 work: redesign and visually validate the existing functional UI; it
  must not create or reinterpret business workflows.

Automated verification passing here is a Development checkpoint. It does not
mean the separate Testing phase is complete.

## 3. Module implementation matrix

Routes are named route groups unless an exact endpoint is useful. Permissions
listed are the principal backend gates; active User/Employee and context gates
also apply to internal routes.

| Module | Implemented routes / main implementation | Permissions and states / integrity | Key tests | Status |
|---|---|---|---|---|
| Authentication / Authorization | `login`, `logout`, internal `/pos`, `/kitchen`, `/admin`; `AuthenticatedSessionController`, context middleware, Gates | Active account and active Employee required internally; exact context and capability permissions; revocation applies next request | `AuthenticationTest`, `InternalContextAuthorizationTest` | Complete |
| Customer Account / Profile | `register`, `profile.*`; registration and own-profile services | `customer.profile.manage-own`; atomic User/Customer create and email sync; ownership is fail-closed | `CustomerRegistrationTest`, `CustomerProfileTest`, `OwnershipAuthorizationTest` | Complete |
| Category / Menu / Product | `customer.menu.*`, `customer.products.show`, `admin.categories.*`, `admin.products.*`; Customer/Admin controllers | Public reads only active data; `category.manage`, `product.manage`, separate `product.update-price`; soft delete and historical snapshots preserved | `PublicMenuTest`, `MenuManagementTest` | Complete |
| Restaurant Table | `admin.restaurant-tables.*`, `pos.tables.*`; table controllers and management/availability services | `restaurant-table.manage`, `table.view`, `table.operate`; `is_active` is separate from `available/reserved/occupied/cleaning`; only cleaning to available uses the operational transition | `RestaurantTableManagementTest`, `TableMapTest` | Complete |
| Reservation | Customer request/own list/detail and POS list/detail/confirm/reject/no-show/check-in; reservation services | Own/customer boundary; `reservation.manage`, separate `reservation.mark-no-show`; pending to confirmed/rejected, confirmed to checked-in/no-show, payment to completed; row locks on transitions | `ReservationRequestTest`, `ReservationManagementTest` | Complete |
| Dining Session / Check-in / Walk-in | `pos.dining-sessions.*`, reservation check-in; open/check-in services | `dining-session.open/view` plus `table.operate`; one active session per table; active to completed; table availability/capacity rechecked transactionally | `DiningSessionManagementTest`, `DiningSessionConcurrencyTest` | Complete |
| POS Order / Additional Order | `pos.orders.*`, `pos.order-items.update`; `CreateOrderService`, waiting-item update service | `order.create/update`; every round is a new Order in the same active session; current price and name become immutable item snapshots; Session/Table/Product locks | `OrderManagementTest` | Complete |
| Customer signed self-order | signed `customer.dining-context.bind`, Cart submit and current order routes; dining-context/capability/cart/order services | Signed and browser-session-bound active Dining Session; guest allowed; employee actor rejected; runtime setting fails closed; submitted item is `waiting` | `CustomerSelfOrderTest`, `PaymentConcurrencyTest` | Complete |
| Cart / Current Order Status | `customer.cart.*`, `customer.orders.current`; `CartController`, `CustomerCartService` | Cart is session state, contains no trusted price, is scoped to bound Dining Session, and is fully revalidated on submit | `CustomerSelfOrderTest` | Complete |
| Kitchen Queue / OrderItem processing | `kitchen.home`, start-preparing/mark-ready, POS served/cancel endpoints; transition/cancel services | Independent queue/action permissions; `waiting -> preparing -> ready -> served`; allowed cancellation records server-owned audit; item row locking | `KitchenQueueTest`, `OrderItemProcessingTest`, `OrderItemConcurrencyTest` | Complete |
| Billing | POS open/show/invoice and Customer read-only checkout; open/refresh/calculator services | `billing.view`; one Bill per Dining Session; all orders aggregated; cancelled items excluded; draft/unpaid/paid/cancelled vocabulary; Bill/order/item locking | `BillingPaymentTest`, `CustomerCheckoutVoucherTest` | Complete |
| Voucher POS | Admin voucher CRUD and POS apply/remove; voucher controller/service and shared calculator | `voucher.manage`, `voucher.apply`; validity, usage, minimum, cap and positive-total rules are rechecked; historical voucher protected | `VoucherManagementTest`, `BillingPaymentTest` | Complete |
| Customer Voucher Checkout | `customer.checkout.show` and voucher apply/remove; `CheckoutController`, `ApplyVoucherService`, `RefreshOpenBillService` | Only valid bound session; no Customer payment/Bill creation; shared POS calculation; Session/Voucher/Bill/Order/Item lock order; usage unchanged until successful payment | `CustomerCheckoutVoucherTest`, `PaymentConcurrencyTest` | Complete |
| Payment / Invoice / Session Completion | POS complete/fail payment and invoice; complete/fail services and transaction-state locker | `payment.complete` plus `billing.view`; pending/success/failed/cancelled; duplicate success prevented; success pays Bill, increments voucher once, completes Session/Reservation and sets Table cleaning; failure leaves session open | `BillingPaymentTest`, `PaymentConcurrencyTest` | Complete |
| Customer Order History / Spending | `customer.orders.history*`, Admin customer detail; history service | `customer.order.view-own` or Admin `customer.view`; ownership is User -> Customer -> `DiningSession.customer_id`; paid successful Payment drives spending; snapshots only | `CustomerOrderHistoryTest`, `CustomerDirectoryTest` | Complete |
| Inventory | `admin.inventory-items.*` and movement create/store; inventory services | `inventory.view`, separate `inventory.stock-movement.create`; import/export/adjustment movement is immutable; item lock and atomic stock update; low-stock filter/warning | `InventoryManagementTest`, `StockMovementConcurrencyTest` | Complete |
| Reporting | `admin.reports.index`; `OperationalReportQuery` | `report.view`; read-only; revenue is successful Payment of paid Bill; snapshots and application-timezone inclusive range | `OperationalReportingTest` | Complete |
| System Settings | `admin.settings.*`; catalog, typed resolver and update service | Admin-only `settings.update`; whitelist contains no-show timeout and customer ordering; canonical values, fail-closed reads, transactional audited writes | `SystemSettingManagementTest`, `SystemSettingConcurrencyTest` | Complete |
| Localization VI/EN/ZH | `locale.switch`, static `lang/{vi,en,zh}` resources | Locale allow-list is `vi/en/zh`, Vietnamese default/fallback; route context retained | `ApplicationFoundationTest` plus localized feature assertions | Complete |
| Persistent Dynamic Translation | `admin.translations.*`; catalog, resolver and manual-save service | Admin-only `translation.update`; Category/Product name/description; VI source, EN/ZH rows; manual wins; stale provider falls back/refreshes safely; historical snapshots never translated | `DynamicTranslationTest` | Complete |
| Google Cloud Translation | Translation contract, Google client/provider, Null provider binding; `google/cloud-translate` dependency | Selected only with complete valid config; `zh -> zh-CN`, timeout/no retries/request budget, failure returns VI | `GoogleCloudTranslationProviderTest`, provider scenarios in `DynamicTranslationTest` | Complete but requires configuration |
| Admin/Manager navigation and boundaries | Shared Admin presentation and permission-aware navigation; Admin role/employee controllers and services | Manager has Admin context but not `permission.assign`, `settings.update`, `translation.update`; Admin retains `context.admin.access` and `permission.assign`; last-active-Admin lockout protection | `EmployeeAdministrationTest`, `AdministrativeLockoutConcurrencyTest`, `InternalContextAuthorizationTest` | Complete |

## 4. Core flow matrix

| Flow | Happy path and authorization boundary | External/manual dependency and deferred work | Status |
|---|---|---|---|
| Walk-in | Authorized POS operator selects a usable Table, atomically opens its only active Dining Session, creates one or more Orders, Kitchen advances items, POS serves them, opens the session Bill and records Payment; success completes Session and sets Table to cleaning | Staff manually marks cleaning Table available; visual/operational polish belongs to Phase 5 | Implemented |
| Reservation | Customer/guest creates pending request; authorized POS staff confirms, checks in against a suitable Table, creating the Dining Session; normal order/payment flow completes the Reservation | No notification provider; no delivery/takeaway branch | Implemented |
| Customer self-order | Authorized staff creates a temporary signed link; browser binds the active session context; guest or linked Customer uses Cart, submits Order into the same Kitchen pipeline, adds rounds, and may apply/remove a voucher at read-only checkout | Staff still opens Bill and completes Payment; requires valid runtime setting; QR rendering/visual polish may be enhanced in Phase 5 without changing the signed-link contract | Implemented |
| Inventory | Authorized Admin/Manager creates an Inventory Item, records immutable import/export/adjustment movements, views audit history and low-stock warning | No automatic recipe consumption, Supplier or purchasing | Implemented |
| Customer | Register/login, atomically maintain own profile, submit/view own Reservations, and view own session-grouped order history/spending | Guest operation remains supported; no loyalty/advanced CRM | Implemented |
| Back-office | Permission-gated management of Category, Product, Table, Employee/account, Role matrix, Voucher, Inventory, Reports, Settings and Translation | Visual redesign is Phase 5; Google translation needs deployment credentials | Implemented |

## 5. Authorization summary

- Customer ownership never follows a request-supplied customer identifier. Own
  reservation/profile/order access resolves through the authenticated User and
  Customer relationship; unrelated resources fail closed.
- Internal entry points require authentication, an active User, an active linked
  Employee, the exact context permission, and each route's capability permission.
- Admin/Manager share the Admin layout, but presentation access does not grant a
  backend capability. Manager cannot assign permissions, change System Settings,
  or manage persistent Translations.
- Admin's `context.admin.access` and `permission.assign` grants cannot be removed.
  Employee disable logic row-locks Admin membership and prevents losing the last
  valid active Admin; Manager cannot disable an Admin.
- Hiding or disabling an action in Blade is only guidance. Middleware, Gates,
  Form Requests and services remain the security boundary.

## 6. Transaction and integrity decisions

The implementation confirms these Phase 5 invariants:

- The billing unit is the entire Dining Session, which has at most one Bill.
- Cancelled OrderItems are not billable. Product name, unit price and line total
  are immutable transaction snapshots and never read back from the current Product.
- Voucher usage increases only with successful Payment. Successful Payment also
  pays the Bill, completes the Dining Session and checked-in Reservation, and
  changes the Table to `cleaning`; failed Payment leaves them open for retry.
- Customer self-order is available only through a signed, session-bound Dining
  Session context. A guest may order with that valid context; a Customer cannot
  create a Bill or complete Payment.
- Critical session opening/check-in, order/item mutation, voucher/bill refresh,
  payment, Admin lockout, settings, translation persistence and stock movement
  operations use database transactions and consistent row locking. Concurrency
  tests cover the collision paths.
- Inventory changes only through immutable Stock Movements. Recipe/BOM-driven
  automatic consumption is not implemented.
- Reports derive revenue from a successful Payment attached to a paid Bill.
- Runtime settings use a fixed whitelist and fail closed when absent/malformed.
- Dynamic content uses Vietnamese source plus persistent EN/ZH rows. Manual
  translations override provider output; historical snapshots are never retranslated.

No conflict with these approved decisions was found.

## 7. Configuration and external integration status

- `google/cloud-translate` is present in Composer and a provider/client adapter
  exists. Required environment values are `TRANSLATION_PROVIDER=google`,
  `GOOGLE_TRANSLATION_PROJECT_ID`, `GOOGLE_APPLICATION_CREDENTIALS`, optional
  `GOOGLE_TRANSLATION_TIMEOUT_SECONDS` and
  `GOOGLE_TRANSLATION_MAX_CALLS_PER_REQUEST`.
- `.env` and common credential material are ignored; no credential was found in
  tracked project files. The credential must remain outside Git.
- Default `TRANSLATION_PROVIDER=null` binds the Null provider. Missing/invalid
  Google configuration, provider errors, blank/unsafe output, timeout or budget
  exhaustion fail safely to Vietnamese.
- Provider behavior is automated-test covered with fakes. A real Google API call
  was intentionally not made and has not been manual-tested in this checkpoint.
- Weather and Recommendation providers are not implemented. They are deferred
  optional scope and cannot block a Core flow.
- There is no online payment gateway integration; Core records supported manual
  payment outcomes internally.

## 8. Deferred scope

- Recommendation and optional Weather context.
- Delivery, takeaway and online payment gateway.
- Recipe/BOM automatic inventory consumption, Supplier and purchasing.
- Loyalty/advanced CRM, advanced analytics and other baseline Future Scope.
- React/Vue, mobile app, multi-branch and distributed-service expansion.

## 9. Known gaps and blockers

No Core blocker was found. Remaining gaps are configuration or UI scope:

- A deployment must supply and manually verify valid Google Cloud credentials
  before real automatic translation can be considered operational.
- UI routes and actions are functional prototypes; full visual and responsive
  QA remains Phase 5 work.
- Recommendation/Weather remain intentionally absent. They must not be reported
  as broken Core features or added during UI work.

## 10. UI prototype status

The four contexts have functional Blade routes, views, forms and actions,
server validation, flash/error feedback, permission-aware navigation, friendly
403/404 pages and basic responsive behavior. Customer dining links have an
explicit confirmation; menu, Cart, current status, checkout and POS access-link
actions expose the Core path.

This is **not visual UI completion**. Brand identity, imagery, homepage
composition, customer hierarchy, POS dashboard polish, Kitchen display polish,
Admin shell/design-system consistency, and complete desktop/tablet/mobile visual
QA remain open for Phase 5.

## 11. Phase 5 constraints

Phase 5 is UI/UX Integration over the existing backend. It must:

- not change business rules merely to simplify UI, and not add Future Scope;
- preserve routes/contracts unless a change is demonstrably necessary;
- preserve permission, ownership, transactions, locks and idempotency;
- never retranslate historical snapshots;
- use existing backend validation; hidden/disabled actions never replace backend
  authorization;
- prioritize shared components and design tokens;
- perform browser visual QA at desktop, tablet and mobile sizes.

Split Phase 5 into: **5.1 Customer Website**, **5.2 POS/Kitchen**, **5.3
Admin**, and **5.4 Responsive/Visual QA**. Do not mix new business flows into
those tasks.

## 12. Verification results

Run on 26 August 2026 without external API calls:

| Check | Result |
|---|---|
| `composer validate` | Pass |
| `php artisan optimize:clear` | Pass |
| `php artisan route:list -vv` | Pass; 120 routes |
| `php artisan view:cache` | Pass |
| `php artisan test` | Pass; 270 tests, 2,428 assertions |
| `npm run build` | Pass; Vite production build |
| `vendor/bin/pint --test` | Pass |
| `git diff --check` | Pass |

`phpunit.xml` explicitly selects `DB_DATABASE=89_beer_garden_test`. No test was
run against the development database and no real Google API request was made.

## 13. Git/worktree state at handoff

- Branch: `main`.
- No staged files and no commit was created by this checkpoint.
- The checkpoint started with existing unstaged/untracked implementation from
  Phase 4.18 and 4.20. Those changes were preserved, not discarded or rewritten.
- This checkpoint adds this handoff document and updates README. The final
  `git status` is the authoritative file-level inventory for handoff.
