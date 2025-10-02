# Derohist

Containerised Derohist stack composed of a Laravel frontend, a MariaDB store and a Python worker that keeps the database in sync with the DERO blockchain.

## Architecture at a glance

| Service  | Path / Image           | Responsibility                                      |
|----------|-----------------------|-----------------------------------------------------|
| frontend | `frontend/` (Laravel) | Renders the dashboard, miners view and HTTP API     |
| backend  | `backend/` (Python)   | Polls DERO RPC, normalises data and writes to MySQL |
| mariadb  | `mariadb:11.4`        | Persists chain, miner and balance data              |

All services are orchestrated through `docker-compose.yml`. The default bind mount exposes the dashboard on `http://localhost:8085`.

## Prerequisites

- Docker Engine **20.10+**
- Docker Compose plugin (`docker compose` v2)
- (Optional) Python 3.11 and PHP 8.1+ if you plan to run services outside Docker

## Configure environment

1. Copy the repository root template and adjust secrets:

   ```bash
   cp .env.example .env
   ```

2. Generate a Laravel application key (32‑byte base64 string) and place it in `APP_KEY` inside `.env`:

   ```bash
   php artisan key:generate --show          # if PHP is installed locally
   # or
   openssl rand -base64 32
   ```

3. Review the remaining keys (see the table below). Most deployments can keep the defaults, but adjust the URL/domain values if you are running behind HTTPS or a custom host.

### Root `.env` variables

| Key | Description | Default/example |
|-----|-------------|------------------|
| `APP_KEY` | Laravel application key shared with the frontend container. Required. | *(none)* |
| `APP_URL` | Canonical public URL used for link generation. | `https://derohist.xyz` |
| `SESSION_DOMAIN` | Domain scope for browser sessions/cookies. | `derohist.xyz` |
| `SESSION_SECURE_COOKIE` | Set to `true` when the site is served via HTTPS. | `true` |
| `TRUST_PROXIES` | IP/CIDR list forwarded to Laravel’s proxy trust middleware. | `"*"` |
| `DERO_RPC_ENDPOINTS` | Comma-separated RPC URLs queried by the backend worker. | See template |
| `DERO_SYNC_LAG_THRESHOLD` | Height difference before the UI shows the “syncing” banner. | `10000` |
| `DONATIONS` | Set to `off` to hide the donation modal in the UI. | `on` |

> **Note:** Database credentials are sourced from `.env` (see `MARIADB_USER`, `MARIADB_PASSWORD`, `MARIADB_ROOT_PASSWORD`). Update them before exposing the stack publicly.

### Frontend `.env`

The Laravel container mounts `frontend/.env`. If you need to override values, edit that file before starting the stack. Key entries include:

| Key | Purpose | Default |
|-----|---------|---------|
| `APP_ENV`, `APP_DEBUG` | Execution mode and debug verbosity. | `local`, `true` |
| `APP_URL` | Base URL used by the frontend. Should match the host/port you use locally. | `http://localhost:8080` |
| `DB_CONNECTION` | Connection driver. | `mysql` |
| `DB_HOST`, `DB_PORT` | Database host/port. Inside Docker these are the service name and `3306`. | `mysql` (overridden to `mariadb` via compose) |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Credentials that must align with the MariaDB container values. | `appdb`, `${MARIADB_USER}`, `${MARIADB_PASSWORD}` |

## Run with Docker Compose

```bash
docker compose up --build
```

The first boot will:

1. Initialise MariaDB using the schema in `mariadb/init/01_schema.sql`.
2. Build the Laravel image, install Composer dependencies and warm caches.
3. Build the Python worker image and start the chain synchroniser.

Once the `frontend` service reports healthy, browse to **http://localhost:8085**.

To stop and clean up:

```bash
docker compose down        # keep volumes
docker compose down -v    # drop mariadb/front storage volumes too
```

## Helpful Docker commands

```bash
# Tail logs for every service
docker compose logs -f

# Run Laravel migrations (after MariaDB is up)
docker compose run --rm frontend php artisan migrate --force

# Execute PHPUnit / Laravel feature tests
docker compose run --rm frontend php artisan test

# Run the Python synchroniser manually
docker compose run --rm backend python dh_updater.py

# Clear cached charts or config inside the frontend container
docker compose exec frontend php artisan cache:clear
```

### Showing the donation modal

Leave `DONATIONS` unset (or set it to `on`) to show the donation icon by default. Set `DONATIONS=off` in the root `.env` if you prefer to hide it.

## Local development without Docker (optional)

### Backend (Python)

```bash
python -m venv .venv
source .venv/bin/activate
pip install -e backend
export DB_HOST=127.0.0.1 DB_NAME=appdb DB_USER=appuser DB_PASSWORD=apppass
python backend/dh_updater.py
```

Environment variables honoured by the worker:

| Variable | Description |
|----------|-------------|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Database connection details |
| `DERO_RPC_ENDPOINTS` | Overrides the default RPC endpoint list |
| `POLL_INTERVAL` | Seconds between block fetch attempts (default `30`) |
| `RETENTION_WEEKS` | Sliding window of data to retain (default `4`) |
| `BLOCK_TIME_SECONDS` | Expected block spacing, used for projections |
| `LOG_LEVEL` | Python logging level (`INFO`, `DEBUG`, …) |

### Frontend (Laravel)

```bash
cd frontend
composer install
npm install
cp .env.example .env   # if you want a standalone config
php artisan key:generate
php artisan migrate
php artisan serve --port=8080
npm run dev
```

Keep the `.env` values aligned with your database credentials if you run MariaDB locally.

## Troubleshooting

- **Frontend shows “Node is still synchronizing”** – the Python worker reports a lag greater than `DERO_SYNC_LAG_THRESHOLD`. Tail backend logs to inspect RPC errors.
- **Charts render empty** – indicates no on-chain data for the selected period or address. Verify the worker has caught up and is writing rows.
- **MariaDB connection failures** – ensure the credentials in `.env`, `frontend/.env`, and `docker-compose.yml` match and the container is healthy.

---

For issues or contributions please open a pull request or file an issue on [GitHub](https://github.com/51FuR4nk/derohist).
