# Enterprise School Management System

Monorepo that brings together four school systems. Each one lives in `apps/` with its full git history, and each still runs on its own.

| App | Folder | Stack | Database | Upstream repo |
|---|---|---|---|---|
| Employee Management System | [`apps/ems`](apps/ems) | Laravel 13, PHP 8.3, Vite | MySQL (`ems`) | [yumulniccolo/EMS](https://github.com/yumulniccolo/EMS) |
| Time, Attendance & Payroll | [`apps/tap-pms`](apps/tap-pms) | HTML + jQuery + Bootstrap 5 | none yet (mock API in browser) | [JD-Slayin/TAP-PMS](https://github.com/JD-Slayin/TAP-PMS) |
| Admission System | [`apps/admission`](apps/admission) | Laravel 13, PHP 8.3 | SQLite | [hanimehub06-eng/Admission_System](https://github.com/hanimehub06-eng/Admission_System) |
| Document Management | [`apps/document-management`](apps/document-management) | Laravel 12, PHP 8.2, Vite | MySQL (`dms`) | [AAAAA13-sys/school-document-management](https://github.com/AAAAA13-sys/school-document-management) |

## Running an app

### Laravel apps (`ems`, `admission`, `document-management`)

```bash
cd apps/<app>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate        # create the database named in .env first (MySQL apps)
npm install && npm run dev # ems and document-management only
php artisan serve --port=<port>
```

Run each app on its own port so all three can run together, for example `8001` for EMS, `8002` for Admission and `8003` for Document Management.

Check each app's own README for seeders and demo accounts.

### TAP-PMS

Open `apps/tap-pms/index.html` in Chrome or Edge. See [its README](apps/tap-pms/README.md).

## Keeping in sync with the original repos

Teams still push to their original repositories. To pull their latest work into this monorepo:

```bash
scripts/sync.sh              # all apps
scripts/sync.sh admission    # one app
```

The script runs `git subtree pull` for each app. Changes to an app can also be made here and sent back upstream with `git subtree push --prefix=apps/<app> <repo-url> <branch>`, preferably to a new branch so the team can review it as a pull request.
