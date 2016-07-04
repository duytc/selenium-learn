<?php

namespace Tagcade\Service\Core;
interface TagcadeRestClientInterface {
    /**
     * @param bool $force
     * @return mixed
     */
    public function getToken($force = false);

    public function getPartnerConfigurationForAllPublishers($partnerCName, $publisherId);
}