# Developer Setup

This guide explains how to reproduce the current Batch Meeting Scheduler environment with the repository as it exists today.

## Supported Setup

The primary supported local setup is Docker Compose from the `src/` directory.

- Web service: Apache + PHP 8.0
- Database service: MySQL 8.0
- SQL bootstrap: `src/db/capstone_project.sql`
- Web root mounted into the container: `src/web`

## Prerequisites

- Docker Desktop or another Docker Engine installation with Compose support
- A free local TCP port `5000` for the web application
- A free local TCP port `50000` for MySQL

Optional but useful:

- A MySQL client for inspecting the database
- A Gmail app password if invitation/result email delivery is required
- A Gemini API key if the chatbot must be enabled

## Project Structure You Need to Know

- `src/docker-compose.yml`
  Defines the local Docker environment.
- `src/Dockerfile.web`
  Builds the PHP + Apache web container.
- `src/db/capstone_project.sql`
  Creates and seeds the application schema.
- `src/web/config.php`
  Centralized environment-driven configuration defaults.
- `src/web/testwsqlnew/conn/conn.php`
  Reads the DB config and establishes the MySQL connection.

## Start the Application

From the `src/` directory, run:

```powershell
docker compose up --build
```

Expected result:

- MySQL starts and imports `db/capstone_project.sql`
- Apache serves the application on port `5000`
- The mounted code under `src/web` is available inside the container at `/var/www/html`

## Access the Running System

- Application homepage: `http://localhost:5000/index.html`
- Teacher landing page: `http://localhost:5000/teacherview.html`
- Student landing page: `http://localhost:5000/studentview.html`
- Chatbot page: `http://localhost:5000/chatbot.php`
- MySQL host from your machine: `localhost`
- MySQL port from your machine: `50000`

Current Compose defaults:

- Database name: `capstone_project`
- Database user: `appuser`
- Database password: `apppassword`
- Root password: `rootpassword`

These are development defaults only. Do not reuse them for production.

## Environment Variables and Configuration

The current codebase reads configuration from `src/web/config.php`. That file provides defaults and reads values from environment variables when present.

### Database

- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Docker Compose currently sets the DB values for the web container. `conn.php` reads them through `config.php`.

### Mail

- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_ENCRYPTION`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`
- `MAIL_STUDENT_DOMAIN`
- `MAIL_RESULT_DOMAIN`

The email queue worker uses these values when sending invitation and result notifications.

### Application URL

- `APP_BASE_URL`

This value is used when invitation emails include a link back to the application.

### Chatbot

- `GEMINI_API_KEY`
- `GEMINI_MODEL`

`api_chat.php` returns an error if `GEMINI_API_KEY` is not set.

## Recommended Local Configuration Practice

The current Compose file already includes the database environment variables and reads `GEMINI_API_KEY` and `GEMINI_MODEL` from the shell environment or an optional `.env` file.

Recommended approach for local work:

1. Keep `src/docker-compose.yml` as the source of truth for container wiring.
2. Put local secrets in a non-committed `.env` file next to `docker-compose.yml` when practical.
3. Prefer overriding values with environment variables instead of hardcoding credentials inside PHP files.

Example `.env` values:

```dotenv
GEMINI_API_KEY=your-api-key
GEMINI_MODEL=gemini-3.1-flash-lite-preview
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=your-account@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-account@gmail.com
MAIL_FROM_NAME=Batch Meeting Scheduler
MAIL_STUDENT_DOMAIN=@connect.polyu.hk
MAIL_RESULT_DOMAIN=@connect.polyu.hk
APP_BASE_URL=http://localhost:5000
```

## Database Bootstrap

The database container imports `src/db/capstone_project.sql` automatically on first initialization.

Important implications:

- The SQL file drops and recreates the application tables.
- Schema changes should be made intentionally and documented because re-initializing the container will rebuild from this file.
- If you already have a persistent MySQL volume from older work, schema changes may not apply until you recreate that state.

## Common Runtime Tasks

### Rebuild after Dockerfile or dependency changes

```powershell
docker compose up --build
```

### Stop the environment

```powershell
docker compose down
```

### View logs

```powershell
docker compose logs web
docker compose logs db
```

### Open a shell in the web container

```powershell
docker compose exec web bash
```

### Manually run the queue worker

```powershell
docker compose exec web php queue_worker.php
```

Run from `/var/www/html` inside the container, or specify the full path if you change directories.

## Integration Tests

The repository now includes a lightweight integration suite under `src/web/tests/` that exercises:

- meeting creation
- preference validation
- allocation after the deadline
- concurrent allocation requests without double booking

The suite uses the current `capstone_project` database and deletes only the records it creates.

Run the integration tests:

```powershell
docker compose exec web php tests/run_integration.php
```

## Email Delivery Notes

The system no longer sends instructor-triggered emails directly inside the request handler. Instead:

1. `sendmail.php` or `sendresultmail.php` insert jobs into `job_queue`.
2. The application attempts to start `queue_worker.php` in the background.
3. The worker uses PHPMailer and the mail settings from `config.php`.

If emails are not arriving:

- Confirm mail credentials and sender settings
- Confirm the worker can run in the current environment
- Inspect `job_queue.last_error`
- Verify the student email domain suffix values

## Chatbot Notes

The chatbot is optional.

- UI page: `chatbot.php`
- API endpoint: `api_chat.php`
- State: PHP session chat history
- External dependency: Gemini API

If the Gemini key is missing, the rest of the application can still function, but the chatbot page will not return successful replies.

## Known Setup Caveats

- `src/web/config.php` currently contains real-looking default mail credentials and a legacy default `APP_BASE_URL`. Treat these as development placeholders and override them locally.
- Some older PHP pages still assume the web root path pattern `/testwsqlnew/...` through `$_SERVER["DOCUMENT_ROOT"]`.
- The automated integration suite uses the shared development database, so avoid running it while manually editing the same meeting records.

## Troubleshooting

### The web page loads but DB actions fail

Check:

- the `db` container is healthy
- the imported schema exists
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` match the running DB

### `exam` table does not exist

This usually means the SQL bootstrap did not run successfully in MySQL, or you are connecting to an unexpected database instance.

### Emails queue but do not send

Check:

- the `job_queue` table contains jobs
- `last_error` shows authentication or transport failures
- the worker is actually being triggered
- mail environment variables override the placeholder defaults

### Chatbot returns `GEMINI_API_KEY is not set`

Set `GEMINI_API_KEY` in the Compose environment or `.env` and recreate the web service.

### Changes are not reflected

The source tree is bind-mounted into the web container. PHP file changes should appear immediately, but Dockerfile changes require a rebuild.

## Minimal Manual Fallback

Docker is the supported path, but the codebase also implies a manual stack of:

- Apache
- PHP 8 with `mysqli`, `pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`, and related extensions
- MySQL 8

If you use a manual stack, you will need to recreate the same DB schema, document root layout, and environment variables yourself.
