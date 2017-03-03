<?php
namespace tagcade\dev;
use Tagcade\Service\Integration\Config;
use AppKernel;

$loader = require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->boot();

//http://api.tagcade.dev/app_dev.php/api/reports/v1/performancereports/platform?endDate=2017-01-18&group=true&startDate=2017-01-13

$container = $kernel->getContainer();

$tagcadeClientFetcher = $container->get('tagcade.service.fetcher.fetchers.api_fetcher');

$publisherId = 2;
$integrationCName = 'tagcade';
$dataSourceId = 3;
$param = [
			"username" => "tcadmin",
			"password" => "123456",
			'startDate'=>'2017-01-10',
			'endDate' =>'2017-01-19',
			//'url'=>'http://api.tagcade.dev/app_dev.php/api/reports/v1/performancereports/platform',
			'group' =>'true'
		];

$param = new Config($publisherId, $integrationCName, $dataSourceId, $param);
$tagcadeClientFetcher->execute($param);
