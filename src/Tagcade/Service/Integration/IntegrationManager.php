<?php


namespace Tagcade\Service\Integration;


use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class IntegrationManager implements IntegrationManagerInterface
{
    /**
     * @var IntegrationInterface[]
     */
    protected $integrations;

    /**
     * FetcherManager constructor.
     * @param array $integrations
     */
    public function __construct(array $integrations)
    {
        $this->integrations = [];

        foreach ($integrations as $integration) {
            if (!$integration instanceof IntegrationInterface) {
                continue;
            }

            $this->integrations[] = $integration;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIntegration(ConfigInterface $config)
    {
        /**
         * @var IntegrationInterface $integration
         */
        foreach ($this->integrations as $integration) {
            if ($integration->supportsConfig($config)) {
                return $integration;
            }
        }

        throw new \InvalidArgumentException(sprintf('Not found any integration support that config'));
    }
}