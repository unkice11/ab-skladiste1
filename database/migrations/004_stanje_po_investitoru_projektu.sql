-- =====================================================================
-- AB SKLADIŠTE — Faza 3 (dopuna): stanje razdvojeno po investitoru/projektu
--
-- Do sada je fizičko stanje bilo generički pool po (element, lokacija).
-- Ova migracija ga mijenja tako da se posebno prati i po investitoru i
-- projektu na ISTOJ lokaciji — dvije pošiljke istog elementa na istu
-- lokaciju, ali za različite investitore, sada su dva odvojena reda
-- stanja, ne jedan zbirni.
--
-- investitor_id/projekat_id = 0 znači "bez investitora/projekta"
-- (generički unos) — NAMJERNO nisu NULL, jer NULL ne može biti dio
-- PRIMARY KEY-a u MySQL-u. Nema FK na ove dvije kolone u ovoj tabeli
-- (0 bi kršio referencijalni integritet), integritet se čuva na nivou
-- aplikacije (uvijek se piše stvarni investitor_id/projekat_id ili 0).
-- =====================================================================

ALTER TABLE skladisno_stanje
    DROP PRIMARY KEY,
    ADD COLUMN investitor_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER lokacija_id,
    ADD COLUMN projekat_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER investitor_id,
    ADD PRIMARY KEY (element_id, lokacija_id, investitor_id, projekat_id);

-- Premještanje mora znati IZ koje investitor/projekat "trake" premješta —
-- destinacija zadržava istu investitor/projekat pripadnost (premještanje
-- mijenja samo lokaciju, ne vlasništvo/namjenu).
ALTER TABLE premjestanja
    ADD COLUMN investitor_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER element_id,
    ADD COLUMN projekat_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER investitor_id;

-- Čisto informativno, radi lakšeg čitanja skladišne kartice.
ALTER TABLE skladisna_kartica
    ADD COLUMN investitor_id INT UNSIGNED NULL AFTER lokacija_id,
    ADD COLUMN projekat_id INT UNSIGNED NULL AFTER investitor_id;

-- Napomena: rezervacije.investitor_id/projekat_id već postoje (nullable,
-- iz Faze 3) i NAMJERNO se ne mijenjaju ovdje — u kodu se NULL tretira
-- kao 0 (COALESCE) kad god se rezervacija poredi sa redom stanja, tako
-- da rezervacija bez investitora ispravno pogađa "generičku" (0) traku.
