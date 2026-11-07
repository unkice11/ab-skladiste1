# Baza podataka — AB Skladište

Sve tabele su InnoDB, `utf8mb4`/`utf8mb4_unicode_ci`. Migracije su
kumulativne i moraju se uvesti tim redoslijedom (vidi
[INSTALLATION.md](INSTALLATION.md)).

## Pregled migracija

| Fajl | Šta uvodi |
|---|---|
| `001_osnovna_sema.sql` | `korisnici`, `role`, `permisije`, `role_permisije`, `korisnik_permisije`, `meni_stavke`, `audit_log`, `korisnik_remember_tokens` |
| `002_sifrarnici.sql` | `kategorije_elemenata`, `ab_elementi`, `investitori`, `projekti`, `objekti`, `skladista`, `lokacije`, `prevoznici`, `vozila`, `vozaci`, `postavke_sistema` |
| `003_skladiste.sql` | `brojaci_dokumenata`, `skladisno_stanje`, `ulazi` + `ulazi_stavke`, `rezervacije`, `premjestanja`, `skladisna_kartica` |
| `004_stanje_po_investitoru_projektu.sql` | ALTER: `skladisno_stanje` dobija `investitor_id`/`projekat_id` u PRIMARY KEY; isto na `premjestanja`; informativno na `skladisna_kartica` |
| `005_rucna_zapremina.sql` | ALTER: `ab_elementi` dobija `automatski_obracun_zapremine`/`rucna_zapremina_m3` |
| `006_otprema_i_firma.sql` | `podaci_firme`, `otpreme` + `otpreme_stavke`; ALTER: `ulazi` dobija storno kolone, `projekti` dobija `planirani_broj_elemenata`, `rezervacije.status` dobija `iskoriscena`, `skladisna_kartica.tip_promjene` dobija `storno_ulaza` |

## Ključne tabele

### `skladisno_stanje` — fizičko stanje

```
PRIMARY KEY (element_id, lokacija_id, investitor_id, projekat_id)
```

`investitor_id`/`projekat_id` = **0** znači "generička traka, bez
investitora/projekta" — NAMJERNO nije NULL (NULL ne može biti dio
PRIMARY KEY-a u MySQL-u). Nema FK na te dvije kolone u ovoj tabeli (0 bi
kršio referencijalni integritet) — integritet čuva aplikacija
(`StanjeRepository::norm()` normalizuje prazno/NULL u 0 na svakom
upisu/poređenju).

Dvije pošiljke istog elementa na istu lokaciju, za različite investitore,
su DVA odvojena reda ovdje — ne jedan zbirni. Vidi
[ARCHITECTURE.md](ARCHITECTURE.md) za poslovnu logiku iza ovoga.

### `skladisna_kartica` — potpuna istorija promjena

Svaki upis ima `tip_promjene`:
`ulaz`, `izlaz`, `premjestanje_iz`, `premjestanje_u`, `rezervacija`,
`oslobadjanje_rezervacije`, `storno_ulaza`. `stanje_nakon` je NULL za
rezervaciju/oslobađanje (ne mijenjaju fizičko stanje, samo "slobodno").

### `rezervacije.status`

`aktivna` → `otkazana` (ručno otkazano) ili `iskoriscena` (potrošeno kroz
otpremu — potpuno ili djelimično, vidi `OtpremaRepository::potrosiRezervaciju()`).

### `brojaci_dokumenata` — centralna numeracija

```
kljuc VARCHAR(30) PK   -- npr. 'UL-2026', 'OTP-2026'
zadnji_broj INT
```

Increment se radi kroz `SELECT ... FOR UPDATE` unutar iste transakcije
koja kreira dokument (`NumeracijaRepository::sljedeciBroj()`) —
**namjerno nikad** `SELECT MAX(id)+1`, koje nije sigurno kod paralelnih
zahtjeva.

### `postavke_sistema` / `podaci_firme` — key-value i single-row postavke

`postavke_sistema` je generički key-value (trenutno samo
`standardna_gustina_betona_kg_m3`). `podaci_firme` je fiksiran na jedan
red (`id=1`, CHECK constraint) — naziv/adresa/JIB/PDV/logo za otpremnice.

## Poslovna pravila implementirana na nivou baze (CHECK/FK)

- `skladisno_stanje.kolicina >= 0` (CHECK) — dodatni sloj zaštite od
  negativnog stanja, iako se primarna provjera radi u
  `StanjeRepository::smanji()` prije UPDATE-a.
- Sve `*_stavke` i `rezervacije`/`premjestanja` tabele imaju
  `CHECK (kolicina > 0)`.
- FK sa `ON DELETE RESTRICT` na `ab_elementi`/`lokacije` iz kretanja
  (ulazi_stavke, otpreme_stavke, skladisna_kartica) — element ili lokacija
  se ne mogu obrisati (fizički) dok postoji istorija koja ih referencira;
  to je i razlog zašto šifrarnici koriste soft-delete (aktivan/neaktivan)
  umjesto DELETE-a.
- FK sa `ON DELETE SET NULL` na investitor/projekat/objekat/prevoznik/
  vozilo/vozač iz dokumenata — brisanje šifrarničkog zapisa (kad bi se
  ikad uradilo) ne bi srušilo istorijske dokumente, samo bi izgubili tu
  referencu.
