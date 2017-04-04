<?php

namespace Tagcade\Service\Integration\Integrations\General;

use Psr\Log\LoggerInterface;
use RestClient\CurlRestClient;
use Tagcade\Service\FileStorageServiceInterface;

class MyCustomApi extends WebApi
{
    const INTEGRATION_C_NAME = 'general-my-custom-api';

    public function __construct(LoggerInterface $logger, FileStorageServiceInterface $fileStorage)
    {
        parent::__construct($logger, $fileStorage);
    }

    /**
     * @inheritdoc
     */
    public function getHeader()
    {
        $token = $this->getToken();

        return ['Authentication: mycustom-auth' . $token];
    }

    /**
     * get Token
     *
     * @return mixed|null
     * @throws \Exception
     */
    private function getToken()
    {
        $curl = new CurlRestClient();

        $this->logger->info('Trying to get token');

        $data = array('username' => $this->username, 'password' => $this->password);
        $tokenResponse = $curl->executeQuery($this->authUrl, 'POST', array(), $data);
        $curl->close();
        $token = json_decode($tokenResponse, true);

        if (empty($token)) {
            $this->logger->error(sprintf('Cannot get token with returned message: %s', $tokenResponse));

            return null;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('json decoding for token error');
        }
        if (!array_key_exists('token', $token)) {
            throw new \Exception(sprintf('Could not authenticate user %s', $this->username));
        }

        $token = $token['token'];

        $this->logger->info(sprintf('Got token %s', $token));

        return $token;
    }
}