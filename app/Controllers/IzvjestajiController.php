<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\OtpremaRepository;
use App\Repositories\RezervacijaRepository;
use App\Repositories\StanjeRepository;
use App\Repositories\UlazRepository;

class IzvjestajiController extends Controller
{
    public function index(): void
    {
        $this->render('izvjestaji/index');
    }

    public function ulazi(): void
    {
        [$od, $do] = $this->periodIzUpita();
        $stavke = (new UlazRepository())->zaPeriod($od, $do);

        $this->izlaz('izvjestaji/ulazi', ['stavke' => $stavke, 'od' => $od, 'do' => $do], [
            'naziv_fajla' => 'ulazi',
            'kolone' => [
                'broj_ulaza' => 'Broj ulaza', 'datum' => 'Datum', 'skladiste_naziv' => 'Skladište',
                'investitor_naziv' => 'Investitor', 'status' => 'Status',
            ],
        ]);
    }

    public function otpreme(): void
    {
        [$od, $do] = $this->periodIzUpita();
        $stavke = (new OtpremaRepository())->zaPeriod($od, $do);
        $ukupnaTezina = array_sum(array_column($stavke, 'ukupna_tezina_kg'));

        $this->izlaz('izvjestaji/otpreme', ['stavke' => $stavke, 'od' => $od, 'do' => $do, 'ukupnaTezina' => $ukupnaTezina], [
            'naziv_fajla' => 'otpreme',
            'kolone' => [
                'broj_otpremnice' => 'Broj otpremnice', 'datum' => 'Datum', 'investitor_naziv' => 'Investitor',
                'ukupno_komada' => 'Komada', 'ukupna_tezina_kg' => 'Težina (kg)',
            ],
        ]);
    }

    public function rezervisani(): void
    {
        $stavke = (new RezervacijaRepository())->aktivne();

        $this->izlaz('izvjestaji/rezervisani', ['stavke' => $stavke], [
            'naziv_fajla' => 'rezervisani-elementi',
            'kolone' => [
                'broj_rezervacije' => 'Broj', 'sifra' => 'Šifra', 'element_naziv' => 'Element',
                'lokacija_oznaka' => 'Lokacija', 'kolicina' => 'Količina',
                'investitor_naziv' => 'Investitor', 'projekat_naziv' => 'Projekat',
            ],
        ]);
    }

    public function poInvestitoru(): void
    {
        $stavke = (new StanjeRepository())->poInvestitoru();

        $this->izlaz('izvjestaji/po_investitoru', ['stavke' => $stavke], [
            'naziv_fajla' => 'stanje-po-investitoru',
            'kolone' => ['investitor_naziv' => 'Investitor', 'ukupno_komada' => 'Komada', 'ukupna_tezina_kg' => 'Težina (kg)'],
        ]);
    }

    public function stanje(): void
    {
        $pretraga = trim($_GET['q'] ?? '');
        $rezultat = (new StanjeRepository())->pregled($pretraga, 1, 1000);

        $this->izlaz('izvjestaji/stanje', ['stavke' => $rezultat['stavke'], 'pretraga' => $pretraga, 'printOrijentacija' => 'landscape'], [
            'naziv_fajla' => 'stanje-skladista',
            'kolone' => [
                'sifra' => 'Šifra', 'naziv' => 'Element', 'investitor_naziv' => 'Investitor',
                'skladiste_naziv' => 'Skladište', 'lokacija_oznaka' => 'Lokacija',
                'kolicina' => 'Količina', 'rezervisano' => 'Rezervisano', 'slobodno' => 'Slobodno',
                'ukupna_tezina_kg' => 'Ukupna težina (kg)',
            ],
        ]);
    }

    public function poObjektu(): void
    {
        [$od, $do] = $this->periodIzUpita();
        $stavke = (new OtpremaRepository())->poObjektuZaPeriod($od, $do);

        $this->izlaz('izvjestaji/po_objektu', ['stavke' => $stavke, 'od' => $od, 'do' => $do], [
            'naziv_fajla' => 'otpremljeno-po-objektu',
            'kolone' => [
                'objekat_naziv' => 'Objekat', 'projekat_naziv' => 'Projekat',
                'ukupno_komada' => 'Komada', 'ukupna_tezina_kg' => 'Težina (kg)',
            ],
        ]);
    }

    public function poElementu(): void
    {
        [$od, $do] = $this->periodIzUpita();
        $stavke = (new OtpremaRepository())->poElementuZaPeriod($od, $do);

        $this->izlaz('izvjestaji/po_elementu', ['stavke' => $stavke, 'od' => $od, 'do' => $do], [
            'naziv_fajla' => 'kretanje-po-elementu',
            'kolone' => [
                'sifra' => 'Šifra', 'naziv' => 'Element', 'ulaz_period' => 'Ulaz (period)',
                'otprema_period' => 'Otprema (period)', 'trenutno_stanje' => 'Trenutno stanje',
            ],
        ]);
    }

    public function poLokaciji(): void
    {
        $stavke = (new StanjeRepository())->poLokaciji();

        $this->izlaz('izvjestaji/po_lokaciji', ['stavke' => $stavke], [
            'naziv_fajla' => 'stanje-po-lokaciji',
            'kolone' => [
                'skladiste_naziv' => 'Skladište', 'zona' => 'Zona', 'lokacija_oznaka' => 'Lokacija',
                'ukupno_komada' => 'Komada', 'ukupna_tezina_kg' => 'Težina (kg)',
            ],
        ]);
    }

    public function ukupnaTezina(): void
    {
        $godina = (int) ($_GET['godina'] ?? date('Y'));
        $poMjesecu = (new OtpremaRepository())->tezinaPoMjesecima($godina);

        $nazivMjeseca = [
            1 => 'Januar', 2 => 'Februar', 3 => 'Mart', 4 => 'April', 5 => 'Maj', 6 => 'Juni',
            7 => 'Juli', 8 => 'Avgust', 9 => 'Septembar', 10 => 'Oktobar', 11 => 'Novembar', 12 => 'Decembar',
        ];

        $redovi = [];
        $ukupnoGodina = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $tezina = (float) ($poMjesecu[$m]['ukupna_tezina_kg'] ?? 0);
            $komada = (float) ($poMjesecu[$m]['ukupno_komada'] ?? 0);
            $ukupnoGodina += $tezina;
            $redovi[] = ['mjesec' => $nazivMjeseca[$m], 'ukupno_komada' => $komada, 'ukupna_tezina_kg' => $tezina];
        }

        $this->izlaz('izvjestaji/ukupna_tezina', ['stavke' => $redovi, 'godina' => $godina, 'ukupnoGodina' => $ukupnoGodina], [
            'naziv_fajla' => 'ukupna-otpremljena-tezina-' . $godina,
            'kolone' => ['mjesec' => 'Mjesec', 'ukupno_komada' => 'Komada', 'ukupna_tezina_kg' => 'Težina (kg)'],
        ]);
    }

    /** @return array{0:string,1:string} [od, do] datumi iz GET parametara, sa razumnim defaultom (tekući mjesec). */
    private function periodIzUpita(): array
    {
        $od = $_GET['od'] ?? date('Y-m-01');
        $do = $_GET['do'] ?? date('Y-m-d');
        return [$od, $do];
    }

    /**
     * Zajednička izlazna tačka za sve izvještaje: normalan prikaz (sa filterima),
     * print prikaz (?stampaj=1), ili CSV export (?export=csv) — sve iz istih
     * već dohvaćenih podataka, bez dupliranja upita.
     */
    private function izlaz(string $view, array $podaci, array $izvozKonfig): void
    {
        if (($_GET['export'] ?? '') === 'csv') {
            $this->izvezCsv($podaci['stavke'], $izvozKonfig['kolone'], $izvozKonfig['naziv_fajla']);
            return;
        }

        if (!empty($_GET['stampaj'])) {
            $this->renderPrint($view, $podaci + ['stampaj' => true]);
        } else {
            $this->render($view, $podaci + ['stampaj' => false]);
        }
    }

    /** @param array<string,string> $kolone kolona_u_redu => naslov u CSV-u */
    private function izvezCsv(array $redovi, array $kolone, string $nazivFajla): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nazivFajla . '-' . date('Y-m-d') . '.csv"');

        $izlaz = fopen('php://output', 'w');
        // BOM radi ispravnog prikaza dijakritika (č/ć/š/ž/đ) kad se otvori u Excelu.
        fwrite($izlaz, "\xEF\xBB\xBF");
        fputcsv($izlaz, array_values($kolone));

        foreach ($redovi as $red) {
            $linija = [];
            foreach (array_keys($kolone) as $kljuc) {
                $linija[] = $red[$kljuc] ?? '';
            }
            fputcsv($izlaz, $linija);
        }

        fclose($izlaz);
        exit;
    }
}
