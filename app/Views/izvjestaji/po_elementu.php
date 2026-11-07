<?php
/** @var array $stavke */
/** @var string $od */
/** @var string $do */
/** @var bool $stampaj */
?>
<h4 class="mb-3">Izvještaj — Kretanje po elementu</h4>

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
                   href="<?= e(base_url('izvjestaji/po-elementu?od=' . urlencode($od) . '&do=' . urlencode($do) . '&stampaj=1')) ?>">
                    <i class="bi bi-printer"></i> Štampaj
                </a>
                <a class="btn btn-outline-dark"
                   href="<?= e(base_url('izvjestaji/po-elementu?od=' . urlencode($od) . '&do=' . urlencode($do) . '&export=csv')) ?>">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                </a>
            </div>
        </form>
    </div>
</div>
<p class="text-muted small">"Ulaz (period)" i "Otprema (period)" su ograničeni na izabrani period; "Trenutno stanje" je uvijek trenutna vrijednost (svi periodi).</p>
<?php else: ?>
    <p>Period: <?= e(date('d.m.Y', strtotime($od))) ?> — <?= e(date('d.m.Y', strtotime($do))) ?></p>
<?php endif; ?>

<div class="<?= $stampaj ? '' : 'card' ?>">
    <div class="table-responsive">
        <table class="table <?= $stampaj ? 'table-bordered' : 'table-hover' ?> mb-0">
            <thead class="table-light">
                <tr><th>Šifra</th><th>Element</th><th class="text-end">Ulaz (period)</th><th class="text-end">Otprema (period)</th><th class="text-end">Trenutno stanje</th></tr>
            </thead>
            <tbody>
                <?php if (empty($stavke)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nema kretanja u izabranom periodu.</td></tr>
                <?php endif; ?>
                <?php foreach ($stavke as $red): ?>
                    <tr>
                        <td><?= e($red['sifra']) ?></td>
                        <td><?= e($red['naziv']) ?></td>
                        <td class="text-end"><?= number_format((float) $red['ulaz_period'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['otprema_period'], 2, ',', '.') ?></td>
                        <td class="text-end fw-semibold"><?= number_format((float) $red['trenutno_stanje'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
