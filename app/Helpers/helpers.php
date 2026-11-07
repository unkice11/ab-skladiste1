<?php
/**
 * Globalne helper funkcije. Namjerno bez namespace-a radi jednostavne
 * upotrebe u view fajlovima.
 */

function config(?string $kljuc = null, $default = null)
{
    $config = $GLOBALS['__config'] ?? [];

    if ($kljuc === null) {
        return $config;
    }

    $dijelovi = explode('.', $kljuc);
    $vrijednost = $config;

    foreach ($dijelovi as $dio) {
        if (!is_array($vrijednost) || !array_key_exists($dio, $vrijednost)) {
            return $default;
        }
        $vrijednost = $vrijednost[$dio];
    }

    return $vrijednost;
}

/** Generiše puni URL na osnovu APP_URL iz konfiguracije. Nikad ne hardkodirati URL u kodu. */
function base_url(string $putanja = ''): string
{
    $base = rtrim(config('app.url'), '/');
    $putanja = ltrim($putanja, '/');
    return $putanja === '' ? $base . '/' : $base . '/' . $putanja;
}

function redirect(string $putanja = ''): void
{
    header('Location: ' . base_url($putanja));
    exit;
}

/** Escape za sigurno ispisivanje u HTML (zaštita od XSS-a). */
function e(?string $vrijednost): string
{
    return htmlspecialchars($vrijednost ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    $naziv = config('security.csrf_token_name');
    if (empty($_SESSION[$naziv])) {
        $_SESSION[$naziv] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$naziv];
}

function csrf_field(): string
{
    $naziv = config('security.csrf_token_name');
    return '<input type="hidden" name="' . e($naziv) . '" value="' . e(csrf_token()) . '">';
}

function csrf_provjeri(): bool
{
    $naziv = config('security.csrf_token_name');
    $poslani = $_POST[$naziv] ?? '';
    $ocekivani = $_SESSION[$naziv] ?? '';
    return $poslani !== '' && $ocekivani !== '' && hash_equals($ocekivani, $poslani);
}

function flash(string $tip, string $poruka): void
{
    $_SESSION['_flash'][$tip][] = $poruka;
}

function uzmi_flash(): array
{
    $poruke = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $poruke;
}

function old(string $polje, $default = '')
{
    return $_SESSION['_old'][$polje] ?? $default;
}

function sacuvaj_old(array $podaci): void
{
    $_SESSION['_old'] = $podaci;
}

function ocisti_old(): void
{
    unset($_SESSION['_old']);
}

function trenutni_korisnik(): ?array
{
    return \App\Core\Auth::korisnik();
}

function ima_pristup(string $kodPermisije): bool
{
    return \App\Core\Auth::imaPristup($kodPermisije);
}

function je_vidljivo(string $kodPermisije): bool
{
    return \App\Core\Auth::jeVidljivo($kodPermisije);
}

function app_log(string $poruka): void
{
    $putanja = rtrim(config('paths.log_path'), '/') . '/app.log';
    $linija = '[' . date('Y-m-d H:i:s') . '] ' . $poruka . PHP_EOL;
    error_log($linija, 3, $putanja);
}

/**
 * Aktivne lokacije prikazane kao "Skladište / Zona-Oznaka", radi lakšeg
 * snalaženja u padajućoj listi. Dijele je Ulaz/Rezervacije/Premještanja
 * kontroleri, pa je ovdje kao zajednički helper umjesto duplog koda.
 */
function lokacije_sa_skladistem(): array
{
    $db = \App\Core\Database::konekcija();
    $stmt = $db->query(
        "SELECT l.id, CONCAT(s.naziv, ' / ', COALESCE(l.zona, ''), IF(l.zona IS NOT NULL, '-', ''), l.oznaka) AS naziv
         FROM lokacije l JOIN skladista s ON s.id = l.skladiste_id
         WHERE l.aktivan = 1 AND s.aktivan = 1
         ORDER BY s.naziv, l.zona, l.oznaka"
    );
    return $stmt->fetchAll();
}

/**
 * Aktivni AB elementi za izbor u Ulazu/Otpremi/Rezervacijama/Premještanju —
 * naziv opcije počinje šifrom ("AG22 — Zid panel tip A") tako da se
 * element može prepoznati/pronaći po šifri (i browser-ov type-to-select
 * u <select> elementu skače na prvo poklapanje po početku teksta).
 */
function elementi_za_select(): array
{
    $db = \App\Core\Database::konekcija();
    $stmt = $db->query(
        "SELECT id, sifra, CONCAT(sifra, ' — ', naziv) AS naziv
         FROM ab_elementi WHERE aktivan = 1 ORDER BY sifra"
    );
    return $stmt->fetchAll();
}

/**
 * PREKON je fiksni brend softvera (logo/ikona) — NIJE vezan za Postavke →
 * Podaci firme, koji je isključivo podatak KLIJENTOVE firme prikazan na
 * otpremnicama/izvještajima. Ove dvije stvari su namjerno razdvojene.
 */
function prekon_ikona_url(int $velicina = 64): string
{
    $fajl = $velicina <= 64 ? 'prekon-icon-64.png' : 'prekon-icon-128.png';
    return base_url('assets/img/' . $fajl);
}
