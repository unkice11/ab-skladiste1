<?php /** @var float $standardnaGustina */ ?>

<h3 class="mb-4">Parametri sistema</h3>

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('postavke/parametri-sistema')) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Standardna gustina betona (kg/m³)</label>
                <input type="number" step="0.01" name="standardna_gustina_betona_kg_m3" class="form-control"
                       value="<?= e((string) $standardnaGustina) ?>">
                <div class="form-text">
                    Koristi se kao podrazumijevana vrijednost prilikom kreiranja NOVOG AB elementa.
                    Izmjena ovdje ne mijenja gustinu već unesenih elemenata.
                </div>
            </div>
            <button type="submit" class="btn btn-dark">Sačuvaj</button>
        </form>
    </div>
</div>
