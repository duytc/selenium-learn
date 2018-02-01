<?php

namespace Tagcade\Service\Fetcher\Pages;

abstract class AbstractCedatoHomePage extends AbstractCedatoPage
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
     * @return mixed
     */
    public abstract function doLogout();

    /**
     * check if is logged in
     *
     * @return mixed
     */
    public abstract function isLoggedIn();
}