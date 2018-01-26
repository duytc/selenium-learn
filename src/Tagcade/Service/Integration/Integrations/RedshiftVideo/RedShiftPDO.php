<?php

namespace Tagcade\Service\Integration\Integrations\RedshiftVideo;


class RedShiftPDO implements RedShiftPDOInterface
{
    const CONNECTION_TEMPLATE = '%s:dbname=%s;host=%s;port=%d';

    /** @var string */
    private $dbType;

    /** @var string */
    private $dbName;

    /** @var string */
    private $dbHost;

    /** @var string */
    private $dbPort;

    /** @var string */
    private $dbUserName;

    /** @var string */
    private $dbPassword;

    protected $pdo;

    /**
     * RedShiftPDO constructor.
     * @param $dbType
     * @param $dbName
     * @param $dbHost
     * @param $dbPort
     * @param $dbUserName
     * @param $dbPassword
     */
    public function __construct($dbType, $dbName, $dbHost, $dbPort, $dbUserName, $dbPassword)
    {
        $this->dbType = $dbType;
        $this->dbName = $dbName;
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
        $this->dbUserName = $dbUserName;
        $this->dbPassword = $dbPassword;
    }

    /**
     * @inheritdoc
     */
    public function getPdo()
    {
        if (!$this->pdo instanceof \PDO) {
            $dns = sprintf(self::CONNECTION_TEMPLATE, $this->dbType, $this->dbName, $this->dbHost, $this->dbPort);

            try {
                $this->pdo = new \PDO($dns, $this->dbUserName, $this->dbPassword);
            } catch (\Exception $e) {

            }
        }

        return $this->pdo;
    }
}