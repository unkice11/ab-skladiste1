<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Svaka permisija (kod) ima dvije nezavisne oznake:
 *   - vidljiv: da li se stavka prikazuje u meniju
 *   - pristup: da li korisnik smije stvarno otvoriti/izvršiti akciju
 *
 * Efektivna permisija korisnika = permisije njegove role, PREKO kojih
 * se (ako postoje) primjenjuju individualni override zapisi iz
 * korisnik_permisije (administrator korisniku može dodatno dozvoliti
 * ili oduzeti nešto van osnovne role).
 */
class PermisijaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function sve(): array
    {
        return $this->db->query('SELECT * FROM permisije ORDER BY modul, kod')->fetchAll();
    }

    /** @return array<string, array{vidljiv:bool,pristup:bool}> indeksirano po kodu permisije */
    public function efektivnePermisijeZaKorisnika(int $korisnikId): array
    {
        $stmt = $this->db->prepare('SELECT role_id FROM korisnici WHERE id = :id');
        $stmt->execute(['id' => $korisnikId]);
        $red = $stmt->fetch();
        $roleId = $red['role_id'] ?? null;

        $rezultat = [];

        if ($roleId) {
            $stmt = $this->db->prepare(
                'SELECT p.kod, rp.vidljiv, rp.pristup
                 FROM role_permisije rp
                 JOIN permisije p ON p.id = rp.permisija_id
                 WHERE rp.role_id = :role_id'
            );
            $stmt->execute(['role_id' => $roleId]);
            foreach ($stmt->fetchAll() as $red) {
                $rezultat[$red['kod']] = [
                    'vidljiv' => (bool) $red['vidljiv'],
                    'pristup' => (bool) $red['pristup'],
                ];
            }
        }

        // Individualni override po korisniku ima prednost nad rolom.
        $stmt = $this->db->prepare(
            'SELECT p.kod, kp.vidljiv, kp.pristup
             FROM korisnik_permisije kp
             JOIN permisije p ON p.id = kp.permisija_id
             WHERE kp.korisnik_id = :korisnik_id'
        );
        $stmt->execute(['korisnik_id' => $korisnikId]);
        foreach ($stmt->fetchAll() as $red) {
            $rezultat[$red['kod']] = [
                'vidljiv' => (bool) $red['vidljiv'],
                'pristup' => (bool) $red['pristup'],
            ];
        }

        return $rezultat;
    }

    /**
     * Sve permisije uz označeno da li ih korisnik efektivno ima, i da li
     * postoji individualni override (za ekran "Korisnici → Permisije").
     */
    public function permisijeZaEkran(int $korisnikId): array
    {
        $sve = $this->sve();
        $efektivne = $this->efektivnePermisijeZaKorisnika($korisnikId);

        $stmt = $this->db->prepare('SELECT permisija_id FROM korisnik_permisije WHERE korisnik_id = :id');
        $stmt->execute(['id' => $korisnikId]);
        $ideOverride = array_column($stmt->fetchAll(), 'permisija_id');

        foreach ($sve as &$p) {
            $p['vidljiv'] = $efektivne[$p['kod']]['vidljiv'] ?? false;
            $p['pristup'] = $efektivne[$p['kod']]['pristup'] ?? false;
            $p['override'] = in_array($p['id'], $ideOverride, true);
        }

        return $sve;
    }

    /**
     * Snima individualni override permisija za korisnika. Očekuje niz:
     * [permisija_id => ['vidljiv' => bool, 'pristup' => bool], ...] za
     * SVE permisije prikazane na formi (ekran šalje hidden polje za
     * svaku permisiju, ne samo za čekirane — vidi korisnici/permisije view).
     * Sve permisije koje nisu poslane brišu override (vraćaju se na rolu).
     */
    public function sacuvajOverrideZaKorisnika(int $korisnikId, array $unosi): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('DELETE FROM korisnik_permisije WHERE korisnik_id = :id');
            $stmt->execute(['id' => $korisnikId]);

            $insert = $this->db->prepare(
                'INSERT INTO korisnik_permisije (korisnik_id, permisija_id, vidljiv, pristup)
                 VALUES (:korisnik_id, :permisija_id, :vidljiv, :pristup)'
            );

            foreach ($unosi as $permisijaId => $vrijednosti) {
                $vidljiv = !empty($vrijednosti['vidljiv']);
                $pristup = !empty($vrijednosti['pristup']);
                // Namjerno upisujemo i red sa vidljiv=0/pristup=0 — to je
                // eksplicitno oduzimanje permisije koju korisnik inače ima
                // preko svoje role, a ne "nema podešeno" (što bi vratilo na rolu).
                $insert->execute([
                    'korisnik_id'  => $korisnikId,
                    'permisija_id' => (int) $permisijaId,
                    'vidljiv'      => $vidljiv ? 1 : 0,
                    'pristup'      => $pristup ? 1 : 0,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Sve permisije uz označeno da li ih ULOGA ima (za ekran
     * "Uloge → Permisije") — ovo je OSNOVNI set za tu rolu (ne override
     * kao kod korisnika), direktno iz role_permisije.
     */
    public function permisijeZaEkranRole(int $roleId): array
    {
        $sve = $this->sve();

        $stmt = $this->db->prepare('SELECT p.kod, rp.vidljiv, rp.pristup FROM role_permisije rp JOIN permisije p ON p.id = rp.permisija_id WHERE rp.role_id = :id');
        $stmt->execute(['id' => $roleId]);
        $trenutne = [];
        foreach ($stmt->fetchAll() as $red) {
            $trenutne[$red['kod']] = $red;
        }

        foreach ($sve as &$p) {
            $p['vidljiv'] = !empty($trenutne[$p['kod']]['vidljiv']);
            $p['pristup'] = !empty($trenutne[$p['kod']]['pristup']);
        }

        return $sve;
    }

    /**
     * Snima OSNOVNI set permisija za ulogu (role_permisije) — za razliku
     * od korisnika, ovo NIJE override nego direktno definiše šta ta
     * uloga daje. Svi korisnici sa tom ulogom (koji nemaju individualni
     * override za datu permisiju) odmah dobijaju/gube pristup.
     */
    public function sacuvajZaRole(int $roleId, array $unosi): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM role_permisije WHERE role_id = :id')->execute(['id' => $roleId]);

            $insert = $this->db->prepare(
                'INSERT INTO role_permisije (role_id, permisija_id, vidljiv, pristup) VALUES (:role_id, :permisija_id, :vidljiv, :pristup)'
            );

            foreach ($unosi as $permisijaId => $vrijednosti) {
                $vidljiv = !empty($vrijednosti['vidljiv']);
                $pristup = !empty($vrijednosti['pristup']);
                if (!$vidljiv && !$pristup) {
                    continue; // za rolu nema smisla čuvati prazan red (za razliku od override-a kod korisnika)
                }
                $insert->execute([
                    'role_id'      => $roleId,
                    'permisija_id' => (int) $permisijaId,
                    'vidljiv'      => $vidljiv ? 1 : 0,
                    'pristup'      => $pristup ? 1 : 0,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
