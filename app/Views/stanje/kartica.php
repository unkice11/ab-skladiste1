<?php /** @var array $element */ /** @var array $rezultat */

$naziviPromjena = [
    'ulaz'                     => 'Ulaz',
    'izlaz'                    => 'Izlaz',
    'premjestanje_iz'          => 'Premještanje (iz)',
    'premjestanje_u'           => 'Premještanje (u)',
    'rezervacija'              => 'Rezervacija',
    'oslobadjanje_rezervacije' => 'Oslobađanje rezervacije',
];
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h3 class="mb-0">Skladišna kartica — <?= e($element['sifra'] . ' ' . $element['naziv']) ?></h3>
    <a href="<?= e(base_url('stanje')) ?>" class="btn btn-outline-secondary btn-sm">Nazad na stanje</a>
</div>
<p class="text-muted mb-4">Potpuna istorija promjena za ovaj element (najnovije prvo).</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Datum i vrijeme</th>
                    <th>Dokument</th>
                    <th>Tip promjene</th>
                    <th>Lokacija</th>
                    <th>Investitor / Projekat</th>
                    <th class="text-end">Količina</th>
                    <th class="text-end">Stanje nakon</th>
                    <th>Korisnik</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezultat['stavke'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nema evidentiranih promjena.</td></tr>
                <?php endif; ?>
                <?php foreach ($rezultat['stavke'] as $red): ?>
                    <tr>
                        <td><?= e(date('d.m.Y H:i', strtotime($red['datum_vrijeme']))) ?></td>
                        <td><?= e($red['dokument_broj'] ?? '—') ?></td>
                        <td><?= e($naziviPromjena[$red['tip_promjene']] ?? $red['tip_promjene']) ?></td>
                        <td><?= e($red['skladiste_naziv'] ? $red['skladiste_naziv'] . ' / ' . $red['lokacija_oznaka'] : '—') ?></td>
                        <td><?= e(trim(($red['investitor_naziv'] ?? '') . ' ' . ($red['projekat_naziv'] ? '/ ' . $red['projekat_naziv'] : '')) ?: '—') ?></td>
                        <td class="text-end <?= (float) $red['kolicina'] < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= ((float) $red['kolicina'] > 0 ? '+' : '') . number_format((float) $red['kolicina'], 2, ',', '.') ?>
                        </td>
                        <td class="text-end"><?= $red['stanje_nakon'] !== null ? number_format((float) $red['stanje_nakon'], 2, ',', '.') : '—' ?></td>
                        <td><?= e($red['korisnicko_ime'] ?? '—') ?></td>
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
                    <a class="page-link" href="<?= e(base_url('stanje/kartica/' . $element['id'] . '?stranica=' . $i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
