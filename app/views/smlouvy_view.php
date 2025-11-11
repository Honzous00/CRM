<?php

function displaySmlouvyTable($smlouvy, $conn)
{
    if (empty($smlouvy)) {
        echo '<p class="text-gray-500">Zatím nejsou přidány žádné smlouvy.</p>';
        return;
    }
?>
    <div class="overflow-x-auto bg-gray-50 rounded-md border border-gray-200 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo smlouvy</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produkt</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pojišťovna</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sjednáno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Počátek platnosti</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specifika</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zeteo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soubor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poznámka</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vytvořeno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akce</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($smlouvy as $row): ?>
                    <tr class="hover:bg-gray-100 transition-colors"
                        data-id="<?php echo htmlspecialchars($row['id']); ?>"
                        data-klient-id="<?php echo htmlspecialchars($row['klient_id']); ?>"
                        data-cislo-smlouvy="<?php echo htmlspecialchars($row['cislo_smlouvy']); ?>"
                        data-produkt-id="<?php echo htmlspecialchars($row['produkt_id']); ?>"
                        data-pojistovna-id="<?php echo htmlspecialchars($row['pojistovna_id']); ?>"
                        data-datum-sjednani="<?php echo htmlspecialchars($row['datum_sjednani']); ?>"
                        data-datum-platnosti="<?php echo htmlspecialchars($row['datum_platnosti']); ?>"
                        data-zaznam-zeteo="<?php echo $row['zaznam_zeteo']; ?>"
                        data-poznamka="<?php echo htmlspecialchars($row['poznamka']); ?>"
                        data-cesta-k-souboru="<?php echo htmlspecialchars($row['cesta_k_souboru']); ?>"
                        data-podminky-produktu="<?php echo htmlspecialchars($row['podminky_produktu']); ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['jmeno_klienta']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['cislo_smlouvy']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nazev_produktu']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nazev_pojistovny']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['datum_sjednani']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['datum_platnosti']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php
                            if ($row['podminky_produktu']) {
                                $podminky = json_decode($row['podminky_produktu'], true);
                                if (is_array($podminky)) {
                                    foreach ($podminky as $klic => $hodnota) {
                                        echo '<strong>' . htmlspecialchars($klic) . ':</strong> ' . htmlspecialchars($hodnota) . '<br>';
                                    }
                                }
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                            <?php echo $row['zaznam_zeteo'] ? '&#x2714;' : '&#x2718;'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php if (!empty($row['cesta_k_souboru'])): ?>
                                <div class="flex flex-col">
                                    <a href="<?php echo htmlspecialchars($row['cesta_k_souboru']); ?>" target="_blank"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200 mb-1">
                                        📄 Hlavní smlouva
                                    </a>
                                    <?php
                                    // Načtení příloh
                                    $dokumentyModel = new DokumentyModel($conn);
                                    $dokumenty = $dokumentyModel->getDokumentyBySmlouva($row['id']);
                                    foreach ($dokumenty as $dokument):
                                        if ($dokument['typ_dokumentu'] !== 'Smlouva'): ?>
                                            <a href="<?php echo htmlspecialchars($dokument['cesta_k_souboru']); ?>" target="_blank"
                                                class="text-green-600 hover:text-green-800 transition-colors duration-200 text-xs">
                                                📎 <?php echo htmlspecialchars($dokument['typ_dokumentu']); ?>
                                            </a>
                                    <?php endif;
                                    endforeach; ?>
                                </div>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['poznamka']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($row['datum_vytvoreni'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="edit-btn text-indigo-600 hover:text-indigo-900 transition-colors duration-200" title="Upravit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Upravit
                            </button>
                            <form method="post" action="smlouvy.php" class="inline-block delete-form ml-2"
                                data-confirm="Opravdu chcete smazat smlouvu č. <?php echo htmlspecialchars($row['cislo_smlouvy']); ?> klienta <?php echo htmlspecialchars($row['jmeno_klienta']); ?>?">
                                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors duration-200" title="Smazat">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    Smazat
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}
?>