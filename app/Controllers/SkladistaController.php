<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;

class SkladistaController extends BazniSifrarnikController
{
    public function __construct()
    {
        $this->repo = new BazniSifrarnikRepository('skladista', ['sifra', 'naziv', 'lokacija']);
        $this->nazivModula = 'Skladišta';
        $this->rutaBazna = 'skladista';
        $this->permisijaPrefix = 'skladista';
        $this->auditModul = 'skladista';

        $this->poljaForme = [
            ['ime' => 'sifra', 'oznaka' => 'Šifra', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'naziv', 'oznaka' => 'Naziv', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'lokacija', 'oznaka' => 'Lokacija (adresa)', 'tip' => 'text'],
            ['ime' => 'opis', 'oznaka' => 'Opis', 'tip' => 'textarea'],
        ];

        $this->koloneListe = [
            ['kljuc' => 'sifra', 'oznaka' => 'Šifra'],
            ['kljuc' => 'naziv', 'oznaka' => 'Naziv'],
            ['kljuc' => 'lokacija', 'oznaka' => 'Lokacija'],
        ];
    }
}
