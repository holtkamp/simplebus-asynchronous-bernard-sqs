<?php
// DIC configuration
return array(
    'settings.displayErrorDetails' => true, //Overwrites default setting in PHP-DI-Slim-Bridge
    \Psr\Log\LoggerInterface::class => function (\Interop\Container\ContainerInterface $container) {
        $logger = new Monolog\Logger('App');
        $logger->pushProcessor(new Monolog\Processor\UidProcessor());
        $path = __DIR__ . '/../../logs/app.log';
        $logger->pushHandler(new Monolog\Handler\StreamHandler($path, \Monolog\Logger::DEBUG));
        return $logger;
    },
);
