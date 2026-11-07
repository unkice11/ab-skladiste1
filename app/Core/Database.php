<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Singleton PDO konekcija.
 * Svi upiti u aplikaciji MORAJU ići preko prepared statementa (PDO),
 * nikada direktnim ubacivanjem vrijednosti u SQL string.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function konekcija(): PDO
    {
        if (self::$instance === null) {
            $cfg = config('db');

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['database'],
                $cfg['charset']
            );

            try {
                self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('[DB] Konekcija neuspješna: ' . $e->getMessage());
                http_response_code(500);
                die('Trenutno nije moguće uspostaviti konekciju sa bazom podataka. Pokušajte kasnije.');
            }
        }

        return self::$instance;
    }
}
