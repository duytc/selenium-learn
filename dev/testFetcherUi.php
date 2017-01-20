<?php
namespace tagcade\dev;
use AppKernel;
use stdClass;
use Tagcade\Service\Fetcher\ApiParameter;
use Tagcade\Service\Fetcher\Fetchers\UiFetcher;

$loader = require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();

$type = 'ui';
$publisherId = 2;
$integrationCName = 'across33';
$params = [
    'username' => 'admin',
    'password' => '123456',
    'startDate' => '2016-01-13',
    'endDate' => '2016-01-14'
];

$parameters = new ApiParameter($publisherId, $integrationCName, $params);

/** @var UiFetcher $uiFetcher */
$uiFetcher = $container->get('tagcade.service.fetcher.ui_fetcher');

if ($uiFetcher->supportType($type)) {
    $uiFetcher->execute($parameters);
}