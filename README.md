# 89 Beer Garden

## 1. Overview

89 Beer Garden is a restaurant website and management system for a dine-in
operation. It brings menu discovery, reservations, table operations,
Dining Sessions, ordering, kitchen coordination, billing and payment into one
system for customers and restaurant staff.

Phase 4 Core Development is complete. Phase 5.2 POS & Kitchen Operational UI
Redesign is complete over the approved backend contracts. Phase 5 remains in
progress, with Phase 5.3 Admin Back-office UI Redesign next. The system baseline,
implementation context and Phase 5 handoff are maintained in [`docs/`](docs/).

## 2. Core Features

- Customer menu, product information, search/filter and reservation.
- Session Cart and customer self-order within a valid Dining Session.
- Table management, walk-in handling and Dining Session operation.
- POS ordering, including additional orders in the same session.
- Basic Kitchen/Bar item processing.
- Billing, payment recording and invoice representation from a paid Bill.
- Role/permission management, customer and employee operations.
- Basic reporting, simplified voucher and inventory stock movements.
- Vietnamese, English and Chinese support.
- Optional AI recommendation and optional weather context.

## 3. Tech Stack

| Area | Baseline technology |
|---|---|
| Backend | PHP 8.4, Laravel 12 |
| Frontend | Blade, HTML5, CSS3, JavaScript, Bootstrap 5 |
| Database | MySQL 8.x, Eloquent ORM |
| Architecture | Conventional Laravel Modular Monolith |
| AI | Google Gemini API |
| Localization | Laravel Localization + Translation API for dynamic content |
| Tooling | Git, GitHub; Vite/npm may be used as build tooling |

The Core is one Laravel application with one Core MySQL database. External
services are isolated behind integration boundaries.

## 4. System Overview

```text
Reservation / Walk-in
        → Table
        → Dining Session
        → Order
        → Kitchen
        → Billing
        → Payment
        → Complete Session
```

The billing unit is a Dining Session. Customer self-order is dine-in only and
requires a valid Dining Session context.

## 5. Repository Structure

The repository contains the approved documentation baseline and a Laravel 12
application scaffold prepared for development.

```text
app/        Laravel application code
database/   Migrations, factories and seeders
docs/       Project baselines and development context
resources/  Blade views and frontend source assets
routes/     Application and console routes
tests/      Automated tests
README.md   Repository entry point
```

Application code must continue to use `app/`, `database/`, `resources/`,
`routes/`, and `tests/`, following the
[Source Code Structure Convention](docs/06-development/06-source-code-structure-convention.md).

## 6. Documentation

Start here: [Development Context](docs/development-context.md) — the
development entry point and context/index for AI Coding Agents.

| Area | Documents |
|---|---|
| 01 — Project Planning | [Project Planning](docs/01-project-planning/01-project-planning.md) |
| 02 — Business & Requirements | [Business Analysis](docs/02-business-requirements/business-analysis.md) · [System Requirements](docs/02-business-requirements/system-requirements.md) |
| 03 — System Analysis | [System Flows](docs/03-system-analysis/03-system-analysis-system-flows.md) · [Users & Use Cases](docs/03-system-analysis/03-system-analysis-users-use-cases.md) |
| 04 — UI/UX Design | [UI Design](docs/04-ui-ux/04-ui-design-baseline.md) · [UX User Flows](docs/04-ui-ux/04-ux-user-flow-baseline.md) |
| 05 — Database & Architecture | [Database Design](docs/05-database-architecture/05-database-design-baseline.md) · [System Architecture](docs/05-database-architecture/05-system-architecture-baseline.md) |
| 06 — Development | [Source Code Structure Convention](docs/06-development/06-source-code-structure-convention.md) |
| Phase handoff | [Phase 4 Closeout and Phase 5 Handoff](docs/06-development/phase-4-closeout-and-phase-5-handoff.md) |

The baseline documents in `docs/01-*` through `docs/05-*` are the detailed
source of truth. `docs/development-context.md` is a navigation context, not a
replacement for them.

## 7. Getting Started

Prerequisites are PHP 8.4, Composer, MySQL 8.x, Node.js and npm. Copy
`.env.example` to `.env`, configure the local MySQL credentials, then run:

```text
composer install
php artisan key:generate
npm install
php artisan migrate
npm run build
php artisan test
```

For local development, use `composer run dev`, or run the Laravel and Vite
development servers separately. On Windows, run `./serve.ps1` instead of
`php artisan serve` so product image/video uploads support up to 5 MB per file;
then run `npm run dev` in another terminal. Compatible web servers also read
the same upload limits from `public/.user.ini`.

Before implementing a feature, read the Development Context and the directly
relevant baseline documents.

## 8. Development Workflow

```text
Read Development Context
        → Identify relevant source documents
        → Inspect existing code
        → Plan
        → Implement
        → Test
```

For complex flows, explicitly account for authorization, canonical state
transitions, transactions, concurrency and payment idempotency.

## 9. AI Coding Agent Instructions

- Read this README, then read [`docs/development-context.md`](docs/development-context.md)
  before every new development task or session.
- Read only the additional source documents directly relevant to the task.
- Treat `docs/01-*` through `docs/05-*` as the source of truth.
- Do not independently change business rules, Core/Future Scope, canonical
  states, database design or architecture.
- If implementation exposes a baseline conflict, report it before changing a
  specification.
- Do not reread all baseline documents for every task.

## 10. Operational Reporting Metrics

The Phase 4.14 operational report is read-only and uses the application
timezone for inclusive date boundaries. Its metrics are defined as follows:

- Revenue is the amount of one successful Payment per paid Bill, filtered by
  `Payment.paid_at`. Failed, cancelled, pending and unpaid records are excluded.
- A valid Order belongs to a dining session with a qualifying paid Bill in the
  selected period and has at least one non-cancelled OrderItem.
- Average Order Value is revenue divided by the valid Order count, using integer
  currency units and returning zero when there are no valid Orders.
- Top products use immutable OrderItem product-name and price snapshots, exclude
  cancelled items, group by `product_id`, then rank by quantity, line revenue
  and product ID. The dashboard displays the first ten.
- Reservation statistics are grouped by status and filtered by
  `reservations.created_at`, because the approved baseline does not define a
  different reporting timestamp for reservations.

## 11. System Configuration

Phase 4.15 exposes only two whitelisted runtime settings in the Admin context:

- `no_show_timeout_minutes`: canonical integer text from `1` through `1440`.
- `customer_ordering_enabled`: canonical boolean text, exactly `true` or `false`.

Missing settings, mismatched types, non-canonical values and out-of-range values
fail closed in both the reservation no-show and customer self-order consumers.
The settings UI never reads or manages environment configuration, application
keys, database credentials, mail credentials, provider tokens or other secrets.
Every successful create or update records the active Employee actor and database
update timestamp; writes are transactional and lock the setting row before actor
validation. No runtime default setting is seeded.

## 12. Dynamic Content Translation

Static interface text remains in Laravel `lang/` resources. Persistent automatic
translations are limited to Category and Product `name` and `description`.
Vietnamese is the source and safe fallback; only English and Chinese rows are
stored. Source hashes use SHA-256. Stale provider translations are refreshed
automatically when possible and otherwise fall back to Vietnamese. The default
provider is an unavailable/null adapter, so failures never break customer pages
and no network is called until a deployment supplies an approved provider.
Credentials remain exclusively in environment/configuration. Transactional
OrderItem, Kitchen, invoice and report snapshots are never translated again.

To enable automatic customer-facing translation, create a Google Cloud service
account with Cloud Translation access, keep its JSON file outside Git, and set:

```text
TRANSLATION_PROVIDER=google
GOOGLE_TRANSLATION_PROJECT_ID=your-project-id
GOOGLE_APPLICATION_CREDENTIALS=/absolute/private/path/service-account.json
GOOGLE_TRANSLATION_TIMEOUT_SECONDS=3
GOOGLE_TRANSLATION_MAX_CALLS_PER_REQUEST=10
```

Select EN or ZH in the Customer navigation, then inspect the `translations`
table to confirm persistence. Set
`TRANSLATION_PROVIDER=null` to verify the safe Vietnamese fallback. The Google
integration uses the official `google/cloud-translate` PHP client, maps `zh` to
Google Simplified Chinese (`zh-CN`), disables request retries, and enforces a
request-scoped call budget. Never commit a service-account file or API key.

## 13. Development Status

Customer order history is grouped by Dining Session and ownership is derived
only from authenticated User → Customer profile → `dining_sessions.customer_id`.
Historical item names and prices always use immutable OrderItem snapshots.
Spending counts one successful Payment amount per paid, completed session;
failed payments and unpaid Bills contribute nothing. Customer routes require
the own-order permission, while Admin history remains read-only and requires
the Admin context plus `customer.view`.

Customer checkout is available only through the browser's signed, bound Dining
Session context. Staff still opens the Bill and completes Payment. Customer
apply/remove operations share the POS voucher calculator, refresh snapshot
totals transactionally, and never change voucher usage; only successful Payment
increments usage. Customer voucher mutation locks follow Session → Voucher →
Bill → Orders → Items → Table. Additional-order/cancellation refresh is already
serialized by the Session lock and uses a current Bill lock before re-reading
its Voucher, avoiding a stale repeatable-read snapshot. Zero-total vouchers fail
closed, and no Bill, Session, amount, payment method or status identifier is
accepted from Customer input.

Phase 4.20 completed the Core UI reachability audit across Customer, POS,
Kitchen and Admin contexts. Controller views and navigation paths are covered by
render/authorization tests; shared layouts provide responsive navigation and
consistent success/error feedback. Customer dining links now lead to an
explicit table/session confirmation, while invalid links render friendly error
pages. Menu, Cart, current status, checkout and POS customer-link actions expose
clear Core CTAs without weakening their backend permission or ownership checks.
AI recommendations, Weather and other Future Scope remain deferred.

- Planning: Completed
- Business & Requirements: Completed
- System Analysis: Completed
- UI/UX Design: Completed
- Database & Architecture: Completed
- Development: Phase 4 Core Development completed
- Current phase: Phase 5.3 Admin Back-office UI Redesign completed; Phase 5 in progress
- Next phase: Phase 5.4 Cross-context UI QA & Polish
- Testing: Pending
- Deployment: Pending

### Customer visual design system

The Customer context uses a mobile-first brand layer over Bootstrap: charcoal
and warm-cream surfaces, amber and ember accents, accessible status treatments,
responsive catalogue cards, and project-owned imagery with category-based
fallbacks. Customer interface copy is maintained in VI, EN, and ZH resources.
Customer styles are isolated in `customer.css`. POS uses a dense operational
shell in `pos.css`, while Kitchen uses a three-state KDS in `kitchen.css` with
manual refresh and responsive tabs. Neutral tokens and primitives remain in
`app.css`; each layout loads only its own context stylesheet. Admin back-office
visual redesign is isolated in `admin.css`: a permission-aware grouped sidebar,
compact actor topbar, dense tables and restrained form/detail surfaces.
Phase 5.4 will complete cross-context visual QA and polish.

The Phase 6.1 ordering foundation now uses a universal session cart: guests and
Customers may select dishes without a Dining Session, retain that cart when a
table context changes or expires, and then choose table ordering, dine-in
reservation, pickup or delivery. Only a valid signed table context may submit
directly into the existing Dining Session and Kitchen pipeline. Reservation
pre-order persistence, pickup processing and delivery processing remain the
next functional increments; the UI does not create placeholder Orders for
those channels.

Phase 6.2A adds the first complete off-table vertical flow for restaurant
pickup. A pickup checkout captures guest/contact details and the requested
pickup time, then transactionally creates an independent `FUL-*` order with
immutable product and price snapshots. Server-owned totals and Voucher data
are recalculated at placement time, and the cart is cleared only after commit.
Pickup orders remain pending for restaurant confirmation; POS acceptance,
Kitchen processing, delivery and dine-in pre-orders are subsequent increments.

Phase 6.2B adds the internal pickup handoff. Staff with the existing order
permission can review, confirm or reject pending pickup orders in POS. Only a
confirmed pickup order enters the Kitchen Queue, where its snapshot items use
the existing waiting-to-preparing-to-ready permissions and appear with a
PICKUP marker and requested collection time. Pickup completion/payment and
delivery remain separate subsequent workflows.

Customer pages retain the VI/EN/ZH language switcher. Employee-facing Admin,
POS and Kitchen contexts are intentionally Vietnamese-only and do not display
language controls. Products support up to five uploaded JPG, PNG, WebP, MP4 or
WebM media files; the first uploaded image is used as the catalogue thumbnail,
while the product detail page renders the complete image/video gallery.

## 14. Project Information

**Project:** 89 Beer Garden Website & Management System  
**Type:** Academic/project-based restaurant management system  
**Operational focus:** Single-location dine-in restaurant workflow
