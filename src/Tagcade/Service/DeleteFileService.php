<?php

namespace Tagcade\Service;


use Exception;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

class DeleteFileService implements DeleteFileServiceInterface
{
    private $logger;

    /**
     * DeleteFileService constructor.
     * @param $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @inheritdoc
     */
    public function removeFileOrFolder($path)
    {
        if (!is_file($path) && !is_dir($path)) {
            return;
        }

        $fs = new Filesystem();

        $fs->chmod($path, 0777, 0000, true);

        if (!is_writable($path)) {
            return;
        }

        try {
            $fs->remove($path);
        } catch (\Exception $e) {
//            $this->logger->notice($e);
        }
    }
}