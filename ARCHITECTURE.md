# Arhitektura — AB Skladište

```
/config/config.php          centralna konfiguracija (URL, DB, sesije, sigurnost)
/public/index.php           front controller (jedina ulazna tačka)
/public/.htaccess           pretty URL rewrite ka index.php
/public/uploads/logos/      javno dostupan upload (logo firme) — jedini javni upload folder
/app/Core/                  bootstrap, Router, Database, Auth, Controller, generički šifrarnik-engine
/app/Controllers/           kontroleri
/app/Repositories/          pristup bazi — sva poslovna logika i transakcije žive ovdje
/app/Views/                 view fajlovi + layouts/glavni.php (sidebar) i layouts/print.php (štampa)
/app/Helpers/helpers.php    globalne funkcije (base_url, e, csrf_field, ima_pristup, ...)
/routes/web.php             definicija SVIH ruta + potrebnih permisija
/database/migrations/       SQL šema, kumulativna (vidi DATABASE.md)
/database/seeders/          početni seed (admin, permisije, meni) — idempotentan
/storage/logs/, /uploads/   privatni fajlovi (van web-korijena)
```

## Slojevi

**Controller** prima HTTP zahtjev, validira ulaz, poziva **Repository**,
priprema podatke za **View**. Kontroleri ne pišu SQL direktno (izuzev
par ad-hoc upita za select opcije, npr. `lokacije_sa_skladistem()` helper).

**Repository** sadrži SVU poslovnu logiku i sve SQL upite. Repository
metode koje mijenjaju stanje (Ulaz, Rezervacija, Premještanje, Otprema,
storno) same otvaraju/zatvaraju transakciju (`beginTransaction`/`commit`/
`rollBack`) — kontroler samo hvata `RuntimeException` za prikaz greške.

**View** je čist prikaz — jedina logika u view fajlovima je iteracija i
formatiranje (number_format, date). Layout (`layouts/glavni.php`) generiše
meni iz baze (`MeniRepository`), ne iz hardkodiranog HTML-a.

## Generički šifrarnik-engine

Devet "jednostavnih" šifrarnika (kategorije, investitori, projekti,
objekti, skladišta, lokacije, prevoznici, vozila, vozači) dijele:

- `App\Core\BazniSifrarnikRepository` — konfigurabilan CRUD (tabela,
  kolone za pretragu, opcioni LEFT JOIN za prikaz FK naziva)
- `App\Core\BazniSifrarnikController` — generički index/forma/snimanje/
  status, konfigurisan poljima forme i kolonama liste
- `app/Views/sifrarnik/lista.php` + `forma.php` — dijele ih svi

Konkretan kontroler (npr. `InvestitoriController`) je samo konfiguracija
u konstruktoru — ne piše se CRUD kod devet puta. `ProjektiController`
override-uje `index()` da doda kolone realizacije (planirano/otpremljeno/
preostalo) koje dolaze iz druge tabele (`otpreme_stavke`) — generički
engine to ne može sam, pa se dodaje kroz opcioni `dodatneAkcije` hook u
`sifrarnik/lista.php`.

`AbElementiRepository`/`ElementiController` NISU generički — zapremina i
okvirna težina se računaju pri svakom čitanju (iz dimenzija ILI ručnih
vrijednosti), ne čuvaju se kao gotova kolona.

## Skladišne operacije — transakcijski obrazac

Ulaz, Rezervacija, Premještanje, Otprema i Storno ulaza svi prate isti
obrazac u svom repository-ju:

```
beginTransaction()
  generiši broj dokumenta (NumeracijaRepository, SELECT...FOR UPDATE)
  insert header
  za svaku stavku:
    provjeri raspoloživu količinu (StanjeRepository, sa FOR UPDATE)
    insert stavka
    ažuriraj skladisno_stanje (povecaj/smanji)
    upiši skladisna_kartica red
commit() / rollBack() na bilo kojoj grešci
```

Fizičko stanje je posebno praćeno po (element, lokacija, investitor,
projekat) — vidi DATABASE.md. To znači da SVAKA operacija koja mijenja
stanje mora znati (ili normalizovati na 0) investitor/projekat "traku"
sa kojom radi — `StanjeRepository::norm()` je centralna tačka za tu
normalizaciju.

## Štampa i izvještaji

`Controller::renderPrint()` renderuje view kroz `layouts/print.php`
(bez sidebar-a, sa zaglavljem firme iz `PodaciFirmeRepository`) —
koristi ga i otpremnica i svi izvještaji. Isti view fajl (npr.
`izvjestaji/stanje.php`) se koristi i za normalan prikaz (sa filter
formom) i za print (bez forme) — razlikuje ih `$stampaj` flag.

CSV export (`IzvjestajiController::izvezCsv()`) koristi već dohvaćene
podatke iz iste metode koja renderuje HTML — nema duplog upita za bazu.

PDF se ne generiše na serveru (nema dodane biblioteke) — štampa ide kroz
browser (`window.print()`), a "Sačuvaj kao PDF" u print dijalogu
pokriva potrebu za PDF fajlom.

## Zašto neke stvari NISU generičke

- **AB elementi**: zapremina/težina je izračunata vrijednost sa dva
  nezavisna prekidača (automatski/ručno) — previše specifično za
  generički engine.
- **Projekti**: nasljeđuje generički engine ALI override-uje `index()`
  zbog kolona realizacije — pokazuje kako se generički engine proširuje
  bez duplirania cijelog CRUD-a.
- **Korisnici**: ima sopstveni kontroler (permisije, reset lozinke, status)
  jer su te akcije specifične za korisnike i ne uklapaju se u
  "naziv/opis/aktivan" obrazac ostalih šifrarnika.
