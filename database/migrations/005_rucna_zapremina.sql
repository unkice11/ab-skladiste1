-- =====================================================================
-- AB SKLADIŠTE — dopuna: ručni unos zapremine za AB element
--
-- Isti princip kao kod težine (Faza 2): standardni obračun d×š×v je
-- tačan samo za "kockaste" elemente. Elementi sa izbočinama, udubljenjima,
-- otvorima, kosinama i sl. trebaju mogućnost da se zapremina unese ručno.
--
-- Kada je automatski_obracun_zapremine = 0, koristi se rucna_zapremina_m3
-- umjesto d×š×v — i ta (ručna) zapremina se dalje koristi za obračun
-- okvirne težine kada je težina i dalje na automatskom režimu
-- (težina = zapremina × gustina), tako da se dva prekidača (zapremina i
-- težina) mogu kombinovati nezavisno jedan od drugog.
-- =====================================================================

ALTER TABLE ab_elementi
    ADD COLUMN automatski_obracun_zapremine TINYINT(1) NOT NULL DEFAULT 1 AFTER gustina_kg_m3,
    ADD COLUMN rucna_zapremina_m3 DECIMAL(10,4) NULL AFTER automatski_obracun_zapremine;
