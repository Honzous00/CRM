<?php
/**
 * Model pro práci se smlouvami (detail, vyhledávání, apod.)
 */
class SmlouvyModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Získání detailu konkrétní smlouvy včetně názvů produktu a pojišťovny.
     *
     * @param int $id ID smlouvy
     * @return array|null Asociativní pole s daty smlouvy nebo null
     */
    public function getSmlouvaById($id)
    {
        $sql = "
            SELECT
                s.*,
                k.jmeno AS jmeno_klienta,
                p.nazev AS nazev_produktu,
                poj.nazev AS nazev_pojistovny
            FROM smlouvy s
            LEFT JOIN klienti k ON s.klient_id = k.id
            LEFT JOIN produkty p ON s.produkt_id = p.id
            LEFT JOIN pojistovny poj ON s.pojistovna_id = poj.id
            WHERE s.id = ?
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $smlouva = $result->fetch_assoc();

        // Pokud je sloupec podminky_produktu uložen jako JSON, převedeme ho na pole
        if (!empty($smlouva['podminky_produktu'])) {
            $smlouva['podminky_produktu'] = json_decode($smlouva['podminky_produktu'], true);
        } else {
            $smlouva['podminky_produktu'] = [];
        }

        $stmt->close();
        return $smlouva;
    }
}