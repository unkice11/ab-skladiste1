<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Skladišna kartica — evidencija SVIH promjena po elementu/lokaciji.
 * Ne poziva se direktno iz kontrolera osim za čitanje istorije; upisuje
 * je svaki repository koji mijenja stanje (Ulaz, Rezervacija,
 * Premještanje), unutar iste transakcije kao i sama promjena.
 */
class SkladisnaKarticaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function upisi(array $p): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO skladisna_kartica
                (element_id, lokacija_id, investitor_id, projekat_id, tip_promjene, kolicina, stanje_nakon, dokument_tip, dokument_id, dokument_broj, korisnik_id)
             VALUES
                (:element_id, :lokacija_id, :investitor_id, :projekat_id, :tip_promjene, :kolicina, :stanje_nakon, :dokument_tip, :dokument_id, :dokument_broj, :korisnik_id)'
        );
        $stmt->execute([
            'element_id'    => $p['element_id'],
            'lokacija_id'   => $p['lokacija_id'] ?? null,
            'investitor_id' => $p['investitor_id'] ?? null,
            'projekat_id'   => $p['projekat_id'] ?? null,
            'tip_promjene'  => $p['tip_promjene'],
            'kolicina'      => $p['kolicina'],
            'stanje_nakon'  => $p['stanje_nakon'] ?? null,
            'dokument_tip'  => $p['dokument_tip'],
            'dokument_id'   => $p['dokument_id'] ?? null,
            'dokument_broj' => $p['dokument_broj'] ?? null,
            'korisnik_id'   => $p['korisnik_id'] ?? null,
        ]);
    }

    /** Puna istorija za jedan element (najnovije prvo), uz naziv lokacije. */
    public function istorijaZaElement(int $elementId, int $stranica = 1, int $poStranici = 50): array
    {
        $offset = ($stranica - 1) * $poStranici;

        $stmtBroj = $this->db->prepare('SELECT COUNT(*) AS ukupno FROM skladisna_kartica WHERE element_id = :id');
        $stmtBroj->execute(['id' => $elementId]);
        $ukupno = (int) $stmtBroj->fetch()['ukupno'];

        $stmt = $this->db->prepare(
            'SELECT sk.*, l.oznaka AS lokacija_oznaka, s.naziv AS skladiste_naziv, k.korisnicko_ime,
                    inv.naziv AS investitor_naziv, proj.naziv AS projekat_naziv
             FROM skladisna_kartica sk
             LEFT JOIN lokacije l ON l.id = sk.lokacija_id
             LEFT JOIN skladista s ON s.id = l.skladiste_id
             LEFT JOIN korisnici k ON k.id = sk.korisnik_id
             LEFT JOIN investitori inv ON inv.id = sk.investitor_id
             LEFT JOIN projekti proj ON proj.id = sk.projekat_id
             WHERE sk.element_id = :id
             ORDER BY sk.datum_vrijeme DESC, sk.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('id', $elementId, PDO::PARAM_INT);
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
}
