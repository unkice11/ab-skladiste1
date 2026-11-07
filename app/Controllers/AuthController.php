<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

class AuthController extends Controller
{
    public function prikaziPrijavu(): void
    {
        if (Auth::jePrijavljen()) {
            redirect('dashboard');
        }
        $this->renderSamostalno('auth/prijava');
    }

    public function prijava(): void
    {
        $korisnickoIme = trim($_POST['korisnicko_ime'] ?? '');
        $lozinka = (string) ($_POST['lozinka'] ?? '');

        if ($korisnickoIme === '' || $lozinka === '') {
            flash('greska', 'Unesite korisničko ime i lozinku.');
            sacuvaj_old(['korisnicko_ime' => $korisnickoIme]);
            redirect('prijava');
        }

        $zapamtiMe = !empty($_POST['zapamti_me']);
        $rezultat = Auth::pokusajPrijavu($korisnickoIme, $lozinka, $zapamtiMe);

        if (!$rezultat['uspjeh']) {
            flash('greska', $rezultat['poruka']);
            sacuvaj_old(['korisnicko_ime' => $korisnickoIme]);
            redirect('prijava');
        }

        ocisti_old();
        redirect('dashboard');
    }

    public function odjava(): void
    {
        Auth::odjava();
        redirect('prijava');
    }
}
