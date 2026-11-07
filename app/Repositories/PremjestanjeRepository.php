<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

class PremjestanjeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function lista(int $stranica = 1, int $poStranici = 20): array
    {
        $stranica = max(1, $stranica);
        $offset = ($stranica - 1) * $poStranici;

        $ukupno = (int) $this->db->query('SELECT COUNT(*) FROM premjestanja')->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT pr.*, e.sifra, e.naziv AS element_naziv,
                    li.oznaka AS iz_lokacija_oznaka, lu.oznaka AS u_lokacija_oznaka, k.korisnicko_ime,
                    inv.naziv AS investitor_naziv, proj.naziv AS projekat_naziv
             FROM premjestanja pr
             JOIN ab_elementi e ON e.id = pr.element_id
             JOIN lokacije li ON li.id = pr.iz_lokacija_id
             JOIN lokacije lu ON lu.id = pr.u_lokacija_id
             LEFT JOIN korisnici k ON k.id = pr.korisnik_id
             LEFT JOIN investitori inv ON inv.id = pr.investitor_id
             LEFT JOIN projekti proj ON proj.id = pr.projekat_id
             ORDER BY pr.id DESC
             LIMIT :limit OFFSET :offset'
        );
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

    /**
     * @param array $podaci ['element_id','iz_lokacija_id','u_lokacija_id','kolicina','napomena','korisnik_id','investitor_id'?,'projekat_id'?]
     *   investitor_id/projekat_id identifikuju KOJU investitor/projekat "traku" stanja premještamo —
     *   destinacija zadržava istu pripadnost (premještanje mijenja samo lokaciju, ne vlasništvo/namjenu).
     * @return array{id:int, broj_premjestanja:string}
     */
    public function kreiraj(array $podaci): array
    {
        if ((int) $podaci['iz_lokacija_id'] === (int) $podaci['u_lokacija_id']) {
            throw new RuntimeException('Izvorna i ciljna lokacija ne mogu biti iste.');
        }

        $investitorId = StanjeRepository::norm($podaci['investitor_id'] ?? null);
        $projekatId = StanjeRepository::norm($podaci['projekat_id'] ?? null);

        $this->db->beginTransaction();
        try {
            $stanjeRepo = new StanjeRepository();

            $fizickoIz = $stanjeRepo->kolicinaZaAzuriranje((int) $podaci['element_id'], (int) $podaci['iz_lokacija_id'], $investitorId, $projekatId);
            $rezervisanoIz = $stanjeRepo->rezervisanoZaAzuriranje((int) $podaci['element_id'], (int) $podaci['iz_lokacija_id'], $investitorId, $projekatId);
            $slobodnoIz = $fizickoIz - $rezervisanoIz;

            // Premještanje je namjerno ograničeno na SLOBODNU (nerezervisanu)
            // količinu na izvornoj lokaciji, za IZABRANU investitor/projekat
            // traku — prebacivanje rezervisane količine bi ostavilo
            // rezervaciju "u zraku" na pogrešnoj lokaciji.
            if ((float) $podaci['kolicina'] > $slobodnoIz) {
                throw new RuntimeException(
                    sprintf(
                        'Premještanje premašuje slobodnu (nerezervisanu) količinu na izvornoj lokaciji za izabranu investitor/projekat traku (slobodno: %s, traženo: %s).',
                        $slobodnoIz,
                        $podaci['kolicina']
                    )
                );
            }

            $broj = (new NumeracijaRepository())->sljedeciBroj('PREM', (int) date('Y'));

            $stmt = $this->db->prepare(
                'INSERT INTO premjestanja (broj_premjestanja, element_id, investitor_id, projekat_id, iz_lokacija_id, u_lokacija_id, kolicina, napomena, korisnik_id)
                 VALUES (:broj, :element_id, :investitor_id, :projekat_id, :iz, :u, :kolicina, :napomena, :korisnik_id)'
            );
            $stmt->execute([
                'broj'          => $broj,
                'element_id'    => $podaci['element_id'],
                'investitor_id' => $investitorId,
                'projekat_id'   => $projekatId,
                'iz'            => $podaci['iz_lokacija_id'],
                'u'             => $podaci['u_lokacija_id'],
                'kolicina'      => $podaci['kolicina'],
                'napomena'      => $podaci['napomena'] ?: null,
                'korisnik_id'   => $podaci['korisnik_id'],
            ]);
            $id = (int) $this->db->lastInsertId();

            $stanjeIzNakon = $stanjeRepo->smanji((int) $podaci['element_id'], (int) $podaci['iz_lokacija_id'], (float) $podaci['kolicina'], $investitorId, $projekatId);
            $stanjeUNakon = $stanjeRepo->povecaj((int) $podaci['element_id'], (int) $podaci['u_lokacija_id'], (float) $podaci['kolicina'], $investitorId, $projekatId);

            $karticaRepo = new SkladisnaKarticaRepository();
            $karticaRepo->upisi([
                'element_id' => $podaci['element_id'], 'lokacija_id' => $podaci['iz_lokacija_id'],
                'investitor_id' => $podaci['investitor_id'] ?: null, 'projekat_id' => $podaci['projekat_id'] ?: null,
                'tip_promjene' => 'premjestanje_iz', 'kolicina' => -1 * (float) $podaci['kolicina'],
                'stanje_nakon' => $stanjeIzNakon, 'dokument_tip' => 'premjestanje', 'dokument_id' => $id,
                'dokument_broj' => $broj, 'korisnik_id' => $podaci['korisnik_id'],
            ]);
            $karticaRepo->upisi([
                'element_id' => $podaci['element_id'], 'lokacija_id' => $podaci['u_lokacija_id'],
                'investitor_id' => $podaci['investitor_id'] ?: null, 'projekat_id' => $podaci['projekat_id'] ?: null,
                'tip_promjene' => 'premjestanje_u', 'kolicina' => $podaci['kolicina'],
                'stanje_nakon' => $stanjeUNakon, 'dokument_tip' => 'premjestanje', 'dokument_id' => $id,
                'dokument_broj' => $broj, 'korisnik_id' => $podaci['korisnik_id'],
            ]);

            $this->db->commit();

            return ['id' => $id, 'broj_premjestanja' => $broj];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
