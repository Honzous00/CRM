# Project Structure

Tento dokument poskytuje přehled organizační struktury CRM systému a definuje role jednotlivých adresářů v rámci architektury aplikace.

---

## Root Directory

Kořenový adresář projektu slouží jako základní kontejner, který striktně odděluje veřejně přístupnou vrstvu od vnitřní logiky systému. 

* **/public/**: Veřejný vstupní bod aplikace (Frontend & API). 
* **/app/**: Jádro aplikace obsahující neveřejnou logiku (Backend & MVC). 
* **/docs/**: (Předpokládané umístění) Technická dokumentace projektu.

---

## app/ Directory

Adresář `/app/` představuje **Application Core Logic**. Je navržen podle principů **MVC (Model-View-Controller)** a slouží k izolaci kritické logiky od koncového uživatele. 

### Význam podsložek:

* **Controllers (`/app/controllers/`)**: Procesní jádro. Zpracovává vstupy z formulářů, provádí validaci a řídí business logiku (např. `smlouvy_controller.php`). 
* **Models (`/app/models/`)**: Datová vrstva (DAO). Obsahuje čistě SQL operace a abstrakci databáze (např. `dokumenty_model.php`). 
* **Views (`/app/views/`)**: Prezentační vrstva. PHP komponenty generující HTML, jako jsou tabulky a modální okna (např. `smlouvy_view.php`). 
* **Includes (`/app/includes/`)**: Infrastruktura. Zajišťuje připojení k DB (`db_connect.php`), autentizaci (`login.php`) a globální layouty (`header.php`). 

**Kde provádět úpravy:**

* **Business logika a validace**: V controllerech. 
* **SQL dotazy**: Výhradně v modelech. 
* **Vzhled formulářů a tabulek**: Ve views. 

---

## public/ Directory

Adresář `/public/` funguje jako **Public Access Layer**. Je to jediná složka přístupná přímo z prohlížeče a slouží jako orchestrační vrstva propojující UI s vnitřní logikou. 

### Hlavní role:

* **Vstupní body (Endpoints)**: Hostuje spustitelné skripty (např. `klienti.php`, `smlouvy.php`), které agregují data a spravují životní cyklus entit. 
* **Směrování a bezpečnost**: Vynucuje autorizaci uživatelů před přístupem k datům a zajišťuje ochranu relací (GDPR). 
* **AJAX & API**: Poskytuje specializované handlery pro dynamickou aktualizaci stránek bez reloadu. 

**Kde provádět úpravy:**

* **Přidávání nových stránek/modulů**: Vytvořením nového PHP skriptu v kořeni `/public/`. 
* **API požadavky**: Úpravou AJAX endpointů. 

---

## Assets & Uploads

Klientské prostředky jsou centralizovány pro zajištění plynulého uživatelského rozhraní a vizuální identity. 

### Statické soubory v `/public/`:

* **/js/**: Obsahuje klientské skripty, asynchronní komunikaci a bezpečnostní časovač `autologout.js`. 
* **/css/**: Definuje vizuální identitu (neumorfní design) a správu vrstvení prvků (Z-index). 

---

### Klíčové mechanismy:

* **Verzování**: Assety jsou v šablonách načítány s využitím `filemtime`, což eliminuje problémy s mezipamětí prohlížeče při aktualizacích. 
* **Bezpečnostní rezerva**: Klientský odpočet v JS je nastaven o 5 minut kratší než serverový timeout pro bezpečné ukončení relace. 