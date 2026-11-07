<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Centralna numeracija dokumenata (UL-2026-000001, PREM-2026-000001, ...).
 *
 * NIKAD ne generisati broj preko SELECT MAX(id)+1 — to nije sigurno kod
 * paralelnih zahtjeva. Umjesto toga: red u brojaci_dokumenata se
 * zaključava (SELECT ... FOR UPDATE) unutar iste transakcije u kojoj se
 * kreira dokument, pa se increment i insert dokumenta commit-uju zajedno.
 *
 * VAŽNO: sljedeciBroj() MORA biti pozvan unutar transakcije koju je
 * pozivalac već otvorio (Repository koji zove ovo je odgovoran za
 * beginTransaction()/commit()/rollBack()).
 */
class NumeracijaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function sljedeciBroj(string $prefiks, int $godina, int $brojCifara = 6): string
    {
        $kljuc = $prefiks . '-' . $godina;

        // Zaključaj (ili kreiraj) red brojača unutar tekuće transakcije.
        $stmt = $this->db->prepare('SELECT zadnji_broj FROM brojaci_dokumenata WHERE kljuc = :k FOR UPDATE');
        $stmt->execute(['k' => $kljuc]);
        $red = $stmt->fetch();

        if ($red === false) {
            $this->db->prepare('INSERT INTO brojaci_dokumenata (kljuc, zadnji_broj) VALUES (:k, 0)')
                      ->execute(['k' => $kljuc]);
            $sljedeci = 1;
        } else {
            $sljedeci = (int) $red['zadnji_broj'] + 1;
        }

        $this->db->prepare('UPDATE brojaci_dokumenata SET zadnji_broj = :z WHERE kljuc = :k')
                  ->execute(['z' => $sljedeci, 'k' => $kljuc]);

        return $prefiks . '-' . $godina . '-' . str_pad((string) $sljedeci, $brojCifara, '0', STR_PAD_LEFT);
    }
}
