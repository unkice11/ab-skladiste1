<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

class OtpremaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function lista(string $pretraga = '', int $stranica = 1, int $poStranici = 20): array
    {
        $stranica = max(1, $stranica);
        $offset = ($stranica - 1) * $poStranici;

        $uslov = '';
        $parametri = [];
        if ($pretraga !== '') {
            $uslov = 'WHERE o.broj_otpremnice LIKE :p';
            $parametri['p'] = '%' . $pretraga . '%';
        }

        $stmtBroj = $this->db->prepare("SELECT COUNT(*) AS ukupno FROM otpreme o $uslov");
        $stmtBroj->execute($parametri);
        $ukupno = (int) $stmtBroj->fetch()['ukupno'];

        $stmt = $this->db->prepare(
            "SELECT o.*, i.naziv AS investitor_naziv, p.naziv AS projekat_naziv,
                    pr.naziv AS prevoznik_naziv, v.registarska_oznaka, k.korisnicko_ime
             FROM otpreme o
             LEFT JOIN investitori i ON i.id = o.investitor_id
             LEFT JOIN projekti p ON p.id = o.projekat_id
             LEFT JOIN prevoznici pr ON pr.id = o.prevoznik_id
             LEFT JOIN vozila v ON v.id = o.vozilo_id
             LEFT JOIN korisnici k ON k.id = o.korisnik_id
             $uslov
             ORDER BY o.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($parametri as $key => $v) {
            $stmt->bindValue($key, $v);
        }
        $stmt->bindValue('limit', $poStranici, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'stavke' => $stmt->fetchAll(),
            'ukupno' => $ukupno,
            'stranica' => $stranica,
            'ukupno_stranica' => max(1, (int) ceil($ukupno / $poStranici)),
        ];
    }

    public function pronadjiPoId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, i.naziv AS investitor_naziv, p.naziv AS projekat_naziv, ob.naziv AS objekat_naziv,
                    pr.naziv AS prevoznik_naziv, v.registarska_oznaka, v.nosivost_kg, vz.ime AS vozac_ime, vz.prezime AS vozac_prezime,
                    k.korisnicko_ime
             FROM otpreme o
             LEFT JOIN investitori i ON i.id = o.investitor_id
             LEFT JOIN projekti p ON p.id = o.projekat_id
             LEFT JOIN objekti ob ON ob.id = o.objekat_id
             LEFT JOIN prevoznici pr ON pr.id = o.prevoznik_id
             LEFT JOIN vozila v ON v.id = o.vozilo_id
             LEFT JOIN vozaci vz ON vz.id = o.vozac_id
             LEFT JOIN korisnici k ON k.id = o.korisnik_id
             WHERE o.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $red = $stmt->fetch();
        if (!$red) {
            return null;
        }

        $stmtStavke = $this->db->prepare(
            'SELECT os.*, e.sifra, e.naziv AS element_naziv, e.opis AS element_opis, l.oznaka AS lokacija_oznaka
             FROM otpreme_stavke os
             JOIN ab_elementi e ON e.id = os.element_id
             JOIN lokacije l ON l.id = os.lokacija_id
             WHERE os.otprema_id = :id'
        );
        $stmtStavke->execute(['id' => $id]);
        $red['stavke'] = $stmtStavke->fetchAll();
        $red['ukupna_tezina_kg'] = array_sum(array_column($red['stavke'], 'ukupna_tezina_kg'));
        $red['ukupno_komada'] = array_sum(array_column($red['stavke'], 'kolicina'));

        return $red;
    }

    /**
     * @param array $header ['datum','investitor_id','projekat_id','objekat_id','prevoznik_id','vozilo_id','vozac_id','napomena','korisnik_id']
     * @param array $stavke [['element_id'=>int,'lokacija_id'=>int,'kolicina'=>float,'rezervacija_id'=>?int], ...]
     * @return array{id:int, broj_otpremnice:string, upozorenje_nosivost:?string}
     */
    public function kreiraj(array $header, array $stavke): array
    {
        $this->db->beginTransaction();
        try {
            $investitorId = StanjeRepository::norm($header['investitor_id'] ?? null);
            $projekatId = StanjeRepository::norm($header['projekat_id'] ?? null);

            $broj = (new NumeracijaRepository())->sljedeciBroj('OTP', (int) date('Y', strtotime($header['datum'])));

            $stmt = $this->db->prepare(
                'INSERT INTO otpreme (broj_otpremnice, datum, investitor_id, projekat_id, objekat_id, prevoznik_id, vozilo_id, vozac_id, napomena, korisnik_id)
                 VALUES (:broj, :datum, :investitor_id, :projekat_id, :objekat_id, :prevoznik_id, :vozilo_id, :vozac_id, :napomena, :korisnik_id)'
            );
            $stmt->execute([
                'broj'          => $broj,
                'datum'         => $header['datum'],
                'investitor_id' => $header['investitor_id'] ?: null,
                'projekat_id'   => $header['projekat_id'] ?: null,
                'objekat_id'    => $header['objekat_id'] ?: null,
                'prevoznik_id'  => $header['prevoznik_id'] ?: null,
                'vozilo_id'     => $header['vozilo_id'] ?: null,
                'vozac_id'      => $header['vozac_id'] ?: null,
                'napomena'      => $header['napomena'] ?: null,
                'korisnik_id'   => $header['korisnik_id'],
            ]);
            $otpremaId = (int) $this->db->lastInsertId();

            $stanjeRepo = new StanjeRepository();
            $karticaRepo = new SkladisnaKarticaRepository();
            $elementiRepo = new AbElementiRepository();

            $insertStavka = $this->db->prepare(
                'INSERT INTO otpreme_stavke (otprema_id, element_id, lokacija_id, rezervacija_id, kolicina, kg_po_komadu, ukupna_tezina_kg)
                 VALUES (:otprema_id, :element_id, :lokacija_id, :rezervacija_id, :kolicina, :kg_po_komadu, :ukupna_tezina_kg)'
            );

            $ukupnaTezinaDokumenta = 0.0;

            foreach ($stavke as $s) {
                $elementId = (int) $s['element_id'];
                $lokacijaId = (int) $s['lokacija_id'];
                $kolicina = (float) $s['kolicina'];
                $rezervacijaId = !empty($s['rezervacija_id']) ? (int) $s['rezervacija_id'] : null;

                if ($rezervacijaId) {
                    $this->potrosiRezervaciju($rezervacijaId, $elementId, $lokacijaId, $investitorId, $projekatId, $kolicina);
                } else {
                    // Bez veze na konkretnu rezervaciju — smije se otpremiti samo SLOBODNA
                    // (nerezervisana) količina na toj investitor/projekat traci, da se ne bi
                    // nesvjesno otpremila roba rezervisana za nekog drugog.
                    $fizicko = $stanjeRepo->kolicinaZaAzuriranje($elementId, $lokacijaId, $investitorId, $projekatId);
                    $rezervisano = $stanjeRepo->rezervisanoZaAzuriranje($elementId, $lokacijaId, $investitorId, $projekatId);
                    $slobodno = $fizicko - $rezervisano;
                    if ($kolicina > $slobodno) {
                        throw new RuntimeException(sprintf(
                            'Otprema premašuje slobodnu (nerezervisanu) količinu za element ID %d na izabranoj traci (slobodno: %s, traženo: %s).',
                            $elementId,
                            $slobodno,
                            $kolicina
                        ));
                    }
                }

                $stanjeNakon = $stanjeRepo->smanji($elementId, $lokacijaId, $kolicina, $investitorId, $projekatId);

                $element = $elementiRepo->pronadjiPoId($elementId);
                $kgPoKomadu = $element['okvirna_tezina_kg'] ?? 0;
                $ukupnaTezinaStavke = round($kolicina * $kgPoKomadu, 2);
                $ukupnaTezinaDokumenta += $ukupnaTezinaStavke;

                $insertStavka->execute([
                    'otprema_id'       => $otpremaId,
                    'element_id'       => $elementId,
                    'lokacija_id'      => $lokacijaId,
                    'rezervacija_id'   => $rezervacijaId,
                    'kolicina'         => $kolicina,
                    'kg_po_komadu'     => $kgPoKomadu,
                    'ukupna_tezina_kg' => $ukupnaTezinaStavke,
                ]);

                $karticaRepo->upisi([
                    'element_id'    => $elementId,
                    'lokacija_id'   => $lokacijaId,
                    'investitor_id' => $header['investitor_id'] ?: null,
                    'projekat_id'   => $header['projekat_id'] ?: null,
                    'tip_promjene'  => 'izlaz',
                    'kolicina'      => -1 * $kolicina,
                    'stanje_nakon'  => $stanjeNakon,
                    'dokument_tip'  => 'otprema',
                    'dokument_id'   => $otpremaId,
                    'dokument_broj' => $broj,
                    'korisnik_id'   => $header['korisnik_id'],
                ]);
            }

            $upozorenje = null;
            if (!empty($header['vozilo_id'])) {
                $stmtVozilo = $this->db->prepare('SELECT nosivost_kg, registarska_oznaka FROM vozila WHERE id = :id');
                $stmtVozilo->execute(['id' => $header['vozilo_id']]);
                $vozilo = $stmtVozilo->fetch();
                if ($vozilo && $ukupnaTezinaDokumenta > (float) $vozilo['nosivost_kg']) {
                    $upozorenje = sprintf(
                        'Ukupna težina otpreme (%s kg) premašuje nosivost vozila %s (%s kg).',
                        number_format($ukupnaTezinaDokumenta, 0, ',', '.'),
                        $vozilo['registarska_oznaka'],
                        number_format((float) $vozilo['nosivost_kg'], 0, ',', '.')
                    );
                }
            }

            $this->db->commit();

            return ['id' => $otpremaId, 'broj_otpremnice' => $broj, 'upozorenje_nosivost' => $upozorenje];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Provjerava i "troši" rezervaciju prilikom otpreme: potpuno poklapanje
     * količine označava rezervaciju kao iskorišćenu, djelimično je umanjuje
     * i ostavlja aktivnom za preostali dio.
     */
    private function potrosiRezervaciju(int $rezervacijaId, int $elementId, int $lokacijaId, int $investitorId, int $projekatId, float $kolicina): void
    {
        $stmt = $this->db->prepare('SELECT * FROM rezervacije WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $rezervacijaId]);
        $rez = $stmt->fetch();

        if (!$rez) {
            throw new RuntimeException('Izabrana rezervacija ne postoji.');
        }
        if ($rez['status'] !== 'aktivna') {
            throw new RuntimeException('Izabrana rezervacija više nije aktivna.');
        }
        if ((int) $rez['element_id'] !== $elementId || (int) $rez['lokacija_id'] !== $lokacijaId) {
            throw new RuntimeException('Izabrana rezervacija se ne odnosi na taj element/lokaciju.');
        }
        if (StanjeRepository::norm($rez['investitor_id']) !== $investitorId || StanjeRepository::norm($rez['projekat_id']) !== $projekatId) {
            throw new RuntimeException('Izabrana rezervacija pripada drugom investitoru/projektu od onog na otpremi.');
        }
        if ($kolicina > (float) $rez['kolicina']) {
            throw new RuntimeException(sprintf(
                'Otprema (%s) premašuje preostalu rezervisanu količinu (%s) za rezervaciju %s.',
                $kolicina,
                $rez['kolicina'],
                $rez['broj_rezervacije']
            ));
        }

        if (abs($kolicina - (float) $rez['kolicina']) < 0.0001) {
            $this->db->prepare('UPDATE rezervacije SET status = "iskoriscena" WHERE id = :id')->execute(['id' => $rezervacijaId]);
        } else {
            $preostalo = (float) $rez['kolicina'] - $kolicina;
            $this->db->prepare('UPDATE rezervacije SET kolicina = :k WHERE id = :id')->execute(['k' => $preostalo, 'id' => $rezervacijaId]);
        }
    }

    /**
     * Stornira otpremu — obavezan razlog. Vraća fizičko stanje koje je ta
     * otprema umanjila (po svakoj stavci, na istu investitor/projekat
     * traku), i po potrebi vraća povezanu rezervaciju u prethodno stanje:
     *   - ako je rezervacija njome u potpunosti iskorišćena ('iskoriscena'),
     *     vraća se na 'aktivna' (njena kolicina se NE mijenja — već
     *     odražava preostalo prije te otpreme, pogledaj potrosiRezervaciju());
     *   - ako je rezervacija njome samo djelimično umanjena (ostala
     *     'aktivna'), vraća joj se oduzeta količina;
     *   - ako je rezervacija u međuvremenu ručno otkazana ('otkazana'),
     *     ne dira se (korisnikova naknadna odluka ima prednost).
     * Za razliku od storna ulaza, ovdje nema scenarija odbijanja — vraćanje
     * (povećanje) stanja ne može proizvesti negativnu količinu.
     */
    public function storniraj(int $otpremaId, string $razlog, int $korisnikId): void
    {
        if (trim($razlog) === '') {
            throw new RuntimeException('Razlog storniranja je obavezan.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM otpreme WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $otpremaId]);
            $otprema = $stmt->fetch();

            if (!$otprema) {
                throw new RuntimeException('Otprema ne postoji.');
            }
            if ($otprema['status'] === 'stornirana') {
                throw new RuntimeException('Otprema je već stornirana.');
            }

            $stmtStavke = $this->db->prepare('SELECT * FROM otpreme_stavke WHERE otprema_id = :id');
            $stmtStavke->execute(['id' => $otpremaId]);
            $stavke = $stmtStavke->fetchAll();

            $stanjeRepo = new StanjeRepository();
            $karticaRepo = new SkladisnaKarticaRepository();

            $investitorId = StanjeRepository::norm($otprema['investitor_id']);
            $projekatId = StanjeRepository::norm($otprema['projekat_id']);

            foreach ($stavke as $s) {
                $stanjeNakon = $stanjeRepo->povecaj(
                    (int) $s['element_id'],
                    (int) $s['lokacija_id'],
                    (float) $s['kolicina'],
                    $investitorId,
                    $projekatId
                );

                if (!empty($s['rezervacija_id'])) {
                    $this->vratiRezervaciju((int) $s['rezervacija_id'], (float) $s['kolicina']);
                }

                $karticaRepo->upisi([
                    'element_id'    => $s['element_id'],
                    'lokacija_id'   => $s['lokacija_id'],
                    'investitor_id' => $otprema['investitor_id'],
                    'projekat_id'   => $otprema['projekat_id'],
                    'tip_promjene'  => 'storno_otpreme',
                    'kolicina'      => $s['kolicina'],
                    'stanje_nakon'  => $stanjeNakon,
                    'dokument_tip'  => 'otprema',
                    'dokument_id'   => $otpremaId,
                    'dokument_broj' => $otprema['broj_otpremnice'],
                    'korisnik_id'   => $korisnikId,
                ]);
            }

            $this->db->prepare(
                'UPDATE otpreme SET status = "stornirana", razlog_storna = :razlog,
                    datum_storniranja = NOW(), stornirao_korisnik_id = :korisnik_id
                 WHERE id = :id'
            )->execute(['razlog' => $razlog, 'korisnik_id' => $korisnikId, 'id' => $otpremaId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function vratiRezervaciju(int $rezervacijaId, float $vracenaKolicina): void
    {
        $stmt = $this->db->prepare('SELECT * FROM rezervacije WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $rezervacijaId]);
        $rez = $stmt->fetch();

        if (!$rez) {
            return; // rezervacija je u međuvremenu nestala — ništa za vratiti
        }

        if ($rez['status'] === 'iskoriscena') {
            $this->db->prepare('UPDATE rezervacije SET status = "aktivna" WHERE id = :id')->execute(['id' => $rezervacijaId]);
        } elseif ($rez['status'] === 'aktivna') {
            $this->db->prepare('UPDATE rezervacije SET kolicina = kolicina + :k WHERE id = :id')
                      ->execute(['k' => $vracenaKolicina, 'id' => $rezervacijaId]);
        }
        // status 'otkazana' (ručno otkazano nakon otpreme) se namjerno ne dira.
    }

    /** Za izvještaj "Otpreme po periodu" i "Ukupna otpremljena okvirna težina". */
    public function zaPeriod(string $od, string $do): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, i.naziv AS investitor_naziv, p.naziv AS projekat_naziv,
                    (SELECT COALESCE(SUM(os.ukupna_tezina_kg), 0) FROM otpreme_stavke os WHERE os.otprema_id = o.id) AS ukupna_tezina_kg,
                    (SELECT COALESCE(SUM(os.kolicina), 0) FROM otpreme_stavke os WHERE os.otprema_id = o.id) AS ukupno_komada
             FROM otpreme o
             LEFT JOIN investitori i ON i.id = o.investitor_id
             LEFT JOIN projekti p ON p.id = o.projekat_id
             WHERE o.datum BETWEEN :od AND :do
             ORDER BY o.datum, o.id"
        );
        $stmt->execute(['od' => $od, 'do' => $do]);
        return $stmt->fetchAll();
    }

    /** Za izvještaj "Po objektu" — zbir otpremljenih komada/težine grupisan po objektu, u periodu. */
    public function poObjektuZaPeriod(string $od, string $do): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.objekat_id, ob.naziv AS objekat_naziv, p.naziv AS projekat_naziv,
                    COALESCE(SUM(os.kolicina), 0) AS ukupno_komada,
                    COALESCE(SUM(os.ukupna_tezina_kg), 0) AS ukupna_tezina_kg
             FROM otpreme o
             JOIN otpreme_stavke os ON os.otprema_id = o.id
             LEFT JOIN objekti ob ON ob.id = o.objekat_id
             LEFT JOIN projekti p ON p.id = o.projekat_id
             WHERE o.datum BETWEEN :od AND :do
             GROUP BY o.objekat_id, ob.naziv, p.naziv
             ORDER BY ob.naziv IS NULL, ob.naziv"
        );
        $stmt->execute(['od' => $od, 'do' => $do]);
        return $stmt->fetchAll();
    }

    /** Za izvještaj "Po elementu" — ulaz/otprema u periodu i trenutno stanje, po AB elementu. */
    public function poElementuZaPeriod(string $od, string $do): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS element_id, e.sifra, e.naziv,
                    COALESCE((SELECT SUM(us.kolicina) FROM ulazi_stavke us
                              JOIN ulazi u ON u.id = us.ulaz_id
                              WHERE us.element_id = e.id AND u.datum BETWEEN :od1 AND :do1 AND u.status = 'aktivan'), 0) AS ulaz_period,
                    COALESCE((SELECT SUM(os.kolicina) FROM otpreme_stavke os
                              JOIN otpreme o ON o.id = os.otprema_id
                              WHERE os.element_id = e.id AND o.datum BETWEEN :od2 AND :do2), 0) AS otprema_period,
                    COALESCE((SELECT SUM(s.kolicina) FROM skladisno_stanje s WHERE s.element_id = e.id), 0) AS trenutno_stanje
             FROM ab_elementi e
             ORDER BY e.sifra"
        );
        $stmt->execute(['od1' => $od, 'do1' => $do, 'od2' => $od, 'do2' => $do]);
        return array_filter($stmt->fetchAll(), fn($r) => $r['ulaz_period'] > 0 || $r['otprema_period'] > 0 || $r['trenutno_stanje'] > 0);
    }

    /** Za izvještaj "Ukupna otpremljena okvirna težina" — zbir po mjesecima za izabranu godinu. */
    public function tezinaPoMjesecima(int $godina): array
    {
        $stmt = $this->db->prepare(
            "SELECT MONTH(o.datum) AS mjesec,
                    COALESCE(SUM(os.ukupna_tezina_kg), 0) AS ukupna_tezina_kg,
                    COALESCE(SUM(os.kolicina), 0) AS ukupno_komada
             FROM otpreme o
             JOIN otpreme_stavke os ON os.otprema_id = o.id
             WHERE YEAR(o.datum) = :godina
             GROUP BY MONTH(o.datum)
             ORDER BY mjesec"
        );
        $stmt->execute(['godina' => $godina]);
        $poMjesecu = [];
        foreach ($stmt->fetchAll() as $red) {
            $poMjesecu[(int) $red['mjesec']] = $red;
        }
        return $poMjesecu;
    }
}
