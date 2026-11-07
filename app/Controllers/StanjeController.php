<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AbElementiRepository;
use App\Repositories\SkladisnaKarticaRepository;
use App\Repositories\StanjeRepository;

class StanjeController extends Controller
{
    public function index(): void
    {
        $pretraga = trim($_GET['q'] ?? '');
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));

        $this->render('stanje/lista', [
            'rezultat' => (new StanjeRepository())->pregled($pretraga, $stranica, 20),
            'pretraga' => $pretraga,
        ]);
    }

    public function karticaElementa(string $elementId): void
    {
        $elementId = (int) $elementId;
        $element = (new AbElementiRepository())->pronadjiPoId($elementId);
        if (!$element) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));

        $this->render('stanje/kartica', [
            'element'  => $element,
            'rezultat' => (new SkladisnaKarticaRepository())->istorijaZaElement($elementId, $stranica, 50),
        ]);
    }
}
