<?php
/** @var array $stavke */
/** @var int $godina */
/** @var float $ukupnoGodina */
/** @var bool $stampaj */
?>
<h4 class="mb-3">Izvještaj — Ukupna otpremljena okvirna težina</h4>

<?php if (!$stampaj): ?>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">Godina</label>
                <input type="number" name="godina" class="form-control" value="<?= (int) $godina ?>" style="width:120px;">
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary">Prikaži</button></div>
            <div class="col-auto">
                <a class="btn btn-dark" target="_blank" href="<?= e(base_url('izvjestaji/ukupna-tezina?godina=' . $godina . '&stampaj=1')) ?>">
                    <i class="bi bi-printer"></i> Štampaj
                </a>
                <a class="btn btn-outline-dark" href="<?= e(base_url('izvjestaji/ukupna-tezina?godina=' . $godina . '&export=csv')) ?>">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                </a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
    <p>Godina: <?= (int) $godina ?></p>
<?php endif; ?>

<div class="<?= $stampaj ? '' : 'card' ?>">
    <div class="table-responsive">
        <table class="table <?= $stampaj ? 'table-bordered' : 'table-hover' ?> mb-0">
            <thead class="table-light">
                <tr><th>Mjesec</th><th class="text-end">Komada</th><th class="text-end">Težina (kg)</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stavke as $red): ?>
                    <tr>
                        <td><?= e($red['mjesec']) ?></td>
                        <td class="text-end"><?= number_format((float) $red['ukupno_komada'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['ukupna_tezina_kg'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-semibold">
                    <td>UKUPNO ZA GODINU</td>
                    <td></td>
                    <td class="text-end"><?= number_format($ukupnoGodina, 0, ',', '.') ?> kg</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
