# Testni scenariji — AB Skladište

Ručni test scenariji pisani tokom razvoja, po fazi/dopuni. Nisu automatizovani (PHPUnit i sl.) — vidi CHANGELOG.md.

## Faza 1 — Autentifikacija i permisije

1. **Pogrešna lozinka** → poruka "Pogrešno korisničko ime ili lozinka.",
   korisnik ostaje na `/prijava`.
2. **Neaktivan korisnik** → isto ponašanje kao pogrešna lozinka.
3. **5 uzastopnih pogrešnih pokušaja** za isto korisničko ime → sistem
   privremeno odbija dalje pokušaje na `login_lockout_seconds`.
4. **Pristup `/dashboard` bez prijave** → redirekcija na `/prijava`.
5. **Pristup `/korisnici` bez `korisnici.view` pristupa** → 403, i stavka
   se uopšte ne prikazuje u meniju.
6. **Direktan unos URL-a** `/korisnici/dodaj` bez `korisnici.kreiraj`
   pristupa → 403, čak i ako je `korisnici.view` dozvoljen.
7. **POST bez CSRF tokena** → 419.
8. **Odjava** → sesija uništena, `/dashboard` ponovo traži prijavu.
9. **Reset lozinke** administratora nad drugim korisnikom → korisnik se
   ne može više prijaviti starom lozinkom.
10. **Individualni permission override** — oduzeti korisniku permisiju koju
    ima preko role, kroz Korisnici → Permisije → treba odmah izgubiti
    pristup toj akciji (permisije se učitavaju po zahtjevu, ne u sesiji).

## Faza 2 — Šifrarnici

1. Kreirati kategoriju, pa AB element sa tom kategorijom — kategorija se
   ispravno prikazuje u listi elemenata.
2. Element sa dužina=6, širina=0.30, visina=0.40, gustina=2500 (automatski
   obračun) → zapremina 0,720 m³, okvirna težina 1.800 kg (provjeriti i u
   formi live pregled i nakon snimanja u listi).
3. Isti element, prebaciti na "ručna težina" sa vrijednošću npr. 1750 kg →
   lista prikazuje 1750 kg i oznaku "ručno".
4. Promjena standardne gustine u Parametrima sistema NE mijenja gustinu
   već postojećih elemenata (samo je default za buduće).
5. Kreirati investitora → projekat (vezan na tog investitora) → objekat
   (vezan na taj projekat) → provjeriti da se nazivi ispravno prikazuju u
   listama projekata/objekata.
6. Kreirati skladište → lokaciju vezanu na to skladište.
7. Kreirati prevoznika → vozilo i vozača vezane na tog prevoznika.
8. Korisniku bez `elementi.uredi` (ali sa `elementi.view`) → vidi listu
   AB elemenata ali ne vidi dugme "Novi element", i direktan pristup
   `/elementi/dodaj` vraća 403.
9. Pokušaj kreiranja AB elementa sa šifrom koja već postoji → greška
   "Šifra je već zauzeta".
10. Deaktivirati investitora → i dalje se ispravno prikazuje (sa nazivom)
    na postojećim projektima koji ga referenciraju, ali se ne nudi kao
    opcija pri kreiranju NOVOG projekta (padajuća lista prikazuje samo
    aktivne).

## Faza 3 — Skladišne operacije

1. Ulaz od 10 komada elementa X na lokaciju A → stanje pokazuje 10,
   rezervisano 0, slobodno 10; skladišna kartica ima jedan red "Ulaz +10,
   stanje nakon 10".
2. Rezervacija 4 komada od tog elementa na istoj lokaciji → stanje ostaje
   10, rezervisano 4, slobodno 6.
3. Pokušaj rezervacije 7 komada (preko slobodnih 6) → odbijeno sa jasnom
   porukom, ništa se ne mijenja.
4. Otkazivanje rezervacije iz koraka 2 → rezervisano se vraća na 0,
   slobodno na 10; kartica ima red "Oslobađanje rezervacije".
5. Premještanje 5 komada sa lokacije A na lokaciju B → stanje na A opadne
   na 5, na B poraste na 5; kartica ima dva reda (premještanje-iz na A,
   premještanje-u na B) sa istim brojem dokumenta.
6. Rezervisati 3 komada na lokaciji A, pa pokušati premjestiti 4 komada sa
   A (veće od slobodnih 2 preostalih nakon koraka 5 i ove rezervacije) →
   odbijeno.
7. Dva ulaza kreirana brzo jedan za drugim (ili paralelno, ako se testira
   sa dva taba) → moraju dobiti različite, uzastopne brojeve dokumenta
   bez duplikata i bez preskakanja (provjerava numeraciju).
8. Korisnik bez `ulaz.uredi` (ali sa `ulaz.view`) → vidi listu ulaza, ne
   vidi dugme "Novi ulaz", direktan pristup `/ulaz/dodaj` vraća 403.
9. Ulaz sa praznom tabelom stavki (sve redove obrisati prije slanja) →
   greška "Dokument mora imati najmanje jednu stavku...", ništa se ne
   kreira.
10. Nakon nekoliko ulaza/rezervacija/premještanja za isti element, otvoriti
    njegovu skladišnu karticu (Stanje → Kartica) → sve promjene se vide
    hronološki, najnovije prvo, sa tačnim tekućim stanjem nakon svake.

---

## Dopuna — Stanje po investitoru/projektu

1. Ulaz 10 komada elementa X na lokaciju A, investitor "Firma 1" → stanje
   za (X, A, Firma 1) = 10.
2. Drugi ulaz 5 komada istog elementa X na istu lokaciju A, ali investitor
   "Firma 2" → DVA odvojena reda u pregledu stanja (10 za Firmu 1, 5 za
   Firmu 2), ne jedan zbirni red od 15.
3. Rezervacija 3 komada za Firmu 1 na lokaciji A → umanjuje slobodno SAMO
   na traci Firme 1 (postaje 10 rezervisano 3 slobodno 7); traka Firme 2
   ostaje netaknuta (5 rezervisano 0 slobodno 5).
4. Premještanje elementa X, investitor Firma 1, sa lokacije A na lokaciju
   B, količina 4 → traka Firme 1 na A opadne na 6 (slobodno 3), na B se
   pojavi novi red za Firmu 1 sa 4; traka Firme 2 na A i dalje pokazuje 5.
5. Ulaz bez izabranog investitora/projekta (generički) → stanje ide u
   traku sa investitor_id=0/projekat_id=0, prikazuje se u pregledu sa
   praznom Investitor/Projekat kolonom, i tretira se kao potpuno odvojena
   traka od bilo kojeg konkretnog investitora na istoj lokaciji.

## Dopuna — Ručni unos zapremine

1. Ulaz 10 komada elementa X na lokaciju A, investitor "Firma 1" → stanje
   za (X, A, Firma 1) = 10.
2. Drugi ulaz 5 komada istog elementa X na istu lokaciju A, ali investitor
   "Firma 2" → DVA odvojena reda u pregledu stanja (10 za Firmu 1, 5 za
   Firmu 2), ne jedan zbirni red od 15.
3. Rezervacija 3 komada za Firmu 1 na lokaciji A → umanjuje slobodno SAMO
   na traci Firme 1 (postaje 10 rezervisano 3 slobodno 7); traka Firme 2
   ostaje netaknuta (5 rezervisano 0 slobodno 5).
4. Premještanje elementa X, investitor Firma 1, sa lokacije A na lokaciju
   B, količina 4 → traka Firme 1 na A opadne na 6 (slobodno 3), na B se
   pojavi novi red za Firmu 1 sa 4; traka Firme 2 na A i dalje pokazuje 5.
5. Ulaz bez izabranog investitora/projekta (generički) → stanje ide u
   traku sa investitor_id=0/projekat_id=0, prikazuje se u pregledu sa
   praznom Investitor/Projekat kolonom, i tretira se kao potpuno odvojena
   traka od bilo kojeg konkretnog investitora na istoj lokaciji.

## Testni scenariji za Fazu 3

1. Ulaz od 10 komada elementa X na lokaciju A → stanje pokazuje 10,
   rezervisano 0, slobodno 10; skladišna kartica ima jedan red "Ulaz +10,
   stanje nakon 10".
2. Rezervacija 4 komada od tog elementa na istoj lokaciji → stanje ostaje
   10, rezervisano 4, slobodno 6.
3. Pokušaj rezervacije 7 komada (preko slobodnih 6) → odbijeno sa jasnom
   porukom, ništa se ne mijenja.
4. Otkazivanje rezervacije iz koraka 2 → rezervisano se vraća na 0,
   slobodno na 10; kartica ima red "Oslobađanje rezervacije".
5. Premještanje 5 komada sa lokacije A na lokaciju B → stanje na A opadne
   na 5, na B poraste na 5; kartica ima dva reda (premještanje-iz na A,
   premještanje-u na B) sa istim brojem dokumenta.
6. Rezervisati 3 komada na lokaciji A, pa pokušati premjestiti 4 komada sa
   A (veće od slobodnih 2 preostalih nakon koraka 5 i ove rezervacije) →
   odbijeno.
7. Dva ulaza kreirana brzo jedan za drugim (ili paralelno, ako se testira
   sa dva taba) → moraju dobiti različite, uzastopne brojeve dokumenta
   bez duplikata i bez preskakanja (provjerava numeraciju).
8. Korisnik bez `ulaz.uredi` (ali sa `ulaz.view`) → vidi listu ulaza, ne
   vidi dugme "Novi ulaz", direktan pristup `/ulaz/dodaj` vraća 403.
9. Ulaz sa praznom tabelom stavki (sve redove obrisati prije slanja) →
   greška "Dokument mora imati najmanje jednu stavku...", ništa se ne
   kreira.
10. Nakon nekoliko ulaza/rezervacija/premještanja za isti element, otvoriti
    njegovu skladišnu karticu (Stanje → Kartica) → sve promjene se vide
    hronološki, najnovije prvo, sa tačnim tekućim stanjem nakon svake.

---

## Dopuna: ručni unos zapremine za AB element

Na tvoj zahtjev, dodata je mogućnost ručnog unosa zapremine, nezavisno od
ručnog unosa težine — jer standardni obračun d×š×v nije tačan za elemente
sa izbočinama, udubljenjima, otvorima ili kosim površinama.

Kako radi:

- Novi prekidač "Automatski obračun zapremine" (analogan postojećem za
  težinu). Kad je isključen, unosi se `Ručno unesena zapremina (m³)`.
- Ta (ručna) zapremina se dalje koristi za obračun okvirne težine kada je
  težina i dalje na automatskom režimu (težina = zapremina × gustina) —
  dva prekidača (zapremina, težina) rade nezavisno, pa možeš imati bilo
  koju kombinaciju automatski/ručno za svaki.
- Migracija: `database/migrations/005_rucna_zapremina.sql` (ALTER, dodaje
  `automatski_obracun_zapremine` i `rucna_zapremina_m3` u `ab_elementi`).

```bash
mysql -u root -p ab_skladiste < database/migrations/005_rucna_zapremina.sql
```
(seed.php nije potreban za ovu dopunu.)

### Test scenariji

1. Element sa dužina=6, širina=0.30, visina=0.40 (automatski obračun
   zapremine i težine) → zapremina 0,720 m³ kao i prije.
2. Isti element, isključiti "Automatski obračun zapremine" i unijeti ručno
   0,650 m³ (npr. zbog velikog otvora) → lista prikazuje zapreminu 0,650
   sa oznakom "ručno", a težina (i dalje automatska) se preračunava iz TE
   ručne zapremine: 0,650 × gustina, ne iz d×š×v.
3. Kombinacija: ručna zapremina I ručna težina istovremeno uključene →
   obje vrijednosti se čuvaju i prikazuju kao unesene, nezavisno jedna od
   druge.
4. Pokušaj snimanja sa isključenim automatskim obračunom zapremine ali
   praznim/0 poljem ručne zapremine → greška, ništa se ne snima.
5. Live pregled u formi (prije snimanja) ispravno reaguje na uključivanje/
   isključivanje oba prekidača nezavisno.

## Faza 4 — Otprema, otpremnica, storno, projekat analitika

1. Otprema 4 komada elementa X sa lokacije A (bez rezervacije) kada je na
   toj traci slobodno 6 → uspijeva, stanje A opadne na 2 slobodno.
2. Ista otprema kad je slobodno samo 3 → odbijeno, jasna poruka.
3. Otprema sa izabranom aktivnom rezervacijom čija je količina TAČNO
   jednaka otpremljenoj → rezervacija prelazi u status "iskorišćena".
4. Otprema manje količine od rezervisane (npr. rezervacija 10, otprema 4)
   → rezervacija ostaje aktivna sa preostalih 6.
5. Otprema vezana za vozilo čija je nosivost manja od ukupne težine
   stavki → dokument se kreira, ali se prikazuje upozorenje o nosivosti
   (i odmah nakon snimanja i trajno na pregledu).
6. Otvoriti otpremnicu (`/otprema/{id}/otpremnica`) → prikazuje se čista
   A4 stranica sa podacima firme (ili placeholder ako nisu uneseni),
   bez sidebar-a, sa dugmetom za štampu.
7. Storno aktivnog ulaza bez unesenog razloga → odbijeno na frontend
   (required) I na serveru (provjera se ne oslanja samo na HTML5
   required).
8. Storno ulaza čija je roba u međuvremenu djelimično premještena na
   drugu lokaciju (tako da na originalnoj traci više nema dovoljno) →
   odbijeno sa jasnom porukom, ulaz ostaje aktivan.
9. Uspješan storno → ulaz označen kao storniran sa razlogom/datumom,
   stanje na toj traci se vrati na iznos prije tog ulaza, skladišna
   kartica ima red "Storno ulaza".
10. Projekat sa unesenim planiranim brojem 100, nakon dvije otpreme od po
    30 komada → lista projekata pokazuje Otpremljeno=60, Preostalo=40;
    stranica "Analitika" pokazuje 60% realizacije i traku napretka.
11. Izvještaj "Otpreme po periodu" za mjesec u kom su napravljene otpreme
    iz koraka 5-6 → prikazuje ih i tačan zbir ukupne težine na dnu; dugme
    "Štampaj" otvara isti podatak u print layoutu.
12. Upload loga u Postavke → Podaci firme sa fajlom koji ima `.png`
    ekstenziju ali je zapravo tekstualni fajl (test sadržaja, ne samo
    ekstenzije) → odbijeno.
