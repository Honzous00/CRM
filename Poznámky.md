1. app/includes/db_connect.php
Toto je nejdůležitější soubor z celého projektu, který se stará o komunikaci s databází.

Účel: Obsahuje přihlašovací údaje k databázi (jméno databáze, uživatelské jméno a heslo) a kód pro vytvoření připojení k MySQL serveru. Tento soubor bude vkládán do všech ostatních PHP souborů, které potřebují pracovat s daty.

Očekávaný kód:

Definice konstant pro nastavení připojení (např. DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME). Použití konstant je bezpečnější než proměnných, protože se nemění za běhu skriptu.

Vytvoření nového objektu mysqli pro připojení.

Kontrola, zda připojení proběhlo úspěšně. Pokud ne, skript by měl vypsat chybovou zprávu a ukončit se, aby se předešlo bezpečnostním rizikům a zbytečným chybám.

Nastavení kódování znaků na UTF-8, aby se správně zobrazovala česká diakritika (á, č, ř, ž...).

2. app/includes/header.php
Tento soubor bude obsahovat opakující se horní část každé webové stránky.

Účel: Zajišťuje jednotný vzhled a strukturu na celém webu. Měl by obsahovat začátek HTML dokumentu, metatagy, titul stránky, odkazy na CSS styly (např. Bootstrap a váš style.css) a hlavní navigační menu.

Očekávaný kód:

Zahajovací tagy <!DOCTYPE html>, <html> a <head>.

<meta> tagy pro kódování a responzivní design.

<title> tag, který se bude měnit podle toho, na které stránce se zrovna nacházíte (např. "Přehled klientů - CMS").

Odkazy na styly (<link rel="stylesheet"...>).

Navigační bar (např. <nav>), který bude obsahovat odkazy na "Klienti", "Smlouvy" a další hlavní sekce.

Otevírací tag <body>.

3. app/includes/footer.php
Tento soubor bude obsahovat opakující se dolní část každé webové stránky.

Účel: Uzavírá HTML dokument a obsahuje patu webu, copyright a odkazy na JavaScriptové soubory (např. jQuery, Bootstrap JS a váš main.js).

Očekávaný kód:

Patička s informacemi o copyrightu (<footer>).

Odkazy na JavaScriptové soubory (<script src="...">). Je dobré je umístit na konec, aby se stránka načítala rychleji.

Ukončovací tagy </body> a </html>.

4. public/index.php
Toto je hlavní vstupní bod vaší aplikace, tedy úvodní stránka, která se zobrazí po otevření domény.

Účel: Slouží jako přehledný dashboard nebo uvítací stránka. Bude obsahovat základní přehled o systému, například počet klientů, počet smluv atd.

Očekávaný kód:

Vkládání souborů header.php a footer.php (pomocí include_once nebo require_once).

Hlavní HTML obsah stránky, například nadpis <h1>Vítejte v klientském systému</h1> a stručné informace.