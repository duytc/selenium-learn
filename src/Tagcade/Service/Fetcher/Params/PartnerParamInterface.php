<?php

namespace Tagcade\Service\Fetcher\Params;

interface PartnerParamInterface
{
    /**
     * @return String
     */
    public function getUsername();

    /**
     * @return String
     */
    public function getPassword();

    /**
     * @return \DateTime
     */
    public function getStartDate();

    /**
     * set startDate, special for modify current startDate directly
     *
     * @param \DateTime $startDate
     * @return self
     */
    public function setStartDate(\DateTime $startDate);

    /**
     * @return \DateTime
     */
    public function getEndDate();

    /**
     * set endDate, special for modify current endDate directly
     *
     * @param \DateTime $endDate
     * @return self
     */
    public function setEndDate(\DateTime $endDate);

    /**
     * @return boolean
     */
    public function isDailyBreakdown();

    /**
     * @return mixed
     */
    public function getConfig();

    /**
     * @param array $config
     * @return self
     */
    public function setConfig(array $config);

    /**
     * @return int
     */
    public function getPublisherId();

    /**
     * @return string
     */
    public function getIntegrationCName();

    /**
     * @return string
     */
    public function getDataSourceId();

    /**
     * @return int
     */
    public function getProcessId();
}