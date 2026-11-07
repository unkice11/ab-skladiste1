<?php
/** @var array $rola */
/** @var array $permisije */
$poModulu = [];
foreach ($permisije as $p) {
    $poModulu[$p['modul']][] = $p;
}
?>

<h3 class="mb-1">Permisije uloge — <?= e($rola['naziv']) ?></h3>
<p class="text-muted mb-4">
    Ovo je OSNOVNI set permisija za ovu ulogu — svaki korisnik sa ovom ulogom ih dobija odmah,
    osim ako za njega postoji individualni izuzetak (Korisnici → [korisnik] → Permisije).
</p>

<form method="post" action="<?= e(base_url('role/permisije/' . $rola['id'])) ?>">
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
    <a href="<?= e(base_url('role')) ?>" class="btn btn-outline-secondary">Nazad</a>
</form>
