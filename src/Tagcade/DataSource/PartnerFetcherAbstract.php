<?php

namespace Tagcade\DataSource;

use Psr\Log\LoggerInterface;
use Tagcade\Service\DownloadFileHelperInterface;

abstract class PartnerFetcherAbstract implements PartnerFetcherInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $name;

    /** @var DownloadFileHelperInterface */
    protected $downloadFileHelper;

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
    public function setDownloadFileHelper(DownloadFileHelperInterface $downloadFileHelper)
    {
        $this->downloadFileHelper = $downloadFileHelper;
    }

    /**
     * @inheritdoc
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}