<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BazniSifrarnikRepository;
use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\RezervacijaRepository;
use RuntimeException;

class RezervacijeController extends Controller
{
    public function index(): void
    {
        $pretraga = trim($_GET['q'] ?? '');
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));

        $this->render('rezervacije/lista', [
            'rezultat' => (new RezervacijaRepository())->lista($pretraga, $stranica, 20),
            'pretraga' => $pretraga,
        ]);
    }

    public function formaZaKreiranje(): void
    {
        $this->render('rezervacije/forma', [
            'elementi'    => elementi_za_select(),
            'lokacije'    => lokacije_sa_skladistem(),
            'investitori' => (new BazniSifrarnikRepository('investitori', ['naziv']))->opcijeZaSelect('naziv'),
            'projekti'    => (new BazniSifrarnikRepository('projekti', ['naziv']))->opcijeZaSelect('naziv'),
            'objekti'     => (new BazniSifrarnikRepository('objekti', ['naziv']))->opcijeZaSelect('naziv'),
        ]);
    }

    public function sacuvaj(): void
    {
        $elementId = (int) ($_POST['element_id'] ?? 0);
        $lokacijaId = (int) ($_POST['lokacija_id'] ?? 0);
        $kolicina = (float) str_replace(',', '.', $_POST['kolicina'] ?? '0');

        $greske = [];
        if ($elementId <= 0) {
            $greske[] = 'Element je obavezan.';
        }
        if ($lokacijaId <= 0) {
            $greske[] = 'Lokacija je obavezna.';
        }
        if ($kolicina <= 0) {
            $greske[] = 'Količina mora biti veća od 0.';
        }

        if ($greske) {
            flash('greska', implode(' ', $greske));
            redirect('rezervacije/dodaj');
        }

        try {
            $rezultat = (new RezervacijaRepository())->kreiraj([
                'element_id'    => $elementId,
                'lokacija_id'   => $lokacijaId,
                'kolicina'      => $kolicina,
                'investitor_id' => (int) ($_POST['investitor_id'] ?? 0) ?: null,
                'projekat_id'   => (int) ($_POST['projekat_id'] ?? 0) ?: null,
                'objekat_id'    => (int) ($_POST['objekat_id'] ?? 0) ?: null,
                'napomena'      => trim($_POST['napomena'] ?? ''),
                'korisnik_id'   => Auth::korisnik()['id'],
            ]);
        } catch (RuntimeException $e) {
            flash('greska', $e->getMessage());
            redirect('rezervacije/dodaj');
        }

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'kreiranje',
            'rezervacije',
            'rezervacije',
            $rezultat['id'],
            null,
            ['broj_rezervacije' => $rezultat['broj_rezervacije']]
        );

        flash('uspjeh', 'Rezervacija ' . $rezultat['broj_rezervacije'] . ' je kreirana.');
        redirect('rezervacije');
    }

    public function otkazi(string $id): void
    {
        $id = (int) $id;
        try {
            (new RezervacijaRepository())->otkazi($id, Auth::korisnik()['id']);
        } catch (RuntimeException $e) {
            flash('greska', $e->getMessage());
            redirect('rezervacije');
        }

        (new AuditRepository())->zabiljezi(Auth::korisnik()['id'], 'otkazivanje', 'rezervacije', 'rezervacije', $id);

        flash('uspjeh', 'Rezervacija je otkazana.');
        redirect('rezervacije');
    }
}
