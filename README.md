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

## Where do I make changes?

**This monorepo is a read-only mirror.** Each team keeps working in its original repository, with its own branch rules and CI. Changes flow one way: original repo → monorepo.

| To change... | Push to |
|---|---|
| `apps/ems` | [yumulniccolo/EMS](https://github.com/yumulniccolo/EMS) (`main`) |
| `apps/tap-pms` | [JD-Slayin/TAP-PMS](https://github.com/JD-Slayin/TAP-PMS) (`main`) |
| `apps/admission` | [hanimehub06-eng/Admission_System](https://github.com/hanimehub06-eng/Admission_System) (`main`) |
| `apps/document-management` | [AAAAA13-sys/school-document-management](https://github.com/AAAAA13-sys/school-document-management) (`master`) |

Only root-level files (this README, `scripts/`, `.github/`) are edited here. Pull requests that touch `apps/` are blocked by the **Guard apps** check.

## How syncing works

The **Sync upstream apps** workflow ([`.github/workflows/sync-upstream.yml`](.github/workflows/sync-upstream.yml)) runs on a schedule. It pulls each original repo's default branch into its `apps/` folder with `git subtree pull` and pushes the result to `main`. Only the default branches are synced. Feature branches stay in the original repos until they are merged there.

To sync right away, open **Actions → Sync upstream apps → Run workflow** on GitHub, or run it locally:

```bash
scripts/sync.sh              # all apps
scripts/sync.sh admission    # one app
```

If the sync fails, it is usually because someone edited `apps/` here and it conflicts with upstream. Revert that edit and make it in the original repo instead.

The CI workflows inside `apps/*/.github/` do not run in this repo. GitHub only reads workflows from the root `.github/workflows/`. They keep running in the original repos.
