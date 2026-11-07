<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prijava — PREKON</title>
    <link rel="icon" href="<?= e(prekon_ikona_url(64)) ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/prekon-theme.css')) ?>" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('<?= e(base_url('assets/img/prekon-hero.jpg')) ?>');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
        }
        .stranica-prijave {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 24px;
        }
        .kartica-prijave {
            max-width: 380px; width: 100%;
            background: rgba(255,255,255,0.96);
            border-radius: 0.75rem;
            box-shadow: 0 20px 50px rgba(11,31,56,0.35);
            padding: 2rem;
            backdrop-filter: blur(2px);
        }
        .kartica-prijave .naslov { font-weight: 700; font-size: 1.15rem; color: var(--prekon-primarna); }
        .kartica-prijave .podnaslov { color: var(--prekon-neutralna); font-size: 0.85rem; }
        .polje-sa-ikonom { position: relative; }
        .polje-sa-ikonom i.ikona-polja {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--prekon-neutralna);
        }
        .polje-sa-ikonom input { padding-left: 40px; }
        .dugme-oko {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            border: none; background: none; color: var(--prekon-neutralna); cursor: pointer;
        }

        @media (max-width: 767.98px) {
            .stranica-prijave { justify-content: center; background: rgba(11,31,56,0.55); }
        }
    </style>
</head>
<body>
<div class="stranica-prijave">
    <div class="kartica-prijave">
        <div class="d-flex align-items-center gap-2 mb-4">
            <img src="<?= e(prekon_ikona_url(64)) ?>" alt="PREKON" style="height:42px;width:42px;border-radius:10px;">
            <div class="naslov lh-1">PREKON</div>
        </div>

        <h5 class="mb-1">Dobro došli u PREKON</h5>
        <p class="podnaslov mb-4">Prijavite se da biste nastavili.</p>

        <?php foreach (($flash['greska'] ?? []) as $poruka): ?>
            <div class="alert alert-danger py-2"><?= e($poruka) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= e(base_url('prijava')) ?>">
            <?= csrf_field() ?>
            <div class="mb-3 polje-sa-ikonom">
                <label class="form-label">Korisničko ime</label>
                <i class="bi bi-person ikona-polja"></i>
                <input type="text" name="korisnicko_ime" class="form-control" value="<?= e(old('korisnicko_ime')) ?>" autofocus required>
            </div>
            <div class="mb-3 polje-sa-ikonom">
                <label class="form-label">Lozinka</label>
                <i class="bi bi-lock ikona-polja"></i>
                <input type="password" name="lozinka" id="polje-lozinke" class="form-control" required>
                <button type="button" class="dugme-oko" id="dugme-prikazi-lozinku" style="margin-top:12px;" tabindex="-1">
                    <i class="bi bi-eye" id="ikona-oka"></i>
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="zapamti_me" id="zapamti_me">
                    <label class="form-check-label" for="zapamti_me">Zapamti me</label>
                </div>
                <span class="small" style="color:var(--prekon-sekundarna); cursor:default;" title="Obratite se administratoru sistema za reset lozinke.">
                    Zaboravili ste lozinku?
                </span>
            </div>
            <button type="submit" class="btn btn-dark w-100">Prijava <i class="bi bi-arrow-right ms-1"></i></button>
        </form>
    </div>
</div>

<script>
document.getElementById('dugme-prikazi-lozinku').addEventListener('click', function () {
    var polje = document.getElementById('polje-lozinke');
    var ikona = document.getElementById('ikona-oka');
    var jeVidljivo = polje.type === 'text';
    polje.type = jeVidljivo ? 'password' : 'text';
    ikona.className = jeVidljivo ? 'bi bi-eye' : 'bi bi-eye-slash';
});
</script>
</body>
</html>
