# Security Overview

This documentation summarizes the security mechanisms implemented in the CRM system based on an analysis of the source files. The system uses a combination of backend logic in PHP, database security, and client-side scripts to ensure data integrity and access control.

---

## Authentication & Access Control

Access control is built on central logic in the file `app/includes/login.php`, which functions as a "Gatekeeper".

* **Authentication Process**: Verification is performed against the database (table `users`) using modern `password_verify()` functions to check hashed passwords.
* **Brute-force Protection**: For unsuccessful login attempts, an artificial delay of `sleep(1)` is implemented.
* **Session Management (Sessions)**:
* After a successful login, variables `$_SESSION['user_id']` and `$_SESSION['is_admin']` are set.
* Server-side session expiration is set to **35 minutes**.
* On the client side, the `autologout.js` script monitors inactivity; it is set 5 minutes shorter than the server timeout to ensure smooth synchronization.


* **Authorization Levels (RBAC)**:
* The system distinguishes between a regular user and an administrator using the `is_admin` flag.
* The functions `require_login()` and `require_admin()` enforce redirection of unauthorized requests.
* UI elements (e.g., "User Management") are dynamically hidden in the navigation based on these permissions.


* **Database Security**: All queries utilize **Prepared Statements** as protection against SQL injection.

---

## Public Endpoints

Publicly accessible files are designed not to leak sensitive data without prior authentication.

* **Login Interface (`/public/login.php`)**: A standalone interface that does not use the standard system header to remain isolated from internal navigation.
* **Input Filtration**: Accepts `username` and `password` inputs via the POST method.
* **Error Messages**: The system dynamically displays warnings for invalid credentials directly in the interface.

---

## Installation-related Security

The system includes specific mechanisms for securing the installation process and subsequent environment cleanup.

* **Lock file mechanism**: The file `db_connect.php` checks for the existence of the `installed.lock` file. If this file is missing, the system automatically redirects the user to the installer.
* **Cleanup script (`remove_install.php`)**: Used to remove installation artifacts (e.g., `install.php`, `database.sql`) after setup is complete.
* **Two-phase Confirmation**: Requires the `?confirm=yes` parameter in the URL to prevent accidental execution.
* **Self-destruct**: The script deletes itself after completion, thereby minimizing the attack surface.

---

## Known Limitations

Based on the analysis, the following security limits of the current implementation were identified:

* **Hardcoded credentials**: Database login credentials are stored as constants directly in the `db_connect.php` file, which complicates secure management across environments.
* **Absence of .env**: The system currently does not utilize environment variables for sensitive data.
* **Relative paths**: Navigation elements and includes rely on relative addressing, which requires strict adherence to the directory structure.

---

## Recommendations for Production

For production deployment, the following steps are recommended:

1. **Run Cleanup script**: Immediately after installation, run `remove_install.php?confirm=yes`.
2. **Secure output**: Ensure that database errors are logged only to the `error_log` and are not printed directly to the user (prevention of infrastructure information leakage).
3. **Permissions**: The web server must have read permissions for `installed.lock`, but after installation, write permissions to system folders should be restricted unless necessary.