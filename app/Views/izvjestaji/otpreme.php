<?php
/** @var array $stavke */
/** @var string $od */
/** @var string $do */
/** @var float $ukupnaTezina */
/** @var bool $stampaj */
?>
<h4 class="mb-3">Izvještaj — Otpreme</h4>

<?php if (!$stampaj): ?>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">Od</label>
                <input type="date" name="od" class="form-control" value="<?= e($od) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label">Do</label>
                <input type="date" name="do" class="form-control" value="<?= e($do) ?>">
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary">Primijeni filtere</button></div>
            <div class="col-auto">
                <a class="btn btn-dark" target="_blank"
                   href="<?= e(base_url('izvjestaji/otpreme?od=' . urlencode($od) . '&do=' . urlencode($do) . '&stampaj=1')) ?>">
                    <i class="bi bi-printer"></i> Štampaj
                </a>
                <a class="btn btn-outline-dark"
                   href="<?= e(base_url('izvjestaji/otpreme?od=' . urlencode($od) . '&do=' . urlencode($do) . '&export=csv')) ?>">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                </a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
    <p>Period: <?= e(date('d.m.Y', strtotime($od))) ?> — <?= e(date('d.m.Y', strtotime($do))) ?></p>
<?php endif; ?>

<div class="<?= $stampaj ? '' : 'card' ?>">
    <div class="table-responsive">
        <table class="table <?= $stampaj ? 'table-bordered' : 'table-hover' ?> mb-0">
            <thead class="table-light">
                <tr><th>Broj otpremnice</th><th>Datum</th><th>Investitor</th><th class="text-end">Komada</th><th class="text-end">Težina (kg)</th></tr>
            </thead>
            <tbody>
                <?php if (empty($stavke)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nema otprema u izabranom periodu.</td></tr>
                <?php endif; ?>
                <?php foreach ($stavke as $o): ?>
                    <tr>
                        <td><?= e($o['broj_otpremnice']) ?></td>
                        <td><?= e(date('d.m.Y', strtotime($o['datum']))) ?></td>
                        <td><?= e($o['investitor_naziv'] ?? '—') ?></td>
                        <td class="text-end"><?= number_format((float) $o['ukupno_komada'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $o['ukupna_tezina_kg'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-semibold">
                    <td colspan="4">Ukupna otpremljena okvirna težina</td>
                    <td class="text-end"><?= number_format($ukupnaTezina, 0, ',', '.') ?> kg</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
