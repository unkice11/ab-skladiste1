<?php
/** @var array $stavke */
/** @var string $pretraga */
/** @var bool $stampaj */
?>
<h4 class="mb-3">Izvještaj — Stanje skladišta</h4>

<?php if (!$stampaj): ?>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">Pretraga</label>
                <input type="text" name="q" class="form-control" value="<?= e($pretraga) ?>">
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary">Primijeni filter</button></div>
            <div class="col-auto">
                <a class="btn btn-dark" target="_blank" href="<?= e(base_url('izvjestaji/stanje?q=' . urlencode($pretraga) . '&stampaj=1')) ?>">
                    <i class="bi bi-printer"></i> Štampaj
                </a>
                <a class="btn btn-outline-dark" href="<?= e(base_url('izvjestaji/stanje?q=' . urlencode($pretraga) . '&export=csv')) ?>">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="<?= $stampaj ? '' : 'card' ?>">
    <div class="table-responsive">
        <table class="table <?= $stampaj ? 'table-bordered' : 'table-hover' ?> mb-0">
            <thead class="table-light">
                <tr>
                    <th>Šifra</th><th>Element</th><th>Investitor / Projekat</th><th>Skladište</th><th>Lokacija</th>
                    <th class="text-end">Količina</th><th class="text-end">Rezervisano</th><th class="text-end">Slobodno</th>
                    <th class="text-end">Ukupna težina (kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stavke)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Nema stanja na skladištu.</td></tr>
                <?php endif; ?>
                <?php foreach ($stavke as $red): ?>
                    <tr>
                        <td><?= e($red['sifra']) ?></td>
                        <td><?= e($red['naziv']) ?></td>
                        <td><?= e(trim(($red['investitor_naziv'] ?? '') . ' ' . ($red['projekat_naziv'] ? '/ ' . $red['projekat_naziv'] : '')) ?: '—') ?></td>
                        <td><?= e($red['skladiste_naziv']) ?></td>
                        <td><?= e(($red['zona'] ? $red['zona'] . '-' : '') . $red['lokacija_oznaka']) ?></td>
                        <td class="text-end"><?= number_format((float) $red['kolicina'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['rezervisano'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['slobodno'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['ukupna_tezina_kg'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
