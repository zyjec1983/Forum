# Academic Forum

A secure academic forum application (PHP + MySQL) built for the ECOMUNDO institution.
Students, teachers and administrators manage academic forums with a participation time
window, classroom assignment, and a full security audit log.

## Features

- **Four roles** – `student` (participates in forums), `guest` (invited parent/auditor, read-only view), `teacher` (self-registers and manages their own classrooms, forums and students), `admin` (super-administrator with full control and deletion rights).
- **Student data capture & authentication** – registration accepts only the configured institutional domains (e.g. `@ecomundo.edu.ec`); the list is editable in Configuration; accounts are locked after repeated failed sign-ins.
- **Teacher accounts** – self-registration with its own form (Soy estudiante / Soy docente); teachers own the classrooms they create and only see their students, forums and audit entries.
- **Forum management** – create, edit, re-open, activate and delete forums; each forum defines a time window (`open_at` / `close_at`).
- **Classroom assignment** – each forum is assigned to one or more classrooms via checkboxes. Students only see and access the forums of their own classroom.
- **Student experience** – a "My forums" sidebar lists every forum assigned to the student's classroom, with its status (Open / Closed / Scheduled) and time range. Only the active forum accepts participation; other assigned forums are read-only.
- **Participation rules** – one response to the teacher, unlimited replies to partners, and one final conclusion, validated server-side.
- **Anti-cheat security** – copy, cut, paste, text selection, drag and developer-tools are blocked on forum pages; every attempt is logged with user, date/time and IP.
- **Screen-capture shield** – a synchronous full-screen shield covers the page on any capture attempt (PrtSc, snip tool, window/tab switch), the screenshot shows black, the clipboard is wiped (best-effort), and the student's session is force-closed.
- **Security audit** – `security_logs` records logins, lockouts, time-window violations, hacking attempts and all blocked actions; browsable from the panel. The super-administrator can delete individual entries or clear the whole log.
- **Configuration (domains)** – super-admin and teachers can toggle "accept any domain" and edit the list of accepted email domains.
- **Guest accounts (read-only)** – teachers create invited accounts (`guest` role) with a fictitious email + password, bound to one of their classrooms, to hand out to parents or auditors. Guests sign in and see the same student views but with no interaction: no responses, no conclusion, no admin panel, and no anti-cheat shield.

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

## How to use (Admin and Teacher)

The panel at `/admin` is shared by the administrator and the teachers. A teacher only sees
their own classrooms, students, forums and audit entries. The **Teachers** section and all
**delete** actions are exclusive to the administrator.

- **Forum Management** (`/admin/forum`): create a forum (title, subject, question, time window) and tick the classrooms that will participate. Use **Edit** to change any forum or its classroom assignment, **Re-open** to extend an expired window, and **Activate** to make a forum the active one (only one active forum at a time). The admin can also **Delete** a forum.
- **Classrooms** (`/admin/salones`): manage the classrooms (salons) that students belong to. Teachers create their own classrooms; the admin can assign an owner.
- **Students** (`/admin/students`): register students and assign them to a classroom; block/unblock accounts. The admin can delete students permanently.
- **Guest Accounts** (`/admin/guests`): create a guest account with any email + password and select the classroom it will be able to view. Teachers manage the guests of their own classrooms (lock/unlock/delete); the admin manages all guests.
- **Teachers** (`/admin/teachers`, admin only): list teacher accounts and delete them along with their classrooms, forums and data.
- **Configuration** (`/admin/settings`): toggle "accept any domain" and edit the list of accepted domains for student and teacher registration.
- **Responses** (`/admin/responses`): review all participations and the partner-reply summary; the admin can delete individual responses.
- **Security Log** (`/admin/logs`): audit everything, from blocked actions to hacking attempts; the admin can delete entries or clear the whole log.

## Teacher registration

The sign-in page offers both **Soy estudiante / Create account** and **Soy docente / Teacher account**.
A teacher account is created with the same accepted-domain rule and signs in directly to the
panel, where it can create classrooms and forums for its students.

## Guest accounts (parents / auditors)

From **Guest Accounts** (`/admin/guests`) a teacher creates an invited account with a fictitious
email and a password, bound to one of the classrooms they own. The credentials are shared with the
parent or auditor, who signs in on the normal page and is redirected to the student forum view of
that classroom **in read-only mode**:

- They see the classroom's forums (same "My forums" sidebar) and any forum contents.
- There is no response form, no final conclusion box and no admin panel; server-side rules reject any participation attempt by a guest.
- The anti-cheat shield is **not** applied to guests, so they can legally view or capture the screen.
- Teachers can block/unblock and delete their own guest accounts; the administrator manages all of them.

## Project structure

```
app/
  Controllers/   # Auth, Forum, Admin controllers
  Helpers/       # functions (time windows, flash messages, CSRF, roles, domains)
  Models/        # Database, Settings, User, Salon, Forum, Response, SecurityLog
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