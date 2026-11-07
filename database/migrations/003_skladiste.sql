-- =====================================================================
-- AB SKLADIŠTE — Faza 3: skladišne operacije
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Centralna numeracija dokumenata (NIKAD SELECT MAX(id)+1 — vidi
-- NumeracijaRepository koji ovo koristi sa SELECT...FOR UPDATE unutar
-- transakcije radi sigurnosti kod paralelnih zahtjeva).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS brojaci_dokumenata (
    kljuc VARCHAR(30) NOT NULL PRIMARY KEY,  -- npr. 'UL-2026', 'PREM-2026'
    zadnji_broj INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Fizičko stanje po elementu i lokaciji (generički pool — vidi napomenu
-- u README o pretpostavci da stanje nije posebno praćeno po investitoru).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skladisno_stanje (
    element_id INT UNSIGNED NOT NULL,
    lokacija_id INT UNSIGNED NOT NULL,
    kolicina DECIMAL(12,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (element_id, lokacija_id),
    CONSTRAINT fk_stanje_element FOREIGN KEY (element_id) REFERENCES ab_elementi(id) ON DELETE RESTRICT,
    CONSTRAINT fk_stanje_lokacija FOREIGN KEY (lokacija_id) REFERENCES lokacije(id) ON DELETE RESTRICT,
    CONSTRAINT chk_stanje_kolicina_nenegativna CHECK (kolicina >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Ulaz na skladište (dokument + stavke)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ulazi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broj_ulaza VARCHAR(30) NOT NULL,
    datum DATE NOT NULL,
    skladiste_id INT UNSIGNED NOT NULL,
    investitor_id INT UNSIGNED NULL,
    projekat_id INT UNSIGNED NULL,
    objekat_id INT UNSIGNED NULL,
    napomena VARCHAR(255) NULL,
    korisnik_id INT UNSIGNED NULL,
    datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ulazi_broj (broj_ulaza),
    KEY idx_ulazi_skladiste (skladiste_id),
    CONSTRAINT fk_ulazi_skladiste FOREIGN KEY (skladiste_id) REFERENCES skladista(id) ON DELETE RESTRICT,
    CONSTRAINT fk_ulazi_investitor FOREIGN KEY (investitor_id) REFERENCES investitori(id) ON DELETE SET NULL,
    CONSTRAINT fk_ulazi_projekat FOREIGN KEY (projekat_id) REFERENCES projekti(id) ON DELETE SET NULL,
    CONSTRAINT fk_ulazi_objekat FOREIGN KEY (objekat_id) REFERENCES objekti(id) ON DELETE SET NULL,
    CONSTRAINT fk_ulazi_korisnik FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ulazi_stavke (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulaz_id INT UNSIGNED NOT NULL,
    element_id INT UNSIGNED NOT NULL,
    lokacija_id INT UNSIGNED NOT NULL,
    kolicina DECIMAL(12,2) NOT NULL,
    KEY idx_ulazi_stavke_ulaz (ulaz_id),
    CONSTRAINT fk_ulazi_stavke_ulaz FOREIGN KEY (ulaz_id) REFERENCES ulazi(id) ON DELETE CASCADE,
    CONSTRAINT fk_ulazi_stavke_element FOREIGN KEY (element_id) REFERENCES ab_elementi(id) ON DELETE RESTRICT,
    CONSTRAINT fk_ulazi_stavke_lokacija FOREIGN KEY (lokacija_id) REFERENCES lokacije(id) ON DELETE RESTRICT,
    CONSTRAINT chk_ulazi_stavke_kolicina_pozitivna CHECK (kolicina > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Rezervacije (ne mijenjaju fizičko stanje, samo umanjuju "slobodno")
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rezervacije (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broj_rezervacije VARCHAR(30) NOT NULL,
    element_id INT UNSIGNED NOT NULL,
    lokacija_id INT UNSIGNED NOT NULL,
    kolicina DECIMAL(12,2) NOT NULL,
    investitor_id INT UNSIGNED NULL,
    projekat_id INT UNSIGNED NULL,
    objekat_id INT UNSIGNED NULL,
    napomena VARCHAR(255) NULL,
    status ENUM('aktivna','otkazana') NOT NULL DEFAULT 'aktivna',
    korisnik_id INT UNSIGNED NULL,
    datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datum_otkazivanja DATETIME NULL,
    UNIQUE KEY uq_rezervacije_broj (broj_rezervacije),
    KEY idx_rezervacije_element_lokacija (element_id, lokacija_id),
    CONSTRAINT fk_rezervacije_element FOREIGN KEY (element_id) REFERENCES ab_elementi(id) ON DELETE RESTRICT,
    CONSTRAINT fk_rezervacije_lokacija FOREIGN KEY (lokacija_id) REFERENCES lokacije(id) ON DELETE RESTRICT,
    CONSTRAINT fk_rezervacije_investitor FOREIGN KEY (investitor_id) REFERENCES investitori(id) ON DELETE SET NULL,
    CONSTRAINT fk_rezervacije_projekat FOREIGN KEY (projekat_id) REFERENCES projekti(id) ON DELETE SET NULL,
    CONSTRAINT fk_rezervacije_objekat FOREIGN KEY (objekat_id) REFERENCES objekti(id) ON DELETE SET NULL,
    CONSTRAINT chk_rezervacije_kolicina_pozitivna CHECK (kolicina > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Premještanja (mijenjaju samo lokaciju, ne ukupnu količinu)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS premjestanja (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broj_premjestanja VARCHAR(30) NOT NULL,
    element_id INT UNSIGNED NOT NULL,
    iz_lokacija_id INT UNSIGNED NOT NULL,
    u_lokacija_id INT UNSIGNED NOT NULL,
    kolicina DECIMAL(12,2) NOT NULL,
    napomena VARCHAR(255) NULL,
    korisnik_id INT UNSIGNED NULL,
    datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_premjestanja_broj (broj_premjestanja),
    CONSTRAINT fk_premjestanja_element FOREIGN KEY (element_id) REFERENCES ab_elementi(id) ON DELETE RESTRICT,
    CONSTRAINT fk_premjestanja_iz FOREIGN KEY (iz_lokacija_id) REFERENCES lokacije(id) ON DELETE RESTRICT,
    CONSTRAINT fk_premjestanja_u FOREIGN KEY (u_lokacija_id) REFERENCES lokacije(id) ON DELETE RESTRICT,
    CONSTRAINT chk_premjestanja_kolicina_pozitivna CHECK (kolicina > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Skladišna kartica — potpuna istorija promjena po elementu/lokaciji
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skladisna_kartica (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    element_id INT UNSIGNED NOT NULL,
    lokacija_id INT UNSIGNED NULL,
    tip_promjene ENUM(
        'ulaz', 'izlaz', 'premjestanje_iz', 'premjestanje_u',
        'rezervacija', 'oslobadjanje_rezervacije'
    ) NOT NULL,
    kolicina DECIMAL(12,2) NOT NULL,           -- +/- promjena
    stanje_nakon DECIMAL(12,2) NULL,           -- fizičko stanje na toj lokaciji nakon promjene (NULL za rezervacije)
    dokument_tip VARCHAR(30) NOT NULL,         -- 'ulaz' | 'premjestanje' | 'rezervacija' | 'otprema' (buduće faze)
    dokument_id INT UNSIGNED NULL,
    dokument_broj VARCHAR(30) NULL,
    korisnik_id INT UNSIGNED NULL,
    datum_vrijeme DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_kartica_element (element_id, datum_vrijeme),
    CONSTRAINT fk_kartica_element FOREIGN KEY (element_id) REFERENCES ab_elementi(id) ON DELETE RESTRICT,
    CONSTRAINT fk_kartica_lokacija FOREIGN KEY (lokacija_id) REFERENCES lokacije(id) ON DELETE SET NULL,
    CONSTRAINT fk_kartica_korisnik FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
