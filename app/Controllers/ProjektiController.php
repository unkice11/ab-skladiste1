<?php

namespace App\Controllers;

use App\Core\BazniSifrarnikController;
use App\Core\BazniSifrarnikRepository;
use App\Repositories\ProjektAnalitikaRepository;

class ProjektiController extends BazniSifrarnikController
{
    public function __construct()
    {
        $investitoriRepo = new BazniSifrarnikRepository('investitori', ['naziv']);

        $this->repo = new BazniSifrarnikRepository(
            'projekti',
            ['naziv'],
            'id',
            [['tabela' => 'investitori i', 'on' => 'i.id = t.investitor_id', 'select' => 'i.naziv AS investitor_naziv']]
        );
        $this->nazivModula = 'Projekti';
        $this->rutaBazna = 'projekti';
        $this->permisijaPrefix = 'projekti';
        $this->auditModul = 'projekti';

        $this->poljaForme = [
            ['ime' => 'naziv', 'oznaka' => 'Naziv projekta', 'tip' => 'text', 'obavezno' => true],
            ['ime' => 'investitor_id', 'oznaka' => 'Investitor', 'tip' => 'select', 'opcije_repo' => $investitoriRepo],
            ['ime' => 'opis', 'oznaka' => 'Opis', 'tip' => 'textarea'],
            ['ime' => 'planirani_broj_elemenata', 'oznaka' => 'Planirani broj elemenata (ukupno komada za projekat)', 'tip' => 'number'],
            ['ime' => 'datum_pocetka', 'oznaka' => 'Datum početka', 'tip' => 'date'],
            ['ime' => 'datum_zavrsetka', 'oznaka' => 'Datum završetka', 'tip' => 'date'],
        ];

        $this->koloneListe = [
            ['kljuc' => 'naziv', 'oznaka' => 'Naziv'],
            ['kljuc' => 'investitor_naziv', 'oznaka' => 'Investitor'],
            ['kljuc' => 'planirano_prikaz', 'oznaka' => 'Planirano'],
            ['kljuc' => 'proizvedeno_prikaz', 'oznaka' => 'Proizvedeno (ulaz)'],
            ['kljuc' => 'za_proizvesti_prikaz', 'oznaka' => 'Za proizvesti'],
            ['kljuc' => 'otpremljeno_prikaz', 'oznaka' => 'Otpremljeno'],
            ['kljuc' => 'za_otpremu_prikaz', 'oznaka' => 'Za otpremu'],
        ];
    }

    /**
     * Override generičkog index()-a samo da bi se listi projekata dodale
     * kolone realizacije (planirano/otpremljeno/preostalo) — te vrijednosti
     * dolaze iz druge tabele (otpreme_stavke), pa ih generički šifrarnik-
     * engine ne može sam izračunati.
     */
    public function index(): void
    {
        $pretraga = trim($_GET['q'] ?? '');
        $stranica = max(1, (int) ($_GET['stranica'] ?? 1));
        $rezultat = $this->repo->lista($pretraga, $stranica, 20);

        $analitikaRepo = new ProjektAnalitikaRepository();
        $otpremljenoPoProjektu = $analitikaRepo->otpremljenoPoSvimProjektima();
        $proizvedenoPoProjektu = $analitikaRepo->proizvedenoPoSvimProjektima();

        $fmt = fn(float $v): string => number_format($v, 0, ',', '.');

        foreach ($rezultat['stavke'] as &$red) {
            $planirano = $red['planirani_broj_elemenata'] !== null ? (float) $red['planirani_broj_elemenata'] : null;
            $otpremljeno = $otpremljenoPoProjektu[(int) $red['id']] ?? 0.0;
            $proizvedeno = $proizvedenoPoProjektu[(int) $red['id']] ?? 0.0;

            $red['planirano_prikaz'] = $planirano !== null ? $fmt($planirano) : '—';
            $red['proizvedeno_prikaz'] = $fmt($proizvedeno);
            $red['za_proizvesti_prikaz'] = $planirano !== null ? $fmt(max(0, $planirano - $proizvedeno)) : '—';
            $red['otpremljeno_prikaz'] = $fmt($otpremljeno);
            $red['za_otpremu_prikaz'] = $planirano !== null ? $fmt(max(0, $planirano - $otpremljeno)) : '—';
        }
        unset($red);

        $this->render('sifrarnik/lista', [
            'rezultat'        => $rezultat,
            'pretraga'        => $pretraga,
            'koloneListe'     => $this->koloneListe,
            'nazivModula'     => $this->nazivModula,
            'rutaBazna'       => $this->rutaBazna,
            'permisijaPrefix' => $this->permisijaPrefix,
            'dodatneAkcije'   => [['ruta' => 'projekti/analitika', 'oznaka' => 'Analitika']],
        ]);
    }

    /** Detaljna analitika realizacije za jedan projekat (raspodjela po elementu). */
    public function analitika(string $id): void
    {
        $id = (int) $id;
        $projekat = $this->repo->pronadjiPoId($id);
        if (!$projekat) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        $analitika = (new ProjektAnalitikaRepository())->analitikaZaProjekat($id);
        $planirano = $projekat['planirani_broj_elemenata'] !== null ? (float) $projekat['planirani_broj_elemenata'] : null;
        $proizvedeno = $analitika['ukupno_proizvedeno'];
        $otpremljeno = $analitika['ukupno_otpremljeno'];
        $postotak = fn(float $dio) => ($planirano !== null && $planirano > 0) ? min(100, (int) round($dio / $planirano * 100)) : null;

        $this->render('projekti/analitika', [
            'projekat'          => $projekat,
            'poElementu'        => $analitika['po_elementu'],
            'planirano'         => $planirano,
            'proizvedeno'       => $proizvedeno,
            'otpremljeno'       => $otpremljeno,
            'zaProizvesti'      => $planirano !== null ? max(0, $planirano - $proizvedeno) : null,
            'zaOtpremu'         => $planirano !== null ? max(0, $planirano - $otpremljeno) : null,
            'cekaOtpremu'       => max(0, $proizvedeno - $otpremljeno),
            'procenatProizvodnje' => $postotak($proizvedeno),
            'procenatOtpreme'   => $postotak($otpremljeno),
        ]);
    }
}
