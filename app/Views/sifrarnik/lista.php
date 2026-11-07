<?php
/** @var array $rezultat */
/** @var string $pretraga */
/** @var array $koloneListe */
/** @var string $nazivModula */
/** @var string $rutaBazna */
/** @var string $permisijaPrefix */
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><?= e($nazivModula) ?></h3>
    <?php if (ima_pristup($permisijaPrefix . '.uredi')): ?>
        <a href="<?= e(base_url($rutaBazna . '/dodaj')) ?>" class="btn btn-dark">
            <i class="bi bi-plus-lg me-1"></i>Novi zapis
        </a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Pretraga..." value="<?= e($pretraga) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary" type="submit">Pretraži</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <?php foreach ($koloneListe as $kol): ?>
                        <th><?= e($kol['oznaka']) ?></th>
                    <?php endforeach; ?>
                    <th>Status</th>
                    <th class="text-end">Akcije</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezultat['stavke'])): ?>
                    <tr><td colspan="<?= count($koloneListe) + 2 ?>" class="text-center text-muted py-4">Nema pronađenih zapisa.</td></tr>
                <?php endif; ?>
                <?php foreach ($rezultat['stavke'] as $red): ?>
                    <tr>
                        <?php foreach ($koloneListe as $kol): ?>
                            <td><?= e((string) ($red[$kol['kljuc']] ?? '—')) ?: '—' ?></td>
                        <?php endforeach; ?>
                        <td>
                            <?php if ((int) $red['aktivan'] === 1): ?>
                                <span class="badge text-bg-success">Aktivan</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Neaktivan</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if (ima_pristup($permisijaPrefix . '.uredi')): ?>
                                <a href="<?= e(base_url($rutaBazna . '/izmijeni/' . $red['id'])) ?>" class="btn btn-sm btn-outline-secondary">Izmijeni</a>
                                <form method="post" action="<?= e(base_url($rutaBazna . '/status/' . $red['id'])) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Promijeniti status ovog zapisa?')">
                                        <?= (int) $red['aktivan'] === 1 ? 'Deaktiviraj' : 'Aktiviraj' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php foreach (($dodatneAkcije ?? []) as $akcija): ?>
                                <a href="<?= e(base_url($akcija['ruta'] . '/' . $red['id'])) ?>" class="btn btn-sm btn-outline-primary"><?= e($akcija['oznaka']) ?></a>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($rezultat['ukupno_stranica'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $rezultat['ukupno_stranica']; $i++): ?>
                <li class="page-item <?= $i === $rezultat['stranica'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(base_url($rutaBazna . '?stranica=' . $i . '&q=' . urlencode($pretraga))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
