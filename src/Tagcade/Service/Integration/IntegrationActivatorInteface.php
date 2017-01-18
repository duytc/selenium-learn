<?php

namespace Tagcade\Service\Integration;


interface IntegrationActivatorInterface
{
    /**
     * @return bool
     */
    public function createExecutionJobs();
}