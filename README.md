# Academic Forum · ECOMUNDO

A secure academic forum application (PHP + MySQL) built for the ECOMUNDO institution.
Teachers and administrators create forums with a participation time window, assign them
to specific classrooms, and monitor all activity through a security audit log.

## Features

- **Student data capture & authentication** – only institutional emails (`@ecomundo.edu.ec`) are accepted; accounts are locked after repeated failed sign-ins.
- **Forum management (admin)** – create, edit, re-open and activate forums; each forum defines a time window (`open_at` / `close_at`).
- **Classroom assignment** – each forum is assigned to one or more classrooms via checkboxes. Students only see and access the forums of their own classroom.
- **Student experience** – a "My forums" sidebar lists every forum assigned to the student's classroom, with its status (Open / Closed / Scheduled) and time range. Only the active forum accepts participation; other assigned forums are read-only.
- **Participation rules** – one response to the teacher, unlimited replies to partners, and one final conclusion, validated server-side.
- **Anti-cheat security** – copy, cut, paste, text selection, drag and developer-tools are blocked on forum pages; every attempt is logged with user, date/time and IP.
- **Screen-capture shield** – a synchronous full-screen shield covers the page on any capture attempt (PrtSc, snip tool, window/tab switch), the screenshot shows black, the clipboard is wiped (best-effort), and the student's session is force-closed.
- **Security audit** – `security_logs` records logins, lockouts, time-window violations, hacking attempts and all blocked actions; browsable from the Admin panel.

## Tech stack

- PHP 8.x (vanilla, no framework)
- MySQL / MariaDB (MySQLi prepared statements)
- Vanilla JavaScript
- Bootstrap 5.3 (CDN) + Bootstrap Icons
- Apache via XAMPP (`.htaccess` rewrites to `index.php`)

## Requirements

- XAMPP (Apache + MariaDB + PHP 8) or equivalent
- A web browser (Chrome / Edge / Firefox)

## Installation

1. Copy the project folder into your web root as `my-forum` (e.g. `C:\xampp\htdocs\my-forum`).
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Import `database/schema.sql` via phpMyAdmin (creates the `my_forum` database, tables and seed data).
4. Adjust the database connection in `config/config.php` (defaults: `localhost`, user `root`, empty password, database `my_forum`).
5. Open `http://localhost/my-forum`.

## Default accounts

| Role    | Email                                   | Password      |
|---------|-----------------------------------------|---------------|
| Admin   | `admin@ecomundo.edu.ec`                 | `Admin@2026`  |
| Student | `maria.andrade@ecomundo.edu.ec`         | `Student@2026`|

> Change these passwords after the first sign-in.

## How to use (Admin)

- **Forum Management** (`/admin/forum`): create a forum (title, subject, question, time window) and tick the classrooms that will participate. Use **Edit** to change any forum or its classroom assignment, **Re-open** to extend an expired window, and **Activate** to make a forum the active one.
- **Classrooms** (`/admin/salones`): manage the classrooms (salons) that students belong to.
- **Students** (`/admin/students`): register students and assign them to a classroom; block/unblock accounts.
- **Responses** (`/admin/responses`): review all participations and the partner-reply summary.
- **Security Log** (`/admin/logs`): audit everything, from blocked actions to hacking attempts.

## Project structure

```
app/
  Controllers/   # Auth, Forum, Admin controllers
  Helpers/       # functions (time windows, flash messages, CSRF, etc.)
  Models/        # Database, User, Salon, Forum, Response, SecurityLog
  Views/         # student, admin and shared templates
config/
  config.php     # DB connection + business rules
database/
  schema.sql     # full schema + seed
public/
  css/           # app.css (forum lock, watermark, capture shield)
  js/            # app.js, forum.js, security.js
index.php        # front controller
.htaccess        # URL rewriting
```

## Security notes

- All actions are validated on the server: time window, role, duplicate submissions and classroom assignment.
- The screen-capture shield ("black screen") is the effective defense against screenshots; clipboard wiping is best-effort because browsers restrict what web pages can remove from the clipboard.
- Report endpoints and CSRF tokens protect against forged browser events.