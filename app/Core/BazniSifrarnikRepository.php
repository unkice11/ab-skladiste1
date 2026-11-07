<?php

namespace App\Core;

use PDO;

/**
 * Generički repository za jednostavne šifrarnike (naziv/opis/aktivan +
 * eventualno FK polja). Umjesto da se za svaki od devet šifrarnika piše
 * skoro identičan CRUD, konfiguracija (tabela, kolone za pretragu, FK
 * spajanja za prikaz naziva umjesto ID-a) se predaje kroz konstruktor.
 *
 * Elementi (koji imaju obračun zapremine/težine) NE koriste ovaj
 * repository — imaju svoj AbElementiRepository.
 */
class BazniSifrarnikRepository
{
    private PDO $db;

    /**
     * @param string $tabela Naziv tabele u bazi.
     * @param string[] $kolonePretrage Kolone po kojima radi tekstualna pretraga (LIKE).
     * @param string $sortiranjeKolona Kolona za podrazumijevano sortiranje liste.
     * @param array $spajanja Dodatna LEFT JOIN spajanja za prikaz naziva umjesto FK ID-a,
     *   format: [['tabela' => 'investitori i', 'on' => 'i.id = t.investitor_id', 'select' => 'i.naziv AS investitor_naziv'], ...]
     *   (u SQL-u se glavna tabela uvijek aliasuje kao "t")
     */
    public function __construct(
        private string $tabela,
        private array $kolonePretrage,
        private string $sortiranjeKolona = 'id',
        private array $spajanja = []
    ) {
        $this->db = Database::konekcija();
    }

    private function joinSql(): string
    {
        $sql = '';
        foreach ($this->spajanja as $j) {
            $sql .= " LEFT JOIN {$j['tabela']} ON {$j['on']}";
        }
        return $sql;
    }

    private function selectDodatno(): string
    {
        $kolone = array_map(fn($j) => $j['select'], $this->spajanja);
        return $kolone ? ', ' . implode(', ', $kolone) : '';
    }

    public function lista(string $pretraga = '', int $stranica = 1, int $poStranici = 20): array
    {
        $stranica = max(1, $stranica);
        $offset = ($stranica - 1) * $poStranici;

        $uslov = '';
        $parametri = [];
        if ($pretraga !== '' && $this->kolonePretrage) {
            // Svaka kolona MORA imati SVOJ named placeholder (:p0, :p1, ...) —
            // sa PDO::ATTR_EMULATE_PREPARES=false isti named placeholder se
            // ne smije ponoviti više puta u istom upitu (baca "Invalid
            // parameter number" na MySQL native prepared statementima).
            $dijelovi = [];
            foreach (array_values($this->kolonePretrage) as $i => $kolona) {
                $dijelovi[] = "t.$kolona LIKE :p$i";
                $parametri["p$i"] = '%' . $pretraga . '%';
            }
            $uslov = 'WHERE ' . implode(' OR ', $dijelovi);
        }

        $stmtBroj = $this->db->prepare("SELECT COUNT(*) AS ukupno FROM {$this->tabela} t {$this->joinSql()} $uslov");
        $stmtBroj->execute($parametri);
        $ukupno = (int) $stmtBroj->fetch()['ukupno'];

        $sql = "SELECT t.*" . $this->selectDodatno() . "
                FROM {$this->tabela} t
                {$this->joinSql()}
                $uslov
                ORDER BY t.{$this->sortiranjeKolona}
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($parametri as $kljuc => $vrijednost) {
            $stmt->bindValue($kljuc, $vrijednost);
        }
        $stmt->bindValue('limit', $poStranici, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'stavke' => $stmt->fetchAll(),
            'ukupno' => $ukupno,
            'stranica' => $stranica,
            'po_stranici' => $poStranici,
            'ukupno_stranica' => max(1, (int) ceil($ukupno / $poStranici)),
        ];
    }

    public function pronadjiPoId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT t.* FROM {$this->tabela} t WHERE t.id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $red = $stmt->fetch();
        return $red ?: null;
    }

    /** @param array<string,mixed> $podaci kolona => vrijednost */
    public function kreiraj(array $podaci): int
    {
        $kolone = array_keys($podaci);
        $placeholderi = array_map(fn($k) => ":$k", $kolone);
        $sql = "INSERT INTO {$this->tabela} (" . implode(', ', $kolone) . ')
                VALUES (' . implode(', ', $placeholderi) . ')';
        $this->db->prepare($sql)->execute($podaci);
        return (int) $this->db->lastInsertId();
    }

    public function azuriraj(int $id, array $podaci): void
    {
        $setovi = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($podaci)));
        $sql = "UPDATE {$this->tabela} SET $setovi WHERE id = :id";
        $this->db->prepare($sql)->execute($podaci + ['id' => $id]);
    }

    public function postaviAktivnost(int $id, bool $aktivan): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->tabela} SET aktivan = :a WHERE id = :id");
        $stmt->execute(['a' => $aktivan ? 1 : 0, 'id' => $id]);
    }

    /** Za popunjavanje <select> opcija u formama (aktivni zapisi, sortirano po nazivu). */
    public function opcijeZaSelect(string $kolonaNaziva = 'naziv'): array
    {
        $stmt = $this->db->query("SELECT id, $kolonaNaziva AS naziv FROM {$this->tabela} WHERE aktivan = 1 ORDER BY $kolonaNaziva");
        return $stmt->fetchAll();
    }

    public function imeTabele(): string
    {
        return $this->tabela;
    }
}
