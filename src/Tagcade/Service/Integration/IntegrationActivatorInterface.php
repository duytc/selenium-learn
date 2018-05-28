<?php

namespace Tagcade\Service\Integration;


interface IntegrationActivatorInterface
{
    /**
     * @return bool
     */
    public function createExecutionJobs();

    /**
     * create Execution Job ForDataSource, support custom params and force run without schedule check
     *
     * @param int $dataSourceId
     * @param null|array $customParams custom params, this will override the original params of datasourceintegration
     * @param bool $isForceRun force run even datasourceintegration has not yet reached schedule
     * @param null $startDate
     * @param null $endDate
     * @return bool
     */
    public function createExecutionJobForDataSource($dataSourceId, $customParams = null, $isForceRun = false, $startDate = null, $endDate = null);

    /**
     * create Execution Job ForDataSources by integrationId, force run is always true without schedule check
     *
     * @param int $integrationId
     * @param bool $isForceRun force run even datasourceintegration has not yet reached schedule
     * @param null $startDate
     * @param null $endDate
     * @return bool
     */
    public function createExecutionJobForDataSourcesByIntegration($integrationId, $isForceRun = true, $startDate = null, $endDate = null);
}