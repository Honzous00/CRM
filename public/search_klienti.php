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
                    <tr>
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
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($klient)); ?>)" class="text-blue-600 hover:text-blue-900 mr-2">Upravit</button>
                            <a href="klienti.php?action=delete&id=<?php echo $klient['id']; ?>" onclick="return confirm('Opravdu chcete smazat tohoto klienta a všechny související smlouvy a provize?');" class="text-red-600 hover:text-red-900">Smazat</a>
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