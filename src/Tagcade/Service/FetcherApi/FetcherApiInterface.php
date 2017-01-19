<?php

namespace Tagcade\Service\FetcherApi;

use Tagcade\Service\FetcherInterface;

interface FetcherApiInterface extends FetcherInterface
{
    /**
     * @param ApiParameterInterface $apiParameter
     */
    public function supportIntegration(ApiParameterInterface $apiParameter);

}