<?php

namespace Tagcade\Service\Integration;


use Pheanstalk\PheanstalkInterface;
use Tagcade\Service\Core\TagcadeRestClientInterface;

class IntegrationActivator implements IntegrationActivatorInterface
{
    /** @var TagcadeRestClientInterface */
    protected $restClient;

    /** @var PheanstalkInterface */
    protected $pheanstalk;

    /** @var string */
    protected $fetcherWorkerTube;

    /** @var int */
    protected $pheanstalkTTR;

    public function __construct(TagcadeRestClientInterface $restClient, PheanstalkInterface $pheanstalk, $fetcherWorkerTube, $pheanstalkTTR)
    {
        $this->restClient = $restClient;
        $this->pheanstalk = $pheanstalk;
        $this->fetcherWorkerTube = $fetcherWorkerTube;
        $this->pheanstalkTTR = $pheanstalkTTR;
    }

    /**
     * @inheritdoc
     */
    public function createExecutionJobs()
    {
        /* get all dataSource-integrations that to be executed, from ur api */
        $dataSourceIntegrations = $this->restClient->getIntegrationToBeExecuted();
        if (!is_array($dataSourceIntegrations)) {
            return false;
        }

        foreach ($dataSourceIntegrations as $dataSourceIntegration) {
            /* create new job for execution */
            $createJobResult = $this->createExecutionJob($dataSourceIntegration);

            if (!$createJobResult) {
                continue;
            }

            /* update last execution time */
            $this->updateLastExecutionTime($dataSourceIntegration);
        }

        return true;
    }

    /**
     * create execution job for dataSourceIntegration
     *
     * @param $dataSourceIntegration
     * @return bool
     */
    private function createExecutionJob($dataSourceIntegration)
    {
        $dataSourceId = $dataSourceIntegration['dataSource']['id'];
        $integrationCName = $dataSourceIntegration['integration']['canonicalName'];
        $type = $dataSourceIntegration['integration']['type'];
        $method = $dataSourceIntegration['integration']['method'];
        $params = $dataSourceIntegration['params'];

        $params = array_merge(['method' => $method], $params);

        /* create job */
        $job = new \stdClass();
        $job->dataSourceId = $dataSourceId;
        $job->integrationCName = $integrationCName;
        $job->type = $type;
        $job->params = $params;

        /** @var PheanstalkInterface $pheanstalk */
        $this->pheanstalk
            ->useTube($this->fetcherWorkerTube)
            ->put(
                $job,
                PheanstalkInterface::DEFAULT_PRIORITY,
                PheanstalkInterface::DEFAULT_DELAY,
                $this->pheanstalkTTR
            )
        ;

        return true;
    }

    /**
     * update Last Execution Time
     *
     * @param $dataSourceIntegration
     * @return mixed
     */
    private function updateLastExecutionTime($dataSourceIntegration)
    {
        $integrationCName = $dataSourceIntegration['integration']['canonicalName'];
        $currentTime = new \DateTime();

        return $this->restClient->updateLastExecutionTimeForIntegrationByCName($integrationCName, $currentTime);
    }
}