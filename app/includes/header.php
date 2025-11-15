<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Můj Klientský Systém</title>

    <!-- Pro rychlejší načítání CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Váš vlastní CSS soubor -->
    <link rel="stylesheet" href="/public/css/style.css">
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../public/index.php" class="text-2xl font-bold">Můj CMS</a>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="../public/klienti.php" class="hover:text-blue-200 transition-colors duration-200">Klienti</a></li>
                    <li><a href="../public/smlouvy.php" class="hover:text-blue-200 transition-colors duration-200">Smlouvy</a></li>
                    <li><a href="../public/provize.php" class="hover:text-blue-200 transition-colors duration-200">Provize</a></li>
                    <li><a href="../public/predavaci_dokumenty.php" class="hover:text-blue-200 transition-colors duration-200">Předávací dokumenty</a></li>
                    <li><a href="../public/produkty.php" class="hover:text-blue-200 transition-colors duration-200">Produkty</a></li>
                    <li><a href="../public/pojistovny.php" class="hover:text-blue-200 transition-colors duration-200">Pojišťovny</a></li>
                    <li><a href="../public/logout.php" class="hover:text-blue-200 transition-colors duration-200">Odhlášení</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="flex-grow">