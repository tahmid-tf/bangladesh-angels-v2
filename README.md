<p align="center">
  <img src="public/Logo%20Base.svg" width="280" alt="Bangladesh Angels Logo" onerror="this.onerror=null;this.src='public/logo.webp';">
</p>

<h1 align="center">Bangladesh Angels Network (BAN) - Platform v2</h1>

<p align="center">
  A modern, high-performance angel investment and venture network platform connecting angel investors, venture capitalists, and HNWIs with vetted startups across Bangladesh and emerging markets.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel&logoColor=white" alt="Laravel 11">
  <img src="https://img.shields.io/badge/Livewire-3.5-FB70A9?style=flat&logo=livewire&logoColor=white" alt="Livewire 3.5">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=flat&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=flat&logo=alpine.js&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/Vite-5.x-646CFF?style=flat&logo=vite&logoColor=white" alt="Vite 5">
</p>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
  - [Investor Portal & Deal Syndication](#1-investor-portal--deal-syndication)
  - [Founder Applications & Pitching](#2-founder-applications--pitching)
  - [Executive Admin Portal](#3-executive-admin-portal)
  - [BAN Wealth Scheme](#4-ban-wealth-scheme)
  - [Content Management & Knowledge Hub](#5-content-management--knowledge-hub)
- [Technology Stack](#-technology-stack)
- [Architecture & Folder Structure](#-architecture--folder-structure)
- [System Requirements](#-system-requirements)
- [Installation & Local Setup](#-installation--local-setup)
- [Environment Configuration](#-environment-configuration)
- [Running Locally](#-running-locally)
- [Testing & Quality Assurance](#-testing--quality-assurance)
- [Deployment & Production Optimization](#-deployment--production-optimization)
- [Security & Access Control](#-security--access-control)
- [License & Acknowledgements](#-license--acknowledgements)

---

## 🌟 Overview

**Bangladesh Angels Network (BAN)** is Bangladesh's pioneering angel investment network. The **BAN v2** platform facilitates end-to-end investment operations:

- **For Startups**: Streamlined pitch submission, deck review, and fundraising pipeline tracking.
- **For Investors**: Membership tiers (Free, Core, Institutional), exclusive deal syndicates, investment commitment workflows, portfolio tracking, and access to the DeckVue resource database.
- **For Network Administrators**: An integrated management suite with real-time Livewire tables, batch member moderation, email verification controls, payment reconciliation (AamarPay and bank transfers), and multi-channel content management.

---

## 🚀 Key Features

### 1. Investor Portal & Deal Syndication
* **Membership Tier System**: Free, Core, and Institutional subscription tiers with feature gating and automated renewal tracking.
* **Deal Pipeline & Syndicates**: Categorized deal stages (`Pipeline`, `Invest`, `Commit`, `Review`, `Portfolio`), investment expressions of interest, and commitment tracking.
* **DeckVue & Investor Resources**: Interactive research hub, investor deck database, and ecosystem reports.
* **Investor Dashboard**: Dedicated member cockpit with investment history, active commitments, and verified status.

### 2. Founder Applications & Pitching
* **Founder Pitch Pipeline**: Comprehensive application form with deck upload, company metrics, and stage analysis.
* **Angel Academy Applications**: Cohort intake workflows for educational and acceleration tracks.

### 3. Executive Admin Portal
* **High-Efficiency Member Management (`/admin/members`)**:
  * **Tabbed Views**: Real-time counters for *All*, *Active*, *Inactive*, *Pending Approval*, and *Featured Members*.
  * **Batch Operations / Bulk Actions**: Floating toolbar with live counters for multi-select **Approve**, **Verify Email**, and **Delete** actions across individual pages or entire datasets.
  * **Inline Status Management**: Instant Active/Inactive toggle dropdown directly within the table row.
  * **Safe Action Confirmation Modals**: Glassmorphism backdrop-blur confirmation modals with summary previews and execution spinners.
  * **Member Export**: One-click data export to Excel and CSV.
  * **Public Testimonial Curation**: Featured member tagging with public testimonial editor.
* **Real-time Member Activity Notifications (`/admin/notifications`)**:
  * Livewire-driven notifications table tracking investor views, interest submissions, and commit submissions.
  * Unread badge counters, toggle-viewed states, and configurable per-page pagination (10, 25, 50, 100).
* **Deal Syndication Management (`/admin/deals`)**:
  * Manage deal lifecycle, terms, valuations, documents, and individual member activity logs.

### 4. BAN Wealth Scheme
* Offline and bank-transfer order management with payment proof uploads.
* Admin verification and order fulfillment pipeline (`/admin/ban-wealth-orders`).

### 5. Content Management & Knowledge Hub
* **Blogs & Insights**: Full publishing suite with slug management, featured images, and sanitization.
* **Publications & Whitepapers**: Document downloads with file serving and download analytics.
* **Landing Page CMS**: Reorderable homepage portfolios, program cards, startup services, what-we-do cards, and live metric statistics.
* **Campaign & Mail Center (`/admin/mail-list`)**: Broadcast mailer to members and subscribers with dispatch logs.

---

## 🛠️ Technology Stack

| Layer | Technologies |
|---|---|
| **Backend Framework** | [Laravel 11.x](https://laravel.com/) (PHP 8.2+) |
| **Reactivity & Components** | [Livewire 3.5](https://livewire.laravel.com/) |
| **Frontend & Styling** | [Tailwind CSS 3.x](https://tailwindcss.com/), [Alpine.js 3.x](https://alpinejs.dev/), [Chart.js](https://www.chartjs.org/) |
| **Asset Bundler** | [Vite 5.x](https://vitejs.dev/) with `laravel-vite-plugin` |
| **Database & ORM** | MySQL 8.x / MariaDB / SQLite (local testing) via Eloquent |
| **Media Management** | [Spatie Laravel MediaLibrary 11](https://spatie.be/docs/laravel-medialibrary) |
| **PDF Generation** | [Barryvdh Laravel DomPDF 3.1](https://github.com/barryvdh/laravel-dompdf) |
| **Data Export/Import** | [Maatwebsite Excel 3.1](https://laravel-excel.com/) |
| **Payment Gateways** | [AamarPay Payment Gateway](https://aamarpay.com/) (Credit/Debit/MFS) + Bank Transfer |
| **Authentication** | Laravel Breeze (Session-based) + Google OAuth (Laravel Socialite) |

---

## 📂 Architecture & Folder Structure

```text
bangladesh-angels-v2/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # Admin controllers (Members, Deals, Orders, CMS)
│   │   │   ├── Auth/             # Breeze & Google OAuth controllers
│   │   │   └── ...               # Public & Investor controllers
│   │   └── Middleware/           # Role verification & user approval guards
│   ├── Livewire/                 # Reactive Livewire 3 components
│   │   ├── AdminNotificationsTable.php
│   │   ├── DealsTable.php
│   │   ├── HomepagePortfoliosTable.php
│   │   ├── MembersTable.php      # Member moderation, bulk actions & inline toggles
│   │   └── ...
│   └── Models/                   # Eloquent Models (User, Deal, Investment, etc.)
├── database/
│   ├── factories/                # Model factories for testing
│   ├── migrations/               # Database schema migrations
│   └── seeders/                  # Base and sample data seeders
├── resources/
│   ├── css/                      # Tailwind & custom CSS layers
│   ├── js/                       # Alpine.js & JavaScript modules
│   └── views/
│       ├── admin/                # Admin blade views & shared layout shells
│       ├── livewire/             # Blade templates for Livewire components
│       └── ...                   # Public, auth, and investor views
├── routes/
│   ├── auth.php                  # Authentication endpoints
│   ├── console.php               # Artisan command schedules
│   └── web.php                   # Core application & admin routing
└── tests/
    ├── Feature/                  # Feature & integration test suites
    └── Unit/                     # Isolated unit tests
```

---

## 💻 System Requirements

Make sure your environment meets the following specifications:

- **PHP**: `^8.2` (with `pdo`, `mbstring`, `openssl`, `curl`, `gd` or `imagick`, `fileinfo`, `xml`, and `zip` extensions enabled)
- **Composer**: `^2.5`
- **Node.js**: `^18.0` or `^20.0`
- **NPM**: `^9.0`
- **Database**: MySQL `8.0+` or MariaDB `10.4+` (SQLite supported for development/testing)
- **Web Server**: Nginx, Apache (Laragon / Valet / Docker)

---

## ⚙️ Installation & Local Setup

### 1. Clone the Repository
```bash
git clone https://github.com/tahmid-tf/bangladsh-angels-v2.git
cd bangladsh-angels-v2
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Frontend dependencies
npm install
```

### 3. Setup Environment File
Copy `.env.example` to create your local `.env`:
```bash
cp .env.example .env
```

Generate the Laravel application key:
```bash
php artisan key:generate
```

### 4. Configure Database
Update `.env` with your database credentials:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bangladesh_angels
DB_USERNAME=root
DB_PASSWORD=
```

Run database migrations and seed default configuration:
```bash
php artisan migrate --seed
```

### 5. Link Public Storage
Create the symbolic link to expose uploaded files and media:
```bash
php artisan storage:link
```

---

## 🔐 Environment Configuration

Key configuration parameters available in `.env`:

### Payment Gateway (AamarPay)
```ini
# Gateway mode: 'sandbox' or 'live'
AAMARPAY_MODE=sandbox
AAMARPAY_CURRENCY=USD

# Sandbox Credentials
AAMARPAY_SANDBOX_JSONPOST_URL=https://sandbox.aamarpay.com/jsonpost.php
AAMARPAY_SANDBOX_TRXCHECK_URL=https://sandbox.aamarpay.com/api/v1/trxcheck/request.php
AAMARPAY_SANDBOX_STORE_ID=aamarpaytest
AAMARPAY_SANDBOX_SIGNATURE_KEY=dbb74894e82415a2f7ff0ec3a97e4183

# Live Credentials (Required in Production)
AAMARPAY_LIVE_JSONPOST_URL=https://secure.aamarpay.com/jsonpost.php
AAMARPAY_LIVE_TRXCHECK_URL=https://secure.aamarpay.com/api/v1/trxcheck/request.php
AAMARPAY_LIVE_STORE_ID=your_store_id
AAMARPAY_LIVE_SIGNATURE_KEY=your_signature_key

# Local testing helper: Bypass gateway and activate subscriptions instantly
SKIP_PAYMENT_GATEWAY=false
```

### Google OAuth (Social Login)
```ini
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### BAN Wealth Offline Bank Details
```ini
BAN_WEALTH_BANK_NAME="BRAC Bank, Gulshan Corporate"
BAN_WEALTH_BANK_ACCOUNT_NUMBER="2201 5567 8890"
```

---

## 🏃 Running Locally

To run the development server, queue listener, log viewer, and Vite bundler simultaneously:

```bash
composer run dev
```

Or run services in separate terminals:

```bash
# Terminal 1: Laravel Web Server
php artisan serve

# Terminal 2: Vite Asset Watcher
npm run dev

# Terminal 3: Queue Worker (For emails and background jobs)
php artisan queue:work
```

Access the application in your browser at `http://127.0.0.1:8000` (or your local Laragon virtual host e.g. `http://bangladsh-angels-v2.test`).

---

## 🧪 Testing & Quality Assurance

The application uses PHPUnit and Laravel Feature testing to ensure stability.

### Run All Tests
```bash
php artisan test
```

### Run Smoke Tests
```bash
php artisan test --filter=AdminUiSmokeTest
```

### Code Formatting (Laravel Pint)
To ensure PSR-12 and Laravel code style compliance:
```bash
vendor/bin/pint
```

---

## 🚀 Deployment & Production Optimization

When deploying to a production server (Ubuntu/Nginx/Forge/Cloud):

1. **Install Production Dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

2. **Cache Configuration, Routes & Views**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Database Migrations**:
   ```bash
   php artisan migrate --force
   ```

4. **Queue & Task Worker**:
   Run a process manager (such as `Supervisor` or `systemd`) to keep the queue worker running:
   ```bash
   php artisan queue:work --tries=3 --timeout=90
   ```

5. **Ensure Folder Permissions**:
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

---

## 🛡️ Security & Access Control

* **Roles & Permissions**: Access is segregated using user roles (`admin`, `superadmin`, `investor`) and account states (`approved`, `pending`, `active`, `inactive`).
* **Route Protection**: The `ApprovedUserMiddleware` and verified email checks prevent unauthorized users from accessing sensitive deals and investor tools.
* **CSRF Exemption**: AamarPay callback endpoints are secured via server-to-server signature checks while exempt from standard CSRF verification in `bootstrap/app.php`.
* **Sanitization**: Blog and content inputs pass through dedicated sanitization filters before storage.

---

## 📄 License & Credits

* Developed for **[Bangladesh Angels Network](https://bangladeshangels.com)**.
* Built on top of the open-source [Laravel Framework](https://laravel.com) under the [MIT License](https://opensource.org/licenses/MIT).
