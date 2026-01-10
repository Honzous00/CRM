# Database Structure

## Overview

The system's database (file `muj_cms.sql`) forms the cornerstone of the entire CRM. It defines the physical storage structure for all data. It serves not only as a template for system initialization during installation via `install.php`, but primarily as the "source of truth" for all application modules.

**Key architectural features:**

* **Consistency:** Ensures logical connections between clients, contracts, and finances.
* **Referential Integrity:** The use of foreign keys with deletion rules (Cascade/Restrict) keeps the database free of orphaned records.
* **Localization:** The used `utf8mb4` encoding guarantees full support for Czech diacritics.
* **Performance:** All tables use primary keys and indexes on foreign keys for fast searching.

## Core Tables

The system consists of 7 main tables, which can be logically divided into client data, business data, and lookup tables.

| Table | Purpose | Key Information |
| --- | --- | --- |
| `klienti` | Records of subjects | Name, email, phone, RČ/IČO, and address. |
| `smlouvy` | Core of the system | Connects a client with a product and an insurance company; contains validity metadata. |
| `provize` | Financial records | Amounts tied to contracts, cancellation flags, and payout stages. |
| `dokumenty` | Attachment management | Records of files uploaded to individual contracts. |
| `produkty` | Service catalog | List of offered insurance types (e.g., Life Insurance). |
| `pojistovny` | Partner catalog | List of cooperating insurance companies. |
| `users` | Access management | Login credentials, roles (e.g., admin), and user status. |

## Relationships

Relationships between tables are defined using **FOREIGN KEY** (foreign keys), which govern data deletion logic:

* **Identification Link (CASCADE):** If a record in the `klienti` table is deleted, its related `smlouvy` and `provize` are automatically removed. This ensures database cleanliness without redundant leftovers.
* **Protective Link (RESTRICT):** The system prevents deleting a record in the `produkty` or `pojistovny` lookup tables if at least one existing contract refers to them. This protects the integrity of historical data.
* **Connection Logic:** The `smlouvy` table functions as a central hub, which connects the client, product, and insurance company via JOIN operations for search requirements (e.g., in `search_smlouvy.php`).

---

## Notes for Development

The following technical aspects must be considered during further development and system maintenance:

* **ID Automation:** All tables use `AUTO_INCREMENT`, which eliminates collisions during concurrent record insertion by multiple users.
* **Audit Trails:** Tables utilize the `datetime` data type with the `DEFAULT CURRENT_TIMESTAMP` setting, allowing for tracking the exact creation time of each record.
* **Financial Precision:** For amounts in the `provize` table, the `decimal` type is used, which is more suitable for financial operations than floating-point numbers.
* **Extensibility:** Thanks to the modular structure of the lookup tables (`produkty`, `pojistovny`), the system can be easily expanded with new service types without requiring intervention in the code structure.