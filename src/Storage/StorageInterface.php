<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Storage;

use Silktide\Reposition\Query\Query;
use Silktide\Reposition\QueryBuilder\QueryBuilderInterface;
use Silktide\Reposition\Hydrator\HydratorInterface;

/**
 *
 */
interface StorageInterface
{

    /**
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder();

    /**
     * @param Query $query
     * @param string $entityClass
     * @return object
     */
    public function query(Query $query, $entityClass);

} 