<?php
declare(strict_types = 1);

use Interop\Container\ContainerInterface;

return array(
    'CommandBus' => function (SimpleBus\Message\Handler\Resolver\MessageHandlerResolver $messageHandlerResolver, Psr\Log\LoggerInterface $logger) {
        return new SimpleBus\Message\Bus\Middleware\MessageBusSupportingMiddleware([
            new SimpleBus\Message\Logging\LoggingMiddleware($logger, Monolog\Logger::DEBUG),
            new SimpleBus\Message\Handler\DelegatesToMessageHandlerMiddleware($messageHandlerResolver)
        ]);
    },
    SimpleBus\Message\Handler\Resolver\MessageHandlerResolver::class => function (ContainerInterface $container) {
        return new SimpleBus\Message\Handler\Resolver\NameBasedMessageHandlerResolver(
            $commandNameResolver = $container->get(SimpleBus\Message\Name\MessageNameResolver::class),
            $commandHandlerMap = new SimpleBus\Message\CallableResolver\CallableMap(
                $container->get('CommandToHandlerMapping'),
                $container->get(SimpleBus\Message\CallableResolver\CallableResolver::class)
            )
        );
    },
);
