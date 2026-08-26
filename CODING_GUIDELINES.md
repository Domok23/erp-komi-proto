# 🚀 Vibe Coding Guidelines — ERP Komi Proto

Selamat datang di lingkungan **Vibe Coding** terbaik untuk proyek **ERP Komi Proto**! Panduan ini dirancang khusus agar kolaborasi coding-mu dengan **Antigravity AI** berjalan dengan sangat lancar, efisien, dan menyenangkan.

---

## 🧭 Panduan Navigasi Cepat
1. **Aturan Utama:** Semua data (kecuali master Company dan User) wajib terikat ke `company_id`.
2. **Framework & Stack:** Laravel 12.x + Filament v4.x + PHP 8.3+.
3. **Format Kode:** Selalu jalankan Linter Pint (`./vendor/bin/pint`) agar kode konsisten.
4. **Filament v4 Syntax:** Selalu gunakan typehint `Schema $schema` untuk form dan `Table $table` untuk table.
5. **Dilarang Crash Raw SQL Exception:** Dilarang melempar exception SQL/Database mentah (seperti `UniqueConstraintViolationException`) ke user saat input data duplikat atau error validasi lainnya. Seluruh form WAJIB menampilkan pesan error validasi Filament atau Notification UI yang rapi.
6. **Strict English UI & Label Standard:** Seluruh teks tampilan UI (Form Labels, Select/Radio Option Labels, Section Titles, Action Button Labels, Table Column Headers, dan Notification Messages) **WAJIB** menggunakan **Bahasa Inggris (English)**. Dilarang keras mencampurkan Bahasa Indonesia di dalam string UI atau label komponen Filament.
7. **Harmonisasi Warna & Styling UI/UX Kustom:** Card, modal, drawer, dan banner kustom wajib selaras dengan palet netral Filament v4 (Zinc-900 / `#18181b` di dark mode, bukan navy `#0f172a`), menggunakan translucent badge standards, serta mengikuti hierarki radius, padding, dan ergonomi modal bawaan Filament.
8. **Zero N+1 Query & Eager Loading:** Seluruh Filament Resource, Table, Relation Manager, dan Widget wajib meng-eager load relasi dengan `with([...])` dan dilarang mengeksekusi query di dalam loop atau column formatting closure.

---

## 🏗️ Struktur & Arsitektur Sistem

Sistem ini didesain menggunakan arsitektur **Multi-Tenancy** tingkat perusahaan yang terbagi ke dalam beberapa fase pengerjaan:

```mermaid
graph TD
    A[Multi-Company Login Gate] --> B[PT Komitrando Emporio - Main Production]
    A --> C[PT Komitrando Textile - Branch Warehouse]
    
    B --> D[Phase 1: Pre-Production]
    B --> E[Phase 2: Production UI & Static Data]
    B --> F[Phase 3: Finance UI & Static Data]
    
    D --> D1[Consumption & R&D]
    D --> D2[Project Initiation]
    D --> D3[Costing & Pricing]
    D --> D4[Sales & Purchase Order]
    D --> D5[Inventory & Goods Receipt]
```

### 🔑 Implementasi Multi-Tenancy (`company_id`)
Setiap kali kamu membuat model baru:
1. Pastikan kolom pertama setelah `id` pada migration adalah `company_id` (foreign key ke `companies`).
2. Pasang trait `App\Traits\BelongsToCompany` pada model baru tersebut.
3. Query database secara otomatis akan disaring sesuai perusahaan aktif dalam session via `CompanyContext::getCompanyId()`.

### 🛡️ Standard Penanganan Error & Unique Constraints (Anti-Crash Rule)
1. **Form-Level Multi-Tenant Unique Rule:** Setiap input form yang memiliki constraint database `unique` (seperti NIK, NIP, Nomor SP, Kode Master) **WAJIB** dipagari per perusahaan pada level form Filament:
   ```php
   ->unique(
       table: 'nama_tabel',
       column: 'nama_kolom',
       ignoreRecord: true,
       modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
   )
   ```
2. **Clean Service Exception Handling:** Jika validasi logika bisnis terjadi di Service layer (misal: Hire Candidate, Leave Approval, Material Usage), Service harus melempar Custom Exception (contoh: `HrHireException`) yang ditangkap oleh Filament Action/Page untuk menampilkan Notifikasi UI yang rapi (`Notification::make()->danger()->send()`). Dilarang membiarkan unhandled SQL Exception membocorkan crash screen ke user!
3. **Strict Date Range Validation:** Setiap form yang memiliki sepasang input tanggal rentang waktu (seperti Tanggal Mulai vs Tanggal Selesai pada Cuti, Kontrak Kerja, Placement, Project) **WAJIB** memasang validasi `->afterOrEqual('start_date')` pada input `end_date` agar tanggal selesai tidak bisa dibuat lebih awal/lampau dari tanggal mulai.
4. **Tanpa Concrete Typehint `Get $get` pada Closure Form:** Saat membuat callback komponen form Filament (misal pada `->visible()`, `->required()`, `->options()`), **DILARANG** memberikan concrete typehint `fn (Get $get)`. Gunakan `$get` biasa (`fn ($get)`) tanpa import `Filament\Forms\Get` untuk mencegah crash `TypeError` akibat ketidakcocokan kelas namespace di Filament v4.
5. **Tenant-Scoped Relationship Selectors:** Setiap `->relationship()` pada komponen `Select` form Filament yang mereferensikan tabel multi-tenant (memiliki `company_id`) **WAJIB** menyertakan `modifyQueryUsing` agar opsi dropdown hanya menampilkan data milik perusahaan aktif:
   ```php
   ->relationship('leaveType', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('company_id', CompanyContext::getCompanyId()))
   ```
   Tanpa ini, user bisa melihat dan memilih data milik perusahaan lain — **kebocoran data tenant**.
6. **TOCTOU Race Condition Safety (Check-Then-Act):** Setiap kali Service layer melakukan pengecekan unik (`->where(...)->exists()`) diikuti `::create()` terpisah, pola ini rentan **race condition** saat dua request bersamaan. **WAJIB** menambahkan salah satu perlindungan:
   - ✅ Tangkap `QueryException` setelah `DB::transaction()` dan konversi ke Custom Exception yang user-friendly.
   - ✅ Pastikan migration memiliki `$table->unique([...])` constraint yang sesuai sebagai safety net terakhir.
   - ❌ **DILARANG** hanya mengandalkan query `->exists()` tanpa database-level constraint.
7. **Edge-Case Validation pada Computed Values:** Saat logika bisnis menghitung nilai turunan (seperti `$workingDays` dari rentang tanggal), **WAJIB** validasi hasilnya sebelum melanjutkan proses. Contoh: jika leave request disetujui tetapi seluruh tanggal jatuh di akhir pekan, `$workingDays = 0` — sistem harus menolak approval, bukan diam-diam approve tanpa efek.
8. **Quota/Balance Guard Sebelum Mutasi:** Setiap operasi yang mengurangi saldo atau menambah pemakaian kuota (seperti `$balance->increment('used_days', $days)`) **WAJIB** dicek terlebih dahulu apakah sisa kapasitas mencukupi sebelum melakukan mutasi:
   ```php
   if (($balance->used_days + $workingDays) > $balance->quota_days) {
       throw new HrLeaveRequestException('Insufficient leave quota');
   }
   ```
   **DILARANG** langsung `increment()` atau `decrement()` tanpa pengecekan batas.
9. **Verifikasi Import & Anti-Crash Filament Component Class:** Saat menambahkan fitur/komponen Filament UI baru:
   - **DILARANG** mengasumsikan namespace class komponen yang tidak valid (seperti `Filament\Forms\Components\Actions` atau `Filament\Tables\Actions\BulkAction`). Selalu gunakan namespace resmi yang digunakan dalam proyek (`Filament\Actions\BulkAction`, `Filament\Actions\Action`, `Filament\Actions\BulkActionGroup`) dan periksa file Livewire/Resource yang sudah ada.
   - Selalu lakukan verifikasi pengujian/kompilasi setelah mengedit Filament Resource/Livewire component untuk memastikan seluruh class yang diimport valid dan mencegah runtime exception `Class not found`.
10. **Reset/Recalculate Autofilled Fields on Deselection (Form Ergonomics):** Pada setiap komponen `Select` atau toggle form Filament yang memiliki kait `afterStateUpdated` untuk meng-autofill atau memicu kalkulasi field turunan (seperti detail item, total biaya, harga unit, supplier, atau nomor dokumen):
    - **WAJIB** menangani percabangan ketika nilai parent di-deselect / dikosongkan (`!$state`).
    - Field-field turunan harus di-reset ke nilai default awal (`null`, `[]`, atau `0`) dan fungsi recalculate wajib dipanggil kembali agar state form Filament tetap bersih dan konsisten.
11. **Strict English Display Language Standard:** Seluruh teks tampilan UI (Form Labels, Select/Radio Option Labels, Section Titles, Action Button Labels, Table Column Headers, dan Notification Messages) **WAJIB** menggunakan **Bahasa Inggris (English)**. Dilarang keras mencampurkan Bahasa Indonesia di dalam string UI atau label komponen Filament.

### 🎨 Standard Pewarnaan & Styling UI/UX Komponen Kustom (Filament Design System Alignment)
Semua komponen Blade kustom, modal, drawer (slide-over), banner, dan widget UI **WAJIB** selaras dengan palet tema dan ergonomi desain bawaan Filament v4:

1. **Latar Belakang Card Netral (Neutral Dark Surface):**
   - **Light Mode:** Putih bersih (`#ffffff` / `bg-white`) dengan border tipis `#e5e7eb` (`border-gray-200`) dan subtle drop shadow.
   - **Dark Mode:** Abu-abu arang netral Zinc-900 (`#18181b` / `dark:bg-gray-900` / `rgba(255, 255, 255, 0.04)`) dengan border `rgba(255, 255, 255, 0.08)` atau `border-gray-800`.
   - ❌ **DILARANG** menggunakan warna navy / biru gelap / dongker seperti `#0f172a`, `#1e293b`, `#1f2937`, atau `#334155` pada card kustom karena bertabrakan dengan estetika netral Filament.

2. **Translucent Badge & Tag Standards:**
   - Badge kustom (Draft, Approved, Shortage, OK, Revision, Tags) wajib menggunakan latar transparan lembut dan border halus:
     - **Light Mode:** `rgba({color}, 0.1)` + teks `{color}-700` + border `rgba({color}, 0.25)`.
     - **Dark Mode:** `rgba({color}, 0.15)` + teks `{color}-400` + border `rgba({color}, 0.3)`.
   - Gunakan class CSS terdefinisi secara eksplisit (seperti `.po-badge-approved`) agar tidak ter-purge oleh build script Tailwind saat dynamic string rendering.
   - ❌ **DILARANG** menggunakan blok warna pastel solid buram yang tidak tembus pandang.

3. **Banner & Header Bersih (Non-Intrusive Card):**
   - Banner status (seperti PO Authorization Banner) tidak boleh mewarnai seluruh background card secara solid/penuh. Gunakan background netral (`#ffffff` / `#18181b`) dan fokuskan pewarnaan status pada ikon, teks status, dan pill badge.

4. **Ergonomi Modal, Drawer (Slide-Over), & Form:**
   - **Hapus Label Wrapper Redundant:** Gunakan `->hiddenLabel()` pada `Placeholder` atau `ViewEntry` jika tampilan custom blade sudah memiliki header/judulnya sendiri di dalam card.
   - **Maksimalkan Ruang Vertikal Drawer:** Pada drawer operasional/monitoring, hilangkan bar footer bawah (`->modalCancelAction(false)` & `->modalSubmitAction(false)`) karena drawer sudah memiliki tombol close `✕` di header dan support tombol `ESC`.
   - **Konsistensi Border Radius & Spacing:**
     - Card / Outer Container: `rounded-xl` (`0.75rem`) dengan padding `1rem` (`p-4`).
     - Sub-item / Inner Box: `rounded-lg` (`0.5rem`) dengan padding `0.5rem - 0.75rem`.
     - Badge & Pill: `rounded-full` (`9999px`) dengan padding `2px 8px - 10px`.
   - **Hierarki Tipografi:**
     - Judul Section: `font-bold` / `font-semibold` (`text-sm` atau `text-base`, `text-gray-900 dark:text-gray-100`).
     - Subteks / Metadata: `text-xs font-normal` (`text-gray-500 dark:text-gray-400`).
     - Kode Proyek / SKU / PO: `font-mono text-xs font-semibold tracking-wider`.

5. **Utamakan Komponen Resmi Filament (Filament First):**
   - Bila memungkinkan, selalu utamakan penggunaan komponen bawaan Blade Filament (`<x-filament::badge>`, `<x-filament::button>`, `<x-filament::modal>`, `Section`, `Split`, `Grid`) dibandingkan membuat tag HTML ad-hoc mentah.

6. **Anti-Inline Style Override pada Dark Mode:**
   - ❌ **DILARANG** menanamkan `style="background-color: #e5e7eb"` atau `#f3f4f6` secara hardcoded pada elemen dinamis (seperti track progress bar atau badge) karena spesifisitas inline style akan menimpa class `dark:bg-...` dan menyebabkan elemen tampil sebagai balok putih silau di dark mode. Gunakan class CSS terpisah atau class Tailwind adaptif.

---

### ⚡ Standard Pencegahan N+1 Query & Optimasi Database (Eager Loading)
Aplikasi ERP memproses ribuan data transaksi lintas departemen, sehingga pencegahan **N+1 Query** adalah kewajiban mutlak:
1. **Wajib Eager Loading pada Filament Resource & Relation Manager:**
   - Setiap `Resource` atau `RelationManager` yang menampilkan kolom relasi (misal: `customer.name`, `supplier.name`, `project.project_code`) **WAJIB** meng-eager load relasi tersebut pada `getEloquentQuery()`:
     ```php
     public static function getEloquentQuery(): Builder
     {
         return parent::getEloquentQuery()->with(['customer', 'items.material', 'supplier']);
     }
     ```
   - Atau pada konfigurasi `Table`:
     ```php
     $table->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'supplier']));
     ```
2. **Eager Loading pada Widgets & Dashboard Cards:**
   - Seluruh Livewire Widget (Chart, Stat Cards, Top Sales/PO Widgets) wajib memuat data relasi menggunakan `with([...])` atau agregasi join SQL sebelum melakukan mapping atau iterasi data.
3. **Dilarang Query di Dalam Perulangan / Accessor / Column Closures:**
   - ❌ **DILARANG** menjalankan query database (`Model::where()`, `DB::table()`, atau memanggil `$record->relation` yang belum di-load) di dalam loop `foreach`, `Collection::map()`, atau closure format kolom tabel (`->formatStateUsing(...)`).
   - ✅ Gunakan batch lookup dengan `whereIn()` atau pre-load seluruh relasi di awal Service/Method.
4. **Monitoring Otomatis via Query Detector:**
   - Proyek dilengkapi dengan package `beyondcode/laravel-query-detector`. Saat melakukan pengembangan atau pengujian di local environment, pantau log browser console dan application logs untuk memastikan tidak ada peringatan N+1 query yang terdeteksi.

---

## 🎛️ Fitur & Formula Bisnis Utama (Wajib Diketahui!)

### 💰 1. Formula Costing & Pricing
Saat mengembangkan fitur costing, pastikan formula perhitungan harga jual berikut diimplementasikan dengan tepat:

**Selling Price = (Material Cost + Man Power Cost) * (1 + Overhead%) * (1 + Profit Margin%) + Shipping**

#### 📊 Parameter Costing Tetap (Static Config):
| Kategori | Parameter | Nilai (Rp) / Persentase |
| :--- | :--- | :--- |
| **Man Power** | Cutting | Rp 5,000 / unit |
| | Sewing | Rp 15,000 / unit |
| | Finishing | Rp 8,000 / unit |
| | QC | Rp 3,000 / unit |
| | Packing | Rp 2,000 / unit |
| | **Total Man Power** | **Rp 33,000 / unit** |
| **Overhead** | Overhead Rate | **15% (0.15)** |
| **Profit** | Profit Margin | **20% (0.20)** |
| **Shipping** | Jakarta | Rp 5,000 / unit |
| | Jawa (non-Jakarta) | Rp 8,000 / unit |
| | Luar Jawa | Rp 12,000 / unit |
| | Export (US/Canada) | Rp 35,000 / unit |

### 🔄 2. Alur Transisi Proyek (Project Type Transitions)
Setiap jenis proyek memiliki perilaku otomatisasi tersendiri:
*   **PROTO Stage:** Fokus pada pembuatan sampel awal. Ketika status proto diubah menjadi **Approved**, sistem harus otomatis membuat proyek baru dengan jenis **SAMPLE**.
*   **SAMPLE Stage:** Ketika status sample diubah menjadi **Approved**, sistem otomatis membuat proyek baru dengan jenis **MASS** (Mass Production) dan menyalin data logistik pendukung.
*   **Duplicate Button:** Disediakan tombol duplikasi proyek yang menyalin seluruh item BOM & perencanaan merchandise untuk mempermudah pesanan berulang (repeat order) di masa mendatang.

---

## ⚡ Perintah Dev yang Sering Digunakan
Jalankan perintah ini langsung di terminal Antigravity:

*   **Menjalankan Seluruh Server Dev (Recomended!):**
    ```bash
    composer dev
    ```
    *Menjalankan web server, queue listener, logs collector, dan Vite server sekaligus.*
*   **Reset & Seed Database Segar:**
    ```bash
    php artisan migrate:fresh --seed
    ```
*   **Memformat Struktur Kode:**
    ```bash
    ./vendor/bin/pint
    ```
*   **Melakukan Uji Coba (Testing):**
    ```bash
    php artisan test
    ```

---

## 🛠️ Slash Commands untuk Vibe Coding di Antigravity
Gunakan slash commands ini di panel chat Antigravity untuk performa coding maksimal:

*   `/goal` ➜ Gunakan perintah ini saat kamu ingin AI menyelesaikan tugas besar atau kompleks tanpa henti (misal: "buat seluruh modul goods receipt beserta relasinya"). AI akan bekerja secara menyeluruh hingga tujuan tercapai.
*   `/schedule` ➜ Jadwalkan pengujian berkala atau backup data demo secara otomatis di latar belakang.
*   `/grill-me` ➜ Gunakan perintah ini jika kamu ingin AI mewawancarai kamu tentang detail desain sistem sebelum mulai menulis kode. Sangat berguna untuk menyamakan visi arsitektur proyek.

---

## 🦖 Pemanfaatan Fitur Caveman & Cavecrew
Di workspace ini, terdapat keahlian khusus (**Skills**) untuk menghemat token dan mempercepat durasi pengerjaan:
*   **Caveman Mode:** Katakan `"use caveman"` atau gunakan `/caveman` jika kamu ingin AI berkomunikasi dengan gaya super-singkat namun 100% akurat secara teknis. Ini menghemat hingga 75% konsumsi token!
*   **Cavecrew Delegation:** Katakan `"delegate to subagent"` untuk meminta agen khusus menyelesaikan sub-task di latar belakang (seperti investigasi kode, perbaikan bug di file terisolasi, atau review diff) tanpa membebani context window utamamu.

---

> [!NOTE]
> Proyek ini menggunakan **Filament v4.x** (versi terbaru). Hindari penggunaan sintaks Filament v3 lama. Gunakan file `.cursorrules` yang telah dibuat di root proyek sebagai referensi utama bagi AI saat membuat kode baru.

*Selamat melakukan Vibe Coding! Mari bangun sistem ERP terbaik untuk PT Komitrando Emporio!* 🎒💼✈️
