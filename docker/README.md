# Docker setup

Development runs on [Laravel Sail](https://laravel.com/docs/sail)
(`docker-compose.yml`); production runs prebuilt images from GHCR
(`docker-compose.prod.yml`). The root `Dockerfile` is only used by CI and
to build the production image.

## Development (Sail)

### First run

Sail lives in `vendor/`, so install Composer dependencies once without any
host PHP:

```bash
docker run --rm -u "$(id -u):$(id -g)" \
    -v "$PWD:/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
vendor/bin/sail npm install
vendor/bin/sail npm run dev     # Vite HMR on http://localhost:5173
```

Open http://localhost:8000.

Tip: `alias sail='vendor/bin/sail'`.

### Services

| Service        | What it is                    | Exposed on            |
|----------------|-------------------------------|-----------------------|
| `laravel.test` | PHP 8.4 app container (Sail)  | http://localhost:8000 |
| `queue`        | `php artisan queue:work`      | -                     |
| `scheduler`    | `php artisan schedule:work`   | -                     |
| `pgsql`        | PostgreSQL 17                 | host `localhost:5433` |

Ports are overridable via `.env`: `APP_PORT`, `VITE_PORT`, `FORWARD_DB_PORT`.

### Everyday commands

```bash
sail up -d                  # start
sail down                   # stop
sail artisan migrate
sail artisan test
sail composer install
sail npm run dev            # Vite dev server / HMR
sail shell                  # shell in the app container
sail psql                   # psql into the pgsql service
```

Unlike the previous setup, `vendor/` and `node_modules/` are plain host
directories bind-mounted into the container - host tooling (IDE, LSP) sees
everything directly.

## Production (GHCR images)

`.github/workflows/docker.yml` tests, builds and pushes
`ghcr.io/tadeasditte/vyjebanaskola` on every push to `main` (`latest` +
`sha-*` tags) and on `v*` tags (semver tags).

On the server you need exactly two files: `docker-compose.prod.yml` and a
`.env` (start from `.env.production.example`; APP_KEY and DB_PASSWORD are
required).

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

The stack: nginx (`web`, published on `APP_PORT`, default 80) → PHP-FPM
(`app`) plus `queue`, `scheduler` and `db` (PostgreSQL 17, not exposed).
On boot the `app` container syncs the baked `public/` (with built Vite
assets) into a shared volume for nginx, waits for Postgres, runs
`php artisan migrate --force`, and `php artisan optimize`s. Uploads and
logs persist in the `storage` volume; the database in `db-data`.

To roll out a new version:

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d   # recreates changed containers
```

Pin a specific release by setting `APP_IMAGE` in `.env`
(e.g. `ghcr.io/tadeasditte/vyjebanaskola:1.2.0`).

If the GHCR package is private, log in first:
`docker login ghcr.io -u <user>` with a token that has `read:packages`.

### Building the production image locally

```bash
docker build --target production -t vyjebanaskola:prod .
```

Builds PHP deps with `--no-dev`, compiles frontend assets, and bakes
everything into the image (no bind mounts, no Node in the final layer).
