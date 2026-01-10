# Installation Guide

Tato dokumentace popisuje proces nasazení CRM systému, od přípravy prostředí až po finální zabezpečení aplikace.

---

## Requirements

Před spuštěním instalace se ujistěte, že váš server splňuje následující technické parametry:

* **PHP**: Verze 8.0 nebo vyšší. 
* **Rozšíření PHP**: Aktivní moduly **MySQLi** (pro objektový přístup k DB) a podpora **JSON**. 
* **Databáze**: MySQL (podporující cizí klíče a `ON DELETE CASCADE`). 
* **Souborová práva**: PHP proces musí mít práva k zápisu v kořenovém adresáři `/CRM/` a v adresáři `/app/includes/`. 
* **Konektivita**: Přístup k externím CDN pro načtení Tailwind CSS a Font Awesome. 

---

## Installation Steps

Instalační proces je navržen jako automatizovaný "Step-by-Step" průvodce, který eliminuje chyby při ručním importu dat. 

1. **Nahrání souborů**: Nahrajte kompletní obsah projektu do kořenového adresáře vašeho webového serveru. 
2. **Spuštění instalátoru**: V prohlížeči otevřete soubor `install.php`. 
3. **Prověření prostředí**: Skript automaticky otestuje verzi PHP a potřebná rozšíření. 
4. **Konfigurace databáze**: Zadejte přístupové údaje (host, jméno databáze, uživatel, heslo). Skript ověří spojení a vytvoří potřebnou strukturu tabulek (klienti, smlouvy, provize, uživatelé atd.). 
5. **Vytvoření administrátora**: Registrujte prvního uživatele. Skript se postará o bezpečné zašifrování hesla a validaci jeho síly pomocí JavaScriptu. 
6. **Dokončení**: Systém vygeneruje konfigurační soubor `app/includes/db_connect.php` a vytvoří bezpečnostní zámek `installed.lock`. 

---

## Post-installation Security

Po dokončení instalace je kriticky důležité provést kroky pro zpevnění systému (System Hardening). 

### Role souboru `remove_install.php`

Tento skript slouží jako "čistič", který minimalizuje bezpečnostní riziko (tzv. attack surface). Ponechání instalačních souborů na serveru by mohlo útočníkovi prozradit strukturu databáze nebo umožnit neautorizovaný reset systému. 

* **Funkce**: Skript odstraní soubory `install.php`, SQL schémata (`database.sql`) a díky mechanizmu "self-destruct" smaže i sám sebe. 
* **Spuštění**: Vyžaduje explicitní potvrzení uživatelem v URL přidáním parametru `?confirm=yes`. 

> **Důležité**: Jakmile je tento proces spuštěn, je nevratný. Pro opětovnou instalaci byste museli soubory znovu nahrát z repozitáře. 

---

## Uninstallation / Reset

Systém obsahuje pojistky proti nechtěnému přepsání dat:

* **Zámek instalace**: Pokud v kořenovém adresáři existuje soubor `installed.lock`, instalátor se nespustí. 
* **Reset**: Pokud potřebujete provést novou, čistou instalaci, musíte ručně smazat soubor `installed.lock` a znovu nahrát `install.php` (pokud byl předtím odstraněn). 