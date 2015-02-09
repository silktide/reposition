<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Storage;
use Silktide\Reposition\Hydrator\HydratorInterface;
use Silktide\Reposition\Query\Query;
use Silktide\Reposition\QueryBuilder\MongoQueryBuilder;
use Silktide\Reposition\QueryInterpreter\MongoQueryInterpreter;

/**
 *
 */
class MongoStorage implements StorageInterface
{

    protected $database;

    protected $builder;

    protected $interpreter;

    protected $hydrator;

    public function __construct(\MongoDB $database, MongoQueryBuilder $builder, MongoQueryInterpreter $interpreter, HydratorInterface $hydrator = null)
    {
        $this->database = $database;
        $this->builder = $builder;
        $this->interpreter = $interpreter;
        $this->hydrator = $hydrator;
    }

    public function getQueryBuilder()
    {
        return $this->builder;
    }

    public function query(Query $query, $entityClass)
    {
        $compiledQuery = $this->interpreter->interpret($query);

        $data = call_user_func_array(
            [$this->database->{$compiledQuery["table"]}, $compiledQuery["method"]],
            $compiledQuery["arguments"]
        );

        if ($this->hydrator instanceof HydratorInterface && !empty($entityClass)) {
            return $this->hydrator->hydrateAll($data, $entityClass);
        }
        return $data;
    }

} 