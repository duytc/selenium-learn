<?php

namespace Tagcade\Service\Integration;


interface IntegrationActivatorInterface
{
    /**
     * @return bool
     */
    public function createExecutionJobs();

    /**
     * @param int $dataSourceId
     * @param null|array $customParams
     * @param null|bool $isForce
     * @param null|bool $isScheduleUpdated
     * @return bool
     */
    public function createExecutionJobForDataSource($dataSourceId, $customParams = null, $isForce = false, $isScheduleUpdated = false);
}