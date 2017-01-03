<?php
declare(strict_types = 1);

use Interop\Container\ContainerInterface;
use function DI\get;
use function DI\object;

return array(
    'settings.amazonWebServices.credentials.key' => 'REPLACE_WITH_YOUR_AWS_KEY',
    'settings.amazonWebServices.credentials.secret' => 'REPLACE_WITH_YOUR_AWS_SECRET',
    'settings.amazonWebServices.region' => 'eu-west-1', //Or other AWS region identifier
    'settings.amazonWebServices.version' => 'latest',
    Aws\Credentials\Credentials::class => object()->constructor(get('settings.amazonWebServices.credentials.key'), get('settings.amazonWebServices.credentials.secret')),
    Aws\Sqs\SqsClient::class => function (ContainerInterface $container) {
        return new Aws\Sqs\SqsClient([
                'credentials' => $container->get(Aws\Credentials\Credentials::class),
                'region' => $container->get('settings.amazonWebServices.region'),
                'version' => $container->get('settings.amazonWebServices.version'),
            ]
        );
    },
);
