<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\KorisniciController;
use App\Controllers\RoleController;
use App\Controllers\KategorijeController;
use App\Controllers\InvestitoriController;
use App\Controllers\ProjektiController;
use App\Controllers\ObjektiController;
use App\Controllers\SkladistaController;
use App\Controllers\LokacijeController;
use App\Controllers\PrevozniciController;
use App\Controllers\VozilaController;
use App\Controllers\VozaciController;
use App\Controllers\ElementiController;
use App\Controllers\ParametriSistemaController;
use App\Controllers\StanjeController;
use App\Controllers\UlazController;
use App\Controllers\RezervacijeController;
use App\Controllers\PremjestanjaController;
use App\Controllers\OtpremaController;
use App\Controllers\PodaciFirmeController;
use App\Controllers\IzvjestajiController;
use App\Controllers\BackupController;
use App\Core\Router;

/** @var Router $router */

// --- Autentifikacija (javne rute, bez permisije) ------------------------
$router->get('prijava', [new AuthController(), 'prikaziPrijavu']);
$router->post('prijava', [new AuthController(), 'prijava']);
$router->get('odjava', [new AuthController(), 'odjava']);

// --- Dashboard ------------------------------------------------------------
$router->get('dashboard', [new DashboardController(), 'index'], 'dashboard.view');

// --- Postavke -> Korisnici --------------------------------------------------
$router->get('korisnici', [new KorisniciController(), 'index'], 'korisnici.view');
$router->get('korisnici/dodaj', [new KorisniciController(), 'formaZaKreiranje'], 'korisnici.kreiraj');
$router->post('korisnici/dodaj', [new KorisniciController(), 'sacuvajNovog'], 'korisnici.kreiraj');
$router->get('korisnici/izmijeni/{id}', [new KorisniciController(), 'formaZaIzmjenu'], 'korisnici.izmijeni');
$router->post('korisnici/izmijeni/{id}', [new KorisniciController(), 'azurirajPostojeceg'], 'korisnici.izmijeni');
$router->post('korisnici/status/{id}', [new KorisniciController(), 'promijeniStatus'], 'korisnici.izmijeni');
$router->post('korisnici/resetuj-lozinku/{id}', [new KorisniciController(), 'resetujLozinku'], 'korisnici.izmijeni');
$router->get('korisnici/permisije/{id}', [new KorisniciController(), 'prikaziPermisije'], 'korisnici.permisije');
$router->post('korisnici/permisije/{id}', [new KorisniciController(), 'sacuvajPermisije'], 'korisnici.permisije');

// --- Postavke -> Uloge --------------------------------------------------------
$roleKontroler = new RoleController();
registruj_sifrarnik_rute($router, 'role', 'role', $roleKontroler);
$router->get('role/permisije/{id}', [$roleKontroler, 'prikaziPermisije'], 'role.uredi');
$router->post('role/permisije/{id}', [$roleKontroler, 'sacuvajPermisije'], 'role.uredi');

// --- Postavke -> Parametri sistema -------------------------------------------
$router->get('postavke/parametri-sistema', [new ParametriSistemaController(), 'prikazi'], 'postavke.parametri');
$router->post('postavke/parametri-sistema', [new ParametriSistemaController(), 'sacuvaj'], 'postavke.parametri');

// --- Postavke -> Podaci firme -------------------------------------------------
$router->get('postavke/podaci-firme', [new PodaciFirmeController(), 'prikazi'], 'postavke.podaci-firme');
$router->post('postavke/podaci-firme', [new PodaciFirmeController(), 'sacuvaj'], 'postavke.podaci-firme');

// --- Sifrarnici: genericka CRUD registracija --------------------------------
/**
 * Svi jednostavni sifrarnici dijele isti obrazac ruta:
 *   GET  <ruta>                    -> lista                (<prefix>.view)
 *   GET  <ruta>/dodaj              -> forma kreiranja       (<prefix>.uredi)
 *   POST <ruta>/dodaj              -> snimanje novog        (<prefix>.uredi)
 *   GET  <ruta>/izmijeni/{id}      -> forma izmjene         (<prefix>.uredi)
 *   POST <ruta>/izmijeni/{id}      -> snimanje izmjene      (<prefix>.uredi)
 *   POST <ruta>/status/{id}        -> aktiviraj/deaktiviraj (<prefix>.uredi)
 */
function registruj_sifrarnik_rute(Router $router, string $ruta, string $prefix, object $kontroler): void
{
    $router->get($ruta, [$kontroler, 'index'], $prefix . '.view');
    $router->get($ruta . '/dodaj', [$kontroler, 'formaZaKreiranje'], $prefix . '.uredi');
    $router->post($ruta . '/dodaj', [$kontroler, 'sacuvajNovog'], $prefix . '.uredi');
    $router->get($ruta . '/izmijeni/{id}', [$kontroler, 'formaZaIzmjenu'], $prefix . '.uredi');
    $router->post($ruta . '/izmijeni/{id}', [$kontroler, 'azurirajPostojeceg'], $prefix . '.uredi');
    $router->post($ruta . '/status/{id}', [$kontroler, 'promijeniStatus'], $prefix . '.uredi');
}

registruj_sifrarnik_rute($router, 'kategorije', 'kategorije', new KategorijeController());
registruj_sifrarnik_rute($router, 'investitori', 'investitori', new InvestitoriController());
registruj_sifrarnik_rute($router, 'projekti', 'projekti', new ProjektiController());
$router->get('projekti/analitika/{id}', [new ProjektiController(), 'analitika'], 'projekti.view');
registruj_sifrarnik_rute($router, 'objekti', 'objekti', new ObjektiController());
registruj_sifrarnik_rute($router, 'skladista', 'skladista', new SkladistaController());
registruj_sifrarnik_rute($router, 'lokacije', 'lokacije', new LokacijeController());
registruj_sifrarnik_rute($router, 'prevoznici', 'prevoznici', new PrevozniciController());
registruj_sifrarnik_rute($router, 'vozila', 'vozila', new VozilaController());
registruj_sifrarnik_rute($router, 'vozaci', 'vozaci', new VozaciController());
registruj_sifrarnik_rute($router, 'elementi', 'elementi', new ElementiController());

// --- Skladiste (Faza 3) ------------------------------------------------------
$stanjeKontroler = new StanjeController();
$router->get('stanje', [$stanjeKontroler, 'index'], 'stanje.view');
$router->get('stanje/kartica/{elementId}', [$stanjeKontroler, 'karticaElementa'], 'stanje.view');

$ulazKontroler = new UlazController();
$router->get('ulaz', [$ulazKontroler, 'index'], 'ulaz.view');
$router->get('ulaz/dodaj', [$ulazKontroler, 'formaZaKreiranje'], 'ulaz.uredi');
$router->post('ulaz/dodaj', [$ulazKontroler, 'sacuvaj'], 'ulaz.uredi');
$router->get('ulaz/{id}', [$ulazKontroler, 'pregled'], 'ulaz.view');
$router->post('ulaz/storniraj/{id}', [$ulazKontroler, 'storniraj'], 'ulaz.storniraj');

$rezervacijeKontroler = new RezervacijeController();
$router->get('rezervacije', [$rezervacijeKontroler, 'index'], 'rezervacije.view');
$router->get('rezervacije/dodaj', [$rezervacijeKontroler, 'formaZaKreiranje'], 'rezervacije.uredi');
$router->post('rezervacije/dodaj', [$rezervacijeKontroler, 'sacuvaj'], 'rezervacije.uredi');
$router->post('rezervacije/otkazi/{id}', [$rezervacijeKontroler, 'otkazi'], 'rezervacije.uredi');

$premjestanjaKontroler = new PremjestanjaController();
$router->get('premjestanja', [$premjestanjaKontroler, 'index'], 'premjestanja.view');
$router->get('premjestanja/dodaj', [$premjestanjaKontroler, 'formaZaKreiranje'], 'premjestanja.uredi');
$router->post('premjestanja/dodaj', [$premjestanjaKontroler, 'sacuvaj'], 'premjestanja.uredi');

$otpremaKontroler = new OtpremaController();
$router->get('otprema', [$otpremaKontroler, 'index'], 'otprema.view');
$router->get('otprema/dodaj', [$otpremaKontroler, 'formaZaKreiranje'], 'otprema.uredi');
$router->post('otprema/dodaj', [$otpremaKontroler, 'sacuvaj'], 'otprema.uredi');
$router->get('otprema/{id}/otpremnica', [$otpremaKontroler, 'otpremnica'], 'otprema.view');
$router->post('otprema/storniraj/{id}', [$otpremaKontroler, 'storniraj'], 'otprema.storniraj');
$router->get('otprema/{id}', [$otpremaKontroler, 'pregled'], 'otprema.view');

// --- Izvjestaji ---------------------------------------------------------------
$izvjestajiKontroler = new IzvjestajiController();
$router->get('izvjestaji', [$izvjestajiKontroler, 'index'], 'izvjestaji.view');
$router->get('izvjestaji/ulazi', [$izvjestajiKontroler, 'ulazi'], 'izvjestaji.view');
$router->get('izvjestaji/otpreme', [$izvjestajiKontroler, 'otpreme'], 'izvjestaji.view');
$router->get('izvjestaji/rezervisani', [$izvjestajiKontroler, 'rezervisani'], 'izvjestaji.view');
$router->get('izvjestaji/po-investitoru', [$izvjestajiKontroler, 'poInvestitoru'], 'izvjestaji.view');
$router->get('izvjestaji/stanje', [$izvjestajiKontroler, 'stanje'], 'izvjestaji.view');
$router->get('izvjestaji/po-objektu', [$izvjestajiKontroler, 'poObjektu'], 'izvjestaji.view');
$router->get('izvjestaji/po-elementu', [$izvjestajiKontroler, 'poElementu'], 'izvjestaji.view');
$router->get('izvjestaji/po-lokaciji', [$izvjestajiKontroler, 'poLokaciji'], 'izvjestaji.view');
$router->get('izvjestaji/ukupna-tezina', [$izvjestajiKontroler, 'ukupnaTezina'], 'izvjestaji.view');

// --- Postavke -> Backup baze ---------------------------------------------------
$backupKontroler = new BackupController();
$router->get('postavke/backup', [$backupKontroler, 'prikazi'], 'postavke.backup');
$router->get('postavke/backup/preuzmi', [$backupKontroler, 'preuzmi'], 'postavke.backup');
