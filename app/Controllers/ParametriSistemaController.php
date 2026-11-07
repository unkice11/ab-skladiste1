<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\PostavkeRepository;

class ParametriSistemaController extends Controller
{
    public function prikazi(): void
    {
        $repo = new PostavkeRepository();
        $this->render('postavke/parametri', [
            'standardnaGustina' => $repo->standardnaGustinaBetona(),
        ]);
    }

    public function sacuvaj(): void
    {
        $novaVrijednost = (float) str_replace(',', '.', $_POST['standardna_gustina_betona_kg_m3'] ?? '0');

        if ($novaVrijednost <= 0) {
            flash('greska', 'Gustina betona mora biti veća od 0.');
            redirect('postavke/parametri-sistema');
        }

        $repo = new PostavkeRepository();
        $staraVrijednost = $repo->standardnaGustinaBetona();
        $repo->set('standardna_gustina_betona_kg_m3', (string) $novaVrijednost);

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'izmjena',
            'parametri_sistema',
            'postavke_sistema',
            null,
            ['standardna_gustina_betona_kg_m3' => $staraVrijednost],
            ['standardna_gustina_betona_kg_m3' => $novaVrijednost]
        );

        flash('uspjeh', 'Parametri sistema su sačuvani. Napomena: ova vrijednost se koristi samo kao podrazumijevana za NOVE elemente — postojeći elementi zadržavaju svoju upisanu gustinu.');
        redirect('postavke/parametri-sistema');
    }
}
