<?php

namespace Tagcade\DataSource\PulsePoint;

use DateTime;

class TaskParams {
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
    protected $emailAddress;
    /**
     * @var DateTime
     */
    protected $reportDate;
    /**
     * @var Boolean
     */
    protected $receiveReportsByEmail = true;

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
        $this->username = (string) $username;

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
        $this->password = (string) $password;

        return $this;
    }

    /**
     * @return String
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param String $emailAddress
     * @return $this
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = (string) $emailAddress;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getReportDate()
    {
        return $this->reportDate;
    }

    /**
     * @param DateTime $reportDate
     * @return $this
     */
    public function setReportDate(DateTime $reportDate)
    {
        $this->reportDate = $reportDate;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getReceiveReportsByEmail()
    {
        return $this->receiveReportsByEmail;
    }

    /**
     * @param boolean $receiveReportsByEmail
     * @return $this
     */
    public function setReceiveReportsByEmail($receiveReportsByEmail)
    {
        $this->receiveReportsByEmail = (bool) $receiveReportsByEmail;

        return $this;
    }
}