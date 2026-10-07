# SavingsApp

![SavingsApp banner](.github/img/banner.png)

SavingsApp is an MVP and a personal web application for recording and keeping
track of financial accounts, income, and expenses.

The application uses ISO 4217 currency codes and currently includes the US
dollar (USD), Colombian peso (COP), and Japanese yen (JPY). The currency data
model includes each currency's minor-unit precision, so additional currencies
can be added to the database as the application grows.

SavingsApp can connect to MySQL or SQLite. MySQL can be hosted locally or as a
database service; SQLite provides a file-based database option. Liquibase can
be used to initialize the MySQL schema.

## Requirements

- PHP 8.3 or later
- Composer
- PHP extensions:
  - `bcmath` and `json`
  - `pdo_mysql` when using MySQL
  - `pdo_sqlite` when using SQLite
- A MySQL server or SQLite support, depending on the selected database
- Liquibase and a compatible Java runtime if using Liquibase

## Installation

1. Install the Composer dependencies from the project root:

   ```sh
   composer install
   ```

2. Create the application environment file by copying `.env.example` to
   `.env`:

   ```sh
   # macOS / Linux
   cp .env.example .env

   # Windows PowerShell
   Copy-Item .env.example .env
   ```

   For a local development environment, the example values can be a starting
   point. For any other environment, configure the application name, timezone,
   database settings, and logging options as appropriate. Set `APP_DEBUG=true`
   and `APP_PRODUCTION=false` for local development. In production, set
   `APP_DEBUG=false` and `APP_PRODUCTION=true`.

3. Configure the database in `.env`.

   **MySQL:** Set `DB_DRIVER=mysql`, create the database named by `DB_NAME`, and
   provide the correct `DB_HOST`, `DB_PORT`, `DB_USER`, and `DB_PASSWORD`.

   **SQLite:** Set `DB_DRIVER=sqlite` and set `SQLITE_DATABASE` to the **full
   path** of the SQLite database file. For example:

   ```dotenv
   DB_DRIVER=sqlite
   SQLITE_DATABASE=/absolute/path/to/SavingsApp/storage/database.sqlite
   ```

   On Windows, use the full path to the file, for example
   `C:/path/to/SavingsApp/storage/database.sqlite`.

4. Initialize the database schema.

   **MySQL with Liquibase:** Copy
   `liquibase/mysql/liquibase.properties.example` to
   `liquibase/mysql/liquibase.properties`, then edit the connection URL,
   username, password, and other settings for your database. Run Liquibase
   from the MySQL directory:

   ```sh
   cd liquibase/mysql
   liquibase --defaults-file=liquibase.properties update
   ```

   The MySQL changelog is
   [`liquibase/mysql/changelog/changelog-master.sql`](liquibase/mysql/changelog/changelog-master.sql).
   If Liquibase is unavailable, create the database first and execute that
   changelog SQL against it using a MySQL client.

   **SQLite:** The application supports SQLite connections, but this repository
   does not currently include a SQLite-compatible master changelog. The SQLite
   Liquibase properties template references a changelog that is not present.
   Create and initialize the database using a schema compatible with the
   current application before running it; do not execute the MySQL changelog
   against SQLite.

5. Create the storage directories if they do not already exist:

   ```sh
   # macOS / Linux
   mkdir -p storage/tmp storage/users/pfp

   # Windows PowerShell
   New-Item -ItemType Directory -Force -Path storage/tmp, storage/users/pfp
   ```

   Make sure the user or service account running PHP can read and write these
   directories. The application needs to create, move, and access temporary
   files and user profile pictures. On macOS or Linux, for example, grant
   write access to the appropriate owner/group rather than making the
   directories world-writable.

6. Start the local PHP development server:

   ```sh
   composer start
   ```

   Then open [http://localhost:8080](http://localhost:8080). The built-in PHP
   server is for local development; use a properly configured web server for
   production.

## Future possibilities

SavingsApp is an MVP that can be expanded with features such as:

- Charts comparing income and expenses
- Filtering transactions by tags
- Optional AI-assisted interpretation of financial behavior
- Expanded administrator and regular-user roles and permissions

## Contact

If you are interested in the application or would like to discuss it, contact
me through [my GitHub profile](https://github.com/juansecydev).
