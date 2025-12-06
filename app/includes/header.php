<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Můj Klientský Systém</title>

    <!-- Pro rychlejší načítání CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Váš vlastní CSS soubor -->
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4 shadow-xl">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../public/index.php" class="text-3xl font-extrabold">Můj <span class="text-blue-200">CMS</span></a>
            <nav>
                <ul class="flex space-x-2 items-center">

                    <li>
                        <a href="../public/klienti.php" class="nav-item">
                            <i class="fas fa-users mr-2"></i> <span class="hidden sm:inline">Klienti</span>
                        </a>
                    </li>

                    <li>
                        <a href="../public/smlouvy.php" class="nav-item">
                            <i class="fas fa-file-contract mr-2"></i> <span class="hidden sm:inline">Smlouvy</span>
                        </a>
                    </li>

                    <li>
                        <a href="../public/provize.php" class="nav-item">
                            <i class="fas fa-hand-holding-dollar mr-2"></i> <span class="hidden sm:inline">Provize</span>
                        </a>
                    </li>

                    <li class="relative" id="reports-li"> <button class="nav-item flex items-center space-x-2">
                            <i class="fas fa-folder-open"></i>
                            <span class="hidden sm:inline"> Reporty</span>
                            <i class="fas fa-caret-down text-xs ml-1"></i>
                        </button>
                        <div id="reports-dropdown" class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-xl py-1 hidden z-20 border border-gray-200">
                            <a href="../public/predavaci_dokumenty.php" class="dropdown-item">
                                <i class="fas fa-file-export mr-2"></i>Předávací dokumenty
                            </a>
                            <a href="../public/cislo_vypisu.php" class="dropdown-item">
                                <i class="fas fa-receipt mr-2"></i> Výpis provizí
                            </a>
                        </div>
                    </li>

                    <li class="relative" id="settings-li"> <button class="nav-item flex items-center space-x-2">
                            <i class="fas fa-cogs"></i>
                            <span class="hidden sm:inline"> Nastavení</span>
                            <i class="fas fa-caret-down text-xs ml-1"></i>
                        </button>
                        <div id="settings-dropdown" class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-xl py-1 hidden z-20 border border-gray-200">
                            <a href="../public/produkty.php" class="dropdown-item">
                                <i class="fas fa-cube mr-2"></i> Produkty
                            </a>
                            <a href="../public/pojistovny.php" class="dropdown-item">
                                <i class="fas fa-building mr-2"></i> Pojišťovny
                            </a>
                        </div>
                    </li>

                    <li id="logoutTimerWrapper" class="flex items-center space-x-2 py-2 px-3 text-sm rounded-lg bg-blue-700/50">
                        <i class="fas fa-clock"></i>
                        <span id="countdownTimer" class="font-medium">načítání...</span>
                    </li>

                    <li>
                        <a href="../public/logout.php" class="flex items-center space-x-2 py-2 px-3 rounded-lg bg-blue-700 hover:bg-red-600 transition-colors duration-200" title="Odhlášení">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="hidden sm:inline">Odhlášení</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="flex-grow">

        <!-- Načtení JavaScriptu na konci stránky -->
        <script src="../public/js/autologout.js?v=<?php echo filemtime(__DIR__ . '/../../public/js/autologout.js'); ?>"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const reportsLi = document.getElementById('reports-li');
                const reportsDropdown = document.getElementById('reports-dropdown');
                const settingsLi = document.getElementById('settings-li');
                const settingsDropdown = document.getElementById('settings-dropdown');

                let timeoutId; // Proměnná pro ukládání ID prodlevy

                function setupHoverDropdown(liElement, dropdownElement) {
                    if (liElement && dropdownElement) {

                        // Otevřít menu při najetí
                        liElement.addEventListener('mouseenter', function() {
                            clearTimeout(timeoutId); // Zrušíme jakýkoliv čekající timeout

                            // Logika pro ZAVÍRÁNÍ OSTATNÍCH MENU
                            document.querySelectorAll('[id$="-dropdown"]').forEach(d => {
                                if (d.id !== dropdownElement.id) {
                                    d.classList.add('hidden');
                                }
                            });

                            dropdownElement.classList.remove('hidden');
                        });

                        // Zavřít menu s PRODLEVOU při opuštění LI
                        liElement.addEventListener('mouseleave', function() {
                            // Nastavíme timeout 150 ms - to dává čas na přesun myši do dropdownu
                            timeoutId = setTimeout(function() {
                                dropdownElement.classList.add('hidden');
                            }, 150);
                        });

                        // Udržet menu otevřené, když myš najede do DROPDOWNU
                        dropdownElement.addEventListener('mouseenter', function() {
                            clearTimeout(timeoutId); // Zrušíme timeout, pokud myš vstoupí do dropdownu
                        });

                        // Zavřít dropdown, když myš opustí samotný DROPDOWN
                        dropdownElement.addEventListener('mouseleave', function() {
                            dropdownElement.classList.add('hidden');
                        });
                    }
                }

                // Aplikace logiky na obě menu
                setupHoverDropdown(reportsLi, reportsDropdown);
                setupHoverDropdown(settingsLi, settingsDropdown);
            });
        </script>

        <style>
            .nav-item {
                display: flex;
                align-items: center;
                padding: 8px 12px;
                /* py-2 px-3 */
                border-radius: 8px;
                /* rounded-lg */
                transition: background-color 200ms ease-in-out;
                color: #fff;
                white-space: nowrap;
                cursor: pointer;
            }

            .nav-item:hover {
                background-color: #3b82f6;
                /* Tmavší modrá, např. blue-500/700 */
            }

            .dropdown-item {
                display: block;
                padding: 8px 16px;
                font-size: 0.875rem;
                /* text-sm */
                color: #4b5563;
                /* text-gray-700 */
                transition: background-color 200ms ease-in-out, color 200ms ease-in-out;
            }

            .dropdown-item:hover {
                background-color: #eff6ff;
                /* blue-50 */
                color: #2563eb;
                /* blue-600 */
            }
        </style>
</body>

</html>