-- =====================================================================
-- AB SKLADIŠTE — Faza 1: osnovna šema (autentifikacija, korisnici,
-- permisije, meni, audit log)
--
-- Pokrenuti nad praznom bazom, npr:
--   mysql -u root -p ab_skladiste < 001_osnovna_sema.sql
-- (bazu 'ab_skladiste' prvo kreirati: CREATE DATABASE ab_skladiste
--  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS role (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(100) NOT NULL,
    opis VARCHAR(255) NULL,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_naziv (naziv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS korisnici (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ime VARCHAR(100) NOT NULL,
    prezime VARCHAR(100) NOT NULL,
    korisnicko_ime VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    lozinka_hash VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NULL,
    status ENUM('aktivan','neaktivan') NOT NULL DEFAULT 'aktivan',
    datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datum_izmjene DATETIME NULL,
    posljednja_prijava DATETIME NULL,
    UNIQUE KEY uq_korisnici_korisnicko_ime (korisnicko_ime),
    UNIQUE KEY uq_korisnici_email (email),
    KEY idx_korisnici_role (role_id),
    CONSTRAINT fk_korisnici_role FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS korisnik_remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT UNSIGNED NOT NULL,
    selektor VARCHAR(64) NOT NULL,
    validator_hash VARCHAR(255) NOT NULL,
    istice DATETIME NOT NULL,
    datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_remember_selektor (selektor),
    KEY idx_remember_korisnik (korisnik_id),
    CONSTRAINT fk_remember_korisnik FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permisije (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kod VARCHAR(100) NOT NULL,
    naziv VARCHAR(150) NOT NULL,
    modul VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_permisije_kod (kod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permisije (
    role_id INT UNSIGNED NOT NULL,
    permisija_id INT UNSIGNED NOT NULL,
    vidljiv TINYINT(1) NOT NULL DEFAULT 0,
    pristup TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (role_id, permisija_id),
    CONSTRAINT fk_role_permisije_role FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permisije_permisija FOREIGN KEY (permisija_id) REFERENCES permisije(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS korisnik_permisije (
    korisnik_id INT UNSIGNED NOT NULL,
    permisija_id INT UNSIGNED NOT NULL,
    vidljiv TINYINT(1) NOT NULL DEFAULT 0,
    pristup TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (korisnik_id, permisija_id),
    CONSTRAINT fk_korisnik_permisije_korisnik FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE,
    CONSTRAINT fk_korisnik_permisije_permisija FOREIGN KEY (permisija_id) REFERENCES permisije(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meni_stavke (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    roditelj_id INT UNSIGNED NULL,
    naziv VARCHAR(100) NOT NULL,
    url VARCHAR(150) NULL,
    ikona VARCHAR(50) NULL,
    permisija_kod VARCHAR(100) NULL,
    redoslijed INT NOT NULL DEFAULT 0,
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_meni_roditelj (roditelj_id),
    CONSTRAINT fk_meni_roditelj FOREIGN KEY (roditelj_id) REFERENCES meni_stavke(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT UNSIGNED NULL,
    datum_vrijeme DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_adresa VARCHAR(45) NULL,
    akcija VARCHAR(100) NOT NULL,
    modul VARCHAR(50) NOT NULL,
    tabela VARCHAR(100) NULL,
    id_zapisa INT UNSIGNED NULL,
    stara_vrijednost TEXT NULL,
    nova_vrijednost TEXT NULL,
    KEY idx_audit_korisnik (korisnik_id),
    KEY idx_audit_datum (datum_vrijeme),
    CONSTRAINT fk_audit_korisnik FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
