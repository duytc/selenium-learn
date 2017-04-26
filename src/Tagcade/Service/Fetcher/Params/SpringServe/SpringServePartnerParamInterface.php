<?php

namespace Tagcade\Service\Fetcher\Params\SpringServe;

interface SpringServePartnerParamInterface
{
    /**
     * @return string
     */
    public function getAccount();

    /**
     * @return string
     */
    public function getTimeZone();

    /**
     * @return string
     */
    public function getInterval();

    /**
     * @return array
     */
    public function getDimensions();
}