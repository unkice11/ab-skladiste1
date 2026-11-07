<?php
/** @var array $meni */
/** @var array|null $korisnik */
/** @var array|null $firma */
/** @var array $flash */

function prikazi_stavke_menija(array $stavke, string $trenutnaPutanja): void
{
    foreach ($stavke as $stavka) {
        $imaDjecu = !empty($stavka['djeca']);

        if ($imaDjecu) {
            $idKolapsa = 'meni-' . $stavka['id'];
            echo '<li class="nav-item">';
            echo '<a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#' . $idKolapsa . '" role="button">';
            echo '<span><i class="' . e($stavka['ikona'] ?: 'bi bi-dot') . ' me-2"></i>' . e($stavka['naziv']) . '</span>';
            echo '<i class="bi bi-chevron-down small"></i>';
            echo '</a>';
            echo '<div class="collapse show" id="' . $idKolapsa . '"><ul class="nav flex-column ps-3">';
            prikazi_stavke_menija($stavka['djeca'], $trenutnaPutanja);
            echo '</ul></div></li>';
        } else {
            $url = $stavka['url'] ?? '#';
            $aktivna = $url !== '' && str_starts_with($trenutnaPutanja, trim($url, '/')) ? ' active' : '';
            echo '<li class="nav-item">';
            // Napomena: NE stavljati data-bs-dismiss="offcanvas" na ovaj link —
            // Bootstrap-ov JS za dismiss poziva event.preventDefault() na klik,
            // što bi blokiralo navigaciju (href) na linku. Pošto je ovo klasična
            // multi-page aplikacija, klik na stavku menija ionako učitava novu
            // stranicu, čime se offcanvas panel sam "zatvara" (cijeli DOM se
            // zamijeni) — nije potreban nikakav eksplicitni dismiss ovdje.
            echo '<a class="nav-link' . $aktivna . '" href="' . e(base_url($url)) . '">';
            echo '<i class="' . e($stavka['ikona'] ?: 'bi bi-dot') . ' me-2"></i>' . e($stavka['naziv']);
            echo '</a></li>';
        }
    }
}

$trenutnaPutanja = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
?>
<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>PREKON<?= isset($nazivStranice) ? ' — ' . e($nazivStranice) : '' ?></title>
    <link rel="icon" href="<?= e(prekon_ikona_url(64)) ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/prekon-theme.css')) ?>" rel="stylesheet">
    <style>
        :root { box-sizing: border-box; }
        body { background: var(--prekon-pozadina); }

        #sidebarOffcanvas { --bs-offcanvas-width: 260px; }
        .sidebar { min-height: 100vh; }
        .sidebar .nav-link { color: #C3CCD8; border-radius: .375rem; }
        .sidebar .brand { color: #fff; font-weight: 700; letter-spacing: .5px; font-size: 1.05rem; }
        .sidebar .brand-tagline { color: #8CA0C0; font-size: 0.62rem; letter-spacing: .08em; text-transform: uppercase; }

        .topbar {
            background: var(--prekon-povrsina); border-bottom: 1px solid #e5e9ef;
            padding-top: max(0.5rem, env(safe-area-inset-top, 0px));
        }
        .sadrzaj-omot { position: relative; min-width: 0; }
        .sadrzaj-wallpaper {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background-image: url('<?= e(base_url('assets/img/prekon-hero.jpg')) ?>');
            background-size: cover; background-position: center;
            opacity: 0.06;
        }
        .sadrzaj-prednji { position: relative; z-index: 1; }

        /* Na uskim ekranima tabele koje nisu eksplicitno omotane skrolaju vodoravno
           umjesto da razvuku stranicu. */
        @media (max-width: 991.98px) {
            .p-4 { padding: 1rem !important; }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <div class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" style="width:260px;">
        <div class="offcanvas-body d-flex flex-column p-3">
            <div class="d-flex align-items-center justify-content-between mb-4 px-2">
                <div class="d-flex align-items-center gap-2">
                    <img src="<?= e(prekon_ikona_url(64)) ?>" alt="PREKON" style="height:34px;width:34px;border-radius:8px;">
                    <div>
                        <div class="brand lh-1">PREKON</div>
                        <div class="brand-tagline">Planiraj. Proizvedi. Isporuči.</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Zatvori meni"></button>
            </div>
            <ul class="nav flex-column">
                <?php prikazi_stavke_menija($meni, $trenutnaPutanja); ?>
            </ul>
        </div>
    </div>

    <div class="flex-grow-1 sadrzaj-omot">
        <?php if ($trenutnaPutanja === '' || $trenutnaPutanja === 'dashboard'): ?>
            <div class="sadrzaj-wallpaper"></div>
        <?php endif; ?>
        <div class="sadrzaj-prednji">
        <div class="topbar d-flex justify-content-between align-items-center px-4 py-2">
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
                <i class="bi bi-list"></i>
            </button>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>
                    <span class="d-none d-sm-inline"><?= e(($korisnik['ime'] ?? '') . ' ' . ($korisnik['prezime'] ?? '')) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= e(base_url('odjava')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Odjava</a></li>
                </ul>
            </div>
        </div>

        <div class="p-4">
            <?php foreach (($flash['uspjeh'] ?? []) as $poruka): ?>
                <div class="alert alert-success"><?= e($poruka) ?></div>
            <?php endforeach; ?>
            <?php foreach (($flash['greska'] ?? []) as $poruka): ?>
                <div class="alert alert-danger"><?= e($poruka) ?></div>
            <?php endforeach; ?>

            <?php require $viewFajl; ?>
        </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
