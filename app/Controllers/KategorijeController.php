<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;

class KategorijeController extends BazniSifrarnikController
{
    public function __construct()
    {
        $this->repo = new BazniSifrarnikRepository('kategorije_elemenata', ['naziv', 'opis']);
        $this->nazivModula = 'Kategorije elemenata';
        $this->rutaBazna = 'kategorije';
        $this->permisijaPrefix = 'kategorije';
        $this->auditModul = 'kategorije_elemenata';

        $this->poljaForme = [
            ['ime' => 'naziv', 'oznaka' => 'Naziv', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'opis', 'oznaka' => 'Opis', 'tip' => 'textarea'],
        ];

        $this->koloneListe = [
            ['kljuc' => 'naziv', 'oznaka' => 'Naziv'],
            ['kljuc' => 'opis', 'oznaka' => 'Opis'],
        ];
    }
}
