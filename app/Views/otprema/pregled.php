<?php /** @var array $otprema */ ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">
        Otprema <?= e($otprema['broj_otpremnice']) ?>
        <?php if ($otprema['status'] === 'stornirana'): ?>
            <span class="badge text-bg-danger align-middle">Stornirana</span>
        <?php endif; ?>
    </h3>
    <div>
        <a href="<?= e(base_url('otprema/' . $otprema['id'] . '/otpremnica')) ?>" class="btn btn-dark btn-sm" target="_blank">
            <i class="bi bi-printer"></i> Otpremnica (štampa)
        </a>
        <a href="<?= e(base_url('otprema')) ?>" class="btn btn-outline-secondary btn-sm">Nazad na listu</a>
    </div>
</div>

<?php if ($otprema['status'] === 'stornirana'): ?>
<div class="alert alert-danger">
    <strong>Stornirana</strong> <?= e(date('d.m.Y H:i', strtotime($otprema['datum_storniranja']))) ?>.<br>
    <strong>Razlog:</strong> <?= e($otprema['razlog_storna']) ?>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>Datum:</strong> <?= e(date('d.m.Y', strtotime($otprema['datum']))) ?></div>
            <div class="col-md-3"><strong>Investitor:</strong> <?= e($otprema['investitor_naziv'] ?? '—') ?></div>
            <div class="col-md-3"><strong>Projekat:</strong> <?= e($otprema['projekat_naziv'] ?? '—') ?></div>
            <div class="col-md-3"><strong>Objekat:</strong> <?= e($otprema['objekat_naziv'] ?? '—') ?></div>
            <div class="col-md-3 mt-2"><strong>Prevoznik:</strong> <?= e($otprema['prevoznik_naziv'] ?? '—') ?></div>
            <div class="col-md-3 mt-2"><strong>Vozilo:</strong> <?= e($otprema['registarska_oznaka'] ?? '—') ?></div>
            <div class="col-md-3 mt-2"><strong>Vozač:</strong> <?= e(trim(($otprema['vozac_ime'] ?? '') . ' ' . ($otprema['vozac_prezime'] ?? '')) ?: '—') ?></div>
            <div class="col-md-3 mt-2"><strong>Kreirao:</strong> <?= e($otprema['korisnicko_ime'] ?? '—') ?></div>
            <div class="col-md-9 mt-2"><strong>Napomena:</strong> <?= e($otprema['napomena'] ?? '—') ?></div>
        </div>
    </div>
</div>

<?php if (!empty($otprema['nosivost_kg']) && $otprema['ukupna_tezina_kg'] > (float) $otprema['nosivost_kg']): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Ukupna težina (<?= number_format($otprema['ukupna_tezina_kg'], 0, ',', '.') ?> kg) premašuje
    nosivost vozila (<?= number_format((float) $otprema['nosivost_kg'], 0, ',', '.') ?> kg).
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light">
                <tr><th>Šifra</th><th>Element</th><th>Lokacija</th><th class="text-end">Količina</th><th class="text-end">kg/kom</th><th class="text-end">Ukupno (kg)</th></tr>
            </thead>
            <tbody>
                <?php foreach ($otprema['stavke'] as $s): ?>
                    <tr>
                        <td><?= e($s['sifra']) ?></td>
                        <td><?= e($s['element_naziv']) ?></td>
                        <td><?= e($s['lokacija_oznaka']) ?></td>
                        <td class="text-end"><?= number_format((float) $s['kolicina'], 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $s['kg_po_komadu'], 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format((float) $s['ukupna_tezina_kg'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-semibold">
                    <td colspan="3">Ukupno</td>
                    <td class="text-end"><?= number_format($otprema['ukupno_komada'], 2, ',', '.') ?></td>
                    <td></td>
                    <td class="text-end"><?= number_format($otprema['ukupna_tezina_kg'], 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if ($otprema['status'] === 'aktivna' && ima_pristup('otprema.storniraj')): ?>
<div class="card border-danger mt-3">
    <div class="card-body">
        <h6 class="text-danger mb-3">Storniraj ovu otpremu</h6>
        <p class="text-muted small">
            Storniranje vraća stanje koje je ova otprema umanjila (po svim stavkama, na istu
            investitor/projekat traku), i ako je stavka bila vezana na rezervaciju, vraća i nju
            u prethodno stanje.
        </p>
        <form method="post" action="<?= e(base_url('otprema/storniraj/' . $otprema['id'])) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Razlog storniranja *</label>
                <textarea name="razlog_storna" class="form-control" rows="2" required></textarea>
            </div>
            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Sigurno stornirati ovu otpremu? Akcija se ne može poništiti.')">
                Storniraj otpremu
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
