# SPEC DOCUMENT

## ERP Prototype Demo - PT Komitrando Emporio

### Manufactured Bag Industry

---

**Document Version:** 1.0  
**Date:** 21 May 2026  
**Author:** Regulux Labs  
**Status:** Draft - For Project Prototype

---

## TABLE OF CONTENTS

1. [Project Overview](#1-project-overview)
2. [Technical Specification](#2-technical-specification)
3. [System Architecture](#3-system-architecture)
4. [Module Specifications](#4-module-specifications)
5. [Data Models](#5-data-models)
6. [User Flows](#6-user-flows)
7. [Demo Scope](#7-demo-scope)
8. [Dummy Data](#8-dummy-data)

---

## 1. PROJECT OVERVIEW

### 1.1 Background

PT Komitrando Emporio adalah perusahaan manufaktur yang bergerak di bidang industri pembuatan tas untuk merek internasional. Produk yang dihasilkan meliputi:

- Handbags (Tas Tangan)
- Sports Bags (Tas Olahraga)
- Backpacks (Ransel)
- etc

Produk di-export ke berbagai negara, terutama **Amerika Serikat** dan **Kanada**.

Perusahaan memiliki **2 PT**:

- **PT Komitrando Emporio** (Main - Production)
- **PT Komitrando Textile** (Branch - Warehouse)

### 1.2 Project Objectives

1. Digitalisasi dan sentralisasi alur kerja operasional
2. Efisiensi operasional dan kurangi proses manual
3. Visibilitas dan pelaporan real-time
4. Kurangi human error dan duplikasi data
5. Skalabilitas bisnis dan kepatuhan regulasi (CEISA)

### 1.3 Project Scope

- **Phase 1:** Core Foundation & Pre-Production (Fully Functional)
- **Phase 2:** Advanced Production (Simplified - UI + Static Data)
- **Phase 3:** Finance (Simplified - UI + Static Data)
- **Phase 4:** CEISA Integration (Future - Not in Demo)

---

## 2. TECHNICAL SPECIFICATION

### 2.1 Technology Stack

| Component   | Technology             | Version |
| ----------- | ---------------------- | ------- |
| Framework   | Laravel                | 12.x    |
| Admin Panel | Filament PHP           | 4.x     |
| Frontend    | Blade + Tailwind CSS   | Latest  |
| Database    | MySQL                  | 8.0+    |
| PHP Version | PHP                    | 8.3+    |
| Server      | Local Laptop (Laragon) | 6.0+    |

### 2.2 System Requirements

- **OS:** Windows 11
- **RAM:** Minimum 8GB
- **Storage:** Minimum 20GB free space
- **PHP:** 8.3 or higher
- **MySQL:** 8.0 or higher

### 2.3 Configuration

```php
// Environment Configuration
APP_NAME="ERP Komi"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_komi
DB_USERNAME=root
DB_PASSWORD=
```

---

## 3. SYSTEM ARCHITECTURE

### 3.1 Login Flow (Multi-Company Wrapper)

```
┌─────────────────────────────────────────────────────────────────────┐
│                         LOGIN                                       │
│                           │                                         │
│                           ▼                                         │
│  ┌────────────────────────────────────────────────────┐             │
│  │           COMPANY SELECTION / CREATION             │  ← WAJIB    │
│  │                                                    │             │
│  │   [Select Company Dropdown]  [Create New Company]  │             │
│  │                                                    │             │
│  └────────────────────────────────────────────────────┘             │
│                           │                                         │
│                           ▼                                         │
│  ┌──────────────────────────────────────────────────────┐           │
│  │              MAIN DASHBOARD                          │           │
│  │         (All Data Scoped by Company)                 │           │
│  │                                                      │           │
│  │   All modules below will filter data by company      │           │
│  │   User has full access (Owner role - no restrictions)│           │
│  └──────────────────────────────────────────────────────┘           │
└─────────────────────────────────────────────────────────────────────┘
```

### 3.2 Module Structure by Phase

```
ERP KOMI SYSTEM
│
├── PHASE 1 - PRE-PRODUCTION (FULLY FUNCTIONAL)
│   │
│   ├── 1. R&D / Consumption Module
│   ├── 2. Project Initiation Module
│   ├── 3. Merchandising Module
│   ├── 4. Costing / Pricing Module
│   ├── 5. Sales Order Module
│   ├── 6. Purchase Order Module
│   ├── 7. Purchase Tracking Module
│   ├── 8. Goods Receipt Module
│   ├── 9. Inventory Module
│   └── 10. Invoice Module (Purchase + Sales with Tax Invoice)
│
├── PHASE 2 - PRODUCTION + ADVANCED (SIMPLIFIED - UI + Static Data)
│   │
│   ├── Production Order (UI + sample data)
│   ├── Job Order (JO) - UI only
│   ├── SPP (Surat Perintah Produksi) - UI only
│   ├── Material Usage & Waste Report (calculated display)
│   ├── Assign User / Lead Task (UI + static data)
│   ├── QC Management (UI + static data)
│   ├── QC Parameters (UI concept only)
│   ├── Enhanced Project Fields (Color/Size/Brand/Collection)
│   └── Enhanced Shipment Tracking (UI concept only)
│
├── PHASE 3 - FINANCE (SIMPLIFIED - UI + Static Data)
│   │
│   ├── Payment Tracking (History pembayaran)
│   ├── Chart of Accounts (COA) - Structure display
│   ├── General Ledger - Sample entries
│   └── L/R Report (Profit/Loss) - Calculated display
│
└── PHASE 4 - CEISA INTEGRATION (FUTURE)
    │
    ├── CEISA API Integration
    ├── Customs Reporting
    └── Compliance Management
```

---

## 4. MODULE SPECIFICATIONS

---

### MODULE 1: COMPANY SELECTION (Login Gate)

**Purpose:** Multi-company wrapper untuk scoped access

**Fields:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | BigInt | Yes | Primary Key |
| code | String(50) | Yes | Company code (e.g., "KOMIT001") |
| name | String(255) | Yes | Company name |
| address | Text | No | Company address |
| phone | String(50) | No | Contact phone |
| email | String(100) | No | Contact email |
| is_active | Boolean | Yes | Default: true |
| created_at | Timestamp | Yes | Auto |
| updated_at | Timestamp | Yes | Auto |

**Features:**

- Select existing company from dropdown
- Create new company (Owner only)
- All subsequent data filtered by selected company
- Session persisted until logout/change company

**Demo Data:**
| Code | Name | Type |
|------|------|------|
| KMT001 | PT Komitrando Emporio | Main (Production) |
| KMT002 | PT Komitrando Textile | Branch (Warehouse) |

---

### MODULE 2: R&D / CONSUMPTION MODULE

**Purpose:** Research & Development data, BOM management, material specifications

**A. Design Library (Sub-Module)**

| Field           | Type        | Required | Description                                |
| --------------- | ----------- | -------- | ------------------------------------------ |
| id              | BigInt      | Yes      | Primary Key                                |
| company_id      | BigInt      | Yes      | FK to companies                            |
| name            | String(255) | Yes      | Design name                                |
| bag_type        | Enum        | Yes      | handbag/sports_bag/backpack/messenger/tote |
| description     | Text        | No       | Design description                         |
| reference_image | File        | No       | Reference images                           |
| tech_pack       | File        | No       | Technical specifications                   |
| brand           | String(100) | No       | Brand reference                            |
| size_range      | String(100) | No       | e.g., "S, M, L, XL"                        |
| status          | Enum        | Yes      | draft/active/discontinued                  |
| created_at      | Timestamp   | Yes      | Auto                                       |
| updated_at      | Timestamp   | Yes      | Auto                                       |

**Bag Types:**

- Handbag
- Sports Bag
- Backpack
- Messenger Bag
- Tote Bag

**B. BOM (Bill of Materials)**

| Field      | Type        | Required | Description          |
| ---------- | ----------- | -------- | -------------------- |
| id         | BigInt      | Yes      | Primary Key          |
| company_id | BigInt      | Yes      | FK to companies      |
| design_id  | BigInt      | Yes      | FK to design_library |
| version    | String(20)  | Yes      | e.g., "v1.0"         |
| name       | String(255) | Yes      | BOM name             |
| status     | Enum        | Yes      | draft/active         |
| notes      | Text        | No       | Additional notes     |
| created_at | Timestamp   | Yes      | Auto                 |
| updated_at | Timestamp   | Yes      | Auto                 |

**BOM Items Table:**

| Field             | Type          | Required | Description                           |
| ----------------- | ------------- | -------- | ------------------------------------- |
| id                | BigInt        | Yes      | Primary Key                           |
| bom_id            | BigInt        | Yes      | FK to boms                            |
| material_id       | BigInt        | Yes      | FK to materials                       |
| category          | Enum          | Yes      | main_material/hardware/trim/packaging |
| quantity_per_unit | Decimal(10,3) | Yes      | Consumption per unit                  |
| unit              | String(20)    | Yes      | yard/meter/pcs/unit                   |
| wastage_percent   | Decimal(5,2)  | No       | Default: 0                            |
| notes             | String(255)   | No       | Additional notes                      |

**C. Consumption Rates**

| Field         | Type          | Required | Description                     |
| ------------- | ------------- | -------- | ------------------------------- |
| id            | BigInt        | Yes      | Primary Key                     |
| company_id    | BigInt        | Yes      | FK to companies                 |
| material_id   | BigInt        | Yes      | FK to materials                 |
| design_id     | BigInt        | No       | FK to design_library (optional) |
| standard_rate | Decimal(10,3) | Yes      | Standard usage per unit         |
| unit          | String(20)    | Yes      | yard/meter/pcs                  |
| wastage_rate  | Decimal(5,2)  | No       | Default: 5%                     |
| notes         | Text          | No       | Notes                           |

**Features:**

- Create/Edit Design Library entries
- Create BOM with multiple items
- Set consumption rates per material per design
- Import/Export BOM data (Excel)
- Version control for BOM

**Demo Data:**

- 5 Designs (1 each bag type + 1 extra)
- 5 BOMs with items
- 17 Consumption rates (matching materials)

---

### MODULE 3: PROJECT INITIATION MODULE

**Purpose:** Create and manage projects (Proto/Sample/Mass)

**Project Table:**

| Field                | Type        | Required | Description                                               |
| -------------------- | ----------- | -------- | --------------------------------------------------------- |
| id                   | BigInt      | Yes      | Primary Key                                               |
| company_id           | BigInt      | Yes      | FK to companies                                           |
| project_code         | String(50)  | Yes      | Auto-generate: PRJ-001-2026                               |
| name                 | String(255) | Yes      | Project name                                              |
| project_type         | Enum        | Yes      | proto/sample/mass                                         |
| status               | Enum        | Yes      | draft/pre_production/production/post_production/completed |
| customer_id          | BigInt      | No       | FK to customers                                           |
| design_id            | BigInt      | No       | FK to design_library (optional)                           |
| bom_id               | BigInt      | No       | FK to boms (optional)                                     |
| reference_project_id | BigInt      | No       | FK to self (for mass referencing sample)                  |
| quantity             | Integer     | No       | Planned quantity                                          |
| target_date          | Date        | No       | Target completion date                                    |
| notes                | Text        | No       | Additional notes                                          |
| approved_at          | Timestamp   | No       | When project is approved                                  |
| approved_by          | BigInt      | No       | FK to users                                               |
| created_at           | Timestamp   | Yes      | Auto                                                      |
| updated_at           | Timestamp   | Yes      | Auto                                                      |

**Project Type Transition:**

```
┌─────────────────────────────────────────────────────────────────────────┐
│ PROJECT TYPE TRANSITIONS                                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  PROTO ──[ Approve ]─────────────────────▶ AUTO-CREATE SAMPLE           │
│   │                                             │                       │
│   │                                             ├── Copy Project        │
│   │                                             ├── Copy Merchandising  │
│   │                                             ├── Copy PO             │
│   │                                             ├── Copy Production     │
│   │                                             ├── Copy QC             │
│   │                                             ├── Copy Invoice        │
│   │                                             └── ALL DATA = EDITABLE │
│   │                                                                     │
│   └── [Duplicate Button] ──▶ New Proto (copy all, editable)             │
│       (for repeat order next year)                                      │
│                                                                         │
│  SAMPLE ──[ Approve ]───────────────────▶ AUTO-CREATE MASS              │
│   │                                             │                       │
│   │                                             ├── Same as above +     │
│   │                                             │   Copy Shipment       │
│   │                                             └── ALL DATA = EDITABLE │
│   │                                                                     │
│   └── [Duplicate Button] ──▶ New Sample (copy all, editable)            │
│                                                                         │
│  (!) RULES:                                                             │
│  ├── Approve = auto-create next type (Proto→Sample, Sample→Mass)        │
│  ├── Duplicate = create new project same type (for repeat orders)       │
│  ├── All copied data remains EDITABLE                                   │
│  └── Each type can have independent data versions                       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

**Features:**

- Auto-generate project code
- Select project type (Proto/Sample/Mass)
- Import BOM from R&D module
- Reference existing project (for similar projects)
- Workflow status tracking
- Project timeline visualization

**Demo Data:**
| Code | Type | Name | Status |
|------|------|------|--------|
| PRJ-001-2026 | Proto | Explorer Backpack Pro | In Progress |
| PRJ-002-2026 | Proto | Urban Handbag Series | Draft |
| PRJ-003-2026 | Sample | Sport Duffle Bag Premium | Sample Stage |
| PRJ-004-2026 | Mass | Corporate Laptop Backpack | Mass Stage |
| PRJ-005-2026 | Mass | Travel Messenger Bag | Completed |

---

### MODULE 4: MERCHANDISING MODULE

**Purpose:** Material planning, supplier assignment, subcon assignment

**Merchandise Planning Table:**

| Field               | Type          | Required | Description             |
| ------------------- | ------------- | -------- | ----------------------- |
| id                  | BigInt        | Yes      | Primary Key             |
| company_id          | BigInt        | Yes      | FK to companies         |
| project_id          | BigInt        | Yes      | FK to projects          |
| planning_date       | Date          | Yes      | Planning date           |
| status              | Enum          | Yes      | draft/planning/approved |
| total_material_cost | Decimal(15,2) | No       | Auto-calculate          |
| total_subcon_cost   | Decimal(15,2) | No       | Auto-calculate          |
| notes               | Text          | No       | Additional notes        |
| created_at          | Timestamp     | Yes      | Auto                    |
| updated_at          | Timestamp     | Yes      | Auto                    |

**Merchandise Planning Items:**

| Field            | Type          | Required | Description                 |
| ---------------- | ------------- | -------- | --------------------------- |
| id               | BigInt        | Yes      | Primary Key                 |
| merchandising_id | BigInt        | Yes      | FK to merchandise_plannings |
| material_id      | BigInt        | Yes      | FK to materials             |
| supplier_id      | BigInt        | No       | FK to suppliers             |
| subcon_id        | BigInt        | No       | FK to subcontractors        |
| planned_qty      | Decimal(10,3) | Yes      | Planned quantity            |
| unit             | String(20)    | Yes      | Unit of measurement         |
| unit_price       | Decimal(12,2) | No       | Price per unit              |
| total_price      | Decimal(15,2) | No       | Auto-calculate              |
| is_subcon        | Boolean       | Yes      | Default: false              |
| notes            | String(255)   | No       | Notes                       |

**Features:**

- Create merchandise planning per project
- Auto-calculate total costs from BOM consumption
- Assign supplier or subcon to each item
- Generate PO from merchandise planning
- Calculate total project cost

---

### MODULE 5: COSTING / PRICING MODULE

**Purpose:** Calculate selling price based on all costs

**Costing Table:**

| Field        | Type       | Required | Description          |
| ------------ | ---------- | -------- | -------------------- |
| id           | BigInt     | Yes      | Primary Key          |
| company_id   | BigInt     | Yes      | FK to companies      |
| project_id   | BigInt     | Yes      | FK to projects       |
| version      | String(20) | Yes      | e.g., "v1.0"         |
| status       | Enum       | Yes      | draft/approved/final |
| costing_date | Date       | Yes      | Costing date         |

**Cost Breakdown:**

| Field       | Type          | Required | Description                                 |
| ----------- | ------------- | -------- | ------------------------------------------- |
| id          | BigInt        | Yes      | Primary Key                                 |
| costing_id  | BigInt        | Yes      | FK to costings                              |
| cost_type   | Enum          | Yes      | material/man_power/overhead/shipping/profit |
| description | String(255)   | Yes      | Cost description                            |
| amount      | Decimal(15,2) | Yes      | Cost amount                                 |
| percentage  | Decimal(5,2)  | No       | Percentage (for markup)                     |
| notes       | Text          | No       | Notes                                       |

**Cost Calculation Formula:**

```
Selling Price = Material Cost + Man Power Cost + Overhead + Shipping + Profit Margin

Where:
- Material Cost = sum(material_qty × unit_price) from BOM
- Man Power Cost = static rate per unit (from config)
- Overhead = (Material + MP) × overhead_percentage
- Shipping = estimated shipping cost to customer
- Profit Margin = subtotal × profit_percentage
```

**Man Power Config (Static):**

| Role         | Rate per Unit | Unit     |
| ------------ | ------------- | -------- |
| Cutting      | Rp 5,000      | per unit |
| Sewing       | Rp 15,000     | per unit |
| Finishing    | Rp 8,000      | per unit |
| QC           | Rp 3,000      | per unit |
| Packing      | Rp 2,000      | per unit |
| **Total MP** | **Rp 33,000** | per unit |

**Shipping Estimation:**

| Destination        | Cost per Unit |
| ------------------ | ------------- |
| Jakarta            | Rp 5,000      |
| Jawa (non-Jakarta) | Rp 8,000      |
| Luar Jawa          | Rp 12,000     |
| Export (US/Canada) | Rp 35,000     |

**Overhead & Profit Config:**
| Item | Percentage |
|------|------------|
| Overhead | 15% |
| Profit Margin | 20% |

**Features:**

- Auto-calculate from BOM consumption
- Manual adjustment option
- Multiple version support
- Export costing report
- Price lock (for approved costing)

---

### MODULE 6: SALES ORDER MODULE

**Purpose:** Create sales order to customer based on costing

**Sales Order Table:**

| Field         | Type          | Required | Description                                            |
| ------------- | ------------- | -------- | ------------------------------------------------------ |
| id            | BigInt        | Yes      | Primary Key                                            |
| company_id    | BigInt        | Yes      | FK to companies                                        |
| so_number     | String(50)    | Yes      | Auto-generate: SO-2026-001                             |
| project_id    | BigInt        | Yes      | FK to projects                                         |
| customer_id   | BigInt        | Yes      | FK to customers                                        |
| costing_id    | BigInt        | Yes      | FK to costings                                         |
| order_date    | Date          | Yes      | Order date                                             |
| delivery_date | Date          | No       | Expected delivery                                      |
| status        | Enum          | Yes      | draft/confirmed/production/shipped/completed/cancelled |
| quantity      | Integer       | Yes      | Order quantity                                         |
| unit_price    | Decimal(15,2) | Yes      | Price per unit                                         |
| total_amount  | Decimal(15,2) | Yes      | Auto-calculate                                         |
| ppn_percent   | Decimal(5,2)  | No       | Default: 10%                                           |
| ppn_amount    | Decimal(15,2) | No       | Auto-calculate                                         |
| grand_total   | Decimal(15,2) | No       | Auto-calculate                                         |
| shipping_cost | Decimal(15,2) | No       | Shipping cost                                          |
| notes         | Text          | No       | Additional notes                                       |
| created_at    | Timestamp     | Yes      | Auto                                                   |
| updated_at    | Timestamp     | Yes      | Auto                                                   |

**Sales Order Items:**

| Field          | Type          | Required | Description        |
| -------------- | ------------- | -------- | ------------------ |
| id             | BigInt        | Yes      | Primary Key        |
| sales_order_id | BigInt        | Yes      | FK to sales_orders |
| description    | String(255)   | Yes      | Item description   |
| quantity       | Integer       | Yes      | Item quantity      |
| unit           | String(20)    | Yes      | Unit               |
| unit_price     | Decimal(15,2) | Yes      | Price per unit     |
| total_price    | Decimal(15,2) | No       | Auto-calculate     |

**Features:**

- Generate from project + costing
- Apply PPn (Pajak/PPN 10%)
- Add shipping cost
- Track order status
- Generate invoice

**Demo Data:**

- 4 Sales Orders (matching customer projects)

---

### MODULE 7: PURCHASE ORDER MODULE

**Purpose:** Create PO to supplier and subcon

**A. PO Supplier (Material)**

| Field         | Type          | Required | Description                                           |
| ------------- | ------------- | -------- | ----------------------------------------------------- |
| id            | BigInt        | Yes      | Primary Key                                           |
| company_id    | BigInt        | Yes      | FK to companies                                       |
| po_number     | String(50)    | Yes      | Auto-generate: PO-SUP-2026-001                        |
| project_id    | BigInt        | No       | FK to projects                                        |
| supplier_id   | BigInt        | Yes      | FK to suppliers                                       |
| po_date       | Date          | Yes      | PO date                                               |
| delivery_date | Date          | No       | Expected delivery                                     |
| status        | Enum          | Yes      | draft/confirmed/partially_received/received/cancelled |
| subtotal      | Decimal(15,2) | No       | Auto-calculate                                        |
| ppn_percent   | Decimal(5,2)  | No       | Default: 0 or 11%                                     |
| ppn_amount    | Decimal(15,2) | No       | Auto-calculate                                        |
| grand_total   | Decimal(15,2) | No       | Auto-calculate                                        |
| notes         | Text          | No       | Additional notes                                      |
| created_at    | Timestamp     | Yes      | Auto                                                  |
| updated_at    | Timestamp     | Yes      | Auto                                                  |

**PO Supplier Items:**

| Field          | Type          | Required | Description        |
| -------------- | ------------- | -------- | ------------------ |
| id             | BigInt        | Yes      | Primary Key        |
| po_supplier_id | BigInt        | Yes      | FK to po_suppliers |
| material_id    | BigInt        | Yes      | FK to materials    |
| description    | String(255)   | No       | Item description   |
| qty            | Decimal(10,3) | Yes      | Order quantity     |
| unit           | String(20)    | Yes      | Unit               |
| unit_price     | Decimal(12,2) | Yes      | Price per unit     |
| total_price    | Decimal(15,2) | No       | Auto-calculate     |
| qty_received   | Decimal(10,3) | No       | Track received qty |

**B. PO Subcon (Jasa + Ongkir - NO Material Cost)**

| Field                | Type          | Required | Description                                    |
| -------------------- | ------------- | -------- | ---------------------------------------------- |
| id                   | BigInt        | Yes      | Primary Key                                    |
| company_id           | BigInt        | Yes      | FK to companies                                |
| po_number            | String(50)    | Yes      | Auto-generate: PO-SUBCON-2026-001              |
| project_id           | BigInt        | No       | FK to projects                                 |
| subcon_id            | BigInt        | Yes      | FK to subcontractors                           |
| po_date              | Date          | Yes      | PO date                                        |
| delivery_date        | Date          | No       | Expected delivery                              |
| status               | Enum          | Yes      | draft/confirmed/in_process/completed/cancelled |
| service_cost         | Decimal(15,2) | Yes      | Service fee (jasa)                             |
| shipping_cost        | Decimal(15,2) | No       | Shipping cost outbound                         |
| shipping_return_cost | Decimal(15,2) | No       | Shipping cost return                           |
| total_cost           | Decimal(15,2) | No       | Auto-calculate                                 |
| notes                | Text          | No       | Additional notes                               |
| created_at           | Timestamp     | Yes      | Auto                                           |
| updated_at           | Timestamp     | Yes      | Auto                                           |

**PO Subcon Items:**

| Field        | Type          | Required | Description         |
| ------------ | ------------- | -------- | ------------------- |
| id           | BigInt        | Yes      | Primary Key         |
| po_subcon_id | BigInt        | Yes      | FK to po_subcons    |
| description  | String(255)   | Yes      | Service description |
| qty          | Decimal(10,3) | Yes      | Service quantity    |
| unit_price   | Decimal(12,2) | Yes      | Price per unit      |
| total_price  | Decimal(15,2) | No       | Auto-calculate      |

**Features:**

- Generate from Merchandise Planning
- Track delivery status
- Partial receipt support
- PO history per project

---

### MODULE 8: PURCHASE TRACKING MODULE

**Purpose:** Track PO status from creation to delivery

**Purchase Tracking Table:**

| Field             | Type      | Required | Description                                                  |
| ----------------- | --------- | -------- | ------------------------------------------------------------ |
| id                | BigInt    | Yes      | Primary Key                                                  |
| company_id        | BigInt    | Yes      | FK to companies                                              |
| po_type           | Enum      | Yes      | supplier/subcon                                              |
| po_id             | BigInt    | Yes      | FK to PO table                                               |
| tracking_status   | Enum      | Yes      | ordered/confirmed/in_transit/partial_received/fully_received |
| estimated_arrival | Date      | No       | ETA                                                          |
| actual_arrival    | Date      | No       | Actual arrival date                                          |
| notes             | Text      | No       | Tracking notes                                               |
| created_at        | Timestamp | Yes      | Auto                                                         |
| updated_at        | Timestamp | Yes      | Auto                                                         |

**Tracking Timeline:**

```
ORDERED ──▶ CONFIRMED ──▶ IN_TRANSIT ──▶ PARTIAL_RECEIVED ──▶ FULLY_RECEIVED
   │           │              │                │                  │
   │           │              │                │                  └── GR created
   │           │              │                └── Multiple GR possible
   │           │              └── Tracking number added
   │           └── Supplier confirms PO
   └── PO created
```

**Features:**

- View all pending POs
- Update tracking status
- Add tracking notes
- Filter by supplier/subcon/status
- Export tracking report

---

### MODULE 9: GOODS RECEIPT MODULE

**Purpose:** Receive material from supplier, update inventory

**Goods Receipt Table:**

| Field        | Type       | Required | Description                     |
| ------------ | ---------- | -------- | ------------------------------- |
| id           | BigInt     | Yes      | Primary Key                     |
| company_id   | BigInt     | Yes      | FK to companies                 |
| gr_number    | String(50) | Yes      | Auto-generate: GR-2026-001      |
| po_type      | Enum       | Yes      | supplier/subcon                 |
| po_id        | BigInt     | Yes      | FK to PO table                  |
| supplier_id  | BigInt     | Yes      | FK to suppliers                 |
| receipt_date | Date       | Yes      | Receipt date                    |
| warehouse_id | BigInt     | Yes      | FK to warehouses                |
| status       | Enum       | Yes      | draft/received/partial/verified |
| notes        | Text       | No       | Additional notes                |
| created_at   | Timestamp  | Yes      | Auto                            |
| updated_at   | Timestamp  | Yes      | Auto                            |

**Goods Receipt Items:**

| Field            | Type          | Required | Description           |
| ---------------- | ------------- | -------- | --------------------- |
| id               | BigInt        | Yes      | Primary Key           |
| goods_receipt_id | BigInt        | Yes      | FK to goods_receipts  |
| material_id      | BigInt        | Yes      | FK to materials       |
| po_item_id       | BigInt        | No       | FK to PO items        |
| qty              | Decimal(10,3) | Yes      | Received quantity     |
| unit             | String(20)    | Yes      | Unit                  |
| unit_price       | Decimal(12,2) | No       | Price (from PO)       |
| total_price      | Decimal(15,2) | No       | Auto-calculate        |
| condition        | Enum          | Yes      | good/damaged/rejected |
| notes            | String(255)   | No       | Notes                 |

**Goods Receipt - Shipping Cost:**

| Field            | Type          | Required | Description           |
| ---------------- | ------------- | -------- | --------------------- |
| id               | BigInt        | Yes      | Primary Key           |
| goods_receipt_id | BigInt        | Yes      | FK to goods_receipts  |
| shipping_cost    | Decimal(15,2) | Yes      | Inbound shipping cost |
| carrier          | String(100)   | No       | Shipping company      |
| tracking_number  | String(100)   | No       | Tracking number       |
| notes            | Text          | No       | Notes                 |

**Retur Handling:**

| Field            | Type          | Required | Description               |
| ---------------- | ------------- | -------- | ------------------------- |
| id               | BigInt        | Yes      | Primary Key               |
| goods_receipt_id | BigInt        | Yes      | FK to goods_receipts      |
| material_id      | BigInt        | Yes      | FK to materials           |
| qty              | Decimal(10,3) | Yes      | Return quantity           |
| reason           | String(255)   | Yes      | Return reason             |
| status           | Enum          | Yes      | pending/approved/returned |
| created_at       | Timestamp     | Yes      | Auto                      |

**Features:**

- Receive material against PO
- QC check on arrival
- Update inventory automatically
- Handle retur for damaged items
- Track shipping cost (for invoice purchase)

---

### MODULE 10: INVENTORY MODULE

**Purpose:** Material stock management, warehouse tracking, subcon material tracking

**A. Material Stock (per Company + Warehouse)**

| Field        | Type          | Required | Description         |
| ------------ | ------------- | -------- | ------------------- |
| id           | BigInt        | Yes      | Primary Key         |
| company_id   | BigInt        | Yes      | FK to companies     |
| warehouse_id | BigInt        | Yes      | FK to warehouses    |
| material_id  | BigInt        | Yes      | FK to materials     |
| qty          | Decimal(10,3) | Yes      | Current stock       |
| unit         | String(20)    | Yes      | Unit                |
| min_stock    | Decimal(10,3) | No       | Minimum stock alert |
| location     | String(50)    | No       | Storage location    |
| last_updated | Timestamp     | Yes      | Auto                |
| created_at   | Timestamp     | Yes      | Auto                |
| updated_at   | Timestamp     | Yes      | Auto                |

**B. Warehouse Table:**

| Field      | Type        | Required | Description     |
| ---------- | ----------- | -------- | --------------- |
| id         | BigInt      | Yes      | Primary Key     |
| company_id | BigInt      | Yes      | FK to companies |
| code       | String(50)  | Yes      | Warehouse code  |
| name       | String(255) | Yes      | Warehouse name  |
| address    | Text        | No       | Address         |
| is_active  | Boolean     | Yes      | Default: true   |

**C. Subcon Material Tracking**

**Material OUT to Subcon:**

| Field            | Type       | Required | Description              |
| ---------------- | ---------- | -------- | ------------------------ |
| id               | BigInt     | Yes      | Primary Key              |
| company_id       | BigInt     | Yes      | FK to companies          |
| subcon_id        | BigInt     | Yes      | FK to subcontractors     |
| project_id       | BigInt     | No       | FK to projects           |
| po_subcon_id     | BigInt     | Yes      | FK to po_subcons         |
| job_order_number | String(50) | Yes      | Job order number         |
| out_date         | Date       | Yes      | Date sent to subcon      |
| status           | Enum       | Yes      | sent/in_process/returned |
| notes            | Text       | No       | Notes                    |
| created_at       | Timestamp  | Yes      | Auto                     |

**Subcon Material OUT Items:**

| Field         | Type          | Required | Description                |
| ------------- | ------------- | -------- | -------------------------- |
| id            | BigInt        | Yes      | Primary Key                |
| subcon_out_id | BigInt        | Yes      | FK to subcon_material_outs |
| material_id   | BigInt        | Yes      | FK to materials            |
| qty_sent      | Decimal(10,3) | Yes      | Quantity sent              |
| unit          | String(20)    | Yes      | Unit                       |

**Material IN from Subcon (Return):**

| Field         | Type      | Required | Description                |
| ------------- | --------- | -------- | -------------------------- |
| id            | BigInt    | Yes      | Primary Key                |
| company_id    | BigInt    | Yes      | FK to companies            |
| subcon_out_id | BigInt    | Yes      | FK to subcon_material_outs |
| return_date   | Date      | Yes      | Date returned              |
| status        | Enum      | Yes      | partial_return/full_return |
| notes         | Text      | No       | Notes                      |
| created_at    | Timestamp | Yes      | Auto                       |

**Subcon Material IN Items:**

| Field        | Type          | Required | Description               |
| ------------ | ------------- | -------- | ------------------------- |
| id           | BigInt        | Yes      | Primary Key               |
| subcon_in_id | BigInt        | Yes      | FK to subcon_material_ins |
| material_id  | BigInt        | Yes      | FK to materials           |
| qty_returned | Decimal(10,3) | Yes      | Quantity returned         |
| condition    | Enum          | Yes      | good/damaged/missing      |
| notes        | String(255)   | No       | Notes                     |

**Stock Movement History:**

| Field          | Type          | Required | Description                                              |
| -------------- | ------------- | -------- | -------------------------------------------------------- |
| id             | BigInt        | Yes      | Primary Key                                              |
| company_id     | BigInt        | Yes      | FK to companies                                          |
| material_id    | BigInt        | Yes      | FK to materials                                          |
| movement_type  | Enum          | Yes      | purchase/production/subcon_out/subcon_in/sale/adjustment |
| reference_type | String(50)    | Yes      | e.g., "goods_receipt", "subcon_material_out"             |
| reference_id   | BigInt        | Yes      | FK to reference table                                    |
| qty_change     | Decimal(10,3) | Yes      | Positive or negative                                     |
| qty_before     | Decimal(10,3) | Yes      | Stock before                                             |
| qty_after      | Decimal(10,3) | Yes      | Stock after                                              |
| notes          | Text          | No       | Notes                                                    |
| created_at     | Timestamp     | Yes      | Auto                                                     |

**Features:**

- View stock per material per warehouse
- Low stock alert (based on min_stock)
- Stock movement history
- Subcon material tracking (OUT/IN)
- Stock opname entry
- Transfer between warehouses

**Demo Data:**

- 2 Warehouses (Main, Branch)
- Stock entries for 17 materials

---

### MODULE 10: SHIPMENT MODULE (Post-Production - Simplified)

**Purpose:** Sample/Mass shipment, Packing List, Delivery Order

**Note:** This is a post-production module. Full functionality is included in Phase 1 for demo purposes.

**A. Shipment:**

| Field           | Type        | Required | Description                    |
| --------------- | ----------- | -------- | ------------------------------ |
| id              | BigInt      | Yes      | Primary Key                    |
| company_id      | BigInt      | Yes      | FK to companies                |
| shipment_number | String(50)  | Yes      | Auto-generate: SHIP-2026-001   |
| project_id      | BigInt      | Yes      | FK to projects                 |
| sales_order_id  | BigInt      | Yes      | FK to sales_orders             |
| shipment_type   | Enum        | Yes      | sample/mass                    |
| shipment_date   | Date        | Yes      | Shipment date                  |
| destination     | String(255) | Yes      | Delivery address               |
| status          | Enum        | Yes      | draft/packed/shipped/delivered |
| notes           | Text        | No       | Notes                          |
| created_at      | Timestamp   | Yes      | Auto                           |
| updated_at      | Timestamp   | Yes      | Auto                           |

**B. Packing List:**

| Field               | Type          | Required | Description     |
| ------------------- | ------------- | -------- | --------------- |
| id                  | BigInt        | Yes      | Primary Key     |
| shipment_id         | BigInt        | Yes      | FK to shipments |
| packing_list_number | String(50)    | Yes      | Auto-generate   |
| total_boxes         | Integer       | Yes      | Number of boxes |
| total_qty           | Integer       | Yes      | Total quantity  |
| gross_weight        | Decimal(10,2) | No       | Weight in kg    |
| dimensions          | String(100)   | No       | L×W×H per box   |
| created_at          | Timestamp     | Yes      | Auto            |

**Packing List Items:**

| Field           | Type        | Required | Description         |
| --------------- | ----------- | -------- | ------------------- |
| id              | BigInt      | Yes      | Primary Key         |
| packing_list_id | BigInt      | Yes      | FK to packing_lists |
| description     | String(255) | Yes      | Item description    |
| qty             | Integer     | Yes      | Quantity            |
| box_no          | String(20)  | No       | Box number          |
| size            | String(20)  | No       | Size                |
| color           | String(50)  | No       | Color               |
| remarks         | String(255) | No       | Remarks             |

**C. Delivery Order (DO):**

| Field             | Type          | Required | Description                        |
| ----------------- | ------------- | -------- | ---------------------------------- |
| id                | BigInt        | Yes      | Primary Key                        |
| company_id        | BigInt        | Yes      | FK to companies                    |
| do_number         | String(50)    | Yes      | Auto-generate: DO-2026-001         |
| shipment_id       | BigInt        | Yes      | FK to shipments                    |
| do_date           | Date          | Yes      | DO date                            |
| delivery_partner  | String(100)   | No       | Shipping company                   |
| tracking_number   | String(100)   | No       | Tracking number                    |
| estimated_arrival | Date          | No       | ETA                                |
| actual_arrival    | Date          | No       | Actual arrival                     |
| status            | Enum          | Yes      | draft/shipped/in_transit/delivered |
| shipping_cost     | Decimal(15,2) | No       | Shipping cost                      |
| notes             | Text          | No       | Notes                              |
| created_at        | Timestamp     | Yes      | Auto                               |
| updated_at        | Timestamp     | Yes      | Auto                               |

**Features:**

- Create shipment (Sample/Mass)
- Generate packing list
- Generate DO
- Track delivery status
- Phase 2: Show enhanced shipment tracking (UI only)

---

### MODULE 11: INVOICE MODULE

**Purpose:** Purchase and Sales invoice management (including Tax Invoice / Faktur Pajak)

**Note:** Tax Invoice (Faktur Pajak PPN 10%) is automatically generated from Sales Invoice and included in this module.

**A. Invoice Purchase (Money Keluar)**

**Invoice Purchase Table:**

| Field          | Type          | Required | Description                       |
| -------------- | ------------- | -------- | --------------------------------- |
| id             | BigInt        | Yes      | Primary Key                       |
| company_id     | BigInt        | Yes      | FK to companies                   |
| invoice_number | String(50)    | Yes      | Auto-generate                     |
| invoice_type   | Enum          | Yes      | po_supplier/gr_shipping/po_subcon |
| reference_id   | BigInt        | Yes      | FK to source (PO/GR/PO-Subcon)    |
| supplier_id    | BigInt        | Yes      | FK to suppliers                   |
| invoice_date   | Date          | Yes      | Invoice date                      |
| due_date       | Date          | Yes      | Payment due date                  |
| amount         | Decimal(15,2) | Yes      | Invoice amount                    |
| ppn_percent    | Decimal(5,2)  | No       | PPN percentage                    |
| ppn_amount     | Decimal(15,2) | No       | PPN amount                        |
| total_amount   | Decimal(15,2) | No       | Grand total                       |
| status         | Enum          | Yes      | pending/partial_paid/paid/overdue |
| paid_amount    | Decimal(15,2) | No       | Amount paid                       |
| paid_date      | Date          | No       | Date paid                         |
| notes          | Text          | No       | Notes                             |
| created_at     | Timestamp     | Yes      | Auto                              |
| updated_at     | Timestamp     | Yes      | Auto                              |

**Invoice Purchase Types:**

| Type        | Source        | Description                                    |
| ----------- | ------------- | ---------------------------------------------- |
| po_supplier | PO Supplier   | Invoice for material cost                      |
| gr_shipping | Goods Receipt | Invoice for inbound shipping cost              |
| po_subcon   | PO Subcon     | Invoice for service + outbound/return shipping |

**B. Invoice Sales (Money Masuk)**

**Invoice Sales Table:**

| Field           | Type          | Required | Description                            |
| --------------- | ------------- | -------- | -------------------------------------- |
| id              | BigInt        | Yes      | Primary Key                            |
| company_id      | BigInt        | Yes      | FK to companies                        |
| invoice_number  | String(50)    | Yes      | Auto-generate: INV-SALES-2026-001      |
| sales_order_id  | BigInt        | Yes      | FK to sales_orders                     |
| customer_id     | BigInt        | Yes      | FK to customers                        |
| invoice_date    | Date          | Yes      | Invoice date                           |
| due_date        | Date          | Yes      | Payment due date                       |
| product_amount  | Decimal(15,2) | Yes      | Product amount                         |
| shipping_amount | Decimal(15,2) | No       | Shipping cost                          |
| ppn_percent     | Decimal(5,2)  | No       | Default: 10%                           |
| ppn_amount      | Decimal(15,2) | No       | Auto-calculate                         |
| total_amount    | Decimal(15,2) | No       | Grand total                            |
| status          | Enum          | Yes      | draft/issued/partial_paid/paid/overdue |
| paid_amount     | Decimal(15,2) | No       | Amount paid                            |
| paid_date       | Date          | No       | Date paid                              |
| notes           | Text          | No       | Notes                                  |
| created_at      | Timestamp     | Yes      | Auto                                   |
| updated_at      | Timestamp     | Yes      | Auto                                   |

**Invoice Sales Types:**

| Type     | Source         | Description                                                |
| -------- | -------------- | ---------------------------------------------------------- |
| product  | Sales Order    | Invoice for product cost                                   |
| shipping | Delivery Order | Invoice for outbound shipping cost (optional, can combine) |

**C. Payment Tracking:**

**Payment Table:**

| Field          | Type          | Required | Description                    |
| -------------- | ------------- | -------- | ------------------------------ |
| id             | BigInt        | Yes      | Primary Key                    |
| company_id     | BigInt        | Yes      | FK to companies                |
| payment_type   | Enum          | Yes      | invoice_purchase/invoice_sales |
| invoice_id     | BigInt        | Yes      | FK to invoice                  |
| payment_date   | Date          | Yes      | Payment date                   |
| amount         | Decimal(15,2) | Yes      | Payment amount                 |
| payment_method | Enum          | Yes      | cash/transfer/giro             |
| reference      | String(100)   | No       | Payment reference              |
| notes          | Text          | No       | Notes                          |
| created_at     | Timestamp     | Yes      | Auto                           |

**Features:**

- Generate invoice from source documents
- Track payment status
- Payment recording
- Overdue tracking
- Print invoice (PDF)
- Phase 3: Show simplified finance UI with sample data

---

### MODULE 14: MASTER DATA MODULE

**Supporting modules for all operations**

**A. Suppliers:**

| Field          | Type        | Required | Description     |
| -------------- | ----------- | -------- | --------------- |
| id             | BigInt      | Yes      | Primary Key     |
| company_id     | BigInt      | Yes      | FK to companies |
| code           | String(50)  | Yes      | Supplier code   |
| name           | String(255) | Yes      | Supplier name   |
| contact_person | String(100) | No       | Contact name    |
| phone          | String(50)  | No       | Phone           |
| email          | String(100) | No       | Email           |
| address        | Text        | No       | Address         |
| is_active      | Boolean     | Yes      | Default: true   |

**Demo Data:**
| Code | Name | Type |
|------|------|------|
| SUP001 | PT Textile Indonesia | Kain & Fabric |
| SUP002 | CV Benang Jaya | Benang jahit |
| SUP003 | PT Aksesoris Prima | Zipper, Button, Hook |
| SUP004 | UD Kain Maju | Kain khusus |
| SUP005 | PT Elastic Sejahtera | Rubber, Elastic |
| SUP006 | CV Labelbox | Label & Tag |

**B. Subcontractors:**

| Field          | Type        | Required | Description                         |
| -------------- | ----------- | -------- | ----------------------------------- |
| id             | BigInt      | Yes      | Primary Key                         |
| company_id     | BigInt      | Yes      | FK to companies                     |
| code           | String(50)  | Yes      | Subcon code                         |
| name           | String(255) | Yes      | Subcon name                         |
| service_type   | Enum        | Yes      | bordir/permak/cutting/sewing/sablon |
| contact_person | String(100) | No       | Contact name                        |
| phone          | String(50)  | No       | Phone                               |
| email          | String(100) | No       | Email                               |
| address        | Text        | No       | Address                             |
| is_active      | Boolean     | Yes      | Default: true                       |

**Demo Data:**
| Code | Name | Service Type |
|------|------|--------------|
| SUBCON001 | Bordir Indo Makmur | Bordir logo |
| SUBCON002 | Permak Sritex | Sablon & printing |
| SUBCON003 | Jasa Jahit Bersama | Cutting & sewing |

**C. Materials:**

| Field      | Type          | Required | Description                         |
| ---------- | ------------- | -------- | ----------------------------------- |
| id         | BigInt        | Yes      | Primary Key                         |
| company_id | BigInt        | Yes      | FK to companies                     |
| code       | String(50)    | Yes      | Material code                       |
| name       | String(255)   | Yes      | Material name                       |
| category   | Enum          | Yes      | kain/benang/hardware/trim/packaging |
| unit       | String(20)    | Yes      | Default unit                        |
| min_stock  | Decimal(10,3) | No       | Min stock alert                     |
| unit_price | Decimal(12,2) | No       | Default price                       |
| is_active  | Boolean       | Yes      | Default: true                       |

**Demo Data (17 Materials):**

| Code   | Name                  | Category  | Unit  |
| ------ | --------------------- | --------- | ----- |
| MAT001 | Kain Katun Combed 30s | kain      | yard  |
| MAT002 | Kain Polyester DTY    | kain      | yard  |
| MAT003 | Kain TC 65/35         | kain      | yard  |
| MAT004 | Kain Ripstop          | kain      | yard  |
| MAT005 | Kain Denim            | kain      | yard  |
| MAT006 | Benang Polyester 150s | benang    | spool |
| MAT007 | Benang Cotton 40s     | benang    | spool |
| MAT008 | Zipper YKK 25cm       | hardware  | pcs   |
| MAT009 | Zipper YKK 45cm       | hardware  | pcs   |
| MAT010 | Button Plastik 20mm   | hardware  | pcs   |
| MAT011 | Interlining KC 303    | trim      | yard  |
| MAT012 | Rubber Strip 2cm      | trim      | yard  |
| MAT013 | Label Dalam - Woven   | trim      | pcs   |
| MAT014 | Label Ukuran          | trim      | pcs   |
| MAT015 | Tag Harga Karton      | packaging | pcs   |
| MAT016 | Plastic Bag           | packaging | pcs   |
| MAT017 | Tissue Paper          | packaging | pcs   |

**D. Customers:**

| Field          | Type        | Required | Description          |
| -------------- | ----------- | -------- | -------------------- |
| id             | BigInt      | Yes      | Primary Key          |
| company_id     | BigInt      | Yes      | FK to companies      |
| code           | String(50)  | Yes      | Customer code        |
| name           | String(255) | Yes      | Customer name        |
| contact_person | String(100) | No       | Contact name         |
| phone          | String(50)  | No       | Phone                |
| email          | String(100) | No       | Email                |
| address        | Text        | No       | Address              |
| country        | String(100) | No       | Country (for export) |
| payment_terms  | String(100) | No       | e.g., "Net 30"       |
| is_active      | Boolean     | Yes      | Default: true        |

**Demo Data:**
| Code | Name | Country |
|------|------|---------|
| CUST001 | PT Astra International | Indonesia |
| CUST002 | PT Bank Nusantara | Indonesia |
| CUST003 | CV OutdoorGear Indonesia | Indonesia |
| CUST004 | RS Medika Utama | Indonesia |

---

## 5. DATA MODELS

### 5.1 Entity Relationship Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                           COMPANIES                                 │
│                    (Multi-Company Wrapper)                          │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │                                                                │ │
│  │    ┌─────────────┐    ┌─────────────┐    ┌──────────────┐      │ │
│  │    │  PROJECTS   │    │ SUPPLIERS   │    │SUBCONTRACTORS│      │ │
│  │    └──────┬──────┘    └─────────────┘    └──────────────┘      │ │
│  │           │                                                    │ │
│  │    ┌──────┴──────┐                                             │ │
│  │    │             │                                             │ │
│  │    ▼             ▼                                             │ │
│  │ ┌──────────┐ ┌──────────────┐                                  │ │
│  │ │  MERCH   │ │  SALES ORDER │                                  │ │
│  │ └────┬─────┘ └──────┬───────┘                                  │ │
│  │      │              │                                          │ │
│  │      ▼              ▼                                          │ │
│  │ ┌────────┐   ┌────────────┐                                    │ │
│  │ │   PO   │   │  COSTING   │                                    │ │
│  │ │SUPPLIER│   └────────────┘                                    │ │
│  │ └────┬───┘                                                     │ │
│  │      │                                                         │ │
│  │      ▼                                                         │ │
│  │ ┌───────────┐  ┌───────────┐  ┌───────────┐                    │ │
│  │ │    GR     │  │ INVENTORY │  │  SUBCON   │                    │ │
│  │ │(Material) │──│  (Stock)  │◄─│ MATERIAL  │                    │ │
│  │ └───────────┘  └───────────┘  └───────────┘                    │ │
│  │                                                                │ │
│  │ ┌───────────┐  ┌───────────┐  ┌───────────┐                    │ │
│  │ │ PRODUCTION│  │  SHIPMENT │  │ INVOICES  │                    │ │
│  │ │    (QC)   │──│   (DO)    │  │ (Purchase/│                    │ │
│  │ └───────────┘  └───────────┘  │  Sales)   │                    │ │
│  │                               └───────────┘                    │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

### 5.2 Database Schema (Key Tables)

```sql
-- Companies (Multi-tenant)
companies
├── id (PK)
├── code
├── name
├── address
├── phone
├── email
├── is_active
├── created_at
└── updated_at

-- Design Library (R&D)
design_library
├── id (PK)
├── company_id (FK)
├── name
├── bag_type (enum: handbag, sports_bag, backpack, messenger, tote)
├── description
├── reference_image
├── tech_pack
├── brand
├── size_range
├── status (enum: draft, active, discontinued)
├── created_at
└── updated_at

-- Bill of Materials
boms
├── id (PK)
├── company_id (FK)
├── design_id (FK)
├── version
├── name
├── status (enum: draft, active)
├── notes
├── created_at
└── updated_at

bom_items
├── id (PK)
├── bom_id (FK)
├── material_id (FK)
├── category (enum: main_material, hardware, trim, packaging)
├── quantity_per_unit
├── unit
├── wastage_percent
└── notes

-- Projects
projects
├── id (PK)
├── company_id (FK)
├── project_code
├── name
├── project_type (enum: proto, sample, mass)
├── status (enum: draft, pre_production, production, post_production, completed)
├── customer_id (FK, nullable)
├── design_id (FK, nullable)
├── bom_id (FK, nullable)
├── reference_project_id (FK, nullable, self-reference)
├── quantity
├── target_date
├── notes
├── approved_at
├── approved_by
├── created_at
└── updated_at

-- Merchandise Planning
merchandise_plannings
├── id (PK)
├── company_id (FK)
├── project_id (FK)
├── planning_date
├── status (enum: draft, planning, approved)
├── total_material_cost
├── total_subcon_cost
├── notes
├── created_at
└── updated_at

merchandise_planning_items
├── id (PK)
├── merchandising_id (FK)
├── material_id (FK)
├── supplier_id (FK, nullable)
├── subcon_id (FK, nullable)
├── planned_qty
├── unit
├── unit_price
├── total_price
├── is_subcon (boolean)
└── notes

-- Costing
costings
├── id (PK)
├── company_id (FK)
├── project_id (FK)
├── version
├── status (enum: draft, approved, final)
├── costing_date
├── material_cost
├── man_power_cost
├── overhead_cost
├── shipping_cost
├── profit_margin
├── selling_price
├── created_at
└── updated_at

-- Sales Orders
sales_orders
├── id (PK)
├── company_id (FK)
├── so_number
├── project_id (FK)
├── customer_id (FK)
├── costing_id (FK)
├── order_date
├── delivery_date
├── status (enum: draft, confirmed, production, shipped, completed, cancelled)
├── quantity
├── unit_price
├── total_amount
├── ppn_percent
├── ppn_amount
├── shipping_cost
├── grand_total
├── notes
├── created_at
└── updated_at

-- PO Suppliers
po_suppliers
├── id (PK)
├── company_id (FK)
├── po_number
├── project_id (FK, nullable)
├── supplier_id (FK)
├── po_date
├── delivery_date
├── status (enum: draft, confirmed, partially_received, received, cancelled)
├── subtotal
├── ppn_percent
├── ppn_amount
├── grand_total
├── notes
├── created_at
└── updated_at

po_supplier_items
├── id (PK)
├── po_supplier_id (FK)
├── material_id (FK)
├── description
├── qty
├── unit
├── unit_price
├── total_price
└── qty_received

-- PO Subcons
po_subcons
├── id (PK)
├── company_id (FK)
├── po_number
├── project_id (FK, nullable)
├── subcon_id (FK)
├── po_date
├── delivery_date
├── status (enum: draft, confirmed, in_process, completed, cancelled)
├── service_cost
├── shipping_cost
├── shipping_return_cost
├── total_cost
├── notes
├── created_at
└── updated_at

po_subcon_items
├── id (PK)
├── po_subcon_id (FK)
├── description
├── qty
├── unit_price
└── total_price

-- Goods Receipt
goods_receipts
├── id (PK)
├── company_id (FK)
├── gr_number
├── po_type (enum: supplier, subcon)
├── po_id
├── supplier_id (FK)
├── receipt_date
├── warehouse_id (FK)
├── status (enum: draft, received, partial, verified)
├── notes
├── created_at
└── updated_at

goods_receipt_items
├── id (PK)
├── goods_receipt_id (FK)
├── material_id (FK)
├── po_item_id (FK, nullable)
├── qty
├── unit
├── unit_price
├── total_price
├── condition (enum: good, damaged, rejected)
└── notes

goods_receipt_shipping
├── id (PK)
├── goods_receipt_id (FK)
├── shipping_cost
├── carrier
├── tracking_number
└── notes

-- Inventory
inventory_stocks
├── id (PK)
├── company_id (FK)
├── warehouse_id (FK)
├── material_id (FK)
├── qty
├── unit
├── min_stock
├── location
├── last_updated
├── created_at
└── updated_at

warehouses
├── id (PK)
├── company_id (FK)
├── code
├── name
├── address
└── is_active

stock_movements
├── id (PK)
├── company_id (FK)
├── material_id (FK)
├── movement_type (enum: purchase, production, subcon_out, subcon_in, sale, adjustment)
├── reference_type
├── reference_id
├── qty_change
├── qty_before
├── qty_after
├── notes
└── created_at

-- Subcon Material Tracking
subcon_material_outs
├── id (PK)
├── company_id (FK)
├── subcon_id (FK)
├── project_id (FK, nullable)
├── po_subcon_id (FK)
├── job_order_number
├── out_date
├── status (enum: sent, in_process, returned)
├── notes
├── created_at
└── updated_at

subcon_material_out_items
├── id (PK)
├── subcon_out_id (FK)
├── material_id (FK)
├── qty_sent
└── unit

subcon_material_ins
├── id (PK)
├── company_id (FK)
├── subcon_out_id (FK)
├── return_date
├── status (enum: partial_return, full_return)
├── notes
├── created_at
└── updated_at

subcon_material_in_items
├── id (PK)
├── subcon_in_id (FK)
├── material_id (FK)
├── qty_returned
├── condition (enum: good, damaged, missing)
└── notes

-- Production
production_orders
├── id (PK)
├── company_id (FK)
├── production_number
├── project_id (FK)
├── sales_order_id (FK, nullable)
├── order_date
├── target_completion
├── status (enum: planning, in_progress, qc, completed)
├── planned_qty
├── produced_qty
├── notes
├── created_at
└── updated_at

job_orders
├── id (PK)
├── company_id (FK)
├── production_order_id (FK)
├── jo_number
├── process
├── assigned_to
├── start_date
├── end_date
├── status (enum: pending, in_progress, completed)
├── output_qty
├── notes
├── created_at
└── updated_at

spp_records
├── id (PK)
├── company_id (FK)
├── production_order_id (FK)
├── spp_number
├── spp_date
├── quantity
├── specifications
├── priority (enum: normal, urgent)
├── status (enum: issued, in_progress, completed)
├── notes
├── created_at
└── updated_at

qc_records
├── id (PK)
├── company_id (FK)
├── production_order_id (FK)
├── qc_number
├── inspection_date
├── inspector
├── batch_qty
├── pass_qty
├── fail_qty
├── result (enum: pass, fail, conditional)
├── notes
├── created_at
└── updated_at

qc_parameters
├── id (PK)
├── qc_id (FK)
├── parameter
├── standard
├── actual
├── tolerance
├── status (enum: pass, fail)
└── notes

-- Shipment
shipments
├── id (PK)
├── company_id (FK)
├── shipment_number
├── project_id (FK)
├── sales_order_id (FK)
├── shipment_type (enum: sample, mass)
├── shipment_date
├── destination
├── status (enum: draft, packed, shipped, delivered)
├── notes
├── created_at
└── updated_at

packing_lists
├── id (PK)
├── shipment_id (FK)
├── packing_list_number
├── total_boxes
├── total_qty
├── gross_weight
├── dimensions
├── created_at
└── updated_at

packing_list_items
├── id (PK)
├── packing_list_id (FK)
├── description
├── qty
├── box_no
├── size
├── color
└── remarks

delivery_orders
├── id (PK)
├── company_id (FK)
├── do_number
├── shipment_id (FK)
├── do_date
├── delivery_partner
├── tracking_number
├── estimated_arrival
├── actual_arrival
├── status (enum: draft, shipped, in_transit, delivered)
├── shipping_cost
├── notes
├── created_at
└── updated_at

-- Invoices
invoice_purchases
├── id (PK)
├── company_id (FK)
├── invoice_number
├── invoice_type (enum: po_supplier, gr_shipping, po_subcon)
├── reference_id
├── supplier_id (FK)
├── invoice_date
├── due_date
├── amount
├── ppn_percent
├── ppn_amount
├── total_amount
├── status (enum: pending, partial_paid, paid, overdue)
├── paid_amount
├── paid_date
├── notes
├── created_at
└── updated_at

invoice_sales
├── id (PK)
├── company_id (FK)
├── invoice_number
├── sales_order_id (FK)
├── customer_id (FK)
├── invoice_date
├── due_date
├── product_amount
├── shipping_amount
├── ppn_percent
├── ppn_amount
├── total_amount
├── status (enum: draft, issued, partial_paid, paid, overdue)
├── paid_amount
├── paid_date
├── notes
├── created_at
└── updated_at

payments
├── id (PK)
├── company_id (FK)
├── payment_type (enum: invoice_purchase, invoice_sales)
├── invoice_id
├── payment_date
├── amount
├── payment_method (enum: cash, transfer, giro)
├── reference
├── notes
├── created_at
└── updated_at

-- Master Data
suppliers
├── id (PK)
├── company_id (FK)
├── code
├── name
├── contact_person
├── phone
├── email
├── address
└── is_active

subcontractors
├── id (PK)
├── company_id (FK)
├── code
├── name
├── service_type (enum: bordir, permak, cutting, sewing, sablon)
├── contact_person
├── phone
├── email
├── address
└── is_active

materials
├── id (PK)
├── company_id (FK)
├── code
├── name
├── category (enum: kain, benang, hardware, trim, packaging)
├── unit
├── min_stock
├── unit_price
└── is_active

customers
├── id (PK)
├── company_id (FK)
├── code
├── name
├── contact_person
├── phone
├── email
├── address
├── country
├── payment_terms
└── is_active
```

---

## 6. USER FLOWS

### 6.1 Complete Workflow - Phase 1

```
┌─────────────────────────────────────────────────────────────────────┐
│                     LOGIN & COMPANY SELECTION                       │
│                             LOGIN                                   │
│                               │                                     │
│                               ▼                                     │
│                     ┌──────────────────┐                            │
│                     │ Select/Create    │                            │
│                     │ Company          │                            │
│                     └────────┬─────────┘                            │
│                              │                                      │
└──────────────────────────────┼──────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    PHASE 1 - PRE-PRODUCTION                         │
│                    (Fully Functional Demo)                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. R&D / CONSUMPTION                                               │
│     Create Design → Create BOM → Set Consumption Rates              │
│                                │                                    │
│                                ▼                                    │
│  2. PROJECT INITIATION                                              │
│     Select Company → Create Project (Proto/Sample/Mass)             │
│     Import BOM → Reference Project (if Mass referencing Sample)     │
│                                │                                    │
│                                ▼                                    │
│  3. MERCHANDISING                                                   │
│     Material Planning → Supplier Assignment                         │
│     Subcon Assignment → Calculate Total Cost                        │
│     Generate PO Supplier + PO Subcon                                │
│                                │                                    │
│                    ┌───────────┴───────────┐                        │
│                    ▼                       ▼                        │
│  ┌──────────────────────┐    ┌──────────────────────┐               │
│  │  PO SUPPLIER         │    │  PO SUBCON           │               │
│  │  (Material)          │    │  (Jasa + Ongkir)     │               │
│  └──────────┬───────────┘    └──────────┬───────────┘               │
│             │                           │                           │
│             ▼                           ▼                           │
│  ┌──────────────────────┐    ┌──────────────────────┐               │
│  │  PURCHASE TRACKING   │    │  SUBCON MATERIAL     │               │
│  │  (Track PO status)   │    │  OUT → IN TRACKING   │               │
│  └──────────┬───────────┘    └───────────┬──────────┘               │
│             │                            │                          │
│             ▼                            │                          │
│  ┌──────────────────────┐                │                          │
│  │  GOODS RECEIPT       │◄───────────────┘                          │
│  │  (Receive Material)  │                                           │
│  │  Update Inventory    │                                           │
│  └──────────┬───────────┘                                           │
│             │                                                       │
│             ▼                                                       │
│  ┌────────────────────────┐                                         │
│  │  INVENTORY             │                                         │
│  │  (Stock Management)    │                                         │
│  │  Subcon Material Track │                                         │
│  └──────────┬─────────────┘                                         │
│             │                                                       │
│             ▼                                                       │
│  ┌────────────────────────┐                                         │
│  │  4. COSTING / PRICING  │                                         │
│  │  Material Cost + MP    │                                         │
│  │  + Overhead + Shipping │                                         │
│  │  = Selling Price       │                                         │
│  └──────────┬─────────────┘                                         │
│             │                                                       │
│             ▼                                                       │
│  ┌──────────────────────┐                                           │
│  │  5. SALES ORDER      │                                           │
│  │  Create to Customer  │                                           │
│  │  Based on Costing    │                                           │
│  └──────────┬───────────┘                                           │
│             │                                                       │
│             ▼                                                       │
│  ┌──────────────────────┐    ┌──────────────────────┐               │
│  │  6. SHIPMENT         │    │  INVOICE (Purchase)  │               │
│  │  Sample/Mass Shipment│    │  From: PO/GR/Subcon  │               │
│  │  Packing List        │    │  Status: Paid/Unpaid │               │
│  │  DO (Delivery Order) │    └──────────────────────┘               │
│  │  Delivery Complete ✓ │                                           │
│  └──────────┬───────────┘                                           │
│             │                                                       │
│             ▼                                                       │
│  ┌──────────────────────┐                                           │
│  │  INVOICE (Sales)     │                                           │
│  │  From: Sales Order   │                                           │
│  │  + DO (Shipping)     │                                           │
│  │  Include: PPN 10%    │                                           │
│  │  Status: Paid/Unpaid │                                           │
│  └──────────────────────┘                                           │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

NOTE: Production modules (Production Order, JO, SPP, QC) are in PHASE 2
```

### 6.2 Project Type Transition Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                  PROJECT TYPE TRANSITIONS                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  EXISTING PROJECT                                                   │
│  (Type: Proto/Sample/Mass)                                          │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │                                                             │    │
│  │   ACTION: APPROVE PROJECT                                   │    │
│  │                                                             │    │
│  │   If Type = Proto ──[Approved]──▶ Auto-create SAMPLE        │    │
│  │      └── Copy all: Project, Merchandising, PO, Production,  │    │
│  │                    QC, Invoice, etc data                    │    │
│  │      └── All copied data = EDITABLE                         │    │
│  │                                                             │    │
│  │   If Type = Sample ──[Approved]──▶ Auto-create MASS         │    │
│  │      └── Copy all: Project, Merchandising, PO, Production,  │    │
│  │                    QC, Shipment, Invoice, etc data          │    │
│  │      └── All copied data = EDITABLE                         │    │
│  │                                                             │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │                                                             │    │
│  │   ACTION: DUPLICATE PROJECT                                 │    │
│  │                                                             │    │
│  │   Create NEW project same TYPE (for repeat order)           │    │
│  │   Copy all data from selected project                       │    │
│  │   New project = EDITABLE (for next year's order)            │    │
│  │                                                             │    │
│  │   Use Case: "Client pesan lagi tahun depan"                 │    │
│  │                                                             │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 6.3 Subcon Material Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                     SUBCON WORKFLOW                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. CREATE PO SUBCON                                                │
│     └── Define: Service (bordir/permak), Qty, Price                 │
│     └── Include: Shipping cost outbound + return                    │
│                                                                     │
│  2. CREATE JOB ORDER TO SUBCON                                      │
│     └── Select materials to send                                    │
│     └── Generate Job Order number                                   │
│                                                                     │
│  3. MATERIAL OUT (to Subcon)                                        │
│     └── Decrease inventory stock                                    │
│     └── Track: Date sent, materials, qty                            │
│     └── Status: "Sent" → "In Process"                               │
│                                                                     │
│  4. SUBCON PROCESSING                                               │
│     └── Bordir / Permak / Sablon / etc                              │
│     └── Duration varies by service type                             │
│                                                                     │
│  5. MATERIAL IN (Return from Subcon)                                │
│     └── Receive finished goods                                      │
│     └── Increase inventory stock (finished material)                │
│     └── Track condition: Good/Damaged/Missing                       │
│     └── Status: "Returned"                                          │
│                                                                     │
│  6. CREATE INVOICE PURCHASE (Subcon)                                │
│     └── Service fee (jasa)                                          │
│     └── Shipping cost (outbound + return)                           │
│     └── NO material cost                                            │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 7. DEMO SCOPE

### 7.1 Phase 1 - PRE-PRODUCTION (Fully Functional)

| Module              | Features                                      | Priority    |
| ------------------- | --------------------------------------------- | ----------- |
| Company Selection   | Multi-company wrapper, create company         | ✅ Critical |
| R&D/Consumption     | Design library, BOM, consumption rates        | ✅ Critical |
| Project Initiation | Create project, type, BOM import, reference   | ✅ Critical |
| Merchandising       | Material planning, supplier/subcon assignment | ✅ Critical |
| Costing/Pricing     | Calculate selling price from all costs        | ✅ Critical |
| Sales Order         | Create SO to customer, apply PPn              | ✅ Critical |
| Purchase Order      | PO Supplier, PO Subcon                        | ✅ Critical |
| Purchase Tracking   | Track PO status                               | ✅ Critical |
| Goods Receipt       | Receive material, update inventory            | ✅ Critical |
| Inventory           | Stock management, subcon tracking             | ✅ Critical |
| Shipment            | Sample/Mass shipment, packing list, DO        | ✅ Critical |
| Invoice             | Purchase (PO/GR/Subcon), Sales (SO/DO+PPn)   | ✅ Critical |

### 7.2 Phase 2 - PRODUCTION + ADVANCED (Simplified - UI + Static Data)

| Module                | Features                                      | Priority   |
| --------------------- | --------------------------------------------- | ---------- |
| Production Order      | UI + sample data (planning/in_progress/QC)    | ⚪ Display |
| Job Order (JO)        | UI only (cutting/sewing/finishing)            | ⚪ Display |
| SPP                   | UI only (Surat Perintah Produksi)             | ⚪ Display |
| Material Usage Report | Calculated from Phase 1 data                 | ⚪ Display |
| Assign User/Lead Task | UI + static data                              | ⚪ Display |
| QC Management         | UI + static data (Pass/Fail gate)            | ⚪ Display |
| QC Parameters         | UI concept only                               | ⚪ Display |
| Enhanced Project      | Color/Size/Brand/Collection fields (UI only) | ⚪ Display |
| Enhanced Shipment     | Tracking concept (UI only)                    | ⚪ Display |

### 7.3 Phase 3 - FINANCE (Simplified - UI + Static Data)

| Module            | Features                          | Priority   |
| ----------------- | --------------------------------- | ---------- |
| Payment Tracking  | Payment history (purchase/sales)  | ⚪ Display |
| Chart of Accounts | COA structure display             | ⚪ Display |
| General Ledger    | Sample accounting entries         | ⚪ Display |
| L/R Report        | Profit/Loss calculation display   | ⚪ Display |

**Note:** Tax Invoice (Faktur Pajak PPN 10%) is included in Phase 1 Invoice Module.

---

## 8. DUMMY DATA

### 8.1 Companies (2)

| Code   | Name                  | Type   | Purpose               |
| ------ | --------------------- | ------ | --------------------- |
| KMT001 | PT Komitrando Emporio | Main   | Production activities |
| KMT002 | PT Komitrando Textile | Branch | Warehouse             |

### 8.2 Projects (5)

| Code         | Type   | Name                      | Status       |
| ------------ | ------ | ------------------------- | ------------ |
| PRJ-001-2026 | Proto  | Explorer Backpack Pro     | In Progress  |
| PRJ-002-2026 | Proto  | Urban Handbag Series      | Draft        |
| PRJ-003-2026 | Sample | Sport Duffle Bag Premium  | Sample Stage |
| PRJ-004-2026 | Mass   | Corporate Laptop Backpack | Mass Stage   |
| PRJ-005-2026 | Mass   | Travel Messenger Bag      | Completed    |

### 8.3 Suppliers (6)

| Code   | Name                 | Category             |
| ------ | -------------------- | -------------------- |
| SUP001 | PT Textile Indonesia | Kain & Fabric        |
| SUP002 | CV Benang Jaya       | Benang jahit         |
| SUP003 | PT Aksesoris Prima   | Zipper, Button, Hook |
| SUP004 | UD Kain Maju         | Kain khusus          |
| SUP005 | PT Elastic Sejahtera | Rubber, Elastic      |
| SUP006 | CV Labelbox          | Label & Tag          |

### 8.4 Subcontractors (3)

| Code      | Name               | Service Type      |
| --------- | ------------------ | ----------------- |
| SUBCON001 | Bordir Indo Makmur | Bordir logo       |
| SUBCON002 | Permak Sritex      | Sablon & printing |
| SUBCON003 | Jasa Jahit Bersama | Cutting & sewing  |

### 8.5 Materials (17)

| Code   | Name                  | Category  | Unit  |
| ------ | --------------------- | --------- | ----- |
| MAT001 | Kain Katun Combed 30s | kain      | yard  |
| MAT002 | Kain Polyester DTY    | kain      | yard  |
| MAT003 | Kain TC 65/35         | kain      | yard  |
| MAT004 | Kain Ripstop          | kain      | yard  |
| MAT005 | Kain Denim            | kain      | yard  |
| MAT006 | Benang Polyester 150s | benang    | spool |
| MAT007 | Benang Cotton 40s     | benang    | spool |
| MAT008 | Zipper YKK 25cm       | hardware  | pcs   |
| MAT009 | Zipper YKK 45cm       | hardware  | pcs   |
| MAT010 | Button Plastik 20mm   | hardware  | pcs   |
| MAT011 | Interlining KC 303    | trim      | yard  |
| MAT012 | Rubber Strip 2cm      | trim      | yard  |
| MAT013 | Label Dalam - Woven   | trim      | pcs   |
| MAT014 | Label Ukuran          | trim      | pcs   |
| MAT015 | Tag Harga Karton      | packaging | pcs   |
| MAT016 | Plastic Bag           | packaging | pcs   |
| MAT017 | Tissue Paper          | packaging | pcs   |

### 8.6 Customers (4)

| Code    | Name                     | Country   |
| ------- | ------------------------ | --------- |
| CUST001 | PT Astra International   | Indonesia |
| CUST002 | PT Bank Nusantara        | Indonesia |
| CUST003 | CV OutdoorGear Indonesia | Indonesia |
| CUST004 | RS Medika Utama          | Indonesia |

### 8.7 Warehouses (2)

| Code  | Name           | Company               |
| ----- | -------------- | --------------------- |
| WH001 | Gudang Utama   | PT Komitrando Emporio |
| WH002 | Gudang Textile | PT Komitrando Textile |

---

## 9. CONFIGURATION

### 9.1 Costing Configuration (Static)

**Man Power Rates per Unit:**

| Process   | Rate (Rp)  |
| --------- | ---------- |
| Cutting   | 5,000      |
| Sewing    | 15,000     |
| Finishing | 8,000      |
| QC        | 3,000      |
| Packing   | 2,000      |
| **Total** | **33,000** |

**Overhead & Profit:**

| Item          | Percentage |
| ------------- | ---------- |
| Overhead      | 15%        |
| Profit Margin | 20%        |

**Shipping Estimation:**

| Destination        | Cost per Unit (Rp) |
| ------------------ | ------------------ |
| Jakarta            | 5,000              |
| Jawa (non-Jakarta) | 8,000              |
| Luar Jawa          | 12,000             |
| Export (US/Canada) | 35,000             |

---

## 10. IMPLEMENTATION TIMELINE

```
Day 1-2:   Setup Laravel + Filament
           Database schema + migrations
           Authentication + Company selection

Day 3-4:   Master Data modules
           (Suppliers, Subcons, Materials, Customers, Warehouses)

Day 5-6:   Core Phase 1 modules (Pre-Production)
           (R&D, Projects, Merchandising, Costing, Sales Order)

Day 7-8:   Procurement & Inventory
           (PO, GR, Inventory, Purchase Tracking, Subcon Material Tracking)

Day 9:     Shipment & Invoice
           (Shipment, Packing List, DO, Invoice Purchase/Sales)

Day 10:    Testing, Polish & Demo Ready
           (Phase 2 & 3 UI setup, Testing, Demo preparation)
```

**Note:** Production modules (Production Order, JO, SPP, QC) are part of Phase 2 and will be shown as simplified UI with sample data during demo.

---

## 10. NON-FUNCTIONAL REQUIREMENTS

### 10.1 Performance Requirements

| Metric | Target | Description |
|--------|--------|-------------|
| Page Load Time | < 2 seconds | Average response time for all pages |
| API Response Time | < 500ms | For CRUD operations on individual records |
| Database Query | < 200ms | For standard filtered queries |
| Concurrent Users | Up to 50 users | Simultaneous active sessions |
| File Upload | Up to 10MB | Tech pack and reference images |
| Report Generation | < 30 seconds | For costing and invoice reports with 100+ records |

### 10.2 Security Requirements

| Aspect | Requirement |
|--------|-------------|
| Authentication | Multi-company login with session management |
| Authorization | All data scoped by company_id (tenant isolation) |
| Data Protection | Sensitive data not logged or exposed in error messages |
| Session Management | 8-hour session timeout with automatic logout |
| Password Policy | Minimum 8 characters, stored as hashed (bcrypt) |
| CSRF Protection | Laravel built-in CSRF tokens on all forms |
| SQL Injection | Eloquent ORM parameterized queries |
| XSS Prevention | Blade template auto-escaping |
| Audit Trail | Created at / updated at timestamps on all records |

### 10.3 Backup & Recovery Strategy

| Item | Policy |
|------|--------|
| Database Backup | Daily automated backup (MySQL) |
| Backup Retention | 30 days rolling retention |
| File Backup | Weekly backup of uploaded files (images, tech packs) |
| Recovery Time Objective (RTO) | < 4 hours |
| Recovery Point Objective (RPO) | < 24 hours (daily backup) |
| Backup Location | Local external drive + cloud storage (optional) |
| Test Restore | Monthly backup restoration test |

### 10.4 Browser Compatibility

| Browser | Minimum Version |
|---------|----------------|
| Google Chrome | 90+ |
| Mozilla Firefox | 88+ |
| Microsoft Edge | 90+ |
| Safari | 14+ |
| Mobile Browser | iOS Safari 14+, Chrome Android 90+ |

---

## 11. ACCEPTANCE CRITERIA

### 11.1 Company Selection Module

**Definition of Done:**
- [ ] User can login with email/password
- [ ] User can select company from dropdown after login
- [ ] All subsequent data is filtered by selected company
- [ ] Company selection persists throughout session
- [ ] User can create new company (if admin)
- [ ] Session expires after 8 hours of inactivity

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-CS-01 | Login with valid credentials | Redirect to company selection page |
| AC-CS-02 | Login with invalid credentials | Show error message, stay on login page |
| AC-CS-03 | Select company from dropdown | Navigate to dashboard with company-scoped data |
| AC-CS-04 | Switch company via dropdown | Reload data for new company, reset all context |
| AC-CS-05 | Access data without company selection | Redirect to company selection page |

### 11.2 R&D / Consumption Module

**Definition of Done:**
- [ ] User can create/edit/delete design library entries
- [ ] User can upload reference images and tech packs
- [ ] User can create BOM with multiple items
- [ ] BOM items linked to materials from master data
- [ ] Wastage percentage calculated in consumption
- [ ] BOM version control working (draft/active status)
- [ ] Consumption rate entry per material per design

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-RND-01 | Create new design library entry | Design saved with auto-generated ID |
| AC-RND-02 | Upload tech pack PDF | File stored, link displayed in design detail |
| AC-RND-03 | Create BOM with 10 items | All items saved with correct material references |
| AC-RND-04 | Set wastage 10% on BOM item | Effective consumption = base_qty × 1.10 |
| AC-RND-05 | Change BOM status from draft to active | BOM locked for editing, cannot revert |
| AC-RND-06 | Delete BOM with existing references | Show error, prevent deletion |

### 11.3 Project Initiation Module

**Definition of Done:**
- [ ] Project code auto-generated (PRJ-XXX-YYYY format)
- [ ] User can select project type (proto/sample/mass)
- [ ] BOM can be imported from R&D module
- [ ] Workflow status updates correctly
- [ ] Approve button auto-creates next project type
- [ ] Duplicate button copies all data to new project
- [ ] All data in copied project remains editable

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-PROJ-01 | Create new proto project | PRJ code generated, status = draft |
| AC-PROJ-02 | Import BOM to project | BOM linked, consumption calculation available |
| AC-PROJ-03 | Approve proto project | Auto-create sample project, copy all data |
| AC-PROJ-04 | Approve sample project | Auto-create mass project, include shipment copy |
| AC-PROJ-05 | Duplicate mass project | New mass project created, all data editable |
| AC-PROJ-06 | Change project status to completed | Project locked, no further edits allowed |

### 11.4 Merchandising Module

**Definition of Done:**
- [ ] User can create merchandise planning per project
- [ ] Material quantities auto-calculated from BOM
- [ ] Supplier/subcon assignment per material item
- [ ] Total cost auto-calculated (material + subcon)
- [ ] Generate PO Supplier from planning
- [ ] Generate PO Subcon from planning

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-MERCH-01 | Create merchandise planning | Planning saved, linked to project |
| AC-MERCH-02 | Bom auto-populated | All BOM items added as planning items |
| AC-MERCH-03 | Assign supplier to item | Supplier ID saved, PO generation enabled |
| AC-MERCH-04 | Assign subcon to item | Subcon ID saved, PO Subcon generation enabled |
| AC-MERCH-05 | Generate PO Supplier | PO Supplier created with all supplier items |
| AC-MERCH-06 | Calculate total cost | Sum of (qty × unit_price) displayed correctly |

### 11.5 Costing / Pricing Module

**Definition of Done:**
- [ ] Material cost auto-calculated from BOM × unit price
- [ ] Man power cost calculated from static config per unit
- [ ] Overhead = (Material + MP) × 15%
- [ ] Profit margin = subtotal × 20%
- [ ] Shipping cost based on destination
- [ ] Selling price formula applied correctly
- [ ] Multiple version support per project
- [ ] Price lock for approved costing

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-COST-01 | Calculate material cost | Sum of (consumption × unit_price) for all BOM items |
| AC-COST-02 | Calculate MP cost | 33,000 per unit × quantity |
| AC-COST-03 | Calculate overhead | (Material + MP) × 15% |
| AC-COST-04 | Calculate profit margin | (Material + MP + Overhead) × 20% |
| AC-COST-05 | Calculate shipping export | 35,000 per unit × quantity |
| AC-COST-06 | Calculate final selling price | Correct formula applied, all components included |
| AC-COST-07 | Approve costing version | Costing locked, cannot be edited |
| AC-COST-08 | Generate new version | Previous version archived, new draft created |

### 11.6 Sales Order Module

**Definition of Done:**
- [ ] SO number auto-generated (SO-YYYY-XXX format)
- [ ] Create SO from project + costing
- [ ] Apply PPN 10% automatically
- [ ] Add shipping cost to grand total
- [ ] Update status through workflow
- [ ] Generate invoice from SO

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-SO-01 | Create SO from project | SO created, costing data populated |
| AC-SO-02 | Calculate PPN | 10% of product_amount added |
| AC-SO-03 | Add shipping cost | Grand total = product + shipping + PPN |
| AC-SO-04 | Update status to confirmed | Status changed, timestamp recorded |
| AC-SO-05 | Generate invoice from SO | Invoice created with all line items |
| AC-SO-06 | Cancel SO | SO marked cancelled, linked invoices updated |

### 11.7 Purchase Order Module

**Definition of Done:**
- [ ] PO Supplier number auto-generated (PO-SUP-YYYY-XXX)
- [ ] PO Subcon number auto-generated (PO-SUBCON-YYYY-XXX)
- [ ] Partial receipt tracking per item
- [ ] PPN 11% calculation for supplier PO
- [ ] Shipping cost included in PO Subcon
- [ ] Generate GR from PO

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-PO-01 | Create PO Supplier | PO generated with correct number format |
| AC-PO-02 | Create PO Subcon | PO Subcon generated, no material cost |
| AC-PO-03 | Track partial receipt | Qty received updated per item |
| AC-PO-04 | Generate GR from PO | GR created with all received items |
| AC-PO-05 | Calculate PPN 11% | PPN added to supplier PO total |
| AC-PO-06 | PO Subcon shipping included | Outbound + return shipping in total cost |

### 11.8 Goods Receipt Module

**Definition of Done:**
- [ ] GR number auto-generated (GR-YYYY-XXX)
- [ ] Receive material against PO
- [ ] QC check on arrival (good/damaged/rejected)
- [ ] Update inventory automatically on receipt
- [ ] Handle returns for damaged items
- [ ] Track inbound shipping cost

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-GR-01 | Create GR from PO | GR created, all PO items listed |
| AC-GR-02 | Receive 80 of 100 qty | Partial receipt status, inventory +80 |
| AC-GR-03 | Mark item as damaged | Damaged qty separated, return initiated |
| AC-GR-04 | Complete receipt | Inventory updated, PO status updated |
| AC-GR-05 | Create return record | Return created with reason and quantity |
| AC-GR-06 | Track inbound shipping | Shipping cost recorded for invoice |

### 11.9 Inventory Module

**Definition of Done:**
- [ ] View stock per material per warehouse
- [ ] Low stock alert when below min_stock
- [ ] Stock movement history recorded
- [ ] Subcon material OUT tracking
- [ ] Subcon material IN tracking
- [ ] Stock opname entry supported

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-INV-01 | View stock levels | All material stocks displayed with current qty |
| AC-INV-02 | Low stock alert | Materials below min_stock highlighted |
| AC-INV-03 | View movement history | All transactions for material listed chronologically |
| AC-INV-04 | Send material to subcon | Subcon OUT created, inventory decreased |
| AC-INV-05 | Receive from subcon | Subcon IN created, inventory increased |
| AC-INV-06 | Stock opname entry | Manual adjustment recorded with reason |

### 11.10 Shipment Module

**Definition of Done:**
- [ ] Create shipment for sample/mass
- [ ] Generate packing list with box details
- [ ] Generate DO with shipping details
- [ ] Track delivery status
- [ ] Update inventory for shipped goods

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-SHIP-01 | Create shipment | Shipment created linked to SO |
| AC-SHIP-02 | Generate packing list | Packing list with all box details |
| AC-SHIP-03 | Generate DO | DO created with tracking info |
| AC-SHIP-04 | Update to shipped status | Timestamp recorded, inventory adjusted |
| AC-SHIP-05 | Track delivery | Status updated to delivered when received |

### 11.11 Invoice Module

**Definition of Done:**
- [ ] Invoice Purchase auto-numbered from PO/GR/Subcon
- [ ] Invoice Sales auto-numbered from SO/DO
- [ ] PPN 10% auto-calculated on sales invoice
- [ ] Payment recording supported
- [ ] Overdue tracking
- [ ] Print invoice (PDF) enabled

**Test Scenarios:**

| ID | Scenario | Expected Result |
|----|----------|----------------|
| AC-INV-01 | Generate purchase invoice from PO | Invoice created with all PO items |
| AC-INV-02 | Generate sales invoice from SO | Invoice created, PPN 10% auto-calculated |
| AC-INV-03 | Record partial payment | Paid amount updated, status = partial_paid |
| AC-INV-04 | Full payment received | Status = paid, paid_date recorded |
| AC-INV-05 | Overdue invoice | Status updated to overdue after due date |
| AC-INV-06 | Print PDF invoice | PDF generated with all invoice details |

---

## 12. STAKEHOLDER ANALYSIS

### 12.1 User Personas

| Persona | Role | Department | Responsibilities |
|---------|------|------------|------------------|
| **Andi Santoso** | R&D Manager | R&D | Manage design library, BOM creation, consumption rates |
| **Budi Prasetyo** | Project Manager | PPIC | Create projects, manage Proto/Sample/Mass workflow, approve projects |
| **Citra Dewi** | Merchandiser | Merchandising | Material planning, supplier/subcon assignment, PO generation |
| **Dian Firmansyah** | Costing Staff | Finance | Calculate selling prices, manage costing versions, price lock |
| **Eko Wijaya** | Sales Admin | Sales | Create sales orders, track shipment, generate invoices |
| **Fitri Handayani** | Procurement Staff | Procurement | Create POs, track deliveries, receive goods, manage inventory |
| **Gunawan Hidayat** | Warehouse Staff | Warehouse | Stock management, subcon material tracking, stock opname |
| **Hendra Kusuma** | Finance Staff | Finance | Payment tracking, invoice management, report generation |
| **Irma Natalia** | QC Supervisor | Production | Inspect goods receipt, manage QC records |
| **Joko Rahmadi** | System Admin | IT | User management, company setup, system configuration |

### 12.2 User Access Matrix

| Feature | Admin | PM | Merch | Costing | Sales | Procure | Warehouse | Finance | QC |
|---------|-------|----|----|---------|-------|---------|-----------|---------|----|
| **Company Selection** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Design Library** | ✅ | R | R | - | - | - | - | - | - |
| **BOM Management** | ✅ | R | ✅ | R | - | - | - | - | - |
| **Project Management** | ✅ | ✅ | R | - | R | - | - | - | - |
| **Merchandising** | ✅ | R | ✅ | R | - | R | - | - | - |
| **Costing** | ✅ | R | R | ✅ | R | - | - | R | - |
| **Sales Order** | ✅ | R | R | - | ✅ | - | - | R | - |
| **PO Supplier** | ✅ | R | ✅ | R | - | ✅ | - | R | - |
| **PO Subcon** | ✅ | R | ✅ | R | - | ✅ | - | R | - |
| **Goods Receipt** | ✅ | R | R | - | - | ✅ | ✅ | R | - |
| **Inventory** | ✅ | R | R | - | - | R | ✅ | R | - |
| **Shipment** | ✅ | R | R | - | ✅ | - | R | R | R |
| **Invoice Purchase** | ✅ | R | R | - | - | R | - | ✅ | - |
| **Invoice Sales** | ✅ | R | R | - | ✅ | - | - | ✅ | - |
| **Payment Tracking** | ✅ | - | - | - | R | - | - | ✅ | - |
| **Reports** | ✅ | R | R | R | R | R | R | ✅ | R |

> **Legend:** ✅ = Full Access (Create/Edit/Delete) | R = Read Only | - = No Access

### 12.3 Key Stakeholders

| Stakeholder | Interest | Influence | Engagement Strategy |
|------------|----------|-----------|---------------------|
| **PT Komitrando Emporio (KMT001)** | Main production company, primary user | High | Regular demo, early access to features |
| **PT Komitrando Textile (KMT002)** | Branch warehouse | High | Parallel testing, feedback session |
| **Regulux Labs** | Development team | High | Weekly sprint review |
| **PT Astra International** | Key customer | Medium | End-user acceptance testing |
| **Export Customers (US/Canada)** | End buyer | Medium | Demo showcase for export workflow |
| **PT Komitrando Emporio Management** | Project sponsor | High | Monthly progress report, decision milestone |

---

## 13. RISK ASSESSMENT

### 13.1 Technical Risks

| Risk | Severity | Likelihood | Impact | Mitigation |
|------|----------|------------|--------|-----------|
| **Data Isolation Breach** | Critical | Low | Customers see each other's data | Middleware enforcement on all queries |
| **Database Performance Under Load** | High | Medium | Slow page loads, timeouts | Index optimization, query caching |
| **File Upload Security** | High | Low | Malware upload | File type validation, storage isolation |
| **Authentication Bypass** | Critical | Low | Unauthorized access | Laravel built-in auth, session hardening |
| **Data Loss** | Critical | Low | Permanent data deletion | Soft deletes, backup strategy |
| **Concurrent Edit Conflict** | Medium | Medium | Data overwriting | Optimistic locking, timestamp checks |
| **Export Function Slow** | Medium | Low | Timeout on large reports | Pagination, background processing |

### 13.2 Business Risks

| Risk | Severity | Likelihood | Impact | Mitigation |
|------|----------|------------|--------|-----------|
| **Scope Creep** | High | Medium | Timeline delay, budget overrun | Strict phase gate, change request process |
| **User Adoption Low** | High | Medium | System underutilized | Training sessions, user involvement in UAT |
| **Incorrect Costing Calculations** | High | Low | Financial loss, pricing errors | Multiple test scenarios, finance team verification |
| **Inventory Discrepancy** | Medium | Medium | Stock mismatch, production delay | Barcode scanning, regular stock opname |
| **Subcon Material Loss** | Medium | Low | Financial loss, project delay | Insurance clause in subcon contract |
| **Late Payment from Customers** | Medium | Medium | Cash flow impact | Credit control process, overdue alerts |
| **Export Compliance (CEISA)** | Medium | Low | Customs delays, penalties | Phase 4 integration roadmap, compliance checklist |

### 13.3 Risk Response Strategy

| Priority | Response | Action |
|----------|----------|--------|
| **Critical** | Avoid or Transfer | Implement strong controls; involve insurance/legal |
| **High** | Mitigate | Process controls, monitoring, escalation |
| **Medium** | Mitigate or Accept | Monitoring dashboards, periodic review |
| **Low** | Accept | Document in risk register, monitor annually |

---

## 14. SUCCESS METRICS

### 14.1 Key Performance Indicators (KPIs)

| Category | KPI | Target | Measurement |
|----------|-----|--------|--------------|
| **Project Delivery** | On-time project completion | ≥ 90% | Completed projects vs target date |
| **Costing Accuracy** | Cost variance | ≤ 5% | Actual cost vs estimated cost |
| **PO Cycle Time** | PO Creation to Receipt | ≤ 14 days | Average days from PO to GR |
| **Inventory Accuracy** | Stock count vs system | ≥ 99% | Stock opname results |
| **Invoice Collection** | Days Sales Outstanding (DSO) | ≤ 45 days | Average collection period |
| **System Uptime** | Availability | ≥ 99.5% | Monthly uptime monitoring |
| **User Adoption** | Active users / Total users | ≥ 85% | Weekly active sessions |

### 14.2 Business Outcome Metrics

| Objective | Metric | Baseline | Target | Timeline |
|-----------|--------|----------|--------|----------|
| Reduce manual data entry | Time spent on manual tasks | 8 hrs/day | 2 hrs/day | 3 months |
| Improve order visibility | Projects with real-time status | 0% | 100% | 1 month |
| Reduce stock discrepancy | Inventory variance | Rp 10M variance | < Rp 1M | 2 months |
| Speed up invoice collection | Payment on time | 60% | 85% | 3 months |
| Reduce approval time | Project approval cycle | 7 days | 2 days | 1 month |
| Improve supplier delivery | On-time delivery rate | 70% | 90% | 3 months |

### 14.3 System Health Metrics

| Metric | Target | Alert Threshold | Monitoring |
|--------|--------|-----------------|------------|
| Average Response Time | < 500ms | > 1000ms | APM monitoring |
| Error Rate | < 0.1% | > 1% | Log monitoring |
| Database Query Time | < 200ms | > 500ms | Query analyzer |
| Session Success Rate | > 99.9% | < 99% | Auth logs |
| Backup Success Rate | 100% | < 99% | Backup logs |
| Disk Usage | < 70% | > 85% | Infrastructure monitoring |

### 14.4 User Satisfaction Metrics

| Metric | Measurement Method | Frequency | Target |
|--------|-------------------|-----------|--------|
| System Usability Scale (SUS) | Survey | Quarterly | ≥ 70 |
| Task Completion Rate | Analytics | Monthly | ≥ 95% |
| Support Ticket Volume | Helpdesk | Monthly | Declining trend |
| Feature Request Resolution | % Implemented | Quarterly | ≥ 80% |
| Training Effectiveness | Post-training quiz | Per training | ≥ 85% pass rate |

---

## 15Glossary

| Term | Definition |
|------|-----------|
| **BOM** | Bill of Materials - List of raw materials needed for production |
| **PPN** | Pajak Pertambahan Nilai - Value Added Tax (10% in Indonesia) |
| **Subcon** | Subcontractor - External vendor providing manufacturing services |
| **PO** | Purchase Order - Formal order document to suppliers |
| **GR** | Goods Receipt - Document confirming material receipt |
| **DO** | Delivery Order - Document for outbound shipment |
| **SO** | Sales Order - Customer purchase order |
| **PPIC** | Production Planning Inventory Control |
| **CEISA** | Indonesia's electronic customs declaration system |
| **COA** | Chart of Accounts - Account structure for finance |
| **L/R** | Laba/Rugi - Profit & Loss statement |
| **JO** | Job Order - Work order for production process |
| **SPP** | Surat Perintah Produksi - Production work order slip |

---

**Document End**

_Created by Regulux Labs_
_Version 1.0 - 21 May 2026_
