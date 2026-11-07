<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AuditRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function zabiljezi(
        ?int $korisnikId,
        string $akcija,
        string $modul,
        ?string $tabela = null,
        ?int $idZapisa = null,
        $staraVrijednost = null,
        $novaVrijednost = null
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_log (korisnik_id, datum_vrijeme, ip_adresa, akcija, modul, tabela, id_zapisa, stara_vrijednost, nova_vrijednost)
             VALUES (:korisnik_id, NOW(), :ip, :akcija, :modul, :tabela, :id_zapisa, :stara, :nova)'
        );

        $stmt->execute([
            'korisnik_id' => $korisnikId,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'akcija'      => $akcija,
            'modul'       => $modul,
            'tabela'      => $tabela,
            'id_zapisa'   => $idZapisa,
            'stara'       => $staraVrijednost !== null ? json_encode($staraVrijednost, JSON_UNESCAPED_UNICODE) : null,
            'nova'        => $novaVrijednost !== null ? json_encode($novaVrijednost, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function lista(int $stranica = 1, int $poStranici = 50): array
    {
        $offset = ($stranica - 1) * $poStranici;
        $stmt = $this->db->prepare(
            'SELECT a.*, k.korisnicko_ime
             FROM audit_log a
             LEFT JOIN korisnici k ON k.id = a.korisnik_id
             ORDER BY a.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $poStranici, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
