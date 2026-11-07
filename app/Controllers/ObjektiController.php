<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;

class ObjektiController extends BazniSifrarnikController
{
    public function __construct()
    {
        $projektiRepo = new BazniSifrarnikRepository('projekti', ['naziv']);

        $this->repo = new BazniSifrarnikRepository(
            'objekti',
            ['naziv'],
            'id',
            [['tabela' => 'projekti p', 'on' => 'p.id = t.projekat_id', 'select' => 'p.naziv AS projekat_naziv']]
        );
        $this->nazivModula = 'Objekti';
        $this->rutaBazna = 'objekti';
        $this->permisijaPrefix = 'objekti';
        $this->auditModul = 'objekti';

        $this->poljaForme = [
            ['ime' => 'naziv', 'oznaka' => 'Naziv objekta', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'projekat_id', 'oznaka' => 'Projekat', 'tip' => 'select', 'opcije_repo' => $projektiRepo],
            ['ime' => 'opis', 'oznaka' => 'Opis', 'tip' => 'textarea'],
        ];

        $this->koloneListe = [
            ['kljuc' => 'naziv', 'oznaka' => 'Naziv'],
            ['kljuc' => 'projekat_naziv', 'oznaka' => 'Projekat'],
        ];
    }
}
