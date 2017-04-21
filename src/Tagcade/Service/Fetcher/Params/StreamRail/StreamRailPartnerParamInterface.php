<?php

namespace Tagcade\Service\Fetcher\Params\StreamRail;

interface StreamRailPartnerParamInterface
{
    /**
     * @return string
     */
    public function getFirstDimension();

    /**
     * @return string
     */
    public function getSecondDimension();
}