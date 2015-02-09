<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Repository;

/**
 *
 */
interface RepositoryInterface 
{

    /**
     * @return string
     */
    public function getEntityName();

    /**
     * @param string|int $id
     * @return object
     */
    public function find($id);

    /**
     * @param array $conditions
     * @param array $sort
     * @param int $limit
     * @param array $options
     * @return array
     */
    public function filter(array $conditions, array $sort = [], $limit = 0, array $options = []);

    /**
     * @param object $entity
     * @param array $options
     * @return string|int - ID of the entity
     */
    public function insert($entity, array $options = []);

    /**
     * @param object $entity
     */
    public function update($entity);

    /**
     * @param object $entity
     * @param array $options
     * @return string|int - ID of the entity
     */
    public function upsert($entity, array $options = []);

    /**
     * @param array $conditions
     * @param array $updateValues
     * @return string|int - ID of the entity
     */
    public function updateBy(array $conditions, array $updateValues);

    /**
     * @param string|int $id
     */
    public function delete($id);

    /**
     * @param array $conditions
     */
    public function deleteBy(array $conditions);

    /**
     * Aggregation / Grouping
     *
     * @param array $operations
     * @param array $conditions
     * @param array $options
     * @return array
     */
    public function aggregate(array $operations, array $conditions = [], array $options = []);
} 