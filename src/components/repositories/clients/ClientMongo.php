<?php
namespace extas\components\repositories\clients;

use extas\components\repositories\clients\databases\ClientDatabaseMongo;
use League\Monga;

/**
 * Class ClientMongo
 *
 * @package extas\components\repositories\clients
 * @author jeyroik@gmail.com
 */
class ClientMongo extends Client
{
    protected static array $instances = [];

    protected string $clientName = 'mongodb';

    /**
     * @param $dbName
     *
     * @return mixed
     * @throws
     */
    public function getDb($dbName)
    {
        $key = $this->dsn . '.' . $dbName;

        return isset(static::$instances[$key])
            ? static::$instances[$key]
            : static::$instances[$key] = new ClientDatabaseMongo(Monga::connection($this->dsn), $dbName);
    }
}
