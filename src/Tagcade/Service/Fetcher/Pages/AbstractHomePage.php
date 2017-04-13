<?php

namespace Tagcade\Service\Fetcher\Pages;

abstract class AbstractHomePage extends AbstractPage
{
    /**
     * do login
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public abstract function doLogin($username, $password);

    /**
     * check if is logged in
     *
     * @return mixed
     */
    public abstract function isLoggedIn();
}