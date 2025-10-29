<?php

declare(strict_types=1);

use App\Application;
use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;

require __DIR__ . '/../vendor/autoload.php';

$repository = RepositoryBuilder::createWithDefaultAdapters()
    ->addAdapter(PutenvAdapter::class)
    ->immutable()
    ->make();

$_ENV = array_replace(
    $_ENV,
    Dotenv::create($repository, __DIR__ . '/../')->load()
);

(new Application())->run();
