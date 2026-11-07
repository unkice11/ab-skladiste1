<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

class UlazRepository
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
            $uslov = 'WHERE u.broj_ulaza LIKE :p';
            $parametri['p'] = '%' . $pretraga . '%';
        }

        $stmtBroj = $this->db->prepare("SELECT COUNT(*) AS ukupno FROM ulazi u $uslov");
        $stmtBroj->execute($parametri);
        $ukupno = (int) $stmtBroj->fetch()['ukupno'];

        $stmt = $this->db->prepare(
            "SELECT u.*, s.naziv AS skladiste_naziv, i.naziv AS investitor_naziv, k.korisnicko_ime
             FROM ulazi u
             JOIN skladista s ON s.id = u.skladiste_id
             LEFT JOIN investitori i ON i.id = u.investitor_id
             LEFT JOIN korisnici k ON k.id = u.korisnik_id
             $uslov
             ORDER BY u.id DESC
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
            'SELECT u.*, s.naziv AS skladiste_naziv, i.naziv AS investitor_naziv,
                    p.naziv AS projekat_naziv, o.naziv AS objekat_naziv
             FROM ulazi u
             JOIN skladista s ON s.id = u.skladiste_id
             LEFT JOIN investitori i ON i.id = u.investitor_id
             LEFT JOIN projekti p ON p.id = u.projekat_id
             LEFT JOIN objekti o ON o.id = u.objekat_id
             WHERE u.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $red = $stmt->fetch();
        if (!$red) {
            return null;
        }

        $stmtStavke = $this->db->prepare(
            'SELECT us.*, e.sifra, e.naziv AS element_naziv, l.oznaka AS lokacija_oznaka
             FROM ulazi_stavke us
             JOIN ab_elementi e ON e.id = us.element_id
             JOIN lokacije l ON l.id = us.lokacija_id
             WHERE us.ulaz_id = :id'
        );
        $stmtStavke->execute(['id' => $id]);
        $red['stavke'] = $stmtStavke->fetchAll();

        return $red;
    }

    /**
     * Kreira ulaz sa stavkama, unutar jedne transakcije:
     * generiše broj dokumenta, upisuje header i stavke, povećava
     * skladišno stanje po svakoj stavci i upisuje skladišnu karticu.
     *
     * @param array $header ['datum','skladiste_id','investitor_id','projekat_id','objekat_id','napomena','korisnik_id']
     * @param array $stavke [['element_id'=>int,'lokacija_id'=>int,'kolicina'=>float], ...]
     * @return array{id:int, broj_ulaza:string}
     */
    public function kreiraj(array $header, array $stavke): array
    {
        $this->db->beginTransaction();
        try {
            $broj = (new NumeracijaRepository())->sljedeciBroj('UL', (int) date('Y', strtotime($header['datum'])));

            $stmt = $this->db->prepare(
                'INSERT INTO ulazi (broj_ulaza, datum, skladiste_id, investitor_id, projekat_id, objekat_id, napomena, korisnik_id)
                 VALUES (:broj, :datum, :skladiste_id, :investitor_id, :projekat_id, :objekat_id, :napomena, :korisnik_id)'
            );
            $stmt->execute([
                'broj'          => $broj,
                'datum'         => $header['datum'],
                'skladiste_id'  => $header['skladiste_id'],
                'investitor_id' => $header['investitor_id'] ?: null,
                'projekat_id'   => $header['projekat_id'] ?: null,
                'objekat_id'    => $header['objekat_id'] ?: null,
                'napomena'      => $header['napomena'] ?: null,
                'korisnik_id'   => $header['korisnik_id'],
            ]);
            $ulazId = (int) $this->db->lastInsertId();

            $stanjeRepo = new StanjeRepository();
            $karticaRepo = new SkladisnaKarticaRepository();

            $investitorId = StanjeRepository::norm($header['investitor_id'] ?? null);
            $projekatId = StanjeRepository::norm($header['projekat_id'] ?? null);

            $insertStavka = $this->db->prepare(
                'INSERT INTO ulazi_stavke (ulaz_id, element_id, lokacija_id, kolicina) VALUES (:ulaz_id, :element_id, :lokacija_id, :kolicina)'
            );

            foreach ($stavke as $s) {
                $insertStavka->execute([
                    'ulaz_id'     => $ulazId,
                    'element_id'  => $s['element_id'],
                    'lokacija_id' => $s['lokacija_id'],
                    'kolicina'    => $s['kolicina'],
                ]);

                $novoStanje = $stanjeRepo->povecaj(
                    (int) $s['element_id'],
                    (int) $s['lokacija_id'],
                    (float) $s['kolicina'],
                    $investitorId,
                    $projekatId
                );

                $karticaRepo->upisi([
                    'element_id'    => $s['element_id'],
                    'lokacija_id'   => $s['lokacija_id'],
                    'investitor_id' => $header['investitor_id'] ?: null,
                    'projekat_id'   => $header['projekat_id'] ?: null,
                    'tip_promjene'  => 'ulaz',
                    'kolicina'      => $s['kolicina'],
                    'stanje_nakon'  => $novoStanje,
                    'dokument_tip'  => 'ulaz',
                    'dokument_id'   => $ulazId,
                    'dokument_broj' => $broj,
                    'korisnik_id'   => $header['korisnik_id'],
                ]);
            }

            $this->db->commit();

            return ['id' => $ulazId, 'broj_ulaza' => $broj];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Stornira ulaz — obavezan razlog. Poništava fizičko stanje koje je
     * taj ulaz unio (po svakoj stavci), uz istu investitor/projekat traku
     * kojom je stanje i povećano. Ako je međuvremenom dio te robe već
     * premješten/rezervisan/otpremljen tako da stanje na toj traci više
     * nije dovoljno da se ulaz u potpunosti poništi, storno se ODBIJA
     * (baca RuntimeException) — ništa se ne mijenja napola.
     */
    public function storniraj(int $ulazId, string $razlog, int $korisnikId): void
    {
        if (trim($razlog) === '') {
            throw new RuntimeException('Razlog storniranja je obavezan.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM ulazi WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $ulazId]);
            $ulaz = $stmt->fetch();

            if (!$ulaz) {
                throw new RuntimeException('Ulaz ne postoji.');
            }
            if ($ulaz['status'] === 'storniran') {
                throw new RuntimeException('Ulaz je već storniran.');
            }

            $stmtStavke = $this->db->prepare('SELECT * FROM ulazi_stavke WHERE ulaz_id = :id');
            $stmtStavke->execute(['id' => $ulazId]);
            $stavke = $stmtStavke->fetchAll();

            $stanjeRepo = new StanjeRepository();
            $karticaRepo = new SkladisnaKarticaRepository();

            $investitorId = StanjeRepository::norm($ulaz['investitor_id']);
            $projekatId = StanjeRepository::norm($ulaz['projekat_id']);

            foreach ($stavke as $s) {
                // smanji() baca RuntimeException ako bi stanje palo ispod 0 —
                // to automatski pokriva pravilo "ne može se stornirati ako je
                // roba u međuvremenu premještena/otpremljena van te trake".
                $stanjeNakon = $stanjeRepo->smanji(
                    (int) $s['element_id'],
                    (int) $s['lokacija_id'],
                    (float) $s['kolicina'],
                    $investitorId,
                    $projekatId
                );

                $karticaRepo->upisi([
                    'element_id'    => $s['element_id'],
                    'lokacija_id'   => $s['lokacija_id'],
                    'investitor_id' => $ulaz['investitor_id'],
                    'projekat_id'   => $ulaz['projekat_id'],
                    'tip_promjene'  => 'storno_ulaza',
                    'kolicina'      => -1 * (float) $s['kolicina'],
                    'stanje_nakon'  => $stanjeNakon,
                    'dokument_tip'  => 'ulaz',
                    'dokument_id'   => $ulazId,
                    'dokument_broj' => $ulaz['broj_ulaza'],
                    'korisnik_id'   => $korisnikId,
                ]);
            }

            $this->db->prepare(
                'UPDATE ulazi SET status = "storniran", razlog_storna = :razlog,
                    datum_storniranja = NOW(), stornirao_korisnik_id = :korisnik_id
                 WHERE id = :id'
            )->execute(['razlog' => $razlog, 'korisnik_id' => $korisnikId, 'id' => $ulazId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Za izvještaj "Ulaz po periodu" — samo aktivni (ne stornirani) ulazi u [od, do]. */
    public function zaPeriod(string $od, string $do): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, s.naziv AS skladiste_naziv, i.naziv AS investitor_naziv
             FROM ulazi u
             JOIN skladista s ON s.id = u.skladiste_id
             LEFT JOIN investitori i ON i.id = u.investitor_id
             WHERE u.datum BETWEEN :od AND :do
             ORDER BY u.datum, u.id"
        );
        $stmt->execute(['od' => $od, 'do' => $do]);
        return $stmt->fetchAll();
    }
}
