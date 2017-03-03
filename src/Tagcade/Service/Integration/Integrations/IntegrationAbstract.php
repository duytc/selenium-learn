<?php

namespace Tagcade\Service\Integration\Integrations;


use Tagcade\Service\Integration\ConfigInterface;

abstract class IntegrationAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = '';

    /**
     * @inheritdoc
     */
    public function supportsConfig(ConfigInterface $config): bool
    {
        return $config->getIntegrationCName() == $this->getIntegrationCName();
    }

    /**
     * @inheritdoc
     */
    public function getIntegrationCName() : string
    {
        return static::INTEGRATION_C_NAME;
    }
}