<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Analitika realizacije projekta.
 *
 *  - planirano    = projekti.planirani_broj_elemenata (unosi se na projektu)
 *  - proizvedeno  = zbir ulazi_stavke.kolicina za AKTIVNE (ne stornirane) ulaze
 *                   kod kojih je izabran taj projekat (ulaz = proizvedeno/primljeno
 *                   na skladište)
 *  - otpremljeno  = zbir otpreme_stavke.kolicina za otpreme vezane na projekat
 *
 * NAPOMENA (pretpostavke):
 *  1. Ulaz koji nema izabran projekat se NE računa nijednom projektu.
 *  2. "Broj elemenata" je zbir komada SVIH tipova elemenata (jedan ukupan broj, ne
 *     plan po šifri); raspodjela po elementu je prikazana samo radi uvida.
 */
class ProjektAnalitikaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    /** @return array<int, float> projekat_id => otpremljeno (jedan grupisani upit za cijelu listu) */
    public function otpremljenoPoSvimProjektima(): array
    {
        $stmt = $this->db->query(
            "SELECT o.projekat_id, COALESCE(SUM(os.kolicina), 0) AS otpremljeno
             FROM otpreme_stavke os
             JOIN otpreme o ON o.id = os.otprema_id
             WHERE o.projekat_id IS NOT NULL
             GROUP BY o.projekat_id"
        );

        $rezultat = [];
        foreach ($stmt->fetchAll() as $red) {
            $rezultat[(int) $red['projekat_id']] = (float) $red['otpremljeno'];
        }
        return $rezultat;
    }

    /** @return array<int, float> projekat_id => proizvedeno (iz aktivnih ulaza) */
    public function proizvedenoPoSvimProjektima(): array
    {
        $stmt = $this->db->query(
            "SELECT u.projekat_id, COALESCE(SUM(us.kolicina), 0) AS proizvedeno
             FROM ulazi_stavke us
             JOIN ulazi u ON u.id = us.ulaz_id
             WHERE u.projekat_id IS NOT NULL AND u.status = 'aktivan'
             GROUP BY u.projekat_id"
        );

        $rezultat = [];
        foreach ($stmt->fetchAll() as $red) {
            $rezultat[(int) $red['projekat_id']] = (float) $red['proizvedeno'];
        }
        return $rezultat;
    }

    /**
     * Detaljna analitika za JEDAN projekat: ukupno + raspodjela po elementu
     * (proizvedeno, otpremljeno, i koliko je proizvedeno a još čeka otpremu).
     */
    public function analitikaZaProjekat(int $projekatId): array
    {
        $stmtOtprema = $this->db->prepare(
            "SELECT os.element_id, e.sifra, e.naziv, COALESCE(SUM(os.kolicina), 0) AS kolicina
             FROM otpreme_stavke os
             JOIN otpreme o ON o.id = os.otprema_id
             JOIN ab_elementi e ON e.id = os.element_id
             WHERE o.projekat_id = :id
             GROUP BY os.element_id, e.sifra, e.naziv"
        );
        $stmtOtprema->execute(['id' => $projekatId]);

        $stmtUlaz = $this->db->prepare(
            "SELECT us.element_id, e.sifra, e.naziv, COALESCE(SUM(us.kolicina), 0) AS kolicina
             FROM ulazi_stavke us
             JOIN ulazi u ON u.id = us.ulaz_id
             JOIN ab_elementi e ON e.id = us.element_id
             WHERE u.projekat_id = :id AND u.status = 'aktivan'
             GROUP BY us.element_id, e.sifra, e.naziv"
        );
        $stmtUlaz->execute(['id' => $projekatId]);

        $redovi = [];
        foreach ($stmtUlaz->fetchAll() as $r) {
            $redovi[(int) $r['element_id']] = [
                'sifra' => $r['sifra'], 'naziv' => $r['naziv'],
                'proizvedeno' => (float) $r['kolicina'], 'otpremljeno' => 0.0,
            ];
        }
        foreach ($stmtOtprema->fetchAll() as $r) {
            $id = (int) $r['element_id'];
            if (!isset($redovi[$id])) {
                $redovi[$id] = ['sifra' => $r['sifra'], 'naziv' => $r['naziv'], 'proizvedeno' => 0.0, 'otpremljeno' => 0.0];
            }
            $redovi[$id]['otpremljeno'] = (float) $r['kolicina'];
        }

        foreach ($redovi as &$red) {
            $red['ceka_otpremu'] = max(0.0, $red['proizvedeno'] - $red['otpremljeno']);
        }
        unset($red);

        usort($redovi, fn($a, $b) => strcmp($a['sifra'], $b['sifra']));

        return [
            'po_elementu'        => $redovi,
            'ukupno_proizvedeno' => (float) array_sum(array_column($redovi, 'proizvedeno')),
            'ukupno_otpremljeno' => (float) array_sum(array_column($redovi, 'otpremljeno')),
        ];
    }
}
