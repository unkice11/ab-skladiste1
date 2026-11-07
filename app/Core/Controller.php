<?php

namespace App\Core;

use App\Repositories\MeniRepository;

abstract class Controller
{
    protected function render(string $view, array $podaci = []): void
    {
        extract($podaci, EXTR_SKIP);

        $meni = (new MeniRepository())->stabloZaTrenutnogKorisnika();
        $korisnik = Auth::korisnik();
        $firma = (new \App\Repositories\PodaciFirmeRepository())->ucitaj();
        $flash = uzmi_flash();

        $viewFajl = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($viewFajl)) {
            throw new \RuntimeException("View ne postoji: $view");
        }

        require BASE_PATH . '/app/Views/layouts/glavni.php';
    }

    protected function renderSamostalno(string $view, array $podaci = []): void
    {
        extract($podaci, EXTR_SKIP);
        $flash = uzmi_flash();
        require BASE_PATH . '/app/Views/' . $view . '.php';
    }

    /** Renderuje view unutar print-friendly layouta (bez sidebar-a, sa zaglavljem firme) — za otpremnicu i izvještaje. */
    protected function renderPrint(string $view, array $podaci = []): void
    {
        extract($podaci, EXTR_SKIP);

        $firma = (new \App\Repositories\PodaciFirmeRepository())->ucitaj();

        $viewFajl = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($viewFajl)) {
            throw new \RuntimeException("View ne postoji: $view");
        }

        require BASE_PATH . '/app/Views/layouts/print.php';
    }
}
