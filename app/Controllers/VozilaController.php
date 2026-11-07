<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;

class VozilaController extends BazniSifrarnikController
{
    public function __construct()
    {
        $prevozniciRepo = new BazniSifrarnikRepository('prevoznici', ['naziv']);

        $this->repo = new BazniSifrarnikRepository(
            'vozila',
            ['registarska_oznaka', 'naziv_tip'],
            'id',
            [['tabela' => 'prevoznici p', 'on' => 'p.id = t.prevoznik_id', 'select' => 'p.naziv AS prevoznik_naziv']]
        );
        $this->nazivModula = 'Vozila';
        $this->rutaBazna = 'vozila';
        $this->permisijaPrefix = 'vozila';
        $this->auditModul = 'vozila';

        $this->poljaForme = [
            ['ime' => 'registarska_oznaka', 'oznaka' => 'Registarska oznaka', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'naziv_tip', 'oznaka' => 'Naziv / tip vozila', 'tip' => 'text'],
            ['ime' => 'prevoznik_id', 'oznaka' => 'Prevoznik', 'tip' => 'select', 'opcije_repo' => $prevozniciRepo],
            ['ime' => 'nosivost_kg', 'oznaka' => 'Nosivost (kg)', 'tip' => 'number', 'obavezno' => true],
        ];

        $this->koloneListe = [
            ['kljuc' => 'registarska_oznaka', 'oznaka' => 'Registracija'],
            ['kljuc' => 'naziv_tip', 'oznaka' => 'Tip'],
            ['kljuc' => 'prevoznik_naziv', 'oznaka' => 'Prevoznik'],
            ['kljuc' => 'nosivost_kg', 'oznaka' => 'Nosivost (kg)'],
        ];
    }
}
