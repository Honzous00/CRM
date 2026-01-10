# Project Structure

This document provides an overview of the organizational structure of the CRM system and defines the roles of individual directories within the application architecture.

---

## Root Directory

The project's root directory serves as the basic container that strictly separates the publicly accessible layer from the internal system logic.

* **/public/**: Public entry point of the application (Frontend & API).
* **/app/**: Application core containing non-public logic (Backend & MVC).
* **/docs/**: (Intended location) Technical project documentation.

---

## app/ Directory

The `/app/` directory represents the **Application Core Logic**. It is designed according to **MVC (Model-View-Controller)** principles and serves to isolate critical logic from the end user.

### Significance of Subfolders:

* **Controllers (`/app/controllers/`)**: Process core. Processes form inputs, performs validation, and manages business logic (e.g., `smlouvy_controller.php`).
* **Models (`/app/models/`)**: Data layer (DAO). Contains purely SQL operations and database abstraction (e.g., `dokumenty_model.php`).
* **Views (`/app/views/`)**: Presentation layer. PHP components generating HTML, such as tables and modal windows (e.g., `smlouvy_view.php`).
* **Includes (`/app/includes/`)**: Infrastructure. Ensures DB connection (`db_connect.php`), authentication (`login.php`), and global layouts (`header.php`).

**Where to make changes:**

* **Business logic and validation**: In controllers.
* **SQL queries**: Exclusively in models.
* **Form and table appearance**: In views.

---

## public/ Directory

The `/public/` directory functions as the **Public Access Layer**. It is the only folder directly accessible from a browser and serves as an orchestration layer connecting the UI with internal logic.

### Main Roles:

* **Entry Points (Endpoints)**: Hosts executable scripts (e.g., `klienti.php`, `smlouvy.php`) that aggregate data and manage the lifecycle of entities.
* **Routing and Security**: Enforces user authorization before accessing data and ensures session protection (GDPR).
* **AJAX & API**: Provides specialized handlers for dynamic page updates without reloads.

**Where to make changes:**

* **Adding new pages/modules**: By creating a new PHP script in the root of `/public/`.
* **API requests**: By modifying AJAX endpoints.

---

## Assets & Uploads

Client-side assets are centralized to ensure a smooth user interface and visual identity.

### Static files in `/public/`:

* **/js/**: Contains client scripts, asynchronous communication, and the `autologout.js` security timer.
* **/css/**: Defines the visual identity (neumorphic design) and manages element layering (Z-index).

---

### Key Mechanisms:

* **Versioning**: Assets are loaded in templates using `filemtime`, which eliminates browser cache issues during updates.
* **Security Buffer**: The client-side countdown in JS is set 5 minutes shorter than the server timeout for secure session termination.