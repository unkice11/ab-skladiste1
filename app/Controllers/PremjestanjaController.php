<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BazniSifrarnikRepository;
use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\PremjestanjeRepository;
use RuntimeException;

class PremjestanjaController extends Controller
{
    public function index(): void
    {
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));
        $this->render('premjestanja/lista', [
            'rezultat' => (new PremjestanjeRepository())->lista($stranica, 20),
        ]);
    }

    public function formaZaKreiranje(): void
    {
        $this->render('premjestanja/forma', [
            'elementi'    => elementi_za_select(),
            'lokacije'    => lokacije_sa_skladistem(),
            'investitori' => (new BazniSifrarnikRepository('investitori', ['naziv']))->opcijeZaSelect('naziv'),
            'projekti'    => (new BazniSifrarnikRepository('projekti', ['naziv']))->opcijeZaSelect('naziv'),
        ]);
    }

    public function sacuvaj(): void
    {
        $elementId = (int) ($_POST['element_id'] ?? 0);
        $izLokacijaId = (int) ($_POST['iz_lokacija_id'] ?? 0);
        $uLokacijaId = (int) ($_POST['u_lokacija_id'] ?? 0);
        $kolicina = (float) str_replace(',', '.', $_POST['kolicina'] ?? '0');

        $greske = [];
        if ($elementId <= 0) {
            $greske[] = 'Element je obavezan.';
        }
        if ($izLokacijaId <= 0 || $uLokacijaId <= 0) {
            $greske[] = 'Izvorna i ciljna lokacija su obavezne.';
        }
        if ($kolicina <= 0) {
            $greske[] = 'Količina mora biti veća od 0.';
        }

        if ($greske) {
            flash('greska', implode(' ', $greske));
            redirect('premjestanja/dodaj');
        }

        try {
            $rezultat = (new PremjestanjeRepository())->kreiraj([
                'element_id'     => $elementId,
                'iz_lokacija_id' => $izLokacijaId,
                'u_lokacija_id'  => $uLokacijaId,
                'kolicina'       => $kolicina,
                'investitor_id'  => (int) ($_POST['investitor_id'] ?? 0) ?: null,
                'projekat_id'    => (int) ($_POST['projekat_id'] ?? 0) ?: null,
                'napomena'       => trim($_POST['napomena'] ?? ''),
                'korisnik_id'    => Auth::korisnik()['id'],
            ]);
        } catch (RuntimeException $e) {
            flash('greska', $e->getMessage());
            redirect('premjestanja/dodaj');
        }

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'kreiranje',
            'premjestanja',
            'premjestanja',
            $rezultat['id'],
            null,
            ['broj_premjestanja' => $rezultat['broj_premjestanja']]
        );

        flash('uspjeh', 'Premještanje ' . $rezultat['broj_premjestanja'] . ' je izvršeno.');
        redirect('premjestanja');
    }
}
