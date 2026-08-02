# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**ERP Komi Proto** — Laravel 12 + Filament 4 ERP prototype for PT Komitrando Emporio, a manufactured bag industry serving international brands (export to US & Canada).

**Two companies (multi-tenant):**
- **KMT001** — PT Komitrando Emporio (Main - Production)
- **KMT002** — PT Komitrando Textile (Branch - Warehouse)

Admin panel at `/admin`. All data is scoped by `company_id`. Full spec: `.claude/SPEC_DOCUMENT_ERP_KOMI.md` and `CODING_GUIDELINES.md`

## Dev Commands

```bash
composer dev          # Full: Laravel server + queue worker + Vite dev server (localhost:8000)
php artisan serve     # Laravel server only
npm run dev           # Vite dev server only

# Database
php artisan migrate                           # Run migrations
php artisan migrate:fresh --seed             # Reset DB and seed (for fresh setup)
php artisan db:seed                           # Seed demo data

# Tests
php artisan test
php artisan test --filter=User

# Code quality
./vendor/bin/pint      # Format code
```

## Architecture

### Filament Admin Panel

All admin UI lives in `app/Filament/Resources/`. Each resource follows this structure:
- `app/Filament/Resources/{Entity}Resource.php` — Form schema + table definition
- `app/Filament/Resources/{Entity}Resource/Pages/` — List, Create, Edit pages

The panel is configured in `app/Providers/Filament/AdminPanelProvider.php`:
- Path: `/admin`
- Color theme: Amber primary, dark mode supported, top navigation enabled

Filament v4 Schema/Table APIs are used: `Schema::schema([...])`, `Table::columns([...])`.

### Multi-Tenancy (company_id)

Most entities have a `company_id` foreign key as the first column after `id`. Always include it in fillables, relations, and as the first database index. Use `App\Traits\BelongsToCompany` trait on models. All Filament resources must scope queries, form unique rules, and relationship selectors by the selected company via `CompanyContext::getCompanyId()`.

### Module Phases

**Phase 1 — Pre-Production (Fully Functional, see spec for details):**
- R&D / Consumption (Design Library, BOM, Consumption Rates)
- Project Initiation (Proto → Sample → Mass workflow)
- Merchandising (Material planning, supplier/subcon assignment)
- Costing / Pricing (Material + Man Power + Overhead + Shipping + Profit)
- Sales Order, Purchase Order (Supplier + Subcon), Purchase Tracking
- Goods Receipt, Inventory (stock management, subcon material tracking)
- Shipment (Packing List, Delivery Order)
- Invoice (Purchase + Sales with Faktur Pajak / PPN 10%)

**Phase 2 — Production (Simplified, UI + static data):**
- Production Order, Job Order (JO), SPP, QC Management, Material Usage Report

**Phase 3 — Finance (Simplified, UI + static data):**
- Payment Tracking, Chart of Accounts, General Ledger, L/R Report

### Database

MySQL (`erp_komi_proto`). Migrations in `database/migrations/`. Tests use in-memory SQLite (`:memory:`).

### Key Patterns & Anti-Crash Rules

- **Enum fields**: Stored as string columns, cast via `$casts` array in models.
- **Dates**: Use `date` or `datetime` cast, not Carbon objects directly. Range dates MUST enforce `->afterOrEqual('start_date')`.
- **Model relations**: Follow Laravel Eloquent conventions (`BelongsTo`, `HasMany`).
- **Project types**: `proto` → approved → auto-create `sample` → approved → auto-create `mass`.
- **Filament v4 Namespaces**: Always use `\Filament\Actions\Action` (NOT `Filament\Tables\Actions\Action`). Use `\Filament\Actions\BulkAction` & `\Filament\Actions\BulkActionGroup`.
- **Closure Typehints**: DO NOT use concrete typehint `fn (Get $get)` in form closures; use `fn ($get)` without importing `Get`.
- **Tenant Scoping**: Form `->unique()` and `->relationship()` selectors MUST be scoped per tenant using `CompanyContext::getCompanyId()`.
- **Clean Error Handling**: Never leak raw SQL/Database exceptions to UI. Catch in Service layer and convert to Filament UI Notification (`Notification::make()->danger()->send()`).

### Costing / Pricing Config (Static)

**Man Power per Unit:** Cutting Rp 5,000 | Sewing Rp 15,000 | Finishing Rp 8,000 | QC Rp 3,000 | Packing Rp 2,000 | **Total Rp 33,000**

**Shipping per Unit:** Jakarta Rp 5,000 | Jawa non-Jakarta Rp 8,000 | Luar Jawa Rp 12,000 | Export (US/Canada) Rp 35,000

**Overhead 15% | Profit Margin 20%**

**Selling Price** = (Material Cost + MP Cost) × (1 + Overhead%) × (1 + Profit%) + Shipping
