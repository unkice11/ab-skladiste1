<?php
/**
 * CENTRALNI KONFIGURACIJSKI FAJL
 *
 * Sve vrijednosti koje zavise od servera/okruženja moraju biti ovdje.
 * Nigdje drugo u aplikaciji se ne smiju hardkodirati URL, DB podaci,
 * putanje ili slični parametri — uvijek se čita odavde preko config().
 */

return [

    'app' => [
        'name'  => 'PREKON',
        // Bazni URL aplikacije, BEZ trailing slasha.
        'url'   => 'http://ab-skladiste.test',
        'env'   => 'local',   // local | production
        'debug' => true,      // OBAVEZNO false u produkciji
        'timezone' => 'Europe/Sarajevo',
        'locale'   => 'bs',
        'date_format'     => 'd.m.Y',
        'datetime_format' => 'd.m.Y H:i',
        'currency' => 'BAM',
    ],

    'db' => [
        'host'     => '127.0.0.1',
        'port'     => '3306',
        'database' => 'ab_skladiste',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    'session' => [
        'name'     => 'ab_skladiste_sid',
        'lifetime' => 7200,
        'remember_lifetime' => 60 * 60 * 24 * 30,
    ],

    'paths' => [
        'upload_path'  => __DIR__ . '/../storage/uploads',
        'storage_path' => __DIR__ . '/../storage',
        'log_path'     => __DIR__ . '/../storage/logs',
    ],

    'security' => [
        'max_login_attempts'    => 5,
        'login_lockout_seconds' => 300,
        'csrf_token_name'       => 'csrf_token',
    ],

    'company' => [
        'name' => '',
    ],
];
