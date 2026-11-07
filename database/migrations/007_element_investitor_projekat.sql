-- =====================================================================
-- AB SKLADIŠTE — dopuna: AB element može biti vezan za investitora/
-- projekat (npr. kalup/tip elementa napravljen posebno za taj projekat),
-- radi filtriranja i štampe šifrarnika po investitoru/projektu.
--
-- NAPOMENA: ovo je NEZAVISNO od skladisno_stanje.investitor_id/
-- projekat_id (Faza 3 dopuna) — ono prati ČIJA je roba FIZIČKI na
-- skladištu, ovo prati da li je SAM TIP elementa (šifra) vezan za
-- određeni projekat u šifrarniku.
-- =====================================================================

ALTER TABLE ab_elementi
    ADD COLUMN investitor_id INT UNSIGNED NULL AFTER kategorija_id,
    ADD COLUMN projekat_id INT UNSIGNED NULL AFTER investitor_id,
    ADD CONSTRAINT fk_ab_elementi_investitor FOREIGN KEY (investitor_id) REFERENCES investitori(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ab_elementi_projekat FOREIGN KEY (projekat_id) REFERENCES projekti(id) ON DELETE SET NULL;
