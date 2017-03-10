<?php

$settings = $app->getContainer()->get('settings');
$username = isset($settings['app']['secure_endpoint_username']) ? $settings['app']['secure_endpoint_username'] : '';
$password = isset($settings['app']['secure_endpoint_password']) ? $settings['app']['secure_endpoint_password'] : '';

foreach (['queryID', 'queryIDnoCD', 'student', 'testServiceStatusp', 'testServiceStatusq'] as $r) {
    $app->getContainer()->get('router')
        ->getNamedRoute($r)
        ->add(new Gr\Gov\Minedu\Osteam\Slim\AuthorizationGuard($username, $password));
}
