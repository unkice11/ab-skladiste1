# Instalacija — AB Skladište (Prekon-lite v1.0)

## 1. Preduslovi

- Laragon (Apache + MySQL/MariaDB + PHP 8.x)
- Nijedna dodatna PHP ekstenzija van standardnog Laragon paketa (koristi se
  samo `pdo_mysql`, `fileinfo`, `session` — sve uključeno po defaultu)

## 2. Kopiranje projekta

Kopirati cijeli folder projekta u:
```
C:\laragon\www\ab-skladiste\
```

Laragon → **Start All** (Apache & MySQL).

Laragon "Auto Virtual Hosts" po defaultu prepoznaje `public/` podfolder
kao document root za projekte u `www/`. Ako se `http://ab-skladiste.test`
ne otvori automatski, ručno podesiti virtual host tako da document root
pokazuje na `ab-skladiste/public`, **ne** na korijen projekta.

Provjeriti da je Apache `mod_rewrite` uključen (Laragon default) —
potreban je zbog `public/.htaccess` (pretty URL-ovi).

## 3. Baza podataka

1. Kreirati praznu bazu:
   ```sql
   CREATE DATABASE ab_skladiste CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Uvesti migracije **tačno ovim redoslijedom** (svaka gradi na prethodnoj):
   ```bash
   mysql -u root -p ab_skladiste < database/migrations/001_osnovna_sema.sql
   mysql -u root -p ab_skladiste < database/migrations/002_sifrarnici.sql
   mysql -u root -p ab_skladiste < database/migrations/003_skladiste.sql
   mysql -u root -p ab_skladiste < database/migrations/004_stanje_po_investitoru_projektu.sql
   mysql -u root -p ab_skladiste < database/migrations/005_rucna_zapremina.sql
   mysql -u root -p ab_skladiste < database/migrations/006_otprema_i_firma.sql
   ```
   (Isto se može uraditi kroz phpMyAdmin → Import, fajl po fajl, istim
   redoslijedom. Migracije se NE mogu pokretati kroz "Run SQL" tako što se
   zalijepi kod PHP fajla — `seed.php` u koraku 4 je PHP skripta, ne SQL.)

## 4. Početni podaci (seed)

`database/seeders/seed.php` je **PHP skripta** — pokreće se kroz PHP
interpreter, ne kroz phpMyAdmin. Dva načina:

**Laragon Terminal (preporučeno):**
```bash
cd C:\laragon\www\ab-skladiste
php database\seeders\seed.php
```

**Kroz browser** (ako terminal nije dostupan): privremeno kopirati
`seed.php` u `public/`, otvoriti ga u browseru, pa **odmah obrisati** iz
`public/` nakon što se izvrši (sadrži privremenu admin lozinku u čistom
tekstu dok se ne obriše/premjesti).

Skripta je idempotentna (bezbjedno je pokrenuti je više puta — provjerava
šta već postoji) i na kraju ispisuje korisničko ime i privremenu lozinku
prvog admin naloga:
```
Kreiran admin nalog:
  Korisničko ime: admin
  Lozinka:        PromijeniMe123!
  >>> OBAVEZNO promijenite ovu lozinku odmah nakon prve prijave. <<<
```

## 5. Konfiguracija

Sve env-zavisne vrijednosti su u `config/config.php` (URL, DB pristup,
sesije, sigurnosni parametri). Pri prebacivanju na live hosting izmijeniti
SAMO ovaj fajl — posebno `app.url`, `app.debug` → `false`, i `db.*`.
Aplikacija nigdje drugo nema hardkodiran URL ili DB pristup.

## 6. Prijava

`http://ab-skladiste.test/prijava` → admin nalog iz koraka 4 → **odmah
promijeniti lozinku** (Postavke → Korisnici → admin → Reset lozinke).

## 7. Nakon instalacije

- Postaviti stvarne podatke firme: Postavke → Podaci firme (koriste se na
  otpremnicama).
- Provjeriti/podesiti standardnu gustinu betona: Postavke → Parametri
  sistema.
- `database/seeders/seed.php` sadrži privremenu lozinku — premjestiti ga
  van web-dostupnog direktorija ili obrisati nakon prvog pokretanja.

## Rješavanje čestih problema

| Simptom | Uzrok / rješenje |
|---|---|
| Nove stavke menija se ne vide nakon update-a | Migracija i/ili `seed.php` nisu (ponovo) pokrenuti nakon preuzimanja novije verzije. Uraditi korake 3-4 ponovo — oba su idempotentna. |
| "SQLSTATE... Invalid parameter number" na pretrazi | Stara verzija koda prije ispravke iz [CHANGELOG.md](CHANGELOG.md) — preuzeti najnoviju arhivu. |
| phpMyAdmin javlja grešku na `seed.php` | `seed.php` je PHP fajl, ne SQL — vidi korak 4, ne ubacivati ga u SQL tab. |
| Meni stavka postoji ali klik vodi na 403 | Korisnik nema `pristup` za tu permisiju — provjeriti Korisnici → [korisnik] → Permisije, ili ulogu korisnika. |
