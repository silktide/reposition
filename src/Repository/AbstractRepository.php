<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
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
        $query = $this->queryBuilder->findById($this->tableName, $id);
        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(array $conditions, array $sort = [], $limit = 0, array $options = [])
    {
        $query = $this->queryBuilder->findBy($this->tableName, $conditions, $sort, $limit);
        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function insert($entity, array $options = [])
    {
        $query = $this->queryBuilder->insert($this->tableName, $entity, $options);
        return $this->doQuery($query, false);
    }

    /**
     * {@inheritDoc}
     */
    public function update($entity, $id = 0)
    {
        if (empty($id)){
            if (!method_exists($entity, "getId")) {
                throw new RepositoryException("Can't update an entity without an ID");
            }
            $id = $entity->getId();
        }
        $query = $this->queryBuilder->updateById($this->tableName, $id, $entity);
        return $this->doQuery($query, false);
    }

    /**
     * {@inheritDoc}
     */
    public function upsert($entity, array $options = [])
    {
        $query = $this->queryBuilder->upsert($this->tableName, $entity, $options);
        return $this->doQuery($query, false);
    }

    /**
     * {@inheritDoc}
     */
    public function updateBy(array $conditions, array $updateValues)
    {
        $query = $this->queryBuilder->updateBy($this->tableName, $conditions, $updateValues);
        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        $query = $this->queryBuilder->deleteById($this->tableName, $id);
        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteBy(array $conditions)
    {
        $query = $this->queryBuilder->deleteBy($this->tableName, $conditions);
        return $this->doQuery($query, false);
    }

    /**
     * {@inheritDoc}
     */
    public function aggregate(array $operations, array $conditions = [], array $options = [])
    {
        $query = $this->queryBuilder->aggregate($this->tableName, $operations, $conditions, $options);
        return $this->doQuery($query, false);
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $conditions = [], array $options = [])
    {
        $query = $this->queryBuilder->aggregate($this->tableName, $conditions, $options);
        return $this->doQuery($query, false);
    }

    /**
     * @param Query $query
     * @param bool $createEntity
     * @return object|array
     */
    protected function doQuery(Query $query, $createEntity = true)
    {
        return $this->storage->query($query, $createEntity? $this->entityName: "");
    }

} 