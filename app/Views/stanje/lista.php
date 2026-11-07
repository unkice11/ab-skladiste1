<?php /** @var array $rezultat */ /** @var string $pretraga */ ?>

<h3 class="mb-4">Stanje skladišta</h3>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Pretraga po šifri ili nazivu elementa..." value="<?= e($pretraga) ?>">
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
                    <th>Šifra</th>
                    <th>Element</th>
                    <th>Investitor / Projekat</th>
                    <th>Skladište</th>
                    <th>Lokacija</th>
                    <th class="text-end">Količina</th>
                    <th class="text-end">Rezervisano</th>
                    <th class="text-end">Slobodno</th>
                    <th class="text-end">kg/kom</th>
                    <th class="text-end">Ukupna težina (kg)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezultat['stavke'])): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">Nema stanja na skladištu.</td></tr>
                <?php endif; ?>
                <?php foreach ($rezultat['stavke'] as $red): ?>
                    <tr>
                        <td><?= e($red['sifra']) ?></td>
                        <td><?= e($red['naziv']) ?></td>
                        <td><?= e(trim(($red['investitor_naziv'] ?? '') . ' ' . ($red['projekat_naziv'] ? '/ ' . $red['projekat_naziv'] : '')) ?: '—') ?></td>
                        <td><?= e($red['skladiste_naziv']) ?></td>
                        <td><?= e(($red['zona'] ? $red['zona'] . '-' : '') . $red['lokacija_oznaka']) ?></td>
                        <td class="text-end"><?= number_format((float) $red['kolicina'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['rezervisano'], 2, ',', '.') ?></td>
                        <td class="text-end fw-semibold"><?= number_format((float) $red['slobodno'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['okvirna_tezina_kg'], 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $red['ukupna_tezina_kg'], 0, ',', '.') ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('stanje/kartica/' . $red['element_id'])) ?>" class="btn btn-sm btn-outline-secondary">Kartica</a>
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
                    <a class="page-link" href="<?= e(base_url('stanje?stranica=' . $i . '&q=' . urlencode($pretraga))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
