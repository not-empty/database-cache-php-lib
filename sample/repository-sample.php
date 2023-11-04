<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DatabaseCache\Repository;

$redisConfig = [
    'host' => 'localhost',
    'port' => 6379,
];

$repository = new Repository($redisConfig);
$data = [
    'name' => 'gabriel',
    'email' => 'testegabs@teste.com',
];
$repository->setQuery(
    'table:id',
    json_encode($data)
);

$getData = $repository->getQuery('table:id');

print_r($getData);
echo PHP_EOL;
