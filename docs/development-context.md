# 89 Beer Garden — Development Context

**Purpose:** Context baseline for AI Coding Agents entering Development. This
document consolidates final system decisions for implementation navigation; it
does not replace the baseline documents.

## 1. Project Overview

89 Beer Garden is a restaurant website and management system centred on a
single-location dine-in operation: digital menu, reservation, table operation,
Dining Session, ordering, kitchen processing, billing/payment and operational
administration. The system serves Customer, POS/Staff, Kitchen and Admin
contexts in one application.

Detailed source: [Project Planning](01-project-planning/01-project-planning.md),
[Business Analysis](02-business-requirements/business-analysis.md).

## 2. Development Baseline

- The current baseline is the result of the eight approved scope and model
  decisions.
- Core operation is **dine-in only**. A Cart is session/application state and
  becomes an Order only in a valid Dining Session.
- The billing unit is a Dining Session, not an individual Order.
- Canonical states, data integrity rules and architecture boundaries are
  mandatory implementation constraints.
- All critical business validation, authorization and state transitions are
  enforced by the backend.

Detailed source: [System Requirements](02-business-requirements/system-requirements.md),
[Database Design](05-database-architecture/05-database-design-baseline.md),
[System Architecture](05-database-architecture/05-system-architecture-baseline.md).

## 3. Core Scope

- Authentication, roles and permissions.
- Customer website: static restaurant information, menu/category/product,
  search/filter, reservation, session Cart and dine-in self-order when enabled.
- Restaurant operations: table management, Dining Session, POS order creation,
  additional orders in the same session, basic Kitchen UI and item processing.
- Billing, manual/cash-supported payment flow and invoice representation from a
  paid Bill.
- Customer and employee management for operational use.
- Basic reporting; simplified voucher and inventory management.
- Vietnamese, English and Chinese support.
- AI recommendation as an optional supporting feature; weather as optional
  recommendation context.

Detailed scope and priorities: [System Requirements — Scope Matrix](02-business-requirements/system-requirements.md).

## 4. Future Scope

Do not implement these without an approved change request:

- Takeaway ordering, delivery integration and a payment gateway.
- Supplier/purchasing, Recipe/BOM and advanced inventory.
- News/CMS/WordPress, product gallery/video and AI analytics.
- Loyalty/membership, advanced CRM, ML recommendation, Kitchen Display
  System, mobile app, multi-branch/ERP/HRM/BI expansion.

Detailed source: [Project Planning](01-project-planning/01-project-planning.md),
[System Requirements — Scope Guardrails](02-business-requirements/system-requirements.md).

## 5. Actors & Roles

Canonical roles are `admin`, `manager`, `staff`, `kitchen`, and `customer`.

- **Customer:** browse menu, manage own reservations, use Cart and submit a
  dine-in self-order only in a valid Dining Session. An account is optional.
- **Staff:** operate POS, tables, sessions, orders and payment according to
  granted permissions. Cashier is an operational capability, not an additional
  canonical role.
- **Kitchen:** sees assigned order items and moves their preparation state.
- **Manager:** operational oversight and management permissions as configured.
- **Admin:** system administration, including roles/permissions, master data,
  translations and settings; admin access never bypasses integrity rules.

Detailed actor responsibilities and permission matrix: [Business Analysis](02-business-requirements/business-analysis.md),
[System Requirements — Authorization](02-business-requirements/system-requirements.md),
[Use Cases](03-system-analysis/03-system-analysis-users-use-cases.md).

## 6. Core Modules

`Auth & Access` · `Menu` · `Customer` · `Reservation` · `Table` · `Dining
Session` · `Order` · `Kitchen` · `Billing` · `Payment` · `Voucher` ·
`Inventory` · `Reporting` · `Localization` · `Configuration` ·
`Recommendation`.

Presentation contexts are `Customer`, `POS`, `Kitchen`, and `Admin`.
Business services are shared by domain where the same flow is used by more
than one context (for example, order creation).

Detailed source: [System Architecture — Logical Business Modules](05-database-architecture/05-system-architecture-baseline.md),
[Source Code Structure Convention](06-development/06-source-code-structure-convention.md).

## 7. Key Business Rules

- One restaurant table has at most one active Dining Session.
- A reservation is not a Dining Session; a reservation may never check in, and
  a walk-in session may have no reservation.
- Every operational Order references a valid active Dining Session. Additional
  orders stay in that same session.
- Customer Cart contents are validated again at submit time: session context,
  product availability, quantity and current price. `OrderItem.unit_price` is
  then an immutable historical snapshot.
- Kitchen processing is item-level; do not create an independent Order-level
  processing state as a source of truth.
- A Bill aggregates billable Order Items for one Dining Session. It may have
  multiple payment attempts. A failed payment leaves the Bill unpaid and the
  session active for retry.
- A paid Bill produces an invoice representation/print; there is no separate
  Core invoice entity/table.
- Core inventory contains Inventory Items and auditable Stock Movements
  (`import`, `export`, `adjustment`); it has no Supplier/Purchase/PurchaseItem
  model.
- Product master changes do not rewrite transaction history. Do not hard-delete
  or cascade-delete historical transactions due to master-data changes.

Detailed rules: [Business Analysis — Business Rules](02-business-requirements/business-analysis.md),
[Database Design — Critical Data Integrity Rules](05-database-architecture/05-database-design-baseline.md).

## 8. Canonical State Models

Use exactly these state vocabularies and validate legal transitions in the
backend:

| Entity | Canonical states |
|---|---|
| Reservation | `pending`, `confirmed`, `checked-in`, `completed`, `rejected`, `cancelled`, `no-show` |
| Restaurant Table runtime | `available`, `reserved`, `occupied`, `cleaning` |
| Dining Session | `active`, `completed` |
| Order Item | `waiting`, `preparing`, `ready`, `served`, `cancelled` |
| Bill | `draft`, `unpaid`, `paid`, `cancelled` |
| Payment | `pending`, `success`, `failed`, `cancelled` |

`RestaurantTable.is_active` is a separate configuration flag, not a runtime
state. `billing` is UI/workflow context only, and `closed` is not a Dining
Session state. Use PHP enums matching persisted database strings.

Detailed source: [System Requirements — SYS-DEC-10](02-business-requirements/system-requirements.md),
[Database Design — Status Strategy](05-database-architecture/05-database-design-baseline.md),
[System Architecture — Enums & State Transitions](05-database-architecture/05-system-architecture-baseline.md).

## 9. Core System Flows

1. **Reservation:** Customer/Staff creates reservation → Staff confirms or
   rejects → check-in may lead to a Dining Session → reservation eventually
   completes, cancels or becomes no-show.
2. **Walk-in / table opening:** Staff validates usable table → opens the only
   active Dining Session for that table → table becomes occupied.
3. **Customer self-order:** Customer uses a session Cart → backend validates
   valid active Dining Session and menu data → creates Order and Order Items
   atomically → Kitchen receives item work. This is not takeaway checkout.
4. **POS order:** Staff creates initial or additional order in the existing
   session; price is snapshotted per item.
5. **Kitchen:** Kitchen moves each item through `waiting → preparing → ready →
   served`, or cancels it under the defined rule.
6. **Payment:** POS prepares Bill for the session → payment is recorded with
   idempotency/atomic protection → success marks Bill paid, completes the
   session and transitions the table for cleaning; failure permits retry.
7. **Stock movement:** Authorized Admin records a movement and updates current
   stock atomically.

Detailed source: [System Flows](03-system-analysis/03-system-analysis-system-flows.md),
[UX User Flows](04-ui-ux/04-ux-user-flow-baseline.md),
[Architecture — Core Transaction Boundaries](05-database-architecture/05-system-architecture-baseline.md).

## 10. Data Model Overview

Core MySQL tables:

- Access: `users`, `roles`, `permissions`, `role_permissions`, `employees`.
- Menu: `categories`, `products`.
- Customer/reservation: `customers`, `reservations`.
- Operations: `restaurant_tables`, `dining_sessions`.
- Ordering: `orders`, `order_items`.
- Billing: `vouchers`, `bills`, `payments`.
- Inventory: `inventory_items`, `stock_movements`.
- Localization/configuration: `translations`, `system_settings`.

Important relationships: a nullable unique `customers.user_id` links an
optional account to a Customer profile; a Dining Session belongs to a table and
can contain many orders; an Order contains many Order Items; a Bill belongs to
a Dining Session and can have many Payment attempts. Store money precisely as
defined in the database baseline, preserve transaction snapshots, and use the
baseline foreign keys, indexes, soft-delete and audit rules.

Detailed schema: [Database Design](05-database-architecture/05-database-design-baseline.md).

## 11. Architecture & Tech Stack

- PHP 8.4, Laravel 12, Eloquent ORM and MySQL 8.x.
- Blade, HTML5, CSS3, JavaScript and Bootstrap 5; responsive mobile → tablet
  → desktop. React/Vue are not part of the current scope. Vite/npm may be used
  as build tooling.
- One deployable **Laravel Modular Monolith** and one Core MySQL database.
- Layer flow: routes/controllers/requests → services when business flow is
  non-trivial → Eloquent/models/queries → MySQL.
- Keep controllers thin. Services own multi-entity transactions, concurrency,
  state transitions and cross-module coordination. Models own relationships,
  casts, scopes and small entity behavior.
- Organize controllers, requests, views and feature tests by presentation
  context; organize services by business domain. Use conventional Laravel
  structure—no `app/Modules` and no deep `Domain/Application/Infrastructure`
  hierarchy in Core.
- Do not introduce repositories, DTOs, actions, events or queues by default.
  Create them only for a demonstrated need. Critical flows remain synchronous
  and explicit.

Detailed source: [Project Planning — Technology](01-project-planning/01-project-planning.md),
[System Architecture](05-database-architecture/05-system-architecture-baseline.md),
[Source Code Structure Convention](06-development/06-source-code-structure-convention.md).

## 12. Authorization & Ownership Rules

- Backend authorization is mandatory; hidden UI controls are not a security
  boundary.
- Role grants determine capability, while business/data-integrity rules still
  apply to every role, including Admin.
- Customer account ownership is limited to that account's own customer profile,
  orders and reservations. Guest/walk-in operation remains valid without an
  account.
- Verify actor permission and resource ownership in Policies and/or the
  appropriate application service. Use the exact permission rules and
  capability matrix from the System Requirements for implementation detail.

Detailed source: [System Requirements — Authorization & Ownership](02-business-requirements/system-requirements.md),
[Database Design — Customer Model](05-database-architecture/05-database-design-baseline.md).

## 13. External Integrations

- **Translation provider:** dynamic-content translation only; read persistent
  translation first, persist provider success, then optionally cache it.
- **Weather provider:** optional context for recommendation only.
- **AI provider (Google Gemini API):** recommendation only.
- **Payment gateway:** Future Scope; current Core payment does not depend on
  one.

Integrations must be called through an application-service → contract → adapter
boundary. Provider SDKs must not be called directly from controllers or models.
Translation, weather and AI failures must degrade gracefully and cannot break
Core menu, reservation, order or payment flows.

Detailed source: [System Architecture — External Integration Architecture](05-database-architecture/05-system-architecture-baseline.md),
[Project Planning](01-project-planning/01-project-planning.md).

## 14. Multilingual Strategy

- Supported locales: `vi` (default and fallback), `en`, `zh`.
- Use Laravel localization resources under `lang/` for static UI text.
- Store manual and dynamic content translations persistently in `translations`.
  Manual translations take precedence; optional runtime cache is never the
  source of truth.
- On translation-provider failure, fall back to Vietnamese without breaking a
  Core flow.
- Admin-managed runtime settings are persisted in `system_settings`; cache is
  optional optimization only.

Detailed source: [Business Analysis — Multilingual](02-business-requirements/business-analysis.md),
[Database Design — Translation & Settings](05-database-architecture/05-database-design-baseline.md).

## 15. AI Recommendation Boundaries

- AI may recommend existing, available products/combinations using approved
  context such as party size, budget, preferences, time, optional weather and
  optional customer history.
- Validate returned product IDs against system data before displaying them.
- AI only advises. It cannot create or mutate an Order, Order Item, Bill,
  Payment, stock, state transition or any other Core transaction.
- Add-to-Cart is an explicit Customer action; AI never adds items automatically.
- If unavailable, fall back to popular/featured/rule-based suggestions or the
  normal menu.

Detailed source: [Business Analysis — AI Recommendation](02-business-requirements/business-analysis.md),
[System Requirements — AI Boundary](02-business-requirements/system-requirements.md),
[System Architecture — AI Integration](05-database-architecture/05-system-architecture-baseline.md).

## 16. Development Constraints

- Preserve Core/Future scope separation and the canonical state models.
- MySQL is the Core source of truth; frontend state, cache and external
  providers cannot replace it.
- Use explicit database transactions and appropriate locks/constraints for
  session opening, order creation, stock movement and payment completion.
- Preserve payment idempotency and retry behavior; never close a session after
  a failed payment.
- Do not hide required atomic steps behind asynchronous events or queues.
  Events/jobs are for post-commit side effects such as notification, analytics,
  heavy report work or cache warming.
- Keep UI aligned with the four contexts and the approved user flows. Do not
  add delivery/takeaway checkout fields to Core screens.

Detailed source: [System Architecture — Critical Rules](05-database-architecture/05-system-architecture-baseline.md),
[UI Design Baseline](04-ui-ux/04-ui-design-baseline.md),
[Source Code Structure Convention](06-development/06-source-code-structure-convention.md).

## 17. Source Documents

The following baseline documents are the source of truth, in their respective
domains:

1. [Project Planning](01-project-planning/01-project-planning.md)
2. [Business Analysis](02-business-requirements/business-analysis.md)
3. [System Requirements](02-business-requirements/system-requirements.md)
4. [System Analysis — System Flows](03-system-analysis/03-system-analysis-system-flows.md)
5. [System Analysis — Users & Use Cases](03-system-analysis/03-system-analysis-users-use-cases.md)
6. [UI Design Baseline](04-ui-ux/04-ui-design-baseline.md)
7. [UX User Flow Baseline](04-ui-ux/04-ux-user-flow-baseline.md)
8. [Database Design Baseline](05-database-architecture/05-database-design-baseline.md)
9. [System Architecture Baseline](05-database-architecture/05-system-architecture-baseline.md)
10. [Source Code Structure Convention](06-development/06-source-code-structure-convention.md)

## 18. AI Coding Agent Rules

1. Always read `docs/development-context.md` before starting a development
   task.
2. Then read only the source documents directly relevant to that task.
3. The baseline files in `docs/01-*` through `docs/06-*` are the source of
   truth.
4. This file is a context/index; it does not replace the source documents.
5. Do not infer a missing business rule.
6. Do not change Core/Future Scope on your own.
7. Do not change canonical state models on your own.
8. Do not change the database or architecture merely for implementation
   convenience.
9. If a conflict materially affects implementation, report it before changing
   any baseline.
10. Do not reread all documents in `docs/01-*` through `docs/06-*` for every
    coding task.

Additionally, determine the relevant use case, presentation context, domain
owner, authorization, state transition, transaction/concurrency and
idempotency needs before writing code. Follow the source-code convention for
file placement, and do not create unused folders or abstractions.
