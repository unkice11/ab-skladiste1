<?php /** @var array $firma */ ?>

<h3 class="mb-4">Podaci firme</h3>
<p class="text-muted">Ovi podaci se koriste na otpremnicama i izvještajima — nikad se ne unose ručno u dokumente.</p>

<div class="row g-4" style="max-width:900px;">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= e(base_url('postavke/podaci-firme')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Naziv firme</label>
                            <input type="text" name="naziv" class="form-control" value="<?= e($firma['naziv'] ?? '') ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Adresa</label>
                            <input type="text" name="adresa" class="form-control" value="<?= e($firma['adresa'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Grad</label>
                            <input type="text" name="grad" class="form-control" value="<?= e($firma['grad'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">JIB</label>
                            <input type="text" name="jib" class="form-control" value="<?= e($firma['jib'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PDV broj</label>
                            <input type="text" name="pdv_broj" class="form-control" value="<?= e($firma['pdv_broj'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="telefon" class="form-control" value="<?= e($firma['telefon'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="text" name="email" class="form-control" value="<?= e($firma['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Web</label>
                            <input type="text" name="web" class="form-control" value="<?= e($firma['web'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Logotip (PNG/JPG/WEBP, do 2 MB)</label>
                            <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg,.webp">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-dark">Sačuvaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="text-muted small mb-2">Trenutni logotip</div>
                <?php if (!empty($firma['logo_putanja'])): ?>
                    <img src="<?= e(base_url($firma['logo_putanja'])) ?>" alt="Logo" style="max-width:100%;max-height:150px;">
                <?php else: ?>
                    <div class="text-muted">Nije postavljen</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
