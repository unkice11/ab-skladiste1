<?php /** @var array $rezultat */ /** @var string $pretraga */ ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">Korisnici</h3>
    <?php if (ima_pristup('korisnici.kreiraj')): ?>
        <a href="<?= e(base_url('korisnici/dodaj')) ?>" class="btn btn-dark">
            <i class="bi bi-plus-lg me-1"></i>Novi korisnik
        </a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Pretraga po imenu, korisničkom imenu, e-mailu..." value="<?= e($pretraga) ?>">
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
                    <th>Ime i prezime</th>
                    <th>Korisničko ime</th>
                    <th>E-mail</th>
                    <th>Uloga</th>
                    <th>Status</th>
                    <th>Posljednja prijava</th>
                    <th class="text-end">Akcije</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezultat['stavke'])): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nema pronađenih korisnika.</td></tr>
                <?php endif; ?>
                <?php foreach ($rezultat['stavke'] as $k): ?>
                    <tr>
                        <td><?= e($k['ime'] . ' ' . $k['prezime']) ?></td>
                        <td><?= e($k['korisnicko_ime']) ?></td>
                        <td><?= e($k['email']) ?></td>
                        <td><?= e($k['uloga_naziv'] ?? '—') ?></td>
                        <td>
                            <?php if ($k['status'] === 'aktivan'): ?>
                                <span class="badge text-bg-success">Aktivan</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Neaktivan</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($k['posljednja_prijava'] ? date('d.m.Y H:i', strtotime($k['posljednja_prijava'])) : '—') ?></td>
                        <td class="text-end">
                            <?php if (ima_pristup('korisnici.izmijeni')): ?>
                                <a href="<?= e(base_url('korisnici/izmijeni/' . $k['id'])) ?>" class="btn btn-sm btn-outline-secondary">Izmijeni</a>
                            <?php endif; ?>
                            <?php if (ima_pristup('korisnici.permisije')): ?>
                                <a href="<?= e(base_url('korisnici/permisije/' . $k['id'])) ?>" class="btn btn-sm btn-outline-secondary">Permisije</a>
                            <?php endif; ?>
                            <?php if (ima_pristup('korisnici.izmijeni')): ?>
                                <form method="post" action="<?= e(base_url('korisnici/status/' . $k['id'])) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Promijeniti status ovog korisnika?')">
                                        <?= $k['status'] === 'aktivan' ? 'Deaktiviraj' : 'Aktiviraj' ?>
                                    </button>
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
                    <a class="page-link" href="<?= e(base_url('korisnici?stranica=' . $i . '&q=' . urlencode($pretraga))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
