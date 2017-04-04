<?php

namespace Tagcade\Service\Integration\Integrations\General;

use Psr\Log\LoggerInterface;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\FileStorageServiceInterface;

class PubvantageAccount extends WebApi
{
    const INTEGRATION_C_NAME = 'general-pubvantage-account';

    /** @var TagcadeRestClientInterface */
    private $tagcadeRestClient;

    public function __construct(LoggerInterface $logger, FileStorageServiceInterface $fileStorage, TagcadeRestClientInterface $tagcadeRestClient)
    {
        parent::__construct($logger, $fileStorage);

        $this->tagcadeRestClient = $tagcadeRestClient;
    }

    /**
     * @inheritdoc
     */
    public function getHeader()
    {
        $token = $this->tagcadeRestClient->getToken();

        return ['Authentication: bearer' . $token];
    }
}