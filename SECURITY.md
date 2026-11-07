# Sigurnost — AB Skladište

## Autentifikacija i sesije

- Lozinke: `password_hash()`/`password_verify()` (bcrypt, PHP default) —
  nikad čist tekst u bazi.
- `session_regenerate_id(true)` nakon svake uspješne prijave (i nakon
  prijave preko "Zapamti me" kolačića) — zaštita od session fixation.
- Session timeout na neaktivnost (`session.lifetime` u `config.php`,
  default 2h) — provjerava se u `bootstrap.php` na svakom zahtjevu.
- "Zapamti me": odvojen dugotrajni kolačić (selector/validator šema,
  `korisnik_remember_tokens` tabela) — ne produžava samu PHP sesiju,
  httpOnly, `secure` kad je HTTPS aktivan.
- Brute-force zaštita: 5 uzastopnih pogrešnih pokušaja po korisničkom
  imenu → privremeno zaključavanje 5 min (`config('security.*')`,
  `Auth::jeZakljucano()`). Poruka za pogrešnu lozinku i neaktivan nalog
  je NAMJERNO ista ("Pogrešno korisničko ime ili lozinka") — ne otkriva
  da li nalog postoji.

## Autorizacija

- Svaka zaštićena ruta u `routes/web.php` navodi permisijski kod; `Router`
  provjerava `Auth::imaPristup()` NA SERVERU prije poziva kontrolera —
  sakrivanje stavke u meniju nije sigurnosna mjera, samo UX.
- Permisije se učitavaju IZ BAZE na svaki zahtjev (ne keširaju se u
  sesiji) — administrator koji oduzme permisiju korisniku, taj korisnik
  je odmah bez pristupa, bez potrebe za ponovnom prijavom.
- Efektivna permisija = uloga korisnika + individualni override
  (`korisnik_permisije` ima prednost nad `role_permisije`).

## Zaštita od injekcija i XSS-a

- SVI upiti idu kroz PDO prepared statements (`PDO::ATTR_EMULATE_PREPARES
  => false` — stvarni pripremljeni upiti na nivou MySQL drajvera, ne
  PHP-simulacija). Nikad se korisnički unos ne ubacuje direktno u SQL
  string.
- **Poznata zamka**: sa `EMULATE_PREPARES=false`, isti named placeholder
  (npr. `:p`) NE SMIJE se ponoviti više puta u istom upitu — MySQL native
  prepared statements to ne podržavaju kao PDO emulacija. Ovo je uzrokovalo
  stvarnu grešku ("Invalid parameter number") na više mjesta u ranijoj
  verziji — vidi CHANGELOG.md. Pravilo za dalji razvoj: pretraga po više
  kolona uvijek dobija poseban placeholder po koloni (`:p1`, `:p2`, ...),
  čak i kad je vrijednost ista.
- Sav izlaz u HTML ide kroz `e()` helper (`htmlspecialchars` sa
  `ENT_QUOTES`) — nema direktnog echo-a korisničkog unosa.

## CSRF

- Svaka forma nosi `csrf_field()` (token vezan za sesiju).
- `Router::pokreni()` odbija SVAKI POST zahtjev bez validnog tokena (419),
  automatski, prije nego što stigne do kontrolera — nema mogućnosti da se
  neka forma "zaboravi" zaštititi.

## Upload fajlova (logo firme)

- Provjerava se STVARNI sadržaj fajla (`finfo`/MIME), ne samo ekstenzija
  imena — sprječava upload izvršnog koda maskiranog kao `.png`.
- Dozvoljen samo PNG/JPG/WEBP, max 2 MB.
- Upload se čuva u `public/uploads/logos/` sa FIKSNIM imenom (`logo.png`
  i sl.) — korisnički unos nikad ne postaje dio naziva fajla ili putanje
  (sprječava path traversal).

## Šta NIJE (još) urađeno

- Rate limiting je samo na login formi (brute-force zaštita), ne na
  ostalim endpoint-ima.
- Nema HTTPS enforcement na nivou aplikacije (očekuje se da to radi
  server/reverse proxy u produkciji — `config('app.debug')` treba biti
  `false` i kolačići se šalju kao `secure` kad Apache javi HTTPS).
- Backup fajl (Postavke → Backup baze) sadrži KOMPLETAN sadržaj baze u
  čistom tekstu (uključujući hash-eve lozinki, koji su ipak jednosmjerno
  heširani) — čuvati preuzete backup fajlove van web-dostupnog prostora.
- Nema formalnog penetration testa / eksternog security review-a.
