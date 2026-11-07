<?php
/**
 * POČETNI SEED — pokreće se JEDNOM, nakon što je 001_osnovna_sema.sql
 * izvršena nad bazom.
 *
 * Kreira: ulogu "Administrator", osnovne permisije (dashboard, korisnici)
 * sa punim pravima za tu ulogu, osnovne stavke menija (Dashboard,
 * Postavke → Korisnici), i prvog admin korisnika.
 *
 * POKRETANJE:  php database/seeders/seed.php
 *
 * VAŽNO: Nakon uspješnog pokretanja, OBRIŠI ili premjesti ovaj fajl van
 * web-dostupnog direktorija, i ODMAH promijeni default lozinku admin
 * naloga kroz aplikaciju.
 */

require dirname(__DIR__, 2) . '/app/Core/bootstrap.php';

use App\Core\Database;

$db = Database::konekcija();

$ADMIN_KORISNICKO_IME = 'admin';
$ADMIN_LOZINKA        = 'PromijeniMe123!';
$ADMIN_EMAIL          = 'admin@ab-skladiste.local';

try {
    $db->beginTransaction();

    // --- 1) Uloga Administrator ------------------------------------------------
    $stmt = $db->prepare('SELECT id FROM role WHERE naziv = :naziv');
    $stmt->execute(['naziv' => 'Administrator']);
    $roleId = $stmt->fetchColumn();

    if (!$roleId) {
        $db->prepare('INSERT INTO role (naziv, opis, aktivan) VALUES (:naziv, :opis, 1)')
           ->execute(['naziv' => 'Administrator', 'opis' => 'Puni pristup sistemu']);
        $roleId = (int) $db->lastInsertId();
        echo "Kreirana uloga: Administrator (id=$roleId)\n";
    } else {
        echo "Uloga Administrator već postoji (id=$roleId)\n";
    }

    // --- 2) Osnovne permisije ---------------------------------------------------
    $permisije = [
        ['kod' => 'dashboard.view',        'naziv' => 'Pregled dashboarda',          'modul' => 'dashboard'],
        ['kod' => 'korisnici.view',        'naziv' => 'Pregled korisnika',           'modul' => 'korisnici'],
        ['kod' => 'korisnici.kreiraj',     'naziv' => 'Kreiranje korisnika',         'modul' => 'korisnici'],
        ['kod' => 'korisnici.izmijeni',    'naziv' => 'Izmjena korisnika',           'modul' => 'korisnici'],
        ['kod' => 'korisnici.permisije',   'naziv' => 'Dodjela permisija korisniku', 'modul' => 'korisnici'],
        ['kod' => 'role.view',             'naziv' => 'Pregled uloga',               'modul' => 'korisnici'],
        ['kod' => 'role.uredi',            'naziv' => 'Uređivanje uloga i njihovih permisija', 'modul' => 'korisnici'],
        ['kod' => 'postavke.parametri',    'naziv' => 'Izmjena parametara sistema',  'modul' => 'postavke'],
        ['kod' => 'postavke.podaci-firme', 'naziv' => 'Izmjena podataka firme',      'modul' => 'postavke'],
        ['kod' => 'postavke.backup',       'naziv' => 'Backup baze podataka',        'modul' => 'postavke'],

        // Šifrarnici (Faza 2) — svaki ima .view (pregled/vidljivost u meniju)
        // i .uredi (kreiranje/izmjena/aktivacija-deaktivacija).
        ['kod' => 'elementi.view',    'naziv' => 'Pregled AB elemenata',     'modul' => 'sifrarnici'],
        ['kod' => 'elementi.uredi',   'naziv' => 'Uređivanje AB elemenata',  'modul' => 'sifrarnici'],
        ['kod' => 'kategorije.view',  'naziv' => 'Pregled kategorija',       'modul' => 'sifrarnici'],
        ['kod' => 'kategorije.uredi', 'naziv' => 'Uređivanje kategorija',    'modul' => 'sifrarnici'],
        ['kod' => 'investitori.view',  'naziv' => 'Pregled investitora',     'modul' => 'sifrarnici'],
        ['kod' => 'investitori.uredi', 'naziv' => 'Uređivanje investitora',  'modul' => 'sifrarnici'],
        ['kod' => 'projekti.view',   'naziv' => 'Pregled projekata',        'modul' => 'sifrarnici'],
        ['kod' => 'projekti.uredi',  'naziv' => 'Uređivanje projekata',     'modul' => 'sifrarnici'],
        ['kod' => 'objekti.view',    'naziv' => 'Pregled objekata',         'modul' => 'sifrarnici'],
        ['kod' => 'objekti.uredi',   'naziv' => 'Uređivanje objekata',      'modul' => 'sifrarnici'],
        ['kod' => 'skladista.view',  'naziv' => 'Pregled skladišta',        'modul' => 'sifrarnici'],
        ['kod' => 'skladista.uredi', 'naziv' => 'Uređivanje skladišta',     'modul' => 'sifrarnici'],
        ['kod' => 'lokacije.view',   'naziv' => 'Pregled lokacija',         'modul' => 'sifrarnici'],
        ['kod' => 'lokacije.uredi',  'naziv' => 'Uređivanje lokacija',      'modul' => 'sifrarnici'],
        ['kod' => 'prevoznici.view',  'naziv' => 'Pregled prevoznika',      'modul' => 'sifrarnici'],
        ['kod' => 'prevoznici.uredi', 'naziv' => 'Uređivanje prevoznika',   'modul' => 'sifrarnici'],
        ['kod' => 'vozila.view',    'naziv' => 'Pregled vozila',           'modul' => 'sifrarnici'],
        ['kod' => 'vozila.uredi',   'naziv' => 'Uređivanje vozila',        'modul' => 'sifrarnici'],
        ['kod' => 'vozaci.view',    'naziv' => 'Pregled vozača',           'modul' => 'sifrarnici'],
        ['kod' => 'vozaci.uredi',   'naziv' => 'Uređivanje vozača',        'modul' => 'sifrarnici'],

        // Skladišne operacije (Faza 3)
        ['kod' => 'stanje.view',        'naziv' => 'Pregled stanja skladišta',   'modul' => 'skladiste'],
        ['kod' => 'ulaz.view',          'naziv' => 'Pregled ulaza',              'modul' => 'skladiste'],
        ['kod' => 'ulaz.uredi',         'naziv' => 'Kreiranje ulaza',            'modul' => 'skladiste'],
        ['kod' => 'ulaz.storniraj',     'naziv' => 'Storniranje ulaza',          'modul' => 'skladiste'],
        ['kod' => 'rezervacije.view',   'naziv' => 'Pregled rezervacija',        'modul' => 'skladiste'],
        ['kod' => 'rezervacije.uredi',  'naziv' => 'Kreiranje/otkazivanje rezervacija', 'modul' => 'skladiste'],
        ['kod' => 'premjestanja.view',  'naziv' => 'Pregled premještanja',       'modul' => 'skladiste'],
        ['kod' => 'premjestanja.uredi', 'naziv' => 'Kreiranje premještanja',     'modul' => 'skladiste'],

        // Otprema (Faza 4)
        ['kod' => 'otprema.view',      'naziv' => 'Pregled otprema/otpremnica', 'modul' => 'otprema'],
        ['kod' => 'otprema.uredi',     'naziv' => 'Kreiranje otprema',          'modul' => 'otprema'],
        ['kod' => 'otprema.storniraj', 'naziv' => 'Storniranje otprema',        'modul' => 'otprema'],

        // Izvještaji (Faza 5)
        ['kod' => 'izvjestaji.view', 'naziv' => 'Pregled izvještaja', 'modul' => 'izvjestaji'],
    ];

    $permisijaId = [];
    $insertPermisija = $db->prepare('INSERT INTO permisije (kod, naziv, modul) VALUES (:kod, :naziv, :modul)');
    $selectPermisija = $db->prepare('SELECT id FROM permisije WHERE kod = :kod');

    foreach ($permisije as $p) {
        $selectPermisija->execute(['kod' => $p['kod']]);
        $id = $selectPermisija->fetchColumn();
        if (!$id) {
            $insertPermisija->execute($p);
            $id = (int) $db->lastInsertId();
            echo "Kreirana permisija: {$p['kod']}\n";
        }
        $permisijaId[$p['kod']] = (int) $id;
    }

    // --- 3) Administrator dobija SVE permisije, vidljiv + pristup ---------------
    $upsertRolePermisija = $db->prepare(
        'INSERT INTO role_permisije (role_id, permisija_id, vidljiv, pristup)
         VALUES (:role_id, :permisija_id, 1, 1)
         ON DUPLICATE KEY UPDATE vidljiv = 1, pristup = 1'
    );
    foreach ($permisijaId as $id) {
        $upsertRolePermisija->execute(['role_id' => $roleId, 'permisija_id' => $id]);
    }
    echo "Administratoru dodijeljene sve permisije.\n";

    // --- 4) Osnovne stavke menija ------------------------------------------------
    $selectMeni = $db->prepare('SELECT id FROM meni_stavke WHERE naziv = :naziv AND roditelj_id <=> :roditelj_id');
    $insertMeni = $db->prepare(
        'INSERT INTO meni_stavke (roditelj_id, naziv, url, ikona, permisija_kod, redoslijed, aktivan)
         VALUES (:roditelj_id, :naziv, :url, :ikona, :permisija_kod, :redoslijed, 1)'
    );

    $selectMeni->execute(['naziv' => 'Dashboard', 'roditelj_id' => null]);
    if (!$selectMeni->fetchColumn()) {
        $insertMeni->execute([
            'roditelj_id' => null, 'naziv' => 'Dashboard', 'url' => 'dashboard',
            'ikona' => 'bi bi-speedometer2', 'permisija_kod' => 'dashboard.view', 'redoslijed' => 1,
        ]);
        echo "Kreirana stavka menija: Dashboard\n";
    }

    $selectMeni->execute(['naziv' => 'Postavke', 'roditelj_id' => null]);
    $postavkeId = $selectMeni->fetchColumn();
    if (!$postavkeId) {
        $insertMeni->execute([
            'roditelj_id' => null, 'naziv' => 'Postavke', 'url' => null,
            'ikona' => 'bi bi-gear', 'permisija_kod' => null, 'redoslijed' => 90,
        ]);
        $postavkeId = (int) $db->lastInsertId();
        echo "Kreirana stavka menija: Postavke\n";
    }

    $selectMeni->execute(['naziv' => 'Korisnici', 'roditelj_id' => $postavkeId]);
    if (!$selectMeni->fetchColumn()) {
        $insertMeni->execute([
            'roditelj_id' => $postavkeId, 'naziv' => 'Korisnici', 'url' => 'korisnici',
            'ikona' => 'bi bi-people', 'permisija_kod' => 'korisnici.view', 'redoslijed' => 1,
        ]);
        echo "Kreirana stavka menija: Postavke → Korisnici\n";
    }

    $selectMeni->execute(['naziv' => 'Uloge', 'roditelj_id' => $postavkeId]);
    if (!$selectMeni->fetchColumn()) {
        $insertMeni->execute([
            'roditelj_id' => $postavkeId, 'naziv' => 'Uloge', 'url' => 'role',
            'ikona' => 'bi bi-shield-check', 'permisija_kod' => 'role.view', 'redoslijed' => 2,
        ]);
        echo "Kreirana stavka menija: Postavke → Uloge\n";
    }

    $selectMeni->execute(['naziv' => 'Parametri sistema', 'roditelj_id' => $postavkeId]);
    if (!$selectMeni->fetchColumn()) {
        $insertMeni->execute([
            'roditelj_id' => $postavkeId, 'naziv' => 'Parametri sistema', 'url' => 'postavke/parametri-sistema',
            'ikona' => 'bi bi-sliders', 'permisija_kod' => 'postavke.parametri', 'redoslijed' => 3,
        ]);
        echo "Kreirana stavka menija: Postavke → Parametri sistema\n";
    }

    $selectMeni->execute(['naziv' => 'Podaci firme', 'roditelj_id' => $postavkeId]);
    if (!$selectMeni->fetchColumn()) {
        $insertMeni->execute([
            'roditelj_id' => $postavkeId, 'naziv' => 'Podaci firme', 'url' => 'postavke/podaci-firme',
            'ikona' => 'bi bi-building', 'permisija_kod' => 'postavke.podaci-firme', 'redoslijed' => 4,
        ]);
        echo "Kreirana stavka menija: Postavke → Podaci firme\n";
    }

    $selectMeni->execute(['naziv' => 'Backup baze', 'roditelj_id' => $postavkeId]);
    if (!$selectMeni->fetchColumn()) {
        $insertMeni->execute([
            'roditelj_id' => $postavkeId, 'naziv' => 'Backup baze', 'url' => 'postavke/backup',
            'ikona' => 'bi bi-hdd-network', 'permisija_kod' => 'postavke.backup', 'redoslijed' => 5,
        ]);
        echo "Kreirana stavka menija: Postavke → Backup baze\n";
    }

    // --- Šifrarnici (grupa) -------------------------------------------------------
    $selectMeni->execute(['naziv' => 'Šifrarnici', 'roditelj_id' => null]);
    $sifrarniciId = $selectMeni->fetchColumn();
    if (!$sifrarniciId) {
        $insertMeni->execute([
            'roditelj_id' => null, 'naziv' => 'Šifrarnici', 'url' => null,
            'ikona' => 'bi bi-journal-bookmark', 'permisija_kod' => null, 'redoslijed' => 50,
        ]);
        $sifrarniciId = (int) $db->lastInsertId();
        echo "Kreirana stavka menija: Šifrarnici\n";
    }

    $sifrarniciStavke = [
        ['naziv' => 'AB elementi',  'url' => 'elementi',     'ikona' => 'bi bi-bricks',        'permisija' => 'elementi.view',    'redoslijed' => 1],
        ['naziv' => 'Kategorije',   'url' => 'kategorije',   'ikona' => 'bi bi-tags',          'permisija' => 'kategorije.view',  'redoslijed' => 2],
        ['naziv' => 'Investitori',  'url' => 'investitori',  'ikona' => 'bi bi-building',      'permisija' => 'investitori.view', 'redoslijed' => 3],
        ['naziv' => 'Projekti',     'url' => 'projekti',     'ikona' => 'bi bi-kanban',        'permisija' => 'projekti.view',    'redoslijed' => 4],
        ['naziv' => 'Objekti',      'url' => 'objekti',      'ikona' => 'bi bi-house',         'permisija' => 'objekti.view',     'redoslijed' => 5],
        ['naziv' => 'Skladišta',    'url' => 'skladista',    'ikona' => 'bi bi-building-gear', 'permisija' => 'skladista.view',   'redoslijed' => 6],
        ['naziv' => 'Lokacije',     'url' => 'lokacije',     'ikona' => 'bi bi-geo-alt',       'permisija' => 'lokacije.view',    'redoslijed' => 7],
        ['naziv' => 'Prevoznici',   'url' => 'prevoznici',   'ikona' => 'bi bi-truck',         'permisija' => 'prevoznici.view',  'redoslijed' => 8],
        ['naziv' => 'Vozila',       'url' => 'vozila',       'ikona' => 'bi bi-truck-front',   'permisija' => 'vozila.view',      'redoslijed' => 9],
        ['naziv' => 'Vozači',       'url' => 'vozaci',       'ikona' => 'bi bi-person-badge',  'permisija' => 'vozaci.view',      'redoslijed' => 10],
    ];

    foreach ($sifrarniciStavke as $s) {
        $selectMeni->execute(['naziv' => $s['naziv'], 'roditelj_id' => $sifrarniciId]);
        if (!$selectMeni->fetchColumn()) {
            $insertMeni->execute([
                'roditelj_id' => $sifrarniciId, 'naziv' => $s['naziv'], 'url' => $s['url'],
                'ikona' => $s['ikona'], 'permisija_kod' => $s['permisija'], 'redoslijed' => $s['redoslijed'],
            ]);
            echo "Kreirana stavka menija: Šifrarnici → {$s['naziv']}\n";
        }
    }

    // --- Skladište (grupa) ---------------------------------------------------------
    $selectMeni->execute(['naziv' => 'Skladište', 'roditelj_id' => null]);
    $skladisteMenuId = $selectMeni->fetchColumn();
    if (!$skladisteMenuId) {
        $insertMeni->execute([
            'roditelj_id' => null, 'naziv' => 'Skladište', 'url' => null,
            'ikona' => 'bi bi-boxes', 'permisija_kod' => null, 'redoslijed' => 20,
        ]);
        $skladisteMenuId = (int) $db->lastInsertId();
        echo "Kreirana stavka menija: Skladište\n";
    }

    $skladisteStavke = [
        ['naziv' => 'Stanje skladišta', 'url' => 'stanje',        'ikona' => 'bi bi-clipboard-data', 'permisija' => 'stanje.view',       'redoslijed' => 1],
        ['naziv' => 'Ulaz',             'url' => 'ulaz',          'ikona' => 'bi bi-box-arrow-in-down', 'permisija' => 'ulaz.view',      'redoslijed' => 2],
        ['naziv' => 'Rezervacije',      'url' => 'rezervacije',   'ikona' => 'bi bi-bookmark-check', 'permisija' => 'rezervacije.view',  'redoslijed' => 3],
        ['naziv' => 'Premještanje',     'url' => 'premjestanja',  'ikona' => 'bi bi-arrow-left-right', 'permisija' => 'premjestanja.view', 'redoslijed' => 4],
    ];

    foreach ($skladisteStavke as $s) {
        $selectMeni->execute(['naziv' => $s['naziv'], 'roditelj_id' => $skladisteMenuId]);
        if (!$selectMeni->fetchColumn()) {
            $insertMeni->execute([
                'roditelj_id' => $skladisteMenuId, 'naziv' => $s['naziv'], 'url' => $s['url'],
                'ikona' => $s['ikona'], 'permisija_kod' => $s['permisija'], 'redoslijed' => $s['redoslijed'],
            ]);
            echo "Kreirana stavka menija: Skladište → {$s['naziv']}\n";
        }
    }

    // --- Otprema (grupa) ------------------------------------------------------------
    $selectMeni->execute(['naziv' => 'Otprema', 'roditelj_id' => null]);
    $otpremaMenuId = $selectMeni->fetchColumn();
    if (!$otpremaMenuId) {
        $insertMeni->execute([
            'roditelj_id' => null, 'naziv' => 'Otprema', 'url' => 'otprema',
            'ikona' => 'bi bi-truck', 'permisija_kod' => 'otprema.view', 'redoslijed' => 21,
        ]);
        echo "Kreirana stavka menija: Otprema\n";
    }

    // --- Izvještaji -------------------------------------------------------------------
    $selectMeni->execute(['naziv' => 'Izvještaji', 'roditelj_id' => null]);
    if (!$selectMeni->fetchColumn()) {
        $insertMeni->execute([
            'roditelj_id' => null, 'naziv' => 'Izvještaji', 'url' => 'izvjestaji',
            'ikona' => 'bi bi-bar-chart', 'permisija_kod' => 'izvjestaji.view', 'redoslijed' => 60,
        ]);
        echo "Kreirana stavka menija: Izvještaji\n";
    }

    // --- 5) Prvi admin korisnik ---------------------------------------------------
    $stmt = $db->prepare('SELECT id FROM korisnici WHERE korisnicko_ime = :ki');
    $stmt->execute(['ki' => $ADMIN_KORISNICKO_IME]);
    $postojeci = $stmt->fetchColumn();

    if (!$postojeci) {
        $db->prepare(
            'INSERT INTO korisnici (ime, prezime, korisnicko_ime, email, lozinka_hash, role_id, status, datum_kreiranja)
             VALUES (:ime, :prezime, :ki, :email, :hash, :role_id, "aktivan", NOW())'
        )->execute([
            'ime'     => 'Administrator',
            'prezime' => 'Sistema',
            'ki'      => $ADMIN_KORISNICKO_IME,
            'email'   => $ADMIN_EMAIL,
            'hash'    => password_hash($ADMIN_LOZINKA, PASSWORD_DEFAULT),
            'role_id' => $roleId,
        ]);
        echo "\nKreiran admin nalog:\n";
        echo "  Korisničko ime: $ADMIN_KORISNICKO_IME\n";
        echo "  Lozinka:        $ADMIN_LOZINKA\n";
        echo "  >>> OBAVEZNO promijenite ovu lozinku odmah nakon prve prijave. <<<\n";
    } else {
        echo "Admin korisnik '$ADMIN_KORISNICKO_IME' već postoji — preskačem kreiranje naloga.\n";
    }

    $db->commit();
    echo "\nSeed uspješno završen.\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "GREŠKA prilikom seed-ovanja: " . $e->getMessage() . "\n";
    exit(1);
}
