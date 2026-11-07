# AB Skladište (Prekon-lite v1.0)

Poslovni web sistem za evidenciju i upravljanje skladištem AB
(armirano-beton) prefabrikovanih elemenata. Plain PHP 8.x / MySQL / PDO,
bez frameworka — vidi [ARCHITECTURE.md](ARCHITECTURE.md) za razloge te
odluke i strukturu koda.

## Brzi start

Puno uputstvo (Laragon, baza, seed, česti problemi): **[INSTALLATION.md](INSTALLATION.md)**

```bash
# nakon kopiranja projekta u C:\laragon\www\ab-skladiste\ i kreiranja baze:
mysql -u root -p ab_skladiste < database/migrations/001_osnovna_sema.sql
mysql -u root -p ab_skladiste < database/migrations/002_sifrarnici.sql
mysql -u root -p ab_skladiste < database/migrations/003_skladiste.sql
mysql -u root -p ab_skladiste < database/migrations/004_stanje_po_investitoru_projektu.sql
mysql -u root -p ab_skladiste < database/migrations/005_rucna_zapremina.sql
mysql -u root -p ab_skladiste < database/migrations/006_otprema_i_firma.sql
php database/seeders/seed.php
```

Otvori `http://ab-skladiste.test/prijava` sa admin nalogom koji seed
ispiše na kraju, i **odmah promijeni lozinku**.

## Dokumentacija

| Fajl | Sadržaj |
|---|---|
| [INSTALLATION.md](INSTALLATION.md) | Instalacija korak-po-korak, česti problemi |
| [DATABASE.md](DATABASE.md) | Šema, migracije, ključne tabele i poslovna pravila u bazi |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Struktura koda, slojevi, generički šifrarnik-engine |
| [SECURITY.md](SECURITY.md) | Autentifikacija, autorizacija, CSRF, upload, poznata zamka sa PDO |
| [PERMISSIONS.md](PERMISSIONS.md) | Puni spisak permisija po modulu, kako dodati novu |
| [TESTING.md](TESTING.md) | Ručni test scenariji po fazi |
| [CHANGELOG.md](CHANGELOG.md) | Istorija po fazi, uključujući ispravke bugova |

## Šta sistem pokriva (do sada)

- **Korisnici i permisije** — granularne (vidljiv/pristup po permisiji),
  uloga + individualni override, audit log.
- **Šifrarnici** — AB elementi (auto/ručan obračun zapremine i težine),
  kategorije, investitori, projekti (+ planirani broj elemenata i
  analitika realizacije), objekti, skladišta, lokacije, prevoznici,
  vozila, vozači.
- **Skladišne operacije** — ulaz (+ storno sa obaveznim razlogom), stanje
  (razdvojeno po investitoru/projektu), rezervacije, premještanje,
  skladišna kartica (puna istorija).
- **Otprema** — dokument sa stavkama (opciona veza na rezervaciju),
  upozorenje o nosivosti vozila, printable otpremnica.
- **Izvještaji** — stanje, stanje po lokaciji/investitoru, ulazi/otpreme
  po periodu, kretanje po elementu, otpremljeno po objektu, rezervisani
  elementi, ukupna otpremljena težina (mjesečno) — svi sa print i CSV
  export opcijom.
- **Postavke** — parametri sistema, podaci firme (+ logo), backup baze
  (pun SQL dump za preuzimanje).
- **Dashboard** — ključni brojevi, posljednje aktivnosti, brze akcije.

## Pretpostavke koje čekaju tvoju potvrdu

Mjesta gdje sam morao nešto odlučiti bez izričitog dogovora — sve radi,
ali ako želiš drugačije, javi (svaka je izmjena šeme/logike, ne kozmetika):

1. Permisije šifrarnika su samo `.view`/`.uredi`, ne puna
   view/create/edit/delete/print/export granularnost iz spec-a.
2. Brisanje je uvijek soft-delete (aktivan/neaktivan), nikad fizički DELETE.
3. Objekat je vezan na Projekat, ne direktno na Investitora.
4. Premještanje smije nositi samo slobodnu (nerezervisanu) količinu —
   rezervacija ne "putuje" sa robom na novu lokaciju.
5. Rezervacija se pravi iz iste investitor/projekat trake za koju se
   rezerviše (nema "rezervišem za X iz zalihe Y").
6. Planirani/otpremljeni broj elemenata na projektu je jedan zbirni broj,
   ne raspoređen po tipu elementa unaprijed.
7. Nema PDF biblioteke — štampa/PDF ide kroz browser ("Sačuvaj kao PDF").
8. Permisija za izvještaje je jedna (`izvjestaji.view`) za sve izvještaje.
9. PREKON logo je rekreirana SVG marka (nemam originalni fajl) — automatski
   se zamjenjuje pravim logom čim se uploaduje kroz Postavke → Podaci
   firme. Pozadina login forme je gradijent sa geometrijskim oblicima
   umjesto fotografije gradilišta iz smjernica (fotografija nije bila
   dostupna kao fajl).

## Šta iz spec-a još nije rađeno

- Dodatni filteri/kombinacije na postojećim izvještajima.
- Formalni automatizovani testovi (PHPUnit i sl.) — trenutno samo ručni
  scenariji u [TESTING.md](TESTING.md).
- Formalni finalni security/performance/UX review kao poseban prolaz
  (sigurnosne prakse su ugrađene od početka — vidi SECURITY.md — ali
  nije rađen poseban sistematski prolaz da se to provjeri na jednom mjestu).
