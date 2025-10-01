# derohist

Containerised stack for Derohist, composed of a Laravel frontend, a MariaDB store, and a Python worker that synchronises DERO chain data.

## Prerequisites
- Docker Engine 20.10+
- Docker Compose v2 (`docker compose` CLI)

## Quick start
1. Copy `.env.example` to `.env`, generate a fresh Laravel key (`php artisan key:generate --show` or any 32-byte base64 string), and paste it into `APP_KEY`.
2. Copy the provided environment file (`frontend/.env`) if you need local overrides.
3. Build and start the stack:
   ```bash
   docker compose up --build
   ```
   The first boot runs the MariaDB schema in `mysql/init/01_schema.sql`, installs PHP dependencies, and starts the Python sync worker. The MariaDB container ships with `max_connections` capped at 200 and a trimmed buffer pool to keep the footprint modest on low-spec VPSs.
   Override `RETENTION_WEEKS` (default `2`) or `BLOCK_TIME_SECONDS` via `docker-compose.yml` if you need a longer or shorter history window.
4. Visit http://localhost:8080 once the frontend container is healthy.

## Useful commands
- Run Laravel database migrations after the database is ready:
  ```bash
  docker compose run --rm frontend php artisan migrate --force
  ```
- Execute the Python synchroniser manually (for debugging):
  ```bash
  docker compose run --rm backend python dh_updater.py
  ```
- Tail logs from all services:
  ```bash
  docker compose logs -f
  ```

## Stopping the stack
Bring down containers and remove anonymous volumes:
```bash
docker compose down -v
```
