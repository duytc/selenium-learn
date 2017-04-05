<?php

namespace Tagcade\Service\Fetcher;

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
     * @return \DateTime
     */
    public function getEndDate();

    /**
     * @return mixed
     */
    public function getConfig();

    /**
     * @return int
     */
    public function getPublisherId();

    /**
     * @return string
     */
    public function getIntegrationCName();

    /**
     * @return int
     */
    public function getProcessId();

    /**
     * @return string
     */
    public function getAccount();
}