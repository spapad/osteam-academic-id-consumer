<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

$autoloader = require __DIR__ . '/../vendor/autoload.php';

session_name('MineduOsteamApp');
session_start();

date_default_timezone_set('Europe/Athens');

// Instantiate the app
$settings_file = __DIR__ . '/../src/settings.php';
if (is_readable($settings_file)) {
    $settings = require($settings_file);
} else {
    $settings = [];
}
$app = new \Slim\App($settings);
$container = $app->getContainer();

$container['autoloader'] = $autoloader;
$autoloader->addPsr4('Gr\Gov\Minedu\Osteam\Slim\\', __DIR__ . '/../src/osteam');

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Run app
$app->run();
