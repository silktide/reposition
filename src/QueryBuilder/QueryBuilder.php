<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\QueryBuilder;
use Silktide\Reposition\Exception\QueryException;
use Silktide\Reposition\Query\AggregationQuery;
use Silktide\Reposition\Query\DeleteQuery;
use Silktide\Reposition\Query\FindQuery;
use Silktide\Reposition\Query\InsertQuery;
use Silktide\Reposition\Query\UpdateQuery;

/**
 *
 */
class QueryBuilder implements QueryBuilderInterface
{
    protected $primaryKey = "id";

    public function findBy($table, array $filters, array $sort = [], $limit = null)
    {
        $find = new FindQuery($table);
        $find->setFilters($this->parseKeys($filters));
        $find->setSort($sort);
        $find->setLimit($limit);
        return $find;
    }

    public function findById($table, $id)
    {
        return $this->findBy($table, [$this->primaryKey => $id]);
    }

    public function findFirst($table, array $filters, array $sort = [])
    {
        return $this->findBy($table, $filters, $sort, 1);
    }

    public function updateBy($table, array $filters, $values)
    {
        $update = new UpdateQuery($table);
        $update->setFilters($this->parseKeys($filters));
        $update->setValues($this->parseValues($values));
        return $update;
    }

    public function updateById($table, $id, $values)
    {
        return $this->updateBy($table, [$this->primaryKey => $id], $values);
    }

    public function insert($table, $values, array $modifiers = [])
    {
        $insert = new InsertQuery($table);
        $values = $this->parseValues($values);
        if ($this->primaryKeyIsSet($values)) {
            // TODO: Should we allow primary keys to be inserted?
            throw new QueryException("Cannot insert a record which has a primary key. Use 'update' instead.");
        }
        $insert->setValues($values);
        $insert->setModifiers($modifiers);
        return $insert;
    }

    public function replace($table, $values, array $modifiers = [])
    {
        $modifiers["strategy"] = "replace";
        return $this->insert($table, $values, $modifiers);
    }

    public function upsert($table, $values, array $modifiers = [])
    {
        $values = $this->parseValues($values);
        if ($this->primaryKeyIsSet($values)) {
            return $this->updateById($table, $values[$this->primaryKey], $values);
        }
        return $this->insert($table, $values, $modifiers);
    }

    public function deleteBy($table, array $filters)
    {
        $delete = new DeleteQuery($table);
        $delete->setFilters($this->parseKeys($filters));
        return $delete;
    }

    public function deleteById($table, $id)
    {
        return $this->deleteBy($table, [$this->primaryKey => $id]);
    }

    public function aggregate($table, array $operations, array $filters = [], array $modifiers = [])
    {
        $aggregate = new AggregationQuery($table);
        $aggregate->setOperations($operations);
        $aggregate->setFilters($filters);
        $aggregate->setModifiers($modifiers);
        return $aggregate;
    }

    public function count($table, array $filters = [], array $modifiers = [])
    {
        return $this->aggregate($table, ["count" => "*"], $filters, $modifiers);
    }

    protected function parseKeys(array $filters)
    {
        foreach ($filters as $key => $filter) {
            if ($key == QueryBuilderInterface::PRIMARY_KEY) {
                $filters[$this->primaryKey] = $filter;
                unset($filters[$key]);
            }
        }
        return $filters;
    }

    protected function parseValues($values)
    {
        if (is_array($values)) {
            return $this->parseKeys($values);
        }
        if (is_object($values)) {
            if (method_exists($values, "toArray")) {
                return $this->parseKeys($values->toArray());
            }
            throw new QueryException("Values object does not implement the 'toArray' method");
        }
        throw new QueryException("Values are not an array or an object");
    }

    protected function primaryKeyIsSet(array $values)
    {
        return !empty($values[$this->primaryKey]);
    }
} 