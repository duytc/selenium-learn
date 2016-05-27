<?php

namespace Tagcade\DataSource;

interface PartnerParamInterface {
    /**
     * @return String
     */
    public function getUsername();
    /**
     * @param String $username
     */
    public function setUsername($username);

    /**
     * @return String
     */
    public function getPassword();

    /**
     * @param String $password
     */
    public function setPassword($password);

    /**
     * @return \DateTime
     */
    public function getStartDate();

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate);

    /**
     * @return \DateTime
     */
    public function getEndDate();

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate);

    /**
     * @return mixed
     */
    public function getConfig();

    /**
     * @param mixed $config
     */
    public function setConfig($config);

}