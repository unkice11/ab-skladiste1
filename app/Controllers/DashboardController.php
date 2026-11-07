<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\StanjeRepository;

class DashboardController extends Controller
{
    public function index(): void
    {
        $stanjeRepo = new StanjeRepository();

        $this->render('dashboard/index', [
            'ukupnoStanje'          => $stanjeRepo->ukupnoStanje(),
            'ukupnoRezervisano'     => $stanjeRepo->ukupnoRezervisano(),
            'ulaziDanas'            => $this->brojDanas('ulazi'),
            'otpremeDanas'          => $this->brojDanas('otpreme'),
            'posljednjeAktivnosti'  => $this->posljednjeAktivnosti(),
        ]);
    }

    private function brojDanas(string $tabela): int
    {
        $db = Database::konekcija();
        $stmt = $db->prepare("SELECT COUNT(*) FROM `$tabela` WHERE datum = CURDATE()");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Spaja zadnjih nekoliko ulaza, otprema i premještanja u jednu
     * hronološku listu (spec sekcija 13: "Posljednje aktivnosti").
     */
    private function posljednjeAktivnosti(): array
    {
        $db = Database::konekcija();

        $ulazi = $db->query(
            "SELECT 'ulaz' AS tip, broj_ulaza AS broj, datum_kreiranja, id
             FROM ulazi WHERE status = 'aktivan' ORDER BY id DESC LIMIT 5"
        )->fetchAll();

        $otpreme = $db->query(
            "SELECT 'otprema' AS tip, broj_otpremnice AS broj, datum_kreiranja, id
             FROM otpreme ORDER BY id DESC LIMIT 5"
        )->fetchAll();

        $premjestanja = $db->query(
            "SELECT 'premjestanje' AS tip, broj_premjestanja AS broj, datum_kreiranja, id
             FROM premjestanja ORDER BY id DESC LIMIT 5"
        )->fetchAll();

        $sve = array_merge($ulazi, $otpreme, $premjestanja);
        usort($sve, fn($a, $b) => strtotime($b['datum_kreiranja']) <=> strtotime($a['datum_kreiranja']));

        return array_slice($sve, 0, 8);
    }
}
