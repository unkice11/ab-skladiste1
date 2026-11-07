<?php
/** @var array $firma */
/**
 * Print layout — A4. Ekranski prikaz izgleda kao list papira (210mm), a
 * @page pravila određuju stvarni format štampe. Pogled može postaviti
 * $printOrijentacija = 'landscape' za široke tabele (npr. stanje skladišta).
 */
$orijentacija = (($printOrijentacija ?? 'portrait') === 'landscape') ? 'landscape' : 'portrait';
$sirinaListaMm = $orijentacija === 'landscape' ? 297 : 210;
$visinaListaMm = $orijentacija === 'landscape' ? 210 : 297;
$marginaMm = 12;
// minimalna visina sadržaja pri štampi (malo manje od visine stranice minus margine, da ne nastane prazna stranica)
$minVisinaStampeMm = $visinaListaMm - 2 * $marginaMm - 5;
?>
<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name')) ?></title>
    <link rel="icon" href="<?= e(prekon_ikona_url(64)) ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/prekon-theme.css')) ?>" rel="stylesheet">
    <style>
        @page { size: A4 <?= $orijentacija ?>; margin: <?= $marginaMm ?>mm; }

        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        body {
            margin: 0;
            padding: 20px 0;
            background: #E2E8F0;
            color: var(--prekon-tekst, #111);
            font-family: 'Inter', sans-serif;
        }

        .dugmad-akcije { width: <?= $sirinaListaMm ?>mm; max-width: 100%; margin: 0 auto 12px; }

        /* "List papira" na ekranu — isto što će biti odštampano */
        .a4-list {
            width: <?= $sirinaListaMm ?>mm;
            min-height: <?= $visinaListaMm ?>mm;
            margin: 0 auto;
            padding: <?= $marginaMm ?>mm;
            background: #fff;
            box-shadow: 0 4px 20px rgba(11, 31, 56, 0.18);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        .a4-sadrzaj { flex: 1 0 auto; }

        .zaglavlje-firme { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 4mm; margin-bottom: 6mm; }
        .zaglavlje-firme img { max-height: 20mm; max-width: 60mm; }
        .zaglavlje-firme .podaci { text-align: right; font-size: 9pt; color: #444; line-height: 1.35; }

        .a4-podnozje {
            margin-top: 8mm;
            padding-top: 2mm;
            border-top: 1px solid #ddd;
            font-size: 7.5pt;
            color: #64748B;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }

        @media screen and (max-width: 991.98px) {
            .a4-list-omot { overflow-x: auto; padding: 0 8px; }
            .a4-list { margin: 0; }
        }
        @media print {
            html, body { background: #fff !important; padding: 0; margin: 0; }
            .dugmad-akcije { display: none !important; }
            .a4-list {
                width: auto;
                min-height: <?= $minVisinaStampeMm ?>mm;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            a[href]:after { content: none !important; }
            tr, .ne-lomi { page-break-inside: avoid; break-inside: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-row-group; }
            table { font-size: 9.5pt; }
        }
        @media screen {
            table { font-size: 0.92rem; }
        }
    </style>
</head>
<body>

<div class="dugmad-akcije">
    <button class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Štampaj / Sačuvaj kao PDF</button>
    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">Nazad</a>
    <span class="text-muted small ms-2">Format: A4 <?= $orijentacija === 'landscape' ? 'položeno' : 'uspravno' ?> — u print dijalogu isključiti "Headers and footers" za čist ispis.</span>
</div>

<div class="a4-list-omot">
<div class="a4-list">
    <div class="zaglavlje-firme">
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($firma['logo_putanja'])): ?>
                <img src="<?= e(base_url($firma['logo_putanja'])) ?>" alt="Logo">
            <?php elseif (!empty($firma['naziv'])): ?>
                <div class="fw-bold fs-4"><?= e($firma['naziv']) ?></div>
            <?php else: ?>
                <div class="text-muted small">(Podaci firme nisu uneseni — Postavke → Podaci firme)</div>
            <?php endif; ?>
        </div>
        <div class="podaci">
            <?php if (!empty($firma['naziv'])): ?><div class="fw-semibold"><?= e($firma['naziv']) ?></div><?php endif; ?>
            <?php if (!empty($firma['adresa']) || !empty($firma['grad'])): ?>
                <div><?= e(trim(($firma['adresa'] ?? '') . ', ' . ($firma['grad'] ?? ''), ', ')) ?></div>
            <?php endif; ?>
            <?php if (!empty($firma['jib'])): ?><div>JIB: <?= e($firma['jib']) ?></div><?php endif; ?>
            <?php if (!empty($firma['pdv_broj'])): ?><div>PDV: <?= e($firma['pdv_broj']) ?></div><?php endif; ?>
            <?php if (!empty($firma['telefon'])): ?><div>Tel: <?= e($firma['telefon']) ?></div><?php endif; ?>
            <?php if (!empty($firma['email'])): ?><div><?= e($firma['email']) ?></div><?php endif; ?>
        </div>
    </div>

    <div class="a4-sadrzaj">
        <?php require $viewFajl; ?>
    </div>

    <div class="a4-podnozje">
        <img src="<?= e(prekon_ikona_url(64)) ?>" alt="" style="height:12px;width:12px;border-radius:2px;">
        Prekon-lite v1.0 / Pametniji procesi . Created by Edim Unkic
    </div>
</div>
</div>

</body>
</html>
