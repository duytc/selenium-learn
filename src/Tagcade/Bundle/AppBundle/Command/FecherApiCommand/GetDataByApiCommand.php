<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetDataByApiCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
			->setName('ur:unified-report-fetcher:get-data')
			->addOption(
				'publisher',
				'p',
				InputOption::VALUE_OPTIONAL,
				'fetcher for a publisher'
			)
			->addOption(
				'integration-cname',
				'i',
				InputOption::VALUE_OPTIONAL,
				'fetcher for integration cname'
			)
			->addOption(
				'method',
				'm',
				InputOption::VALUE_OPTIONAL,
				'Start date (YYYY-MM-DD) to get report.'
			)
			->addOption(
				'url',
				'u',
				InputOption::VALUE_OPTIONAL,
				'URL to get report.'
			)
			->addOption(
				'parameter',
				null,
				InputOption::VALUE_OPTIONAL,
				'Parameters use to get data'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

	}

	protected function validateInputOptions()
	{

	}

	protected function buildQuery()
	{

	}

	protected function buildPathToStoreDownloadData($publisherId, $integrationCName, $runningDate, $startDate, $endDate, $processId)
	{

	}

}