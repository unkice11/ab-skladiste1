<?php
/** @var array|null $zapis */
/** @var array $polja */
/** @var string $nazivModula */
/** @var string $rutaBazna */
$jeIzmjena = $zapis !== null;
$akcija = $jeIzmjena ? base_url($rutaBazna . '/izmijeni/' . $zapis['id']) : base_url($rutaBazna . '/dodaj');
?>

<h3 class="mb-4"><?= $jeIzmjena ? 'Izmjena — ' . e($nazivModula) : 'Novi zapis — ' . e($nazivModula) ?></h3>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= e($akcija) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <?php foreach ($polja as $p): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= e($p['oznaka']) ?><?= !empty($p['obavezno']) ? ' *' : '' ?></label>

                        <?php $vrijednost = old($p['ime'], $zapis[$p['ime']] ?? ''); ?>

                        <?php if ($p['tip'] === 'textarea'): ?>
                            <textarea name="<?= e($p['ime']) ?>" class="form-control" rows="2"><?= e((string) $vrijednost) ?></textarea>

                        <?php elseif ($p['tip'] === 'select'): ?>
                            <select name="<?= e($p['ime']) ?>" class="form-select">
                                <option value="">— nije izabrano —</option>
                                <?php foreach (($p['opcije'] ?? []) as $opcija): ?>
                                    <option value="<?= (int) $opcija['id'] ?>" <?= ((string) $vrijednost === (string) $opcija['id']) ? 'selected' : '' ?>>
                                        <?= e($opcija['naziv']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php elseif ($p['tip'] === 'checkbox'): ?>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" name="<?= e($p['ime']) ?>" value="1"
                                       <?= $vrijednost ? 'checked' : '' ?>>
                            </div>

                        <?php elseif ($p['tip'] === 'number'): ?>
                            <input type="number" step="0.001" name="<?= e($p['ime']) ?>" class="form-control"
                                   value="<?= e((string) $vrijednost) ?>" <?= !empty($p['obavezno']) ? 'required' : '' ?>>

                        <?php elseif ($p['tip'] === 'date'): ?>
                            <input type="date" name="<?= e($p['ime']) ?>" class="form-control" value="<?= e((string) $vrijednost) ?>">

                        <?php else: ?>
                            <input type="text" name="<?= e($p['ime']) ?>" class="form-control"
                                   value="<?= e((string) $vrijednost) ?>" <?= !empty($p['obavezno']) ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Sačuvaj</button>
                <a href="<?= e(base_url($rutaBazna)) ?>" class="btn btn-outline-secondary">Odustani</a>
            </div>
        </form>
    </div>
</div>
