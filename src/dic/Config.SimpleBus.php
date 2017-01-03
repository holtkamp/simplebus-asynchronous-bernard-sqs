<?php
declare(strict_types = 1);

use Interop\Container\ContainerInterface;

/**
 * Define dependencies that can be used by both a CommandBus and a EventBus.
 */
return array(
    SimpleBus\Message\Name\MessageNameResolver::class => function () {
        return new SimpleBus\Message\Name\ClassBasedNameResolver();
    },
    SimpleBus\Message\CallableResolver\CallableResolver::class => function (ContainerInterface $container) {
        $serviceLocatorCallable = function (string $serviceName) use ($container) {
            return $container->get($serviceName);
        };

        return new SimpleBus\Message\CallableResolver\ServiceLocatorAwareCallableResolver($serviceLocatorCallable);
    }
);
