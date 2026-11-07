<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\PodaciFirmeRepository;

class PodaciFirmeController extends Controller
{
    /** Dozvoljeni tipovi loga — sadržaj se provjerava (ne samo ekstenzija), radi sigurnosti uploada. */
    private const DOZVOLJENI_MIME = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];
    private const MAX_VELICINA_BAJTOVA = 2 * 1024 * 1024; // 2 MB

    public function prikazi(): void
    {
        $this->render('postavke/podaci_firme', [
            'firma' => (new PodaciFirmeRepository())->ucitaj(),
        ]);
    }

    public function sacuvaj(): void
    {
        $repo = new PodaciFirmeRepository();
        $staraVrijednost = $repo->ucitaj();

        $podaci = [
            'naziv'    => trim($_POST['naziv'] ?? ''),
            'adresa'   => trim($_POST['adresa'] ?? ''),
            'grad'     => trim($_POST['grad'] ?? ''),
            'jib'      => trim($_POST['jib'] ?? ''),
            'pdv_broj' => trim($_POST['pdv_broj'] ?? ''),
            'telefon'  => trim($_POST['telefon'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'web'      => trim($_POST['web'] ?? ''),
        ];

        $repo->sacuvaj($podaci);

        if (!empty($_FILES['logo']['name'])) {
            $greska = $this->obradiUploadLogotipa($_FILES['logo'], $repo);
            if ($greska !== null) {
                flash('greska', $greska);
                redirect('postavke/podaci-firme');
            }
        }

        (new AuditRepository())->zabiljezi(
            Auth::korisnik()['id'],
            'izmjena',
            'podaci_firme',
            'podaci_firme',
            null,
            $staraVrijednost,
            $podaci
        );

        flash('uspjeh', 'Podaci firme su sačuvani.');
        redirect('postavke/podaci-firme');
    }

    /** @return string|null poruka greške, ili null ako je upload uspio (ili nije bilo fajla) */
    private function obradiUploadLogotipa(array $fajl, PodaciFirmeRepository $repo): ?string
    {
        if ($fajl['error'] !== UPLOAD_ERR_OK) {
            return 'Greška prilikom uploada logotipa.';
        }
        if ($fajl['size'] > self::MAX_VELICINA_BAJTOVA) {
            return 'Logotip je prevelik (maksimalno 2 MB).';
        }

        // Provjera STVARNOG sadržaja fajla, ne samo ekstenzije iz imena.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fajl['tmp_name']);

        if (!isset(self::DOZVOLJENI_MIME[$mime])) {
            return 'Dozvoljeni formati logotipa su PNG, JPG i WEBP.';
        }

        $ekstenzija = self::DOZVOLJENI_MIME[$mime];
        $nazivFajla = 'logo.' . $ekstenzija;
        $odrediste = rtrim(BASE_PATH, '/') . '/public/uploads/logos/' . $nazivFajla;

        // Ukloni prethodni logo ako je bio drugog formata (npr. bio .png, sada .jpg).
        foreach (self::DOZVOLJENI_MIME as $ext) {
            $stariFajl = rtrim(BASE_PATH, '/') . '/public/uploads/logos/logo.' . $ext;
            if (is_file($stariFajl)) {
                @unlink($stariFajl);
            }
        }

        if (!move_uploaded_file($fajl['tmp_name'], $odrediste)) {
            return 'Nije moguće sačuvati logotip na server.';
        }

        // Putanja koja se koristi u <img src> — javno dostupna preko public/.
        $repo->sacuvajLogo('uploads/logos/' . $nazivFajla . '?v=' . time());

        return null;
    }
}
