<?php
include_once __DIR__ . '/../app/includes/login.php';
require_login();
include_once __DIR__ . '/../app/includes/db_connect.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// SQL dotaz – načte všechny provize odpovídající vyhledávání (i s JOINy na potřeby pro seskupení)
$sql = "
    SELECT
        p.id,
        p.smlouva_id,
        p.castka,
        s.cislo_smlouvy,
        k.jmeno AS jmeno_klienta
    FROM provize p
    LEFT JOIN smlouvy s ON p.smlouva_id = s.id
    LEFT JOIN klienti k ON s.klient_id = k.id
";

if (!empty($search_query)) {
    $sql .= " WHERE k.jmeno LIKE ? OR s.cislo_smlouvy LIKE ?";
}

$sql .= " ORDER BY p.smlouva_id, p.datum_vytvoreni DESC";

$stmt = $conn->prepare($sql);

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt->bind_param("ss", $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();

// Seskupení podle smlouva_id
$contracts = [];
while ($row = $result->fetch_assoc()) {
    $smlouva_id = $row['smlouva_id'];
    if (!isset($contracts[$smlouva_id])) {
        $contracts[$smlouva_id] = [
            'smlouva_id' => $smlouva_id,
            'cislo_smlouvy' => $row['cislo_smlouvy'],
            'jmeno_klienta' => $row['jmeno_klienta'],
            'count' => 0,
            'total' => 0
        ];
    }
    $contracts[$smlouva_id]['count']++;
    $contracts[$smlouva_id]['total'] += $row['castka'];
}
?>

<?php if (!empty($contracts)): ?>
    <div class="overflow-x-auto bg-gray-50 rounded-md border border-gray-200 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo smlouvy</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Počet provizí</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Celková částka</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" colspan="7"> </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($contracts as $contract): ?>
                    <tr class="hover:bg-blue-50 transition-colors duration-150 cursor-pointer contract-row"
                        data-smlouva-id="<?php echo $contract['smlouva_id']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($contract['jmeno_klienta']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <a href="smlouvy.php?search=<?php echo urlencode($contract['cislo_smlouvy']); ?>"
                                class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($contract['cislo_smlouvy']); ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $contract['count']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo number_format($contract['total'], 2, ',', ' '); ?> Kč
                        </td>
                        <td colspan="7" class="px-6 py-4 text-sm text-gray-400 italic">
                            Kliknutím na řádek zobrazíte detail provizí
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-gray-500 text-center py-4">Žádné provize nebyly nalezeny.</p>
<?php endif; ?>

<?php
$conn->close();
?>