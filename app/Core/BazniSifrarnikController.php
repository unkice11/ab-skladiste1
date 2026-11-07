<?php

namespace App\Core;

use App\Repositories\AuditRepository;

/**
 * Generički kontroler za jednostavne šifrarnike. Konkretan šifrarnik
 * (npr. InvestitoriController) samo nasljeđuje ovu klasu i u konstruktoru
 * postavlja konfiguraciju (tabela, polja forme, kolone liste, permisije).
 * Time se izbjegava skoro identičan CRUD kod ponavljan devet puta.
 *
 * Permisije: očekuje se par "<prefix>.view" i "<prefix>.uredi" (uredi
 * pokriva kreiranje/izmjenu/aktivaciju-deaktivaciju — za šifrarnike spec
 * ne traži razdvojenu create/edit/delete granularnost kao za skladišne
 * i otpremne module).
 */
abstract class BazniSifrarnikController extends Controller
{
    protected BazniSifrarnikRepository $repo;
    protected string $nazivModula;       // npr. "Investitori"
    protected string $rutaBazna;         // npr. "investitori"
    protected string $permisijaPrefix;   // npr. "investitori"
    protected string $auditModul;        // npr. "investitori"

    /** @var array<int, array{ime:string,oznaka:string,tip:string,obavezno?:bool,opcije?:array}> */
    protected array $poljaForme = [];

    /** @var array<int, array{kljuc:string,oznaka:string}> */
    protected array $koloneListe = [];

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
        ]);
    }

    public function formaZaKreiranje(): void
    {
        $this->render('sifrarnik/forma', [
            'zapis'           => null,
            'polja'           => $this->poljaForme(),
            'nazivModula'     => $this->nazivModula,
            'rutaBazna'       => $this->rutaBazna,
            'permisijaPrefix' => $this->permisijaPrefix,
        ]);
    }

    public function sacuvajNovog(): void
    {
        [$podaci, $greske] = $this->obradiUnos($_POST);

        if ($greske) {
            flash('greska', implode(' ', $greske));
            sacuvaj_old($_POST);
            redirect($this->rutaBazna . '/dodaj');
        }

        $id = $this->repo->kreiraj($podaci + ['aktivan' => 1]);

        (new AuditRepository())->zabiljezi(
            trenutni_korisnik()['id'],
            'kreiranje',
            $this->auditModul,
            $this->repo->imeTabele(),
            $id,
            null,
            $podaci
        );

        ocisti_old();
        flash('uspjeh', $this->nazivModula . ': zapis je kreiran.');
        redirect($this->rutaBazna);
    }

    public function formaZaIzmjenu(string $id): void
    {
        $zapis = $this->repo->pronadjiPoId((int) $id);
        if (!$zapis) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $this->render('sifrarnik/forma', [
            'zapis'           => $zapis,
            'polja'           => $this->poljaForme(),
            'nazivModula'     => $this->nazivModula,
            'rutaBazna'       => $this->rutaBazna,
            'permisijaPrefix' => $this->permisijaPrefix,
        ]);
    }

    public function azurirajPostojeceg(string $id): void
    {
        $id = (int) $id;
        $postojeci = $this->repo->pronadjiPoId($id);
        if (!$postojeci) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        [$podaci, $greske] = $this->obradiUnos($_POST);

        if ($greske) {
            flash('greska', implode(' ', $greske));
            sacuvaj_old($_POST);
            redirect($this->rutaBazna . '/izmijeni/' . $id);
        }

        $this->repo->azuriraj($id, $podaci);

        (new AuditRepository())->zabiljezi(
            trenutni_korisnik()['id'],
            'izmjena',
            $this->auditModul,
            $this->repo->imeTabele(),
            $id,
            $postojeci,
            $podaci
        );

        ocisti_old();
        flash('uspjeh', $this->nazivModula . ': zapis je ažuriran.');
        redirect($this->rutaBazna);
    }

    public function promijeniStatus(string $id): void
    {
        $id = (int) $id;
        $zapis = $this->repo->pronadjiPoId($id);
        if (!$zapis) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $noviStatus = !((bool) $zapis['aktivan']);
        $this->repo->postaviAktivnost($id, $noviStatus);

        (new AuditRepository())->zabiljezi(
            trenutni_korisnik()['id'],
            'promjena_statusa',
            $this->auditModul,
            $this->repo->imeTabele(),
            $id,
            ['aktivan' => $zapis['aktivan']],
            ['aktivan' => $noviStatus ? 1 : 0]
        );

        flash('uspjeh', 'Status je promijenjen.');
        redirect($this->rutaBazna);
    }

    /** Popunjava opcije za select polja neposredno prije renderovanja forme (FK repozitoriji su dinamični). */
    private function poljaForme(): array
    {
        $polja = $this->poljaForme;
        foreach ($polja as &$p) {
            if ($p['tip'] === 'select' && isset($p['opcije_repo']) && $p['opcije_repo'] instanceof BazniSifrarnikRepository) {
                $p['opcije'] = $p['opcije_repo']->opcijeZaSelect($p['opcije_kolona_naziva'] ?? 'naziv');
            }
        }
        return $polja;
    }

    /** @return array{0: array, 1: string[]} [obrađeni podaci za bazu, poruke grešaka] */
    protected function obradiUnos(array $post): array
    {
        $podaci = [];
        $greske = [];

        foreach ($this->poljaForme as $p) {
            $ime = $p['ime'];
            $vrijednost = $post[$ime] ?? null;

            if (!empty($p['obavezno']) && trim((string) $vrijednost) === '') {
                $greske[] = ($p['oznaka'] ?? $ime) . ' je obavezno polje.';
            }

            switch ($p['tip']) {
                case 'number':
                    $podaci[$ime] = $vrijednost === '' || $vrijednost === null ? 0 : (float) str_replace(',', '.', $vrijednost);
                    break;
                case 'checkbox':
                    $podaci[$ime] = !empty($vrijednost) ? 1 : 0;
                    break;
                case 'select':
                    $podaci[$ime] = $vrijednost !== '' && $vrijednost !== null ? (int) $vrijednost : null;
                    break;
                case 'date':
                    $podaci[$ime] = $vrijednost !== '' ? $vrijednost : null;
                    break;
                default:
                    $podaci[$ime] = trim((string) $vrijednost);
            }
        }

        // Status (aktivan/neaktivan) se uređuje isključivo kroz zasebnu
        // akciju (promijeniStatus) — ova metoda ga namjerno ne dira, kako
        // izmjena podataka ne bi slučajno "reaktivirala" deaktiviran zapis.
        return [$podaci, $greske];
    }
}
