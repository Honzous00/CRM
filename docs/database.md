# Database Structure

## Overview

Databáze systému (soubor `muj_cms.sql`) tvoří základní kámen celého CRM. Definuje fyzickou strukturu úložiště pro všechna data. Slouží nejen jako šablona pro inicializaci systému při instalaci skrze `install.php`, ale především jako "zdroj pravdy" pro všechny aplikační moduly.

**Klíčové vlastnosti architektury:**

* **Konzistence:** Zajišťuje logické propojení mezi klienty, smlouvami a financemi.
* **Referenční integrita:** Využití cizích klíčů s pravidly pro mazání (Cascade/Restrict) udržuje databázi bez sirotčích záznamů.
* **Lokalizace:** Použité kódování `utf8mb4` garantuje plnou podporu české diakritiky.
* **Výkon:** Všechny tabulky využívají primární klíče a indexy na cizích klíčích pro rychlé vyhledávání.

## Core Tables

Systém se skládá ze 7 hlavních tabulek, které lze logicky rozdělit na klientská data, obchodní data a číselníky.

| Tabulka | Účel | Klíčové informace |
| --- | --- | --- |
| `klienti` | Evidence subjektů | Jméno, email, telefon, RČ/IČO a adresa. |
| `smlouvy` | Jádro systému | Propojuje klienta s produktem a pojišťovnou; obsahuje metadata o platnosti. |
| `provize` | Finanční záznamy | Částky vázané na smlouvy, storno příznaky a stupně vyplacení. |
| `dokumenty` | Správa příloh | Evidence souborů nahraných k jednotlivým smlouvám. |
| `produkty` | Číselník služeb | Seznam nabízených typů pojištění (např. Životní pojištění). |
| `pojistovny` | Číselník partnerů | Seznam spolupracujících pojišťoven.
 |
| `users` | Správa přístupů | Přihlašovací údaje, role (např. admin) a status uživatele. |

## Relationships

Vazby mezi tabulkami jsou definovány pomocí **FOREIGN KEY** (cizích klíčů), které řídí logiku odstraňování dat:

* **Identifikační vazba (CASCADE):** Pokud je smazán záznam v tabulce `klienti`, automaticky se odstraní i jeho související `smlouvy` a `provize`. To zajišťuje čistotu databáze bez zbytečných zbytků.
* **Ochranná vazba (RESTRICT):** Systém neumožní smazat záznam v číselnících `produkty` nebo `pojistovny`, pokud na ně odkazuje alespoň jedna existující smlouva. Tím je chráněna integrita historických dat.
* **Propojovací logika:** Tabulka `smlouvy` funguje jako centrální uzel, který přes JOIN operace spojuje klienta, produkt a pojišťovnu pro potřeby vyhledávání (např. v `search_smlouvy.php`).

---

## Notes for Development

Při dalším vývoji a údržbě systému je nutné brát v úvahu následující technické aspekty:

* **Automatizace ID:** Všechny tabulky používají `AUTO_INCREMENT`, což eliminuje kolize při souběžném vkládání záznamů více uživateli.
* **Auditní stopy:** Tabulky využívají datový typ `datetime` s nastavením `DEFAULT CURRENT_TIMESTAMP`, což umožňuje sledovat přesný čas vytvoření každého záznamu.
* **Finanční přesnost:** Pro částky v tabulce `provize` je použit typ `decimal`, který je pro finanční operace vhodnější než plovoucí řádová čárka.
* **Rozšiřitelnost:** Díky modulární struktuře číselníků (`produkty`, `pojistovny`) lze systém snadno rozšiřovat o nové typy služeb bez nutnosti zásahu do struktury kódu.
