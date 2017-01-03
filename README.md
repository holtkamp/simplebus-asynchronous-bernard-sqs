# SimpleBus Asynchronous Message Bus using Bernard and Amazon SQS

Demonstration on how to configure two [SimpleBus Message Buses](https://github.com/SimpleBus/MessageBus):

- [Asynchronous](http://simplebus.github.io/Asynchronous/): which serializes and persists all incoming Commands on a [Amazon SQS](https://aws.amazon.com/sqs/) Message Queue using [Bernard](http://bernard.readthedocs.io/).
- Synchronous: actually processes the Commands using CommandHandlers

## Install the Application

    composer install
    
## Configure the Application
Make sure the Amazon Web Services credentials are configured properly in 

    src/dic/Config.AmazonWebServices.php

## Start the Application

	composer start

### List Message Queues

    http://localhost:8080/list-queues

### Publish to an Asynchronous CommandBus => persist on Message Queue

    http://localhost:8080/publish-message
    
### Consume Message from Queue => repost on Synchronous CommandBus

    http://localhost:8080/consume-messages