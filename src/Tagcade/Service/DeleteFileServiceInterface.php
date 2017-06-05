<?php


namespace Tagcade\Service;

interface DeleteFileServiceInterface
{
    /**
     * @param $path
     */
    public function removeFileOrFolder($path);
}