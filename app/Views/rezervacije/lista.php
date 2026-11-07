<?php /** @var array $rezultat */ /** @var string $pretraga */ ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">Rezervacije</h3>
    <?php if (ima_pristup('rezervacije.uredi')): ?>
        <a href="<?= e(base_url('rezervacije/dodaj')) ?>" class="btn btn-dark"><i class="bi bi-plus-lg me-1"></i>Nova rezervacija</a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Pretraga po broju, šifri ili nazivu..." value="<?= e($pretraga) ?>">
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
                    <th>Broj</th>
                    <th>Element</th>
                    <th>Lokacija</th>
                    <th class="text-end">Količina</th>
                    <th>Investitor / Projekat</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezultat['stavke'])): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nema rezervacija.</td></tr>
                <?php endif; ?>
                <?php foreach ($rezultat['stavke'] as $r): ?>
                    <tr>
                        <td><?= e($r['broj_rezervacije']) ?></td>
                        <td><?= e($r['sifra'] . ' — ' . $r['element_naziv']) ?></td>
                        <td><?= e($r['lokacija_oznaka']) ?></td>
                        <td class="text-end"><?= number_format((float) $r['kolicina'], 2, ',', '.') ?></td>
                        <td><?= e(trim(($r['investitor_naziv'] ?? '') . ' ' . ($r['projekat_naziv'] ? '/ ' . $r['projekat_naziv'] : '')) ?: '—') ?></td>
                        <td>
                            <?php if ($r['status'] === 'aktivna'): ?>
                                <span class="badge text-bg-success">Aktivna</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Otkazana</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($r['status'] === 'aktivna' && ima_pristup('rezervacije.uredi')): ?>
                                <form method="post" action="<?= e(base_url('rezervacije/otkazi/' . $r['id'])) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Otkazati ovu rezervaciju?')">Otkaži</button>
                                </form>
                            <?php endif; ?>
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
                    <a class="page-link" href="<?= e(base_url('rezervacije?stranica=' . $i . '&q=' . urlencode($pretraga))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
