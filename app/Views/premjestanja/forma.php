<?php
/** @var array $elementi */
/** @var array $lokacije */
/** @var array $investitori */
/** @var array $projekti */

function opcije_select_prem(array $stavke, $izabrano = null): string
{
    $html = '<option value="">— izaberi —</option>';
    foreach ($stavke as $s) {
        $sel = ((string) $izabrano === (string) $s['id']) ? 'selected' : '';
        $html .= '<option value="' . (int) $s['id'] . '" ' . $sel . '>' . e($s['naziv']) . '</option>';
    }
    return $html;
}
?>

<h3 class="mb-4">Novo premještanje</h3>

<div class="alert alert-info" style="max-width:700px;">
    Premještanje je moguće samo za <strong>slobodnu (nerezervisanu)</strong> količinu na izvornoj
    lokaciji, za IZABRANU investitor/projekat traku. Ciljna lokacija zadržava istu
    investitor/projekat pripadnost — premještanje mijenja samo lokaciju, ne vlasništvo.
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('premjestanja/dodaj')) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">AB element *</label>
                    <select name="element_id" class="form-select" required><?= opcije_select_prem($elementi, old('element_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Investitor (traka stanja)</label>
                    <select name="investitor_id" class="form-select"><?= opcije_select_prem($investitori, old('investitor_id')) ?></select>
                    <div class="form-text">Ostaviti prazno za "generičku" traku bez investitora.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Projekat (traka stanja)</label>
                    <select name="projekat_id" class="form-select"><?= opcije_select_prem($projekti, old('projekat_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Iz lokacije *</label>
                    <select name="iz_lokacija_id" class="form-select" required><?= opcije_select_prem($lokacije, old('iz_lokacija_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">U lokaciju *</label>
                    <select name="u_lokacija_id" class="form-select" required><?= opcije_select_prem($lokacije, old('u_lokacija_id')) ?></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Količina *</label>
                    <input type="number" step="0.01" min="0.01" name="kolicina" class="form-control" required value="<?= e(old('kolicina')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Napomena</label>
                    <input type="text" name="napomena" class="form-control" value="<?= e(old('napomena')) ?>">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Sačuvaj premještanje</button>
                <a href="<?= e(base_url('premjestanja')) ?>" class="btn btn-outline-secondary">Odustani</a>
            </div>
        </form>
    </div>
</div>
