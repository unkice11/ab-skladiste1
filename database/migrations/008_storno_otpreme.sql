-- =====================================================================
-- AB SKLADIŠTE — dopuna: storno otpreme (obavezan razlog), isti princip
-- kao storno ulaza iz Faze 4.
-- =====================================================================

ALTER TABLE otpreme
    ADD COLUMN status ENUM('aktivna','stornirana') NOT NULL DEFAULT 'aktivna' AFTER napomena,
    ADD COLUMN razlog_storna VARCHAR(255) NULL AFTER status,
    ADD COLUMN datum_storniranja DATETIME NULL AFTER razlog_storna,
    ADD COLUMN stornirao_korisnik_id INT UNSIGNED NULL AFTER datum_storniranja,
    ADD CONSTRAINT fk_otpreme_stornirao FOREIGN KEY (stornirao_korisnik_id) REFERENCES korisnici(id) ON DELETE SET NULL;

-- Nova vrsta promjene u skladišnoj kartici za storno otpreme.
ALTER TABLE skladisna_kartica
    MODIFY COLUMN tip_promjene ENUM(
        'ulaz', 'izlaz', 'premjestanje_iz', 'premjestanje_u',
        'rezervacija', 'oslobadjanje_rezervacije', 'storno_ulaza', 'storno_otpreme'
    ) NOT NULL;
