# Permisije — AB Skladište

## Kako radi

Svaka permisija ima kod (npr. `korisnici.izmijeni`) i dvije nezavisne
oznake:

- **vidljiv** — da li se povezana stavka menija prikazuje korisniku
- **pristup** — da li server dozvoljava izvršenje/otvaranje te akcije

Uloga (`role`) nosi osnovni set permisija. Administrator po korisniku
može dodati **individualni override** (Korisnici → [korisnik] →
Permisije) koji ima prednost nad rolom — npr. korisniku sa rolom
"Skladištar" ručno oduzeti ili dodati nešto van uobičajenog za tu rolu.

Meni (`meni_stavke`) se generiše iz baze — stavka je vidljiva samo ako
je `vidljiv = 1` za trenutnog korisnika. **Sakrivanje u meniju nije
sigurnosna mjera** — svaka ruta i dalje provjerava `pristup` na serveru
nezavisno od toga da li je stavka u meniju.

## Spisak permisija po modulu

| Modul | Kod | Značenje |
|---|---|---|
| dashboard | `dashboard.view` | Pristup dashboardu |
| korisnici | `korisnici.view` | Pregled liste korisnika |
| korisnici | `korisnici.kreiraj` | Kreiranje novog korisnika |
| korisnici | `korisnici.izmijeni` | Izmjena, deaktivacija, reset lozinke |
| korisnici | `korisnici.permisije` | Dodjela individualnih permisija |
| postavke | `postavke.parametri` | Izmjena standardne gustine betona i sl. |
| postavke | `postavke.podaci-firme` | Izmjena podataka firme i logotipa |
| postavke | `postavke.backup` | Preuzimanje backup-a cijele baze |
| sifrarnici | `<naziv>.view` / `<naziv>.uredi` | Za svaki od 9 šifrarnika (kategorije, investitori, projekti, objekti, skladista, lokacije, prevoznici, vozila, vozaci) — `.uredi` pokriva kreiranje, izmjenu i aktivaciju/deaktivaciju |
| sifrarnici | `elementi.view` / `elementi.uredi` | AB elementi (posebno od ostalih šifrarnika, ali ista dva nivoa) |
| skladiste | `stanje.view` | Pregled stanja i skladišne kartice |
| skladiste | `ulaz.view` / `ulaz.uredi` | Pregled / kreiranje ulaza |
| skladiste | `ulaz.storniraj` | Storniranje ulaza (namjerno odvojeno od `ulaz.uredi` — osjetljivija akcija) |
| skladiste | `rezervacije.view` / `rezervacije.uredi` | Pregled / kreiranje i otkazivanje rezervacija |
| skladiste | `premjestanja.view` / `premjestanja.uredi` | Pregled / kreiranje premještanja |
| otprema | `otprema.view` / `otprema.uredi` | Pregled i otpremnice / kreiranje otprema |
| izvjestaji | `izvjestaji.view` | Pristup svim izvještajima (nije razdvojeno po pojedinačnom izvještaju) |

## Namjerne pojednostavljene granularnosti

Spec je za skladišne/otpremne module predvidio punu
view/create/edit/delete/print/export granularnost. U implementaciji je
to svedeno na:

- **Šifrarnici**: `.view` + `.uredi` (uredi = create+edit+status)
- **Skladišne operacije**: `.view` + `.uredi`, plus `ulaz.storniraj` kao
  jedini treći nivo (jer je storno dovoljno osjetljiva, nepovratna akcija
  da zaslužuje svoju permisiju)
- **Izvještaji**: jedna permisija za sve izvještaje, bez print/export kao
  posebnih prava

Ovo je flagovana pretpostavka — ako ti treba finija granulacija (npr.
"smije kreirati otpremu ali ne i printati otpremnicu"), to je izmjena u
`routes/web.php` (dodavanje novog koda) + `seed.php` (nova permisija) +
provjera na dotičnoj ruti — mehanizam već postoji, samo nije iskorišten
do te granularnosti.

## Dodavanje nove permisije (za buduće module)

1. Dodati red u `$permisije` niz u `database/seeders/seed.php`.
2. Ako treba nova stavka menija, dodati u odgovarajući `*Stavke` niz u
   istom fajlu.
3. Pokrenuti `php database/seeders/seed.php` ponovo (idempotentno).
4. U `routes/web.php`, dodati kod permisije kao treći argument
   `$router->get()`/`post()` na dotičnoj ruti.
