<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowRedisKeysOfFetcherCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'ur:redis:fetcher-keys:show';
    const DATA_SOURCES = 'data-sources';

    /** @var  SymfonyStyle */
    private $io;

    /** @var  \Redis */
    private $redis;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Show all redis fetcher keys');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->redis = $container->get('redis.app_cache');
        $this->io = new SymfonyStyle($input, $output);

        $allKeys = $this->redis->keys("*");

        if (count($allKeys) < 1) {
            $this->io->newLine(2);
            $this->io->write("There is no redis keys.");
            return;
        }

        $this->io->write(sprintf("There are %d redis keys.\n", count($allKeys)));
        $this->showKeys($allKeys);
    }

    /**
     * @param $keys
     */
    private function showKeys($keys)
    {
        $keys = is_array($keys) ? $keys : [$keys];

        foreach ($keys as $key) {
            $this->io->note(sprintf("\tKey:\t%s\n", $key));
        }
    }
}
