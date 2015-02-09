<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\QueryBuilder;

use Silktide\Reposition\Query\FindQuery;
use Silktide\Reposition\Query\InsertQuery;
use Silktide\Reposition\Query\UpdateQuery;
use Silktide\Reposition\Query\DeleteQuery;
use Silktide\Reposition\Query\AggregationQuery;

/**
 *
 */
interface QueryBuilderInterface
{

    const PRIMARY_KEY = "'primary_key`";

    /**
     * @param string $table
     * @param array $filters
     * @param array $sort
     * @param int $limit
     * @return FindQuery
     */
    public function findBy($table, array $filters, array $sort = [], $limit = 0);

    /**
     * Convenience function wrapping findBy
     *
     * @param string $table
     * @param string|int $id
     * @return FindQuery
     */
    public function findById($table, $id);

    /**
     * @param string $table
     * @param array $filters
     * @param object|array $values
     * @return UpdateQuery
     */
    public function updateBy($table, array $filters, $values);

    /**
     * Convenience function wrapping updateBy
     *
     * @param string $table
     * @param string|int $id
     * @param object|array $values
     * @return UpdateQuery
     */
    public function updateById($table, $id, $values);

    /**
     * @param string $table
     * @param object|array $values
     * @param array $modifiers
     * @return InsertQuery
     */
    public function insert($table, $values, array $modifiers = []);

    /**
     * Convenience function wrapping insert
     *
     * @param string $table
     * @param object|array $values
     * @param array $modifiers
     * @return InsertQuery
     */
    public function replace($table, $values, array $modifiers = []);

    /**
     * @param string $table
     * @param object|array $values
     * @param array $modifiers
     * @return InsertQuery|UpdateQuery
     */
    public function upsert($table, $values, array $modifiers = []);

    /**
     * @param string $table
     * @param array $filters
     * @return DeleteQuery
     */
    public function deleteBy($table, array $filters);

    /**
     * Convenience function wrapping deleteBy
     *
     * @param string $table
     * @param string|int $id
     * @return mixed
     */
    public function deleteById($table, $id);

    /**
     * @param string $table
     * @param array $operations
     * @param array $filters
     * @param array $modifiers
     * @return AggregationQuery
     */
    public function aggregate($table, array $operations, array $filters = [], array $modifiers = []);

    /**
     * Convenience function wrapping aggregate
     *
     * @param string $table
     * @param array $filters
     * @param array $modifiers
     * @return AggregationQuery
     */
    public function count($table, array $filters = [], array $modifiers = []);

} 