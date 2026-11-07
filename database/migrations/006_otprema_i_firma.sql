-- =====================================================================
-- AB SKLADIŠTE — Faza 4: otprema, otpremnica, podaci firme
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Storno ulaza (obavezan razlog) — vidi UlazRepository::storniraj()
-- ---------------------------------------------------------------------
ALTER TABLE ulazi
    ADD COLUMN status ENUM('aktivan','storniran') NOT NULL DEFAULT 'aktivan' AFTER napomena,
    ADD COLUMN razlog_storna VARCHAR(255) NULL AFTER status,
    ADD COLUMN datum_storniranja DATETIME NULL AFTER razlog_storna,
    ADD COLUMN stornirao_korisnik_id INT UNSIGNED NULL AFTER datum_storniranja,
    ADD CONSTRAINT fk_ulazi_stornirao FOREIGN KEY (stornirao_korisnik_id) REFERENCES korisnici(id) ON DELETE SET NULL;

-- Nova vrsta promjene u skladišnoj kartici za storno ulaza.
ALTER TABLE skladisna_kartica
    MODIFY COLUMN tip_promjene ENUM(
        'ulaz', 'izlaz', 'premjestanje_iz', 'premjestanje_u',
        'rezervacija', 'oslobadjanje_rezervacije', 'storno_ulaza'
    ) NOT NULL;

-- ---------------------------------------------------------------------
-- Projekat: planirani broj elemenata (za praćenje realizacije/preostalog)
-- ---------------------------------------------------------------------
ALTER TABLE projekti
    ADD COLUMN planirani_broj_elemenata DECIMAL(12,2) NULL AFTER opis;

-- ---------------------------------------------------------------------
-- Podaci firme (jedan red — koristi se na otpremnicama/izvještajima,
-- NIKAD hardkodirano u template-ima)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS podaci_firme (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    naziv VARCHAR(190) NULL,
    adresa VARCHAR(190) NULL,
    grad VARCHAR(100) NULL,
    jib VARCHAR(50) NULL,
    pdv_broj VARCHAR(50) NULL,
    telefon VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    web VARCHAR(190) NULL,
    logo_putanja VARCHAR(255) NULL,
    CONSTRAINT chk_podaci_firme_jedan_red CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO podaci_firme (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id;

-- ---------------------------------------------------------------------
-- Rezervacije: dodati status "iskoriscena" (otpremljeno iz rezervacije),
-- razdvojen od "otkazana" (ručno otkazano bez otpreme) radi izvještaja.
-- ---------------------------------------------------------------------
ALTER TABLE rezervacije
    MODIFY COLUMN status ENUM('aktivna','otkazana','iskoriscena') NOT NULL DEFAULT 'aktivna';

-- ---------------------------------------------------------------------
-- Otprema (dokument + stavke)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS otpreme (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broj_otpremnice VARCHAR(30) NOT NULL,
    datum DATE NOT NULL,
    investitor_id INT UNSIGNED NULL,
    projekat_id INT UNSIGNED NULL,
    objekat_id INT UNSIGNED NULL,
    prevoznik_id INT UNSIGNED NULL,
    vozilo_id INT UNSIGNED NULL,
    vozac_id INT UNSIGNED NULL,
    napomena VARCHAR(255) NULL,
    korisnik_id INT UNSIGNED NULL,
    datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_otpreme_broj (broj_otpremnice),
    CONSTRAINT fk_otpreme_investitor FOREIGN KEY (investitor_id) REFERENCES investitori(id) ON DELETE SET NULL,
    CONSTRAINT fk_otpreme_projekat FOREIGN KEY (projekat_id) REFERENCES projekti(id) ON DELETE SET NULL,
    CONSTRAINT fk_otpreme_objekat FOREIGN KEY (objekat_id) REFERENCES objekti(id) ON DELETE SET NULL,
    CONSTRAINT fk_otpreme_prevoznik FOREIGN KEY (prevoznik_id) REFERENCES prevoznici(id) ON DELETE SET NULL,
    CONSTRAINT fk_otpreme_vozilo FOREIGN KEY (vozilo_id) REFERENCES vozila(id) ON DELETE SET NULL,
    CONSTRAINT fk_otpreme_vozac FOREIGN KEY (vozac_id) REFERENCES vozaci(id) ON DELETE SET NULL,
    CONSTRAINT fk_otpreme_korisnik FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS otpreme_stavke (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    otprema_id INT UNSIGNED NOT NULL,
    element_id INT UNSIGNED NOT NULL,
    lokacija_id INT UNSIGNED NOT NULL,
    rezervacija_id INT UNSIGNED NULL,
    kolicina DECIMAL(12,2) NOT NULL,
    kg_po_komadu DECIMAL(10,2) NOT NULL DEFAULT 0,   -- snapshot u trenutku otpreme
    ukupna_tezina_kg DECIMAL(12,2) NOT NULL DEFAULT 0, -- snapshot: kolicina * kg_po_komadu
    KEY idx_otpreme_stavke_otprema (otprema_id),
    CONSTRAINT fk_otpreme_stavke_otprema FOREIGN KEY (otprema_id) REFERENCES otpreme(id) ON DELETE CASCADE,
    CONSTRAINT fk_otpreme_stavke_element FOREIGN KEY (element_id) REFERENCES ab_elementi(id) ON DELETE RESTRICT,
    CONSTRAINT fk_otpreme_stavke_lokacija FOREIGN KEY (lokacija_id) REFERENCES lokacije(id) ON DELETE RESTRICT,
    CONSTRAINT fk_otpreme_stavke_rezervacija FOREIGN KEY (rezervacija_id) REFERENCES rezervacije(id) ON DELETE SET NULL,
    CONSTRAINT chk_otpreme_stavke_kolicina_pozitivna CHECK (kolicina > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
