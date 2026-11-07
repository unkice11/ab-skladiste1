<?php

namespace App\Core;

use App\Repositories\KorisnikRepository;
use App\Repositories\PermisijaRepository;
use App\Repositories\AuditRepository;

/**
 * Centralna autentifikacija i autorizacija.
 *
 * Permisije trenutno prijavljenog korisnika se učitaju JEDNOM po
 * zahtjevu (iz baze: role permisije + individualni override-i) i drže
 * u statičkoj varijabli — ne u sesiji — kako bi promjena permisija od
 * strane administratora odmah važila na sljedećem zahtjevu korisnika,
 * a ne tek nakon novog logina.
 */
class Auth
{
    private static ?array $korisnik = null;
    private static bool $ucitan = false;

    /** @var array<string, array{vidljiv:bool,pristup:bool}>|null */
    private static ?array $permisije = null;

    public static function pokusajPrijavu(string $korisnickoIme, string $lozinka, bool $zapamtiMe = false): array
    {
        $repo = new KorisnikRepository();

        if (self::jeZakljucano($korisnickoIme)) {
            return ['uspjeh' => false, 'poruka' => 'Previše neuspješnih pokušaja prijave. Pokušajte ponovo za nekoliko minuta.'];
        }

        $korisnik = $repo->pronadjiPoKorisnickomImenu($korisnickoIme);

        if (!$korisnik || $korisnik['status'] !== 'aktivan' || !password_verify($lozinka, $korisnik['lozinka_hash'])) {
            self::zabiljeziNeuspjeliPokusaj($korisnickoIme);
            (new AuditRepository())->zabiljezi(
                $korisnik['id'] ?? null,
                'neuspjela_prijava',
                'auth',
                'korisnici',
                $korisnik['id'] ?? null,
                null,
                ['korisnicko_ime' => $korisnickoIme]
            );
            return ['uspjeh' => false, 'poruka' => 'Pogrešno korisničko ime ili lozinka.'];
        }

        self::ocistiNeuspjelePokusaje($korisnickoIme);

        // Regeneracija session ID-a nakon uspješne prijave — zaštita od session fixation.
        session_regenerate_id(true);

        $_SESSION['korisnik_id'] = $korisnik['id'];
        self::$korisnik = $korisnik;
        self::$ucitan = true;
        self::$permisije = null;

        $repo->azurirajPosljednjuPrijavu($korisnik['id']);

        if ($zapamtiMe) {
            self::postaviRememberKolacic((int) $korisnik['id']);
        }

        (new AuditRepository())->zabiljezi(
            $korisnik['id'],
            'prijava',
            'auth',
            'korisnici',
            $korisnik['id']
        );

        return ['uspjeh' => true];
    }

    public static function odjava(): void
    {
        $korisnik = self::korisnik();
        if ($korisnik) {
            (new AuditRepository())->zabiljezi($korisnik['id'], 'odjava', 'auth', 'korisnici', $korisnik['id']);
        }

        if (!empty($_COOKIE['zapamti_me'])) {
            [$selektor] = array_pad(explode(':', $_COOKIE['zapamti_me'], 2), 2, '');
            if ($selektor !== '') {
                $db = Database::konekcija();
                $stmt = $db->prepare('DELETE FROM korisnik_remember_tokens WHERE selektor = :s');
                $stmt->execute(['s' => $selektor]);
            }
            setcookie('zapamti_me', '', time() - 3600, '/');
        }

        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    public static function jePrijavljen(): bool
    {
        return self::korisnik() !== null;
    }

    public static function korisnik(): ?array
    {
        if (self::$ucitan) {
            return self::$korisnik;
        }

        self::$ucitan = true;

        if (empty($_SESSION['korisnik_id'])) {
            $izKolacica = self::prijaviIzRememberKolacica();
            return self::$korisnik = $izKolacica;
        }

        $repo = new KorisnikRepository();
        $korisnik = $repo->pronadjiPoId((int) $_SESSION['korisnik_id']);

        if (!$korisnik || $korisnik['status'] !== 'aktivan') {
            self::odjava();
            return self::$korisnik = null;
        }

        return self::$korisnik = $korisnik;
    }

    private static function ucitajPermisije(): array
    {
        if (self::$permisije !== null) {
            return self::$permisije;
        }

        $korisnik = self::korisnik();
        if (!$korisnik) {
            return self::$permisije = [];
        }

        self::$permisije = (new PermisijaRepository())->efektivnePermisijeZaKorisnika((int) $korisnik['id']);

        return self::$permisije;
    }

    public static function imaPristup(string $kod): bool
    {
        $permisije = self::ucitajPermisije();
        return !empty($permisije[$kod]['pristup']);
    }

    public static function jeVidljivo(string $kod): bool
    {
        $permisije = self::ucitajPermisije();
        return !empty($permisije[$kod]['vidljiv']);
    }

    /** "Zapamti me" — dugotrajni kolačić nezavisan od PHP sesije (selector/validator šema). */
    private static function postaviRememberKolacic(int $korisnikId): void
    {
        $selektor = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $trajanje = (int) config('session.remember_lifetime');

        $db = Database::konekcija();
        $stmt = $db->prepare(
            'INSERT INTO korisnik_remember_tokens (korisnik_id, selektor, validator_hash, istice)
             VALUES (:korisnik_id, :selektor, :hash, DATE_ADD(NOW(), INTERVAL :sekundi SECOND))'
        );
        $stmt->execute([
            'korisnik_id' => $korisnikId,
            'selektor'    => $selektor,
            'hash'        => hash('sha256', $validator),
            'sekundi'     => $trajanje,
        ]);

        setcookie('zapamti_me', $selektor . ':' . $validator, [
            'expires'  => time() + $trajanje,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function prijaviIzRememberKolacica(): ?array
    {
        if (empty($_COOKIE['zapamti_me'])) {
            return null;
        }

        [$selektor, $validator] = array_pad(explode(':', $_COOKIE['zapamti_me'], 2), 2, '');
        if ($selektor === '' || $validator === '') {
            return null;
        }

        $db = Database::konekcija();
        $stmt = $db->prepare('SELECT * FROM korisnik_remember_tokens WHERE selektor = :s AND istice > NOW() LIMIT 1');
        $stmt->execute(['s' => $selektor]);
        $red = $stmt->fetch();

        if (!$red || !hash_equals($red['validator_hash'], hash('sha256', $validator))) {
            return null;
        }

        $repo = new KorisnikRepository();
        $korisnik = $repo->pronadjiPoId((int) $red['korisnik_id']);
        if (!$korisnik || $korisnik['status'] !== 'aktivan') {
            return null;
        }

        session_regenerate_id(true);
        $_SESSION['korisnik_id'] = $korisnik['id'];

        return $korisnik;
    }

    // -----------------------------------------------------------------
    // Zaštita od brute-force napada na login formu (po korisničkom imenu).
    // -----------------------------------------------------------------

    private static function jeZakljucano(string $korisnickoIme): bool
    {
        $podaci = self::ucitajPokusaje($korisnickoIme);
        $max = (int) config('security.max_login_attempts');
        $lockout = (int) config('security.login_lockout_seconds');

        return $podaci['broj'] >= $max && (time() - $podaci['zadnji']) < $lockout;
    }

    private static function zabiljeziNeuspjeliPokusaj(string $korisnickoIme): void
    {
        $podaci = self::ucitajPokusaje($korisnickoIme);
        $podaci['broj']++;
        $podaci['zadnji'] = time();
        self::sacuvajPokusaje($korisnickoIme, $podaci);
    }

    private static function ocistiNeuspjelePokusaje(string $korisnickoIme): void
    {
        unset($_SESSION['_login_pokusaji'][mb_strtolower($korisnickoIme)]);
    }

    private static function ucitajPokusaje(string $korisnickoIme): array
    {
        return $_SESSION['_login_pokusaji'][mb_strtolower($korisnickoIme)] ?? ['broj' => 0, 'zadnji' => 0];
    }

    private static function sacuvajPokusaje(string $korisnickoIme, array $podaci): void
    {
        $_SESSION['_login_pokusaji'][mb_strtolower($korisnickoIme)] = $podaci;
    }
}
