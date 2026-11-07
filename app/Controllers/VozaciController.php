<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;

class VozaciController extends BazniSifrarnikController
{
    public function __construct()
    {
        $prevozniciRepo = new BazniSifrarnikRepository('prevoznici', ['naziv']);

        $this->repo = new BazniSifrarnikRepository(
            'vozaci',
            ['ime', 'prezime'],
            'id',
            [['tabela' => 'prevoznici p', 'on' => 'p.id = t.prevoznik_id', 'select' => 'p.naziv AS prevoznik_naziv']]
        );
        $this->nazivModula = 'Vozači';
        $this->rutaBazna = 'vozaci';
        $this->permisijaPrefix = 'vozaci';
        $this->auditModul = 'vozaci';

        $this->poljaForme = [
            ['ime' => 'ime', 'oznaka' => 'Ime', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'prezime', 'oznaka' => 'Prezime', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'prevoznik_id', 'oznaka' => 'Prevoznik', 'tip' => 'select', 'opcije_repo' => $prevozniciRepo],
            ['ime' => 'telefon', 'oznaka' => 'Telefon', 'tip' => 'text'],
        ];

        $this->koloneListe = [
            ['kljuc' => 'ime', 'oznaka' => 'Ime'],
            ['kljuc' => 'prezime', 'oznaka' => 'Prezime'],
            ['kljuc' => 'prevoznik_naziv', 'oznaka' => 'Prevoznik'],
            ['kljuc' => 'telefon', 'oznaka' => 'Telefon'],
        ];
    }
}
