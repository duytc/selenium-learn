<?php

namespace Tagcade\Service\Lock;

interface LockServiceInterface
{
    public function lock($key);

    public function unlock($lock);
}