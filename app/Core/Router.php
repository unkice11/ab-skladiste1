<?php

namespace App\Core;

/**
 * Jednostavan ruter za plain PHP aplikaciju.
 * Rute se definišu u routes/web.php preko $router->get()/post().
 *
 * Svaka ruta može imati opcioni "permisija" kod — ako je postavljen,
 * ruter prije poziva kontrolera provjerava (server-side!) da li
 * trenutni korisnik ima pravo pristupa toj permisiji.
 */
class Router
{
    private array $rute = [];

    public function get(string $putanja, callable $handler, ?string $permisija = null): void
    {
        $this->dodaj('GET', $putanja, $handler, $permisija);
    }

    public function post(string $putanja, callable $handler, ?string $permisija = null): void
    {
        $this->dodaj('POST', $putanja, $handler, $permisija);
    }

    private function dodaj(string $metoda, string $putanja, callable $handler, ?string $permisija): void
    {
        $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', trim($putanja, '/'));
        $this->rute[] = [
            'metoda'    => $metoda,
            'regex'     => '#^' . $regex . '$#',
            'handler'   => $handler,
            'permisija' => $permisija,
        ];
    }

    public function pokreni(): void
    {
        $metoda = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $skriptaDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($skriptaDir !== '' && str_starts_with($uri, $skriptaDir)) {
            $uri = substr($uri, strlen($skriptaDir));
        }
        $putanja = trim($uri, '/');
        if ($putanja === '') {
            $putanja = 'dashboard';
        }

        foreach ($this->rute as $ruta) {
            if ($ruta['metoda'] !== $metoda) {
                continue;
            }
            if (preg_match($ruta['regex'], $putanja, $parametri)) {
                array_shift($parametri);

                if ($ruta['permisija'] !== null && !Auth::jePrijavljen()) {
                    redirect('prijava');
                }

                if ($ruta['permisija'] !== null && !Auth::imaPristup($ruta['permisija'])) {
                    http_response_code(403);
                    require BASE_PATH . '/app/Views/errors/403.php';
                    return;
                }

                if ($metoda === 'POST' && !csrf_provjeri()) {
                    http_response_code(419);
                    require BASE_PATH . '/app/Views/errors/419.php';
                    return;
                }

                call_user_func_array($ruta['handler'], $parametri);
                return;
            }
        }

        http_response_code(404);
        require BASE_PATH . '/app/Views/errors/404.php';
    }
}
