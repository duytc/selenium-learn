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
     * @param null|bool $isScheduleUpdated
     * @return bool
     */
    public function createExecutionJobForDataSourceWithSchedule($dataSourceId, $customParams = null, $isScheduleUpdated = false);

    /**
     * @param int $dataSourceId
     * @param null|array $customParams
     * @return bool
     */
    public function createExecutionJobForDataSourceWithoutSchedule($dataSourceId, $customParams = null);
}