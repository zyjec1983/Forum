# Academic Forum (ECOMUNDO)

A secure academic forum application (vanilla PHP 8 + MySQL) built for the ECOMUNDO
institution. Students, teachers, guest auditors and administrators manage academic
forums with a participation time window, classroom assignment and a full security
audit log.

---

## Table of contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Architecture overview](#architecture-overview)
- [Request lifecycle](#request-lifecycle)
- [Routing table](#routing-table)
- [Database schema](#database-schema)
- [Helpers and security guards](#helpers-and-security-guards)
- [Anti-capture / anti-cheat frontend](#anti-capture--anti-cheat-frontend)
- [Guest accounts (parents / auditors)](#guest-accounts-parents--auditors)
- [Timezone handling](#timezone-handling)
- [Project structure](#project-structure)
- [Installation (local XAMPP)](#installation-local-xampp)
- [Default accounts](#default-accounts)
- [How to use (Admin and Teacher)](#how-to-use-admin-and-teacher)
- [Deployment to a production host (InfinityFree)](#deployment-to-a-production-host-infinityfree)
- [Migrating an existing database to production](#migrating-an-existing-database-to-production)
- [Testing and verification](#testing-and-verification)
- [Known security limits](#known-security-limits)

---

## Features

- **Four roles** – `student` (participates in forums), `guest` (invited parent/auditor,
  read-only view), `teacher` (self-registers and manages their own classrooms, forums
  and students), `admin` (super-administrator with full control and deletion rights).
- **Student data capture & authentication** – registration accepts only the configured
  institutional domains (e.g. `@ecomundo.edu.ec`); the list is editable in Configuration;
  accounts are locked after repeated failed sign-ins.
- **Teacher accounts** – self-registration with its own form (Soy estudiante / Soy docente);
  teachers own the classrooms they create and only see their students, forums and audit entries.
- **Forum management** – create, edit, re-open, activate and delete forums; each forum
  defines a time window (`open_at` / `close_at`).
- **Classroom assignment** – each forum is assigned to one or more classrooms via checkboxes.
  Students only see and access the forums of their own classroom.
- **Student experience** – a "My forums" sidebar lists every forum assigned to the student's
  classroom, with its status (Open / Closed / Scheduled) and time range. Only the active forum
  accepts participation; other assigned forums are read-only.
- **Participation rules** – one response to the teacher, unlimited replies to partners, and one
  final conclusion, validated server-side.
- **Anti-cheat security** – copy, cut, paste, text selection, drag and developer-tools are
  blocked on forum pages; every attempt is logged with user, date/time, IP and device platform.
- **Screen-capture shield** – a synchronous full-screen shield covers the page on any capture
  attempt (PrtSc, snip tool, window/tab switch), the clipboard is wiped (text **and image**
  formats), and the student's session is force-closed.
- **Security audit** – `security_logs` records logins, lockouts, time-window violations,
  hacking attempts and all blocked actions; browsable from the panel. The super-administrator
  can delete individual entries or clear the whole log.
- **Configuration (domains)** – super-admin and teachers can toggle "accept any domain" and
  edit the list of accepted email domains.
- **Guest accounts (read-only)** – teachers create invited accounts (`guest` role) with a
  fictitious email + password, bound to one of their classrooms. Guests sign in and see the
  same student views but with no interaction: no responses, no conclusion, no admin panel,
  and no anti-cheat shield.

---

## Tech stack

- PHP 8.x (vanilla, **no framework**)
- MySQL / MariaDB (MySQLi prepared statements)
- Vanilla JavaScript (no jQuery)
- Bootstrap 5.3 (CDN) + Bootstrap Icons
- SweetAlert2 (CDN) for alerts/confirmations
- Apache via XAMPP (`.htaccess` LiteSpeed/mod_rewrite compatible)

---

## Architecture overview

The application follows a **model–view–controller (MVC)** pattern with a single entry point
(front controller). It is intentionally framework-free: every piece is explicit and auditable.

```
Browser
   │  GET/POST  http://host/my-forum?url=admin/forum
   ▼
index.php  (front controller: session, autoload, helpers, Router::dispatch)
   │
   ▼
app/Core/Router.php  (matches method + URL against app/routes.php)
   │
   ▼
app/Controllers/*   (validation, business rules, security guards)
   │       │
   │       ├──► app/Models/*   (Database, User, Salon, Forum, Response, SecurityLog, Settings)
   │       │
   │       └──► app/Core/View.php  +  app/Views/*  (templates render $data)
   │
   ▼
HTML to the browser  (+ public/js/*, public/css/*)
```

### Directory roles

| Path | Responsibility |
|------|----------------|
| `index.php` | Front controller: starts the session, autoloads classes, loads helpers, dispatches the request. |
| `config/config.php` | DB credentials, business rules, base URL, **application timezone**. |
| `app/Core/Router.php` | Registers routes (GET/POST) and resolves `Controller@method`. |
| `app/Core/Controller.php` | Base controller (view rendering, JSON output helpers). |
| `app/Core/View.php` | Small template renderer (extracts `$data` into the view). |
| `app/Helpers/functions.php` | Global helpers: escaping, URLs, flash, CSRF, roles, domains, time windows. |
| `app/Controllers/` | `AuthController`, `ForumController`, `AdminController`, `HomeController`. |
| `app/Models/` | Data access layer (all queries use prepared statements). |
| `app/Views/` | Templates rendered on the server. |
| `public/js/app.js` | Browser helpers (`App.baseURL`, `App.csrf`, `App.post`, `App.alert`, `App.confirm`). |
| `public/js/forum.js` | Forum countdown, time-window control and AJAX participation. |
| `public/js/security.js` | Anti-capture / anti-cheat client defence. |
| `public/css/app.css` | Forum lock, watermark, capture shield, admin styles. |

---

## Request lifecycle

1. `.htaccess` rewrites every non-file/non-directory request to `index.php?url=$1&...`.
2. `index.php` starts the session (`MYFORUM_SESSION`), loads `config/config.php` (which sets the
   timezone to `America/Guayaquil`), registers the PSR-0-style autoloader and the helpers.
3. `Router::dispatch($method, $url)` solves the route (e.g. `POST forum/respond-teacher`) and
   instantiates the controller.
4. The controller method validates the request:
   - **role guards** (`require_login`, `require_student`, `require_staff`, `require_admin`),
   - **CSRF check** on every state-changing request (`csrf_check()`),
   - **business rules** (time window, duplicate submissions, classroom scope),
   - model calls and finally either a rendered view or a JSON response (`json_out`).
5. `View` renders the template with the controller's `$data`.

---

## Routing table

Defined in `app/routes.php`.

### Public / authentication
| Method & path | Controller action |
|---|---|
| `GET /` | `HomeController@index` (redirects to registration or to the forum) |
| `GET/POST auth/register` | `AuthController@showRegister / register` |
| `GET/POST auth/register-teacher` | `AuthController@showRegisterTeacher / registerTeacher` |
| `GET/POST auth/login` | `AuthController@showLogin / login` |
| `GET/POST auth/recover` | `AuthController@showRecover / recover` |
| `GET auth/logout` | `AuthController@logout` |

### Student forum
| Method & path | Controller action |
|---|---|
| `GET forum` | `ForumController@show` (forums of the student's classroom) |
| `POST forum/respond-teacher` | `ForumController@respondTeacher` (one per student) |
| `POST forum/respond-partner` | `ForumController@respondPartner` (replies to classmates) |
| `POST forum/conclusion` | `ForumController@saveConclusion` (one per student) |
| `POST forum/report` | `ForumController@reportSecurity` (client-side security events) |

### Admin panel (staff: admin + teachers, scoped)
| Method & path | Controller action |
|---|---|
| `GET admin` | `AdminController@dashboard` |
| `GET admin/forum` · `POST admin/forum/create/edit/activate/reopen/delete` | Forum management |
| `GET admin/salones` · `POST admin/salones/save/delete` | Classroom management (delete = admin only) |
| `GET admin/students` · `POST admin/students/save/toggle/delete` | Student management (delete = admin only) |
| `GET admin/guests` · `POST admin/guests/save/toggle/delete` | Guest accounts (teacher manages own classroom) |
| `GET admin/teachers` · `POST admin/teachers/delete` | Teacher list (admin only) |
| `GET admin/settings` · `POST admin/settings/save` | Domain / registration settings |
| `GET admin/logs` · `POST admin/logs/delete/clear` | Security audit (delete/clear = admin only) |
| `GET admin/responses` · `POST admin/responses/delete` | Review participations (delete = admin only) |

---

## Database schema

Database `my_forum`, charset `utf8mb4`. All foreign keys are `InnoDB`.

### `settings` — global key–value configuration
| Column | Notes |
|---|---|
| `setting_key` (PK) | e.g. `allow_any_domain`, `accepted_domains` |
| `setting_value` | `0`/`1`, or comma-separated domains |

### `salones` — classrooms
| Column | Notes |
|---|---|
| `id` (PK) | |
| `name` | unique (e.g. `9th "A"`) |
| `teacher_id` FK → `users.id` | owner; `ON DELETE CASCADE` (a deleted teacher takes their salons) |
| `created_at` | |

### `users` — students, teachers, guests, admin
| Column | Notes |
|---|---|
| `email` | unique |
| `first_name` / `last_name` | |
| `salon_id` FK → `salones.id` | `ON DELETE SET NULL` |
| `password` | bcrypt (`password_hash`) |
| `role` | **ENUM `student`, `teacher`, `admin`, `guest`** |
| `failed_attempts` | login lock counter |
| `locked` | hard lock flag |
| `created_at` | |

### `forums` — the activity with a time window
| Column | Notes |
|---|---|
| `title`, `subject`, `question` | the question students must answer |
| `open_at` / `close_at` | participation window (local time of the app timezone) |
| `is_active` | only the active forum in a classroom accepts responses |
| `created_by` FK → `users.id` | `ON DELETE SET NULL` |
| `created_at` | |

### `forum_salones` — assignment (forum ↔ classroom, m:n)
Composite PK `(forum_id, salon_id)`; both FKs `ON DELETE CASCADE`.

### `responses` — participations
| Column | Notes |
|---|---|
| `forum_id` FK | `ON DELETE CASCADE` |
| `user_id` FK | `ON DELETE CASCADE` |
| `parent_id` FK → `responses.id` | for partner replies (`NULL` = root) |
| `type` | **ENUM `teacher`, `partner`, `conclusion`** |
| `content` | the text |
| `created_at` | |
| `idx_responses_user_forum_type` | key to enforce one response/conclusion per student |

### `security_logs` — audit trail
| Column | Notes |
|---|---|
| `user_id` FK | `ON DELETE SET NULL` (`NULL` = anonymous attempt) |
| `event` | `login`, `logout`, `failed_login`, `lock`, `attempt_copy`, `attempt_printscreen`,
  `time_block`, `hack`, `guest_*` etc. |
| `detail` | e.g. `Print Screen key pressed · Device: Windows` |
| `ip`, `user_agent`, `created_at` | who / when |

**Seed data:** the `settings` rows, five example salons, the default admin
(`admin@ecomundo.edu.ec` / `Admin@2026`), one active example forum assigned to all salons.

---

## Helpers and security guards

All in `app/Helpers/functions.php`:

| Group | Functions |
|---|---|
| Output | `e()` (HTML escape), `base_url()`, `asset()`, `url_current()` |
| HTTP | `redirect()`, `redirect_back()`, `json_out()` |
| CSRF | `csrf_token()`, `csrf_field()`, `csrf_check()` |
| Flash | `flash_set()`, `flash_get()` |
| Client | `client_ip()`, `client_user_agent()` |
| Guards | `require_login()`, `require_student()`, `require_admin()`, `require_staff()` |
| Roles | `current_user()`, `is_logged()`, `is_staff()`, `is_admin_user()`, `redirect_after_login()` |
| Domains | `setting_get()`, `any_domain_allowed()`, `allowed_domains()`, `domain_allowed()` |
| Time window | `diff_parts()`, `expired_message()`, `starts_in_message()`, `time_status()`, `window_progress()` |
| Misc | `initials()`, `avatar_color()`, `pretty_datetime()`, `pretty_date()`, `time_ago()` |

**Guard behavior**
- `require_staff()` → admin **or** teacher; guests/students get a 403 JSON error.
- `require_admin()` → admin only; every destructive action (delete/clear) is wrapped in
  `confirm()` on the client and enforced again on the server.
- `domain_allowed($email)` → `allow_any_domain=1` accepts everything; otherwise the email
  must end with one of `accepted_domains`.
- `redirect_after_login()` → staff go to `/admin`, everyone else to `/forum`.

---

## Anti-capture / anti-cheat frontend

`public/js/security.js` is loaded **only for students on forum pages** (guests never load it).

### Blocked actions (with a logged SweetAlert warning)
- Copy / Cut / Paste / text selection (`selectstart`) / drag
- Context menu (right click)
- `Ctrl+C`, `Ctrl+X`, `Ctrl+V`, `Ctrl+A`
- `F12` and `Ctrl+Shift+I/J/C/U/P/S`
- DevTools panel detection (window-dimension heuristic, checked every 5 s and on `resize`)

### Clipboard wipe (best-effort, multi-format)
Windows keeps **several formats** per clipboard entry; writing text alone leaves the bitmap of
a capture intact. `overwriteClipboard()` therefore:
1. Writes `' '` with the async Clipboard API **and** the `document.execCommand('copy')` fallback
   (so a rejected promise never skips the wipe);
2. **Overwrites the image format with a blank 1×1 transparent PNG** (`ClipboardItem`) so pasting
   the capture into Paint / Photos shows nothing;
3. Wipes on `clipboardchange` (with retries), on Print Screen and immediately when the tab
   regains focus (clean-up after a snip done in the background).

### Capture shield (black screen + forced sign-out)
- **Print Screen / Alt+Print Screen**, the **Snipping tool**, a **window/tab switch** (Alt+Tab,
  another app, background tab) hide the tab → `visibilitychange → hidden` or the synchronous
  key handler triggers `forceSignOut()`.
- A `window blur` is only treated as a capture attempt **when the tab really left the screen**
  (`document.visibilityState === 'hidden'`), so clicking/typing in the answer box never fires it.
- `forceSignOut()` shows the full-screen `.capture-shield` (black, with a "Sign out" button),
  wipes the clipboard, reports the event via `sendBeacon` and redirects to logout after ~3 s.
- **macOS** shortcuts `Cmd+Shift+3/4/5` are intercepted on `keydown`.
- **Watermark:** the student's name + email are drawn diagonally on the page (`.screen-watermark`).

### Device/platform awareness
`security.js` detects the OS from the user agent (`Windows`, `macOS`, `Android`, `iOS`,
`Linux`, `Unknown`) and appends `· Device: <platform>` to **every** security log, so the
teacher sees which system each attempt came from. Capture handling per platform:
- **Windows** – key interception + clipboard wipe (text + PNG) + shield on hidden.
- **macOS** – `Cmd+Shift+3/4/5` key interception + wipe + shield on hidden.
- **Android/iOS** – the screenshot gesture hides the tab → shield + forced sign-out + log.

### Reporting / throttling
Client events post to `forum/report` (CSRF-protected). Report throttling: 90 s for devtools,
45 s for the rest, to avoid flooding the log.

---

## Guest accounts (parents / auditors)

From **Guest Accounts** (`/admin/guests`) a teacher creates an invited account with a fictitious
email and a password, bound to one of the classrooms **they own**. The credentials are shared
with the parent or auditor, who signs in on the normal page and is redirected to `/forum`.

- Guests see the classroom's forums and their contents, exactly like a student.
- There is **no** response form, final conclusion box or admin panel; the server rejects any
  participation attempt by a guest (`{"ok":false,"hack":true,...}`).
- The anti-cheat shield **is not** applied to guests (they may legally view the screen).
- Teachers can lock/unlock and delete **their own** guests; the admin manages all of them.

---

## Timezone handling

Ecuador operates on **UTC–5 year round (no DST)**. `config/config.php` sets
`date_default_timezone_set('America/Guayaquil')`, so **every** `open_at`, `close_at`, countdown
and audit timestamp is interpreted in local Ecuador time regardless of the server's own `php.ini`
(XAMPP defaults to `Europe/Berlin`, which would otherwise offset assignments by 7 hours).

At the frontend, the forum page receives the server's timestamp (`data-server`) and the window
(`data-open` / `data-close`) in epoch seconds; `forum.js` keeps the countdown synchronized with a
computed offset so the student's clock does not matter.

---

## Project structure

```
my-forum/
├── .htaccess                # URL rewriting (LiteSpeed/mod_rewrite compatible)
├── index.php                # front controller
├── README.md
├── config/
│   └── config.php           # DB connection, business rules, timezone (America/Guayaquil)
├── database/
│   └── schema.sql           # full schema + seed data (phpMyAdmin import)
├── public/
│   ├── css/
│   │   └── app.css          # forum lock, watermark, capture shield, admin styles
│   └── js/
│       ├── app.js           # App helper (baseURL, csrf, post, alert, confirm)
│       ├── forum.js         # countdown, time-window control, AJAX participation
│       └── security.js      # anti-capture / anti-cheat defence
└── app/
    ├── routes.php           # all routes
    ├── Core/
    │   ├── Controller.php
    │   ├── Router.php
    │   └── View.php
    ├── Helpers/
    │   └── functions.php    # guards, CSRF, flash, domains, time window, etc.
    ├── Controllers/
    │   ├── HomeController.php
    │   ├── AuthController.php
    │   ├── ForumController.php
    │   └── AdminController.php
    ├── Models/
    │   ├── Database.php
    │   ├── User.php
    │   ├── Salon.php
    │   ├── Forum.php
    │   ├── Response.php
    │   ├── SecurityLog.php
    │   └── Settings.php
    └── Views/
        ├── auth/            # login, register, register_teacher, recover
        ├── forum/           # index, empty, _aside, partials
        ├── admin/           # dashboard, forum, salones, students, guests, teachers,
        │                    # settings, logs, responses + head/foot partials
        ├── shared/          # _head, _foot, _flash
        └── errors/          # 404
```

---

## Installation (local XAMPP)

1. Copy the project folder into the web root as `my-forum` (e.g. `C:\xampp\htdocs\my-forum`).
2. Start Apache and MySQL from the XAMPP Control Panel.
3. In phpMyAdmin create a database named `my_forum` (utf8mb4 / utf8mb4_unicode_ci), select it,
   and import `database/schema.sql` (creates the tables + seed data).
4. Check the database connection in `config/config.php` (defaults: `localhost`, user `root`,
   empty password, database `my_forum`).
5. Open `http://localhost/my-forum`.
6. **Optional:** `curl -s "https://cdn.jsdelivr.net"` availability for Bootstrap/SweetAlert CDNs.

---

## Default accounts

`schema.sql` seeds a single account:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@ecomundo.edu.ec` | `Admin@2026` |

No student or teacher is seeded — create them from the sign-in page
(**Soy estudiante / Soy docente**), or as admin from **Students** / **Teachers**.
The institutional domain (`@ecomundo.edu.ec`) is accepted by default.

> Change the admin password after the first sign-in.

---

## How to use (Admin and Teacher)

The panel at `/admin` is shared by the administrator and the teachers. A teacher only sees their
own classrooms, students, forums and audit entries. The **Teachers** section and all **delete**
actions are exclusive to the administrator.

- **Forum Management** (`/admin/forum`): create a forum (title, subject, question, time window)
  and tick the classrooms that will participate. Use **Edit**, **Re-open** (extend an expired
  window), **Activate** (only one active forum at a time) and, as admin, **Delete**.
- **Classrooms** (`/admin/salones`): teachers create their own classrooms; the admin can assign
  an owner and delete.
- **Students** (`/admin/students`): register students and assign them to a classroom;
  block/unblock accounts; admin can delete permanently.
- **Guest Accounts** (`/admin/guests`): create guest accounts (any email + password) bound to one
  classroom. Teachers manage the guests of their own classrooms; admin manages all.
- **Teachers** (`/admin/teachers`, admin only): list teachers and delete them along with their
  classrooms, forums and data.
- **Configuration** (`/admin/settings`): toggle "accept any domain" and edit the accepted domains.
- **Responses** (`/admin/responses`): review participations and the partner-reply summary;
  admin can delete individual responses.
- **Security Log** (`/admin/logs`): full audit; admin can delete entries or clear the log.

---

## Deployment to a production host (InfinityFree)

1. **Upload the files** (FTP or File Manager) into the web root — everything top-level goes
   to `htdocs/`. `.htaccess` (LiteSpeed) works as-is.
2. **Create the MySQL database** in the control panel and write down the **real** values:
   host (e.g. `sqlXXX.epizy.com` / `sql3.freesqldatabase.com`), database name, user and password.
   They must NOT point to `localhost`.
3. **Edit `config/config.php`** for production:
   ```php
   define('DB_HOST', '<your-host>');
   define('DB_USER', '<your-user>');
   define('DB_PASS', '<your-password>');
   define('DB_NAME', '<your-db-name>');
   define('DEBUG', false);   // never leave DEBUG true in production
   ```
   (A commented-out "Production" block is already provided in the file.)
   The timezone stays `America/Guayaquil` — no server php.ini changes are needed.
4. **Import the database** (see next section).
5. **HTTPS:** InfinityFree's free SSL covers their subdomains. If you use a custom domain,
   terminate TLS with Cloudflare (their Flexible/Full modes) so security cookies can be
   `secure`. A production hardening you can add in that case:
   ```php
   ini_set('session.cookie_httponly', '1');
   ini_set('session.cookie_samesite', 'Lax');
   ```
6. **Re-test E2E against the production URL** (see Testing).

> Free shared hosts often let PHP generate a random session directory; if sessions don't
> persist, set an explicit `session.save_path` inside a writable folder of the project.

---

## Migrating an existing database to production

Fresh install: **create the database first in the control panel** (e.g. `my_forum`), select it in
phpMyAdmin, then import `database/schema.sql`. The file has no `CREATE DATABASE`/`USE` statements,
so it imports even when the MySQL user only has rights over its own database (typical on shared hosts).

Migrate the current local data instead (recommended for a real rollout):

```powershell
# export (XAMPP)
C:\xampp\mysql\bin\mysqldump.exe -u root my_forum > C:\Temp\my_forum.sql

# then import my_forum.sql via phpMyAdmin on the production host
```

> Before going live, decide what data to carry over: test users, salons and example forums
> may be regenerated from scratch instead of migrated.

---

## Testing and verification

### Static checks
```powershell
php -l config/config.php            # PHP syntax
foreach ($f in (Get-ChildItem app -Recurse -Filter *.php)) { php -l $f.FullName }
node --check public/js/security.js  # JS syntax
node --check public/js/forum.js
node --check public/js/app.js
```

### End-to-end smoke test (cookies + CSRF)
```powershell
# 1) login
curl.exe -s -c c.jar -b c.jar "http://localhost/my-forum/auth/login" -O
# 2) open the forum as a student and confirm the page renders the window + security.js
curl.exe -s -b c.jar "http://localhost/my-forum/forum"
# 3) verify security events were recorded
mysql.exe -u root my_forum -e "SELECT id,user_id,event,detail,created_at FROM security_logs ORDER BY id DESC LIMIT 10;"
```

### What to test manually after every change
- Sign-in routes for each role (student / teacher / admin / guest) and the post-login redirect.
- Forum visibility per classroom and the time-window banner (open / scheduled / expired).
- One response to the teacher, partner replies, one final conclusion, duplicate rules.
- Capture scenarios (PrtSc, Snipping, Alt+Tab) → black shield + wipe + forced sign-out;
  clicks/typing in the answer box must **not** trigger it.
- Guest session: read-only views, 403 to `/admin`, server rejection of participation.

---

## Known security limits

- A website running in a normal browser **cannot prevent the operating system from taking a
  screenshot**: Windows grabs the frame before the page can paint anything. The shield, the
  clipboard wipe (text + image formats) and the forced sign-out are the strongest available
  deterrence — and every attempt is logged with user, IP, date and device. A 100% block requires
  running the exam in a kiosk / safe-exam-browser.
- **Windows clipboard history (`Win+V`)** cannot be cleared by a web page; only the current
  clipboard entry is overwritten.
- On **Android/iOS** the clipboard is not reliably writable from a page; detection relies on
  the tab-hiding visibility event → shield + forced sign-out + device log.
- All business rules (time window, roles, duplicates, classroom scope) are enforced **server-side**,
  so client defence failures never allow an unauthorized action.