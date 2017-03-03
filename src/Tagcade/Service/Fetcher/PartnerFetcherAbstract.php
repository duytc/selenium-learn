<?php

namespace Tagcade\Service\Fetcher;

use Psr\Log\LoggerInterface;
use Tagcade\Service\DownloadFileHelperInterface;

abstract class PartnerFetcherAbstract implements PartnerFetcherInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /** @var DownloadFileHelperInterface */
    protected $downloadFileHelper;

    /**
     * PartnerFetcherAbstract constructor.
     * @param LoggerInterface $logger
     * @param DownloadFileHelperInterface $downloadFileHelper
     */
    public function __construct(LoggerInterface $logger, DownloadFileHelperInterface $downloadFileHelper)
    {
        $this->logger = $logger;
        $this->downloadFileHelper = $downloadFileHelper;
    }

    /**
     * @inheritdoc
     */
    public function getDownloadFileHelper()
    {
        return $this->downloadFileHelper;
    }

    /**
     * @inheritdoc
     */
    public function getLogger()
    {
        return $this->logger;
    }
}