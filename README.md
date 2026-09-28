# Laravel Log Pipeline (Data Prepper → OpenSearch)

Ships structured Laravel logs asynchronously through OpenSearch Data Prepper into OpenSearch, with pipeline enrichment and a dedicated Horizon queue for delivery.

## Requirements

- PHP 8.3+ (project targets Laravel 13 / PHP 8.4-capable tooling)
- Composer
- Docker & Docker Compose
- Redis PHP extension (`phpredis`) recommended
- Node.js (optional, for Vite frontend assets)

## Quick start

### 1. Install the app

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Ensure `.env` includes:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

OPENSEARCH_INITIAL_ADMIN_PASSWORD=Developer@123
DATA_PREPPER_URL=http://127.0.0.1:2021/laravel/logs
DATA_PREPPER_QUEUE_NAME=logs
DATA_PREPPER_TIMEOUT=5
```

### 2. Start infrastructure

```bash
docker compose up -d
```

| Service | URL |
|---------|-----|
| OpenSearch | https://localhost:9200 |
| OpenSearch Dashboards | http://localhost:5601 |
| Data Prepper HTTP ingest | http://localhost:2021/laravel/logs |
| Data Prepper API | http://localhost:4900 |
| Redis | localhost:6379 |

**Local credentials:** `admin` / `Developer@123` (from `.env`)

### 3. Run Laravel + Horizon

```bash
php artisan serve
php artisan horizon
```

Horizon dashboard: http://localhost:8000/horizon

### 4. Emit a demo log

```bash
curl http://localhost:8000/demo/payment-failed
```

Or in application code:

```php
Log::channel('data-prepper')->error('Payment failed', [
    'user_id' => 123,
    'order_id' => 456,
    'service' => 'payment-service',
]);
```

### 5. Query OpenSearch

```bash
curl -sk -u admin:Developer@123 \
  'https://localhost:9200/laravel-logs/_search?pretty&size=5'
```

Or open Dashboards → Discover and create an index pattern for `laravel-logs`.

## Architecture details

### Laravel logging channel

- Channel: `data-prepper` (`config/logging.php`)
- Factory: `App\Logging\CreateDataPrepperLogger`
- Handler: `App\Logging\DataPrepperHandler` builds the payload and dispatches `SendLogToDataPrepper`

### Async delivery job

`App\Jobs\SendLogToDataPrepper`:

- Always queued on the `logs` queue
- Retries with backoff (`tries = 5`)
- POSTs a **JSON array** of events (Data Prepper HTTP source contract)
- On final failure, writes to the local `single` log channel

Horizon runs a dedicated supervisor for `logs` so log shipping does not starve the `default` queue (`config/horizon.php`).

### Data Prepper pipeline

Configured in `data-prepper/pipelines.yaml`:

1. **Source** — HTTP `POST /laravel/logs` on port `2021`
2. **Processors** (in order):
   - `add_entries` — `processed_by`, `pipeline`
   - `rename_keys` — `datetime` → `@timestamp`
   - `lowercase_string` — `level`, `environment`
   - `uppercase_string` — `service`
   - `delete_entries` — remove `channel`
3. **Sink** — OpenSearch index `laravel-logs`

After editing the pipeline:

```bash
docker compose up -d --force-recreate data-prepper
```

## Configuration reference

| Variable | Purpose | Default |
|----------|---------|---------|
| `DATA_PREPPER_URL` | Prepper HTTP ingest endpoint | `http://127.0.0.1:2021/laravel/logs` |
| `DATA_PREPPER_QUEUE_NAME` | Horizon/Redis queue for log jobs | `logs` |
| `DATA_PREPPER_TIMEOUT` | HTTP timeout (seconds) for the job | `5` |
| `OPENSEARCH_INITIAL_ADMIN_PASSWORD` | OpenSearch + Dashboards admin password | (see `.env.example`) |
| `QUEUE_CONNECTION` | Must be `redis` for Horizon | `redis` |

## Useful commands

```bash
# Stack
docker compose up -d
docker compose ps
docker compose logs -f data-prepper
docker compose down

# App
php artisan serve
php artisan horizon
php artisan test --compact

# Manual ingest (bypass Laravel)
curl -X POST 'http://127.0.0.1:2021/laravel/logs' \
  -H 'Content-Type: application/json' \
  -d '[{"level":"ERROR","message":"manual test","datetime":"2026-09-28T12:00:00+00:00","service":"api","environment":"local"}]'
```