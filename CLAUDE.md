# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**ERP Komi Proto** — A Laravel 12 + Filament 4 ERP system prototype for managing projects, production, inventory, and supply chain operations. The admin panel is at `/admin`.

## Dev Commands

```bash
# Full dev environment (serves at localhost:8000)
composer dev

# Single commands
php artisan serve              # Laravel server only
php artisan queue:listen --tries=1 --timeout=0   # Queue worker
npm run dev                    # Vite dev server
npm run build                  # Production frontend build

# Tests
php artisan test               # Run all tests
php artisan test --filter=User # Run specific test

# Database
php artisan migrate            # Run migrations
php artisan migrate:fresh --seed   # Reset DB and seed

# Code quality
./vendor/bin/pint               # Format code
```

## Architecture

### Filament Admin Panel

All admin UI lives in `app/Filament/Resources/`. Each resource follows this structure:
- `app/Filament/Resources/{Entity}Resource.php` — Form schema + table definition
- `app/Filament/Resources/{Entity}Resource/Pages/` — List, Create, Edit (and optionally View) pages

The panel is configured in `app/Providers/Filament/AdminPanelProvider.php`:
- Path: `/admin`
- Auth: enabled with registration
- Color theme: Amber primary
- Dark mode: disabled
- Top navigation enabled

### Multi-Tenancy

Most entities have a `company_id` foreign key as the first column after `id`. The `company_id` is the tenant identifier. Always include it in fillables and as the first database index.

### Domain Models

Core ERP domain entities in `app/Models/`:
- **Companies** — `Company.php`
- **Projects** — `Project.php` with types: `proto`, `sample`, `mass` and statuses: `planning` → `completed`
- **Materials & Products** — `Material.php`, `Product.php`
- **R&D Design** — `RdDesign.php`
- **Sales & Purchasing** — `SalesOrder.php`, `PurchaseOrder.php`, `PurchaseReceipt.php`
- **Inventory** — `Inventory.php`, `InventoryMovement.php`, `GoodsReceipt.php`
- **Production** — `ProductionOrder.php`, `ProjectBom.php`, `ProjectConsumption.php`
- **QC** — `QcInspection.php`
- **Outbound** — `Shipment.php`
- **Finance** — `Invoice.php`, `InvoiceItem.php`, `Costing.php`
- **Merchandising** — `Merchandising.php`
- **Parties** — `Customer.php`, `Supplier.php`, `Subcon.php`

### Key Patterns

1. **Resource definition**: Uses Filament v4 Schema + Table APIs (`Schema::schema([...])`, `Table::columns([...])`)
2. **Model relations**: Follow Laravel Eloquent conventions with explicit types (`BelongsTo`, `HasMany`)
3. **Enum fields**: Stored as string columns, cast via `$casts` array in models
4. **Dates**: Use `date` or `datetime` cast, not Carbon objects directly

### Database

MySQL (`DB_CONNECTION=mysql` in `.env`, database: `erp_komi_proto`). Migrations in `database/migrations/`. Tests use in-memory SQLite (`:memory:`).
