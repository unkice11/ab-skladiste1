<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

class RezervacijaRepository
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
            $uslov = 'WHERE r.broj_rezervacije LIKE :p1 OR e.sifra LIKE :p2 OR e.naziv LIKE :p3';
            $parametri['p1'] = '%' . $pretraga . '%';
            $parametri['p2'] = '%' . $pretraga . '%';
            $parametri['p3'] = '%' . $pretraga . '%';
        }

        $stmtBroj = $this->db->prepare(
            "SELECT COUNT(*) AS ukupno FROM rezervacije r JOIN ab_elementi e ON e.id = r.element_id $uslov"
        );
        $stmtBroj->execute($parametri);
        $ukupno = (int) $stmtBroj->fetch()['ukupno'];

        $stmt = $this->db->prepare(
            "SELECT r.*, e.sifra, e.naziv AS element_naziv, l.oznaka AS lokacija_oznaka,
                    i.naziv AS investitor_naziv, p.naziv AS projekat_naziv, o.naziv AS objekat_naziv
             FROM rezervacije r
             JOIN ab_elementi e ON e.id = r.element_id
             JOIN lokacije l ON l.id = r.lokacija_id
             LEFT JOIN investitori i ON i.id = r.investitor_id
             LEFT JOIN projekti p ON p.id = r.projekat_id
             LEFT JOIN objekti o ON o.id = r.objekat_id
             $uslov
             ORDER BY r.id DESC
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
        $stmt = $this->db->prepare('SELECT * FROM rezervacije WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $red = $stmt->fetch();
        return $red ?: null;
    }

    /**
     * @param array $podaci ['element_id','lokacija_id','kolicina','investitor_id','projekat_id','objekat_id','napomena','korisnik_id']
     * @return array{id:int, broj_rezervacije:string}
     */
    public function kreiraj(array $podaci): array
    {
        $this->db->beginTransaction();
        try {
            $stanjeRepo = new StanjeRepository();

            $investitorId = StanjeRepository::norm($podaci['investitor_id'] ?? null);
            $projekatId = StanjeRepository::norm($podaci['projekat_id'] ?? null);

            $fizicko = $stanjeRepo->kolicinaZaAzuriranje((int) $podaci['element_id'], (int) $podaci['lokacija_id'], $investitorId, $projekatId);
            $vecRezervisano = $stanjeRepo->rezervisanoZaAzuriranje((int) $podaci['element_id'], (int) $podaci['lokacija_id'], $investitorId, $projekatId);
            $slobodno = $fizicko - $vecRezervisano;

            if ((float) $podaci['kolicina'] > $slobodno) {
                throw new RuntimeException(
                    sprintf(
                        'Rezervacija premašuje slobodnu količinu na toj investitor/projekat traci (slobodno: %s, traženo: %s).',
                        $slobodno,
                        $podaci['kolicina']
                    )
                );
            }

            $broj = (new NumeracijaRepository())->sljedeciBroj('REZ', (int) date('Y'));

            $stmt = $this->db->prepare(
                'INSERT INTO rezervacije
                    (broj_rezervacije, element_id, lokacija_id, kolicina, investitor_id, projekat_id, objekat_id, napomena, status, korisnik_id)
                 VALUES
                    (:broj, :element_id, :lokacija_id, :kolicina, :investitor_id, :projekat_id, :objekat_id, :napomena, "aktivna", :korisnik_id)'
            );
            $stmt->execute([
                'broj'          => $broj,
                'element_id'    => $podaci['element_id'],
                'lokacija_id'   => $podaci['lokacija_id'],
                'kolicina'      => $podaci['kolicina'],
                'investitor_id' => $podaci['investitor_id'] ?: null,
                'projekat_id'   => $podaci['projekat_id'] ?: null,
                'objekat_id'    => $podaci['objekat_id'] ?: null,
                'napomena'      => $podaci['napomena'] ?: null,
                'korisnik_id'   => $podaci['korisnik_id'],
            ]);
            $id = (int) $this->db->lastInsertId();

            (new SkladisnaKarticaRepository())->upisi([
                'element_id'    => $podaci['element_id'],
                'lokacija_id'   => $podaci['lokacija_id'],
                'investitor_id' => $podaci['investitor_id'] ?: null,
                'projekat_id'   => $podaci['projekat_id'] ?: null,
                'tip_promjene'  => 'rezervacija',
                'kolicina'      => $podaci['kolicina'],
                'stanje_nakon'  => null, // rezervacija ne mijenja fizičko stanje
                'dokument_tip'  => 'rezervacija',
                'dokument_id'   => $id,
                'dokument_broj' => $broj,
                'korisnik_id'   => $podaci['korisnik_id'],
            ]);

            $this->db->commit();

            return ['id' => $id, 'broj_rezervacije' => $broj];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function otkazi(int $id, int $korisnikId): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM rezervacije WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $rezervacija = $stmt->fetch();

            if (!$rezervacija) {
                throw new RuntimeException('Rezervacija ne postoji.');
            }
            if ($rezervacija['status'] !== 'aktivna') {
                throw new RuntimeException('Rezervacija je već otkazana.');
            }

            $this->db->prepare('UPDATE rezervacije SET status = "otkazana", datum_otkazivanja = NOW() WHERE id = :id')
                      ->execute(['id' => $id]);

            (new SkladisnaKarticaRepository())->upisi([
                'element_id'    => $rezervacija['element_id'],
                'lokacija_id'   => $rezervacija['lokacija_id'],
                'investitor_id' => $rezervacija['investitor_id'],
                'projekat_id'   => $rezervacija['projekat_id'],
                'tip_promjene'  => 'oslobadjanje_rezervacije',
                'kolicina'      => $rezervacija['kolicina'],
                'stanje_nakon'  => null,
                'dokument_tip'  => 'rezervacija',
                'dokument_id'   => $id,
                'dokument_broj' => $rezervacija['broj_rezervacije'],
                'korisnik_id'   => $korisnikId,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Za izvještaj "Rezervisani elementi" — sve aktivne rezervacije. */
    public function aktivne(): array
    {
        $stmt = $this->db->query(
            "SELECT r.*, e.sifra, e.naziv AS element_naziv, l.oznaka AS lokacija_oznaka,
                    i.naziv AS investitor_naziv, p.naziv AS projekat_naziv
             FROM rezervacije r
             JOIN ab_elementi e ON e.id = r.element_id
             JOIN lokacije l ON l.id = r.lokacija_id
             LEFT JOIN investitori i ON i.id = r.investitor_id
             LEFT JOIN projekti p ON p.id = r.projekat_id
             WHERE r.status = 'aktivna'
             ORDER BY r.broj_rezervacije"
        );
        return $stmt->fetchAll();
    }
}
