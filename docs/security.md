# Security Overview

Tato dokumentace shrnuje bezpečnostní mechanismy implementované v CRM systému na základě analýzy zdrojových souborů. Systém využívá kombinaci backendové logiky v PHP, databázového zabezpečení a klientských skriptů pro zajištění integrity dat a řízení přístupu.

---

## Authentication & Access Control

Řízení přístupu je postaveno na centrální logice v souboru `app/includes/login.php`, který funguje jako "strážce brány" (Gatekeeper).

* **Proces autentizace**: Ověřování probíhá proti databázi (tabulka `users`) s využitím moderních funkcí `password_verify()` pro kontrolu hashovaných hesel.
* **Ochrana proti Brute-force**: Při neúspěšném pokusu o přihlášení je implementováno umělé zpoždění `sleep(1)`.
* **Správa relací (Sessions)**:
* Po úspěšném přihlášení jsou nastaveny proměnné `$_SESSION['user_id']` a `$_SESSION['is_admin']`.
* Expirace relace na straně serveru je nastavena na **35 minut**.
* Na straně klienta sleduje neaktivitu skript `autologout.js`, který je o 5 minut kratší než serverový timeout pro zajištění hladké synchronizace.


* **Autorizační úrovně (RBAC)**:
* Systém rozlišuje mezi běžným uživatelem a administrátorem pomocí příznaku `is_admin`.
* Funkce `require_login()` a `require_admin()` vynucují přesměrování neautorizovaných požadavků.
* UI prvky (např. "Správa uživatelů") jsou v navigaci dynamicky skrývány na základě těchto oprávnění.


* **Bezpečnost databáze**: Veškeré dotazy využívají **Prepared Statements** jako ochranu proti SQL injection.

---

## Public Endpoints

Veřejně přístupné soubory jsou navrženy tak, aby nepropouštěly citlivá data bez předchozí autentizace.

* **Login Interface (`/public/login.php`)**: Samostatné rozhraní, které nepoužívá standardní hlavičku systému, aby bylo izolováno od vnitřní navigace.
* **Vstupní filtrace**: Přijímá vstupy `username` a `password` přes metodu POST.
* **Chybová hlášení**: Systém dynamicky zobrazuje varování při neplatných údajích přímo v rozhraní.

---

## Installation-related Security

Systém obsahuje specifické mechanismy pro zabezpečení procesu instalace a následné vyčištění prostředí.

* **Lock file mechanismus**: Soubor `db_connect.php` kontroluje existenci souboru `installed.lock`. Pokud tento soubor chybí, systém automaticky přesměruje uživatele na instalátor.
* **Cleanup skript (`remove_install.php`)**: Slouží k odstranění instalačních artefaktů (např. `install.php`, `database.sql`) po dokončení nastavení.
* **Dvoufázové potvrzení**: Vyžaduje parametr v URL `?confirm=yes` pro zamezení nechtěného spuštění.
* **Self-destruct**: Skript po dokončení smaže i sám sebe, čímž minimalizuje plochu útoku ("attack surface").

---

## Known Limitations

Na základě analýzy byly identifikovány následující bezpečnostní limity současné implementace:

* **Hardcoded údaje**: Přihlašovací údaje k databázi jsou uloženy jako konstanty přímo v souboru `db_connect.php`, což komplikuje bezpečnou správu napříč prostředími.
* **Absence .env**: Systém aktuálně nevyužívá environmentální proměnné pro citlivá data.
* **Relativní cesty**: Navigační prvky a inkludy spoléhají na relativní adresaci, což vyžaduje striktní dodržování adresářové struktury.

---

## Recommendations for Production

Pro nasazení do ostrého provozu se doporučují následující kroky:

1. **Spuštění Cleanup skriptu**: Okamžitě po instalaci spustit `remove_install.php?confirm=yes`.
2. **Zabezpečení výstupu**: Zajistit, aby chyby databáze byly logovány pouze do `error_log` a nebyly vypisovány přímo uživateli (prevence úniku informací o infrastruktuře).
3. **Oprávnění**: Webový server musí mít práva ke čtení `installed.lock`, ale po instalaci by měla být omezena práva k zápisu do systémových složek, pokud to není nezbytné.