<?php
namespace tagcade\dev;
use AppKernel;
use stdClass;

$loader = require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$queue = $container->get('leezy.pheanstalk');
const TUBE = 'fetcher-worker';


$params = new StdClass();
$params->publisherId = 1;
$params->type = 'api';
$params->cname = 'sovrn';
$params->param = '{"username": "admin", "password": "123456"}';
$payload = new StdClass;

$payload->task = 'getPartnerReport';
$payload->params = $params;

$queue
    ->useTube(TUBE)
    ->put(json_encode($payload))
;
