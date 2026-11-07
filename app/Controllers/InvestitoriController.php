<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;

class InvestitoriController extends BazniSifrarnikController
{
    public function __construct()
    {
        $this->repo = new BazniSifrarnikRepository('investitori', ['naziv', 'grad', 'jib', 'email']);
        $this->nazivModula = 'Investitori';
        $this->rutaBazna = 'investitori';
        $this->permisijaPrefix = 'investitori';
        $this->auditModul = 'investitori';

        $this->poljaForme = [
            ['ime' => 'naziv', 'oznaka' => 'Naziv', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'adresa', 'oznaka' => 'Adresa', 'tip' => 'text'],
            ['ime' => 'grad', 'oznaka' => 'Grad', 'tip' => 'text'],
            ['ime' => 'jib', 'oznaka' => 'JIB', 'tip' => 'text'],
            ['ime' => 'pdv_broj', 'oznaka' => 'PDV broj', 'tip' => 'text'],
            ['ime' => 'telefon', 'oznaka' => 'Telefon', 'tip' => 'text'],
            ['ime' => 'email', 'oznaka' => 'E-mail', 'tip' => 'text'],
        ];

        $this->koloneListe = [
            ['kljuc' => 'naziv', 'oznaka' => 'Naziv'],
            ['kljuc' => 'grad', 'oznaka' => 'Grad'],
            ['kljuc' => 'jib', 'oznaka' => 'JIB'],
            ['kljuc' => 'telefon', 'oznaka' => 'Telefon'],
        ];
    }
}
