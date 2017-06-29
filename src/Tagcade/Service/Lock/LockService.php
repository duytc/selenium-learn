<?php

namespace Tagcade\Service\Lock;

class LockService implements LockServiceInterface
{
    /**
     * @var RedLock
     */
    private $redLock;
    /**
     * @var string
     */
    private $lockKeyPrefix;
    /**
     * @var int
     */
    private $lockKeyTTL;

    /**
     * LockService constructor.
     * @param RedLock $redLock
     * @param string $lockKeyPrefix
     * @param int $lockKeyTTL in milliseconds
     */
    public function __construct(RedLock $redLock, $lockKeyPrefix = 'fetcher_ur_lock_', $lockKeyTTL = 30000)
    {
        $this->redLock = $redLock;
        $this->lockKeyPrefix = $lockKeyPrefix;
        $this->lockKeyTTL = $lockKeyTTL;
    }

    public function lock($key)
    {
        return $this->redLock->lock($this->lockKeyPrefix . (string) $key, $this->lockKeyTTL, [
            'pid' => getmypid()
        ]);
    }

    public function unlock($lock)
    {
        if (is_array($lock)) {
            $this->redLock->unlock($lock);
        }
    }
}