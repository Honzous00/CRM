<?php
class DokumentyModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getDokumentyBySmlouva($smlouva_id)
    {
        $dokumenty = [];
        $stmt = $this->conn->prepare("SELECT * FROM dokumenty WHERE smlouva_id = ? ORDER BY typ_dokumentu, datum_vytvoreni");
        $stmt->bind_param("i", $smlouva_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $dokumenty[] = $row;
        }

        $stmt->close();
        return $dokumenty;
    }

    public function getTypyDokumentu()
    {
        $typy = [];
        $sql = "SELECT typ FROM dokument_typy ORDER BY pocet_pouziti DESC, datum_posledniho_pouziti DESC LIMIT 10";
        $result = $this->conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            $typy[] = $row['typ'];
        }

        return $typy;
    }

    public function pridejDokument($smlouva_id, $typ_dokumentu, $nazev_souboru, $cesta_k_souboru, $poznamka = '')
    {
        // Aktualizace počtu použití typu dokumentu
        $this->aktualizujTypDokumentu($typ_dokumentu);

        $stmt = $this->conn->prepare("INSERT INTO dokumenty (smlouva_id, typ_dokumentu, nazev_souboru, cesta_k_souboru, poznamka) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $smlouva_id, $typ_dokumentu, $nazev_souboru, $cesta_k_souboru, $poznamka);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    private function aktualizujTypDokumentu($typ)
    {
        // Kontrola, zda typ již existuje
        $stmt = $this->conn->prepare("SELECT id FROM dokument_typy WHERE typ = ?");
        $stmt->bind_param("s", $typ);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Aktualizace existujícího typu
            $stmt = $this->conn->prepare("UPDATE dokument_typy SET pocet_pouziti = pocet_pouziti + 1, datum_posledniho_pouziti = NOW() WHERE typ = ?");
            $stmt->bind_param("s", $typ);
        } else {
            // Vložení nového typu
            $stmt = $this->conn->prepare("INSERT INTO dokument_typy (typ) VALUES (?)");
            $stmt->bind_param("s", $typ);
        }

        $stmt->execute();
        $stmt->close();
    }

    public function smazDokument($dokument_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM dokumenty WHERE id = ?");
        $stmt->bind_param("i", $dokument_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}
