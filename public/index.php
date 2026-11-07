<?php

require dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\Router;

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
$router->pokreni();
