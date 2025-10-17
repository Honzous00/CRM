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

        // Zpracování souboru
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

        // Update v databázi - OPRAVA ZDE:
        $stmt_update = $this->conn->prepare("UPDATE smlouvy SET klient_id=?, cislo_smlouvy=?, produkt_id=?, pojistovna_id=?, datum_sjednani=?, datum_platnosti=?, zaznam_zeteo=?, poznamka=?, podminky_produktu=?, cesta_k_souboru=? WHERE id=?");
        $stmt_update->bind_param("isssssisssi", $klient_id, $cislo_smlouvy, $produkt_id, $pojistovna_id, $datum_sjednani, $datum_platnosti, $zaznam_zeteo, $poznamka, $json_podminky, $cesta_k_souboru, $id);

        if ($stmt_update->execute()) {
            $this->setMessage("Smlouva byla úspěšně aktualizována.", "success");
            header("Location: smlouvy.php");
            exit;
        } else {
            // ZACHYCENÍ DUPLICITNÍ CHYBY
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

        // Zpracování souboru
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

        // Vložení smlouvy do databáze - OPRAVA ZDE:
        $stmt_insert = $this->conn->prepare("INSERT INTO smlouvy (klient_id, cislo_smlouvy, produkt_id, pojistovna_id, datum_sjednani, datum_platnosti, zaznam_zeteo, poznamka, podminky_produktu, cesta_k_souboru) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("isssssisss", $klient_id, $cislo_smlouvy, $produkt_id, $pojistovna_id, $datum_sjednani, $datum_platnosti, $zaznam_zeteo, $poznamka, $json_podminky, $cesta_k_souboru);

        if ($stmt_insert->execute()) {
            $this->setMessage("Smlouva byla úspěšně přidána.", "success");
            header("Location: smlouvy.php");
            exit;
        } else {
            // ZACHYCENÍ DUPLICITNÍ CHYBY
            if ($this->conn->errno == 1062) { // MySQL error code for duplicate entry
                $this->setMessage("Chyba: Smlouva s číslem '" . $cislo_smlouvy . "' již existuje v databázi.", "error");
            } else {
                $this->setMessage("Chyba při přidávání smlouvy: " . $stmt_insert->error, "error");
            }
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
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext !== 'pdf') {
            return ['success' => false, 'error' => 'Nahráný soubor není PDF.'];
        }

        $new_file_name = uniqid('smlouva_', true) . '.pdf';
        $upload_path = __DIR__ . '/../../uploads/' . $new_file_name;

        if (move_uploaded_file($file_tmp_path, $upload_path)) {
            return ['success' => true, 'file_path' => 'uploads/' . $new_file_name];
        } else {
            return ['success' => false, 'error' => 'Chyba při nahrávání souboru.'];
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
}
