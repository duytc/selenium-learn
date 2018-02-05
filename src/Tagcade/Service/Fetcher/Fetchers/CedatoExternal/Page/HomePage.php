<?php

namespace Tagcade\Service\Fetcher\Fetchers\CedatoExternal\Page;

class HomePage extends \Tagcade\Service\Fetcher\Fetchers\CedatoInternal\Page\HomePage
{
    const URL = 'https://publisher.cedato.com/#!/login';
    const LOG_OUT_URL = 'https://dashboard.cedato.com/#/login';

    /**
     * @param string $username
     * @param string $password
     * @return bool|mixed
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function doLogin($username, $password)
    {
        return parent::doLogin($username, $password);
    }

}