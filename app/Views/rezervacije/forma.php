<?php
/** @var array $elementi */
/** @var array $lokacije */
/** @var array $investitori */
/** @var array $projekti */
/** @var array $objekti */

function opcije_select_rez(array $stavke, $izabrano = null): string
{
    $html = '<option value="">— izaberi —</option>';
    foreach ($stavke as $s) {
        $sel = ((string) $izabrano === (string) $s['id']) ? 'selected' : '';
        $html .= '<option value="' . (int) $s['id'] . '" ' . $sel . '>' . e($s['naziv']) . '</option>';
    }
    return $html;
}
?>

<h3 class="mb-4">Nova rezervacija</h3>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('rezervacije/dodaj')) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">AB element *</label>
                    <select name="element_id" class="form-select" required><?= opcije_select_rez($elementi, old('element_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lokacija *</label>
                    <select name="lokacija_id" class="form-select" required><?= opcije_select_rez($lokacije, old('lokacija_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Količina *</label>
                    <input type="number" step="0.01" min="0.01" name="kolicina" class="form-control" required value="<?= e(old('kolicina')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Investitor</label>
                    <select name="investitor_id" class="form-select"><?= opcije_select_rez($investitori, old('investitor_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Projekat</label>
                    <select name="projekat_id" class="form-select"><?= opcije_select_rez($projekti, old('projekat_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Objekat</label>
                    <select name="objekat_id" class="form-select"><?= opcije_select_rez($objekti, old('objekat_id')) ?></select>
                </div>
                <div class="col-12">
                    <label class="form-label">Napomena</label>
                    <input type="text" name="napomena" class="form-control" value="<?= e(old('napomena')) ?>">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Sačuvaj rezervaciju</button>
                <a href="<?= e(base_url('rezervacije')) ?>" class="btn btn-outline-secondary">Odustani</a>
            </div>
        </form>
    </div>
</div>
