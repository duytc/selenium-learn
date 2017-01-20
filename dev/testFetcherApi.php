<?php
namespace tagcade\dev;
use Tagcade\Service\Fetcher\ApiParameter;
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
$param = [
			"username" => "tcadmin",
			"password" => "123456",
			'startDate'=>'2017-01-10',
			'endDate' =>'2017-01-19',
			'method'=>'GET',
			'url'=>'http://api.tagcade.dev/app_dev.php/api/reports/v1/performancereports/platform',
			'group' =>'true'
		];

$param = new ApiParameter($publisherId, $integrationCName, $param);
$tagcadeClientFetcher->doGetData($param);
