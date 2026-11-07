<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\BackupRepository;

class BackupController extends Controller
{
    public function prikazi(): void
    {
        $this->render('postavke/backup');
    }

    public function preuzmi(): void
    {
        $nazivFajla = 'ab_skladiste_backup_' . date('Y-m-d_His') . '.sql';

        header('Content-Type: application/sql; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nazivFajla . '"');
        header('Cache-Control: no-store');

        (new AuditRepository())->zabiljezi(Auth::korisnik()['id'], 'preuzimanje', 'backup', null, null);

        (new BackupRepository())->generisiDump(fopen('php://output', 'w'));
        exit;
    }
}
