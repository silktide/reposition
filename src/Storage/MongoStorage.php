<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Storage;

use Silktide\Reposition\Hydrator\HydratorInterface;
use Silktide\Reposition\Normaliser\NormaliserInterface;
use Silktide\Reposition\Query\Query;
use Silktide\Reposition\QueryBuilder\QueryBuilder;
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

    public function __construct(
        \MongoDB $database,
        QueryBuilder $builder,
        MongoQueryInterpreter $interpreter,
        HydratorInterface $hydrator = null,
        NormaliserInterface $normaliser = null
    ) {
        $this->database = $database;
        $this->builder = $builder;
        $this->interpreter = $interpreter;
        $this->hydrator = $hydrator;

        if (!empty($normaliser)) {
            $this->interpreter->setNormaliser($normaliser);
            if (!empty($hydrator)) {
                $this->hydrator->setNormaliser($normaliser);
            }
        }
    }

    public function getQueryBuilder()
    {
        return $this->builder;
    }

    public function query(Query $query, $entityClass)
    {
        $compiledQuery = $this->interpreter->interpret($query);

        $cursor = call_user_func_array(
            [$this->database->{$compiledQuery->getTable()}, $compiledQuery->getMethod()],
            $compiledQuery->getArguments()
        );

        foreach ($compiledQuery->getCalls() as $call) {
            call_user_func_array([$cursor, $call[0]], $call[1]);
        }

        $data = [];
        foreach ($cursor as $document) {
            $data[] = $document;
        }

        if ($this->hydrator instanceof HydratorInterface && !empty($entityClass)) {
            return $this->hydrator->hydrateAll($data, $entityClass);
        }
        return $data;
    }

} 