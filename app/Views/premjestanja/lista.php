<?php /** @var array $rezultat */ ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">Premještanja</h3>
    <?php if (ima_pristup('premjestanja.uredi')): ?>
        <a href="<?= e(base_url('premjestanja/dodaj')) ?>" class="btn btn-dark"><i class="bi bi-plus-lg me-1"></i>Novo premještanje</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Broj</th>
                    <th>Element</th>
                    <th>Investitor / Projekat</th>
                    <th>Iz lokacije</th>
                    <th>U lokaciju</th>
                    <th class="text-end">Količina</th>
                    <th>Datum</th>
                    <th>Korisnik</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezultat['stavke'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nema evidentiranih premještanja.</td></tr>
                <?php endif; ?>
                <?php foreach ($rezultat['stavke'] as $p): ?>
                    <tr>
                        <td><?= e($p['broj_premjestanja']) ?></td>
                        <td><?= e($p['sifra'] . ' — ' . $p['element_naziv']) ?></td>
                        <td><?= e(trim(($p['investitor_naziv'] ?? '') . ' ' . ($p['projekat_naziv'] ? '/ ' . $p['projekat_naziv'] : '')) ?: '—') ?></td>
                        <td><?= e($p['iz_lokacija_oznaka']) ?></td>
                        <td><?= e($p['u_lokacija_oznaka']) ?></td>
                        <td class="text-end"><?= number_format((float) $p['kolicina'], 2, ',', '.') ?></td>
                        <td><?= e(date('d.m.Y H:i', strtotime($p['datum_kreiranja']))) ?></td>
                        <td><?= e($p['korisnicko_ime'] ?? '—') ?></td>
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
                    <a class="page-link" href="<?= e(base_url('premjestanja?stranica=' . $i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
