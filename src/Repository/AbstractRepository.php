<?php

namespace Silktide\Reposition\Repository;

use Silktide\Reposition\Exception\RepositoryException;
use Silktide\Reposition\Query\Query;
use Silktide\Reposition\QueryBuilder\QueryBuilderInterface;
use Silktide\Reposition\Storage\StorageInterface;

/**
 *
 */
abstract class AbstractRepository implements RepositoryInterface
{

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var QueryBuilderInterface
     */
    protected $queryBuilder;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @param string $entityName
     * @param QueryBuilderInterface $queryBuilder
     * @param StorageInterface $storage
     */
    public function __construct($entityName, QueryBuilderInterface $queryBuilder, StorageInterface $storage)
    {
        $this->entityName = $entityName;
        $this->queryBuilder = $queryBuilder;
        $this->storage = $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * {@inheritDoc}
     */
    public function find($id)
    {
        $query = $this->queryBuilder->find($this->tableName)
            ->where()
            ->ref(QueryBuilderInterface::PRIMARY_KEY)
            ->op("=")
            ->val($id);
        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(array $filters, array $sort = [], $limit = 0, array $options = [])
    {
        $query = $this->queryBuilder->find($this->tableName);

        $this->createWhereFromFilters($query, $filters);

        if (!empty($sort)) {
            $query->order($sort);
        }

        if (!empty($limit)) {
            $query->limit($limit);
        }

        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function save($entity, array $options = [])
    {
        $query = $this->queryBuilder->save($this->tableName)->entity($entity);
        return $this->doQuery($query, false);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        $query = $this->queryBuilder->delete($this->tableName)
            ->where()
            ->ref(QueryBuilderInterface::PRIMARY_KEY)
            ->op("=")
            ->val($id);
        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $conditions = [], array $groupBy = [])
    {
        $query = $this->queryBuilder->find($this->tableName)->aggregate("count", "*");

        $this->createWhereFromFilters($query, $conditions);

        if (!empty($groupBy)) {
            $query->group($groupBy);
        }

        return $this->doQuery($query, false);
    }

    /**
     * @param Query $query
     * @param bool $createEntity
     * @return object|array
     */
    protected function doQuery(TokenSequencerInterface $query, $createEntity = true)
    {
        return $this->storage->query($query, $createEntity? $this->entityName: "");
    }

    protected function createWhereFromFilters(TokenSequencerInterface $query, array $filters, $startWithWhere = true)
    {
        if (empty($filters)) {
            return;
        }

        if ($startWithWhere) {
            $query->where();
        }

        // we need to add "andL" to all but the last field, so
        // get the values for the last field and remove it from the array
        end($filters);
        $lastField = key($filters);
        $lastValue = array_pop($filters);
        reset($filters);

        // create filters
        for ($filters as $field => $value) {
            $query->ref($field)->op("=")->val($value)->andL();
        }
        // filter last field
        $query->ref($lastField)->op("=")->val($lastValue);
    }

} 