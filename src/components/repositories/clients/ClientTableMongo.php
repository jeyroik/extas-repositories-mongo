<?php
namespace extas\components\repositories\clients;

use extas\interfaces\IItem;
use extas\interfaces\repositories\clients\IClientTable;
use League\Monga\Collection;
use League\Monga\Cursor;
use League\Monga\Query\Aggregation;
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
     * @param array $fields
     *
     * @return array|IItem|null
     * @throws
     */
    public function findOne(array $query = [], array $fields = [])
    {
        $record = $this->collection->findOne($query, $fields);

        if ($record) {
            $record['_id'] = (string) $record['_id'];
            $itemClass = $this->getItemClass();
            return new $itemClass($record);
        }

        return $record;
    }

    /**
     * @param array|Where $query
     * @param array $fields
     *
     * @return array|IItem[]
     * @throws
     */
    public function findAll(array $query = [], array $fields = [])
    {
        /**
         * @var $recordsCursor Cursor
         */
        $recordsCursor = $this->collection->find($query, $fields);
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
     * @return \Exception|IItem|mixed
     * @throws
     */
    public function insert($item)
    {
        $itemData = $item->__toArray();
        $itemClass = get_class($item);

        $this->collection->insert($itemData);
        $idAs = $this->getIdAs();
        $itemData[$idAs ?: '_id'] = (string) $itemData['_id'];

        return new $itemClass($itemData);
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
}