<?php
/** @var array $otprema */

/** Količina: cijeli broj bez decimala, inače do 2 decimale (npr. 10 ili 2,5). */
$fmtKol = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
/** Težina u kg sa 2 decimale — aritmetika na dokumentu se može provjeriti (kom × kg/kom = ukupno). */
$fmtKg = fn($v) => number_format((float) $v, 2, ',', '.');

$stavke = $otprema['stavke'];
$viseStavki = count($stavke) > 1;

// Zbir po elementu — prikazuje se samo ako se isti element javlja u više redova (npr. sa različitih lokacija).
$poElementu = [];
foreach ($stavke as $s) {
    $k = $s['sifra'];
    if (!isset($poElementu[$k])) {
        $poElementu[$k] = ['sifra' => $s['sifra'], 'naziv' => $s['element_naziv'], 'redova' => 0, 'komada' => 0.0, 'kg' => 0.0];
    }
    $poElementu[$k]['redova']++;
    $poElementu[$k]['komada'] += (float) $s['kolicina'];
    $poElementu[$k]['kg'] += (float) $s['ukupna_tezina_kg'];
}
$imaPonavljanja = (bool) array_filter($poElementu, fn($e) => $e['redova'] > 1);

$vozac = trim(($otprema['vozac_ime'] ?? '') . ' ' . ($otprema['vozac_prezime'] ?? ''));
?>
<style>
    .otp-naslov { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 5mm; }
    .otp-naslov h1 { font-size: 22pt; font-weight: 700; letter-spacing: .06em; margin: 0; line-height: 1; }
    .otp-meta { text-align: right; font-size: 10pt; line-height: 1.4; }
    .otp-meta .broj { font-size: 14pt; font-weight: 700; }

    .otp-podaci { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; margin-bottom: 6mm; }
    .otp-kutija { border: 1px solid #94A3B8; border-radius: 1.5mm; padding: 3mm 4mm; font-size: 10pt; }
    .otp-kutija h6 { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .1em; color: #64748B; margin: 0 0 2mm; font-weight: 600; }
    .otp-red { display: flex; justify-content: space-between; gap: 4mm; padding: .5mm 0; }
    .otp-red > span:first-child { color: #64748B; white-space: nowrap; }
    .otp-red > span:last-child { text-align: right; font-weight: 600; }

    .otp-tabela { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    .otp-tabela th, .otp-tabela td { border: 1px solid #64748B; padding: 1.6mm 2mm; vertical-align: top; }
    .otp-tabela thead th { background: #E2E8F0; font-weight: 600; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .04em; }
    .otp-tabela .d { text-align: right; white-space: nowrap; }
    .otp-tabela .c { text-align: center; }
    .otp-ukupno td { font-weight: 700; background: #F1F5F9; font-size: 10.5pt; }

    .otp-rekap { font-size: 9pt; margin-bottom: 4mm; }
    .otp-rekap h6 { font-size: 8pt; text-transform: uppercase; letter-spacing: .08em; color: #64748B; margin: 0 0 1.5mm; font-weight: 600; }
    .otp-rekap table { width: auto; border-collapse: collapse; }
    .otp-rekap th, .otp-rekap td { border: 1px solid #CBD5E1; padding: 1mm 3mm; }
    .otp-rekap th { background: #F8FAFC; font-weight: 600; }

    .otp-napomena { border: 1px solid #CBD5E1; border-radius: 1.5mm; padding: 2.5mm 4mm; font-size: 9.5pt; margin-bottom: 3mm; }
    .otp-sitno { font-size: 8pt; color: #64748B; font-style: italic; margin-bottom: 4mm; }

    .otp-potpisi { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10mm; margin-top: 16mm; page-break-inside: avoid; break-inside: avoid; }
    .otp-potpis { border-top: 1px solid #333; padding-top: 1.5mm; text-align: center; font-size: 9pt; }
    .otp-potpis small { display: block; color: #64748B; font-size: 7.5pt; }
    .otp-sastavio { margin-top: 5mm; font-size: 8pt; color: #64748B; }
</style>

<?php if (($otprema['status'] ?? 'aktivna') === 'stornirana'): ?>
    <div style="border:3px solid #DC2626; color:#DC2626; padding:3mm 4mm; margin-bottom:5mm; text-align:center; font-weight:700; border-radius:1.5mm;">
        <div style="font-size:16pt; letter-spacing:.15em;">STORNIRANA — NEVAŽEĆI DOKUMENT</div>
        <div style="font-size:9pt; font-weight:400; margin-top:1mm;">
            Storno: <?= e(!empty($otprema['datum_storniranja']) ? date('d.m.Y H:i', strtotime($otprema['datum_storniranja'])) : '—') ?>
            — Razlog: <?= e($otprema['razlog_storna'] ?? '—') ?>
        </div>
    </div>
<?php endif; ?>

<div class="otp-naslov">
    <h1>OTPREMNICA</h1>
    <div class="otp-meta">
        <div class="broj">Br. <?= e($otprema['broj_otpremnice']) ?></div>
        <div>Datum: <strong><?= e(date('d.m.Y', strtotime($otprema['datum']))) ?></strong></div>
    </div>
</div>

<div class="otp-podaci ne-lomi">
    <div class="otp-kutija">
        <h6>Investitor / Gradilište</h6>
        <div class="otp-red"><span>Investitor</span><span><?= e($otprema['investitor_naziv'] ?? '—') ?></span></div>
        <div class="otp-red"><span>Projekat</span><span><?= e($otprema['projekat_naziv'] ?? '—') ?></span></div>
        <div class="otp-red"><span>Objekat</span><span><?= e($otprema['objekat_naziv'] ?? '—') ?></span></div>
    </div>
    <div class="otp-kutija">
        <h6>Prevoz</h6>
        <div class="otp-red"><span>Prevoznik</span><span><?= e($otprema['prevoznik_naziv'] ?? '—') ?></span></div>
        <div class="otp-red">
            <span>Vozilo</span>
            <span>
                <?= e($otprema['registarska_oznaka'] ?? '—') ?>
                <?php if (!empty($otprema['nosivost_kg'])): ?>
                    <span style="font-weight:400;color:#64748B;">(nosivost <?= e(number_format((float) $otprema['nosivost_kg'], 0, ',', '.')) ?> kg)</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="otp-red"><span>Vozač</span><span><?= e($vozac ?: '—') ?></span></div>
    </div>
</div>

<table class="otp-tabela">
    <thead>
        <tr>
            <th class="c" style="width:8mm">R.br.</th>
            <th style="width:22mm">Šifra</th>
            <th>Naziv elementa</th>
            <th style="width:38mm">Napomena</th>
            <th class="d" style="width:18mm">Količina<br>(kom)</th>
            <th class="d" style="width:24mm">Okvirna težina<br>kg / kom</th>
            <th class="d" style="width:28mm">Ukupno<br>(kg)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($stavke as $i => $s): ?>
            <tr>
                <td class="c"><?= $i + 1 ?></td>
                <td><strong><?= e($s['sifra']) ?></strong></td>
                <td><?= e($s['element_naziv']) ?></td>
                <td><?= e($s['element_opis'] ?? '') ?: '—' ?></td>
                <td class="d"><?= e($fmtKol($s['kolicina'])) ?></td>
                <td class="d"><?= e($fmtKg($s['kg_po_komadu'])) ?></td>
                <td class="d"><strong><?= e($fmtKg($s['ukupna_tezina_kg'])) ?></strong></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <?php if ($viseStavki): ?>
    <tfoot>
        <tr class="otp-ukupno">
            <td colspan="4">UKUPNO (<?= count($stavke) ?> stavki)</td>
            <td class="d"><?= e($fmtKol($otprema['ukupno_komada'])) ?></td>
            <td></td>
            <td class="d"><?= e($fmtKg($otprema['ukupna_tezina_kg'])) ?> kg</td>
        </tr>
        <tr class="otp-ukupno">
            <td colspan="6" style="text-align:right;">Ukupna okvirna težina u tonama</td>
            <td class="d"><?= e(number_format($otprema['ukupna_tezina_kg'] / 1000, 3, ',', '.')) ?> t</td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>

<?php if ($viseStavki && $imaPonavljanja): ?>
<div class="otp-rekap ne-lomi">
    <h6>Zbirno po elementu</h6>
    <table>
        <thead><tr><th>Šifra</th><th>Naziv</th><th class="d" style="text-align:right">Komada</th><th style="text-align:right">Težina (kg)</th></tr></thead>
        <tbody>
            <?php foreach ($poElementu as $e2): ?>
                <tr>
                    <td><strong><?= e($e2['sifra']) ?></strong></td>
                    <td><?= e($e2['naziv']) ?></td>
                    <td style="text-align:right"><?= e($fmtKol($e2['komada'])) ?></td>
                    <td style="text-align:right"><?= e($fmtKg($e2['kg'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($otprema['napomena'])): ?>
    <div class="otp-napomena ne-lomi"><strong>Napomena:</strong> <?= e($otprema['napomena']) ?></div>
<?php endif; ?>

<div class="otp-sitno">Navedene težine su okvirne (obračunate iz dimenzija elementa i gustine betona, ili ručno unesene u šifrarniku).</div>

<div class="otp-potpisi">
    <div class="otp-potpis">Otpremio<small>(skladištar)</small></div>
    <div class="otp-potpis">Vozač / prevoznik<small>(potpis i datum)</small></div>
    <div class="otp-potpis">Primio<small>(ovlaštena osoba, potpis i pečat)</small></div>
</div>

<div class="otp-sastavio">
    Dokument sastavio: <?= e($otprema['korisnicko_ime'] ?? '—') ?>
    <?php if (!empty($otprema['datum_kreiranja'])): ?>
        — <?= e(date('d.m.Y H:i', strtotime($otprema['datum_kreiranja']))) ?>
    <?php endif; ?>
</div>
