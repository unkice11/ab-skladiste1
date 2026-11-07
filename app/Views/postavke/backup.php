<h3 class="mb-4">Backup baze podataka</h3>

<div class="alert alert-warning" style="max-width:700px;">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Ova funkcija generiše potpuni SQL dump cijele baze (sve tabele i svi podaci) i nudi ga za
    preuzimanje kao <code>.sql</code> fajl. Za veće baze generisanje može potrajati nekoliko
    sekundi — sačekaj da se preuzimanje završi.
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <p>Preporučeno: čuvati backup fajlove van servera (lokalni disk, eksterni storage), sa
        jasnim datumom u nazivu — naziv fajla već sadrži datum i vrijeme generisanja.</p>
        <a href="<?= e(base_url('postavke/backup/preuzmi')) ?>" class="btn btn-dark">
            <i class="bi bi-download"></i> Preuzmi backup sada
        </a>
    </div>
</div>
