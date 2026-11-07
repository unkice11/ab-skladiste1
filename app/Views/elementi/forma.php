<?php
/** @var array|null $element */
/** @var array $kategorije */
/** @var float $standardnaGustina */
$jeIzmjena = $element !== null;
$akcija = $jeIzmjena ? base_url('elementi/izmijeni/' . $element['id']) : base_url('elementi/dodaj');
$automatskiZapremina = $element['automatski_obracun_zapremine'] ?? 1;
$automatskiTezina = $element['automatski_obracun_tezine'] ?? 1;
?>

<h3 class="mb-4"><?= $jeIzmjena ? 'Izmjena AB elementa' : 'Novi AB element' ?></h3>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= e($akcija) ?>" id="forma-element">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Šifra *</label>
                    <input type="text" name="sifra" class="form-control" required
                           value="<?= e(old('sifra', $element['sifra'] ?? '')) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Naziv *</label>
                    <input type="text" name="naziv" class="form-control" required
                           value="<?= e(old('naziv', $element['naziv'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategorija</label>
                    <select name="kategorija_id" class="form-select">
                        <option value="">— nije izabrano —</option>
                        <?php foreach ($kategorije as $k): ?>
                            <option value="<?= (int) $k['id'] ?>" <?= (($element['kategorija_id'] ?? null) == $k['id']) ? 'selected' : '' ?>>
                                <?= e($k['naziv']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Opis</label>
                    <input type="text" name="opis" class="form-control" value="<?= e(old('opis', $element['opis'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Investitor</label>
                    <select name="investitor_id" class="form-select">
                        <option value="">— nije izabrano —</option>
                        <?php foreach ($investitori as $i): ?>
                            <option value="<?= (int) $i['id'] ?>" <?= (($element['investitor_id'] ?? null) == $i['id']) ? 'selected' : '' ?>>
                                <?= e($i['naziv']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Opciono — npr. kad je element (kalup/tip) napravljen posebno za jednog investitora.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Projekat</label>
                    <select name="projekat_id" class="form-select">
                        <option value="">— nije izabrano —</option>
                        <?php foreach ($projekti as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= (($element['projekat_id'] ?? null) == $p['id']) ? 'selected' : '' ?>>
                                <?= e($p['naziv']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12"><hr class="my-2"></div>

                <div class="col-md-3">
                    <label class="form-label">Dužina (m)</label>
                    <input type="number" step="0.001" name="duzina_m" id="duzina_m" class="form-control racunska-vrijednost"
                           value="<?= e(old('duzina_m', $element['duzina_m'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Širina (m)</label>
                    <input type="number" step="0.001" name="sirina_m" id="sirina_m" class="form-control racunska-vrijednost"
                           value="<?= e(old('sirina_m', $element['sirina_m'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Visina (m)</label>
                    <input type="number" step="0.001" name="visina_m" id="visina_m" class="form-control racunska-vrijednost"
                           value="<?= e(old('visina_m', $element['visina_m'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gustina betona (kg/m³)</label>
                    <input type="number" step="0.01" name="gustina_kg_m3" id="gustina_kg_m3" class="form-control racunska-vrijednost"
                           value="<?= e(old('gustina_kg_m3', $element['gustina_kg_m3'] ?? $standardnaGustina)) ?>">
                    <div class="form-text">Podrazumijevano iz Postavke → Parametri sistema.</div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="automatski_obracun_zapremine"
                               name="automatski_obracun_zapremine" value="1" <?= $automatskiZapremina ? 'checked' : '' ?>>
                        <label class="form-check-label" for="automatski_obracun_zapremine">
                            Automatski obračun zapremine (dužina × širina × visina)
                        </label>
                    </div>
                </div>

                <div class="col-md-4" id="polje-rucna-zapremina" style="<?= $automatskiZapremina ? 'display:none;' : '' ?>">
                    <label class="form-label">Ručno unesena zapremina (m³)</label>
                    <input type="number" step="0.0001" name="rucna_zapremina_m3" id="rucna_zapremina_m3" class="form-control racunska-vrijednost"
                           value="<?= e(old('rucna_zapremina_m3', $element['rucna_zapremina_m3'] ?? '')) ?>">
                    <div class="form-text">Koristiti kada element ima izbočine, udubljenja, otvore ili kose površine koje pravougaoni obračun ne pokriva tačno.</div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="automatski_obracun_tezine"
                               name="automatski_obracun_tezine" value="1" <?= $automatskiTezina ? 'checked' : '' ?>>
                        <label class="form-check-label" for="automatski_obracun_tezine">
                            Automatski obračun težine (zapremina × gustina)
                        </label>
                    </div>
                </div>

                <div class="col-md-4" id="polje-rucna-tezina" style="<?= $automatskiTezina ? 'display:none;' : '' ?>">
                    <label class="form-label">Ručno unesena težina (kg)</label>
                    <input type="number" step="0.01" name="rucna_tezina_kg" id="rucna_tezina_kg" class="form-control"
                           value="<?= e(old('rucna_tezina_kg', $element['rucna_tezina_kg'] ?? '')) ?>">
                    <div class="form-text">Koristiti kada ni ispravljena zapremina ne daje tačnu masu (npr. ugrađena armatura, metalni dijelovi).</div>
                </div>
            </div>

            <div class="card bg-light border-0 mt-4">
                <div class="card-body py-3">
                    <div class="row text-center">
                        <div class="col-md-6">
                            <div class="text-muted small">Izračunata zapremina</div>
                            <div class="fs-5 fw-semibold"><span id="prikaz-zapremina">0,000</span> m³</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Izračunata okvirna težina</div>
                            <div class="fs-5 fw-semibold"><span id="prikaz-tezina">0</span> kg</div>
                        </div>
                    </div>
                    <div class="text-muted small text-center mt-2">
                        Ovo je samo pregled — konačna vrijednost se izračunava na serveru pri snimanju.
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Sačuvaj</button>
                <a href="<?= e(base_url('elementi')) ?>" class="btn btn-outline-secondary">Odustani</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function broj(id) {
        var el = document.getElementById(id);
        if (!el) return 0;
        var v = parseFloat(el.value.replace(',', '.'));
        return isNaN(v) ? 0 : v;
    }

    function preracunaj() {
        var automatskiZapremina = document.getElementById('automatski_obracun_zapremine').checked;
        var zapremina;
        if (automatskiZapremina) {
            zapremina = broj('duzina_m') * broj('sirina_m') * broj('visina_m');
        } else {
            zapremina = broj('rucna_zapremina_m3');
        }

        var automatskiTezina = document.getElementById('automatski_obracun_tezine').checked;
        var tezina;
        if (automatskiTezina) {
            tezina = zapremina * broj('gustina_kg_m3');
        } else {
            tezina = broj('rucna_tezina_kg');
        }

        document.getElementById('prikaz-zapremina').textContent = zapremina.toLocaleString('bs-BA', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
        document.getElementById('prikaz-tezina').textContent = Math.round(tezina).toLocaleString('bs-BA');
    }

    function preklopiZapreminu() {
        var automatski = document.getElementById('automatski_obracun_zapremine').checked;
        document.getElementById('polje-rucna-zapremina').style.display = automatski ? 'none' : '';
        preracunaj();
    }

    function preklopiTezinu() {
        var automatski = document.getElementById('automatski_obracun_tezine').checked;
        document.getElementById('polje-rucna-tezina').style.display = automatski ? 'none' : '';
        preracunaj();
    }

    document.querySelectorAll('.racunska-vrijednost').forEach(function (el) {
        el.addEventListener('input', preracunaj);
    });
    document.getElementById('rucna_tezina_kg').addEventListener('input', preracunaj);
    document.getElementById('automatski_obracun_zapremine').addEventListener('change', preklopiZapreminu);
    document.getElementById('automatski_obracun_tezine').addEventListener('change', preklopiTezinu);

    preklopiZapreminu();
    preklopiTezinu();
})();
</script>
