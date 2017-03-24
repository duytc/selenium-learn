<?php

namespace Tagcade\Service\Integration;


interface IntegrationActivatorInterface
{
    /**
     * @return bool
     */
    public function createExecutionJobs();

    /**
     * @inheritdoc
     */
    public function createExecutionJobForDataSource($dataSourceId, array $params);
}