<?php
/** @var array $projekat */
/** @var array $poElementu */
/** @var float|null $planirano */
/** @var float $proizvedeno */
/** @var float $otpremljeno */
/** @var float|null $zaProizvesti */
/** @var float|null $zaOtpremu */
/** @var float $cekaOtpremu */
/** @var int|null $procenatProizvodnje */
/** @var int|null $procenatOtpreme */

$fmt = fn(float $v): string => number_format($v, 0, ',', '.');
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h3 class="mb-0">Analitika realizacije — <?= e($projekat['naziv']) ?></h3>
    <a href="<?= e(base_url('projekti')) ?>" class="btn btn-outline-secondary btn-sm">Nazad na projekte</a>
</div>
<p class="text-muted mb-4">
    "Proizvedeno" se računa iz ulaza na skladište vezanih za ovaj projekat (stornirani ulazi se ne računaju).
</p>

<div class="row g-3 mb-2">
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Planirano (komada)</div>
            <div class="fs-4 fw-semibold"><?= $planirano !== null ? $fmt($planirano) : '— (nije uneseno)' ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Proizvedeno (ulaz)</div>
            <div class="fs-4 fw-semibold text-primary"><?= $fmt($proizvedeno) ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Za proizvesti</div>
            <div class="fs-4 fw-semibold text-danger"><?= $zaProizvesti !== null ? $fmt($zaProizvesti) : '—' ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Realizacija proizvodnje</div>
            <div class="fs-4 fw-semibold"><?= $procenatProizvodnje !== null ? $procenatProizvodnje . '%' : '—' ?></div>
        </div></div>
    </div>
</div>
<?php if ($procenatProizvodnje !== null): ?>
<div class="progress mb-4" style="height:1.2rem;">
    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $procenatProizvodnje ?>%;"><?= $procenatProizvodnje ?>%</div>
</div>
<?php else: ?>
<div class="mb-4"></div>
<?php endif; ?>

<div class="row g-3 mb-2">
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Proizvedeno, čeka otpremu</div>
            <div class="fs-4 fw-semibold text-warning"><?= $fmt($cekaOtpremu) ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Otpremljeno</div>
            <div class="fs-4 fw-semibold text-success"><?= $fmt($otpremljeno) ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Za otpremu (od plana)</div>
            <div class="fs-4 fw-semibold text-danger"><?= $zaOtpremu !== null ? $fmt($zaOtpremu) : '—' ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Realizacija otpreme</div>
            <div class="fs-4 fw-semibold"><?= $procenatOtpreme !== null ? $procenatOtpreme . '%' : '—' ?></div>
        </div></div>
    </div>
</div>
<?php if ($procenatOtpreme !== null): ?>
<div class="progress mb-4" style="height:1.2rem;">
    <div class="progress-bar bg-dark" role="progressbar" style="width: <?= $procenatOtpreme ?>%;"><?= $procenatOtpreme ?>%</div>
</div>
<?php endif; ?>

<h6 class="mb-3">Proizvedeno / otpremljeno po elementu</h6>
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Šifra</th><th>Element</th>
                    <th class="text-end">Proizvedeno</th>
                    <th class="text-end">Otpremljeno</th>
                    <th class="text-end">Čeka otpremu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($poElementu)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Za ovaj projekat još nema evidentiranih ulaza ni otprema.</td></tr>
                <?php endif; ?>
                <?php foreach ($poElementu as $red): ?>
                    <tr>
                        <td><?= e($red['sifra']) ?></td>
                        <td><?= e($red['naziv']) ?></td>
                        <td class="text-end"><?= $fmt($red['proizvedeno']) ?></td>
                        <td class="text-end"><?= $fmt($red['otpremljeno']) ?></td>
                        <td class="text-end <?= $red['ceka_otpremu'] > 0 ? 'text-warning fw-semibold' : '' ?>"><?= $fmt($red['ceka_otpremu']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3">
    Napomena: "planirano/proizvedeno/otpremljeno" su zbirovi komada svih tipova elemenata za ovaj
    projekat (jedan ukupan broj, ne raspoređen po tipu unaprijed) — tabela iznad samo prikazuje
    KAKO se taj ukupan broj do sada sastavio po pojedinim elementima. Ulaz bez izabranog projekta
    se ne računa nijednom projektu.
</p>
