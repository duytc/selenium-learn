<?php

namespace Tagcade\Service\Integration\Integrations;

use Tagcade\Service\Integration\ConfigInterface;

interface IntegrationInterface
{
    /**
     * @param ConfigInterface $config
     * @return bool
     */
    public function supportsConfig(ConfigInterface $config): bool;

    /**
     * @return string
     */
    public function getIntegrationCName() : string;

    /**
     * @param ConfigInterface $config
     * @return void
     * @throws \Exception any exception when run.
     * When throw an exception, our fetcher will retry to run fetcher due to config max retries number and delay before retry.
     */
    public function run(ConfigInterface $config);
}