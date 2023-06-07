<?php
namespace extas\components\repositories\drivers;

use extas\components\THasConfig;
use extas\interfaces\IItem;
use MongoDB\Collection;

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

        try{
            $driver = new \MongoDB\Client($this->getDsn());
            $dbName = $this->getDbName();
            $tableName = $this->getTableName();
            $this->collection = $driver->$dbName->$tableName;
        } catch (\Exception $e) {

        }
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

        $options = ['skip' => $offset];

        if (!empty($fields)) {
            $options['projection'] = $fields;
        }
        
        $record = $this->collection->findOne($query, $options);

        if ($record) {
            $record = $record->getArrayCopy();
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
        $options = ['skip' => $offset, 'limit' => $limit, 'sort' => $orderBy];

        if (!empty($fields)) {
            $options['projection'] = $fields;
        }

        $rawRecords = $this->collection->find($query, $fields);
        $records = [];

        foreach ($rawRecords as $record) {
            $record = $this->unSerializeItem($record);
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
        $result = $this->collection->insertOne($itemData);

        if ($result->getInsertedCount()) {
            if ($this->getPk() == '_id') {
                $item['_id'] = $result->getInsertedId();
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

        $result = $this->collection->updateMany($query, $this->prepareForUpdate($data));

        return $result->getModifiedCount();
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

        $result = $this->collection->updateOne([$this->getPk() => $pk], $this->prepareForUpdate($item->__toArray()));

        return $result->getModifiedCount() ? true : false;
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
        $result = $this->collection->deleteMany($query);

        return $result->getDeletedCount();
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

        $result = $this->collection->deleteOne([$this->getPk() => $item[$this->getPk()]]);

        return $result->getDeletedCount() ? true : false;
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
     * @param $item
     *
     * @return array
     */
    protected function prepareForUpdate($item)
    {
        return ['$set' => $item];
    }

    /**
     * @param $item
     *
     * @return array
     */
    protected function unSerializeItem($item)
    {
        $unSerialized = [];

        $item = (array) $item;

        foreach ($item as $field => $value) {
            if (is_object($value)) {
                $value = $this->unSerializeItem($value);
            }

            $unSerialized[$field] = $value;
        }

        return $unSerialized;
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
