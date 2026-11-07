<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BazniSifrarnikRepository;
use App\Core\Controller;
use App\Repositories\AbElementiRepository;
use App\Repositories\AuditRepository;
use App\Repositories\PostavkeRepository;

class ElementiController extends Controller
{
    public function index(): void
    {
        $repo = new AbElementiRepository();
        $pretraga = trim($_GET['q'] ?? '');
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));
        $investitorId = (int) ($_GET['investitor_id'] ?? 0);
        $projekatId = (int) ($_GET['projekat_id'] ?? 0);

        $poStranici = !empty($_GET['stampaj']) || !empty($_GET['export']) ? 10000 : 20;

        $rezultat = $repo->lista($pretraga, $stranica, $poStranici, [
            'investitor_id' => $investitorId,
            'projekat_id'   => $projekatId,
        ]);

        $podaci = [
            'rezultat'      => $rezultat,
            'pretraga'      => $pretraga,
            'investitorId'  => $investitorId,
            'projekatId'    => $projekatId,
            'investitori'   => (new BazniSifrarnikRepository('investitori', ['naziv']))->opcijeZaSelect('naziv'),
            'projekti'      => (new BazniSifrarnikRepository('projekti', ['naziv']))->opcijeZaSelect('naziv'),
        ];

        if (($_GET['export'] ?? '') === 'csv') {
            $this->izvezCsv($rezultat['stavke']);
            return;
        }

        if (!empty($_GET['stampaj'])) {
            $this->renderPrint('elementi/lista', $podaci + ['stampaj' => true, 'printOrijentacija' => 'landscape']);
        } else {
            $this->render('elementi/lista', $podaci + ['stampaj' => false]);
        }
    }

    private function izvezCsv(array $redovi): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ab-elementi-' . date('Y-m-d') . '.csv"');

        $izlaz = fopen('php://output', 'w');
        fwrite($izlaz, "\xEF\xBB\xBF");
        fputcsv($izlaz, ['Šifra', 'Naziv', 'Kategorija', 'Investitor', 'Projekat', 'Opis', 'Dužina (m)', 'Širina (m)', 'Visina (m)', 'Zapremina (m³)', 'kg/kom', 'Status']);

        foreach ($redovi as $el) {
            fputcsv($izlaz, [
                $el['sifra'], $el['naziv'], $el['kategorija_naziv'] ?? '',
                $el['investitor_naziv'] ?? '', $el['projekat_naziv'] ?? '', $el['opis'] ?? '',
                $el['duzina_m'], $el['sirina_m'], $el['visina_m'],
                $el['zapremina_m3'], $el['okvirna_tezina_kg'],
                ((int) $el['aktivan'] === 1) ? 'Aktivan' : 'Neaktivan',
            ]);
        }

        fclose($izlaz);
        exit;
    }

    public function formaZaKreiranje(): void
    {
        $this->prikaziFormu(null);
    }

    public function formaZaIzmjenu(string $id): void
    {
        $element = (new AbElementiRepository())->pronadjiPoId((int) $id);
        if (!$element) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }
        $this->prikaziFormu($element);
    }

    private function prikaziFormu(?array $element): void
    {
        $kategorijeRepo = new BazniSifrarnikRepository('kategorije_elemenata', ['naziv']);
        $this->render('elementi/forma', [
            'element'            => $element,
            'kategorije'         => $kategorijeRepo->opcijeZaSelect(),
            'investitori'        => (new BazniSifrarnikRepository('investitori', ['naziv']))->opcijeZaSelect('naziv'),
            'projekti'           => (new BazniSifrarnikRepository('projekti', ['naziv']))->opcijeZaSelect('naziv'),
            'standardnaGustina'  => (new PostavkeRepository())->standardnaGustinaBetona(),
        ]);
    }

    public function sacuvajNovog(): void
    {
        [$podaci, $greske] = $this->obradiUnos($_POST, null);

        if ($greske) {
            flash('greska', implode(' ', $greske));
            sacuvaj_old($_POST);
            redirect('elementi/dodaj');
        }

        $repo = new AbElementiRepository();
        $id = $repo->kreiraj($podaci);

        (new AuditRepository())->zabiljezi(Auth::korisnik()['id'], 'kreiranje', 'ab_elementi', 'ab_elementi', $id, null, $podaci);

        ocisti_old();
        flash('uspjeh', 'AB element je kreiran.');
        redirect('elementi');
    }

    public function azurirajPostojeceg(string $id): void
    {
        $id = (int) $id;
        $repo = new AbElementiRepository();
        $postojeci = $repo->pronadjiPoId($id);
        if (!$postojeci) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        [$podaci, $greske] = $this->obradiUnos($_POST, $id);

        if ($greske) {
            flash('greska', implode(' ', $greske));
            sacuvaj_old($_POST);
            redirect('elementi/izmijeni/' . $id);
        }

        $repo->azuriraj($id, $podaci);

        (new AuditRepository())->zabiljezi(Auth::korisnik()['id'], 'izmjena', 'ab_elementi', 'ab_elementi', $id, $postojeci, $podaci);

        ocisti_old();
        flash('uspjeh', 'AB element je ažuriran.');
        redirect('elementi');
    }

    public function promijeniStatus(string $id): void
    {
        $id = (int) $id;
        $repo = new AbElementiRepository();
        $element = $repo->pronadjiPoId($id);
        if (!$element) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $noviStatus = !((bool) $element['aktivan']);
        $repo->postaviAktivnost($id, $noviStatus);

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'promjena_statusa',
            'ab_elementi',
            'ab_elementi',
            $id,
            ['aktivan' => $element['aktivan']],
            ['aktivan' => $noviStatus ? 1 : 0]
        );

        flash('uspjeh', 'Status elementa je promijenjen.');
        redirect('elementi');
    }

    /** @return array{0: array, 1: string[]} */
    private function obradiUnos(array $post, ?int $trenutniId): array
    {
        $greske = [];

        $sifra = trim($post['sifra'] ?? '');
        $naziv = trim($post['naziv'] ?? '');

        if ($sifra === '') {
            $greske[] = 'Šifra je obavezna.';
        }
        if ($naziv === '') {
            $greske[] = 'Naziv je obavezan.';
        }

        $repo = new AbElementiRepository();
        $postojeciSaSifrom = $sifra !== '' ? $repo->pronadjiPoSifri($sifra) : null;
        if ($postojeciSaSifrom && (int) $postojeciSaSifrom['id'] !== $trenutniId) {
            $greske[] = 'Šifra je već zauzeta od strane drugog elementa.';
        }

        $automatskiZapremina = !empty($post['automatski_obracun_zapremine']);
        $rucnaZapremina = (float) str_replace(',', '.', $post['rucna_zapremina_m3'] ?? '0');

        if (!$automatskiZapremina && $rucnaZapremina <= 0) {
            $greske[] = 'Kod ručnog unosa zapremine, zapremina mora biti veća od 0.';
        }

        $automatskiObracun = !empty($post['automatski_obracun_tezine']);
        $rucnaTezina = (float) str_replace(',', '.', $post['rucna_tezina_kg'] ?? '0');

        if (!$automatskiObracun && $rucnaTezina <= 0) {
            $greske[] = 'Kod ručnog unosa težine, ručna težina mora biti veća od 0.';
        }

        $podaci = [
            'sifra'                        => $sifra,
            'naziv'                        => $naziv,
            'kategorija_id'                => $post['kategorija_id'] !== '' ? (int) $post['kategorija_id'] : null,
            'investitor_id'                => $post['investitor_id'] !== '' ? (int) $post['investitor_id'] : null,
            'projekat_id'                  => $post['projekat_id'] !== '' ? (int) $post['projekat_id'] : null,
            'opis'                         => trim($post['opis'] ?? ''),
            'duzina_m'                     => (float) str_replace(',', '.', $post['duzina_m'] ?? '0'),
            'sirina_m'                     => (float) str_replace(',', '.', $post['sirina_m'] ?? '0'),
            'visina_m'                     => (float) str_replace(',', '.', $post['visina_m'] ?? '0'),
            'gustina_kg_m3'                => (float) str_replace(',', '.', $post['gustina_kg_m3'] ?? '0'),
            'automatski_obracun_zapremine' => $automatskiZapremina,
            'rucna_zapremina_m3'           => $rucnaZapremina,
            'automatski_obracun_tezine'    => $automatskiObracun,
            'rucna_tezina_kg'              => $rucnaTezina,
        ];

        return [$podaci, $greske];
    }
}
