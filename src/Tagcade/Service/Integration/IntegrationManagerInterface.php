<?php


namespace Tagcade\Service\Integration;


use Tagcade\Service\Integration\Integrations\IntegrationInterface;

interface IntegrationManagerInterface
{
    /**
     * @param ConfigInterface $config
     * @return IntegrationInterface
     */
    public function getIntegration(ConfigInterface $config);
}