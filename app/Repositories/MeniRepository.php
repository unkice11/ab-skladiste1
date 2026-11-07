<?php

namespace App\Repositories;

use App\Core\Database;
use App\Core\Auth;
use PDO;

/**
 * Centralni meni sistem — sve stavke menija dolaze iz tabele meni_stavke,
 * a ne iz ručno pisanog HTML-a po view fajlovima. Nove module u
 * narednim fazama je dovoljno dodati kao red u ovoj tabeli.
 */
class MeniRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function stabloZaTrenutnogKorisnika(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM meni_stavke WHERE aktivan = 1 ORDER BY roditelj_id IS NOT NULL, redoslijed'
        );
        $sve = $stmt->fetchAll();

        $vidljive = array_filter($sve, function ($stavka) {
            if (empty($stavka['permisija_kod'])) {
                return true;
            }
            return Auth::jeVidljivo($stavka['permisija_kod']);
        });

        return $this->izgradiStablo($vidljive);
    }

    private function izgradiStablo(array $stavke, ?int $roditeljId = null): array
    {
        $grane = [];
        foreach ($stavke as $stavka) {
            if ($stavka['roditelj_id'] == $roditeljId) {
                $djeca = $this->izgradiStablo($stavke, (int) $stavka['id']);
                $stavka['djeca'] = $djeca;
                if (empty($stavka['url']) && empty($djeca)) {
                    continue;
                }
                $grane[] = $stavka;
            }
        }
        return $grane;
    }
}
