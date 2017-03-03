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
     */
    public function run(ConfigInterface $config);
}