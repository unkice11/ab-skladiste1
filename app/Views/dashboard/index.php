<?php
/** @var array $ukupnoStanje */
/** @var float $ukupnoRezervisano */
/** @var int $ulaziDanas */
/** @var int $otpremeDanas */
/** @var array $posljednjeAktivnosti */

$naziviTipova = [
    'ulaz' => ['Ulaz', 'bi-box-arrow-in-down', 'text-success', 'ulaz'],
    'otprema' => ['Otprema', 'bi-box-arrow-up', 'text-primary', 'otprema'],
    'premjestanje' => ['Premještanje', 'bi-arrow-left-right', 'text-secondary', 'premjestanja'],
];
?>
<h3 class="mb-4">Dashboard</h3>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Ukupno komada na skladištu</div>
                <div class="fs-4 fw-semibold"><?= number_format($ukupnoStanje['ukupno_komada'], 0, ',', '.') ?></div>
                <div class="text-muted small"><?= $ukupnoStanje['broj_elemenata'] ?> različitih elemenata</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Ukupna okvirna težina</div>
                <div class="fs-4 fw-semibold"><?= number_format($ukupnoStanje['ukupna_tezina'], 0, ',', '.') ?> kg</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Ulazi / Otpreme danas</div>
                <div class="fs-4 fw-semibold"><?= $ulaziDanas ?> / <?= $otpremeDanas ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Ukupno rezervisano (komada)</div>
                <div class="fs-4 fw-semibold"><?= number_format($ukupnoRezervisano, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header fw-semibold">Posljednje aktivnosti</div>
            <div class="list-group list-group-flush">
                <?php if (empty($posljednjeAktivnosti)): ?>
                    <div class="list-group-item text-muted">Još nema evidentiranih aktivnosti.</div>
                <?php endif; ?>
                <?php foreach ($posljednjeAktivnosti as $a): ?>
                    <?php [$naziv, $ikona, $boja, $ruta] = $naziviTipova[$a['tip']]; ?>
                    <a href="<?= e(base_url($ruta . ('otprema' === $a['tip'] || 'ulaz' === $a['tip'] ? '/' . $a['id'] : ''))) ?>"
                       class="list-group-item list-group-item-action d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1">
                        <span>
                            <i class="bi <?= $ikona ?> <?= $boja ?> me-2"></i>
                            <span class="fw-semibold"><?= e($naziv) ?></span> — <?= e($a['broj']) ?>
                        </span>
                        <span class="text-muted small"><?= e(date('d.m.Y H:i', strtotime($a['datum_kreiranja']))) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header fw-semibold">Brze akcije</div>
            <div class="list-group list-group-flush">
                <?php if (ima_pristup('ulaz.uredi')): ?>
                    <a href="<?= e(base_url('ulaz/dodaj')) ?>" class="list-group-item list-group-item-action"><i class="bi bi-plus-lg me-2"></i>Novi ulaz</a>
                <?php endif; ?>
                <?php if (ima_pristup('otprema.uredi')): ?>
                    <a href="<?= e(base_url('otprema/dodaj')) ?>" class="list-group-item list-group-item-action"><i class="bi bi-plus-lg me-2"></i>Nova otprema</a>
                <?php endif; ?>
                <?php if (ima_pristup('rezervacije.uredi')): ?>
                    <a href="<?= e(base_url('rezervacije/dodaj')) ?>" class="list-group-item list-group-item-action"><i class="bi bi-plus-lg me-2"></i>Nova rezervacija</a>
                <?php endif; ?>
                <?php if (ima_pristup('izvjestaji.view')): ?>
                    <a href="<?= e(base_url('izvjestaji')) ?>" class="list-group-item list-group-item-action"><i class="bi bi-bar-chart me-2"></i>Izvještaji</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
