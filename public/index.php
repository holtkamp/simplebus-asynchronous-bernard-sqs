<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

//Instantiate the PHP-DI DependencyInjectionContainer
$containerBuilder = new \DI\ContainerBuilder();
$configurationFiles = require __DIR__ . '/../src/configurationFiles.php';
foreach($configurationFiles as $configurationFile){
    $containerBuilder->addDefinitions($configurationFile);
}

// Use the DIC to instantiate the app
$app = new \Slim\App($containerBuilder->build());

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
