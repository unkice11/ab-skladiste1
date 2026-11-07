<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;
use App\Repositories\AuditRepository;
use App\Repositories\PermisijaRepository;

class RoleController extends BazniSifrarnikController
{
    public function __construct()
    {
        $this->repo = new BazniSifrarnikRepository('role', ['naziv', 'opis']);
        $this->nazivModula = 'Uloge';
        $this->rutaBazna = 'role';
        $this->permisijaPrefix = 'role';
        $this->auditModul = 'role';

        $this->poljaForme = [
            ['ime' => 'naziv', 'oznaka' => 'Naziv uloge', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'opis', 'oznaka' => 'Opis', 'tip' => 'textarea'],
        ];

        $this->koloneListe = [
            ['kljuc' => 'naziv', 'oznaka' => 'Naziv'],
            ['kljuc' => 'opis', 'oznaka' => 'Opis'],
        ];
    }

    public function index(): void
    {
        $pretraga = trim($_GET['q'] ?? '');
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));
        $rezultat = $this->repo->lista($pretraga, $stranica, 20);

        $this->render('sifrarnik/lista', [
            'rezultat'        => $rezultat,
            'pretraga'        => $pretraga,
            'koloneListe'     => $this->koloneListe,
            'nazivModula'     => $this->nazivModula,
            'rutaBazna'       => $this->rutaBazna,
            'permisijaPrefix' => $this->permisijaPrefix,
            'dodatneAkcije'   => [['ruta' => 'role/permisije', 'oznaka' => 'Permisije']],
        ]);
    }

    public function prikaziPermisije(string $id): void
    {
        $id = (int) $id;
        $rola = $this->repo->pronadjiPoId($id);
        if (!$rola) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $permisije = (new PermisijaRepository())->permisijeZaEkranRole($id);

        $this->render('role/permisije', [
            'rola'      => $rola,
            'permisije' => $permisije,
        ]);
    }

    public function sacuvajPermisije(string $id): void
    {
        $id = (int) $id;
        if (!$this->repo->pronadjiPoId($id)) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $unosi = [];
        $vidljivi = $_POST['vidljiv'] ?? [];
        $pristupi = $_POST['pristup'] ?? [];

        foreach (($_POST['obradjene'] ?? []) as $permisijaId) {
            $permisijaId = (int) $permisijaId;
            $unosi[$permisijaId] = [
                'vidljiv' => isset($vidljivi[$permisijaId]),
                'pristup' => isset($pristupi[$permisijaId]),
            ];
        }

        (new PermisijaRepository())->sacuvajZaRole($id, $unosi);

        (new AuditRepository())->zabiljezi(Auth::korisnik()['id'], 'izmjena_permisija', 'role', 'role', $id);

        flash('uspjeh', 'Permisije uloge su sačuvane. Odmah važe za sve korisnike sa ovom ulogom (koji nemaju individualni izuzetak).');
        redirect('role/permisije/' . $id);
    }
}
