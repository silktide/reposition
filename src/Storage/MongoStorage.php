<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Storage;

use Silktide\Reposition\Exception\StorageException;
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

        // this mongo library writes new _ids to the array variable passed to it (by value)
        // so ... if we want to capture the new _id, we need to assign a variable that we can access directly

        $arguments = [];
        foreach ($compiledQuery->getArguments() as $arg) {
            $arguments[] = $arg;
        }

        try {
            $response = call_user_func_array(
                [$this->database->{$compiledQuery->getTable()}, $compiledQuery->getMethod()],
                $compiledQuery->getArguments()
            );
        } catch (\MongoException $e) {
            $response = $e;
        }

        if ($response instanceof \MongoCursor) {

            foreach ($compiledQuery->getCalls() as $call) {
                call_user_func_array([$response, $call[0]], $call[1]);
            }

            $data = [];
            foreach ($response as $document) {
                $data[] = $document;
            }

            if ($this->hydrator instanceof HydratorInterface && !empty($entityClass)) {
                $response = $this->hydrator->hydrateAll($data, $entityClass);
            } else  {
                $response = $data;
            }
            return $response;
        } elseif (is_array($response) && !empty($response["ok"])) {
            if (!empty($arguments[0]["_id"])) {
                return "" . $arguments[0]["_id"];
            }
            return true;
        }

        $errorMsg = "There was an error performing a '{$query->getAction()}' Mongo query on the '{$query->getTable()}' table";
        if ($response instanceof \Exception) {
            $errorMsg .= ": " . $response->getMessage();
        } elseif (!empty($response["err"])) {
            $errorMsg .= ": " . $response["err"];
        }
        throw new StorageException($errorMsg);

    }

} 