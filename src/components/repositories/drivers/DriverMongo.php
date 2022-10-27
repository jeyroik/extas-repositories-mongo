<?php
namespace extas\components\repositories\drivers;

use extas\components\THasConfig;
use extas\interfaces\IItem;
use League\Monga;
use League\Monga\Collection;

class DriverMongo extends Driver
{
    protected const FIELD__DSN = 'dsn';
    protected const FIELD__DB = 'db';
    protected const FIELD__TABLE = 'table';

    use THasConfig;
    use THasConfig {
        THasConfig::__construct as baseConstruct;
    }

    /**
     * @var Collection
     */
    protected ?Collection $collection = null;

    /**
     * ClientTableMongo constructor.
     *
     * @param $collection
     */
    public function __construct(array $config = [])
    {
        $this->baseConstruct($config);

        $connection = Monga::connection($this->getDsn());
        $db = $connection->database($this->getDbName());
        $this->collection = $db->collection($this->getTableName());
    }

    /**
     * @param array|Where $query
     * @param int $offset
     * @param array $fields
     *
     * @return array|IItem|null
     * @throws
     */
    public function findOne(array $query = [], int $offset = 0, array $fields = [])
    {
        $this->prepareQuery($query);
        $record = $this->collection->findOne(
            function($q) use ($query, $offset) {
                /**
                 * @var $q Find
                 */
                $q->skip($offset);
                foreach ($query as $fieldName => $fieldValue) {
                    $q->where($fieldName, $fieldValue);
                }
            },
            $fields
        );

        if ($record) {
            if ($this->getPk() != '_id') {
                unset($record['_id']);
            } else {
                $record['_id'] = (string)$record['_id'];
            }
            return $record;
        }

        return $record;
    }

    /**
     * @param array|Where $query
     * @param int $limit
     * @param int $offset
     * @param array $orderBy
     * @param array $fields
     *
     * @return array|IItem[]
     * @throws
     */
    public function findAll(array $query = [], int $limit = 0, int $offset = 0, array $orderBy = [], array $fields = [])
    {
        /**
         * @var $recordsCursor Cursor
         */
        $this->prepareQuery($query);
        $recordsCursor = $this->collection->find(
            function ($q) use ($query, $limit, $offset, $orderBy) {
                /**
                 * @var $q Find
                 */
                $limit && $q->limit($limit);
                $q->skip($offset);
                if (!empty($orderBy)) {
                    $q->orderBy(...$orderBy);
                }
                foreach ($query as $fieldName => $fieldValue) {
                    $q->where($fieldName, $fieldValue);
                }
            },
            $fields
        );
        $rawRecords = $recordsCursor->toArray();
        $records = [];

        foreach ($rawRecords as $record) {
            if ($this->getPk() != '_id') {
                unset($record['_id']);
            } else {
                $record['_id'] = (string)$record['_id'];
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * @param IItem $item
     *
     * @return IItem
     * @throws \Exception
     */
    public function insert($item)
    {
        $itemData = $item->__toArray();
        $id = $this->collection->insert($itemData);

        if ($id) {
            if ($this->getPk() == '_id') {
                $item['_id'] = $id;
            }
            return $item;
        }

        throw new \Exception('Can not insert a record');
    }

    /**
     * @param array $query
     * @param $data
     *
     * @return int
     * @throws
     */
    public function updateMany($query, $data)
    {
        if ($data instanceof IItem) {
            $data = $data->__toArray();
        }

        $result = $this->collection->update($data, $query);

        if (is_bool($result)) {
            return (int) $result;
        }

        return isset($result['n']) ? (int) $result['n'] : 0;
    }

    /**
     * @param IItem $item
     *
     * @return bool
     * @throws
     */
    public function update($item): bool
    {
        $pk = $this->getPk() == '_id' ? new \MongoId($item[$this->getPk()]) : $item[$this->getPk()];
        if (isset($item['_id'])) {
            unset($item['_id']);
        }

        $result = $this->collection->update($item->__toArray(), [$this->getPk() => $pk]);

        return is_bool($result)
            ? $result
            : (isset($result['n']) && ($result['n'] >= 1)
                ? true
                : false);
    }

    /**
     * @param array $query
     *
     * @return int
     * @throws
     */
    public function deleteMany($query)
    {
        if ($query instanceof IItem) {
            $query = $query->__toArray();
        }

        $this->prepareQuery($query);
        $result = $this->collection->remove($query);

        if (is_bool($result)) {
            return (int) $result;
        }

        return isset($result['n']) ? (int) $result['n'] : 0;
    }

    /**
     * @param IItem $item
     *
     * @return bool
     * @throws
     */
    public function delete($item): bool
    {
        if ($this->getPk() == '_id') {
            $item[$this->getPk()] = new \MongoId($item[$this->getPk()]);
        }

        $result = $this->collection->remove([$this->getPk() => $item[$this->getPk()]]);

        return is_bool($result)
            ? $result
            : (isset($result['n']) && ($result['n'] >= 1)
                ? true
                : false);
    }

    /**
     * @return bool
     */
    public function drop(): bool
    {
        $this->collection->drop();

        return true;
    }

    /**
     * @param array $groupBy
     *
     * @return $this
     * @throws
     */
    public function  group(array $groupBy)
    {
        $this->collection->aggregate(function ($a) use ($groupBy) {
            /**
             * @var $a Aggregation
             */
            $a->group(function ($g) use ($groupBy) {
                /**
                 * @var $g Group
                 */
                $g->by([$groupBy]);
            });
        });

        return $this;
    }

    public function getDbName(): string
    {
        return $this->config[static::FIELD__DB] ?? '';
    }

    public function getDsn(): string
    {
        return $this->config[static::FIELD__DSN] ?? '';
    }

    /**
     * @param array $query
     */
    protected function prepareQuery(&$query)
    {
        foreach ($query as $fieldName => $fieldValue) {
            if (is_array($fieldValue)) {
                $query[$fieldName] = ['$in' => $fieldValue];
            }
        }
    }
}
