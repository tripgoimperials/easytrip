<?php
declare(strict_types=1);

require __DIR__ ."../src/";


use App\Core\Cors;
use App\Core\Request;
use App\Core\Router;

Cors::handle();

$router = new Router();

(require __DIR__ . '../src/Core/Router')($router);

$router->dispatch(Request::capture());
