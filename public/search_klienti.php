<?php
include_once __DIR__ . '/../app/includes/login.php';
require_login();
include_once __DIR__ . '/../app/includes/db_connect.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$klienti = [];

$sql_klienti = "SELECT * FROM klienti";
$params = [];
$types = '';

if (!empty($search_query)) {
    $sql_klienti .= " WHERE jmeno LIKE ? OR email LIKE ? OR rc_ico LIKE ?";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$sql_klienti .= " ORDER BY jmeno ASC";

$stmt_klienti = $conn->prepare($sql_klienti);

if (!empty($params)) {
    $stmt_klienti->bind_param($types, ...$params);
}

$stmt_klienti->execute();
$result_klienti = $stmt_klienti->get_result();

if ($result_klienti->num_rows > 0) {
    while ($row = $result_klienti->fetch_assoc()) {
        $klienti[] = $row;
    }
}
?>

<div class="overflow-x-auto bg-gray-50 rounded-md border border-gray-200 shadow-sm">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jméno / Firma</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rodné č. / IČO</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefon</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trvalá adresa</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Korespond. adresa</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akce</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($klienti)): ?>
                <?php foreach ($klienti as $klient): ?>
                    <tr class="cursor-pointer hover:bg-blue-50 transition-colors duration-150 client-row"
                        data-client-id="<?php echo $klient['id']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['jmeno']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['rc_ico']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['telefon']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['ulice'] . ', ' . $klient['mesto'] . ', ' . $klient['psc']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php
                            if ($klient['ulice'] !== $klient['korespondencni_ulice'] || $klient['mesto'] !== $klient['korespondencni_mesto'] || $klient['psc'] !== $klient['korespondencni_psc']) {
                                echo htmlspecialchars($klient['korespondencni_ulice'] . ', ' . $klient['korespondencni_mesto'] . ', ' . $klient['korespondencni_psc']);
                            } else {
                                echo 'shodná s trvalou';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" onclick="event.stopPropagation()">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($klient)); ?>)" class="text-blue-600 hover:text-blue-900 mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>Upravit
                            </button>
                            <a href="klienti.php?action=delete&id=<?php echo $klient['id']; ?>" onclick="return confirm('Opravdu chcete smazat tohoto klienta a všechny související smlouvy a provize?');" class="text-red-600 hover:text-red-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Smazat
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                        Žádní klienti nebyli nalezeni.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$conn->close();
?>