<?php

namespace Tagcade\Worker\Workers;


// responsible for doing the background tasks assigned by the manager
// all public methods on the class represent tasks that can be done

use stdClass;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\IntegrationManagerInterface;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class GetPartnerReportWorker
{
    /**
     * @var IntegrationManagerInterface
     */
    protected $fetcherManager;

    /**
     * GetPartnerReportWorker constructor.
     * @param IntegrationManagerInterface $fetcherManager
     */
    public function __construct(IntegrationManagerInterface $fetcherManager)
    {
        $this->fetcherManager = $fetcherManager;
    }

    /**
     * get Partner Report
     *
     * @param stdClass $params
     */
    public function getPartnerReport(stdClass $params)
    {
        /** @var ConfigInterface $config */
        $config = new Config($params->publisherId, $params->integrationCName, $params->dataSourceId, json_decode($params->params, true));

        /** @var IntegrationInterface $integration */
        $integration = $this->fetcherManager->getIntegration($config);

        $integration->run($config);
    }
}