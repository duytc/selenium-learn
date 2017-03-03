<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class GetAllDataCommand
{
    /**
     * @var Parser
     */
    private $yaml;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Parser $yaml, LoggerInterface $logger)
    {
        $this->yaml = $yaml;
        $this->logger = $logger;
    }

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $config = $this->yaml->parse(file_get_contents($input->getOption('config-file')));

        $this->logger->info($config['username']);
    }
}