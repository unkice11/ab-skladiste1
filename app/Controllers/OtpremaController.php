<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BazniSifrarnikRepository;
use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\OtpremaRepository;
use RuntimeException;

class OtpremaController extends Controller
{
    public function index(): void
    {
        $pretraga = trim($_GET['q'] ?? '');
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));

        $this->render('otprema/lista', [
            'rezultat' => (new OtpremaRepository())->lista($pretraga, $stranica, 20),
            'pretraga' => $pretraga,
        ]);
    }

    public function pregled(string $id): void
    {
        $otprema = (new OtpremaRepository())->pronadjiPoId((int) $id);
        if (!$otprema) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }
        $this->render('otprema/pregled', ['otprema' => $otprema]);
    }

    public function storniraj(string $id): void
    {
        $id = (int) $id;
        $razlog = trim($_POST['razlog_storna'] ?? '');

        if ($razlog === '') {
            flash('greska', 'Razlog storniranja je obavezan.');
            redirect('otprema/' . $id);
        }

        try {
            (new OtpremaRepository())->storniraj($id, $razlog, Auth::korisnik()['id']);
        } catch (RuntimeException $e) {
            flash('greska', $e->getMessage());
            redirect('otprema/' . $id);
        }

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'storniranje',
            'otprema',
            'otpreme',
            $id,
            null,
            ['razlog_storna' => $razlog]
        );

        flash('uspjeh', 'Otprema je stornirana.');
        redirect('otprema/' . $id);
    }

    /** Otpremnica za štampu — bez sidebar-a, sa zaglavljem firme. */
    public function otpremnica(string $id): void
    {
        $otprema = (new OtpremaRepository())->pronadjiPoId((int) $id);
        if (!$otprema) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }
        $this->renderPrint('otprema/otpremnica', ['otprema' => $otprema]);
    }

    public function formaZaKreiranje(): void
    {
        $this->render('otprema/forma', $this->podaciZaFormu());
    }

    public function sacuvaj(): void
    {
        $greske = [];

        $datum = $_POST['datum'] ?? '';
        if ($datum === '') {
            $greske[] = 'Datum je obavezan.';
        }

        $stavke = [];
        foreach (($_POST['stavke'] ?? []) as $red) {
            $elementId = (int) ($red['element_id'] ?? 0);
            $lokacijaId = (int) ($red['lokacija_id'] ?? 0);
            $kolicina = (float) str_replace(',', '.', $red['kolicina'] ?? '0');
            $rezervacijaId = (int) ($red['rezervacija_id'] ?? 0) ?: null;

            if ($elementId <= 0 || $lokacijaId <= 0 || $kolicina <= 0) {
                continue;
            }
            $stavke[] = [
                'element_id'     => $elementId,
                'lokacija_id'    => $lokacijaId,
                'kolicina'       => $kolicina,
                'rezervacija_id' => $rezervacijaId,
            ];
        }

        if (!$stavke) {
            $greske[] = 'Otprema mora imati najmanje jednu stavku sa elementom, lokacijom i količinom.';
        }

        if ($greske) {
            flash('greska', implode(' ', $greske));
            redirect('otprema/dodaj');
        }

        try {
            $rezultat = (new OtpremaRepository())->kreiraj([
                'datum'         => $datum,
                'investitor_id' => (int) ($_POST['investitor_id'] ?? 0) ?: null,
                'projekat_id'   => (int) ($_POST['projekat_id'] ?? 0) ?: null,
                'objekat_id'    => (int) ($_POST['objekat_id'] ?? 0) ?: null,
                'prevoznik_id'  => (int) ($_POST['prevoznik_id'] ?? 0) ?: null,
                'vozilo_id'     => (int) ($_POST['vozilo_id'] ?? 0) ?: null,
                'vozac_id'      => (int) ($_POST['vozac_id'] ?? 0) ?: null,
                'napomena'      => trim($_POST['napomena'] ?? ''),
                'korisnik_id'   => Auth::korisnik()['id'],
            ], $stavke);
        } catch (RuntimeException $e) {
            flash('greska', $e->getMessage());
            redirect('otprema/dodaj');
        }

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'kreiranje',
            'otprema',
            'otpreme',
            $rezultat['id'],
            null,
            ['broj_otpremnice' => $rezultat['broj_otpremnice'], 'broj_stavki' => count($stavke)]
        );

        if ($rezultat['upozorenje_nosivost']) {
            flash('greska', 'Otprema ' . $rezultat['broj_otpremnice'] . ' je kreirana, ALI: ' . $rezultat['upozorenje_nosivost']);
        } else {
            flash('uspjeh', 'Otprema ' . $rezultat['broj_otpremnice'] . ' je uspješno kreirana.');
        }

        redirect('otprema/' . $rezultat['id']);
    }

    private function podaciZaFormu(): array
    {
        $db = \App\Core\Database::konekcija();
        $rezervacije = $db->query(
            "SELECT r.id, CONCAT(r.broj_rezervacije, ' — ', e.sifra, ' (', r.kolicina, ' kom, lok. ', l.oznaka, ')') AS naziv,
                    r.element_id, r.lokacija_id
             FROM rezervacije r
             JOIN ab_elementi e ON e.id = r.element_id
             JOIN lokacije l ON l.id = r.lokacija_id
             WHERE r.status = 'aktivna'
             ORDER BY r.broj_rezervacije"
        )->fetchAll();

        return [
            'elementi'    => elementi_za_select(),
            'lokacije'    => lokacije_sa_skladistem(),
            'investitori' => (new BazniSifrarnikRepository('investitori', ['naziv']))->opcijeZaSelect('naziv'),
            'projekti'    => (new BazniSifrarnikRepository('projekti', ['naziv']))->opcijeZaSelect('naziv'),
            'objekti'     => (new BazniSifrarnikRepository('objekti', ['naziv']))->opcijeZaSelect('naziv'),
            'prevoznici'  => (new BazniSifrarnikRepository('prevoznici', ['naziv']))->opcijeZaSelect('naziv'),
            'vozila'      => $db->query("SELECT id, CONCAT(registarska_oznaka, ' (', nosivost_kg, ' kg)') AS naziv FROM vozila WHERE aktivan = 1 ORDER BY registarska_oznaka")->fetchAll(),
            'vozaci'      => (new BazniSifrarnikRepository('vozaci', ['ime']))->opcijeZaSelect('CONCAT(ime, " ", prezime)'),
            'rezervacije' => $rezervacije,
        ];
    }
}
