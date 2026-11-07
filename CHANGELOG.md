# Changelog — AB Skladište (Prekon-lite v1.0)

## Faza 1 — Arhitektura i autentifikacija
- Centralna konfiguracija, PDO konekcija, ruter, bootstrap.
- Login/logout, sesije, "Zapamti me", brute-force zaštita, CSRF.
- Korisnici (CRUD, status, reset lozinke).
- Granularni permission sistem (vidljiv/pristup po permisiji, uloga +
  individualni override), centralni meni iz baze.
- Audit log, osnovni dashboard.

## Faza 2 — Šifrarnici
- Generički šifrarnik-engine (`BazniSifrarnikRepository`/`Controller`) za
  9 jednostavnih šifrarnika: kategorije, investitori, projekti, objekti,
  skladišta, lokacije, prevoznici, vozila, vozači.
- AB elementi (posebna logika): automatski obračun zapremine/težine iz
  dimenzija i standardne gustine, sa mogućnošću ručnog unosa težine.
- Postavke → Parametri sistema (standardna gustina betona).

## Faza 3 — Skladišne operacije
- Ulaz na skladište (dokument + stavke), centralna numeracija
  (`SELECT...FOR UPDATE`, ne `MAX(id)+1`).
- Stanje skladišta, rezervacije, premještanje, skladišna kartica.
- **Dopuna**: fizičko stanje razdvojeno po (element, lokacija,
  investitor, projekat), ne samo (element, lokacija) — na zahtjev.
- **Dopuna**: nezavisan ručni unos zapremine (pored već postojećeg ručnog
  unosa težine) za elemente sa izbočinama/udubljenjima — na zahtjev.

## Faza 4 — Otprema, otpremnica, storno, izvještaji (prvi krug)
- Otprema (dokument + stavke, opciona veza na rezervaciju koja se troši
  potpuno ili djelimično), upozorenje o nosivosti vozila (ne blokira).
- Otpremnica — printable A4, zaglavlje firme, štampa kroz browser.
- Podaci firme (naziv/adresa/JIB/PDV/logo).
- **Na zahtjev**: storno ulaza sa obaveznim razlogom (`ulaz.storniraj`),
  poništava stanje na istoj investitor/projekat traci, odbija se ako
  stanje više nije dovoljno.
- **Na zahtjev**: planirani broj elemenata po projektu + analitika
  realizacije (planirano/otpremljeno/preostalo, raspodjela po elementu).
- 5 prvih izvještaja (stanje, ulazi/otpreme po periodu, rezervisani
  elementi, po investitoru), svaki sa print varijantom.

## Ispravka: PDO "Invalid parameter number"

Otkriveno kroz stvarno testiranje (pretraga AB elemenata). Uzrok: upiti
oblika `WHERE sifra LIKE :p OR naziv LIKE :p` — isti named placeholder
korišten više puta u istom upitu. Sa `PDO::ATTR_EMULATE_PREPARES =>
false` (koje ovaj projekat koristi namjerno, radi pravih pripremljenih
upita na nivou baze), MySQL native prepared statements NE dozvoljavaju
ponavljanje istog named placeholder-a — za razliku od PDO emulacije, koja
to tiho podržava. Ispravljeno u pet fajlova (svaki dobio poseban
placeholder po pojavljivanju: `:p1`, `:p2`, ...):

- `App\Core\BazniSifrarnikRepository` (generisao dinamički duplikate za
  šifrarnike sa >1 kolonom za pretragu — kategorije, investitori,
  skladišta, lokacije, prevoznici, vozila, vozači)
- `AbElementiRepository`, `StanjeRepository`, `RezervacijaRepository`,
  `KorisnikRepository`

Cijela kodna baza je pretražena (regex sken svih SQL literala) da se
potvrdi da nema drugih pojavljivanja istog obrasca. Vidi SECURITY.md za
pravilo za dalji razvoj.

## Faza 4 (dopuna) — Preostali izvještaji, backup, dashboard, branding

- Novi izvještaji: po objektu (period), kretanje po elementu (ulaz/
  otprema u periodu + trenutno stanje), po lokaciji, ukupna otpremljena
  težina (mjesečni pregled po godini).
- CSV export na svim izvještajima (plain PHP `fputcsv`, bez biblioteke).
- Backup baze (Postavke → Backup baze) — pun SQL dump kroz PDO, bez
  `mysqldump`/`shell_exec` zavisnosti; `postavke.backup` permisija
  (samo Administrator po defaultu).
- Dashboard: dodano "Otpreme danas" pored "Ulazi danas", lista
  "Posljednje aktivnosti" (zadnjih 8 ulaza/otprema/premještanja
  hronološki), panel "Brze akcije".
- Branding: login forma prikazuje "Prekon-lite v1.0"; svi printani
  izvještaji i otpremnica imaju footer "Prekon-lite v1.0 / Pametniji
  procesi . Created by Edim Unkic".

## Poznate pretpostavke koje čekaju potvrdu

Vidi listu u glavnom README.md ("Pretpostavke koje čekaju odgovor") —
nije ponovljena ovdje da se izbjegne duplo održavanje.

## Nije još rađeno

- Preostali izvještaji iz spec-a koji nisu pokriveni gore (npr. dodatne
  kombinacije filtera po periodu na izvještajima koji trenutno filtriraju
  samo po drugim kriterijumima).
- Formalni automatizovani testovi (postoje pisani test scenariji za
  ručno testiranje u README.md, ne i PHPUnit i sl.).
- Formalni finalni security/performance/UX review kao poseban prolaz.

## Brendiranje — PREKON dizajn sistem

Na zahtjev, primijenjen kompletan brend paket (logo, boje, tipografija,
UI stil, redizajn login forme) dostavljen kao slika smjernica:

- Centralna tema `public/assets/css/prekon-theme.css` — CSS varijable za
  boje/gradijente + override Bootstrap komponenti (dugmad, forme, kartice,
  bedževi, alert-i) — cijela aplikacija se rebrendira sa jednog mjesta,
  bez izmjene svakog view fajla pojedinačno.
- Font Inter (Google Fonts) učitan u svim layoutima.
- Sidebar, login forma i print zaglavlje prikazuju logo firme (Postavke →
  Podaci firme) ako je uploadovan, inače rekreiranu SVG "M" marku
  (`prekon_marka_svg()`/`prekon_logo_ili_marka()` u `helpers.php`).
- Login forma potpuno redizajnirana: split-screen (forma lijevo, brend
  panel sa gradijentom i geometrijskim oblicima desno), tekstovi tačno
  kako je zadano ("Dobro došli u PREKON", "Prijavite se da biste
  nastavili.", footer "Siguran sistem. Bolja organizacija. Veća
  produktivnost."), toggle za prikaz/skrivanje lozinke.
- `config('app.name')` promijenjen sa "AB Skladište" na "PREKON".

**Dvije stvari iz smjernica NISU doslovno preuzete** (nisu bile dostupne
kao odvojeni fajlovi, samo unutar mockup slike):
1. Pravi logo fajl — zamijenjen rekreiranom SVG markom koja se automatski
   povlači sa Podaci firme čim se pravi logo uploaduje.
2. Fotografija gradilišta u pozadini login forme — zamijenjena gradijentom
   sa apstraktnim geometrijskim oblicima u istom stilu, jer izvorna
   fotografija nije dostupna kao fajl niti ima poznatu licencu.

Print footer ("Prekon-lite v1.0 / Pametniji procesi...") nije mijenjan —
ostaje kako je ranije zadano, odvojeno od ovog brend paketa.

## Uloge (Role) — ekran za upravljanje, i pravi PREKON logo/ikona

**Problem koji je ovo pokrenuo**: novokreirani korisnik bez izabrane uloge
nema NIKAKAV pristup (ni dashboardu) — po dizajnu (permisije = uloga +
individualni izuzeci, a bez uloge nema ni jedno ni drugo), ali dotad nije
postojao ekran da se uloge uopšte kreiraju/uređuju kroz aplikaciju (samo
"Administrator" iz seed-a). Dodano:

- **Postavke → Uloge** (`RoleController`, generički šifrarnik-engine za
  osnovni CRUD naziv/opis) + dugme "Permisije" po redu koje vodi na
  poseban ekran (`role/permisije/{id}`) za čekiranje vidljiv/pristup po
  permisiji — analogno ekranu za korisnike, ali ovo je OSNOVNI set za tu
  ulogu (ne override). Nove permisije: `role.view`, `role.uredi`.
- `PermisijaRepository::permisijeZaEkranRole()`/`sacuvajZaRole()` — pandan
  postojećim metodama za korisnički override, ali pišu direktno u
  `role_permisije`.
- Forma za kreiranje/izmjenu korisnika sada upozorava (žuti tekst ispod
  padajuće liste uloga) da korisnik bez uloge nema pristup dok mu se
  ručno ne dodijele permisije.

**Razdvajanje brenda softvera od podataka klijenta**: dobijeni su stvarni
PREKON fajlovi (kvadratna ikona i široka hero fotografija sa ugrađenim
logom/sloganom). Ispravljena je ranija greška gdje je sidebar/login marka
zavisila od Postavke → Podaci firme — to polje je isključivo za
KLIJENTOVU firmu na otpremnicama i nema veze sa softverskim brendom:

- `public/assets/img/prekon-icon-64.png`/`prekon-icon-128.png` — fiksna
  ikonica (sidebar, login, favicon, print footer) — više NE zavisi od
  Podaci firme.
- `public/assets/img/prekon-hero.jpg` (komprimovano sa originalnih ~1.8MB
  na ~180KB) — puna pozadina login stranice; na Dashboard-u prikazana kao
  suptilan "wallpaper" (6% providnosti) iza sadržaja.
- Print zaglavlje (otpremnica/izvještaji) sada, ako Podaci firme nisu
  popunjeni, prikazuje neutralnu poruku umjesto da nazad padne na PREKON
  brend — ta dva su namjerno nezavisna.
- Uklonjena rekreirana SVG marka i njena "logo ili marka" logika (više
  nije potrebna sad kad postoje pravi fajlovi).

## Prepoznavanje elemenata po šifri, filter/print šifrarnika, investitor/projekat na elementu

Migracija: `database/migrations/007_element_investitor_projekat.sql`

- **Ulaz i Otprema**: polje "AB element" u stavkama je sada tekstualno sa
  HTML5 `<datalist>` prijedlozima — kucanjem šifre ili dijela naziva se
  lista filtrira, a izborom se element automatski prepoznaje (skriveno
  polje se puni ID-jem preko JS mape šifra/naziv → ID). Rezervacije i
  Premještanje i dalje koriste padajuću listu, ali sad sa "šifra — naziv"
  oznakom (prepoznavanje po šifri kroz browser-ov type-to-select).
- **AB elementi mogu biti vezani za investitora/projekat** u samom
  šifrarniku (novo, opciono) — npr. kalup/tip elementa napravljen posebno
  za jedan projekat. Ovo je NEZAVISNO od `skladisno_stanje` investitor/
  projekat podjele (Faza 3 dopuna), koja prati čija je roba fizički na
  skladištu — ovo prati da li je sam TIP elementa vezan za projekat.
- **Filter i štampa šifrarnika AB elemenata**: lista elemenata sad ima
  filter po investitoru/projektu (pored postojeće pretrage), dugme
  "Štampaj" (print-friendly, isti filteri) i "CSV" export.
- **Opis je sad vidljiv u prikazu** — dodata kolona "Opis" u listu AB
  elemenata, i na ekranu i na štampi (ranije se opis unosio ali nigdje
  nije prikazivan van forme za izmjenu).

## A4 otpremnica i print layout

- `layouts/print.php` prepisan: stvarni `@page { size: A4 }` sa marginom 12 mm, ekranski
  pregled izgleda kao list papira (210 mm), zaglavlje tabele se ponavlja na svakoj stranici,
  redovi se ne lome preko stranica, podnožje "Prekon-lite v1.0..." na dnu lista. Široki
  izvještaji (stanje skladišta, šifrarnik AB elemenata) se štampaju A4 položeno
  (`$printOrijentacija = 'landscape'`), ostali uspravno.
- `otprema/otpremnica.php` prepisana za A4: naslov + broj + datum, dvije kutije (investitor/
  projekat/objekat i prevoznik/vozilo sa nosivošću/vozač), tabela stavki sa **okvirnom težinom
  kg/kom i ukupno kg za svaku stavku**, red **UKUPNO (komada i kg, plus u tonama) prikazan kad
  otpremnica ima više od jedne stavke**, "Zbirno po elementu" tabela samo ako se isti element
  javlja u više redova, napomena, tri polja za potpis (otpremio / vozač / primio) i "Dokument
  sastavio: korisnik, datum".
- Težine se prikazuju sa 2 decimale da se aritmetika na dokumentu može provjeriti
  (količina × kg/kom = ukupno; zbir redova = ukupno).

## Projekat: "Proizvedeno" iz ulaza, "Za proizvesti" i "Za otpremu"

Bez nove migracije — koristi postojeći `projekti.planirani_broj_elemenata`
i postojeće `ulazi`/`otpreme` tabele.

- `ProjektAnalitikaRepository::proizvedenoPoSvimProjektima()` — zbir
  `ulazi_stavke.kolicina` za AKTIVNE (ne stornirane) ulaze vezane za
  projekat. Ulaz bez izabranog projekta se ne računa nijednom projektu.
- Lista projekata ima nove kolone: **Proizvedeno (ulaz)**, **Za
  proizvesti** (planirano − proizvedeno), pored postojećih Otpremljeno i
  **Za otpremu** (planirano − otpremljeno).
- Stranica "Analitika" po projektu: proizvedeno/za proizvesti/realizacija
  proizvodnje, i odvojeno otpremljeno/za otpremu/realizacija otpreme, plus
  "Proizvedeno, čeka otpremu" (proizvedeno − otpremljeno) kao poseban
  broj — koliko je već napravljeno a još stoji na skladištu neotpremljeno.
  Tabela po elementu sad ima kolone Proizvedeno/Otpremljeno/Čeka otpremu.

**Pretpostavka**: "proizvedeno" = fizički ušlo na skladište (ulaz), ne
neka posebna evidencija proizvodnog naloga/pogona — sistem trenutno nema
zaseban koncept "proizvodnog naloga" prije ulaska na skladište (spec
sekcija 55 dijagram toka ide PROIZVEDEN/PRIMLJEN → ULAZ NA SKLADIŠTE kao
jedan korak). Ako fizička proizvodnja i ulaz na skladište kod tebe nisu
isti trenutak (npr. nešto se proizvede ali čeka kontrolu kvaliteta prije
skladištenja), "proizvedeno" ovdje zapravo znači "primljeno na
skladište" — javi ako treba razdvojiti.

## Responsive dizajn (mobilni pristup)

Bez izmjene baze. Glavne promjene:

- **Sidebar** (`layouts/glavni.php`) prepravljen u Bootstrap 5.3
  `offcanvas-lg` — na desktopu (≥992px) izgleda i ponaša se identično kao
  prije (statičan, uvijek prikazan), na manjim ekranima postaje klizni
  meni koji se otvara hamburger dugmetom u topbar-u i zatvara se
  automatski čim se izabere stavka menija (X dugme ili dodirom van
  panela).
- Topbar: ime korisnika se sakriva na jako uskim ekranima (ostaje samo
  ikonica sa padajućim menijem), da ne gura hamburger dugme.
- Tabele sa dinamičkim redovima (stavke Ulaza i Otpreme) sad su omotane
  u `.table-responsive` — vodoravno skrolaju umjesto da razvuku cijelu
  stranicu na uskom ekranu (ostale tabele su to već imale).
- Print/A4 pregled (`layouts/print.php`): na uskim ekranima se list
  papira može vodoravno skrolati umjesto da se stisne ili razvuče izvan
  ekrana — pravilo za sam ispis (`@media print`) nije dirano.
- `viewport-fit=cover` dodano radi ispravnog ponašanja na telefonima sa
  "notch" ekranom (safe-area padding na topbar-u).

**Nije rađeno** (van dogovorenog obima za sada): birane forme sa puno
kolona u redu (npr. filter forme sa 3-4 polja u nizu) i dalje se lome u
Bootstrap-ovoj standardnoj mreži (stackuju se jedno ispod drugog na
uskom ekranu) — to je prihvatljivo ponašanje ali nije posebno
optimizovano/skraćeno za dodir prstom. Ako nakon testiranja na telefonu
neki konkretan ekran bude nezgodan za rad, javi koji, pa ga doradimo
pojedinačno.

## Responsive — dovršavanje (sitni popravci)

Nastavak prethodne dopune, nakon sistematskog pregleda svih ekrana:

- "Posljednje aktivnosti" na dashboard-u: red (naziv dokumenta + datum)
  se na uskom ekranu slaže jedno ispod drugog umjesto da se stisne u
  jedan red (`flex-column flex-sm-row`).
- Svih 9 zaglavlja tipa "Naslov + dugme Novi..." (šifrarnici, Ulaz,
  Otprema, Rezervacije, Premještanja, Korisnici, pregled Ulaza/Otpreme)
  sad dozvoljavaju prelamanje (`flex-wrap`) umjesto da stisnu dugme uz
  dugačak naslov na uskom ekranu.
- Provjereno cijelim projektom: nema više neomotanih tabela, nema
  fiksnih piksel-širina koje bi izazvale vodoravni scroll cijele
  stranice, login stranica se uklapa i na najužim telefonskim ekranima
  (~320px).

Ovim je responsive prolaz završen za trenutni obim ekrana. Kao i ranije:
ako neki konkretan ekran na stvarnom telefonu bude nezgodan, javi koji pa
ga doradimo pojedinačno — ovo je pregled "na oko" (kod nije testiran u
pravom browseru sa ove strane).

## Ispravka: navigacija nije radila nakon responsive update-a

**Uzrok**: linkovi u sidebar meniju su imali `data-bs-dismiss="offcanvas"`
(dodano da se mobilni panel sam zatvori pri izboru stavke). Bootstrap-ov
JS za `data-bs-dismiss` poziva `event.preventDefault()` na klik — na
dugmetu to je OK (dugme ionako ništa ne "navigira"), ali na `<a href>`
linku to blokira samu navigaciju. Svaki klik na stavku menija je bio
nevidljivo poništen prije nego što je browser stigao otvoriti href.

**Ispravka**: uklonjen `data-bs-dismiss`/`data-bs-target` sa linkova
stavki menija (`app/Views/layouts/glavni.php`). Pošto je ovo klasična
multi-page aplikacija (ne SPA), klik na stavku ionako učitava novu
stranicu pa se offcanvas panel sam "zatvori" zajedno sa cijelim DOM-om —
eksplicitni JS dismiss na linku nikad nije ni bio potreban. Dugme za
zatvaranje (X) u uglu panela zadržava dismiss, jer je to stvarno dugme
bez href-a.

## Otpremnica: napomena umjesto lokacije; storno otpreme

Migracija: `database/migrations/008_storno_otpreme.sql` (+ seed.php za novu permisiju `otprema.storniraj`).

- **Otpremnica (A4 štampa)**: kolona "Lokacija" zamijenjena kolonom
  "Napomena" koja prikazuje polje **Opis** AB elementa iz šifrarnika
  (za tu šifru). Lokacija se i dalje bira pri kreiranju otpreme i
  prikazuje na ekranu u pregledu otpreme (potrebna je za knjiženje
  stanja) — samo je uklonjena sa odštampanog dokumenta.
  *Pretpostavka*: "napomena iz šifrarnika" = postojeće polje Opis (šifrarnik
  nema zasebno polje "napomena"). Ako je potrebno posebno polje, javi.
- **Storno otpreme sa obaveznim razlogom** (`OtpremaRepository::storniraj()`):
  vraća fizičko stanje koje je otprema umanjila (na istu investitor/projekat
  traku); ako je stavka trošila rezervaciju — potpuno iskorišćena se vraća
  u 'aktivna', djelimično umanjena dobija nazad oduzetu količinu, a ručno
  otkazana se ne dira. Nema scenarija odbijanja (povećanje stanja ne može
  dati negativno). Nova vrsta u skladišnoj kartici: `storno_otpreme`.
  Nova permisija `otprema.storniraj` (odvojena od `otprema.uredi`).
- Lista i pregled otprema prikazuju status (Aktivna/Stornirana), na
  pregledu razlog i datum storna. **Odštampana stornirana otpremnica nosi
  crveni natpis "STORNIRANA — NEVAŽEĆI DOKUMENT"** sa razlogom, da se ne
  može zamijeniti za važeći dokument.
