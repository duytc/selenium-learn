<?php

$loader = require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel('prod', false);
$kernel->boot();

$container = $kernel->getContainer();

$redLock = $container->get('tagcade.service.lock.red_lock');

$lockTTL = 60 * 60 * 24; // 1 day
$lockTTL *= 1000;

$lockService = new \Tagcade\Service\Lock\LockService($redLock, $container->getParameter('lock_key_prefix'), $lockTTL);

$cname = 'video-optimatic-internal-marketplace';

// use this when there is a big backlog of integrations and some integrations are known to cause error.
// We do this to clear the backlog, there we can debug the problem
$key = sprintf('integration-%s-lock', $cname);

echo "Locking $key\n";

$lockService->lock($key);