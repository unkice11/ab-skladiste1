<?php
/** @var array $elementi */
/** @var array $lokacije */
/** @var array $investitori */
/** @var array $projekti */
/** @var array $objekti */
/** @var array $prevoznici */
/** @var array $vozila */
/** @var array $vozaci */
/** @var array $rezervacije */

function opcije_select_otp(array $stavke, $izabrano = null): string
{
    $html = '<option value="">— izaberi —</option>';
    foreach ($stavke as $s) {
        $sel = ((string) $izabrano === (string) $s['id']) ? 'selected' : '';
        $html .= '<option value="' . (int) $s['id'] . '" ' . $sel . '>' . e($s['naziv']) . '</option>';
    }
    return $html;
}
?>

<h3 class="mb-4">Nova otprema</h3>

<div class="alert alert-info">
    U polje "AB element" možeš upisati <strong>šifru</strong> (ili dio naziva) — ponuđeni prijedlozi
    se filtriraju dok kucaš. Ako stavka povlači robu iz aktivne rezervacije, izaberi je u koloni
    "Rezervacija" — sistem provjerava da li se element/lokacija/investitor/projekat poklapaju sa
    rezervacijom, i po potrebi je označava iskorišćenom (ili umanjuje ako je otprema manja od
    rezervisane količine). Bez izabrane rezervacije, otprema koristi samo slobodnu (nerezervisanu)
    količinu.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('otprema/dodaj')) ?>" id="forma-otprema">
            <?= csrf_field() ?>

            <div class="row g-3 mb-2">
                <div class="col-md-3">
                    <label class="form-label">Datum *</label>
                    <input type="date" name="datum" class="form-control" value="<?= e(old('datum', date('Y-m-d'))) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Investitor</label>
                    <select name="investitor_id" class="form-select"><?= opcije_select_otp($investitori, old('investitor_id')) ?></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Projekat</label>
                    <select name="projekat_id" class="form-select"><?= opcije_select_otp($projekti, old('projekat_id')) ?></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Objekat</label>
                    <select name="objekat_id" class="form-select"><?= opcije_select_otp($objekti, old('objekat_id')) ?></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prevoznik</label>
                    <select name="prevoznik_id" class="form-select"><?= opcije_select_otp($prevoznici, old('prevoznik_id')) ?></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vozilo</label>
                    <select name="vozilo_id" class="form-select"><?= opcije_select_otp($vozila, old('vozilo_id')) ?></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vozač</label>
                    <select name="vozac_id" class="form-select"><?= opcije_select_otp($vozaci, old('vozac_id')) ?></select>
                </div>
                <div class="col-12">
                    <label class="form-label">Napomena</label>
                    <input type="text" name="napomena" class="form-control" value="<?= e(old('napomena')) ?>">
                </div>
            </div>

            <hr>
            <h6 class="mb-3">Stavke</h6>

            <div class="table-responsive">
            <table class="table" id="tabela-stavki">
                <thead>
                    <tr>
                        <th style="width:25%">AB element</th>
                        <th style="width:20%">Lokacija</th>
                        <th style="width:15%">Količina</th>
                        <th style="width:30%">Rezervacija (opciono)</th>
                        <th style="width:10%"></th>
                    </tr>
                </thead>
                <tbody id="tijelo-stavki"></tbody>
            </table>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm" id="dodaj-red">
                <i class="bi bi-plus-lg me-1"></i>Dodaj stavku
            </button>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Sačuvaj otpremu</button>
                <a href="<?= e(base_url('otprema')) ?>" class="btn btn-outline-secondary">Odustani</a>
            </div>
        </form>
    </div>
</div>

<template id="sablon-red">
    <tr>
        <td>
            <input type="text" class="form-control form-control-sm element-unos" list="lista-elemenata"
                   placeholder="Unesite šifru ili naziv..." autocomplete="off" required>
            <input type="hidden" name="stavke[__i__][element_id]" class="element-id-skriveno">
        </td>
        <td>
            <select name="stavke[__i__][lokacija_id]" class="form-select form-select-sm" required>
                <?= opcije_select_otp($lokacije) ?>
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="stavke[__i__][kolicina]" class="form-control form-control-sm" required>
        </td>
        <td>
            <select name="stavke[__i__][rezervacija_id]" class="form-select form-select-sm">
                <option value="">— bez rezervacije —</option>
                <?= opcije_select_otp($rezervacije) ?>
            </select>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger dugme-obrisi-red"><i class="bi bi-trash"></i></button>
        </td>
    </tr>
</template>

<datalist id="lista-elemenata">
    <?php foreach ($elementi as $el): ?>
        <option data-id="<?= (int) $el['id'] ?>" value="<?= e($el['naziv']) ?>"></option>
    <?php endforeach; ?>
</datalist>

<script>
(function () {
    var mapaElemenata = <?= json_encode(array_column($elementi, 'id', 'naziv'), JSON_UNESCAPED_UNICODE) ?>;
    var brojac = 0;
    var tijelo = document.getElementById('tijelo-stavki');
    var sablon = document.getElementById('sablon-red').innerHTML;

    function povezElementUnos(red) {
        var unos = red.querySelector('.element-unos');
        var skriveno = red.querySelector('.element-id-skriveno');
        unos.addEventListener('input', function () {
            var id = mapaElemenata[unos.value];
            skriveno.value = id !== undefined ? id : '';
            unos.classList.toggle('is-invalid', unos.value !== '' && id === undefined);
        });
    }

    function dodajRed() {
        var html = sablon.replaceAll('__i__', brojac);
        var privremeni = document.createElement('tbody');
        privremeni.innerHTML = html;
        var red = privremeni.firstElementChild;
        red.querySelector('.dugme-obrisi-red').addEventListener('click', function () {
            red.remove();
        });
        povezElementUnos(red);
        tijelo.appendChild(red);
        brojac++;
    }

    document.getElementById('dodaj-red').addEventListener('click', dodajRed);

    dodajRed();
    dodajRed();
})();
</script>
