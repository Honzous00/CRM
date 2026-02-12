

<h1 align="center">
  <br>
    <a href="https://github.com/Honzous00/CRM">
      <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/Honzous00/CRM/refs/heads/main/public/images/logo/White.svg">
        <source media="(prefers-color-scheme: light)" srcset="https://raw.githubusercontent.com/Honzous00/CRM/refs/heads/main/public/images/logo/Color.svg">
        <img src="https://raw.githubusercontent.com/Honzous00/CRM/refs/heads/main/public/images/logo/Color.svg" alt="CRM Logo" width="400">
      </picture>
    </a>
    <br>
</h1>

<p align="center">
  <a href="README.en.md">🇬🇧 English</a> •
  <a href="README.md">🇨🇿 Čeština</a> •
</p>  

<p align="center">
  <a href="LICENSE">
    <img src="https://img.shields.io/badge/license-GNU%20GPLv3-blue?style=flat" alt="License">
  </a>
  <a href="https://github.com/Honzous00/CRM/releases">
    <img src="https://img.shields.io/badge/release-v1.9.2-8a2be2?style=flat" alt="Version">
  </a>
  <img src="https://img.shields.io/badge/PHP-%3E%3D8.1-777bb4?style=flat" alt="PHP Version">
</p>

---

Tento projekt představuje modulární CRM systém vyvinutý pro efektivní správu klientů, smluv a finančních provizí. Aplikace klade důraz na vysokou integritu dat, bezpečnost a přehledné uživatelské rozhraní založené na moderních webových technologiích.

## 📸 Preview

<p align="center">
  <img src="docs/images/dashboard.png" alt="Dashboard CRM" width="100%">
</p>

---

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

Podrobnosti naleznete v: **[Dokumentace architektury](docs/architecture.md)**

---

## Installation

Pro instalaci je vyžadováno prostředí s **PHP 8.0+** a databáze **MySQL/MariaDB**.

1. Nahrajte soubory na server.
2. Spusťte `install.php` v prohlížeči a postupujte dle instrukcí.
3. Po dokončení spusťte bezpečnostní skript `remove_install.php`.

Podrobnosti naleznete v: **[Instalační přiručce](docs/installation.md)**

---

## Project Structure

Adresářová struktura je navržena pro maximální bezpečnost a přehlednost kódu.

* `/app/`: Obsahuje neveřejnou logiku, DB dotazy a HTML šablony.
* `/public/`: Obsahuje spustitelné skripty, klientský JavaScript a CSS styly.
* `/docs/`: Technická dokumentace projektu.

Podrobnosti naleznete v: **[Struktuře projektu](docs/project-structure.md)**

---

## Database

Datové schéma (soubor `muj_cms.sql`) tvoří 7 hlavních tabulek, které zajišťují konzistenci dat pomocí cizích klíčů s pravidly `CASCADE` a `RESTRICT`.

Podrobnosti naleznete v: **[Příručce databáze](docs/database.md)**

---

## Security

Zabezpečení je integrováno ve všech vrstvách systému:

* **Ochrana proti SQL Injection**: Výhradní používání Prepared Statements.
* **Správa relací**: Serverový timeout (35 min) synchronizovaný s klientským odpojením.
* **Hardening**: Mechanismus `installed.lock` a samočistící instalační skripty.

Podrobnosti naleznete v: **[Přiručce bezepčnosti](docs/security.md)**

---

## Notes

* **Aktuální omezení**: Systém v současné verzi využívá hardcoded údaje k databázi v souboru `db_connect.php` a zatím nepodporuje environmentální proměnné (`.env`).
* **Závislosti**: UI využívá externí CDN pro Tailwind CSS a Font Awesome.
