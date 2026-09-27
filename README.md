# JobPortal — Internship & Job Portal

A secure, responsive Internship and Job Portal built with **HTML, Tailwind CSS (CDN), vanilla JavaScript, PHP, and MySQL**.

## Features

- **Authentication** — register / log in / log out with server-side validation, `password_hash()` hashing, duplicate-email checks, and secure PHP sessions (HttpOnly + SameSite cookies, session regeneration on login).
- **Roles** — `seeker` (job seeker) and `employer`. Each dashboard is protected and redirects unauthorized users.
- **Jobs** — employers post jobs via prepared statements; seekers browse, search, and filter jobs on the landing page.
- **Applications** — seekers upload a PDF resume (validated by MIME type and size) to apply; employers see applicants and update application status (`pending` → `reviewed` → `accepted` / `rejected`).
- **Role-based entry flow** — the public landing page asks visitors to continue as a job seeker or employer before showing the relevant authenticated workflow.
- **Job CRUD** — employers can create, edit, and delete their own postings; deleting a posting also removes its applications.
- **Responsive UI** — mobile-first layout with Tailwind CSS, micro-interactions, and accessible forms.

## Project structure

```
.
├── database/
│   └── schema.sql            # MySQL schema (creates the job_portal DB + 3 tables)
├── config/
│   ├── config.php            # App constants, session bootstrap, helpers
│   └── db.php                 # PDO connection (singleton)
├── uploads/                   # Uploaded PDF resumes (auto-created)
├── assets/
│   ├── styles.css             # Shared styles
│   ├── auth.js                # Session-aware navbar + shared fetch helpers
│   ├── login.js               # Login form handler
│   ├── register.js            # Register form handler
│   ├── jobs.js                # Landing page: fetch + render jobs, apply modal
│   └── employer.js            # Post-job form + status updates
├── index.html                 # Landing page (search/filter + job grid)
├── login.html                 # Login form
├── register.html              # Registration form
├── register.php               # Registration API (JSON)
├── login.php                  # Login API (JSON)
├── logout.php                 # Session destroy
├── session.php                # Current session info (JSON) for the navbar
├── fetch_jobs.php             # List jobs (JSON, with filters)
├── post_job.php               # Employer: create job (JSON)
├── apply_job.php              # Seeker: upload resume + apply (multipart)
├── update_application.php     # Employer: update application status (JSON)
├── seeker_dashboard.php       # Protected: seeker's applications
└── employer_dashboard.php     # Protected: employer's jobs + applicants
```

## Setup

### 1. Database

Create the database and tables:

```bash
mysql -u root -p < database/schema.sql
```

### 2. Configure the connection

Edit `config/config.php` and set the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` constants to match your MySQL server.

### 3. Run with PHP's built-in server

From the project root:

```bash
php -S localhost:8000
```

Then open <http://localhost:8000/index.html>.

### Windows with Laragon

If PHP and MySQL are installed through Laragon, start **MySQL** in Laragon and run these commands from PowerShell:

```powershell
cd D:\Portal\project
Get-Content .\database\schema.sql | & "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -h 127.0.0.1 -P 3306 -u root
& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" -S localhost:8000
```

Open <http://localhost:8000/index.html> and choose **Find a job** or **Hire talent**. Visitors must log in or sign up for the selected role. Seekers can browse live openings, upload a PDF resume, apply once per opening, and track status. Employers can post, edit, and delete their own jobs and review applicants.

### Authentication flow

1. The landing page is public and does not expose the job browser to signed-out visitors.
2. A visitor chooses **Job Seeker** or **Employer**, then logs in or registers.
3. PHP stores the authenticated user ID, role, name, and email in a secure session and redirects to the matching dashboard.
4. Server-side guards reject role mismatches and protect job creation, editing, deletion, resume upload, and application-status updates.
5. Only seekers can upload a PDF resume and create an application. Employers can only manage jobs they own.

## Security notes

- All SQL uses **PDO prepared statements** (no string interpolation of user input).
- Passwords are hashed with `password_hash()` and verified with `password_verify()`.
- Sessions use `HttpOnly` cookies with `SameSite=Lax`, and the session ID is regenerated on login to prevent fixation.
- Resume uploads are validated by real MIME type (`finfo`) and size, stored with safe unique names, and rejected if not PDF.
- Protected dashboards call `require_auth('role')` and redirect unauthorized users; API endpoints call `require_api_auth('role')` and return 401/403 JSON.
- Application status updates verify the application belongs to a job owned by the requesting employer before updating.
