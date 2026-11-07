<?php
/** @var array $korisnik */
/** @var array $permisije */
$poModulu = [];
foreach ($permisije as $p) {
    $poModulu[$p['modul']][] = $p;
}
?>

<h3 class="mb-1">Permisije — <?= e($korisnik['ime'] . ' ' . $korisnik['prezime']) ?></h3>
<p class="text-muted mb-4">
    Označene vrijednosti preuzete su od uloge korisnika (<?= e($korisnik['uloga_naziv'] ?? 'bez uloge') ?>).
    Čekiranjem/otčekiranjem ovdje pravite individualni izuzetak samo za ovog korisnika.
</p>

<form method="post" action="<?= e(base_url('korisnici/permisije/' . $korisnik['id'])) ?>">
    <?= csrf_field() ?>

    <?php foreach ($poModulu as $modul => $stavke): ?>
        <div class="card mb-3">
            <div class="card-header fw-semibold text-capitalize"><?= e($modul) ?></div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Permisija</th>
                            <th class="text-center" style="width:110px;">Vidljiv</th>
                            <th class="text-center" style="width:110px;">Pristup</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stavke as $p): ?>
                            <tr>
                                <td>
                                    <?= e($p['naziv']) ?>
                                    <?php if ($p['override']): ?>
                                        <span class="badge text-bg-warning ms-1">izuzetak</span>
                                    <?php endif; ?>
                                    <div class="text-muted small"><?= e($p['kod']) ?></div>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" name="vidljiv[<?= (int) $p['id'] ?>]"
                                           <?= $p['vidljiv'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" name="pristup[<?= (int) $p['id'] ?>]"
                                           <?= $p['pristup'] ? 'checked' : '' ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($permisije as $p): ?>
        <input type="hidden" name="obradjene[]" value="<?= (int) $p['id'] ?>">
    <?php endforeach; ?>

    <button type="submit" class="btn btn-dark">Sačuvaj permisije</button>
    <a href="<?= e(base_url('korisnici')) ?>" class="btn btn-outline-secondary">Nazad</a>
</form>
