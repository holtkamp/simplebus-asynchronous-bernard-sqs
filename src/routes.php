<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

// Routes
$app->get('/', function (Request $request, Response $response, \Psr\Log\LoggerInterface $logger) {
    $logger->info("Slim-Skeleton Hello '/' route");
    $response->getBody()->write('Hello!');
    return $response;
});
$app->get('/list-queues', function (Request $request, Response $response, \Bernard\QueueFactory $queueFactory) {
    $response->getBody()->write('List Message Queues<br/>');
    foreach($queueFactory->all() as $name => $queue){
        $response->getBody()->write(sprintf('%s: %d messages <br/>', $name,  $queue->count()));
    }
    $response->getBody()->write('<a href="./publish-message" target="_blank">Now publish a message</a>');
    return $response;
});
$app->get('/publish-message', function (Request $request, Response $response, \Interop\Container\ContainerInterface $container) {
    $response->getBody()->write('<h1>Publishing Messages by posting a Command to the Asynchronous CommandBus</h1>');

    $commandBus = $container->get('AsynchronousCommandBus');
    $command = new stdClass();
    $command->payload = 'Some basic payload';
    $command->date = new \DateTime();
    $command->time = time();
    $commandBus->handle($command);

    $response->getBody()->write('<a href="./consume-messages" target="_blank">Now consume the messages</a>');

    return $response;
});
$app->get('/consume-messages', function (Request $request, Response $response, \Bernard\QueueFactory $queueFactory, \SimpleBus\BernardBundleBridge\BernardConsumer $consumer) {
    $queue = $queueFactory->create(stdClass::class); //By default, the name of the command class is used to assemble the queue name
    $response->getBody()->write(sprintf('<h1>Consume %d Messages from Message Queue "%s"</h1>', $queue->count(), (string) $queue));
    while($aSerializedEnvelope = $queue->dequeue()) {
        $consumer($aSerializedEnvelope->getMessage());
        $queue->acknowledge($aSerializedEnvelope);
        echo '<hr/>';
    }

    return $response;
});

