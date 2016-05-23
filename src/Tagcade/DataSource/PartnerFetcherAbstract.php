<?php

namespace Tagcade\DataSource;

use Psr\Log\LoggerInterface;

abstract class PartnerFetcherAbstract {

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $name;

    protected $downloadFileHelper;

    /**
     * @return mixed
     */
    public function getDownloadFileHelper()
    {
        return $this->downloadFileHelper;
    }

    /**
     * @param mixed $downloadFileHelper
     */
    public function setDownloadFileHelper($downloadFileHelper)
    {
        $this->downloadFileHelper = $downloadFileHelper;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
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