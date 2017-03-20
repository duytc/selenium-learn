<?php

namespace tagcade\dev;
use AppKernel;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\IntegrationManagerInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

$loader = require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();

$configFile = dirname(__FILE__) . '/integration_config.json';

if (!file_exists($configFile)) {
    echo $configFile . " does not exist\n";
    exit(1);
}

$rawConfig = json_decode(file_get_contents($configFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "json config file has errors";
    exit(1);
}

$config = new Config($rawConfig['publisherId'], $rawConfig['integrationCName'], $rawConfig['dataSourceId'], $rawConfig['params'], $rawConfig['backFill']);

/** @var IntegrationManagerInterface $fetcherManager */
$fetcherManager = $container->get('tagcade.service.integration.integration_manager');

/** @var IntegrationInterface $integration */
$integration = $fetcherManager->getIntegration($config);

// problem: no monolog output will be shown in the terminal, it will only be logged in app/logs/.
// config_dev.yml defines 2 handlers, the console handler will output to the terminal but this dev script does
// not active the console handler
$integration->run($config);