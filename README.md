# ERP Komi Proto

ERP system prototype built with Laravel 12 and Filament 4 for managing projects, production, inventory, and supply chain operations.

## Requirements

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- MySQL 8.0+
- Git

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Domok23/erp-komi-proto.git
cd erp-komi-proto
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and update these values for your local setup:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_komi_proto
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Create Database

Create a MySQL database named `erp_komi_proto`:

```sql
CREATE DATABASE erp_komi_proto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Seed Database (Optional)

Seed with sample data for development:

```bash
php artisan db:seed
```

## Running the Application

### Start Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Start All Services (Recommended)

Runs server, queue worker, and Vite dev server concurrently:

```bash
composer dev
```

## Access

- **Admin Panel**: http://localhost:8000/admin
- **Default Admin Login**: admin@erp-komi.test / password

## Common Commands

```bash
# Run tests
php artisan test

# Clear cache
php artisan config:clear
php artisan cache:clear

# Code formatting
./vendor/bin/pint

# Create a new resource
php artisan make:filament-resource EntityName
```

## Tech Stack

- Laravel 12
- Filament 4
- MySQL 8
- TailwindCSS 4
- Vite