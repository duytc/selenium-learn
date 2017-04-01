<?php

namespace Tagcade\Service\Fetcher;


class PartnerParams implements PartnerParamInterface
{
    /**
     * @var String
     */
    protected $username;
    /**
     * @var String
     */
    protected $password;

    /**
     * @var String
     */
    protected $reportType;

    /**
     * @var \DateTime
     */
    protected $startDate;

    /**
     * @var \DateTime
     */
    protected $endDate;

    /**
     * @var $config
     */
    protected $config;

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     * @return $this
     */

    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return String
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param String $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return String
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param String $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     *
     * @return $this
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     *
     * @return $this
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return String
     */
    public function getReportType()
    {
        return $this->reportType;
    }

    /**
     * @param String $reportType
     * @return $this
     */
    public function setReportType($reportType)
    {
        $this->reportType = $reportType;

        return $this;
    }
}