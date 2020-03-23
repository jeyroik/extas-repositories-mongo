<?php
namespace extas\components\repositories\clients;

use extas\interfaces\IItem;
use extas\interfaces\repositories\clients\IClientTable;
use League\Monga\Collection;
use League\Monga\Cursor;
use League\Monga\Query\Aggregation;
use League\Monga\Query\Find;
use League\Monga\Query\Group;
use League\Monga\Query\Where;

/**
 * Class ClientTableMongo
 *
 * @package extas\components\repositories\clients
 * @author jeyroik@gmail.com
 */
class ClientTableMongo extends ClientTableAbstract implements IClientTable
{
    /**
     * @var Collection
     */
    protected ?Collection $collection = null;

    /**
     * ClientTableMongo constructor.
     *
     * @param $collection
     */
    public function __construct($collection)
    {
        $this->collection = $collection;
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
            $record['_id'] = (string) $record['_id'];
            $itemClass = $this->getItemClass();
            return new $itemClass($record);
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
        $itemClass = $this->getItemClass();
        $records = [];

        foreach ($rawRecords as $record) {
            $record['_id'] = (string) $record['_id'];
            $records[] = new $itemClass($record);
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
        $itemClass = get_class($item);

        $id = $this->collection->insert($itemData);
        if ($id) {
            if ($idAs = $this->getIdAs()) {
                $itemData[$idAs ?: '_id'] = (string) $id;
                $changedItem = new $itemClass($itemData);
                $this->update($changedItem);
                return $changedItem;
            } else {
                return $item;
            }
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

        $result = $this->collection->update([$this->getPk() => $pk], $item->__toArray());

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
