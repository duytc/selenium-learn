<?php

namespace Tagcade\DataSource;


use Facebook\WebDriver\Remote\RemoteWebDriver;

interface PartnerFetcherInterface {

    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param mixed $name
     */
    public function setName($name);
}