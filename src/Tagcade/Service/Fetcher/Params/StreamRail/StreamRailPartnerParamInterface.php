<?php

namespace Tagcade\Service\Fetcher\Params\StreamRail;

interface StreamRailPartnerParamInterface
{
    /**
     * @return string
     */
    public function getPrimaryDimension();

    /**
     * @return string
     */
    public function getSecondaryDimension();
}