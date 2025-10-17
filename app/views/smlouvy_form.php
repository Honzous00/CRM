<?php
function displayAddModal($klienti, $produkty, $pojistovny)
{
?>
    <!-- Modální okno pro přidání smlouvy -->
    <div id="add-modal" class="hidden fixed inset-0 z-50 overflow-auto bg-gray-800 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-auto my-12 shadow-lg">
            <div class="flex justify-between items-center pb-3">
                <h2 class="text-2xl font-semibold text-gray-800">Přidat novou smlouvu</h2>
                <button id="close-add-modal-btn" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form action="" method="post" enctype="multipart/form-data" id="add-smlouva-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="klient_id" class="block text-sm font-medium text-gray-700">Klient</label>
                        <select id="klient_id" name="klient_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <?php if (empty($klienti)): ?>
                                <option value="" disabled selected>Nejsou k dispozici žádní klienti</option>
                            <?php else: ?>
                                <?php foreach ($klienti as $klient): ?>
                                    <option value="<?php echo htmlspecialchars($klient['id']); ?>">
                                        <?php echo htmlspecialchars($klient['jmeno']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="cislo_smlouvy" class="block text-sm font-medium text-gray-700">Číslo smlouvy *</label>
                        <input type="text" id="cislo_smlouvy" name="cislo_smlouvy" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 validation-field">
                        <div id="cislo_smlouvy_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    <div class="mb-4">
                        <label for="produkt_id" class="block text-sm font-medium text-gray-700">Typ produktu</label>
                        <select id="produkt_id" name="produkt_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <?php if (empty($produkty)): ?>
                                <option value="" disabled selected>Nejsou k dispozici žádné produkty</option>
                            <?php else: ?>
                                <?php foreach ($produkty as $produkt): ?>
                                    <option value="<?php echo htmlspecialchars($produkt['id']); ?>">
                                        <?php echo htmlspecialchars($produkt['nazev']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="pojistovna_id" class="block text-sm font-medium text-gray-700">Pojišťovna/Instituce</label>
                        <select id="pojistovna_id" name="pojistovna_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <?php if (empty($pojistovny)): ?>
                                <option value="" disabled selected>Nejsou k dispozici žádné pojišťovny</option>
                            <?php else: ?>
                                <?php foreach ($pojistovny as $pojistovna): ?>
                                    <option value="<?php echo htmlspecialchars($pojistovna['id']); ?>">
                                        <?php echo htmlspecialchars($pojistovna['nazev']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="datum_sjednani" class="block text-sm font-medium text-gray-700">Datum sjednání *</label>
                        <input type="date" id="datum_sjednani" name="datum_sjednani" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 validation-field">
                        <div id="datum_sjednani_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    <div class="mb-4">
                        <label for="datum_platnosti" class="block text-sm font-medium text-gray-700">Datum počátku smlouvy *</label>
                        <input type="date" id="datum_platnosti" name="datum_platnosti" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 validation-field">
                        <div id="datum_platnosti_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <!-- Dynamicky vkládané podmínky -->
                <div id="dynamic-fields" class="mt-4 border-t pt-4 border-gray-200">
                    <!-- Životní pojištění -->
                    <div id="zivotni_pojisteni_fields" data-product-id="11" class="hidden">
                        <div id="zivotni_cpp_fields" data-pojistovna-id="2" class="hidden">
                            <div class="mb-4">
                                <label for="podtyp_cpp" class="block text-sm font-medium text-gray-700">Podtyp ČPP</label>
                                <select id="podtyp_cpp" name="podtyp_cpp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                    <option value="">Vyberte podtyp</option>
                                    <option value="RISK">RISK</option>
                                    <option value="Life">Life</option>
                                    <option value="Invest">Invest</option>
                                </select>
                            </div>
                        </div>
                        <div id="zivotni_kooperativa_fields" data-pojistovna-id="3" class="hidden">
                            <div class="mb-4">
                                <label for="podtyp_kooperativa" class="block text-sm font-medium text-gray-700">Podtyp Kooperativa</label>
                                <select id="podtyp_kooperativa" name="podtyp_kooperativa" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                    <option value="">Vyberte podtyp</option>
                                    <option value="Koop_Moznost1">Koop_Možnost1</option>
                                    <option value="Koop_Moznost2">Koop_Možnost2</option>
                                </select>
                            </div>
                        </div>
                        <div id="zivotni_allianz_fields" data-pojistovna-id="1" class="hidden">
                            <div class="mb-4">
                                <label for="podtyp_allianz" class="block text-sm font-medium text-gray-700">Podtyp Allianz</label>
                                <select id="podtyp_allianz" name="podtyp_allianz" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                    <option value="">Vyberte podtyp</option>
                                    <option value="Allianz_Moznost">Allianz_Možnost</option>
                                </select>
                            </div>
                        </div>
                        <div id="zivotni_maxima_fields" data-pojistovna-id="4" class="hidden">
                            <div class="mb-4">
                                <label for="podtyp_maxima" class="block text-sm font-medium text-gray-700">Podtyp Maxima</label>
                                <select id="podtyp_maxima" name="podtyp_maxima" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                    <option value="">Vyberte podtyp</option>
                                    <option value="Maxima_Moznost">Maxima_Možnost</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="dip" name="dip" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="dip" class="ml-2 block text-sm text-gray-900">DIP</label>
                        </div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="detske" name="detske" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="detske" class="ml-2 block text-sm text-gray-900">Dětské pojištění</label>
                        </div>
                    </div>

                    <!-- Cestovní pojištění -->
                    <div id="cestovni_pojisteni_fields" data-product-id="2" class="hidden">
                        <div class="mb-4">
                            <label for="cestovni_zacatek" class="block text-sm font-medium text-gray-700">Začátek pojištění</label>
                            <input type="date" id="cestovni_zacatek" name="cestovni_zacatek" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div class="mb-4">
                            <label for="cestovni_konec" class="block text-sm font-medium text-gray-700">Konec pojištění</label>
                            <input type="date" id="cestovni_konec" name="cestovni_konec" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                    </div>

                    <!-- Autopojištění -->
                    <div id="autopojisteni_fields" data-product-id="1" class="hidden">
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="pov" name="pov" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="pov" class="ml-2 block text-sm text-gray-900">POV</label>
                        </div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="hav" name="hav" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="hav" class="ml-2 block text-sm text-gray-900">HAV</label>
                        </div>
                        <div class="mb-4">
                            <label for="dalsi_pripojisteni" class="block text-sm font-medium text-gray-700">Další připojištění (text)</label>
                            <input type="text" id="dalsi_pripojisteni" name="dalsi_pripojisteni" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                    </div>

                    <!-- Pojištění nemovitosti -->
                    <div id="nemovitost_fields" data-product-id="8" class="hidden">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_domacnost" name="nemovitost_domacnost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_domacnost" class="ml-2 block text-sm text-gray-900">Domácnost</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_stavba" name="nemovitost_stavba" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_stavba" class="ml-2 block text-sm text-gray-900">Stavba</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_odpovednost" name="nemovitost_odpovednost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_odpovednost" class="ml-2 block text-sm text-gray-900">Odpovědnost</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_asistence" name="nemovitost_asistence" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_asistence" class="ml-2 block text-sm text-gray-900">Domácí asistence</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_nop" name="nemovitost_nop" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_nop" class="ml-2 block text-sm text-gray-900">NOP</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_asistence_plus" name="nemovitost_asistence_plus" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_asistence_plus" class="ml-2 block text-sm text-gray-900">Domácí asistence plus</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_povoden_zaplava" name="nemovitost_povoden_zaplava" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_povoden_zaplava" class="ml-2 block text-sm text-gray-900">Povodeň, záplava</label>
                            </div>
                        </div>
                        <div class="mt-4 mb-4">
                            <label for="nemovitost_nop_poznamka" class="block text-sm font-medium text-gray-700">Poznámka k NOP</label>
                            <textarea id="nemovitost_nop_poznamka" name="nemovitost_nop_poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                        </div>
                    </div>

                    <!-- Bytový dům -->
                    <div id="bytovy_fields" data-product-id="12" class="hidden">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_domacnost" name="bytovy_domacnost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_domacnost" class="ml-2 block text-sm text-gray-900">Domácnost</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_stavba" name="bytovy_stavba" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_stavba" class="ml-2 block text-sm text-gray-900">Stavba</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_odpovednost" name="bytovy_odpovednost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_odpovednost" class="ml-2 block text-sm text-gray-900">Odpovědnost</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_asistence" name="bytovy_asistence" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_asistence" class="ml-2 block text-sm text-gray-900">Domácí asistence</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_nop" name="bytovy_nop" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_nop" class="ml-2 block text-sm text-gray-900">NOP</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="bytovy_asistence_plus" name="bytovy_asistence_plus" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="bytovy_asistence_plus" class="ml-2 block text-sm text-gray-900">Domácí asistence plus</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="bytovy_povoden_zaplava" name="bytovy_povoden_zaplava" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="bytovy_povoden_zaplava" class="ml-2 block text-sm text-gray-900">Povodeň, záplava</label>
                            </div>
                        </div>
                        <div class="mt-4 mb-4">
                            <label for="edit_bytovy_nop_poznamka" class="block text-sm font-medium text-gray-700">Poznámka k NOP</label>
                            <textarea id="edit_bytovy_nop_poznamka" name="bytovy_nop_poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                        </div>
                    </div>
                </div>
                <!-- Konec dynamických podmínek -->

                <div class="mt-4 flex items-center">
                    <input type="checkbox" id="zaznam_zeteo" name="zaznam_zeteo" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="zaznam_zeteo" class="ml-2 block text-sm text-gray-900">
                        Zeteo
                    </label>
                </div>
                <div class="mb-4">
                    <label for="soubor" class="block text-sm font-medium text-gray-700">Přiložit soubor (pouze PDF)</label>
                    <input type="file" id="soubor" name="soubor" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <div class="md:col-span-2 mb-4">
                    <label for="poznamka" class="block text-sm font-medium text-gray-700">Poznámka</label>
                    <textarea id="poznamka" name="poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                </div>
                <button type="submit" id="add-submit-btn" class="w-full mt-6 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Přidat smlouvu
                </button>
            </form>
        </div>
    </div>
<?php
}

function displayEditModal($klienti, $produkty, $pojistovny)
{
?>
    <!-- Modální okno pro úpravu smlouvy -->
    <div id="edit-modal" class="hidden fixed inset-0 z-50 overflow-auto bg-gray-800 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-auto my-12 shadow-lg">
            <div class="flex justify-between items-center pb-3">
                <h2 class="text-2xl font-semibold text-gray-800">Upravit smlouvu</h2>
                <button id="close-modal-btn" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form action="" method="post" enctype="multipart/form-data" id="edit-smlouva-form">
                <input type="hidden" name="update_id" id="edit_id">
                <input type="hidden" name="stara_cesta_k_souboru" id="stara_cesta_k_souboru">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="edit_klient_id" class="block text-sm font-medium text-gray-700">Klient</label>
                        <select id="edit_klient_id" name="klient_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <?php foreach ($klienti as $klient): ?>
                                <option value="<?php echo htmlspecialchars($klient['id']); ?>"><?php echo htmlspecialchars($klient['jmeno']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit_cislo_smlouvy" class="block text-sm font-medium text-gray-700">Číslo smlouvy *</label>
                        <input type="text" id="edit_cislo_smlouvy" name="cislo_smlouvy" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 validation-field">
                        <div id="edit_cislo_smlouvy_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    <div class="mb-4">
                        <label for="edit_produkt_id" class="block text-sm font-medium text-gray-700">Typ produktu</label>
                        <select id="edit_produkt_id" name="produkt_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <?php foreach ($produkty as $produkt): ?>
                                <option value="<?php echo htmlspecialchars($produkt['id']); ?>"><?php echo htmlspecialchars($produkt['nazev']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit_pojistovna_id" class="block text-sm font-medium text-gray-700">Pojišťovna/Instituce</label>
                        <select id="edit_pojistovna_id" name="pojistovna_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <?php foreach ($pojistovny as $pojistovna): ?>
                                <option value="<?php echo htmlspecialchars($pojistovna['id']); ?>"><?php echo htmlspecialchars($pojistovna['nazev']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit_datum_sjednani" class="block text-sm font-medium text-gray-700">Datum sjednání *</label>
                        <input type="date" id="edit_datum_sjednani" name="datum_sjednani" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 validation-field">
                        <div id="edit_datum_sjednani_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    <div class="mb-4">
                        <label for="edit_datum_platnosti" class="block text-sm font-medium text-gray-700">Datum počátku smlouvy *</label>
                        <input type="date" id="edit_datum_platnosti" name="datum_platnosti" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 validation-field">
                        <div id="edit_datum_platnosti_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <!-- Dynamické podmínky pro editaci -->
                <div id="edit_dynamic-fields" class="mt-4 border-t pt-4 border-gray-200">
                    <!-- Životní pojištění -->
                    <div id="edit_zivotni_pojisteni_fields" data-product-id="11" class="hidden">
                        <div id="edit_zivotni_cpp_fields" data-pojistovna-id="2" class="hidden">
                            <div class="mb-4">
                                <label for="edit_podtyp_cpp" class="block text-sm font-medium text-gray-700">Podtyp ČPP</label>
                                <select id="edit_podtyp_cpp" name="podtyp_cpp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                    <option value="">Vyberte podtyp</option>
                                    <option value="RISK">RISK</option>
                                    <option value="Life">Life</option>
                                    <option value="Invest">Invest</option>
                                </select>
                            </div>
                        </div>
                        <div id="edit_zivotni_kooperativa_fields" data-pojistovna-id="3" class="hidden">
                            <div class="mb-4">
                                <label for="edit_podtyp_kooperativa" class="block text-sm font-medium text-gray-700">Podtyp Kooperativa</label>
                                <select id="edit_podtyp_kooperativa" name="podtyp_kooperativa" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                    <option value="">Vyberte podtyp</option>
                                    <option value="Koop_Moznost1">Koop_Možnost1</option>
                                    <option value="Koop_Moznost2">Koop_Možnost2</option>
                                </select>
                            </div>
                        </div>
                        <div id="edit_zivotni_allianz_fields" data-pojistovna-id="1" class="hidden">
                            <div class="mb-4">
                                <label for="edit_podtyp_allianz" class="block text-sm font-medium text-gray-700">Podtyp Allianz</label>
                                <select id="edit_podtyp_allianz" name="podtyp_allianz" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                    <option value="">Vyberte podtyp</option>
                                    <option value="Allianz_Moznost">Allianz_Možnost</option>
                                </select>
                            </div>
                        </div>
                        <div id="edit_zivotni_maxima_fields" data-pojistovna-id="4" class="hidden">
                            <div class="mb-4">
                                <label for="edit_podtyp_maxima" class="block text-sm font-medium text-gray-700">Podtyp Maxima</label>
                                <select id="edit_podtyp_maxima" name="podtyp_maxima" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                    <option value="">Vyberte podtyp</option>
                                    <option value="Maxima_Moznost">Maxima_Možnost</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="edit_dip" name="dip" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_dip" class="ml-2 block text-sm text-gray-900">DIP</label>
                        </div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="edit_detske" name="detske" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_detske" class="ml-2 block text-sm text-gray-900">Dětské pojištění</label>
                        </div>
                    </div>

                    <!-- Cestovní pojištění -->
                    <div id="edit_cestovni_pojisteni_fields" data-product-id="2" class="hidden">
                        <div class="mb-4">
                            <label for="edit_cestovni_zacatek" class="block text-sm font-medium text-gray-700">Začátek pojištění</label>
                            <input type="date" id="edit_cestovni_zacatek" name="cestovni_zacatek" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div class="mb-4">
                            <label for="edit_cestovni_konec" class="block text-sm font-medium text-gray-700">Konec pojištění</label>
                            <input type="date" id="edit_cestovni_konec" name="cestovni_konec" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                    </div>

                    <!-- Autopojištění -->
                    <div id="edit_autopojisteni_fields" data-product-id="1" class="hidden">
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="edit_pov" name="pov" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_pov" class="ml-2 block text-sm text-gray-900">POV</label>
                        </div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="edit_hav" name="hav" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_hav" class="ml-2 block text-sm text-gray-900">HAV</label>
                        </div>
                        <div class="mb-4">
                            <label for="edit_dalsi_pripojisteni" class="block text-sm font-medium text-gray-700">Další připojištění (text)</label>
                            <input type="text" id="edit_dalsi_pripojisteni" name="dalsi_pripojisteni" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                    </div>

                    <!-- Pojištění nemovitosti -->
                    <div id="edit_nemovitost_fields" data-product-id="8" class="hidden">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_nemovitost_domacnost" name="nemovitost_domacnost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_nemovitost_domacnost" class="ml-2 block text-sm text-gray-900">Domácnost</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_nemovitost_stavba" name="nemovitost_stavba" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_nemovitost_stavba" class="ml-2 block text-sm text-gray-900">Stavba</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_nemovitost_odpovednost" name="nemovitost_odpovednost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_nemovitost_odpovednost" class="ml-2 block text-sm text-gray-900">Odpovědnost</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_nemovitost_asistence" name="nemovitost_asistence" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_nemovitost_asistence" class="ml-2 block text-sm text-gray-900">Domácí asistence</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_nemovitost_nop" name="nemovitost_nop" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_nemovitost_nop" class="ml-2 block text-sm text-gray-900">NOP</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_asistence_plus" name="nemovitost_asistence_plus" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_asistence_plus" class="ml-2 block text-sm text-gray-900">Domácí asistence plus</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="nemovitost_povoden_zaplava" name="nemovitost_povoden_zaplava" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="nemovitost_povoden_zaplava" class="ml-2 block text-sm text-gray-900">Povodeň, záplava</label>
                            </div>
                        </div>
                        <div class="mt-4 mb-4">
                            <label for="edit_nemovitost_nop_poznamka" class="block text-sm font-medium text-gray-700">Poznámka k NOP</label>
                            <textarea id="edit_nemovitost_nop_poznamka" name="nemovitost_nop_poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                        </div>
                    </div>

                    <!-- Bytový dům -->
                    <div id="edit_bytovy_fields" data-product-id="12" class="hidden">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_domacnost" name="bytovy_domacnost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_domacnost" class="ml-2 block text-sm text-gray-900">Domácnost</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_stavba" name="bytovy_stavba" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_stavba" class="ml-2 block text-sm text-gray-900">Stavba</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_odpovednost" name="bytovy_odpovednost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_odpovednost" class="ml-2 block text-sm text-gray-900">Odpovědnost</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_asistence" name="bytovy_asistence" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_asistence" class="ml-2 block text-sm text-gray-900">Domácí asistence</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_bytovy_nop" name="bytovy_nop" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="edit_bytovy_nop" class="ml-2 block text-sm text-gray-900">NOP</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="bytovy_asistence_plus" name="bytovy_asistence_plus" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="bytovy_asistence_plus" class="ml-2 block text-sm text-gray-900">Domácí asistence plus</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="bytovy_povoden_zaplava" name="bytovy_povoden_zaplava" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="bytovy_povoden_zaplava" class="ml-2 block text-sm text-gray-900">Povodeň, záplava</label>
                            </div>
                        </div>
                        <div class="mt-4 mb-4">
                            <label for="edit_bytovy_nop_poznamka" class="block text-sm font-medium text-gray-700">Poznámka k NOP</label>
                            <textarea id="edit_bytovy_nop_poznamka" name="bytovy_nop_poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center">
                    <input type="checkbox" id="edit_zaznam_zeteo" name="zaznam_zeteo" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="edit_zaznam_zeteo" class="ml-2 block text-sm text-gray-900">Zeteo</label>
                </div>
                <div class="mb-4">
                    <label for="edit_soubor" class="block text-sm font-medium text-gray-700">Přiložit nový soubor (pouze PDF)</label>
                    <input type="file" id="edit_soubor" name="soubor" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <div class="md:col-span-2 mb-4">
                    <label for="edit_poznamka" class="block text-sm font-medium text-gray-700">Poznámka</label>
                    <textarea id="edit_poznamka" name="poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                </div>
                <button type="submit" id="edit-submit-btn" class="w-full mt-6 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Uložit změny
                </button>
            </form>
        </div>
    </div>
<?php
}
?>