# Installation Guide

This documentation describes the deployment process of the CRM system, from environment preparation to final application security.

---

## Requirements

Before starting the installation, ensure that your server meets the following technical parameters:

* **PHP**: Version 8.0 or higher.
* **PHP Extensions**: Active **MySQLi** modules (for object-oriented DB access) and **JSON** support.
* **Database**: MySQL (supporting foreign keys and `ON DELETE CASCADE`).
* **File Permissions**: The PHP process must have write permissions in the root directory `/CRM/` and in the directory `/app/includes/`.
* **Connectivity**: Access to external CDNs for loading Tailwind CSS and Font Awesome.

---

## Installation Steps

The installation process is designed as an automated "Step-by-Step" guide that eliminates errors during manual data import.

1. **File Upload**: Upload the complete project content to the root directory of your web server.
2. **Running the Installer**: Open the `install.php` file in your browser.
3. **Environment Verification**: The script automatically tests the PHP version and required extensions.
4. **Database Configuration**: Enter the access credentials (host, database name, user, password). The script will verify the connection and create the necessary table structure (clients, contracts, commissions, users, etc.).
5. **Creating an Administrator**: Register the first user. The script handles secure password encryption and validation of its strength using JavaScript.
6. **Completion**: The system generates the configuration file `app/includes/db_connect.php` and creates the security lock `installed.lock`.

---

## Post-installation Security

After completing the installation, it is critically important to perform steps for system hardening.

### Role of the `remove_install.php` file

This script serves as a "cleaner" that minimizes security risk (the so-called attack surface). Leaving installation files on the server could reveal the database structure to an attacker or allow an unauthorized system reset.

* **Function**: The script removes the `install.php` files, SQL schemas (`database.sql`), and, thanks to a "self-destruct" mechanism, deletes itself as well.
* **Execution**: Requires explicit user confirmation in the URL by adding the `?confirm=yes` parameter.

> **Important**: Once this process is started, it is irreversible. To reinstall, you would have to upload the files from the repository again.

---

## Uninstallation / Reset

The system contains safeguards against accidental data overwriting:

* **Installation Lock**: If the `installed.lock` file exists in the root directory, the installer will not run.
* **Reset**: If you need to perform a new, clean installation, you must manually delete the `installed.lock` file and re-upload `install.php` (if it was previously removed).