# Warehouse Reservation — Test Task (Laravel)

A simplified event-driven warehouse reservation system built with Laravel.

## Requirements

- Docker + Docker Desktop
- Git
- Composer (only to install dependencies; host PHP version is not important if you use `--ignore-platform-reqs`)

---

## Project Setup

### 1. Clone the repository

1. Clone the repo:
    - `git clone git@github.com:Serzg1k/warehouse.git`
2. Go to the project directory:
    - `cd warehouse-reservation`

### 2. Environment setup

1. Copy env file:
    - `cp .env.example .env`
2. Generate app key:
    - `php artisan key:generate`  
      If host PHP is too old, you can later run inside Sail:  
      `./vendor/bin/sail artisan key:generate`

### 3. Install dependencies

Install PHP dependencies via Composer:

- `composer install --ignore-platform-reqs`

### 4. Install and configure Sail

1. Install Sail:
    - `composer require laravel/sail --dev`
2. Run Sail installation wizard:
    - `php artisan sail:install`

### 5. Start containers

Start Docker containers via Sail:

- `./vendor/bin/sail up -d`

### 6. Run migrations and seeders

Apply database migrations and seed demo data:

- `./vendor/bin/sail artisan migrate:fresh --seed`

Seeders will create demo inventory records for test SKUs (for example `ABC123` and `XYZ999`).

### 7. Run queue worker

For events and jobs to be processed, start the queue worker in a separate terminal:

- `cd warehouse-reservation`
- `./vendor/bin/sail artisan queue:work`

---

## How to Use


### Basic API usage

Create order:

- `curl -X POST http://localhost/api/order -H "Content-Type: application/json" -d '{"sku":"ABC123","qty":3}'`

Get order:

- `curl http://localhost/api/orders/1`

Get inventory movements:

- `curl http://localhost/api/inventory/ABC123/movements`

---

## Run Tests

Run the test suite with:

- `./vendor/bin/sail artisan test`
## What I would improve in a production version (що б ви покращили у продакшн-версії)

1. **Authentication & authorization**
    - Protect all API endpoints (e.g. Laravel Sanctum / Passport, role-based access for internal tools).
    - Rate limiting per client / API key to avoid abuse.

2. **Rate limiting & quotas**
    - Per-client throttling on our API (Laravel throttle middleware).
    - Global and per-order limits for calls to the supplier API to respect their rate limits and protect us from cascading failures.

3. **Idempotency & robustness**
    - Idempotent handling of `OrderCreated` and background jobs (e.g. by using idempotency keys or strict status transitions).
    - Protection against double-reservation in case of job retries.
    - Explicit dead-letter queue / failure handling for jobs.

4. **Supplier integration**
    - Replace random responses with a real HTTP integration and configurable retry/backoff policy (exponential backoff instead of fixed 15s).
    - Timeouts, circuit breaker pattern, structured logging of all supplier calls.
    - Configurable maximum waiting time for `awaiting_restock` per order.

5. **Monitoring & observability**
    - Centralized logging (e.g. ELK / Loki) for events, jobs and supplier interactions.
    - Metrics and dashboards (success/fail rate of reservations, latency, number of delayed orders).
    - Alerts on abnormal failure rates or long `awaiting_restock` queues.

6. **Data model & performance**
    - Proper indexing (by `sku`, `status`, `created_at`) for orders and movements.
    - Pagination for listing endpoints (`/api/orders`, `/api/inventory/{sku}/movements`).
    - Archival strategy for old movements (cold storage / separate table).

7. **Validation & safety**
    - Stronger validation and business rules (reasonable bounds on `qty`, allowed SKU formats).
    - Configurable limits per client (max qty per order, max concurrent pending orders).

8. **API design**
    - Versioned API (`/api/v1/...`).
    - More detailed error payloads and domain-specific error codes.
    - Webhooks or event stream (e.g. when order status changes) instead of pure polling.
