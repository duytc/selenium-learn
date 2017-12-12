<?php

namespace Tagcade\Service\Fetcher\Params\Lkqd;

interface LkqdPartnerParamInterface
{
    /**
     * @return String
     */
    public function getTimeZone();

    /**
     * @return mixed
     */
    public function getDimensions();

    /**
     * @return mixed
     */
    public function getMetrics();
}