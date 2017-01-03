<?php
declare(strict_types = 1);

use Interop\Container\ContainerInterface;
use function DI\get;
use function DI\object;

return array(
    /**
     * By using a separate Asynchronous CommandBus, we can publish Commands on a remote / persistent queue (Amazon SQS).
     * Worker processes can then periodically fetch the messages from the queue and have the 'normal' Synchronous CommandBus
     * process them.
     */
    'AsynchronousCommandBus' => function (SimpleBus\BernardBundleBridge\BernardPublisher $publisher, \Psr\Log\LoggerInterface $logger) {
        return new SimpleBus\Message\Bus\Middleware\MessageBusSupportingMiddleware([
            new SimpleBus\Message\Logging\LoggingMiddleware($logger, Monolog\Logger::DEBUG),
            new SimpleBus\Asynchronous\MessageBus\AlwaysPublishesMessages($publisher )//Use "Bernard" to publish (all) Commands on a persistent queue using Amazon Simple Queue Service (SQS)
        ]);
    },
    Bernard\Driver\SqsDriver::class => object()->constructor(get(Aws\Sqs\SqsClient::class)),
    Bernard\QueueFactory::class => function (Bernard\Driver\SqsDriver $sqsDriver) {
        return new Bernard\QueueFactory\PersistentFactory($sqsDriver, new Bernard\Serializer());
    },
    Bernard\Producer::class => object()->constructor(
        $queueFactory = get(Bernard\QueueFactory::class),
        $eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher()
    ),
    SimpleBus\BernardBundleBridge\BernardConsumer::class => object()->constructor(
        get(SimpleBus\Asynchronous\Consumer\StandardSerializedEnvelopeConsumer::class)
    ),
    SimpleBus\BernardBundleBridge\BernardPublisher::class => object()->constructor(
        $serializer = get(SimpleBus\Serialization\Envelope\Serializer\StandardMessageInEnvelopeSerializer::class),
        $producer = get(Bernard\Producer::class),
        $queueResolver = get(SimpleBus\Asynchronous\Routing\RoutingKeyResolver::class),
        $type = 'Command' //TODO: what significance does this have? Where is it used?
    ),
    SimpleBus\Asynchronous\Routing\RoutingKeyResolver::class => function (ContainerInterface $container) {
        //return $queueResolver = new SimpleBus\Asynchronous\Routing\ClassBasedRoutingKeyResolver();
        return new class implements SimpleBus\Asynchronous\Routing\RoutingKeyResolver
        {
            public function resolveRoutingKeyFor($message)
            {
                return str_replace(
                        '\\',
                        '-',
                        is_object($message) ? get_class($message) : $message
                    ); //Use own QueueResolver since the default uses dots to separate classname components, which are not allowed in Amazon SQS
            }
        };
    },
    SimpleBus\Asynchronous\Consumer\StandardSerializedEnvelopeConsumer::class => object()->constructor(
        get(SimpleBus\Serialization\Envelope\Serializer\StandardMessageInEnvelopeSerializer::class),
        get('CommandBus') //Re-post the deserialized Command on the normal (Synchronous) CommandBus
    ),
    SimpleBus\Serialization\Envelope\Serializer\StandardMessageInEnvelopeSerializer::class => object()->constructor(
        $envelopeFactory = new SimpleBus\Serialization\Envelope\DefaultEnvelopeFactory(),
        $objectSerializer = new SimpleBus\Serialization\NativeObjectSerializer()
    ),
);
