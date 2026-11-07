<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AbElementiRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    /**
     * @param array $filteri opciono ['investitor_id' => int, 'projekat_id' => int]
     */
    public function lista(string $pretraga = '', int $stranica = 1, int $poStranici = 20, array $filteri = []): array
    {
        $stranica = max(1, $stranica);
        $offset = ($stranica - 1) * $poStranici;

        $uslovi = [];
        $parametri = [];
        if ($pretraga !== '') {
            $uslovi[] = '(t.sifra LIKE :p1 OR t.naziv LIKE :p2)';
            $parametri['p1'] = '%' . $pretraga . '%';
            $parametri['p2'] = '%' . $pretraga . '%';
        }
        if (!empty($filteri['investitor_id'])) {
            $uslovi[] = 't.investitor_id = :investitor_id';
            $parametri['investitor_id'] = (int) $filteri['investitor_id'];
        }
        if (!empty($filteri['projekat_id'])) {
            $uslovi[] = 't.projekat_id = :projekat_id';
            $parametri['projekat_id'] = (int) $filteri['projekat_id'];
        }
        $uslov = $uslovi ? 'WHERE ' . implode(' AND ', $uslovi) : '';

        $stmtBroj = $this->db->prepare("SELECT COUNT(*) AS ukupno FROM ab_elementi t $uslov");
        $stmtBroj->execute($parametri);
        $ukupno = (int) $stmtBroj->fetch()['ukupno'];

        $sql = "SELECT t.*, k.naziv AS kategorija_naziv, i.naziv AS investitor_naziv, p.naziv AS projekat_naziv
                FROM ab_elementi t
                LEFT JOIN kategorije_elemenata k ON k.id = t.kategorija_id
                LEFT JOIN investitori i ON i.id = t.investitor_id
                LEFT JOIN projekti p ON p.id = t.projekat_id
                $uslov
                ORDER BY t.sifra
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($parametri as $kljuc => $vrijednost) {
            $stmt->bindValue($kljuc, $vrijednost);
        }
        $stmt->bindValue('limit', $poStranici, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $stavke = array_map([$this, 'dodajObracunatePolja'], $stmt->fetchAll());

        return [
            'stavke' => $stavke,
            'ukupno' => $ukupno,
            'stranica' => $stranica,
            'po_stranici' => $poStranici,
            'ukupno_stranica' => max(1, (int) ceil($ukupno / $poStranici)),
        ];
    }

    public function pronadjiPoId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, i.naziv AS investitor_naziv, p.naziv AS projekat_naziv
             FROM ab_elementi t
             LEFT JOIN investitori i ON i.id = t.investitor_id
             LEFT JOIN projekti p ON p.id = t.projekat_id
             WHERE t.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $red = $stmt->fetch();
        return $red ? $this->dodajObracunatePolja($red) : null;
    }

    /**
     * Zapremina i okvirna težina se NE čuvaju u bazi kao gotove vrijednosti
     * (osim ručno unesenih brojeva kad su prekidači isključeni) — računaju
     * se pri svakom čitanju, kako bi izmjena dimenzija ili standardne
     * gustine odmah bila tačna.
     *
     * Zapremina i težina imaju NEZAVISNE prekidače automatski/ručno:
     * element može imati automatski obračun zapremine ali ručnu težinu
     * (npr. armatura mijenja stvarnu masu), ili ručnu zapreminu (izbočine/
     * udubljenja) a automatski obračun težine iz te (ručne) zapremine.
     */
    private function dodajObracunatePolja(array $red): array
    {
        $duzina = (float) $red['duzina_m'];
        $sirina = (float) $red['sirina_m'];
        $visina = (float) $red['visina_m'];
        $gustina = (float) $red['gustina_kg_m3'];

        if ((int) $red['automatski_obracun_zapremine'] === 1) {
            $zapremina = $duzina * $sirina * $visina;
        } else {
            $zapremina = (float) ($red['rucna_zapremina_m3'] ?? 0);
        }

        if ((int) $red['automatski_obracun_tezine'] === 1) {
            $okvirnaTezina = $zapremina * $gustina;
        } else {
            $okvirnaTezina = (float) ($red['rucna_tezina_kg'] ?? 0);
        }

        $red['zapremina_m3'] = round($zapremina, 4);
        $red['okvirna_tezina_kg'] = round($okvirnaTezina, 2);

        return $red;
    }

    public function kreiraj(array $podaci): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ab_elementi
                (sifra, naziv, kategorija_id, investitor_id, projekat_id, opis, duzina_m, sirina_m, visina_m, gustina_kg_m3,
                 automatski_obracun_zapremine, rucna_zapremina_m3,
                 automatski_obracun_tezine, rucna_tezina_kg, aktivan)
             VALUES
                (:sifra, :naziv, :kategorija_id, :investitor_id, :projekat_id, :opis, :duzina_m, :sirina_m, :visina_m, :gustina_kg_m3,
                 :automatski_obracun_zapremine, :rucna_zapremina_m3,
                 :automatski_obracun_tezine, :rucna_tezina_kg, 1)'
        );
        $stmt->execute($this->pripremiZaBazu($podaci));
        return (int) $this->db->lastInsertId();
    }

    public function azuriraj(int $id, array $podaci): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ab_elementi SET
                sifra = :sifra, naziv = :naziv, kategorija_id = :kategorija_id,
                investitor_id = :investitor_id, projekat_id = :projekat_id, opis = :opis,
                duzina_m = :duzina_m, sirina_m = :sirina_m, visina_m = :visina_m,
                gustina_kg_m3 = :gustina_kg_m3,
                automatski_obracun_zapremine = :automatski_obracun_zapremine, rucna_zapremina_m3 = :rucna_zapremina_m3,
                automatski_obracun_tezine = :automatski_obracun_tezine, rucna_tezina_kg = :rucna_tezina_kg
             WHERE id = :id'
        );
        $stmt->execute($this->pripremiZaBazu($podaci) + ['id' => $id]);
    }

    private function pripremiZaBazu(array $p): array
    {
        return [
            'sifra'                        => $p['sifra'],
            'naziv'                        => $p['naziv'],
            'kategorija_id'                => $p['kategorija_id'] ?: null,
            'investitor_id'                => $p['investitor_id'] ?: null,
            'projekat_id'                  => $p['projekat_id'] ?: null,
            'opis'                         => $p['opis'] ?? null,
            'duzina_m'                     => $p['duzina_m'],
            'sirina_m'                     => $p['sirina_m'],
            'visina_m'                     => $p['visina_m'],
            'gustina_kg_m3'                => $p['gustina_kg_m3'],
            'automatski_obracun_zapremine' => $p['automatski_obracun_zapremine'] ? 1 : 0,
            'rucna_zapremina_m3'           => $p['automatski_obracun_zapremine'] ? null : $p['rucna_zapremina_m3'],
            'automatski_obracun_tezine'    => $p['automatski_obracun_tezine'] ? 1 : 0,
            'rucna_tezina_kg'              => $p['automatski_obracun_tezine'] ? null : $p['rucna_tezina_kg'],
        ];
    }

    public function postaviAktivnost(int $id, bool $aktivan): void
    {
        $stmt = $this->db->prepare('UPDATE ab_elementi SET aktivan = :a WHERE id = :id');
        $stmt->execute(['a' => $aktivan ? 1 : 0, 'id' => $id]);
    }

    public function pronadjiPoSifri(string $sifra): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ab_elementi WHERE sifra = :s LIMIT 1');
        $stmt->execute(['s' => $sifra]);
        $red = $stmt->fetch();
        return $red ?: null;
    }
}
