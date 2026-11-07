<?php /** @var array $rezultat */ /** @var string $pretraga */ ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">Ulaz na skladište</h3>
    <?php if (ima_pristup('ulaz.uredi')): ?>
        <a href="<?= e(base_url('ulaz/dodaj')) ?>" class="btn btn-dark"><i class="bi bi-plus-lg me-1"></i>Novi ulaz</a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Pretraga po broju ulaza..." value="<?= e($pretraga) ?>">
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary" type="submit">Pretraži</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Broj ulaza</th>
                    <th>Datum</th>
                    <th>Skladište</th>
                    <th>Investitor</th>
                    <th>Status</th>
                    <th>Kreirao</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezultat['stavke'])): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nema evidentiranih ulaza.</td></tr>
                <?php endif; ?>
                <?php foreach ($rezultat['stavke'] as $u): ?>
                    <tr>
                        <td><?= e($u['broj_ulaza']) ?></td>
                        <td><?= e(date('d.m.Y', strtotime($u['datum']))) ?></td>
                        <td><?= e($u['skladiste_naziv']) ?></td>
                        <td><?= e($u['investitor_naziv'] ?? '—') ?></td>
                        <td>
                            <?php if ($u['status'] === 'storniran'): ?>
                                <span class="badge text-bg-danger">Storniran</span>
                            <?php else: ?>
                                <span class="badge text-bg-success">Aktivan</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($u['korisnicko_ime'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('ulaz/' . $u['id'])) ?>" class="btn btn-sm btn-outline-secondary">Pregled</a>
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
                    <a class="page-link" href="<?= e(base_url('ulaz?stranica=' . $i . '&q=' . urlencode($pretraga))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
