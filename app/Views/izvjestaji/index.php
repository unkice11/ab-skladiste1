<h3 class="mb-4">Izvještaji</h3>

<div class="row g-3">
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/stanje')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-clipboard-data me-2"></i>Stanje skladišta</h6>
                <p class="text-muted small mb-0">Trenutna količina, rezervisano, slobodno i težina po elementu/lokaciji.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/po-lokaciji')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-geo-alt me-2"></i>Stanje po lokaciji</h6>
                <p class="text-muted small mb-0">Zbir komada i težine po lokaciji, svi elementi zajedno.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/po-investitoru')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-building me-2"></i>Stanje po investitoru</h6>
                <p class="text-muted small mb-0">Zbir količine i okvirne težine na stanju, grupisano po investitoru.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/ulazi')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-box-arrow-in-down me-2"></i>Ulaz po periodu</h6>
                <p class="text-muted small mb-0">Svi ulazi na skladište u izabranom periodu.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/otpreme')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-box-arrow-up me-2"></i>Otpreme po periodu</h6>
                <p class="text-muted small mb-0">Sve otpreme u izabranom periodu, sa ukupnom otpremljenom težinom.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/po-elementu')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-bricks me-2"></i>Kretanje po elementu</h6>
                <p class="text-muted small mb-0">Ulaz/otprema u periodu i trenutno stanje, po AB elementu.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/po-objektu')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-house me-2"></i>Otpremljeno po objektu</h6>
                <p class="text-muted small mb-0">Zbir otpremljenih komada/težine po objektu, u periodu.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/rezervisani')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-bookmark-check me-2"></i>Rezervisani elementi</h6>
                <p class="text-muted small mb-0">Sve trenutno aktivne rezervacije.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('izvjestaji/ukupna-tezina')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-graph-up me-2"></i>Ukupna otpremljena težina</h6>
                <p class="text-muted small mb-0">Pregled po mjesecima za izabranu godinu.</p>
            </div></div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(base_url('projekti')) ?>" class="text-decoration-none">
            <div class="card h-100"><div class="card-body">
                <h6><i class="bi bi-kanban me-2"></i>Realizacija po projektu</h6>
                <p class="text-muted small mb-0">Planirano/otpremljeno/preostalo — otvori "Analitika" na projektu.</p>
            </div></div>
        </a>
    </div>
</div>
