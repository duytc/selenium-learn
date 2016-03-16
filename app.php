<?php

use DI\ContainerBuilder;

require 'vendor/autoload.php';

$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions('./app/container.php');
$containerBuilder->useAutowiring(true);
$container = $containerBuilder->build();

$temp = $container->get('console.handler');

$temp2 = 1;

//$app = new Silly\Edition\PhpDi\Application($container);
//
//$app->command('pulsepoint:get-all-data [--config-file=]', '\Tagcade\DataSource\PulsePoint\Command\GetAllDataCommand');
//
//$app->run($input = null, $container->get('console.output'));