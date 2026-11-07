<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;

class LokacijeController extends BazniSifrarnikController
{
    public function __construct()
    {
        $skladistaRepo = new BazniSifrarnikRepository('skladista', ['naziv']);

        $this->repo = new BazniSifrarnikRepository(
            'lokacije',
            ['zona', 'oznaka'],
            'id',
            [['tabela' => 'skladista s', 'on' => 's.id = t.skladiste_id', 'select' => 's.naziv AS skladiste_naziv']]
        );
        $this->nazivModula = 'Lokacije';
        $this->rutaBazna = 'lokacije';
        $this->permisijaPrefix = 'lokacije';
        $this->auditModul = 'lokacije';

        $this->poljaForme = [
            ['ime' => 'skladiste_id', 'oznaka' => 'Skladište', 'tip' => 'select', 'obavezno' => true, 'opcije_repo' => $skladistaRepo],
            ['ime' => 'zona', 'oznaka' => 'Zona', 'tip' => 'text'],
            ['ime' => 'oznaka', 'oznaka' => 'Oznaka lokacije (npr. A-01)', 'tip' => 'text', 'obavezno' => true],
        ];

        $this->koloneListe = [
            ['kljuc' => 'skladiste_naziv', 'oznaka' => 'Skladište'],
            ['kljuc' => 'zona', 'oznaka' => 'Zona'],
            ['kljuc' => 'oznaka', 'oznaka' => 'Oznaka'],
        ];
    }
}
