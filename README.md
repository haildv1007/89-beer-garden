# 89 Beer Garden

## 1. Overview

89 Beer Garden is a restaurant website and management system for a dine-in
operation. It brings menu discovery, reservations, table operations,
Dining Sessions, ordering, kitchen coordination, billing and payment into one
system for customers and restaurant staff.

The project is currently in the development-preparation stage. The approved
system baseline and implementation context are maintained in [`docs/`](docs/).

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
development servers separately with `php artisan serve` and `npm run dev`.

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

## 10. Development Status

- Planning: Completed
- Business & Requirements: Completed
- System Analysis: Completed
- UI/UX Design: Completed
- Database & Architecture: Completed
- Development: In progress — Phase 4.10 Kitchen Queue & Order Item Processing completed
- Testing: Pending
- Deployment: Pending

## 11. Project Information

**Project:** 89 Beer Garden Website & Management System  
**Type:** Academic/project-based restaurant management system  
**Operational focus:** Single-location dine-in restaurant workflow
