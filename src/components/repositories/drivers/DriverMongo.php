<?php
namespace extas\components\repositories\drivers;

use extas\components\repositories\clients\ClientMongo;

/**
 * Class DriverMongo
 *
 * @package extas\components\repositories\drivers
 * @author jeyroik@gmail.com
 */
class DriverMongo extends Driver
{
    protected string $clientClass = ClientMongo::class;
}
