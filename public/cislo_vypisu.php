<?php
include_once __DIR__ . '/../app/includes/login.php';
require_login();
include_once __DIR__ . '/../app/includes/header.php';
include_once __DIR__ . '/../app/includes/db_connect.php';

$selected_cislo = $_GET['cislo'] ?? '';

// Získání seznamu všech čísel výpisů z provizí
$sql_cisla_vypisu = "SELECT DISTINCT cislo_vypisu FROM provize WHERE cislo_vypisu IS NOT NULL AND cislo_vypisu != '' ORDER BY cislo_vypisu";
$result_cisla_vypisu = $conn->query($sql_cisla_vypisu);
$cisla_vypisu = [];
if ($result_cisla_vypisu->num_rows > 0) {
    while ($row = $result_cisla_vypisu->fetch_assoc()) {
        $cisla_vypisu[] = $row;
    }
}

// Pokud je vybráno konkrétní číslo výpisu, načteme smlouvy, které mají provizi s tímto číslem výpisu
$smlouvy = [];
if (!empty($selected_cislo)) {
    $sql_smlouvy = "
        SELECT 
            smlouvy.*,
            klienti.jmeno AS jmeno_klienta,
            produkty.nazev AS nazev_produktu,
            pojistovny.nazev AS nazev_pojistovny
        FROM smlouvy
        LEFT JOIN klienti ON smlouvy.klient_id = klienti.id
        LEFT JOIN produkty ON smlouvy.produkt_id = produkty.id
        LEFT JOIN pojistovny ON smlouvy.pojistovna_id = pojistovny.id
        WHERE smlouvy.id IN (
            SELECT DISTINCT smlouva_id 
            FROM provize 
            WHERE cislo_vypisu = ?
        )
        ORDER BY smlouvy.datum_sjednani DESC
    ";
    $stmt = $conn->prepare($sql_smlouvy);
    $stmt->bind_param("s", $selected_cislo);
    $stmt->execute();
    $result_smlouvy = $stmt->get_result();
    if ($result_smlouvy->num_rows > 0) {
        while ($row = $result_smlouvy->fetch_assoc()) {
            $smlouvy[] = $row;
        }
    }
    $stmt->close();
}
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Čísla výpisů</h1>

    <?php if (empty($selected_cislo)): ?>
        <!-- Seznam čísel výpisů -->
        <div class="mb-6">
            <p class="text-gray-600 mb-4">Klikněte na číslo výpisu pro zobrazení příslušných smluv.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cisla_vypisu as $cislo): ?>
                <a href="?cislo=<?php echo urlencode($cislo['cislo_vypisu']); ?>"
                    class="block p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                    <h2 class="text-lg font-semibold text-green-800"><?php echo htmlspecialchars($cislo['cislo_vypisu']); ?></h2>
                    <p class="text-sm text-green-600 mt-1">Klikněte pro detail</p>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($cisla_vypisu)): ?>
            <div class="text-center py-8">
                <p class="text-gray-500 text-lg">Žádná čísla výpisů nebyly nalezeny.</p>
                <p class="text-gray-400 mt-2">Čísla výpisů se zobrazí po jejich zadání u provizí.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Detail čísla výpisu -->
        <div class="mb-6">
            <a href="cislo_vypisu.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Zpět na seznam čísel výpisů
            </a>
            <h2 class="text-2xl font-semibold text-gray-800">Číslo výpisu: <span class="text-green-600"><?php echo htmlspecialchars($selected_cislo); ?></span></h2>
            <p class="text-gray-600 mt-2">Počet smluv: <span class="font-semibold"><?php echo count($smlouvy); ?></span></p>
        </div>

        <!-- Tabulka smluv - STEJNÁ JAKO V PREDAVACI_DOKUMENTY.PHP -->
        <?php if (!empty($smlouvy)): ?>
            <div class="overflow-x-auto bg-white rounded-lg border border-gray-200 shadow-sm mt-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo smlouvy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produkt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pojišťovna</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sjednáno</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platnost od</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soubor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($smlouvy as $smlouva): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['jmeno_klienta']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="smlouvy.php?search=<?php echo urlencode($smlouva['cislo_smlouvy']); ?>" class="text-blue-600 hover:text-blue-800">
                                        <?php echo htmlspecialchars($smlouva['cislo_smlouvy']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['nazev_produktu']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['nazev_pojistovny']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['datum_sjednani']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['datum_platnosti']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if (!empty($smlouva['cesta_k_souboru'])): ?>
                                        <a href="<?php echo htmlspecialchars($smlouva['cesta_k_souboru']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Otevřít
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <p class="text-gray-500 text-lg">Pro toto číslo výpisu nebyly nalezeny žádné smlouvy.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$conn->close();
include_once __DIR__ . '/../app/includes/footer.php';
?>