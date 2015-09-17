<?php

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
     * @param array $filter
     * @param array $sort
     * @param int $limit
     * @param array $options
     * @return array
     */
    public function filter(array $filter, array $sort = [], $limit = 0, array $options = []);

    /**
     * @param object $entity
     * @param array $options
     * @return string|int - ID of the entity
     */
    public function save($entity, array $options = []);

    /**
     * @param string|int $id
     */
    public function delete($id);

    /**
     * Aggregation / Grouping
     *
     * @param array $conditions
     * @param array $groupBy
     * @return array
     */
    public function count(array $conditions = [], array $groupBy = []);
} 