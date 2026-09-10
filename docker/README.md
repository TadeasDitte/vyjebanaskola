# Docker setup

The whole stack runs in containers. `docker-compose.yml` builds the `dev`
target of the root `Dockerfile` and shares that image across the PHP, Vite,
queue and scheduler services.

## Services

| Service     | What it is                        | Exposed on            |
|-------------|-----------------------------------|-----------------------|
| `web`       | nginx, serves `public/`           | http://localhost:8000 |
| `app`       | PHP 8.4 FPM (Laravel)             | internal `:9000`      |
| `vite`      | Vite dev server (HMR)             | http://localhost:5173 |
| `queue`     | `php artisan queue:work`          | -                     |
| `scheduler` | `php artisan schedule:work`       | -                     |
| `db`        | PostgreSQL 17                     | host `localhost:5433` |

## First run

```bash
# UID/GID default to 1000; override if your host user differs:
#   export UID=$(id -u) GID=$(id -g)

docker compose up -d --build
```

On boot the `app` container generates `APP_KEY` if missing, waits for
Postgres, and runs `php artisan migrate --force`.

Open http://localhost:8000.

## Everyday commands

```bash
docker compose up -d              # start
docker compose down               # stop
docker compose logs -f app        # tail logs
docker compose exec app bash      # shell in the PHP container
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app composer install
docker compose exec vite npm install
```

## Dependencies

`vendor/` and `node_modules/` live in named volumes (`vendor`,
`node_modules`), seeded from the image on first start. They are **not** the
host folders, so host and container tooling never fight over binaries.

After changing `composer.json` / `package.json`:

```bash
docker compose exec app composer update    # or: install
docker compose exec vite npm install
```

To rebuild them from scratch:

```bash
docker compose down -v && docker compose up -d --build
```

(`-v` also drops the database volume `db-data`.)

## Running on the host instead

Set `DB_HOST=127.0.0.1` and `DB_PORT=5433` in `.env`, then
`docker compose up -d db` and `composer dev` as before.

## Production image

```bash
docker build --target production -t vyjebanaskola:prod .
```

Builds PHP deps with `--no-dev`, compiles frontend assets, and bakes
everything into the image (no bind mounts, no Node in the final layer).
You still need to supply an `.env`, a database, and a web server that
forwards PHP to the container's `:9000` (see `docker/nginx/default.conf`).
