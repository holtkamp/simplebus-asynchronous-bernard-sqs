<?php
// DIC configuration
return array(
    __DIR__ . '/../vendor/php-di/slim-bridge/src/config.php',
    __DIR__ . '/../src/dic/Config.php',
    __DIR__ . '/../src/dic/Config.AmazonWebServices.php',
    __DIR__ . '/../src/dic/Config.SimpleBus.php',
    __DIR__ . '/../src/dic/Config.SimpleBus.CommandBus.php',
    __DIR__ . '/../src/dic/Config.SimpleBus.CommandBus.Asynchronous.php',
    __DIR__ . '/../src/dic/Config.SimpleBus.CommandBus.Synchronous.php',
);
