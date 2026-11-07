<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\KorisnikRepository;
use App\Repositories\PermisijaRepository;

class KorisniciController extends Controller
{
    public function index(): void
    {
        $repo = new KorisnikRepository();
        $pretraga = trim($_GET['q'] ?? '');
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));

        $rezultat = $repo->lista($pretraga, $stranica, 20);

        $this->render('korisnici/lista', [
            'rezultat' => $rezultat,
            'pretraga' => $pretraga,
        ]);
    }

    public function formaZaKreiranje(): void
    {
        $repo = new KorisnikRepository();
        $this->render('korisnici/forma', [
            'korisnik' => null,
            'role'     => $repo->sveRole(),
        ]);
    }

    public function sacuvajNovog(): void
    {
        $greske = $this->validiraj($_POST, true);

        if ($greske) {
            flash('greska', implode(' ', $greske));
            sacuvaj_old($_POST);
            redirect('korisnici/dodaj');
        }

        $repo = new KorisnikRepository();

        if ($repo->pronadjiPoKorisnickomImenu($_POST['korisnicko_ime'])) {
            flash('greska', 'Korisničko ime je već zauzeto.');
            sacuvaj_old($_POST);
            redirect('korisnici/dodaj');
        }

        $id = $repo->kreiraj([
            'ime'            => trim($_POST['ime']),
            'prezime'        => trim($_POST['prezime']),
            'korisnicko_ime' => trim($_POST['korisnicko_ime']),
            'email'          => trim($_POST['email']),
            'lozinka'        => $_POST['lozinka'],
            'role_id'        => $_POST['role_id'] ?: null,
            'status'         => $_POST['status'] ?? 'aktivan',
        ]);

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'kreiranje',
            'korisnici',
            'korisnici',
            $id,
            null,
            ['korisnicko_ime' => $_POST['korisnicko_ime']]
        );

        ocisti_old();
        flash('uspjeh', 'Korisnik je uspješno kreiran.');
        redirect('korisnici');
    }

    public function formaZaIzmjenu(string $id): void
    {
        $repo = new KorisnikRepository();
        $korisnik = $repo->pronadjiPoId((int) $id);
        if (!$korisnik) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $this->render('korisnici/forma', [
            'korisnik' => $korisnik,
            'role'     => $repo->sveRole(),
        ]);
    }

    public function azurirajPostojeceg(string $id): void
    {
        $id = (int) $id;
        $greske = $this->validiraj($_POST, false);

        if ($greske) {
            flash('greska', implode(' ', $greske));
            sacuvaj_old($_POST);
            redirect('korisnici/izmijeni/' . $id);
        }

        $repo = new KorisnikRepository();
        $postojeci = $repo->pronadjiPoId($id);
        if (!$postojeci) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $sukob = $repo->pronadjiPoKorisnickomImenu(trim($_POST['korisnicko_ime']));
        if ($sukob && (int) $sukob['id'] !== $id) {
            flash('greska', 'Korisničko ime je već zauzeto od strane drugog korisnika.');
            sacuvaj_old($_POST);
            redirect('korisnici/izmijeni/' . $id);
        }

        $noviPodaci = [
            'ime'            => trim($_POST['ime']),
            'prezime'        => trim($_POST['prezime']),
            'korisnicko_ime' => trim($_POST['korisnicko_ime']),
            'email'          => trim($_POST['email']),
            'role_id'        => $_POST['role_id'] ?: null,
            'status'         => $_POST['status'] ?? 'aktivan',
        ];

        $repo->azuriraj($id, $noviPodaci);

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'izmjena',
            'korisnici',
            'korisnici',
            $id,
            $postojeci,
            $noviPodaci
        );

        ocisti_old();
        flash('uspjeh', 'Podaci korisnika su ažurirani.');
        redirect('korisnici');
    }

    public function promijeniStatus(string $id): void
    {
        $id = (int) $id;
        $repo = new KorisnikRepository();
        $korisnik = $repo->pronadjiPoId($id);
        if (!$korisnik) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        if ((int) $korisnik['id'] === (int) Auth::korisnik()['id']) {
            flash('greska', 'Ne možete deaktivirati vlastiti nalog.');
            redirect('korisnici');
        }

        $noviStatus = $korisnik['status'] === 'aktivan' ? 'neaktivan' : 'aktivan';
        $repo->postaviStatus($id, $noviStatus);

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'promjena_statusa',
            'korisnici',
            'korisnici',
            $id,
            ['status' => $korisnik['status']],
            ['status' => $noviStatus]
        );

        flash('uspjeh', 'Status korisnika je promijenjen.');
        redirect('korisnici');
    }

    public function resetujLozinku(string $id): void
    {
        $id = (int) $id;
        $novaLozinka = $_POST['nova_lozinka'] ?? '';

        if (mb_strlen($novaLozinka) < 8) {
            flash('greska', 'Nova lozinka mora imati najmanje 8 karaktera.');
            redirect('korisnici/izmijeni/' . $id);
        }

        $repo = new KorisnikRepository();
        if (!$repo->pronadjiPoId($id)) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $repo->postaviLozinku($id, $novaLozinka);

        (new AuditRepository())->zabiljezi(Auth::korisnik()['id'], 'reset_lozinke', 'korisnici', 'korisnici', $id);

        flash('uspjeh', 'Lozinka je resetovana.');
        redirect('korisnici/izmijeni/' . $id);
    }

    public function prikaziPermisije(string $id): void
    {
        $id = (int) $id;
        $repo = new KorisnikRepository();
        $korisnik = $repo->pronadjiPoId($id);
        if (!$korisnik) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $permisije = (new PermisijaRepository())->permisijeZaEkran($id);

        $this->render('korisnici/permisije', [
            'korisnik'  => $korisnik,
            'permisije' => $permisije,
        ]);
    }

    public function sacuvajPermisije(string $id): void
    {
        $id = (int) $id;
        $repo = new KorisnikRepository();
        if (!$repo->pronadjiPoId($id)) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $unosi = [];
        $vidljivi = $_POST['vidljiv'] ?? [];
        $pristupi = $_POST['pristup'] ?? [];
        $obradjene = $_POST['obradjene'] ?? [];

        // Iteriramo kroz SVE permisije prikazane na formi (ne samo čekirane) —
        // neoznačen checkbox jednostavno izostane iz POST-a, pa bez ovoga ne
        // bismo mogli oduzeti permisiju koju korisnik ima preko role.
        foreach ($obradjene as $permisijaId) {
            $permisijaId = (int) $permisijaId;
            $unosi[$permisijaId] = [
                'vidljiv' => isset($vidljivi[$permisijaId]),
                'pristup' => isset($pristupi[$permisijaId]),
            ];
        }

        (new PermisijaRepository())->sacuvajOverrideZaKorisnika($id, $unosi);

        (new AuditRepository())->zabiljezi(Auth::korisnik()['id'], 'izmjena_permisija', 'korisnici', 'korisnici', $id);

        flash('uspjeh', 'Permisije korisnika su sačuvane.');
        redirect('korisnici/permisije/' . $id);
    }

    private function validiraj(array $podaci, bool $zahtijevaLozinku): array
    {
        $greske = [];

        if (trim($podaci['ime'] ?? '') === '') {
            $greske[] = 'Ime je obavezno.';
        }
        if (trim($podaci['prezime'] ?? '') === '') {
            $greske[] = 'Prezime je obavezno.';
        }
        if (trim($podaci['korisnicko_ime'] ?? '') === '') {
            $greske[] = 'Korisničko ime je obavezno.';
        }
        if (!filter_var($podaci['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $greske[] = 'Unesite ispravnu e-mail adresu.';
        }
        if ($zahtijevaLozinku && mb_strlen($podaci['lozinka'] ?? '') < 8) {
            $greske[] = 'Lozinka mora imati najmanje 8 karaktera.';
        }

        return $greske;
    }
}
