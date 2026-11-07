-- =====================================================================
-- AB SKLADIŠTE — Faza 2: šifrarnici
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Parametri sistema (key-value, npr. standardna gustina betona)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS postavke_sistema (
    kljuc VARCHAR(100) NOT NULL PRIMARY KEY,
    vrijednost VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO postavke_sistema (kljuc, vrijednost)
VALUES ('standardna_gustina_betona_kg_m3', '2500')
ON DUPLICATE KEY UPDATE kljuc = kljuc;

-- ---------------------------------------------------------------------
-- Kategorije AB elemenata
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kategorije_elemenata (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(150) NOT NULL,
    opis VARCHAR(255) NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_kategorije_naziv (naziv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Investitori
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS investitori (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(190) NOT NULL,
    adresa VARCHAR(190) NULL,
    grad VARCHAR(100) NULL,
    jib VARCHAR(50) NULL,
    pdv_broj VARCHAR(50) NULL,
    telefon VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Projekti (pripadaju investitoru)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projekti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(190) NOT NULL,
    investitor_id INT UNSIGNED NULL,
    opis VARCHAR(255) NULL,
    datum_pocetka DATE NULL,
    datum_zavrsetka DATE NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_projekti_investitor (investitor_id),
    CONSTRAINT fk_projekti_investitor FOREIGN KEY (investitor_id) REFERENCES investitori(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Objekti (pripadaju projektu)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS objekti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(190) NOT NULL,
    projekat_id INT UNSIGNED NULL,
    opis VARCHAR(255) NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_objekti_projekat (projekat_id),
    CONSTRAINT fk_objekti_projekat FOREIGN KEY (projekat_id) REFERENCES projekti(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Skladišta
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skladista (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sifra VARCHAR(50) NOT NULL,
    naziv VARCHAR(190) NOT NULL,
    lokacija VARCHAR(190) NULL,
    opis VARCHAR(255) NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_skladista_sifra (sifra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Lokacije (zone/pozicije unutar skladišta)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lokacije (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skladiste_id INT UNSIGNED NOT NULL,
    zona VARCHAR(50) NULL,
    oznaka VARCHAR(50) NOT NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_lokacije_skladiste (skladiste_id),
    CONSTRAINT fk_lokacije_skladiste FOREIGN KEY (skladiste_id) REFERENCES skladista(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Prevoznici
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prevoznici (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(190) NOT NULL,
    adresa VARCHAR(190) NULL,
    telefon VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Vozila
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vozila (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registarska_oznaka VARCHAR(30) NOT NULL,
    naziv_tip VARCHAR(150) NULL,
    prevoznik_id INT UNSIGNED NULL,
    nosivost_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_vozila_registracija (registarska_oznaka),
    KEY idx_vozila_prevoznik (prevoznik_id),
    CONSTRAINT fk_vozila_prevoznik FOREIGN KEY (prevoznik_id) REFERENCES prevoznici(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Vozači
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vozaci (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ime VARCHAR(100) NOT NULL,
    prezime VARCHAR(100) NOT NULL,
    prevoznik_id INT UNSIGNED NULL,
    telefon VARCHAR(50) NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_vozaci_prevoznik (prevoznik_id),
    CONSTRAINT fk_vozaci_prevoznik FOREIGN KEY (prevoznik_id) REFERENCES prevoznici(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- AB elementi (centralni šifrarnik)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ab_elementi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sifra VARCHAR(50) NOT NULL,
    naziv VARCHAR(190) NOT NULL,
    kategorija_id INT UNSIGNED NULL,
    opis VARCHAR(255) NULL,
    duzina_m DECIMAL(8,3) NOT NULL DEFAULT 0,
    sirina_m DECIMAL(8,3) NOT NULL DEFAULT 0,
    visina_m DECIMAL(8,3) NOT NULL DEFAULT 0,
    gustina_kg_m3 DECIMAL(8,2) NOT NULL DEFAULT 2500,
    automatski_obracun_tezine TINYINT(1) NOT NULL DEFAULT 1,
    rucna_tezina_kg DECIMAL(10,2) NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_ab_elementi_sifra (sifra),
    KEY idx_ab_elementi_kategorija (kategorija_id),
    CONSTRAINT fk_ab_elementi_kategorija FOREIGN KEY (kategorija_id) REFERENCES kategorije_elemenata(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
