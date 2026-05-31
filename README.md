# 🏢 EMS Pro — Employee Management System

A beginner-friendly PHP & MySQL web application for managing employees, managers, and activity logs. Built with Bootstrap 5 and a clean role-based dashboard for Admin, Manager, and Employee users.

---

## ✨ Features

- 🔐 **Secure Login** with bcrypt password hashing and session management
- 👤 **3 Role Types** — Admin, Manager, and Employee with separate dashboards
- 🛡️ **Role-Based Access Control** — each role only sees what they're allowed to
- 👥 **Admin Panel** — manage all employees and managers, view activity logs
- 📋 **Manager Panel** — manage only their own team members
- 🙋 **Employee Panel** — view their own profile and job info
- 📝 **Activity Logs** — every login, add, edit, and delete is recorded
- 🔍 **Search & Filter** — search employees by name, position, or department
- 📄 **Pagination** — clean page-by-page browsing for large lists
- ✏️ **Profile Editor** — all users can update their name, email, phone, and password
- 📱 **Responsive Design** — works on desktop, tablet, and mobile

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP (beginner-friendly, no frameworks) |
| Database | MySQL |
| Frontend | Bootstrap 5, Bootstrap Icons |
| Font | Google Fonts — Sora |
| Local Server | XAMPP (Apache + MySQL) |

---

## 📁 Project Structure

```
emp_system/
│
├── config/
│   ├── db.php          # Database connection (PDO)
│   └── app.php         # App settings, helper functions
│
├── includes/
│   ├── auth.php        # Session boot + login check
│   ├── header.php      # HTML <head> block
│   ├── sidebar.php     # Role-based sidebar navigation
│   ├── topbar.php      # Top navigation bar
│   └── footer.php      # Closing scripts and tags
│
├── admin/
│   ├── dashboard.php       # Admin home with stats
│   ├── employees.php       # List / search / delete employees
│   ├── employee_form.php   # Add / edit employee
│   ├── managers.php        # List / delete managers
│   ├── manager_form.php    # Add / edit manager
│   └── activity_logs.php   # Full audit log viewer
│
├── manager/
│   ├── dashboard.php       # Manager home with team stats
│   ├── employees.php       # Manager's team list
│   └── employee_form.php   # Add / edit team member
│
├── employee/
│   └── dashboard.php       # Employee's own profile view
│
├── assets/
│   ├── css/style.css   # Custom styles
│   └── js/app.js       # Custom JavaScript
│
├── index.php           # Entry point (redirects to login or dashboard)
├── login.php           # Login page
├── logout.php          # Destroys session and redirects
├── profile.php         # Shared profile editor (all roles)
└── database.sql        # Full database schema + demo data
```

---

## ⚙️ Installation & Setup

### Requirements
- XAMPP (or any Apache + PHP + MySQL setup)
- PHP 7.4 or higher
- MySQL 5.7 or higher

---

### Step 1 — Copy the project files

Place the `emp_system` folder inside your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\emp_system\
```

---

### Step 2 — Start XAMPP

Open the XAMPP Control Panel and start:
- ✅ **Apache**
- ✅ **MySQL**

---

### Step 3 — Import the database

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar and create a database named:
   ```
   emp_management
   ```
3. Click on the new database, then go to the **Import** tab
4. Click **Choose File**, select `database.sql` from the project folder
5. Click **Go** — all tables and demo data will be created automatically

---

### Step 4 — Configure the project

Open `config/db.php` and make sure these match your setup:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'emp_management');
define('DB_USER', 'root');
define('DB_PASS', '');   // leave empty for default XAMPP
```

Open `config/app.php` and confirm the base URL:

```php
define('BASE_URL', 'http://localhost/emp_system');
```

---

### Step 5 — Open the app

Go to:
```
http://localhost/emp_system
```

You will be redirected to the login page automatically.

---

## 🔑 Demo Login Accounts

All demo accounts use the same password: **`password`**

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@ems.local` | `password` |
| Manager | `alice@ems.local` | `password` |
| Employee | `carol@ems.local` | `password` |

> These credentials are also shown at the bottom of the login page.

---

## 🔒 Password Not Working?

If you see **"Invalid credentials or account is inactive"**, the password hash in the database may not match your PHP version. Fix it by running this in phpMyAdmin's SQL tab:

**Step 1 — Create a file** `genhash.php` inside `htdocs`:

```php
<?php
$hash = password_hash('password', PASSWORD_BCRYPT);
echo $hash;
?>
```

**Step 2 —** Open `http://localhost/genhash.php` and copy the hash.

**Step 3 —** Run this in phpMyAdmin (replace with your copied hash):

```sql
UPDATE users
SET password = 'PASTE_YOUR_HASH_HERE'
WHERE email IN ('admin@ems.local', 'alice@ems.local', 'carol@ems.local');
```

**Step 4 —** Delete `genhash.php` and log in normally.

---

## 👥 User Roles Explained

### 🔴 Admin
- Full access to everything
- Can add, edit, and delete both employees and managers
- Can view all activity logs across the system

### 🟡 Manager
- Can only see and manage their own team
- Can add, edit, and delete employees assigned to them
- Cannot see other managers' teams or system logs

### 🟢 Employee
- Read-only access to their own profile and job info
- Can update their name, email, phone, and password
- Cannot see other employees or any admin data

---

## 🗃️ Database Tables

| Table | Description |
|-------|-------------|
| `users` | Stores all user accounts (admin, manager, employee) |
| `employees` | Stores job details — position, salary, department, manager |
| `activity_logs` | Records every action taken in the system |

---

## 🚀 Future Improvements

- [ ] Add forgot password / email reset
- [ ] Add profile photo upload
- [ ] Add attendance tracking
- [ ] Add leave request system
- [ ] Export employee data to CSV or PDF
- [ ] Add dark mode toggle

---

## 📄 License

This project is open source and free to use for learning and personal projects.

---

## 🙌 Acknowledgements

- [Bootstrap 5](https://getbootstrap.com/) — UI framework
- [Bootstrap Icons](https://icons.getbootstrap.com/) — Icon library
- [Google Fonts — Sora](https://fonts.google.com/specimen/Sora) — Typography
- [XAMPP](https://www.apachefriends.org/) — Local development server
