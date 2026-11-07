<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Generiše potpuni SQL dump baze kroz PDO (bez oslanjanja na mysqldump
 * binarni fajl ili shell_exec, koji možda nisu dostupni/dozvoljeni na
 * hosting okruženju) — čist PHP, u skladu sa ostatkom arhitekture.
 *
 * Namjerno piše direktno u dati stream (umjesto da vrati ogroman string u
 * memoriji) da bi backup baze razumne veličine radio i sa ograničenim
 * memory_limit-om.
 */
class BackupRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    /** @param resource $izlaz otvoren stream (npr. php://output ili fopen na fajl) */
    public function generisiDump($izlaz): void
    {
        $baza = $this->db->query('SELECT DATABASE()')->fetchColumn();

        fwrite($izlaz, "-- PREKON — backup baze '$baza'\n");
        fwrite($izlaz, "-- Generisano: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($izlaz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

        $tabele = $this->db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tabele as $tabela) {
            $this->dumpTabelu($izlaz, $tabela);
        }

        fwrite($izlaz, "SET FOREIGN_KEY_CHECKS = 1;\n");
    }

    /** @param resource $izlaz */
    private function dumpTabelu($izlaz, string $tabela): void
    {
        fwrite($izlaz, "-- ----------------------------\n-- Tabela: $tabela\n-- ----------------------------\n");

        $createRed = $this->db->query("SHOW CREATE TABLE `$tabela`")->fetch();
        $createSql = $createRed['Create Table'] ?? '';

        fwrite($izlaz, "DROP TABLE IF EXISTS `$tabela`;\n");
        fwrite($izlaz, $createSql . ";\n\n");

        $stmt = $this->db->query("SELECT * FROM `$tabela`");
        $kolone = null;
        $redoviUBatchu = [];
        $velicinaBatcha = 500;

        while ($red = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($kolone === null) {
                $kolone = array_keys($red);
            }
            $redoviUBatchu[] = $red;
            if (count($redoviUBatchu) >= $velicinaBatcha) {
                $this->upisiInsertBatch($izlaz, $tabela, $kolone, $redoviUBatchu);
                $redoviUBatchu = [];
            }
        }
        if ($redoviUBatchu) {
            $this->upisiInsertBatch($izlaz, $tabela, $kolone ?? [], $redoviUBatchu);
        }

        fwrite($izlaz, "\n");
    }

    /** @param resource $izlaz */
    private function upisiInsertBatch($izlaz, string $tabela, array $kolone, array $redovi): void
    {
        if (!$kolone) {
            return;
        }

        $koloneSql = '`' . implode('`, `', $kolone) . '`';
        $vrijednosti = [];

        foreach ($redovi as $red) {
            $dijelovi = array_map(function ($v) {
                if ($v === null) {
                    return 'NULL';
                }
                return $this->db->quote((string) $v);
            }, $red);
            $vrijednosti[] = '(' . implode(', ', $dijelovi) . ')';
        }

        fwrite($izlaz, "INSERT INTO `$tabela` ($koloneSql) VALUES\n" . implode(",\n", $vrijednosti) . ";\n");
    }
}
