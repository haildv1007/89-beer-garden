# CPanel initial deployment

## Server requirements
- PHP 8.4 with pdo_mysql, mbstring, openssl, fileinfo, intl, gd, curl, tokenizer, xml, ctype and dom.
- MySQL 8.x or a compatible MariaDB release.
- Domain document root must point to the application's `public` directory.

## Initial installation
1. Extract the release outside `public_html` when cPanel permits it.
2. Point the domain document root to `<application>/public`.
3. Create a MySQL database and user in cPanel, then grant all privileges for that database.
4. Create `.env` from `.env.example` and set production values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-domain`
   - MySQL `DB_*` values
   - `SESSION_DOMAIN` for the production domain
   - mail/provider credentials as needed
5. Run:
   - `composer install --no-dev --optimize-autoloader`
   - `php artisan key:generate`
   - `php artisan migrate --force`
   - `php artisan storage:link`
   - `php artisan optimize`
6. Ensure `storage` and `bootstrap/cache` are writable by PHP.
7. Configure the scheduler cron: `* * * * * cd /path/to/app && php artisan schedule:run >/dev/null 2>&1`.
8. Configure a persistent queue worker or a cPanel cron appropriate for the hosting plan.

## Package notes
- Frontend assets in `public/build` are already compiled.
- `.env`, local logs, caches, sessions, tests, Git metadata, Node modules and local uploads are excluded.
- The bundled logo/banner remain safe fallbacks until images are uploaded in Admin > System settings.
