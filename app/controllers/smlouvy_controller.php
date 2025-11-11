<?php
include_once __DIR__ . '/../includes/db_connect.php';

class SmlouvyController
{
    private $conn;
    private $message = '';
    private $message_type = '';

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }
    }

    private function handlePost()
    {
        if (isset($_POST['delete_id'])) {
            $this->deleteSmlouva($_POST['delete_id']);
        } elseif (isset($_POST['update_id'])) {
            $this->updateSmlouva($_POST['update_id']);
        } else {
            $this->addSmlouva();
        }
    }

    private function deleteSmlouva($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM smlouvy WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $this->setMessage("Smlouva byla úspěšně smazána.", "success");
        } else {
            $this->setMessage("Chyba při mazání smlouvy: " . $stmt->error, "error");
        }
        $stmt->close();
    }

    private function updateSmlouva($id)
    {
        $klient_id = $this->conn->real_escape_string($_POST['klient_id']);
        $cislo_smlouvy = $this->conn->real_escape_string($_POST['cislo_smlouvy']);
        $produkt_id = $this->conn->real_escape_string($_POST['produkt_id']);
        $pojistovna_id = $this->conn->real_escape_string($_POST['pojistovna_id']);
        $datum_sjednani = $this->conn->real_escape_string($_POST['datum_sjednani']);
        $datum_platnosti = $this->conn->real_escape_string($_POST['datum_platnosti']);
        $zaznam_zeteo = isset($_POST['zaznam_zeteo']) ? 1 : 0;
        $poznamka = $this->conn->real_escape_string($_POST['poznamka']);
        $cesta_k_souboru = $_POST['stara_cesta_k_souboru'];

        // Zpracování souboru hlavní smlouvy PŘED update v databázi
        if (isset($_FILES['soubor']) && $_FILES['soubor']['error'] == UPLOAD_ERR_OK) {
            $uploadResult = $this->processFileUpload($_FILES['soubor']);
            if ($uploadResult['success']) {
                $cesta_k_souboru = $uploadResult['file_path'];
            } else {
                $this->setMessage($uploadResult['error'], "error");
                return;
            }
        }

        // Zpracování dynamických podmínek
        $podminky_produktu = $this->processDynamicConditions($produkt_id, $pojistovna_id, $_POST);
        $json_podminky = json_encode($podminky_produktu);

        // Update v databázi
        $stmt_update = $this->conn->prepare("UPDATE smlouvy SET klient_id=?, cislo_smlouvy=?, produkt_id=?, pojistovna_id=?, datum_sjednani=?, datum_platnosti=?, zaznam_zeteo=?, poznamka=?, podminky_produktu=?, cesta_k_souboru=? WHERE id=?");
        $stmt_update->bind_param("isssssisssi", $klient_id, $cislo_smlouvy, $produkt_id, $pojistovna_id, $datum_sjednani, $datum_platnosti, $zaznam_zeteo, $poznamka, $json_podminky, $cesta_k_souboru, $id);

        if ($stmt_update->execute()) {
            // ZPRACOVÁNÍ DOKUMENTŮ - Hlavní smlouva již byla zpracována (true)
            if (!$this->processDocuments($id, $_POST, $_FILES, true)) {
                return;
            }

            $this->setMessage("Smlouva byla úspěšně aktualizována.", "success");
            header("Location: smlouvy.php");
            exit;
        } else {
            if ($this->conn->errno == 1062) {
                $this->setMessage("Chyba: Smlouva s číslem '" . $cislo_smlouvy . "' již existuje v databázi.", "error");
            } else {
                $this->setMessage("Chyba při aktualizaci smlouvy: " . $stmt_update->error, "error");
            }
        }
        $stmt_update->close();
    }

    private function addSmlouva()
    {
        $klient_id = $this->conn->real_escape_string($_POST['klient_id']);
        $cislo_smlouvy = $this->conn->real_escape_string($_POST['cislo_smlouvy']);
        $produkt_id = $this->conn->real_escape_string($_POST['produkt_id']);
        $pojistovna_id = $this->conn->real_escape_string($_POST['pojistovna_id']);
        $datum_sjednani = $this->conn->real_escape_string($_POST['datum_sjednani']);
        $datum_platnosti = $this->conn->real_escape_string($_POST['datum_platnosti']);
        $zaznam_zeteo = isset($_POST['zaznam_zeteo']) ? 1 : 0;
        $poznamka = $this->conn->real_escape_string($_POST['poznamka']);
        $cesta_k_souboru = '';

        // Validace
        $validation_errors = $this->validateSmlouva($cislo_smlouvy, $datum_sjednani, $datum_platnosti);
        if (!empty($validation_errors)) {
            $this->setMessage("Chyba: " . implode(" ", $validation_errors), "error");
            return;
        }

        // Zpracování souboru hlavní smlouvy PŘED vložením do databáze
        if (isset($_FILES['soubor']) && $_FILES['soubor']['error'] == UPLOAD_ERR_OK) {
            $uploadResult = $this->processFileUpload($_FILES['soubor']);
            if ($uploadResult['success']) {
                $cesta_k_souboru = $uploadResult['file_path'];
            } else {
                $this->setMessage($uploadResult['error'], "error");
                return;
            }
        }

        // Zpracování dynamických podmínek
        $podminky_produktu = $this->processDynamicConditions($produkt_id, $pojistovna_id, $_POST);
        $json_podminky = json_encode($podminky_produktu);

        // Vložení smlouvy do databáze
        $stmt_insert = $this->conn->prepare("INSERT INTO smlouvy (klient_id, cislo_smlouvy, produkt_id, pojistovna_id, datum_sjednani, datum_platnosti, zaznam_zeteo, poznamka, podminky_produktu, cesta_k_souboru) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("isssssisss", $klient_id, $cislo_smlouvy, $produkt_id, $pojistovna_id, $datum_sjednani, $datum_platnosti, $zaznam_zeteo, $poznamka, $json_podminky, $cesta_k_souboru);

        if ($stmt_insert->execute()) {
            $smlouva_id = $stmt_insert->insert_id;

            // ZPRACOVÁNÍ DOKUMENTŮ - Hlavní smlouva již byla zpracována (true)
            if (!$this->processDocuments($smlouva_id, $_POST, $_FILES, true)) {
                return;
            };

            $this->setMessage("Smlouva byla úspěšně přidána.", "success");
            //header("Location: smlouvy.php");
            //exit;
        }
        $stmt_insert->close();
    }

    private function validateSmlouva($cislo_smlouvy, $datum_sjednani, $datum_platnosti)
    {
        $errors = [];

        if (empty($cislo_smlouvy)) {
            $errors[] = "Číslo smlouvy je povinné.";
        }
        if (empty($datum_sjednani)) {
            $errors[] = "Datum sjednání je povinné.";
        }
        if (empty($datum_platnosti)) {
            $errors[] = "Datum platnosti je povinné.";
        }

        // Kontrola duplicity - PŘIDÁNO
        if (empty($errors) && !empty($cislo_smlouvy)) {
            $stmt_check = $this->conn->prepare("SELECT id FROM smlouvy WHERE cislo_smlouvy = ?");
            $stmt_check->bind_param("s", $cislo_smlouvy);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $errors[] = "Smlouva s číslem '" . htmlspecialchars($cislo_smlouvy) . "' již existuje v databázi.";
            }
            $stmt_check->close();
        }

        return $errors;
    }

    private function processFileUpload($file)
    {
        $file_tmp_path = $file['tmp_name'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_error = $file['error'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Kontrola chyby uploadu
        if ($file_error !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'Soubor překračuje maximální povolenou velikost',
                UPLOAD_ERR_FORM_SIZE => 'Soubor překračuje maximální velikost formuláře',
                UPLOAD_ERR_PARTIAL => 'Soubor byl nahrán pouze částečně',
                UPLOAD_ERR_NO_FILE => 'Nebyl nahrán žádný soubor',
                UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasná složka',
                UPLOAD_ERR_CANT_WRITE => 'Chyba zápisu na disk',
                UPLOAD_ERR_EXTENSION => 'Nahrávání zastaveno extensioní'
            ];
            $error_message = $upload_errors[$file_error] ?? "Neznámá chyba uploadu: $file_error";
            return ['success' => false, 'error' => $error_message];
        }

        // POVOLIT KONTROLU: existence dočasného souboru
        if (!file_exists($file_tmp_path)) {
            return ['success' => false, 'error' => 'Dočasný soubor neexistuje.'];
        }

        // POVOLIT KONTROLU: zda je soubor skutečně nahraný
        if (!is_uploaded_file($file_tmp_path)) {
            return ['success' => false, 'error' => 'Soubor nebyl nahraný legitimním způsobem.'];
        }

        // Kontrola velikosti souboru (10MB)
        $max_size = 10 * 1024 * 1024;
        if ($file_size > $max_size) {
            return ['success' => false, 'error' => 'Soubor je příliš velký. Maximální velikost je 10MB.'];
        }

        // Povolené typy souborů
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_extensions)) {
            return ['success' => false, 'error' => 'Nepodporovaný typ souboru. Povolené typy: ' . implode(', ', $allowed_extensions)];
        }

        $new_file_name = uniqid('dokument_', true) . '.' . $file_ext;
        $project_root = realpath(__DIR__ . '/../../');
        $upload_dir = $project_root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $error = error_get_last();
                $error_message = $error ? $error['message'] : 'Neznámá chyba při vytváření složky';
                return ['success' => false, 'error' => 'Nelze vytvořit složku pro upload: ' . $error_message];
            }
        }

        if (!is_writable($upload_dir)) {
            return ['success' => false, 'error' => 'Složka pro upload není zapisovatelná. Zkontrolujte práva.'];
        }

        $upload_path = $upload_dir . $new_file_name;

        // VRÁTIT: Použijte move_uploaded_file místo copy
        if (move_uploaded_file($file_tmp_path, $upload_path)) {
            $relative_path = 'uploads/' . $new_file_name;
            return ['success' => true, 'file_path' => $relative_path];
        } else {
            $error = error_get_last();
            $error_message = $error ? $error['message'] : 'Neznámá chyba při přesunu souboru';
            return ['success' => false, 'error' => 'Chyba při nahrávání souboru: ' . $error_message];
        }
    }

    private function processDynamicConditions($produkt_id, $pojistovna_id, $post_data)
    {
        $podminky = [];

        switch ($produkt_id) {
            case 11: // Životní pojištění
                switch ($pojistovna_id) {
                    case 1:
                        $podminky['podtyp'] = $post_data['podtyp_allianz'] ?? '';
                        break;
                    case 2:
                        $podminky['podtyp'] = $post_data['podtyp_cpp'] ?? '';
                        break;
                    case 3:
                        $podminky['podtyp'] = $post_data['podtyp_kooperativa'] ?? '';
                        break;
                    case 4:
                        $podminky['podtyp'] = $post_data['podtyp_maxima'] ?? '';
                        break;
                }
                $podminky['dip'] = isset($post_data['dip']) ? 'Ano' : 'Ne';
                $podminky['detske'] = isset($post_data['detske']) ? 'Ano' : 'Ne';
                break;

            case 2: // Cestovní pojištění
                $podminky['zacatek'] = $post_data['cestovni_zacatek'] ?? '';
                $podminky['konec'] = $post_data['cestovni_konec'] ?? '';
                break;

            case 1: // Autopojištění
                $podminky['pov'] = isset($post_data['pov']) ? 'Ano' : 'Ne';
                $podminky['hav'] = isset($post_data['hav']) ? 'Ano' : 'Ne';
                $podminky['dalsi_pripojisteni'] = $post_data['dalsi_pripojisteni'] ?? '';
                break;

            case 8: // Pojištění nemovitosti
                $podminky['domacnost'] = isset($post_data['nemovitost_domacnost']) ? 'Ano' : 'Ne';
                $podminky['stavba'] = isset($post_data['nemovitost_stavba']) ? 'Ano' : 'Ne';
                $podminky['odpovednost'] = isset($post_data['nemovitost_odpovednost']) ? 'Ano' : 'Ne';
                $podminky['asistence'] = isset($post_data['nemovitost_asistence']) ? 'Ano' : 'Ne';
                $podminky['nop'] = isset($post_data['nemovitost_nop']) ? 'Ano' : 'Ne';
                $podminky['nop_poznamka'] = $post_data['nemovitost_nop_poznamka'] ?? '';
                break;
            case 12: // Bytový dům
                $podminky['domacnost'] = isset($post_data['bytovy_domacnost']) ? 'Ano' : 'Ne';
                $podminky['stavba'] = isset($post_data['bytovy_stavba']) ? 'Ano' : 'Ne';
                $podminky['odpovednost'] = isset($post_data['bytovy_odpovednost']) ? 'Ano' : 'Ne';
                $podminky['asistence'] = isset($post_data['bytovy_asistence']) ? 'Ano' : 'Ne';
                $podminky['nop'] = isset($post_data['bytovy_nop']) ? 'Ano' : 'Ne';
                $podminky['nop_poznamka'] = $post_data['bytovy_nop_poznamka'] ?? '';
                break;
        }

        return $podminky;
    }

    public function getKlienti()
    {
        $klienti = [];
        $sql = "SELECT id, jmeno FROM klienti ORDER BY jmeno ASC";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $klienti[] = $row;
            }
        }
        return $klienti;
    }

    public function getProdukty()
    {
        $produkty = [];
        $sql = "SELECT id, nazev FROM produkty ORDER BY nazev ASC";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $produkty[] = $row;
            }
        }
        return $produkty;
    }

    public function getPojistovny()
    {
        $pojistovny = [];
        $sql = "SELECT id, nazev FROM pojistovny ORDER BY nazev ASC";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pojistovny[] = $row;
            }
        }
        return $pojistovny;
    }

    public function getSmlouvy($search_query = '')
    {
        $smlouvy = [];
        $sql = "
            SELECT
                smlouvy.id,
                smlouvy.cislo_smlouvy,
                smlouvy.cesta_k_souboru,
                smlouvy.datum_vytvoreni,
                smlouvy.datum_sjednani,
                smlouvy.datum_platnosti,
                smlouvy.zaznam_zeteo,
                smlouvy.poznamka,
                smlouvy.podminky_produktu,
                smlouvy.klient_id,
                smlouvy.produkt_id,
                smlouvy.pojistovna_id,
                klienti.jmeno AS jmeno_klienta,
                produkty.nazev AS nazev_produktu,
                pojistovny.nazev AS nazev_pojistovny
            FROM smlouvy
            LEFT JOIN klienti ON smlouvy.klient_id = klienti.id
            LEFT JOIN produkty ON smlouvy.produkt_id = produkty.id
            LEFT JOIN pojistovny ON smlouvy.pojistovna_id = pojistovny.id
        ";

        if (!empty($search_query)) {
            $search_escaped = $this->conn->real_escape_string($search_query);
            $sql .= " WHERE 
                smlouvy.cislo_smlouvy LIKE '%$search_escaped%' OR 
                klienti.jmeno LIKE '%$search_escaped%' OR 
                produkty.nazev LIKE '%$search_escaped%' OR 
                pojistovny.nazev LIKE '%$search_escaped%' OR 
                smlouvy.poznamka LIKE '%$search_escaped%'";
        }

        $sql .= " ORDER BY smlouvy.datum_sjednani DESC";

        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $smlouvy[] = $row;
            }
        }
        return $smlouvy;
    }

    public function hasMessage()
    {
        return !empty($this->message);
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getMessageType()
    {
        return $this->message_type;
    }

    private function setMessage($message, $type)
    {
        $this->message = $message;
        $this->message_type = $type;
    }

    private function processDocuments($smlouva_id, $post_data, $files_data, $hlavni_smlouva_jiz_zpracovana = false)
    {
        $dokumentyModel = new DokumentyModel($this->conn);

        // Zpracování hlavní smlouvy - POUZE POKUD JEŠTĚ NEBYLA ZPRACOVÁNA
        if (!$hlavni_smlouva_jiz_zpracovana && isset($files_data['soubor']) && $files_data['soubor']['error'] == UPLOAD_ERR_OK) {
            $uploadResult = $this->processFileUpload($files_data['soubor']);
            if ($uploadResult['success']) {
                $dokumentyModel->pridejDokument(
                    $smlouva_id,
                    'Smlouva',
                    $files_data['soubor']['name'],
                    $uploadResult['file_path'],
                    $post_data['poznamka'] ?? ''
                );
            } else {
                $this->setMessage($uploadResult['error'], "error");
                return false;
            }
        }

        // Zpracování příloh
        if (isset($post_data['dokument_typ']) && is_array($post_data['dokument_typ'])) {

            // Zkontrolujeme, zda máme pole dokument_soubor
            if (isset($files_data['dokument_soubor']) && is_array($files_data['dokument_soubor']['name'])) {

                foreach ($post_data['dokument_typ'] as $index => $typ) {
                    $typ = trim($typ);
                    if (empty($typ)) continue;

                    $popis = $post_data['dokument_popis'][$index] ?? '';

                    // Kontrola, zda pro tento index existuje soubor
                    if (!isset($files_data['dokument_soubor']['name'][$index])) {
                        continue;
                    }

                    // Sestavení pole pro jeden soubor
                    $file_info = [
                        'name' => $files_data['dokument_soubor']['name'][$index],
                        'type' => $files_data['dokument_soubor']['type'][$index],
                        'tmp_name' => $files_data['dokument_soubor']['tmp_name'][$index],
                        'error' => $files_data['dokument_soubor']['error'][$index],
                        'size' => $files_data['dokument_soubor']['size'][$index]
                    ];

                    if ($file_info['error'] == UPLOAD_ERR_OK) {
                        $uploadResult = $this->processFileUpload($file_info);
                        if ($uploadResult['success']) {
                            $dokumentyModel->pridejDokument(
                                $smlouva_id,
                                $typ,
                                $file_info['name'],
                                $uploadResult['file_path'],
                                $popis
                            );
                        } else {
                            $this->setMessage("Chyba při nahrávání přílohy '$typ': " . $uploadResult['error'], "error");
                            return false;
                        }
                    } elseif ($file_info['error'] != UPLOAD_ERR_NO_FILE) {
                        $upload_errors = [
                            UPLOAD_ERR_INI_SIZE => 'Soubor překračuje maximální povolenou velikost',
                            UPLOAD_ERR_FORM_SIZE => 'Soubor překračuje maximální velikost formuláře',
                            UPLOAD_ERR_PARTIAL => 'Soubor byl nahrán pouze částečně',
                            UPLOAD_ERR_NO_FILE => 'Nebyl nahrán žádný soubor',
                            UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasná složka',
                            UPLOAD_ERR_CANT_WRITE => 'Chyba zápisu na disk',
                            UPLOAD_ERR_EXTENSION => 'Nahrávání zastaveno extensioní'
                        ];
                        $error_message = $upload_errors[$file_info['error']] ?? "Neznámá chyba uploadu: " . $file_info['error'];
                        $this->setMessage("Chyba při nahrávání přílohy '$typ': " . $error_message, "error");
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Reorganizuje FILES pole z PHP struktury do normální podoby
     */
    private function reorganizeFilesArray($files, $key)
    {
        $reorganized = [];
        if (!isset($files[$key])) {
            return $reorganized;
        }

        // Pokud je to normální soubor (ne pole), vrátíme jej jako jediný prvek
        if (!is_array($files[$key]['name'])) {
            return [0 => $files[$key]];
        }

        // Reorganizace pole souborů
        foreach ($files[$key]['name'] as $index => $name) {
            $reorganized[$index] = [
                'name' => $files[$key]['name'][$index],
                'type' => $files[$key]['type'][$index],
                'tmp_name' => $files[$key]['tmp_name'][$index],
                'error' => $files[$key]['error'][$index],
                'size' => $files[$key]['size'][$index]
            ];
        }

        return $reorganized;
    }
}
