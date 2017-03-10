<?php

$app->get('/queryID', '\Gr\Gov\Minedu\Osteam\Slim\App:queryID')
    ->setName('queryID');

$app->get('/queryIDnoCD', '\Gr\Gov\Minedu\Osteam\Slim\App:queryIDnoCD')
    ->setName('queryIDnoCD');

$app->get('/student/{identity}', '\Gr\Gov\Minedu\Osteam\Slim\App:student')
    ->setName('student');

$app->get('/testServiceStatus/[{identity}]', '\Gr\Gov\Minedu\Osteam\Slim\App:testServiceStatus')
    ->setName('testServiceStatusp');

$app->get('/testServiceStatus', '\Gr\Gov\Minedu\Osteam\Slim\App:testServiceStatus')
    ->setName('testServiceStatusq');

$app->any('/[{anythingelse}]', function ($request, $response, $args) {
    $this->logger->info("Void response, no action route was enabled");
    return $response->withJson([
        'message' => 'Your request is not valid',
        'in' => var_export($args, true)
    ], 404);
});
