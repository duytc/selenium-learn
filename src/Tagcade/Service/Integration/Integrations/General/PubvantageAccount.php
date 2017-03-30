<?php

namespace Tagcade\Service\Integration\Integrations\General;

class PubvantageAccount extends WebApi
{
    const INTEGRATION_C_NAME = 'general-pubvantage-account';

    const URL = 'http://pubvantage/account...';

    /**
     * @inheritdoc
     */
    public function getHeader()
    {
        $token = $this->getToken();

        return ['Authentication: bearer' . $token];
    }

    /**
     * @return string
     */
    private function getToken()
    {
        return '';
    }
}