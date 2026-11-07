<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class PodaciFirmeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::konekcija();
    }

    public function ucitaj(): array
    {
        $stmt = $this->db->query('SELECT * FROM podaci_firme WHERE id = 1 LIMIT 1');
        $red = $stmt->fetch();
        return $red ?: [
            'naziv' => null, 'adresa' => null, 'grad' => null, 'jib' => null,
            'pdv_broj' => null, 'telefon' => null, 'email' => null, 'web' => null, 'logo_putanja' => null,
        ];
    }

    public function sacuvaj(array $podaci): void
    {
        $stmt = $this->db->prepare(
            'UPDATE podaci_firme SET naziv = :naziv, adresa = :adresa, grad = :grad, jib = :jib,
                pdv_broj = :pdv_broj, telefon = :telefon, email = :email, web = :web
             WHERE id = 1'
        );
        $stmt->execute([
            'naziv'    => $podaci['naziv'] ?: null,
            'adresa'   => $podaci['adresa'] ?: null,
            'grad'     => $podaci['grad'] ?: null,
            'jib'      => $podaci['jib'] ?: null,
            'pdv_broj' => $podaci['pdv_broj'] ?: null,
            'telefon'  => $podaci['telefon'] ?: null,
            'email'    => $podaci['email'] ?: null,
            'web'      => $podaci['web'] ?: null,
        ]);
    }

    public function sacuvajLogo(string $putanja): void
    {
        $stmt = $this->db->prepare('UPDATE podaci_firme SET logo_putanja = :p WHERE id = 1');
        $stmt->execute(['p' => $putanja]);
    }
}
