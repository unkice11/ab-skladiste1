<?php
/** @var array $stavke */
/** @var bool $stampaj */
?>
<h4 class="mb-3">Izvještaj — Rezervisani elementi</h4>

<?php if (!$stampaj): ?>
<div class="mb-3">
    <a class="btn btn-dark" target="_blank" href="<?= e(base_url('izvjestaji/rezervisani?stampaj=1')) ?>">
        <i class="bi bi-printer"></i> Štampaj
    </a>
    <a class="btn btn-outline-dark" href="<?= e(base_url('izvjestaji/rezervisani?export=csv')) ?>">
        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
    </a>
</div>
<?php endif; ?>

<div class="<?= $stampaj ? '' : 'card' ?>">
    <div class="table-responsive">
        <table class="table <?= $stampaj ? 'table-bordered' : 'table-hover' ?> mb-0">
            <thead class="table-light">
                <tr><th>Broj</th><th>Element</th><th>Lokacija</th><th class="text-end">Količina</th><th>Investitor / Projekat</th></tr>
            </thead>
            <tbody>
                <?php if (empty($stavke)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Trenutno nema aktivnih rezervacija.</td></tr>
                <?php endif; ?>
                <?php foreach ($stavke as $r): ?>
                    <tr>
                        <td><?= e($r['broj_rezervacije']) ?></td>
                        <td><?= e($r['sifra'] . ' — ' . $r['element_naziv']) ?></td>
                        <td><?= e($r['lokacija_oznaka']) ?></td>
                        <td class="text-end"><?= number_format((float) $r['kolicina'], 2, ',', '.') ?></td>
                        <td><?= e(trim(($r['investitor_naziv'] ?? '') . ' ' . ($r['projekat_naziv'] ? '/ ' . $r['projekat_naziv'] : '')) ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
