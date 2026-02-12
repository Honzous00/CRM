<?php
/**
 * Model pro práci s provizemi (výpis dle smlouvy)
 */
class ProvizeModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Získání všech provizí k dané smlouvě.
     *
     * @param int $smlouva_id ID smlouvy
     * @return array Pole provizí
     */
    public function getProvizeBySmlouva($smlouva_id)
    {
        $provize = [];

        $sql = "
            SELECT *
            FROM provize
            WHERE smlouva_id = ?
            ORDER BY datum_vytvoreni DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $smlouva_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $provize[] = $row;
        }

        $stmt->close();
        return $provize;
    }

    /**
     * Získání celkové sumy provizí pro danou smlouvu.
     *
     * @param int $smlouva_id ID smlouvy
     * @return float
     */
    public function getTotalProvizeBySmlouva($smlouva_id)
    {
        $sql = "
            SELECT SUM(castka) as total
            FROM provize
            WHERE smlouva_id = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $smlouva_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();
        return $row['total'] ?? 0.0;
    }
}