<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;

class PrevozniciController extends BazniSifrarnikController
{
    public function __construct()
    {
        $this->repo = new BazniSifrarnikRepository('prevoznici', ['naziv', 'telefon', 'email']);
        $this->nazivModula = 'Prevoznici';
        $this->rutaBazna = 'prevoznici';
        $this->permisijaPrefix = 'prevoznici';
        $this->auditModul = 'prevoznici';

        $this->poljaForme = [
            ['ime' => 'naziv', 'oznaka' => 'Naziv', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'adresa', 'oznaka' => 'Adresa', 'tip' => 'text'],
            ['ime' => 'telefon', 'oznaka' => 'Telefon', 'tip' => 'text'],
            ['ime' => 'email', 'oznaka' => 'E-mail', 'tip' => 'text'],
        ];

        $this->koloneListe = [
            ['kljuc' => 'naziv', 'oznaka' => 'Naziv'],
            ['kljuc' => 'telefon', 'oznaka' => 'Telefon'],
            ['kljuc' => 'email', 'oznaka' => 'E-mail'],
        ];
    }
}
