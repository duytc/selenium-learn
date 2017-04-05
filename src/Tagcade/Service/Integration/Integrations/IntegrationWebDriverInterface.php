<?php

namespace Tagcade\Service\Integration\Integrations;

interface IntegrationWebDriverInterface extends IntegrationInterface
{
    public function createPartnerParams($config);
}