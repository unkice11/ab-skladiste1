<?php
/** @var array $elementi */
/** @var array $lokacije */
/** @var array $skladista */
/** @var array $investitori */
/** @var array $projekti */
/** @var array $objekti */

function opcije_select(array $stavke, $izabrano = null): string
{
    $html = '<option value="">— izaberi —</option>';
    foreach ($stavke as $s) {
        $sel = ((string) $izabrano === (string) $s['id']) ? 'selected' : '';
        $html .= '<option value="' . (int) $s['id'] . '" ' . $sel . '>' . e($s['naziv']) . '</option>';
    }
    return $html;
}
?>

<h3 class="mb-4">Novi ulaz na skladište</h3>

<div class="alert alert-info">
    U polje "AB element" možeš upisati <strong>šifru</strong> (ili dio naziva) — ponuđeni prijedlozi
    se filtriraju dok kucaš; izborom iz liste se element prepoznaje automatski.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('ulaz/dodaj')) ?>" id="forma-ulaz">
            <?= csrf_field() ?>

            <div class="row g-3 mb-2">
                <div class="col-md-3">
                    <label class="form-label">Datum *</label>
                    <input type="date" name="datum" class="form-control" value="<?= e(old('datum', date('Y-m-d'))) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Skladište *</label>
                    <select name="skladiste_id" class="form-select" required>
                        <?= opcije_select($skladista, old('skladiste_id')) ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Investitor</label>
                    <select name="investitor_id" class="form-select"><?= opcije_select($investitori, old('investitor_id')) ?></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Projekat</label>
                    <select name="projekat_id" class="form-select"><?= opcije_select($projekti, old('projekat_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Objekat</label>
                    <select name="objekat_id" class="form-select"><?= opcije_select($objekti, old('objekat_id')) ?></select>
                </div>
                <div class="col-md-6">
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
                        <th style="width:35%">AB element (šifra ili naziv)</th>
                        <th style="width:35%">Lokacija</th>
                        <th style="width:20%">Količina</th>
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
                <button type="submit" class="btn btn-dark">Sačuvaj ulaz</button>
                <a href="<?= e(base_url('ulaz')) ?>" class="btn btn-outline-secondary">Odustani</a>
            </div>
        </form>
    </div>
</div>

<datalist id="lista-elemenata">
    <?php foreach ($elementi as $el): ?>
        <option data-id="<?= (int) $el['id'] ?>" value="<?= e($el['naziv']) ?>"></option>
    <?php endforeach; ?>
</datalist>

<template id="sablon-red">
    <tr>
        <td>
            <input type="text" class="form-control form-control-sm element-unos" list="lista-elemenata"
                   placeholder="Unesite šifru ili naziv..." autocomplete="off" required>
            <input type="hidden" name="stavke[__i__][element_id]" class="element-id-skriveno">
        </td>
        <td>
            <select name="stavke[__i__][lokacija_id]" class="form-select form-select-sm" required>
                <?= opcije_select($lokacije) ?>
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="stavke[__i__][kolicina]" class="form-control form-control-sm" required>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger dugme-obrisi-red"><i class="bi bi-trash"></i></button>
        </td>
    </tr>
</template>

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

    // Počni sa dvije prazne stavke radi bržeg unosa.
    dodajRed();
    dodajRed();
})();
</script>
