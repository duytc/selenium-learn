<?php

namespace Tagcade\Exception;


use DateTime;

class LoginFailException extends \Exception
{
    /** @var int */
    protected $publisherId;
    /** @var string */
    protected $integrationCName;
    /** @var int */
    protected $dataSourceId;
    /** @var DateTime */
    protected $startDate;
    /** @var DateTime */
    protected $endDate;
    /** @var DateTime */
    protected $executionDate;

    /**
     * LoginFailException constructor.
     * @param int $publisherId
     * @param string $integrationCName
     * @param int $dataSourceId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param DateTime $executionDate
     */
    public function __construct($publisherId, $integrationCName, $dataSourceId, DateTime $startDate, DateTime $endDate, DateTime $executionDate)
    {
        $this->publisherId = $publisherId;
        $this->integrationCName = $integrationCName;
        $this->dataSourceId = $dataSourceId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->executionDate = $executionDate;
    }

    /**
     * @return int
     */
    public function getPublisherId()
    {
        return $this->publisherId;
    }

    /**
     * @return string
     */
    public function getIntegrationCName()
    {
        return $this->integrationCName;
    }

    /**
     * @return int
     */
    public function getDataSourceId()
    {
        return $this->dataSourceId;
    }

    /**
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @return DateTime
     */
    public function getExecutionDate()
    {
        return $this->executionDate;
    }
}