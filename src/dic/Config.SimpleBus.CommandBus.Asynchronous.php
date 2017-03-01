<?php
declare(strict_types = 1);

use Interop\Container\ContainerInterface;
use function DI\get;
use function DI\object;

return array(
    SimpleBus\Asynchronous\Publisher\Publisher::class => get(SimpleBus\BernardBundleBridge\BernardPublisher::class),
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
    Bernard\Driver::class => get(Bernard\Driver\SqsDriver::class), //This is where we choose which concrete implementation of the Driver interface will be used
    Bernard\Driver\SqsDriver::class => object()->constructor(get(Aws\Sqs\SqsClient::class)),
    Bernard\QueueFactory::class => get(Bernard\QueueFactory\PersistentFactory::class),
    Bernard\QueueFactory\PersistentFactory::class => object()->constructor(
        $driver = get(Bernard\Driver::class),
        $serializer = get(Bernard\Serializer::class)
    ),
    Bernard\Serializer::class => object()->lazy(),
    Bernard\Producer::class => object()->constructor(
        $queueFactory = get(Bernard\QueueFactory::class),
        $eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher()
    ),
    SimpleBus\BernardBundleBridge\BernardConsumer::class => object()->constructor(
        get(SimpleBus\Asynchronous\Consumer\MessageInEnvelopSerializer::class)
    ),
    SimpleBus\BernardBundleBridge\BernardPublisher::class => object()->constructor(
        $serializer = get(SimpleBus\Serialization\Envelope\Serializer\MessageInEnvelopSerializer::class),
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
                $queueName = CommonUtils::getApplicationEnvironment() . '-' . str_replace(
                        '\\',
                        '-',
                        is_object($message) ? get_class($message) : $message
                    ); //Use own QueueResolver since the default uses dots to separate classname components, which are not allowed in Amazon SQS

                return substr($queueName, 0, 80); //Max length of 80 characters for a queue applies on SQS
            }
        };
    },
    SimpleBus\Asynchronous\Consumer\SerializedEnvelopeConsumer::class => get(SimpleBus\Asynchronous\Consumer\StandardSerializedEnvelopeConsumer::class),
    SimpleBus\Asynchronous\Consumer\StandardSerializedEnvelopeConsumer::class => object()->constructor(
        get(SimpleBus\Serialization\Envelope\Serializer\MessageInEnvelopSerializer::class),
        get('CommandBus') //Re-post the deserialized Command on the normal (Synchronous) CommandBus
    ),
    SimpleBus\Serialization\Envelope\Serializer\MessageInEnvelopSerializer::class => get(SimpleBus\Serialization\Envelope\Serializer\StandardMessageInEnvelopeSerializer::class),
    SimpleBus\Serialization\Envelope\Serializer\StandardMessageInEnvelopeSerializer::class => object()->constructor(
        $envelopeFactory = new SimpleBus\Serialization\Envelope\DefaultEnvelopeFactory(),
        $objectSerializer = new SimpleBus\Serialization\NativeObjectSerializer()
    ),
);
