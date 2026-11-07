<?php
/** @var array|null $korisnik */
/** @var array $role */
$jeIzmjena = $korisnik !== null;
$akcija = $jeIzmjena ? base_url('korisnici/izmijeni/' . $korisnik['id']) : base_url('korisnici/dodaj');
?>

<h3 class="mb-4"><?= $jeIzmjena ? 'Izmjena korisnika' : 'Novi korisnik' ?></h3>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= e($akcija) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ime</label>
                    <input type="text" name="ime" class="form-control" required
                           value="<?= e(old('ime', $korisnik['ime'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prezime</label>
                    <input type="text" name="prezime" class="form-control" required
                           value="<?= e(old('prezime', $korisnik['prezime'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Korisničko ime</label>
                    <input type="text" name="korisnicko_ime" class="form-control" required
                           value="<?= e(old('korisnicko_ime', $korisnik['korisnicko_ime'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= e(old('email', $korisnik['email'] ?? '')) ?>">
                </div>

                <?php if (!$jeIzmjena): ?>
                    <div class="col-md-6">
                        <label class="form-label">Lozinka</label>
                        <input type="password" name="lozinka" class="form-control" minlength="8" required>
                        <div class="form-text">Najmanje 8 karaktera.</div>
                    </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label">Uloga</label>
                    <select name="role_id" class="form-select">
                        <option value="">— bez uloge —</option>
                        <?php foreach ($role as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= (($korisnik['role_id'] ?? null) == $r['id']) ? 'selected' : '' ?>>
                                <?= e($r['naziv']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Bez izabrane uloge, korisnik NEMA nikakav pristup dok mu se ručno ne dodijele
                        permisije (Korisnici → Permisije) ili se naknadno dodijeli uloga.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktivan" <?= (($korisnik['status'] ?? 'aktivan') === 'aktivan') ? 'selected' : '' ?>>Aktivan</option>
                        <option value="neaktivan" <?= (($korisnik['status'] ?? '') === 'neaktivan') ? 'selected' : '' ?>>Neaktivan</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Sačuvaj</button>
                <a href="<?= e(base_url('korisnici')) ?>" class="btn btn-outline-secondary">Odustani</a>
            </div>
        </form>
    </div>
</div>

<?php if ($jeIzmjena && ima_pristup('korisnici.izmijeni')): ?>
<div class="card mt-3">
    <div class="card-body">
        <h6 class="mb-3">Reset lozinke</h6>
        <form method="post" action="<?= e(base_url('korisnici/resetuj-lozinku/' . $korisnik['id'])) ?>" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-4">
                <label class="form-label">Nova lozinka</label>
                <input type="password" name="nova_lozinka" class="form-control" minlength="8" required>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-dark" onclick="return confirm('Resetovati lozinku ovog korisnika?')">Resetuj lozinku</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
