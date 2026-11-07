<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class PostavkeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function get(string $kljuc, $default = null)
    {
        $stmt = $this->db->prepare('SELECT vrijednost FROM postavke_sistema WHERE kljuc = :k');
        $stmt->execute(['k' => $kljuc]);
        $vrijednost = $stmt->fetchColumn();
        return $vrijednost !== false ? $vrijednost : $default;
    }

    public function set(string $kljuc, string $vrijednost): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO postavke_sistema (kljuc, vrijednost) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE vrijednost = :v2'
        );
        $stmt->execute(['k' => $kljuc, 'v' => $vrijednost, 'v2' => $vrijednost]);
    }

    public function standardnaGustinaBetona(): float
    {
        return (float) $this->get('standardna_gustina_betona_kg_m3', 2500);
    }
}
