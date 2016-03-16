<?php

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    'console.output' => DI\Object('Symfony\Component\Console\Output\ConsoleOutput'),
    'console.formatter' => DI\Object('Symfony\Bridge\Monolog\Formatter\ConsoleFormatter'),
    'console.handler' => DI\Object('Symfony\Bridge\Monolog\Handler\ConsoleHandler')
        ->constructor('setFormatter', DI\Get('console.output'))
        ->method('setFormatter', DI\get('console.formatter'))
        return (new \Symfony\Bridge\Monolog\Handler\ConsoleHandler($c->get('console.output')))
            ->setFormatter(new \Symfony\Bridge\Monolog\Formatter\ConsoleFormatter())
        ;
    },
    LoggerInterface::class => DI\object(\Monolog\Logger::class),
];