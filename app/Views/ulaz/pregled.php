<?php /** @var array $ulaz */ ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">
        Ulaz <?= e($ulaz['broj_ulaza']) ?>
        <?php if ($ulaz['status'] === 'storniran'): ?>
            <span class="badge text-bg-danger align-middle">Storniran</span>
        <?php endif; ?>
    </h3>
    <a href="<?= e(base_url('ulaz')) ?>" class="btn btn-outline-secondary btn-sm">Nazad na listu</a>
</div>

<?php if ($ulaz['status'] === 'storniran'): ?>
<div class="alert alert-danger">
    <strong>Storniran</strong> <?= e(date('d.m.Y H:i', strtotime($ulaz['datum_storniranja']))) ?>.<br>
    <strong>Razlog:</strong> <?= e($ulaz['razlog_storna']) ?>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>Datum:</strong> <?= e(date('d.m.Y', strtotime($ulaz['datum']))) ?></div>
            <div class="col-md-3"><strong>Skladište:</strong> <?= e($ulaz['skladiste_naziv']) ?></div>
            <div class="col-md-3"><strong>Investitor:</strong> <?= e($ulaz['investitor_naziv'] ?? '—') ?></div>
            <div class="col-md-3"><strong>Projekat:</strong> <?= e($ulaz['projekat_naziv'] ?? '—') ?></div>
            <div class="col-md-3 mt-2"><strong>Objekat:</strong> <?= e($ulaz['objekat_naziv'] ?? '—') ?></div>
            <div class="col-md-9 mt-2"><strong>Napomena:</strong> <?= e($ulaz['napomena'] ?? '—') ?></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light">
                <tr><th>Šifra</th><th>Element</th><th>Lokacija</th><th class="text-end">Količina</th></tr>
            </thead>
            <tbody>
                <?php foreach ($ulaz['stavke'] as $s): ?>
                    <tr>
                        <td><?= e($s['sifra']) ?></td>
                        <td><?= e($s['element_naziv']) ?></td>
                        <td><?= e($s['lokacija_oznaka']) ?></td>
                        <td class="text-end"><?= number_format((float) $s['kolicina'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($ulaz['status'] === 'aktivan' && ima_pristup('ulaz.storniraj')): ?>
<div class="card border-danger">
    <div class="card-body">
        <h6 class="text-danger mb-3">Storniraj ovaj ulaz</h6>
        <p class="text-muted small">
            Storniranje poništava stanje koje je ovaj ulaz unio. Moguće je samo ako roba u
            međuvremenu nije premještena/rezervisana/otpremljena tako da je više nema na traci
            gdje je ušla — u tom slučaju sistem odbija storno umjesto pogrešnog (negativnog) stanja.
        </p>
        <form method="post" action="<?= e(base_url('ulaz/storniraj/' . $ulaz['id'])) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Razlog storniranja *</label>
                <textarea name="razlog_storna" class="form-control" rows="2" required></textarea>
            </div>
            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Sigurno stornirati ovaj ulaz? Akcija se ne može poništiti.')">
                Storniraj ulaz
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
