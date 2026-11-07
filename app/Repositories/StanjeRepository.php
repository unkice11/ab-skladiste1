<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Stanje skladišta — fizička količina po (element, lokacija, investitor,
 * projekat). investitor_id/projekat_id = 0 znači "generički, bez
 * investitora/projekta" (NULL ne može biti dio PRIMARY KEY-a u MySQL-u,
 * pa se 0 koristi kao "prazna" vrijednost svuda u ovoj klasi).
 *
 * Ova klasa ne otvara/zatvara transakcije sama — to radi pozivalac
 * (UlazRepository/RezervacijaRepository/PremjestanjeRepository), jer
 * promjena stanja mora biti atomična zajedno sa kreiranjem dokumenta i
 * upisom u skladišnu karticu.
 */
class StanjeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    /** Pretvara nullable investitor/projekat ID u 0 (konvencija ove tabele). */
    public static function norm(?int $id): int
    {
        return $id ?: 0;
    }

    /** Pregled stanja sa pretragom (šifra/naziv elementa) i paginacijom. */
    public function pregled(string $pretraga = '', int $stranica = 1, int $poStranici = 20): array
    {
        $stranica = max(1, $stranica);
        $offset = ($stranica - 1) * $poStranici;

        $uslov = 'WHERE s.kolicina > 0';
        $parametri = [];
        if ($pretraga !== '') {
            $uslov .= ' AND (e.sifra LIKE :p1 OR e.naziv LIKE :p2)';
            $parametri['p1'] = '%' . $pretraga . '%';
            $parametri['p2'] = '%' . $pretraga . '%';
        }

        $stmtBroj = $this->db->prepare(
            "SELECT COUNT(*) AS ukupno
             FROM skladisno_stanje s
             JOIN ab_elementi e ON e.id = s.element_id
             $uslov"
        );
        $stmtBroj->execute($parametri);
        $ukupno = (int) $stmtBroj->fetch()['ukupno'];

        $sql = "SELECT s.element_id, s.lokacija_id, s.investitor_id, s.projekat_id, s.kolicina,
                       e.sifra, e.naziv,
                       l.oznaka AS lokacija_oznaka, l.zona,
                       sk.naziv AS skladiste_naziv,
                       inv.naziv AS investitor_naziv,
                       proj.naziv AS projekat_naziv,
                       (SELECT COALESCE(SUM(r.kolicina), 0) FROM rezervacije r
                        WHERE r.element_id = s.element_id AND r.lokacija_id = s.lokacija_id
                          AND COALESCE(r.investitor_id, 0) = s.investitor_id
                          AND COALESCE(r.projekat_id, 0) = s.projekat_id
                          AND r.status = 'aktivna'
                       ) AS rezervisano
                FROM skladisno_stanje s
                JOIN ab_elementi e ON e.id = s.element_id
                JOIN lokacije l ON l.id = s.lokacija_id
                JOIN skladista sk ON sk.id = l.skladiste_id
                LEFT JOIN investitori inv ON inv.id = s.investitor_id
                LEFT JOIN projekti proj ON proj.id = s.projekat_id
                $uslov
                ORDER BY e.sifra, sk.naziv, l.oznaka
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($parametri as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $poStranici, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $stavke = $stmt->fetchAll();

        // Okvirna težina po komadu se računa u AbElementiRepository (nije
        // gotova kolona u bazi) — dohvatimo je po elementu (mala keš mapa
        // da se izbjegne ponovni upit za isti element na više redova).
        $elementiRepo = new AbElementiRepository();
        $kesTezina = [];
        foreach ($stavke as &$red) {
            if (!isset($kesTezina[$red['element_id']])) {
                $el = $elementiRepo->pronadjiPoId((int) $red['element_id']);
                $kesTezina[$red['element_id']] = $el['okvirna_tezina_kg'] ?? 0;
            }
            $red['okvirna_tezina_kg'] = $kesTezina[$red['element_id']];
            $red['rezervisano'] = (float) $red['rezervisano'];
            $red['slobodno'] = (float) $red['kolicina'] - $red['rezervisano'];
            $red['ukupna_tezina_kg'] = round((float) $red['kolicina'] * (float) $red['okvirna_tezina_kg'], 2);
        }

        return [
            'stavke' => $stavke,
            'ukupno' => $ukupno,
            'stranica' => $stranica,
            'ukupno_stranica' => max(1, (int) ceil($ukupno / $poStranici)),
        ];
    }

    /** Fizička količina na (element, lokacija, investitor, projekat), sa zaključavanjem reda. */
    public function kolicinaZaAzuriranje(int $elementId, int $lokacijaId, int $investitorId = 0, int $projekatId = 0): float
    {
        $stmt = $this->db->prepare(
            'SELECT kolicina FROM skladisno_stanje
             WHERE element_id = :e AND lokacija_id = :l AND investitor_id = :i AND projekat_id = :p
             FOR UPDATE'
        );
        $stmt->execute(['e' => $elementId, 'l' => $lokacijaId, 'i' => $investitorId, 'p' => $projekatId]);
        $red = $stmt->fetch();
        return $red ? (float) $red['kolicina'] : 0.0;
    }

    public function rezervisanoZaAzuriranje(int $elementId, int $lokacijaId, int $investitorId = 0, int $projekatId = 0): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(kolicina), 0) AS ukupno FROM rezervacije
             WHERE element_id = :e AND lokacija_id = :l
               AND COALESCE(investitor_id, 0) = :i AND COALESCE(projekat_id, 0) = :p
               AND status = 'aktivna'
             FOR UPDATE"
        );
        $stmt->execute(['e' => $elementId, 'l' => $lokacijaId, 'i' => $investitorId, 'p' => $projekatId]);
        return (float) $stmt->fetch()['ukupno'];
    }

    /** Povećava stanje (ulaz, premještanje-u). Kreira red ako ne postoji. */
    public function povecaj(int $elementId, int $lokacijaId, float $kolicina, int $investitorId = 0, int $projekatId = 0): float
    {
        $trenutno = $this->kolicinaZaAzuriranje($elementId, $lokacijaId, $investitorId, $projekatId);
        $novo = $trenutno + $kolicina;

        $stmt = $this->db->prepare(
            'INSERT INTO skladisno_stanje (element_id, lokacija_id, investitor_id, projekat_id, kolicina)
             VALUES (:e, :l, :i, :p, :k)
             ON DUPLICATE KEY UPDATE kolicina = :k2'
        );
        $stmt->execute([
            'e' => $elementId, 'l' => $lokacijaId, 'i' => $investitorId, 'p' => $projekatId,
            'k' => $novo, 'k2' => $novo,
        ]);

        return $novo;
    }

    /**
     * Smanjuje stanje (izlaz, premještanje-iz). Baca izuzetak ako bi
     * rezultat bio negativan — pravilo iz spec-a (nije dozvoljeno
     * negativno fizičko stanje). Pozivalac je odgovoran za rollback.
     */
    public function smanji(int $elementId, int $lokacijaId, float $kolicina, int $investitorId = 0, int $projekatId = 0): float
    {
        $trenutno = $this->kolicinaZaAzuriranje($elementId, $lokacijaId, $investitorId, $projekatId);
        $novo = $trenutno - $kolicina;

        if ($novo < 0) {
            throw new RuntimeException(
                sprintf('Nedovoljno stanje na toj investitor/projekat traci: dostupno %s, traženo %s.', $trenutno, $kolicina)
            );
        }

        $this->db->prepare(
            'UPDATE skladisno_stanje SET kolicina = :k
             WHERE element_id = :e AND lokacija_id = :l AND investitor_id = :i AND projekat_id = :p'
        )->execute(['k' => $novo, 'e' => $elementId, 'l' => $lokacijaId, 'i' => $investitorId, 'p' => $projekatId]);

        return $novo;
    }

    /** Agregatni brojevi za dashboard: ukupno komada na stanju i ukupna okvirna težina. */
    public function ukupnoStanje(): array
    {
        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT s.element_id) AS broj_elemenata,
                    COALESCE(SUM(s.kolicina), 0) AS ukupno_komada
             FROM skladisno_stanje s WHERE s.kolicina > 0"
        );
        $red = $stmt->fetch();

        // Ukupna okvirna težina se računa po elementu (jer okvirna_tezina_kg
        // nije kolona u bazi) — zbir (kolicina_na_svim_redovima * kg/kom).
        $stmtPoElementu = $this->db->query(
            "SELECT element_id, SUM(kolicina) AS kolicina FROM skladisno_stanje WHERE kolicina > 0 GROUP BY element_id"
        );
        $elementiRepo = new AbElementiRepository();
        $ukupnaTezina = 0.0;
        foreach ($stmtPoElementu->fetchAll() as $red2) {
            $el = $elementiRepo->pronadjiPoId((int) $red2['element_id']);
            $ukupnaTezina += ((float) $red2['kolicina']) * ($el['okvirna_tezina_kg'] ?? 0);
        }

        return [
            'broj_elemenata' => (int) $red['broj_elemenata'],
            'ukupno_komada'  => (float) $red['ukupno_komada'],
            'ukupna_tezina'  => round($ukupnaTezina, 2),
        ];
    }

    /** Ukupno rezervisano (svih aktivnih rezervacija, svi elementi/lokacije). */
    public function ukupnoRezervisano(): float
    {
        $stmt = $this->db->query("SELECT COALESCE(SUM(kolicina), 0) FROM rezervacije WHERE status = 'aktivna'");
        return (float) $stmt->fetchColumn();
    }

    /** Za izvještaj "Po investitoru" — zbir stanja grupisan po investitoru (0/generičko posebno). */
    public function poInvestitoru(): array
    {
        $stmt = $this->db->query(
            "SELECT s.investitor_id, inv.naziv AS investitor_naziv,
                    COALESCE(SUM(s.kolicina), 0) AS ukupno_komada
             FROM skladisno_stanje s
             LEFT JOIN investitori inv ON inv.id = s.investitor_id
             WHERE s.kolicina > 0
             GROUP BY s.investitor_id, inv.naziv
             ORDER BY inv.naziv IS NULL, inv.naziv"
        );
        $redovi = $stmt->fetchAll();

        $elementiRepo = new AbElementiRepository();
        // Za tačnu ukupnu težinu po investitoru moramo znati kg/kom po elementu —
        // ponovo grupišemo, ovaj put uz element_id, pa sabiramo težinu u PHP-u.
        $stmtDetalj = $this->db->query(
            "SELECT investitor_id, element_id, SUM(kolicina) AS kolicina
             FROM skladisno_stanje WHERE kolicina > 0
             GROUP BY investitor_id, element_id"
        );
        $tezinaPoInvestitoru = [];
        foreach ($stmtDetalj->fetchAll() as $red) {
            $el = $elementiRepo->pronadjiPoId((int) $red['element_id']);
            $kljuc = (int) $red['investitor_id'];
            $tezinaPoInvestitoru[$kljuc] = ($tezinaPoInvestitoru[$kljuc] ?? 0) + ((float) $red['kolicina'] * ($el['okvirna_tezina_kg'] ?? 0));
        }

        foreach ($redovi as &$red) {
            $red['ukupna_tezina_kg'] = round($tezinaPoInvestitoru[(int) $red['investitor_id']] ?? 0, 2);
        }

        return $redovi;
    }

    /** Za izvještaj "Po lokaciji" — zbir komada/težine po lokaciji (svi elementi zajedno). */
    public function poLokaciji(): array
    {
        $stmt = $this->db->query(
            "SELECT s.lokacija_id, l.oznaka AS lokacija_oznaka, l.zona, sk.naziv AS skladiste_naziv,
                    SUM(s.kolicina) AS ukupno_komada
             FROM skladisno_stanje s
             JOIN lokacije l ON l.id = s.lokacija_id
             JOIN skladista sk ON sk.id = l.skladiste_id
             WHERE s.kolicina > 0
             GROUP BY s.lokacija_id, l.oznaka, l.zona, sk.naziv
             ORDER BY sk.naziv, l.zona, l.oznaka"
        );
        $redovi = $stmt->fetchAll();

        $stmtDetalj = $this->db->query(
            "SELECT lokacija_id, element_id, SUM(kolicina) AS kolicina
             FROM skladisno_stanje WHERE kolicina > 0
             GROUP BY lokacija_id, element_id"
        );
        $elementiRepo = new AbElementiRepository();
        $tezinaPoLokaciji = [];
        foreach ($stmtDetalj->fetchAll() as $red) {
            $el = $elementiRepo->pronadjiPoId((int) $red['element_id']);
            $kljuc = (int) $red['lokacija_id'];
            $tezinaPoLokaciji[$kljuc] = ($tezinaPoLokaciji[$kljuc] ?? 0) + ((float) $red['kolicina'] * ($el['okvirna_tezina_kg'] ?? 0));
        }

        foreach ($redovi as &$red) {
            $red['ukupna_tezina_kg'] = round($tezinaPoLokaciji[(int) $red['lokacija_id']] ?? 0, 2);
        }

        return $redovi;
    }
}
