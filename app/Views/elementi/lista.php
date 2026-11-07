<?php
/** @var array $rezultat */
/** @var string $pretraga */
/** @var int $investitorId */
/** @var int $projekatId */
/** @var array $investitori */
/** @var array $projekti */
/** @var bool $stampaj */

function opcije_select_el(array $stavke, $izabrano = null): string
{
    $html = '<option value="">— svi —</option>';
    foreach ($stavke as $s) {
        $sel = ((string) $izabrano === (string) $s['id']) ? 'selected' : '';
        $html .= '<option value="' . (int) $s['id'] . '" ' . $sel . '>' . e($s['naziv']) . '</option>';
    }
    return $html;
}

$upitFiltera = 'q=' . urlencode($pretraga) . '&investitor_id=' . $investitorId . '&projekat_id=' . $projekatId;
?>

<?php if (!$stampaj): ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">AB elementi</h3>
    <?php if (ima_pristup('elementi.uredi')): ?>
        <a href="<?= e(base_url('elementi/dodaj')) ?>" class="btn btn-dark">
            <i class="bi bi-plus-lg me-1"></i>Novi element
        </a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Pretraga</label>
                <input type="text" name="q" class="form-control" placeholder="Šifra ili naziv..." value="<?= e($pretraga) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Investitor</label>
                <select name="investitor_id" class="form-select"><?= opcije_select_el($investitori, $investitorId) ?></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Projekat</label>
                <select name="projekat_id" class="form-select"><?= opcije_select_el($projekti, $projekatId) ?></select>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary" type="submit">Primijeni filtere</button>
            </div>
            <div class="col-auto">
                <a class="btn btn-dark" target="_blank" href="<?= e(base_url('elementi?' . $upitFiltera . '&stampaj=1')) ?>">
                    <i class="bi bi-printer"></i> Štampaj
                </a>
                <a class="btn btn-outline-dark" href="<?= e(base_url('elementi?' . $upitFiltera . '&export=csv')) ?>">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                </a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
    <h4 class="mb-3">Šifrarnik — AB elementi</h4>
<?php endif; ?>

<div class="<?= $stampaj ? '' : 'card' ?>">
    <div class="table-responsive">
        <table class="table <?= $stampaj ? 'table-bordered' : 'table-hover' ?> align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Šifra</th>
                    <th>Naziv</th>
                    <th>Opis</th>
                    <th>Kategorija</th>
                    <th>Investitor / Projekat</th>
                    <th>Dimenzije (d×š×v, m)</th>
                    <th>Zapremina (m³)</th>
                    <th>kg/kom</th>
                    <?php if (!$stampaj): ?>
                        <th>Status</th>
                        <th class="text-end">Akcije</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezultat['stavke'])): ?>
                    <tr><td colspan="<?= $stampaj ? 8 : 10 ?>" class="text-center text-muted py-4">Nema pronađenih elemenata.</td></tr>
                <?php endif; ?>
                <?php foreach ($rezultat['stavke'] as $el): ?>
                    <tr>
                        <td><?= e($el['sifra']) ?></td>
                        <td><?= e($el['naziv']) ?></td>
                        <td><?= e($el['opis'] ?? '') ?: '—' ?></td>
                        <td><?= e($el['kategorija_naziv'] ?? '—') ?></td>
                        <td><?= e(trim(($el['investitor_naziv'] ?? '') . ' ' . ($el['projekat_naziv'] ? '/ ' . $el['projekat_naziv'] : '')) ?: '—') ?></td>
                        <td><?= number_format((float) $el['duzina_m'], 2, ',', '.') ?> × <?= number_format((float) $el['sirina_m'], 2, ',', '.') ?> × <?= number_format((float) $el['visina_m'], 2, ',', '.') ?></td>
                        <td>
                            <?= number_format((float) $el['zapremina_m3'], 3, ',', '.') ?>
                            <?php if (!(int) $el['automatski_obracun_zapremine']): ?>
                                <span class="badge text-bg-info ms-1" title="Ručno unesena zapremina">ručno</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= number_format((float) $el['okvirna_tezina_kg'], 0, ',', '.') ?>
                            <?php if (!(int) $el['automatski_obracun_tezine']): ?>
                                <span class="badge text-bg-info ms-1" title="Ručno unesena težina">ručno</span>
                            <?php endif; ?>
                        </td>
                        <?php if (!$stampaj): ?>
                            <td>
                                <?php if ((int) $el['aktivan'] === 1): ?>
                                    <span class="badge text-bg-success">Aktivan</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Neaktivan</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (ima_pristup('elementi.uredi')): ?>
                                    <a href="<?= e(base_url('elementi/izmijeni/' . $el['id'])) ?>" class="btn btn-sm btn-outline-secondary">Izmijeni</a>
                                    <form method="post" action="<?= e(base_url('elementi/status/' . $el['id'])) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Promijeniti status ovog elementa?')">
                                            <?= (int) $el['aktivan'] === 1 ? 'Deaktiviraj' : 'Aktiviraj' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$stampaj && $rezultat['ukupno_stranica'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $rezultat['ukupno_stranica']; $i++): ?>
                <li class="page-item <?= $i === $rezultat['stranica'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(base_url('elementi?stranica=' . $i . '&' . $upitFiltera)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
