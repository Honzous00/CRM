<?php
// CRM/install.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kontrola, zda je již nainstalováno
$lock_file = __DIR__ . '/installed.lock';
$config_file = __DIR__ . '/app/includes/db_connect.php';

if (file_exists($lock_file)) {
    header('Location: public/index.php');
    exit;
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';
$requirements = [];

// Kontrola požadavků systému
$requirements = [
    'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'MySQLi rozšíření' => extension_loaded('mysqli'),
    'Povolené session' => function_exists('session_start'),
    'JSON podpora' => extension_loaded('json'),
    'File uploads' => ini_get('file_uploads'),
    'Zápis do souborů' => is_writable(__DIR__),
];

// Zpracování POST požadavků
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Test připojení k databázi
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_user = $_POST['db_user'] ?? 'root';
        $db_pass = $_POST['db_pass'] ?? '';
        $db_name = $_POST['db_name'] ?? 'muj_cms';

        try {
            // Test připojení k MySQL
            $test_conn = new mysqli($db_host, $db_user, $db_pass);

            if ($test_conn->connect_error) {
                throw new Exception("Nelze se připojit k MySQL: " . $test_conn->connect_error);
            }

            // Zkusíme vytvořit databázi pokud neexistuje
            if (!$test_conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci")) {
                throw new Exception("Nelze vytvořit databázi: " . $test_conn->error);
            }

            // Uložíme si data do session pro další kroky
            session_start();
            $_SESSION['install_db'] = [
                'host' => $db_host,
                'user' => $db_user,
                'pass' => $db_pass,
                'name' => $db_name
            ];

            header('Location: install.php?step=2');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    if ($step == 2) {
        session_start();
        $db_config = $_SESSION['install_db'] ?? null;

        if (!$db_config) {
            $error = "Nenalezena konfigurace databáze. Začněte znovu.";
            $step = 1;
        } else {
            // Vytvoření admin uživatele
            $admin_username = $_POST['admin_username'] ?? 'admin';
            $admin_email = $_POST['admin_email'] ?? '';
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';

            if (empty($admin_password) || $admin_password !== $admin_password_confirm) {
                $error = "Hesla se neshodují nebo jsou prázdná.";
            } elseif (strlen($admin_password) < 8) {
                $error = "Heslo musí mít alespoň 8 znaků.";
            } else {
                try {
                    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);

                    if ($conn->connect_error) {
                        throw new Exception("Chyba připojení k databázi: " . $conn->connect_error);
                    }

                    // Nastavení kódování
                    $conn->set_charset("utf8mb4");

                    // Načtení VAŠEHO SQL souboru
                    $sql_file = __DIR__ . '/muj_cms.sql';

                    if (!file_exists($sql_file)) {
                        // Pokud neexistuje váš soubor, zkusíme alternativy
                        $sql_file = __DIR__ . '/database.sql';
                        if (!file_exists($sql_file)) {
                            throw new Exception("SQL soubor nebyl nalezen. Nahrajte 'muj_cms.sql' do kořenového adresáře.");
                        }
                    }

                    // Čtení SQL souboru
                    $sql = file_get_contents($sql_file);

                    if ($sql === false) {
                        throw new Exception("Nelze načíst SQL soubor.");
                    }

                    // Spuštění SQL příkazů
                    $conn->multi_query($sql);

                    // Čekání na dokončení všech dotazů
                    while ($conn->more_results()) {
                        $conn->next_result();
                    }

                    // Ověření, zda byla vytvořena tabulka users
                    $result = $conn->query("SHOW TABLES LIKE 'users'");
                    if ($result->num_rows === 0) {
                        throw new Exception("Tabulka 'users' nebyla vytvořena. Zkontrolujte SQL soubor.");
                    }

                    // Vytvoření admin uživatele (podle vaší struktury)
                    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, is_admin, aktivni) VALUES (?, ?, 1, 1)");
                    $stmt->bind_param("ss", $admin_username, $hashed_password);

                    if (!$stmt->execute()) {
                        // Pokud uživatel již existuje, aktualizujeme heslo
                        if ($conn->errno == 1062) { // Duplicate entry
                            $stmt = $conn->prepare("UPDATE users SET password_hash = ?, is_admin = 1, aktivni = 1 WHERE username = ?");
                            $stmt->bind_param("ss", $hashed_password, $admin_username);
                            if (!$stmt->execute()) {
                                throw new Exception("Nelze aktualizovat existujícího uživatele: " . $conn->error);
                            }
                        } else {
                            throw new Exception("Chyba při vytváření admin uživatele: " . $conn->error);
                        }
                    }

                    // Vytvoření konfiguračního souboru
                    $config_content = generateConfigFile($db_config);

                    if (file_put_contents($config_file, $config_content) === false) {
                        throw new Exception("Nelze vytvořit konfigurační soubor. Zkontrolujte práva zápisu.");
                    }

                    // Vytvoření lock souboru
                    if (file_put_contents($lock_file, "Instalace dokončena: " . date('Y-m-d H:i:s') . "\nAdmin: $admin_username") === false) {
                        throw new Exception("Nelze vytvořit lock soubor. Zkontrolujte práva zápisu.");
                    }

                    // Smazání session
                    session_destroy();

                    header('Location: install.php?step=3');
                    exit;
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    // Smazání databáze při chybě (volitelné)
                    if (isset($conn)) {
                        $conn->query("DROP DATABASE IF EXISTS `{$db_config['name']}`");
                    }
                }
            }
        }
    }
}

function generateConfigFile($db_config)
{
    return '<?php
// app/includes/db_connect.php
// Vygenerováno instalací: ' . date('Y-m-d H:i:s') . '

// Bezpečná definice konstant pro připojení k databázi.
define(\'DB_SERVER\', \'' . addslashes($db_config['host']) . '\');
define(\'DB_USERNAME\', \'' . addslashes($db_config['user']) . '\');
define(\'DB_PASSWORD\', \'' . addslashes($db_config['pass']) . '\');
define(\'DB_NAME\', \'' . addslashes($db_config['name']) . '\');

// Vytvoření instance mysqli pro připojení
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola připojení
if ($conn->connect_error) {
    die("Chyba při připojování k databázi: " . $conn->connect_error);
}

// Nastavení kódování znaků na UTF-8 pro správnou podporu češtiny
$conn->set_charset("utf8mb4");
';
}
?>

<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalace CRM systému</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* ŽÁDNÉ @apply - jen čisté CSS */
        .sql-preview {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .requirement-ok {
            color: #10B981;
        }

        .requirement-error {
            color: #EF4444;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto max-w-4xl py-12 px-4">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-3">
                <i class="fas fa-cogs text-blue-600 mr-3"></i>Instalace CRM systému
            </h1>
            <p class="text-gray-600">Průvodce nastavením vašeho CRM systému</p>
        </div>

        <div class="text-center mb-8">
            <?php if ($step == 1): ?>
                <h2 class="text-2xl font-bold text-gray-700">1. Kontrola systému a databáze</h2>
                <p class="text-gray-500 mt-2">Ověření požadavků a připojení k databázi</p>
            <?php elseif ($step == 2): ?>
                <h2 class="text-2xl font-bold text-gray-700">2. Vytvoření administrátora</h2>
                <p class="text-gray-500 mt-2">Nastavte účet pro správu systému</p>
            <?php else: ?>
                <h2 class="text-2xl font-bold text-gray-700">3. Instalace dokončena</h2>
                <p class="text-gray-500 mt-2">Systém je připraven k použití</p>
            <?php endif; ?>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <strong>Chyba:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- Step 1: System Check & Database Configuration -->
                <div class="space-y-8">
                    <!-- System Requirements -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">
                            <i class="fas fa-check-circle mr-2"></i>Kontrola systému
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($requirements as $req => $status): ?>
                                <div class="flex items-center p-3 rounded-lg <?php echo $status ? 'bg-green-50' : 'bg-red-50'; ?>">
                                    <i class="fas fa-<?php echo $status ? 'check' : 'times'; ?> mr-3 <?php echo $status ? 'text-green-500' : 'text-red-500'; ?>"></i>
                                    <span class="text-gray-700"><?php echo $req; ?></span>
                                    <span class="ml-auto text-sm font-medium <?php echo $status ? 'text-green-700' : 'text-red-700'; ?>">
                                        <?php echo $status ? 'OK' : 'CHYBA'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php
                        $all_ok = !in_array(false, $requirements);
                        if (!$all_ok): ?>
                            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-yellow-700">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Některé požadavky nejsou splněny. Instalace může fungovat omezeně.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SQL File Check -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">
                            <i class="fas fa-database mr-2"></i>Kontrola SQL souboru
                        </h3>
                        <?php
                        $sql_file = __DIR__ . '/muj_cms.sql';
                        $sql_file2 = __DIR__ . '/database.sql';
                        $sql_exists = file_exists($sql_file) || file_exists($sql_file2);
                        ?>
                        <div class="p-4 rounded-lg <?php echo $sql_exists ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $sql_exists ? 'check' : 'times'; ?> mr-3 <?php echo $sql_exists ? 'text-green-500' : 'text-red-500'; ?>"></i>
                                <div>
                                    <p class="<?php echo $sql_exists ? 'text-green-700' : 'text-red-700'; ?> font-medium">
                                        <?php if ($sql_exists): ?>
                                            SQL soubor nalezen:
                                            <?php
                                            if (file_exists($sql_file)) {
                                                $size = filesize($sql_file);
                                                echo "muj_cms.sql (" . round($size / 1024, 1) . " KB)";
                                            } else {
                                                $size = filesize($sql_file2);
                                                echo "database.sql (" . round($size / 1024, 1) . " KB)";
                                            }
                                            ?>
                                        <?php else: ?>
                                            SQL soubor nebyl nalezen! Nahrajte 'muj_cms.sql' do kořenového adresáře.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <?php if ($sql_exists): ?>
                                <div class="mt-3">
                                    <details class="cursor-pointer">
                                        <summary class="text-sm text-gray-600 hover:text-gray-800">Náhled struktury databáze</summary>
                                        <div class="mt-2 sql-preview">
                                            <?php
                                            $file_to_read = file_exists($sql_file) ? $sql_file : $sql_file2;
                                            $content = file_get_contents($file_to_read);
                                            $lines = explode("\n", $content);
                                            $preview_lines = array_slice($lines, 0, 30);
                                            echo htmlspecialchars(implode("\n", $preview_lines));
                                            if (count($lines) > 30) {
                                                echo "\n... (" . (count($lines) - 30) . " dalších řádků)";
                                            }
                                            ?>
                                        </div>
                                    </details>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Database Configuration Form -->
                    <form method="POST" action="install.php?step=1" class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">
                            <i class="fas fa-server mr-2"></i>Konfigurace databáze
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="db_host" class="block text-sm font-medium text-gray-700 mb-2">
                                    Hostitel databáze
                                </label>
                                <input type="text" id="db_host" name="db_host" value="localhost" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-3">
                                <p class="mt-1 text-sm text-gray-500">Obvykle <code>localhost</code></p>
                            </div>

                            <div>
                                <label for="db_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Název databáze
                                </label>
                                <input type="text" id="db_name" name="db_name" value="muj_cms" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-3">
                                <p class="mt-1 text-sm text-gray-500">Bude vytvořena pokud neexistuje</p>
                            </div>

                            <div>
                                <label for="db_user" class="block text-sm font-medium text-gray-700 mb-2">
                                    Uživatel databáze
                                </label>
                                <input type="text" id="db_user" name="db_user" value="root" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-3">
                            </div>

                            <div>
                                <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-2">
                                    Heslo databáze
                                </label>
                                <input type="password" id="db_pass" name="db_pass"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-3">
                            </div>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Tipy pro XAMPP/Laragon</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li><strong>XAMPP:</strong> Uživatel: <code>root</code>, Heslo: <em>prázdné</em></li>
                                            <li><strong>Laragon:</strong> Uživatel: <code>root</code>, Heslo: <em>prázdné</em></li>
                                            <li>Ujistěte se, že MySQL služba je spuštěna</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200 <?php echo (!$sql_exists || !$all_ok) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo (!$sql_exists || !$all_ok) ? 'disabled' : ''; ?>>
                                <i class="fas fa-arrow-right mr-2"></i> Pokračovat k dalšímu kroku
                            </button>
                            <?php if (!$sql_exists): ?>
                                <p class="mt-2 text-sm text-red-600 text-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Nelze pokračovat bez SQL souboru
                                </p>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- Step 2: Admin Creation -->
                <form method="POST" action="install.php?step=2" class="space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label for="admin_username" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-circle mr-2"></i>Uživatelské jméno administrátora
                            </label>
                            <input type="text" id="admin_username" name="admin_username" value="admin" required
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-3">
                            <p class="mt-1 text-sm text-gray-500">Toto jméno použijete pro přihlášení</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-key mr-2"></i>Heslo administrátora
                                </label>
                                <input type="password" id="admin_password" name="admin_password" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-3"
                                    minlength="8">
                                <div class="mt-1 text-sm">
                                    <p class="text-gray-500">Minimálně 8 znaků</p>
                                    <div id="password-strength" class="mt-2 hidden">
                                        <div class="flex items-center space-x-1">
                                            <div class="h-2 flex-1 bg-gray-200 rounded-full overflow-hidden">
                                                <div id="password-strength-bar" class="h-full transition-all duration-300"></div>
                                            </div>
                                            <span id="password-strength-text" class="text-xs font-medium"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="admin_password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-key mr-2"></i>Potvrzení hesla
                                </label>
                                <input type="password" id="admin_password_confirm" name="admin_password_confirm" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-3">
                                <div id="password-match" class="mt-1 text-sm hidden">
                                    <i class="fas fa-check text-green-500 mr-1"></i>
                                    <span class="text-green-600">Hesla se shodují</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database Info Preview -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium text-gray-700 mb-2">
                            <i class="fas fa-database mr-2"></i>Konfigurace databáze
                        </h4>
                        <?php if (isset($db_config)): ?>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="text-gray-600">Host:</div>
                                <div class="font-mono"><?php echo htmlspecialchars($db_config['host']); ?></div>
                                <div class="text-gray-600">Databáze:</div>
                                <div class="font-mono"><?php echo htmlspecialchars($db_config['name']); ?></div>
                                <div class="text-gray-600">Uživatel:</div>
                                <div class="font-mono"><?php echo htmlspecialchars($db_config['user']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Důležité bezpečnostní informace</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li>Tento účet bude mít <strong>plná administrátorská práva</strong></li>
                                        <li>Heslo by mělo být <strong>silné a jedinečné</strong></li>
                                        <li>Údaje si poznamenejte, nebudou zobrazeny znovu</li>
                                        <li>Po instalaci bude vytvořena kompletní databáze s předdefinovanými produkty a pojišťovnami</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-4">
                        <a href="install.php?step=1" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-4 rounded-lg transition-colors duration-200 text-center">
                            <i class="fas fa-arrow-left mr-2"></i> Zpět
                        </a>
                        <button type="submit" id="install-button" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200">
                            <i class="fas fa-play-circle mr-2"></i> Spustit instalaci
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <!-- Step 3: Installation Complete -->
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check text-green-600 text-4xl"></i>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Instalace byla úspěšně dokončena!</h3>

                    <div class="max-w-md mx-auto space-y-4 mb-8">
                        <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                            <p class="text-green-700">
                                <strong>CRM systém je nyní připraven k použití</strong>
                            </p>
                            <p class="text-sm text-green-600 mt-1">
                                Byly vytvořeny všechny tabulky a vložena výchozí data.
                            </p>
                        </div>

                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200 text-left">
                            <h4 class="font-medium text-blue-800 mb-2">
                                <i class="fas fa-check-circle mr-2"></i>Co bylo nainstalováno:
                            </h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>✓ Tabulka uživatelů (users) s admin účtem</li>
                                <li>✓ Tabulka klientů (klienti)</li>
                                <li>✓ Tabulka smluv (smlouvy)</li>
                                <li>✓ Tabulka provizí (provize)</li>
                                <li>✓ Tabulka dokumentů a jejich typů</li>
                                <li>✓ Tabulka produktů (12 předdefinovaných)</li>
                                <li>✓ Tabulka pojišťoven (15 předdefinovaných)</li>
                                <li>✓ Tabulka předávacích dokumentů</li>
                                <li>✓ Všechny vazby a cizí klíče</li>
                            </ul>
                        </div>

                        <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                            <p class="text-red-700">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Bezpečnostní upozornění:</strong>
                                Tento instalační soubor ihned smažte!
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <a href="public/login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i> Přihlásit se do systému
                        </a>

                        <div class="text-sm text-gray-500">
                            <p>Pro přihlášení použijte údaje, které jste zadali v předchozím kroku</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Můj CRM systém</p>
            <p class="mt-1">Tento instalační průvodce se automaticky zablokuje po úspěšné instalaci</p>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('admin_password');
        const confirmInput = document.getElementById('admin_password_confirm');
        const strengthBar = document.getElementById('password-strength-bar');
        const strengthText = document.getElementById('password-strength-text');
        const strengthContainer = document.getElementById('password-strength');
        const matchContainer = document.getElementById('password-match');
        const installButton = document.getElementById('install-button');

        function checkPasswordStrength(password) {
            let strength = 0;

            // Délka
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;

            // Různé znaky
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;

            return strength;
        }

        function updateStrengthIndicator() {
            const password = passwordInput.value;
            const strength = checkPasswordStrength(password);

            if (password.length === 0) {
                strengthContainer.classList.add('hidden');
                return;
            }

            strengthContainer.classList.remove('hidden');

            let color, width, text;
            switch (true) {
                case strength >= 5:
                    color = 'bg-green-500';
                    width = '100%';
                    text = 'Velmi silné';
                    break;
                case strength >= 4:
                    color = 'bg-green-400';
                    width = '80%';
                    text = 'Silné';
                    break;
                case strength >= 3:
                    color = 'bg-yellow-500';
                    width = '60%';
                    text = 'Střední';
                    break;
                case strength >= 2:
                    color = 'bg-orange-500';
                    width = '40%';
                    text = 'Slabé';
                    break;
                default:
                    color = 'bg-red-500';
                    width = '20%';
                    text = 'Velmi slabé';
            }

            strengthBar.className = `h-full transition-all duration-300 ${color}`;
            strengthBar.style.width = width;
            strengthText.textContent = text;
            strengthText.className = `text-xs font-medium ${color.replace('bg-', 'text-')}`;
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            if (password.length === 0 || confirm.length === 0) {
                matchContainer.classList.add('hidden');
                return;
            }

            if (password === confirm) {
                matchContainer.classList.remove('hidden');
                matchContainer.querySelector('span').textContent = 'Hesla se shodují';
                matchContainer.querySelector('i').className = 'fas fa-check text-green-500 mr-1';
            } else {
                matchContainer.classList.remove('hidden');
                matchContainer.querySelector('span').textContent = 'Hesla se neshodují';
                matchContainer.querySelector('i').className = 'fas fa-times text-red-500 mr-1';
            }
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                updateStrengthIndicator();
                checkPasswordMatch();
            });
        }

        if (confirmInput) {
            confirmInput.addEventListener('input', checkPasswordMatch);
        }

        // Form validation before installation
        const form = document.querySelector('form[action*="step=2"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirm = confirmInput.value;

                if (password.length < 8) {
                    e.preventDefault();
                    alert('Heslo musí mít alespoň 8 znaků.');
                    return;
                }

                if (password !== confirm) {
                    e.preventDefault();
                    alert('Hesla se neshodují.');
                    return;
                }

                // Show loading state
                if (installButton) {
                    installButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Probíhá instalace...';
                    installButton.disabled = true;
                }
            });
        }
    </script>
</body>

</html>