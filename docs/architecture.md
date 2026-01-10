# Architecture Overview

## System Overview

Systém je postaven na modulární architektuře s jasným oddělením uživatelského rozhraní, aplikační logiky a datové vrstvy. Celý projekt je navržen tak, aby minimalizoval režii při nasazení a zajistil vysokou integritu dat prostřednictvím striktních databázových vazeb. 

## Application Layers

Architektura je rozdělena do tří hlavních vrstev, které zajišťují separaci odpovědnosti:

### 1. Public Access Layer (`/public/`)

Slouží jako jediný vstupní bod a orchestrační vrstva systému. 

* **Frontend & Assets**: Hostuje klientské skripty (JS), styly (CSS) a zajišťuje vizuální identitu (neumorfní design). 
* **Security Gateway**: Vynucuje autorizaci uživatelů před přístupem k datům a spravuje bezpečnost relací (GDPR kompatibilní autologout). 
* **Interface Controllers**: Soubory jako `smlouvy.php` agregují data a řídí životní cyklus entit (CRUD). 

### 2. Application Core Logic (`/app/`)

Backendová vrstva implementovaná dle vzoru **MVC (Model-View-Controller)**. 

* **Controllers**: Zpracovávají uživatelské požadavky, provádějí validaci a rozhodují o uložení dat. 
* **Models (DAO)**: Izolují SQL operace a zajišťují čistou manipulaci s databázovými tabulkami. 
* **Views**: Generují HTML komponenty s využitím Tailwind CSS pro zobrazení dat uživateli. 
* **Includes**: Poskytují sdílené služby jako DB připojení a globální šablony. 

### 3. Data Layer (`/CRM/`)

Fyzická struktura definovaná v `muj_cms.sql`, která slouží jako "zdroj pravdy" pro celý systém. Obsahuje 7 hlavních tabulek (klienti, smlouvy, provize, dokumenty atd.) s definovanou referenční integritou. 

---

## Data Flow

Tok dat v systému následuje standardní request-response cyklus:

**Request**: Uživatel interaguje s rozhraním v `/public/` (např. odeslání formuláře). 

**Controller**: Požadavek je zachycen v `/app/controllers/`, kde proběhne validace (např. unikátnost smlouvy). 

**Model**: Controller volá příslušný Model v `/app/models/`, který provede SQL operaci nad databází. 

**Database**: MySQL/MariaDB zpracuje dotaz, přičemž hlídá integritu (např. `ON DELETE CASCADE` pro smazání klienta i jeho smluv). 

**View**: Výsledek je předán do `/app/views/`, který vygeneruje HTML fragmenty nebo JSON data. 

**Response**: Frontend v `/public/` (často přes AJAX) aktualizuje UI bez nutnosti plného reloadu stránky. 

---

## Application Lifecycle

### Instalační fáze

Proces nasazení je automatizován skriptem `install.php`. 

* **Příprava**: Skript ověří požadavky serveru (PHP 8.0+, MySQLi) a vytvoří databázové schéma. 
* **Konfigurace**: Generuje se `db_connect.php` a registruje se první administrátor. 
* **Zabezpečení**: Po instalaci vznikne `installed.lock` a uživatel by měl spustit `remove_install.php` pro nevratné odstranění instalačních artefaktů, čímž se minimalizuje plocha útoku. 

### Běžný provoz

V ostrém provozu systém využívá mechanismy pro udržení konzistence, jako je verzování assetů přes `filemtime` (pro eliminaci cache problémů) a synchronizace klientského a serverového timeoutu relace. 

---

## Security Considerations

Bezpečnost je integrovaná do všech vrstev systému:

* **SQL Injection**: Povinné používání Prepared Statements u všech datových operací. 
* **Access Control**: Centrální autentizační funkce `require_login()` a `require_admin()` chrání každý modul v `/app/`. 
* **Data Integrity**: Využití cizích klíčů a pravidel `ON DELETE RESTRICT` chrání historii produktů a pojišťoven. 
* **GDPR**: Implementace bezpečnostních časovačů a šifrování hesel. 

---

## Extensibility & Maintenance

Při rozšiřování systému (např. o nový modul) je nutné dodržovat striktní separaci odpovědnosti: 

* **SQL operace** patří výhradně do `/app/models/`. 
* **Business logika** a zpracování POST dat do `/app/controllers/`. 
* **HTML formuláře a tabulky** do `/app/views/`. 
