<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class KorisnikRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function pronadjiPoKorisnickomImenu(string $korisnickoIme): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM korisnici WHERE korisnicko_ime = :ki LIMIT 1');
        $stmt->execute(['ki' => $korisnickoIme]);
        $red = $stmt->fetch();
        return $red ?: null;
    }

    public function pronadjiPoId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM korisnici WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $red = $stmt->fetch();
        return $red ?: null;
    }

    public function lista(string $pretraga = '', int $stranica = 1, int $poStranici = 20): array
    {
        $stranica = max(1, $stranica);
        $offset = ($stranica - 1) * $poStranici;

        $uslov = '';
        $parametri = [];
        if ($pretraga !== '') {
            $uslov = 'WHERE ime LIKE :p1 OR prezime LIKE :p2 OR korisnicko_ime LIKE :p3 OR email LIKE :p4';
            $parametri['p1'] = '%' . $pretraga . '%';
            $parametri['p2'] = '%' . $pretraga . '%';
            $parametri['p3'] = '%' . $pretraga . '%';
            $parametri['p4'] = '%' . $pretraga . '%';
        }

        $stmtBroj = $this->db->prepare("SELECT COUNT(*) AS ukupno FROM korisnici $uslov");
        $stmtBroj->execute($parametri);
        $ukupno = (int) $stmtBroj->fetch()['ukupno'];

        $sql = "SELECT k.*, r.naziv AS uloga_naziv
                FROM korisnici k
                LEFT JOIN role r ON r.id = k.role_id
                $uslov
                ORDER BY k.id DESC
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
            'ukupno_stranica' => (int) ceil($ukupno / $poStranici),
        ];
    }

    public function kreiraj(array $podaci): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO korisnici (ime, prezime, korisnicko_ime, email, lozinka_hash, role_id, status, datum_kreiranja)
             VALUES (:ime, :prezime, :korisnicko_ime, :email, :lozinka_hash, :role_id, :status, NOW())'
        );
        $stmt->execute([
            'ime'            => $podaci['ime'],
            'prezime'        => $podaci['prezime'],
            'korisnicko_ime' => $podaci['korisnicko_ime'],
            'email'          => $podaci['email'],
            'lozinka_hash'   => password_hash($podaci['lozinka'], PASSWORD_DEFAULT),
            'role_id'        => $podaci['role_id'] ?: null,
            'status'         => $podaci['status'] ?? 'aktivan',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function azuriraj(int $id, array $podaci): void
    {
        $stmt = $this->db->prepare(
            'UPDATE korisnici SET ime = :ime, prezime = :prezime, korisnicko_ime = :korisnicko_ime,
                email = :email, role_id = :role_id, status = :status, datum_izmjene = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'ime'            => $podaci['ime'],
            'prezime'        => $podaci['prezime'],
            'korisnicko_ime' => $podaci['korisnicko_ime'],
            'email'          => $podaci['email'],
            'role_id'        => $podaci['role_id'] ?: null,
            'status'         => $podaci['status'],
            'id'             => $id,
        ]);
    }

    public function postaviLozinku(int $id, string $novaLozinka): void
    {
        $stmt = $this->db->prepare('UPDATE korisnici SET lozinka_hash = :hash, datum_izmjene = NOW() WHERE id = :id');
        $stmt->execute([
            'hash' => password_hash($novaLozinka, PASSWORD_DEFAULT),
            'id'   => $id,
        ]);
    }

    public function postaviStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE korisnici SET status = :status, datum_izmjene = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function azurirajPosljednjuPrijavu(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE korisnici SET posljednja_prijava = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function sveRole(): array
    {
        return $this->db->query('SELECT * FROM role WHERE aktivan = 1 ORDER BY naziv')->fetchAll();
    }
}
