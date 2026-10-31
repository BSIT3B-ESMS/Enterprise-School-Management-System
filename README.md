# Enterprise School Management System

Monorepo that brings together four school systems. Each one lives in `apps/` with its full git history, and each still runs on its own.

| App | Folder | Stack | Database | Upstream repo |
|---|---|---|---|---|
| Employee Management System | [`apps/ems`](apps/ems) | Laravel 13, PHP 8.3, Vite | MySQL (`ems`) | [yumulniccolo/EMS](https://github.com/yumulniccolo/EMS) |
| Time, Attendance & Payroll | [`apps/tap-pms`](apps/tap-pms) | Laravel 13, PHP 8.4 | MySQL (`tap_pms`) | [timanfrenchie-lgtm/tap-pms-laravel](https://github.com/timanfrenchie-lgtm/tap-pms-laravel) |
| Admission System | [`apps/admission`](apps/admission) | Laravel 13, PHP 8.3 | SQLite | [hanimehub06-eng/Admission_System](https://github.com/hanimehub06-eng/Admission_System) |
| Document Management | [`apps/document-management`](apps/document-management) | Laravel 12, PHP 8.2, Vite | MySQL (`dms`) | [AAAAA13-sys/school-document-management](https://github.com/AAAAA13-sys/school-document-management) |

## Running an app

### Laravel apps (`ems`, `tap-pms`, `admission`, `document-management`)

```bash
cd apps/<app>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate        # create the database named in .env first (MySQL apps)
npm install && npm run dev # ems and document-management only
php artisan serve --port=<port>
```

Run each app on its own port so they can all run together, for example `8001` for EMS, `8002` for Admission, `8003` for Document Management and `8004` for TAP-PMS.

Check each app's own README for seeders and demo accounts.
