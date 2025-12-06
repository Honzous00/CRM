<?php
// Vložení login logiky a kontrola přihlášení
include_once __DIR__ . '/../app/includes/login.php';
require_login();

// Vložení hlavičky a připojení k databázi
include_once __DIR__ . '/../app/includes/header.php';
include_once __DIR__ . '/../app/includes/db_connect.php';

// Zpracování filtru období
$selected_period = isset($_GET['period']) ? $_GET['period'] : 'celkem';

// SQL dotaz pro získání počtu záznamů
$sql_klienti = "SELECT COUNT(*) AS total FROM klienti";
$result_klienti = $conn->query($sql_klienti);
$total_klienti = $result_klienti->fetch_assoc()['total'];

$sql_smlouvy = "SELECT COUNT(*) AS total FROM smlouvy";
$result_smlouvy = $conn->query($sql_smlouvy);
$total_smlouvy = $result_smlouvy->fetch_assoc()['total'];

// SQL dotaz pro získání součtu vyplacené částky provizí podle období
// DŮLEŽITÉ: Sčítáme pouze nestornované provize (stornovana = 0)
$sql_provize_castka = "SELECT SUM(castka) AS total FROM provize WHERE stornovana = 0";

switch ($selected_period) {
    case 'minuly_rok':
        $start_date = date('Y-01-01', strtotime('-1 year'));
        $end_date = date('Y-12-31', strtotime('-1 year'));
        $sql_provize_castka .= " AND datum_vyplaty BETWEEN '$start_date' AND '$end_date'";
        $current_year = date('Y') - 1;
        break;
    case 'minuly_mesic':
        $start_date = date('Y-m-01', strtotime('last month'));
        $end_date = date('Y-m-t', strtotime('last month'));
        $sql_provize_castka .= " AND datum_vyplaty BETWEEN '$start_date' AND '$end_date'";
        $previous_month_number = date('n', strtotime('last month'));
        $month_names = [
            1 => 'Leden',
            2 => 'Únor',
            3 => 'Březen',
            4 => 'Duben',
            5 => 'Květen',
            6 => 'Červen',
            7 => 'Červenec',
            8 => 'Srpen',
            9 => 'Září',
            10 => 'Říjen',
            11 => 'Listopad',
            12 => 'Prosinec'
        ];
        $month_name = $month_names[$previous_month_number];
        break;
    case 'tento_mesic':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        $sql_provize_castka .= " AND datum_vyplaty BETWEEN '$start_date' AND '$end_date'";
        break;
    case 'celkem':
    default:
        // Žádný dodatečný filtr - zobrazit vše
        break;
}

$result_provize_castka = $conn->query($sql_provize_castka);

// Opravená logika - zkontrolujeme, zda existuje výsledek, než se k němu pokusíme přistoupit
$total_provize_castka = 0;
if ($result_provize_castka && $result_provize_castka->num_rows > 0) {
    $row = $result_provize_castka->fetch_assoc();
    $total_provize_castka = $row['total'] ?? 0;
}

// Dynamický nadpis podle vybraného období
$provize_title = "Celkem vyplaceno provizí";
switch ($selected_period) {
    case 'minuly_rok':
        $provize_title = "Provize $current_year";
        break;
    case 'minuly_mesic':
        $provize_title = "Provize $month_name";
        break;
    case 'tento_mesic':
        $provize_title = "Aktuální provize";
        break;
    case 'celkem':
    default:
        $provize_title = "Celkem vyplaceno provizí";
        break;
}

// Získání posledních 5 smluv
$sql_last_smlouvy = "SELECT * FROM smlouvy ORDER BY datum_sjednani DESC LIMIT 5";
$result_last_smlouvy = $conn->query($sql_last_smlouvy);
$last_smlouvy = [];
if ($result_last_smlouvy->num_rows > 0) {
    while ($row = $result_last_smlouvy->fetch_assoc()) {
        $last_smlouvy[] = $row;
    }
}

// Získání posledních 5 klientů
$sql_last_klienti = "SELECT * FROM klienti ORDER BY datum_vytvoreni DESC LIMIT 5";
$result_last_klienti = $conn->query($sql_last_klienti);
$last_klienti = [];
if ($result_last_klienti->num_rows > 0) {
    while ($row = $result_last_klienti->fetch_assoc()) {
        $last_klienti[] = $row;
    }
}

// Mapování hodnot na české názvy pro dropdown
$period_labels = [
    'celkem' => 'Celkem',
    'minuly_rok' => 'Minulý rok',
    'minuly_mesic' => 'Minulý měsíc',
    'tento_mesic' => 'Tento měsíc'
];
?>

<div class="container mx-auto mt-8 px-4">
    <h1 class="text-4xl font-bold text-gray-800 mb-6 text-center">Přehledný panel</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Počet klientů -->
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Celkový počet klientů</h2>
            <p class="text-5xl font-bold text-blue-600"><?php echo $total_klienti; ?></p>
        </div>
        <!-- Počet smluv -->
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Celkový počet smluv</h2>
            <p class="text-5xl font-bold text-green-600"><?php echo $total_smlouvy; ?></p>
        </div>
        <!-- Celková částka provizí -->
        <div class="bg-white rounded-lg shadow-md p-6 text-center relative">
            <!-- Ikona filtru v pravém horním rohu -->
            <div class="absolute top-4 right-4">
                <div class="relative">
                    <!-- Tlačítko s ikonou filtru -->
                    <button
                        id="filterButton"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500"
                        title="Filtrovat období">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                        </svg>
                    </button>

                    <!-- Skrytý dropdown menu -->
                    <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-10 py-1">
                        <form method="GET" id="periodForm">
                            <?php foreach ($period_labels as $value => $label): ?>
                                <button
                                    type="submit"
                                    name="period"
                                    value="<?php echo $value; ?>"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 <?php echo $selected_period == $value ? 'bg-purple-50 text-purple-700' : ''; ?>">
                                    <?php echo $label; ?>
                                </button>
                            <?php endforeach; ?>
                        </form>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-semibold text-gray-700 mb-2"><?php echo $provize_title; ?></h2>
            <p class="text-5xl font-bold text-purple-600"><?php echo number_format($total_provize_castka, 2, ',', ' '); ?> Kč</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Posledních 5 klientů -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Posledních 5 klientů</h2>
            <ul class="divide-y divide-gray-200">
                <?php if (!empty($last_klienti)): ?>
                    <?php foreach ($last_klienti as $klient): ?>
                        <li class="py-4 flex justify-between items-center">
                            <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($klient['jmeno']); ?></span>
                            <span class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($klient['datum_vytvoreni'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="py-4 text-gray-500">Zatím nejsou přidáni žádní klienti.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Posledních 5 smluv -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Posledních 5 smluv</h2>
            <ul class="divide-y divide-gray-200">
                <?php if (!empty($last_smlouvy)): ?>
                    <?php foreach ($last_smlouvy as $smlouva): ?>
                        <li class="py-4 flex justify-between items-center">
                            <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($smlouva['cislo_smlouvy']); ?></span>
                            <span class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($smlouva['datum_sjednani'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="py-4 text-gray-500">Zatím nejsou přidány žádné smlouvy.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterButton = document.getElementById('filterButton');
        const filterDropdown = document.getElementById('filterDropdown');

        // Přepínání dropdown menu
        filterButton.addEventListener('click', function(e) {
            e.stopPropagation();
            filterDropdown.classList.toggle('hidden');
        });

        // Skrytí dropdown menu při kliknutí mimo
        document.addEventListener('click', function() {
            filterDropdown.classList.add('hidden');
        });

        // Zabránění skrytí při kliknutí do dropdown menu
        filterDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
</script>

<?php
// Uzavření připojení k databázi
$conn->close();

// Vložení patičky
include_once __DIR__ . '/../app/includes/footer.php';
?>