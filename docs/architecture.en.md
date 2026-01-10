# Architecture Overview

## System Overview

The system is built on a modular architecture with a clear separation of the user interface, application logic, and data layer. The entire project is designed to minimize deployment overhead and ensure high data integrity through strict database constraints.

## Application Layers

The architecture is divided into three main layers that ensure separation of concerns:

### 1. Public Access Layer (`/public/`)

Serves as the single entry point and orchestration layer of the system.

* **Frontend & Assets**: Hosts client scripts (JS), styles (CSS), and ensures visual identity (neumorphic design).
* **Security Gateway**: Enforces user authorization before data access and manages session security (GDPR-compliant autologout).
* **Interface Controllers**: Files like `smlouvy.php` aggregate data and manage the lifecycle of entities (CRUD).

### 2. Application Core Logic (`/app/`)

Backend layer implemented according to the **MVC (Model-View-Controller)** pattern.

* **Controllers**: Process user requests, perform validation, and decide on data storage.
* **Models (DAO)**: Isolate SQL operations and ensure clean manipulation of database tables.
* **Views**: Generate HTML components using Tailwind CSS to display data to the user.
* **Includes**: Provide shared services such as DB connection and global templates.

### 3. Data Layer (`/CRM/`)

Physical structure defined in `muj_cms.sql`, which serves as the "source of truth" for the entire system. It contains 7 main tables (clients, contracts, commissions, documents, etc.) with defined referential integrity.

---

## Data Flow

The data flow in the system follows a standard request-response cycle:

**Request**: The user interacts with the interface in `/public/` (e.g., submitting a form).

**Controller**: The request is intercepted in `/app/controllers/`, where validation takes place (e.g., contract uniqueness).

**Model**: The controller calls the corresponding Model in `/app/models/`, which performs the SQL operation on the database.

**Database**: MySQL/MariaDB processes the query while maintaining integrity (e.g., `ON DELETE CASCADE` for deleting a client and their contracts).

**View**: The result is passed to `/app/views/`, which generates HTML fragments or JSON data.

**Response**: The frontend in `/public/` (often via AJAX) updates the UI without requiring a full page reload.

---

## Application Lifecycle

### Installation Phase

The deployment process is automated by the `install.php` script.

* **Preparation**: The script verifies server requirements (PHP 8.0+, MySQLi) and creates the database schema.
* **Configuration**: `db_connect.php` is generated and the first administrator is registered.
* **Security**: After installation, `installed.lock` is created, and the user should run `remove_install.php` to irreversibly remove installation artifacts, thereby minimizing the attack surface.

### Standard Operation

In production, the system utilizes mechanisms to maintain consistency, such as asset versioning via `filemtime` (to eliminate cache issues) and synchronization of client and server session timeouts.

---

## Security Considerations

Security is integrated into all layers of the system:

* **SQL Injection**: Mandatory use of Prepared Statements for all data operations.
* **Access Control**: Central authentication functions `require_login()` and `require_admin()` protect every module in `/app/`.
* **Data Integrity**: Use of foreign keys and `ON DELETE RESTRICT` rules protects the history of products and insurance companies.
* **GDPR**: Implementation of security timers and password encryption.

---

## Extensibility & Maintenance

When extending the system (e.g., adding a new module), strict separation of concerns must be followed:

* **SQL operations** belong exclusively in `/app/models/`.
* **Business logic** and POST data processing in `/app/controllers/`.
* **HTML formuláře a tabulky** into `/app/views/`.