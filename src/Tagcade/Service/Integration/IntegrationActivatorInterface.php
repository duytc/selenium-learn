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
     * @param bool $isUpdateNextExecute update the nextExecuteAt schedule for datasourceintegration
     * @return bool
     */
    public function createExecutionJobForDataSource($dataSourceId, $customParams = null, $isForceRun = false, $isUpdateNextExecute = false);
}