<?php
/** @var array $stavke */
/** @var bool $stampaj */
?>
<h4 class="mb-3">Izvještaj — Stanje po lokaciji</h4>

<?php if (!$stampaj): ?>
<div class="mb-3">
    <a class="btn btn-dark" target="_blank" href="<?= e(base_url('izvjestaji/po-lokaciji?stampaj=1')) ?>">
        <i class="bi bi-printer"></i> Štampaj
    </a>
    <a class="btn btn-outline-dark" href="<?= e(base_url('izvjestaji/po-lokaciji?export=csv')) ?>">
        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
    </a>
</div>
<?php endif; ?>

<div class="<?= $stampaj ? '' : 'card' ?>">
    <div class="table-responsive">
        <table class="table <?= $stampaj ? 'table-bordered' : 'table-hover' ?> mb-0">
            <thead class="table-light">
                <tr><th>Skladište</th><th>Zona</th><th>Lokacija</th><th class="text-end">Komada (svi elementi)</th><th class="text-end">Ukupna težina (kg)</th></tr>
            </thead>
            <tbody>
                <?php if (empty($stavke)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nema stanja na skladištu.</td></tr>
                <?php endif; ?>
                <?php foreach ($stavke as $red): ?>
                    <tr>
                        <td><?= e($red['skladiste_naziv']) ?></td>
                        <td><?= e($red['zona'] ?? '—') ?></td>
                        <td><?= e($red['lokacija_oznaka']) ?></td>
                        <td class="text-end"><?= number_format((float) $red['ukupno_komada'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['ukupna_tezina_kg'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
