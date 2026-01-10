# CRM System

Tento projekt představuje modulární CRM systém vyvinutý pro efektivní správu klientů, smluv a finančních provizí. Aplikace klade důraz na vysokou integritu dat, bezpečnost a přehledné uživatelské rozhraní založené na moderních webových technologiích.

## Features

* **Komplexní správa entit**: Evidence klientů, pojistných smluv, produktů a pojišťoven v provázaném systému.
* **Finanční přehled**: Sledování provizí, jejich stavů a historie plateb.
* **Správa dokumentů**: Možnost nahrávání a evidence příloh přímo k jednotlivým smlouvám.
* **Řízení přístupu (RBAC)**: Diferenciace oprávnění mezi administrátory a běžnými uživateli.
* **Automatizovaná instalace**: Intuitivní průvodce nastavením systému a databáze.

---

## Architecture

Systém využívá třívrstvou architekturu s důsledným oddělením veřejné vrstvy od aplikační logiky (MVC pattern).

* **Public Access Layer**: Jediný vstupní bod aplikace zajišťující směrování a bezpečnost.
* **Application Core**: Backendová logika v PHP rozdělena na modely (DAO), controllery a views.
* **Data Layer**: MySQL/MariaDB databáze s vynucenou referenční integritou

Podrobnosti naleznete v: **[docs/CZ/architecture.md]**

---

## Installation

Pro instalaci je vyžadováno prostředí s **PHP 8.0+** a databáze **MySQL/MariaDB**.

1. Nahrajte soubory na server.
2. Spusťte `install.php` v prohlížeči a postupujte dle instrukcí.
3. Po dokončení spusťte bezpečnostní skript `remove_install.php?confirm=yes`.

Podrobnosti naleznete v: **[docs/CZ/installation.md]**

---

## Project Structure

Adresářová struktura je navržena pro maximální bezpečnost a přehlednost kódu.

* `/app/`: Obsahuje neveřejnou logiku, DB dotazy a HTML šablony.
* `/public/`: Obsahuje spustitelné skripty, klientský JavaScript a CSS styly.
* `/docs/`: Technická dokumentace projektu.

Podrobnosti naleznete v: **[docs/CZ/project-structure.md]**

---

## Database

Datové schéma (soubor `muj_cms.sql`) tvoří 7 hlavních tabulek, které zajišťují konzistenci dat pomocí cizích klíčů s pravidly `CASCADE` a `RESTRICT`.

Podrobnosti naleznete v: **[docs/CZ/database.md]**

---

## Security

Zabezpečení je integrováno ve všech vrstvách systému:

* **Ochrana proti SQL Injection**: Výhradní používání Prepared Statements.
* **Správa relací**: Serverový timeout (35 min) synchronizovaný s klientským odpojením.
* **Hardening**: Mechanismus `installed.lock` a samočistící instalační skripty.

Podrobnosti naleznete v: **[docs/CZ/security.md]**

---

## Notes

* **Aktuální omezení**: Systém v současné verzi využívá hardcoded údaje k databázi v souboru `db_connect.php` a zatím nepodporuje environmentální proměnné (`.env`).
* **Závislosti**: UI využívá externí CDN pro Tailwind CSS a Font Awesome.