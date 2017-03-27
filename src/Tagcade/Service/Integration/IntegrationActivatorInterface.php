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
     * @return bool
     */
    public function createExecutionJobForDataSource($dataSourceId, $customParams = null);
}