<?php
declare(strict_types = 1);

return array(
    //Mapping of the Command class names to the Handler classes that should process the Commands
    'CommandToHandlerMapping' => array(
        stdClass::class => new class
        {
            public function handle(stdClass $command)
            {
                echo 'Simplest example of a CommandHandler handling command: ' . print_r($command, true);
            }
        },
        //Project\Domain\Command\SpecificCommand1::class => Project\Domain\Handler\SpecificHandler1::class
        //Project\Domain\Command\SpecificCommand2::class => Project\Domain\Handler\SpecificHandler2::class
        //Project\Domain\Command\SpecificCommandN::class => Project\Domain\Handler\SpecificHandlerN::class
    ),
);
