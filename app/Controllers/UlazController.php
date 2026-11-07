<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BazniSifrarnikRepository;
use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\UlazRepository;
use RuntimeException;

class UlazController extends Controller
{
    public function index(): void
    {
        $pretraga = trim($_GET['q'] ?? '');
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));

        $this->render('ulaz/lista', [
            'rezultat' => (new UlazRepository())->lista($pretraga, $stranica, 20),
            'pretraga' => $pretraga,
        ]);
    }

    public function pregled(string $id): void
    {
        $ulaz = (new UlazRepository())->pronadjiPoId((int) $id);
        if (!$ulaz) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }
        $this->render('ulaz/pregled', ['ulaz' => $ulaz]);
    }

    public function storniraj(string $id): void
    {
        $id = (int) $id;
        $razlog = trim($_POST['razlog_storna'] ?? '');

        if ($razlog === '') {
            flash('greska', 'Razlog storniranja je obavezan.');
            redirect('ulaz/' . $id);
        }

        try {
            (new UlazRepository())->storniraj($id, $razlog, Auth::korisnik()['id']);
        } catch (RuntimeException $e) {
            flash('greska', $e->getMessage());
            redirect('ulaz/' . $id);
        }

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'storniranje',
            'ulaz',
            'ulazi',
            $id,
            null,
            ['razlog_storna' => $razlog]
        );

        flash('uspjeh', 'Ulaz je storniran.');
        redirect('ulaz/' . $id);
    }

    public function formaZaKreiranje(): void
    {
        $this->render('ulaz/forma', $this->podaciZaFormu());
    }

    public function sacuvaj(): void
    {
        $greske = [];

        $datum = $_POST['datum'] ?? '';
        $skladisteId = (int) ($_POST['skladiste_id'] ?? 0);

        if ($datum === '') {
            $greske[] = 'Datum je obavezan.';
        }
        if ($skladisteId <= 0) {
            $greske[] = 'Skladište je obavezno.';
        }

        $stavke = [];
        foreach (($_POST['stavke'] ?? []) as $red) {
            $elementId = (int) ($red['element_id'] ?? 0);
            $lokacijaId = (int) ($red['lokacija_id'] ?? 0);
            $kolicina = (float) str_replace(',', '.', $red['kolicina'] ?? '0');

            if ($elementId <= 0 || $lokacijaId <= 0 || $kolicina <= 0) {
                continue; // prazan/nepopunjen red se tiho preskače
            }
            $stavke[] = ['element_id' => $elementId, 'lokacija_id' => $lokacijaId, 'kolicina' => $kolicina];
        }

        if (!$stavke) {
            $greske[] = 'Dokument mora imati najmanje jednu stavku sa elementom, lokacijom i količinom.';
        }

        if ($greske) {
            flash('greska', implode(' ', $greske));
            redirect('ulaz/dodaj');
        }

        try {
            $rezultat = (new UlazRepository())->kreiraj([
                'datum'         => $datum,
                'skladiste_id'  => $skladisteId,
                'investitor_id' => (int) ($_POST['investitor_id'] ?? 0) ?: null,
                'projekat_id'   => (int) ($_POST['projekat_id'] ?? 0) ?: null,
                'objekat_id'    => (int) ($_POST['objekat_id'] ?? 0) ?: null,
                'napomena'      => trim($_POST['napomena'] ?? ''),
                'korisnik_id'   => Auth::korisnik()['id'],
            ], $stavke);
        } catch (RuntimeException $e) {
            flash('greska', $e->getMessage());
            redirect('ulaz/dodaj');
        }

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'kreiranje',
            'ulaz',
            'ulazi',
            $rezultat['id'],
            null,
            ['broj_ulaza' => $rezultat['broj_ulaza'], 'broj_stavki' => count($stavke)]
        );

        flash('uspjeh', 'Ulaz ' . $rezultat['broj_ulaza'] . ' je uspješno kreiran.');
        redirect('ulaz');
    }

    private function podaciZaFormu(): array
    {
        return [
            'elementi'    => elementi_za_select(),
            'lokacije'    => lokacije_sa_skladistem(),
            'skladista'   => (new BazniSifrarnikRepository('skladista', ['naziv']))->opcijeZaSelect('naziv'),
            'investitori' => (new BazniSifrarnikRepository('investitori', ['naziv']))->opcijeZaSelect('naziv'),
            'projekti'    => (new BazniSifrarnikRepository('projekti', ['naziv']))->opcijeZaSelect('naziv'),
            'objekti'     => (new BazniSifrarnikRepository('objekti', ['naziv']))->opcijeZaSelect('naziv'),
        ];
    }
}
