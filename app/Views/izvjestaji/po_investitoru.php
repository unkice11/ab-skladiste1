<?php
/** @var array $stavke */
/** @var bool $stampaj */
?>
<h4 class="mb-3">Izvještaj — Stanje po investitoru</h4>

<?php if (!$stampaj): ?>
<div class="mb-3">
    <a class="btn btn-dark" target="_blank" href="<?= e(base_url('izvjestaji/po-investitoru?stampaj=1')) ?>">
        <i class="bi bi-printer"></i> Štampaj
    </a>
    <a class="btn btn-outline-dark" href="<?= e(base_url('izvjestaji/po-investitoru?export=csv')) ?>">
        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
    </a>
</div>
<?php endif; ?>

<div class="<?= $stampaj ? '' : 'card' ?>">
    <div class="table-responsive">
        <table class="table <?= $stampaj ? 'table-bordered' : 'table-hover' ?> mb-0">
            <thead class="table-light">
                <tr><th>Investitor</th><th class="text-end">Ukupno komada</th><th class="text-end">Ukupna okvirna težina (kg)</th></tr>
            </thead>
            <tbody>
                <?php if (empty($stavke)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Nema stanja na skladištu.</td></tr>
                <?php endif; ?>
                <?php foreach ($stavke as $red): ?>
                    <tr>
                        <td><?= e($red['investitor_naziv'] ?? '— (bez investitora) —') ?></td>
                        <td class="text-end"><?= number_format((float) $red['ukupno_komada'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['ukupna_tezina_kg'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
